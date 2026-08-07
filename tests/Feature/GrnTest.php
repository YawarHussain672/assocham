<?php

namespace Tests\Feature;

use App\Models\Grn;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies GRN's "multiple GRNs until fully received" quantity tracking
 * (see project memory: erp-laravel-phase2-procurement) — originally
 * verified by hand: partial receipt of 3/5, blocked over-receipt attempt
 * (0 new rows), correct remaining 2, PO drops out of the create list once
 * fully received, HOD-only approval.
 */
class GrnTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;
    use BuildsProcurementChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_partial_receipt_leaves_the_remainder_outstanding(): void
    {
        $po = $this->issuedPo('HR', 2000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 3])
            ->assertRedirect(route('grn'));

        $this->assertSame(2, $po->fresh()->remainingQtyToReceive());
    }

    public function test_over_receipt_is_blocked_and_writes_nothing(): void
    {
        $po = $this->issuedPo('HR', 2000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 3]);

        // Only 2 remain — asking for 3 more must be rejected outright.
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 3])
            ->assertSessionHasErrors('received_qty');

        $this->assertSame(1, Grn::where('purchase_order_id', $po->id)->count());
    }

    public function test_po_drops_out_of_the_create_list_once_fully_received(): void
    {
        $po = $this->issuedPo('HR', 2000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 5]);

        $this->assertSame(0, $po->fresh()->remainingQtyToReceive());

        $response = $this->get(route('grn.create'));
        $response->assertOk();
        $response->assertDontSee($po->po_number);
    }

    public function test_only_hod_may_approve_a_grn(): void
    {
        $po = $this->issuedPo('HR', 2000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), ['purchase_order_id' => $po->id, 'received_qty' => 5]);
        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();

        $this->loginAs(Roles::BUYER);
        $this->post(route('grn.approve', $grn))->assertForbidden();

        $this->loginAs(Roles::HOD);
        $this->post(route('grn.approve', $grn))->assertRedirect();

        $this->assertSame('approved', $grn->fresh()->status);
    }
}
