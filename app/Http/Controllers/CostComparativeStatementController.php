<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\CostComparativeStatement;
use App\Models\Rfq;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cost Comparative Statement — fourth Phase 2 module (see project memory:
 * erp-laravel-phase2-procurement). Workflow (deck p.12): Quotes Received
 * (per RFQ) -> Enter & Upload -> Compare (auto L1/L2 ranking by lowest
 * cost) -> Committee Approval -> Feeds PO.
 *
 * The deck requires "Procurement Committee" approval but that isn't one of
 * the spec's 10 named role logins — there's no dedicated committee login.
 * Admin Head is used as the approving role here (closest fit: they already
 * hold a review/oversight role ahead of ASG/CFO on Vendor Empanelment).
 * This is an interpretive choice, not verbatim from the deck — revisit if
 * a real "Procurement Committee" role/login is ever introduced.
 */
class CostComparativeStatementController extends Controller
{
    private const APPROVER_ROLE = Roles::ADMIN_HEAD;

    public function index(): View
    {
        $statements = CostComparativeStatement::with(['rfq.purchaseRequisition', 'quotes'])->latest()->get();

        return view('pages.ccs', [
            'statements' => $statements,
        ]);
    }

    public function create(Rfq $rfq): View
    {
        abort_unless(request()->user()->role === Roles::BUYER, 403, 'Only the Buyer prepares Cost Comparative Statements.');
        abort_unless($rfq->status === 'quotes_received', 422, 'Mark the RFQ as quotes received before preparing a CCS.');
        abort_if($rfq->costComparativeStatement, 422, 'This RFQ already has a Cost Comparative Statement.');

        $rfq->load('rfqVendors.vendor');

        return view('pages.ccs-create', ['rfq' => $rfq]);
    }

    public function store(Request $request, Rfq $rfq): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::BUYER, 403, 'Only the Buyer prepares Cost Comparative Statements.');
        abort_unless($rfq->status === 'quotes_received', 422, 'Mark the RFQ as quotes received before preparing a CCS.');
        abort_if($rfq->costComparativeStatement, 422, 'This RFQ already has a Cost Comparative Statement.');

        $data = $request->validate([
            'quotes' => ['required', 'array', 'min:1'],
            'quotes.*.vendor_id' => ['required', 'exists:vendors,id'],
            'quotes.*.quoted_cost' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.delivery_days' => ['nullable', 'integer', 'min:0'],
            'quotes.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $entries = collect($data['quotes'])->filter(fn ($q) => $q['quoted_cost'] !== null && $q['quoted_cost'] !== '');
        if ($entries->isEmpty()) {
            return back()->withErrors(['quotes' => 'Enter at least one vendor quote.'])->withInput();
        }

        $ccs = CostComparativeStatement::create([
            'ccs_number' => $this->nextCcsNumber(),
            'rfq_id' => $rfq->id,
            'status' => 'pending_committee',
        ]);

        // Automatic L1/L2 ranking: lowest quoted cost is L1, per the deck.
        $entries->sortBy('quoted_cost')->values()->each(function ($q, $i) use ($ccs) {
            $ccs->quotes()->create([
                'vendor_id' => $q['vendor_id'],
                'quoted_cost' => $q['quoted_cost'],
                'delivery_days' => $q['delivery_days'] ?? null,
                'remarks' => $q['remarks'] ?? null,
                'rank' => 'L'.($i + 1),
            ]);
        });

        $ccs->approvals()->create(['level' => 1, 'role_required' => self::APPROVER_ROLE, 'status' => 'pending']);

        return redirect()->route('ccs.show', $ccs)
            ->with('status', "CCS {$ccs->ccs_number} submitted for Procurement Committee (Admin Head) approval.");
    }

    public function show(CostComparativeStatement $ccs): View
    {
        $ccs->load(['rfq.purchaseRequisition', 'quotes.vendor', 'purchaseOrder', 'approvals' => fn ($q) => $q->orderBy('level')]);

        return view('pages.ccs-show', [
            'ccs' => $ccs,
            'pendingApproval' => $this->currentApprovalFor($ccs, request()->user()),
        ]);
    }

    public function approve(Request $request, CostComparativeStatement $ccs): RedirectResponse
    {
        $approval = $this->currentApprovalFor($ccs, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this CCS matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $ccs->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $ccs->rfq->update(['status' => 'approved']);

        return back()->with('status', "CCS {$ccs->ccs_number} approved — ready for PO Creation against ".$ccs->lowestQuote()?->vendor?->company_name.'.');
    }

    public function reject(Request $request, CostComparativeStatement $ccs): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($ccs, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this CCS matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $ccs->update(['status' => 'rejected']);

        return back()->with('status', "CCS {$ccs->ccs_number} rejected.");
    }

    private function currentApprovalFor(CostComparativeStatement $ccs, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists — a rejection must be terminal (not currently exploitable
        // with CCS's single approval level, but kept consistent in case a
        // multi-level chain is ever added here).
        if ($ccs->status === 'rejected') {
            return null;
        }

        $next = $ccs->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextCcsNumber(): string
    {
        do {
            $number = 'CCS-'.random_int(1000, 9999);
        } while (CostComparativeStatement::where('ccs_number', $number)->exists());

        return $number;
    }
}
