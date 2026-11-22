<?php

namespace App\Http\Controllers;

use App\Mail\VendorEmpanelledMail;
use App\Models\Approval;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Vendor Empanelment — first real module of Phase 2 (see project memory:
 * assocham-portal-spec, erp-laravel-phase2-procurement). Replaces the
 * mock/localStorage VENDORS_RAW rendering in public/js/app.js's
 * initOnboardingPage()/initEmpanelmentPage() with real persistence.
 *
 * Workflow (deck p.9): Submit (Buyer) -> Admin Head Review -> L1 (ASG 1)
 * -> L2 (ASG 2) -> CFO (final/empanelled). Modeled as four rows in the
 * polymorphic `approvals` table, one per level, seeded on submission.
 */
class VendorController extends Controller
{
    /** level => role required to act at that step, level => vendor status while that step is pending. */
    private const CHAIN = [
        1 => Roles::ADMIN_HEAD,
        2 => Roles::ASG1,
        3 => Roles::ASG2,
        4 => Roles::CFO,
    ];

    private const STATUS_FOR_LEVEL = [
        1 => 'pending_admin_head',
        2 => 'pending_l1',
        3 => 'pending_l2',
        4 => 'pending_cfo',
    ];

    public function index(): View
    {
        $vendors = Vendor::with([
                'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
                'evaluations',
                'purchaseOrders',
            ])
            ->latest()
            ->get();

        return view('pages.empanelment', [
            'vendors' => $vendors,
            'canSubmit' => in_array(request()->user()?->role, [Roles::BUYER, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function create(): View
    {
        abort_unless(in_array(request()->user()?->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Unauthorized.');

        return view('pages.onboarding');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Unauthorized.');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_type' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'string'],
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'msme_status' => ['nullable'],
            'msme_number' => ['nullable', 'string', 'max:100'],
            'incorporation_certificate_no' => ['nullable', 'string', 'max:100'],
            'partnership_deed_no' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'previous_orders' => ['nullable', 'integer', 'min:0'],
            'declaration_accepted' => ['nullable'],
            'declaration_signatory_name' => ['nullable', 'string', 'max:255'],
            'declaration_designation' => ['nullable', 'string', 'max:255'],
            'declaration_date' => ['nullable', 'date'],
            'doc_entity_id' => ['nullable', 'file', 'max:10240'],
            'doc_cancelled_cheque' => ['nullable', 'file', 'max:10240'],
            'doc_tax_registration' => ['nullable', 'file', 'max:10240'],
            'doc_signature' => ['nullable', 'file', 'max:10240'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $fileKeys = ['doc_entity_id', 'doc_cancelled_cheque', 'doc_tax_registration', 'doc_signature', 'attachments'];
        $vendorPayload = collect($data)->except($fileKeys)->all();

        $vendor = Vendor::create([
            ...$vendorPayload,
            'vendor_code' => $this->nextVendorCode(),
            'msme_status' => $request->boolean('msme_status'),
            'declaration_accepted' => $request->boolean('declaration_accepted'),
            'declaration_date' => $data['declaration_date'] ?? now()->toDateString(),
            'status' => self::STATUS_FOR_LEVEL[1],
            'compliance_status' => 'active',
            'submitted_by' => $request->user()->id,
        ]);

        // Upload and link supporting documents
        $fileCategories = [
            'doc_entity_id' => 'Government ID / Incorporation Certificate',
            'doc_cancelled_cheque' => 'Banking Document / Cancelled Cheque',
            'doc_tax_registration' => 'Tax / PAN / GST / MSME Document',
            'doc_signature' => 'Authorized Signatory Signature / Stamp',
        ];

        foreach ($fileCategories as $inputKey => $docCategory) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                if ($file && $file->isValid()) {
                    $stored = \App\Support\StorageHelper::storeUploadedFile($file, 'vendor-documents');
                    $vendor->attachments()->create([
                        'file_path' => $stored['path'],
                        'disk' => $stored['disk'],
                        'original_name' => $file->getClientOriginalName() . ' (' . $docCategory . ')',
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $stored = \App\Support\StorageHelper::storeUploadedFile($file, 'vendor-documents');
                    $vendor->attachments()->create([
                        'file_path' => $stored['path'],
                        'disk' => $stored['disk'],
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }
        }

        foreach (self::CHAIN as $level => $role) {
            $vendor->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
        }

        return redirect()->route('empanelment')
            ->with('status', "Vendor {$vendor->vendor_code} submitted — awaiting Admin Head review.");
    }

    public function show(Vendor $vendor): View
    {
        $user = request()->user();
        abort_unless(
            $user && (
                Roles::canAccessModuleForUser($user, 'empanelment')
                || Roles::canAccessModuleForUser($user, 'rfq')
                || Roles::canAccessModuleForUser($user, 'po')
                || Roles::canAccessModuleForUser($user, 'traveleval')
            ),
            403,
            'Your account does not have access to vendor dossiers.'
        );

        $vendor->load([
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'submittedBy',
            'evaluations',
            'purchaseOrders',
            'attachments',
        ]);

        return view('pages.vendor-show', [
            'vendor' => $vendor,
            'pendingApproval' => $this->currentApprovalFor($vendor, request()->user()),
        ]);
    }

    public function downloadSelfDeclaration(Request $request, Vendor $vendor)
    {
        $user = $request->user();
        abort_unless(
            $user && (
                Roles::canAccessModuleForUser($user, 'empanelment')
                || Roles::canAccessModuleForUser($user, 'onboarding')
                || Roles::canAccessModuleForUser($user, 'requisition')
                || Roles::canAccessModuleForUser($user, 'rfq')
                || Roles::canAccessModuleForUser($user, 'po')
                || (int) $vendor->submitted_by === (int) $user->id
            ),
            403,
            'Your account does not have access to vendor dossiers.'
        );

        $vendor->loadMissing('attachments');
        $signatureAttachment = $vendor->signatureAttachment();

        return view('pages.vendor-self-declaration', [
            'vendor' => $vendor,
            'signature' => $signatureAttachment,
            'isTemplate' => false,
        ]);
    }

    public function downloadDeclarationTemplate()
    {
        return view('pages.vendor-self-declaration', [
            'vendor' => null,
            'signature' => null,
            'isTemplate' => true,
        ]);
    }

    public function approve(Request $request, Vendor $vendor): RedirectResponse
    {
        $approval = $this->currentApprovalFor($vendor, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this vendor matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $next = $vendor->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->first();
        $isFullyEmpanelled = !$next;
        $vendor->update(['status' => $next ? self::STATUS_FOR_LEVEL[$next->level] : 'approved']);

        if ($isFullyEmpanelled) {
            $emailStatus = $this->sendEmpanelmentEmail($vendor);
            $message = "Vendor {$vendor->vendor_code} is fully empanelled" . ($emailStatus ? " and notification email was sent to vendor ({$vendor->contact_email})." : ".");
        } else {
            $message = "Approved. Now awaiting {$next->role_required}.";
        }

        return back()->with('status', $message);
    }

    public function resendEmpanelmentEmail(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::ADMIN_HEAD, Roles::CFO, Roles::BUYER], true), 403, 'Unauthorized.');
        abort_unless($vendor->isEmpanelled(), 422, 'Cannot send empanelment email because this vendor is not yet fully empanelled.');

        if (empty($vendor->contact_email)) {
            return back()->with('error', "Vendor {$vendor->vendor_code} has no contact email address configured.");
        }

        $sent = $this->sendEmpanelmentEmail($vendor, true);
        if ($sent) {
            return back()->with('status', "Empanelment email successfully resent to {$vendor->contact_email}.");
        }

        return back()->with('error', "Failed to send email to {$vendor->contact_email}. Please check mail configuration.");
    }

    private function sendEmpanelmentEmail(Vendor $vendor, bool $isResend = false): bool
    {
        if (empty($vendor->contact_email)) {
            return false;
        }

        try {
            Mail::to($vendor->contact_email)->send(new VendorEmpanelledMail($vendor));
            $vendor->update(['empanelment_email_sent_at' => now()]);
            $action = $isResend ? 'empanelment_email_resent' : 'empanelment_email_sent';
            $vendor->writeAuditEntry($action, "Empanelment notification email successfully sent to {$vendor->contact_email}.");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send empanelment email to {$vendor->contact_email}: " . $e->getMessage());
            $vendor->writeAuditEntry('empanelment_email_failed', "Attempted to send empanelment email to {$vendor->contact_email} but failed: " . $e->getMessage());
            return false;
        }
    }

    public function hold(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $approval = $this->currentApprovalFor($vendor, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this vendor matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'on_hold',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $vendor->update(['status' => 'on_hold']);
        $vendor->writeAuditEntry('on_hold', "Vendor {$vendor->vendor_code} ({$vendor->company_name}) placed on hold by {$request->user()->name} (" . Roles::label($request->user()->role) . "): {$request->input('comments')}");

        return back()->with('status', "Vendor {$vendor->vendor_code} has been placed on hold.");
    }

    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($vendor, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this vendor matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $vendor->update(['status' => 'rejected']);

        return back()->with('status', "Vendor {$vendor->vendor_code} rejected.");
    }

    public function edit(Vendor $vendor): View
    {
        abort_unless(in_array(request()->user()?->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Unauthorized.');
        abort_if($vendor->isBlacklisted(), 403, 'This vendor is blacklisted and permanently locked from editing.');
        abort_if($vendor->status === 'approved', 422, 'Empanelled vendors cannot be edited directly.');

        return view('pages.vendor-edit', ['vendor' => $vendor]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Unauthorized.');
        abort_if($vendor->isBlacklisted(), 403, 'This vendor is blacklisted and permanently locked from editing.');
        abort_if($vendor->status === 'approved', 422, 'Empanelled vendors cannot be edited directly.');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_type' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'string'],
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'msme_status' => ['nullable'],
            'msme_number' => ['nullable', 'string', 'max:100'],
            'incorporation_certificate_no' => ['nullable', 'string', 'max:100'],
            'partnership_deed_no' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'previous_orders' => ['nullable', 'integer', 'min:0'],
            'declaration_accepted' => ['nullable'],
            'declaration_signatory_name' => ['nullable', 'string', 'max:255'],
            'declaration_designation' => ['nullable', 'string', 'max:255'],
            'declaration_date' => ['nullable', 'date'],
            'doc_entity_id' => ['nullable', 'file', 'max:10240'],
            'doc_cancelled_cheque' => ['nullable', 'file', 'max:10240'],
            'doc_tax_registration' => ['nullable', 'file', 'max:10240'],
            'doc_signature' => ['nullable', 'file', 'max:10240'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
            'resubmit' => ['nullable', 'boolean'],
        ]);

        $wasRejected = $vendor->status === 'rejected';
        $shouldResubmit = $wasRejected || $request->boolean('resubmit');

        $fileKeys = ['doc_entity_id', 'doc_cancelled_cheque', 'doc_tax_registration', 'doc_signature', 'attachments', 'resubmit'];
        $updatePayload = collect($data)->except($fileKeys)->all();
        $updatePayload['msme_status'] = $request->boolean('msme_status');
        $updatePayload['declaration_accepted'] = $request->boolean('declaration_accepted');

        // Upload and link new supporting documents if provided
        $fileCategories = [
            'doc_entity_id' => 'Government ID / Incorporation Certificate',
            'doc_cancelled_cheque' => 'Banking Document / Cancelled Cheque',
            'doc_tax_registration' => 'Tax / PAN / GST / MSME Document',
            'doc_signature' => 'Authorized Signatory Signature / Stamp',
        ];

        foreach ($fileCategories as $inputKey => $docCategory) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                if ($file && $file->isValid()) {
                    $stored = \App\Support\StorageHelper::storeUploadedFile($file, 'vendor-documents');
                    $vendor->attachments()->create([
                        'file_path' => $stored['path'],
                        'disk' => $stored['disk'],
                        'original_name' => $file->getClientOriginalName() . ' (' . $docCategory . ')',
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $stored = \App\Support\StorageHelper::storeUploadedFile($file, 'vendor-documents');
                    $vendor->attachments()->create([
                        'file_path' => $stored['path'],
                        'disk' => $stored['disk'],
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($shouldResubmit) {
            // Re-route through the 4-level empanelment approval chain starting from Admin Head
            $updatePayload['status'] = self::STATUS_FOR_LEVEL[1];
            
            // Reset existing approvals or re-seed them
            $vendor->approvals()->delete();
            foreach (self::CHAIN as $level => $role) {
                $vendor->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
            }
        }

        $vendor->update($updatePayload);

        $message = $shouldResubmit
            ? "Vendor {$vendor->vendor_code} corrected and re-submitted for executive empanelment review."
            : "Vendor {$vendor->vendor_code} application details updated.";

        $vendor->writeAuditEntry('updated', $message);

        return redirect()->route('empanelment.show', $vendor)->with('status', $message);
    }

    public function destroy(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Unauthorized.');
        abort_if($vendor->isBlacklisted(), 403, 'This vendor is blacklisted and permanently locked from deletion.');
        abort_if($vendor->status === 'approved', 422, 'Empanelled vendors cannot be deleted.');

        // Prevent deletion if already referenced in transactions
        if ($vendor->purchaseOrders()->exists()) {
            return back()->with('error', "Cannot delete Vendor {$vendor->vendor_code} because it is referenced in Purchase Orders.");
        }

        $code = $vendor->vendor_code;
        $name = $vendor->company_name;

        // Clean up approvals and audit
        $vendor->approvals()->delete();
        $vendor->writeAuditEntry('deleted', "Vendor {$code} ({$name}) was removed.");
        $vendor->delete();

        return redirect()->route('empanelment')
            ->with('status', "Vendor {$code} ({$name}) was deleted successfully.");
    }

    public function blacklist(Request $request, Vendor $vendor): RedirectResponse
    {
        $canBlacklist = $request->user()->role === Roles::ADMIN_HEAD
            || $this->currentApprovalFor($vendor, $request->user()) !== null;
        abort_unless($canBlacklist, 403, 'Unauthorized. Only Admin Head or the active approver can blacklist a vendor.');
        abort_if($vendor->isBlacklisted(), 422, 'This vendor is already blacklisted.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $vendor->update([
            'status' => 'blacklisted',
            'compliance_status' => 'blacklisted',
            'blacklist_reason' => $data['reason'],
            'blacklisted_at' => now(),
            'blacklisted_by' => $request->user()->id,
        ]);

        // Cancel any pending or on-hold approvals on this vendor
        $vendor->approvals()->whereIn('status', ['pending', 'on_hold'])->update([
            'status' => 'rejected',
            'comments' => 'Vendor blacklisted: ' . $data['reason'],
            'acted_at' => now(),
            'approver_id' => $request->user()->id,
        ]);

        $vendor->writeAuditEntry('blacklisted', "Vendor {$vendor->vendor_code} ({$vendor->company_name}) was blacklisted. Reason: {$data['reason']}");

        return redirect()->route('empanelment.show', $vendor)
            ->with('status', "Vendor {$vendor->vendor_code} has been blacklisted and permanently disqualified from all transactions.");
    }

    public function unblacklist(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Unauthorized. Only Admin Head can unblacklist a vendor.');
        abort_unless($vendor->isBlacklisted(), 422, 'This vendor is not currently blacklisted.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $vendor->update([
            'status' => self::STATUS_FOR_LEVEL[1],
            'compliance_status' => 'active',
            'blacklist_reason' => null,
            'blacklisted_at' => null,
            'blacklisted_by' => null,
        ]);

        // Model A: Re-seed fresh 4-level empanelment approval chain starting from Admin Head
        $vendor->approvals()->delete();
        foreach (self::CHAIN as $level => $role) {
            $vendor->approvals()->create([
                'level' => $level,
                'role_required' => $role,
                'status' => 'pending',
            ]);
        }

        $vendor->writeAuditEntry('unblacklisted', "Vendor {$vendor->vendor_code} ({$vendor->company_name}) was unblacklisted / reinstated. Approval cycle reset to Admin Head (Stage 1). Reason: {$data['reason']}");

        return redirect()->route('empanelment.show', $vendor)
            ->with('status', "Vendor {$vendor->vendor_code} has been successfully reinstated. The approval workflow has been reset to Admin Head (Stage 1).");
    }

    /** The one pending approval this user is actually allowed to act on right now, if any. */
    private function currentApprovalFor(Vendor $vendor, $user): ?Approval
    {
        return $vendor->currentApprovalFor($user);
    }

    private function nextVendorCode(): string
    {
        do {
            $code = 'VEN-'.random_int(1000, 9999);
        } while (Vendor::where('vendor_code', $code)->exists());

        return $code;
    }
}
