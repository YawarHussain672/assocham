<?php

namespace App\Http\Controllers;

use App\Mail\RfqDispatchedMail;
use App\Models\Attachment;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\RfqVendor;
use App\Models\User;
use App\Models\Vendor;
use App\Support\DepartmentRouting;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * RFQ Creation — third Phase 2 module (see project memory:
 * erp-laravel-phase2-procurement). Simpler than Vendor Empanelment/PR: no
 * multi-level approval chain in the deck (p.11) — just "locked to an
 * approved PR" + multi-vendor dispatch, then it feeds Cost Comparative
 * Statement (not built yet) once quotes are back.
 */
class RfqController extends Controller
{
    public function index(): View
    {
        $rfqs = Rfq::with([
            'poc',
            'purchaseRequisition.items',
            'purchaseRequisition.projectBudget',
            'purchaseRequisition.purchaseOrders',
            'rfqVendors.vendor',
            'costComparativeStatement.quotes.vendor',
            'costComparativeStatement.selectedVendor',
        ])->latest()->get();

        return view('pages.rfq', [
            'rfqs' => $rfqs,
            'activeBuyers' => DepartmentRouting::activeBuyers(),
            'canCreate' => in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function create(): View
    {
        abort_unless(in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head creates RFQs.');

        $vendors = Vendor::empanelled()
            ->with(['evaluations.rater', 'purchaseOrders'])
            ->get()
            ->sortByDesc(function ($v) {
                return [
                    $v->average_rating ?? 0.0,
                    $v->total_orders_count,
                    $v->company_name,
                ];
            })
            ->values();

        // Top suggested vendors based on review ratings and completed orders
        $suggestedVendors = $vendors->filter(function ($v) {
            return ($v->average_rating ?? 0.0) >= 4.0;
        })->take(6)->values();

        // Fallback: If no vendors have >= 4.0, take top 3 rated available
        if ($suggestedVendors->isEmpty() && $vendors->isNotEmpty()) {
            $suggestedVendors = $vendors->take(3)->values();
        }

        return view('pages.rfq-create', [
            'purchaseRequisitions' => PurchaseRequisition::with('projectBudget')->whereIn('status', ['approved', 'partially_approved'])->orderBy('pr_number')->get(),
            'vendors' => $vendors,
            'suggestedVendors' => $suggestedVendors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head creates RFQs.');

        $data = $request->validate([
            'purchase_requisition_id' => ['required', 'exists:purchase_requisitions,id'],
            'sector' => ['required', 'string', 'max:100'],
            'project_name' => ['required', 'string', 'max:255', 'unique:rfqs,project_name'],
            'due_date' => ['nullable', 'date'],
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['exists:vendors,id'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ], [
            'project_name.unique' => 'The project name has already been taken. Duplicate project names are not allowed.',
        ]);

        $pr = PurchaseRequisition::findOrFail($data['purchase_requisition_id']);
        abort_unless($pr->status === 'approved', 422, 'RFQs can only be raised against an approved PR.');

        $selectedVendors = Vendor::whereIn('id', $data['vendor_ids'])->get();
        $blacklistedVendor = $selectedVendors->first(fn (Vendor $v) => $v->isBlacklisted());
        if ($blacklistedVendor) {
            $referer = (string) $request->header('referer', '');
            if (str_contains($referer, 'rfq/create')) {
                return back()
                    ->withErrors(['vendor_ids' => "Vendor '{$blacklistedVendor->company_name}' is blacklisted and not eligible for transactions."])
                    ->withInput();
            }
            abort(422, "Vendor '{$blacklistedVendor->company_name}' is blacklisted and not eligible for transactions.");
        }
        $invalidVendor = $selectedVendors->first(fn (Vendor $v) => ! $v->isEmpanelled());
        if ($invalidVendor) {
            return back()
                ->withErrors(['vendor_ids' => "Vendor '{$invalidVendor->company_name}' is not an active empanelled vendor and cannot be invited to an RFQ."])
                ->withInput();
        }

        $dueDate = !empty($data['due_date'])
            ? $data['due_date']
            : ($pr->required_by ? $pr->required_by->toDateString() : now()->addDays(7)->toDateString());

        $rfq = Rfq::create([
            'rfq_number' => $this->nextRfqNumber(),
            'sector' => $data['sector'],
            'project_name' => $data['project_name'],
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'poc_user_id' => $request->user()->id,
            'status' => 'dispatched',
            'email_dispatched_at' => now(),
        ]);

        // Handle uploaded file attachments if provided (S3 if credentials available, else public storage fallback)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $stored = \App\Support\StorageHelper::storeUploadedFile($file, 'rfq-attachments');
                    $rfq->attachments()->create([
                        'file_path' => $stored['path'],
                        'disk' => $stored['disk'],
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }
        }

        // Create all vendor dispatch records first so an SMTP outage never leaves an RFQ half-saved
        $rfq->load(['purchaseRequisition.items', 'attachments']);
        $vendors = Vendor::whereIn('id', $data['vendor_ids'])->get()->keyBy('id');
        $rfqVendorRows = [];
        foreach ($data['vendor_ids'] as $vendorId) {
            $rfqVendorRows[$vendorId] = $rfq->rfqVendors()->create([
                'vendor_id' => $vendorId,
                'response_status' => 'awaiting',
                'email_sent_at' => null,
            ]);
        }

        $emailedCount = 0;
        $mailTransportFailed = false;

        foreach ($data['vendor_ids'] as $vendorId) {
            if ($mailTransportFailed) {
                break;
            }

            $vendor = $vendors->get($vendorId);
            if ($vendor && ! empty($vendor->email)) {
                try {
                    Mail::to($vendor->email)->send(new RfqDispatchedMail($rfq, $vendor));
                    $rfqVendorRows[$vendorId]->update(['email_sent_at' => now()]);
                    $emailedCount++;
                } catch (\Throwable $e) {
                    $mailTransportFailed = true;
                    Log::error("Failed sending RFQ dispatch email to vendor {$vendor->id} ({$vendor->email}): " . $e->getMessage());
                }
            }
        }

        $rfq->writeAuditEntry('dispatched', "RFQ {$rfq->rfq_number} dispatched to " . count($data['vendor_ids']) . " vendor(s); {$emailedCount} email(s) sent successfully.");

        if ($mailTransportFailed) {
            return redirect()->route('rfq')
                ->with('warning', "RFQ {$rfq->rfq_number} created for " . count($data['vendor_ids']) . " vendor(s), but the mail server timed out or rejected delivery ({$emailedCount} sent). You can resend pending vendor emails from the RFQ details page.");
        }

        return redirect()->route('rfq')
            ->with('status', "RFQ {$rfq->rfq_number} dispatched with complete details sent to {$emailedCount} vendor(s) via email.");
    }

    public function show(Rfq $rfq): View
    {
        $rfq->load([
            'poc',
            'purchaseRequisition.items',
            'purchaseRequisition.attachments',
            'purchaseRequisition.projectBudget',
            'attachments.uploader',
            'rfqVendors.vendor',
            'costComparativeStatement.quotes.vendor',
            'costComparativeStatement.selectedVendor',
            'costComparativeStatement.selectedByUser',
        ]);

        return view('pages.rfq-show', [
            'rfq' => $rfq,
            'activeBuyers' => DepartmentRouting::activeBuyers(),
            'canManage' => in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function assignBuyer(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only a Buyer or Admin Head can reassign RFQ ownership.');

        $data = $request->validate([
            'poc_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newBuyer = User::whereKey($data['poc_user_id'])
            ->where('is_active', true)
            ->whereIn('role', [Roles::BUYER, Roles::ADMIN_HEAD])
            ->first();

        if (! $newBuyer) {
            return back()->with('error', 'Selected user must be an active Buyer or Admin Head.');
        }

        $previousName = $rfq->poc?->name ?? 'Unassigned';
        $rfq->update(['poc_user_id' => $newBuyer->id]);

        $rfq->writeAuditEntry(
            'reassigned',
            "Reassigned RFQ {$rfq->rfq_number} Buyer / POC from {$previousName} to {$newBuyer->name}."
        );

        return back()->with('status', "Assigned Buyer / POC for {$rfq->rfq_number} updated to {$newBuyer->name}.");
    }

    public function saveQuotations(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head can record quotations.');

        $data = $request->validate([
            'quotes' => ['required', 'array', 'min:1'],
            'quotes.*.vendor_id' => ['required', 'exists:vendors,id'],
            'quotes.*.basic_price' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.gst_amount' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.transportation_cost' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.delivery_days' => ['nullable', 'integer', 'min:0'],
            'quotes.*.warranty' => ['nullable', 'string', 'max:100'],
            'quotes.*.payment_terms' => ['nullable', 'string', 'max:100'],
            'quotes.*.technical_compliance' => ['nullable', 'string', 'max:50'],
            'quotes.*.quotation_validity' => ['nullable', 'string', 'max:50'],
            'quotes.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $ccs = $rfq->costComparativeStatement;
        if (!$ccs) {
            do {
                $ccsNumber = 'CCS-' . random_int(1000, 9999);
            } while (\App\Models\CostComparativeStatement::where('ccs_number', $ccsNumber)->exists());

            $ccs = \App\Models\CostComparativeStatement::create([
                'ccs_number' => $ccsNumber,
                'rfq_id' => $rfq->id,
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        // Filter and process quotes
        $entries = collect($data['quotes'])->map(function ($item) {
            $basic = (float) ($item['basic_price'] ?? 0);
            $gst = (float) ($item['gst_amount'] ?? 0);
            $transport = (float) ($item['transportation_cost'] ?? 0);
            $total = $basic + $gst + $transport;

            return [
                ...$item,
                'basic_price' => $basic,
                'gst_amount' => $gst,
                'transportation_cost' => $transport,
                'quoted_cost' => $total,
            ];
        });

        // Split between vendors with positive quotes and unquoted
        $withCost = $entries->filter(fn($q) => $q['quoted_cost'] > 0)->sortBy('quoted_cost')->values();
        $withoutCost = $entries->filter(fn($q) => $q['quoted_cost'] <= 0)->values();

        $ccs->quotes()->delete();

        foreach ($withCost as $idx => $q) {
            $ccs->quotes()->create([
                'vendor_id' => $q['vendor_id'],
                'quoted_cost' => $q['quoted_cost'],
                'basic_price' => $q['basic_price'],
                'gst_amount' => $q['gst_amount'],
                'transportation_cost' => $q['transportation_cost'],
                'delivery_days' => $q['delivery_days'] ?? null,
                'warranty' => $q['warranty'] ?? '1 Year',
                'payment_terms' => $q['payment_terms'] ?? '30 Days',
                'technical_compliance' => $q['technical_compliance'] ?? 'Yes',
                'quotation_validity' => $q['quotation_validity'] ?? '30 Days',
                'remarks' => $q['remarks'] ?? null,
                'rank' => 'L' . ($idx + 1),
            ]);

            // Update vendor response status to received
            $rfq->rfqVendors()->where('vendor_id', $q['vendor_id'])->update(['response_status' => 'received']);
        }

        foreach ($withoutCost as $q) {
            $ccs->quotes()->create([
                'vendor_id' => $q['vendor_id'],
                'quoted_cost' => 0,
                'basic_price' => 0,
                'gst_amount' => 0,
                'transportation_cost' => 0,
                'delivery_days' => $q['delivery_days'] ?? null,
                'warranty' => $q['warranty'] ?? null,
                'payment_terms' => $q['payment_terms'] ?? null,
                'technical_compliance' => $q['technical_compliance'] ?? null,
                'quotation_validity' => $q['quotation_validity'] ?? null,
                'remarks' => $q['remarks'] ?? null,
                'rank' => null,
            ]);
        }

        if ($rfq->status === 'dispatched' && $withCost->isNotEmpty()) {
            $rfq->update(['status' => 'quotes_received']);
        }

        return back()->with('status', 'Commercial quotation comparison table saved and evaluated successfully.');
    }

    public function markQuotesReceived(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head manages RFQs.');
        abort_unless($rfq->status === 'dispatched', 422, 'Only a dispatched RFQ can be marked as having quotes received.');

        $rfq->update(['status' => 'quotes_received']);

        return back()->with('status', "RFQ {$rfq->rfq_number} marked quotes received — ready for a Cost Comparative Statement.");
    }

    public function selectBestVendor(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head can select the best vendor.');

        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'selection_remarks' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'selection_remarks.required' => 'Please provide specific justification or remarks explaining why this vendor was chosen.',
            'selection_remarks.min' => 'Selection remarks must be at least 3 characters.',
        ]);

        $ccs = $rfq->costComparativeStatement;
        abort_unless($ccs, 422, 'Quotation matrix must be saved before selecting a vendor.');

        $vendor = Vendor::findOrFail($data['vendor_id']);
        abort_if($vendor->isBlacklisted(), 422, 'Cannot award RFQ: vendor is blacklisted and not eligible for transactions.');
        $quote = $ccs->quotes()->where('vendor_id', $vendor->id)->first();
        abort_unless($quote, 422, 'Selected vendor does not belong to this RFQ comparison.');

        // Update CCS state
        $ccs->update([
            'selected_vendor_id' => $vendor->id,
            'selection_remarks' => $data['selection_remarks'],
            'selected_by' => $user->id,
            'selected_at' => now(),
        ]);

        // Reset all quotes is_selected, and mark chosen one
        $ccs->quotes()->update(['is_selected' => false, 'selection_remarks' => null]);
        $quote->update([
            'is_selected' => true,
            'selection_remarks' => $data['selection_remarks'],
        ]);

        // Send award notification email to the selected vendor
        $awardEmailSent = false;
        if (!empty($vendor->email)) {
            try {
                Mail::to($vendor->email)->send(new \App\Mail\VendorRfqAwardedMail($rfq, $vendor, $ccs));
                $ccs->update(['award_email_sent_at' => now()]);
                $awardEmailSent = true;
            } catch (\Throwable $e) {
                Log::error("Failed sending RFQ award email to vendor {$vendor->id} ({$vendor->email}): " . $e->getMessage());
            }
        }

        $rfq->writeAuditEntry('vendor_selected', "Manually selected {$vendor->company_name} as best vendor for RFQ {$rfq->rfq_number}. Justification: {$data['selection_remarks']}" . ($awardEmailSent ? ' (Award email sent to vendor)' : ''));

        $statusMsg = "Vendor {$vendor->company_name} successfully selected as the awarded vendor for this project" . ($awardEmailSent ? " and award notification email sent to {$vendor->email}." : '.');

        return back()->with('status', $statusMsg);
    }

    public function resendAwardEmail(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head can resend award emails.');

        $ccs = $rfq->costComparativeStatement;
        abort_unless($ccs && $ccs->selected_vendor_id, 422, 'No awarded vendor has been selected for this RFQ.');

        $vendor = $ccs->selectedVendor;
        abort_unless($vendor && !empty($vendor->email), 422, "Vendor does not have an email address configured.");

        try {
            Mail::to($vendor->email)->send(new \App\Mail\VendorRfqAwardedMail($rfq, $vendor, $ccs));
            $ccs->update(['award_email_sent_at' => now()]);

            $rfq->writeAuditEntry('award_email_resent', "Award notification email resent to vendor {$vendor->company_name} ({$vendor->email}).");

            return back()->with('status', "Award notification email successfully resent to {$vendor->company_name} ({$vendor->email}).");
        } catch (\Throwable $e) {
            Log::error("Failed resending award email to vendor {$vendor->id}: " . $e->getMessage());
            return back()->with('error', "Failed sending award email: " . $e->getMessage());
        }
    }

    public function resendVendorEmail(Request $request, Rfq $rfq, Vendor $vendor): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();
        abort_unless($user && in_array($user->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head can resend RFQ emails.');

        abort_unless(!empty($vendor->email), 422, "Vendor {$vendor->company_name} does not have an email address configured.");

        $rfqVendor = $rfq->rfqVendors()->where('vendor_id', $vendor->id)->firstOrFail();

        $rfq->load(['purchaseRequisition.items', 'attachments']);

        try {
            Mail::to($vendor->email)->send(new RfqDispatchedMail($rfq, $vendor));
            $rfqVendor->update(['email_sent_at' => now()]);
            $rfq->update(['email_dispatched_at' => now()]);

            $rfq->writeAuditEntry('email_resent', "RFQ details email resent to vendor {$vendor->company_name} ({$vendor->email}).");

            return back()->with('status', "RFQ details email successfully resent to {$vendor->company_name} ({$vendor->email}).");
        } catch (\Throwable $e) {
            Log::error("Failed resending RFQ email to vendor {$vendor->id}: " . $e->getMessage());
            return back()->with('error', "Failed to send email to {$vendor->company_name}: " . $e->getMessage());
        }
    }

    private function nextRfqNumber(): string
    {
        do {
            $number = 'RFQ-'.random_int(1000, 9999);
        } while (Rfq::where('rfq_number', $number)->exists());

        return $number;
    }
}
