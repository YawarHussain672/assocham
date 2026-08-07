<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GRN Creation & Approval — sixth and final Phase 2 (Procurement) module
 * (see project memory: erp-laravel-phase2-procurement). Workflow (deck
 * p.14): Goods Received (at site/store) -> Line-item Entry (qty, invoice,
 * remarks) -> HOD Approval (verifies qty & quality) -> GRN Closed.
 *
 * "CREATED / INITIATED BY: PR Creator / Same-Dept User" — modeled as
 * Roles::PR_SR_EXEC, the role that creates PRs in this system. Single-level
 * approval (HOD only), same polymorphic-approvals pattern as CCS.
 *
 * Deck p.43 (live-portal note): multiple GRNs may be raised against one PO
 * until its full po_qty is received — see PurchaseOrder::remainingQtyToReceive().
 */
class GrnController extends Controller
{
    public function index(): View
    {
        $grns = Grn::with(['purchaseOrder.vendor'])->latest()->get();

        return view('pages.grn', [
            'grns' => $grns,
            'canCreate' => request()->user()->role === Roles::PR_SR_EXEC,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::PR_SR_EXEC, 403, 'Only the PR creator logs Goods Receipt Notes.');

        $purchaseOrders = PurchaseOrder::where('status', 'approved')
            ->with(['vendor', 'purchaseRequisition.items', 'grns'])
            ->get()
            ->filter(fn (PurchaseOrder $po) => $po->remainingQtyToReceive() > 0)
            ->values();

        return view('pages.grn-create', ['purchaseOrders' => $purchaseOrders]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::PR_SR_EXEC, 403, 'Only the PR creator logs Goods Receipt Notes.');

        $data = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'received_qty' => ['required', 'integer', 'min:1'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'receipt_date' => ['nullable', 'date'],
            'quality_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $po = PurchaseOrder::with(['grns', 'purchaseRequisition.items'])->findOrFail($data['purchase_order_id']);
        abort_unless($po->status === 'approved', 422, 'GRNs can only be raised against an issued (approved) PO.');

        $remaining = $po->remainingQtyToReceive();
        if ($data['received_qty'] > $remaining) {
            return back()
                ->withErrors(['received_qty' => "Only {$remaining} of {$po->po_qty} on {$po->po_number} remain to be received."])
                ->withInput();
        }

        $grn = Grn::create([
            'grn_number' => $this->nextGrnNumber(),
            'purchase_order_id' => $po->id,
            'item_description' => $po->purchaseRequisition->items->pluck('item_name')->implode(', '),
            'po_qty' => $po->po_qty,
            'received_qty' => $data['received_qty'],
            'invoice_number' => $data['invoice_number'] ?? null,
            'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
            'quality_remarks' => $data['quality_remarks'] ?? null,
            'status' => 'pending_hod',
            'created_by' => $request->user()->id,
        ]);

        $grn->approvals()->create(['level' => 1, 'role_required' => Roles::HOD, 'status' => 'pending']);

        return redirect()->route('grn')->with('status', "GRN {$grn->grn_number} logged — awaiting HOD approval.");
    }

    public function show(Grn $grn): View
    {
        $grn->load(['purchaseOrder.vendor', 'purchaseOrder.purchaseRequisition', 'creator', 'approvals' => fn ($q) => $q->orderBy('level')]);

        return view('pages.grn-show', [
            'grn' => $grn,
            'pendingApproval' => $this->currentApprovalFor($grn, request()->user()),
        ]);
    }

    public function approve(Request $request, Grn $grn): RedirectResponse
    {
        $approval = $this->currentApprovalFor($grn, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this GRN matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $grn->update(['status' => 'approved']);

        return back()->with('status', "GRN {$grn->grn_number} approved and closed — payment sync can proceed.");
    }

    public function reject(Request $request, Grn $grn): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($grn, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this GRN matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $grn->update(['status' => 'rejected']);

        return back()->with('status', "GRN {$grn->grn_number} rejected — its quantity no longer counts toward the PO as received.");
    }

    private function currentApprovalFor(Grn $grn, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists (not currently exploitable with GRN's single approval
        // level, kept consistent regardless).
        if ($grn->status === 'rejected') {
            return null;
        }

        $next = $grn->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextGrnNumber(): string
    {
        do {
            $number = 'GRN-'.random_int(1000, 9999);
        } while (Grn::where('grn_number', $number)->exists());

        return $number;
    }
}
