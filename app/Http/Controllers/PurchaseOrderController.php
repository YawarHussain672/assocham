<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\CostComparativeStatement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PO Creation & Approval — fifth Phase 2 module (see project memory:
 * erp-laravel-phase2-procurement). Workflow (deck p.13): Create PO (Buyer)
 * -> Admin Head Review (non-IT items) -> CFO Approval (per DoA/value) ->
 * SG Approval (where applicable) -> PO Issued.
 *
 * Unlike every prior module, the approval chain isn't fixed — it's built
 * per PO from department + value, since the deck's permission matrix (p.7)
 * gives Admin Head "Approve (Non-IT Items)" and CFO "Approve ≥₹10L" as
 * conditional grants, not a flat 4-level ladder. See buildChain() for the
 * exact synthesis (including a documented judgment call on what "SG —
 * where applicable" means, since the deck doesn't spell that out).
 */
class PurchaseOrderController extends Controller
{
    /** ₹10,00,000 — the deck's high-value DoA threshold (matches PurchaseOrder::isHighValue()). */
    private const HIGH_VALUE_THRESHOLD = 1_000_000;

    public function index(): View
    {
        $pos = PurchaseOrder::with(['vendor', 'purchaseRequisition', 'approvals'])->latest()->get();

        return view('pages.po', [
            'pos' => $pos,
            'canCreate' => request()->user()->role === Roles::BUYER,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::BUYER, 403, 'Only the Buyer creates Purchase Orders.');

        $statements = CostComparativeStatement::where('status', 'approved')
            ->whereDoesntHave('purchaseOrder')
            ->with(['rfq.purchaseRequisition', 'quotes.vendor'])
            ->get();

        return view('pages.po-create', ['statements' => $statements]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::BUYER, 403, 'Only the Buyer creates Purchase Orders.');

        $data = $request->validate([
            'cost_comparative_statement_id' => ['required', 'exists:cost_comparative_statements,id'],
            'delivery_date' => ['nullable', 'date'],
        ]);

        $ccs = CostComparativeStatement::with(['rfq.purchaseRequisition.items', 'quotes.vendor'])
            ->findOrFail($data['cost_comparative_statement_id']);

        abort_unless($ccs->status === 'approved', 422, 'A PO can only be raised from an approved Cost Comparative Statement.');
        abort_if($ccs->purchaseOrder, 422, 'This CCS already has a Purchase Order.');

        $l1 = $ccs->lowestQuote();
        abort_unless($l1, 422, 'This CCS has no vendor quotes to raise a PO against.');

        $pr = $ccs->rfq->purchaseRequisition;

        $po = PurchaseOrder::create([
            'po_number' => $this->nextPoNumber(),
            'purchase_requisition_id' => $pr->id,
            'cost_comparative_statement_id' => $ccs->id,
            'vendor_id' => $l1->vendor_id, // auto-selected: CCS's L1 (lowest) quote is the documented justification
            'po_qty' => max((int) $pr->items->sum('quantity'), 1),
            'delivery_date' => $data['delivery_date'] ?? ($l1->delivery_days ? now()->addDays($l1->delivery_days)->toDateString() : null),
            'amount_incl_gst' => $l1->quoted_cost,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        foreach (array_values($this->buildChain($po, $pr)) as $i => $role) {
            $po->approvals()->create(['level' => $i + 1, 'role_required' => $role, 'status' => 'pending']);
        }

        return redirect()->route('po')
            ->with('status', "PO {$po->po_number} created for {$l1->vendor->company_name} — routing for approval.");
    }

    public function show(PurchaseOrder $po): View
    {
        $po->load(['vendor', 'purchaseRequisition', 'costComparativeStatement', 'approvals' => fn ($q) => $q->orderBy('level')]);

        return view('pages.po-show', [
            'po' => $po,
            'pendingApproval' => $this->currentApprovalFor($po, request()->user()),
        ]);
    }

    public function approve(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $approval = $this->currentApprovalFor($po, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PO matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $next = $po->approvals()->where('status', 'pending')->orderBy('level')->first();
        $po->update(['status' => $next ? 'pending' : 'approved']);

        $message = $next
            ? 'Approved. Now awaiting '.Roles::label($next->role_required).'.'
            : "PO {$po->po_number} is fully approved and issued to the vendor.";

        return back()->with('status', $message);
    }

    public function reject(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($po, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PO matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $po->update(['status' => 'rejected']);

        return back()->with('status', "PO {$po->po_number} rejected.");
    }

    /**
     * Synthesized from the deck's permission matrix (p.7) + the "Governance
     * Authority Matrix" reference screenshot (p.43, high-value procurement
     * requires "CFO budget clearing & SG sign-off"):
     *   - Admin Head reviews unless the PR's department is IT.
     *   - CFO + SG both approve when the PO is high-value (≥₹10L) — "SG
     *     where applicable" is read as "applicable at the same threshold
     *     CFO's approval kicks in", since the deck doesn't define "where
     *     applicable" more precisely.
     *   - Safety net: if department is IT AND the value is below the
     *     threshold, the rules above would leave zero approvers — that's
     *     never allowed to happen, so Admin Head is added as a fallback
     *     single reviewer rather than auto-issuing a PO with no sign-off.
     *
     * @return list<string> role slugs in approval order
     */
    private function buildChain(PurchaseOrder $po, PurchaseRequisition $pr): array
    {
        $steps = [];

        if ($pr->department !== 'IT') {
            $steps[] = Roles::ADMIN_HEAD;
        }

        if ((float) $po->amount_incl_gst >= self::HIGH_VALUE_THRESHOLD) {
            $steps[] = Roles::CFO;
            $steps[] = Roles::SG;
        }

        return $steps ?: [Roles::ADMIN_HEAD];
    }

    private function currentApprovalFor(PurchaseOrder $po, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists — a rejection at any level must be terminal.
        if ($po->status === 'rejected') {
            return null;
        }

        $next = $po->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextPoNumber(): string
    {
        do {
            $number = 'PO-'.now()->format('Y').'-'.random_int(1000, 9999);
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }
}
