<?php

namespace Tests\Concerns;

use App\Models\CostComparativeStatement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Support\Roles;

/**
 * Walks a vendor/PR/RFQ/CCS all the way to an issued PO, reusing the real
 * controllers/routes (not direct model factories) so these helpers exercise
 * the same code path the manual curl verification did for each module.
 * Used by tests that need a fully-issued PO as their starting point (GRN,
 * Exception Handling's po_force_close/grn_reversal).
 */
trait BuildsProcurementChain
{
    protected function empanelledVendor(string $name = 'Test Traders Pvt Ltd'): Vendor
    {
        $this->loginAs(Roles::BUYER);
        $this->post(route('onboarding.store'), ['company_name' => $name, 'pan' => 'AABCT1234E']);
        $vendor = Vendor::where('company_name', $name)->firstOrFail();

        foreach ([Roles::ADMIN_HEAD, Roles::ASG1, Roles::ASG2, Roles::CFO] as $role) {
            $this->loginAs($role);
            $this->post(route('empanelment.approve', $vendor));
        }

        return $vendor->fresh();
    }

    protected function approvedPr(string $department = 'Projects', float $unitCost = 1000, int $quantity = 1): PurchaseRequisition
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => $department,
            'items' => [['item_name' => 'Item', 'quantity' => $quantity, 'unit_cost' => $unitCost, 'gst_percent' => 18]],
        ]);
        $pr = PurchaseRequisition::latest('id')->firstOrFail();

        foreach ([Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO, Roles::SG] as $role) {
            $this->loginAs($role);
            $this->post(route('requisition.approve', $pr));
        }

        return $pr->fresh();
    }

    protected function approvedCcs(PurchaseRequisition $pr, Vendor $vendor, float $quotedCost): CostComparativeStatement
    {
        $this->loginAs(Roles::BUYER);
        $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_ids' => [$vendor->id],
        ]);
        $rfq = $pr->rfqs()->latest('id')->firstOrFail();

        $this->post(route('rfq.quotes-received', $rfq));

        $this->post(route('ccs.store', $rfq), [
            'quotes' => [['vendor_id' => $vendor->id, 'quoted_cost' => $quotedCost, 'delivery_days' => 7]],
        ]);
        $ccs = CostComparativeStatement::where('rfq_id', $rfq->id)->firstOrFail();

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('ccs.approve', $ccs));

        return $ccs->fresh();
    }

    /** Builds and fully approves a PO (walks whatever dynamic chain applies) in one call. */
    protected function issuedPo(string $department = 'Projects', float $quotedCost = 5000, int $quantity = 1): PurchaseOrder
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr($department, $quotedCost / max($quantity, 1), $quantity);
        $ccs = $this->approvedCcs($pr, $vendor, $quotedCost);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), ['cost_comparative_statement_id' => $ccs->id]);
        $po = PurchaseOrder::where('cost_comparative_statement_id', $ccs->id)->firstOrFail();

        // Walk whatever chain PurchaseOrderController::buildChain() produced —
        // read it back from the DB rather than re-deriving the rule here, so
        // this helper stays correct even if the routing rule changes.
        foreach ($po->approvals()->orderBy('level')->pluck('role_required') as $role) {
            $this->loginAs($role);
            $this->post(route('po.approve', $po));
        }

        return $po->fresh();
    }
}
