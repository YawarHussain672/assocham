<?php

namespace Tests\Feature;

use App\Models\ExceptionRequest;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies Exception Handling's type-based approver routing and its real
 * "Action Applied" mechanics for po_force_close/grn_reversal (see project
 * memory: erp-laravel-phase4-governance) — originally verified by hand via
 * curl, including confirming the GRN reversal has a genuine downstream
 * effect on the PO's received-quantity count, not just a status flip.
 */
class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;
    use BuildsProcurementChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_only_admin_head_may_file_an_exception(): void
    {
        $this->loginAs(Roles::BUYER);

        $this->post(route('exceptions.store'), [
            'exception_type' => 'single_quotation',
            'justification' => 'Urgent',
        ])->assertForbidden();
    }

    public function test_budget_exceed_routes_to_cfo_not_sg(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $this->post(route('exceptions.store'), [
            'exception_type' => 'budget_exceed',
            'justification' => 'Marketing needs emergency campaign spend above allocation',
        ]);

        $exception = ExceptionRequest::latest('id')->firstOrFail();
        $this->assertSame(Roles::CFO, $exception->approver_queue);

        $this->loginAs(Roles::SG);
        $this->post(route('exceptions.approve', $exception))->assertForbidden();
    }

    public function test_po_force_close_actually_approves_the_stuck_po(): void
    {
        // Build a PO but never walk its approval chain — it's stuck pending.
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr('IT', 5000, 1);
        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 5000,
        ]);
        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
        $this->assertSame('pending', $po->status);

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('exceptions.store'), [
            'exception_type' => 'po_force_close',
            'reference_type' => 'purchase_orders',
            'reference_id' => $po->id,
            'justification' => 'Vendor needs urgent confirmation',
        ]);
        $exception = ExceptionRequest::latest('id')->firstOrFail();
        $this->assertSame(Roles::SG, $exception->approver_queue);

        $this->loginAs(Roles::SG);
        $this->post(route('exceptions.approve', $exception))->assertRedirect();

        $this->assertSame('approved', $po->fresh()->status);
        $this->assertFalse($po->approvals()->where('status', 'pending')->exists());
    }

    public function test_grn_reversal_reduces_the_pos_received_quantity(): void
    {
        $po = $this->issuedPo('HR', 2000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 3]);
        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();
        $this->loginAs(Roles::HOD);
        $this->post(route('grn.approve', $grn));

        $this->assertSame(2, $po->fresh()->remainingQtyToReceive());

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('exceptions.store'), [
            'exception_type' => 'grn_reversal',
            'reference_type' => 'grns',
            'reference_id' => $grn->id,
            'justification' => 'Quality issue discovered post-approval',
        ]);
        $exception = ExceptionRequest::latest('id')->firstOrFail();

        $this->loginAs(Roles::SG);
        $this->post(route('exceptions.approve', $exception))->assertRedirect();

        $this->assertSame('rejected', $grn->fresh()->status);
        // The reversed GRN's 3 units no longer count — back up to 5 remaining.
        $this->assertSame(5, $po->fresh()->remainingQtyToReceive());
    }
}
