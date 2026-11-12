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
        $grns = Grn::with(['purchaseOrder.vendor', 'purchaseOrder.purchaseRequisition', 'purchaseRequisition'])->latest()->get();

        return view('pages.grn', [
            'grns' => $grns,
            'canCreate' => in_array(request()->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR creator or Admin Head logs Goods Receipt Notes.');

        $purchaseOrders = PurchaseOrder::where('status', 'approved')
            ->with(['vendor', 'purchaseRequisition.items', 'grns'])
            ->get()
            ->filter(fn (PurchaseOrder $po) => $po->remainingQtyToReceive() > 0)
            ->values();

        $purchaseRequisitions = $purchaseOrders
            ->pluck('purchaseRequisition')
            ->filter()
            ->unique('id')
            ->values();

        $selectedPoId = $request->input('po_id') ?? $request->input('po');
        $selectedPrId = $request->input('pr_id');

        if (!$selectedPoId && $selectedPrId) {
            $selectedPoId = optional($purchaseOrders->firstWhere('purchase_requisition_id', (int) $selectedPrId))->id;
        }
        if ($selectedPoId && !$selectedPrId) {
            $selectedPrId = optional($purchaseOrders->firstWhere('id', (int) $selectedPoId))->purchase_requisition_id;
        }

        return view('pages.grn-create', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseRequisitions' => $purchaseRequisitions,
            'selectedPoId' => $selectedPoId,
            'selectedPrId' => $selectedPrId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR creator or Admin Head logs Goods Receipt Notes.');

        $data = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'purchase_requisition_id' => ['nullable', 'exists:purchase_requisitions,id'],
            'received_qty' => ['required', 'integer', 'min:1'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'receipt_date' => ['nullable', 'date'],
            'quality_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $po = PurchaseOrder::with(['grns', 'purchaseRequisition.items'])->findOrFail($data['purchase_order_id']);
        abort_unless($po->status === 'approved', 422, 'GRNs can only be raised against an issued (approved) PO.');

        if (!empty($data['purchase_requisition_id']) && (int) $data['purchase_requisition_id'] !== (int) $po->purchase_requisition_id) {
            return back()
                ->withErrors(['purchase_requisition_id' => "Selected PR does not match the originating PR ({$po->purchaseRequisition->pr_number}) for {$po->po_number}."])
                ->withInput();
        }

        $remaining = $po->remainingQtyToReceive();
        if ($data['received_qty'] > $remaining) {
            return back()
                ->withErrors(['received_qty' => "Only {$remaining} of {$po->po_qty} on {$po->po_number} remain to be received."])
                ->withInput();
        }

        $grn = Grn::create([
            'grn_number' => $this->nextGrnNumber(),
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $po->purchase_requisition_id,
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
        $grn->load([
            'purchaseOrder.vendor',
            'purchaseOrder.purchaseRequisition.items',
            'purchaseOrder.purchaseRequisition.creator',
            'purchaseRequisition.items',
            'purchaseRequisition.creator',
            'creator',
            'approvals.approver',
        ]);

        return view('pages.grn-show', [
            'grn' => $grn,
            'pendingApproval' => $this->currentApprovalFor($grn, request()->user()),
        ]);
    }

    public function edit(Grn $grn): View
    {
        abort_unless(in_array(request()->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR creator or Admin Head may edit Goods Receipt Notes.');
        abort_unless($grn->status === 'pending_hod', 422, 'This GRN has already been finalized and is locked against edits.');

        $grn->load([
            'purchaseOrder.vendor',
            'purchaseOrder.grns',
            'purchaseOrder.purchaseRequisition',
            'purchaseRequisition',
        ]);

        $maxEditableQty = $grn->purchaseOrder
            ? ($grn->purchaseOrder->remainingQtyToReceive() + $grn->received_qty)
            : $grn->po_qty;

        return view('pages.grn-edit', [
            'grn' => $grn,
            'maxEditableQty' => max($maxEditableQty, 1),
        ]);
    }

    public function update(Request $request, Grn $grn): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR creator or Admin Head may edit Goods Receipt Notes.');
        abort_unless($grn->status === 'pending_hod', 422, 'This GRN has already been finalized and is locked against edits.');

        // Lock after submission: only Quantity (received_qty) and Date (receipt_date)
        // remain editable. PO, PR, Invoice Number, Quality Remarks, and Item fields are locked.
        $data = $request->validate([
            'received_qty' => ['required', 'integer', 'min:1'],
            'receipt_date' => ['required', 'date'],
        ]);

        $po = $grn->purchaseOrder()->with('grns')->firstOrFail();
        $maxEditableQty = $po->remainingQtyToReceive() + $grn->received_qty;

        if ($data['received_qty'] > $maxEditableQty) {
            return back()
                ->withErrors(['received_qty' => "Only {$maxEditableQty} of {$po->po_qty} on {$po->po_number} remain available for this receipt."])
                ->withInput();
        }

        $grn->update([
            'received_qty' => $data['received_qty'],
            'receipt_date' => $data['receipt_date'],
        ]);

        return redirect()->route('grn.show', $grn)
            ->with('status', "GRN {$grn->grn_number} updated — Quantity and Date saved (all other fields locked).");
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

        if (!$next) {
            return null;
        }

        if ($next->role_required === $user->role || $user->role === Roles::ADMIN_HEAD) {
            return $next;
        }

        return null;
    }

    private function nextGrnNumber(): string
    {
        do {
            $number = 'GRN-'.random_int(1000, 9999);
        } while (Grn::where('grn_number', $number)->exists());

        return $number;
    }
}
