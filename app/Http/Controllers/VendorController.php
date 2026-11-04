<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $vendors = Vendor::with(['approvals' => fn ($q) => $q->orderBy('level')])
            ->latest()
            ->get();

        return view('pages.empanelment', [
            'vendors' => $vendors,
            'canSubmit' => request()->user()->role === Roles::BUYER,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::BUYER, 403, 'Only the Buyer submits new vendors.');

        return view('pages.onboarding');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::BUYER, 403, 'Only the Buyer submits new vendors.');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
        ]);

        $vendor = Vendor::create([
            ...$data,
            'vendor_code' => $this->nextVendorCode(),
            'msme_status' => $request->boolean('msme_status'),
            'status' => self::STATUS_FOR_LEVEL[1],
            'compliance_status' => 'active',
            'submitted_by' => $request->user()->id,
        ]);

        foreach (self::CHAIN as $level => $role) {
            $vendor->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
        }

        return redirect()->route('empanelment')
            ->with('status', "Vendor {$vendor->vendor_code} submitted — awaiting Admin Head review.");
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['approvals' => fn ($q) => $q->orderBy('level')->with('approver'), 'submittedBy']);

        return view('pages.vendor-show', [
            'vendor' => $vendor,
            'pendingApproval' => $this->currentApprovalFor($vendor, request()->user()),
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

        $next = $vendor->approvals()->where('status', 'pending')->orderBy('level')->first();
        $vendor->update(['status' => $next ? self::STATUS_FOR_LEVEL[$next->level] : 'approved']);

        $message = $next
            ? "Approved. Now awaiting {$next->role_required}."
            : "Vendor {$vendor->vendor_code} is fully empanelled.";

        return back()->with('status', $message);
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

    /** The one pending approval this user is actually allowed to act on right now, if any. */
    private function currentApprovalFor(Vendor $vendor, $user): ?Approval
    {
        // A rejection at any level is terminal — without this check, a later
        // level's row is still nominally 'pending' in the DB (only the
        // rejected level's own row changed status), so a later-level
        // approver could otherwise still act and even resurrect the
        // vendor's status back out of 'rejected'. Caught by
        // VendorEmpanelmentTest::test_rejection_at_any_level_is_terminal.
        if ($vendor->status === 'rejected') {
            return null;
        }

        $next = $vendor->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextVendorCode(): string
    {
        do {
            $code = 'VEN-'.random_int(1000, 9999);
        } while (Vendor::where('vendor_code', $code)->exists());

        return $code;
    }
}
