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

    public function test_grn_create_and_store_auto_link_originating_pr_and_po(): void
    {
        $po = $this->issuedPo('Finance', 5000, quantity: 4);
        $pr = $po->purchaseRequisition;

        $this->loginAs(Roles::PR_SR_EXEC);

        $createRes = $this->get(route('grn.create', ['pr_id' => $pr->id]));
        $createRes->assertOk();
        $createRes->assertSee($po->po_number);
        $createRes->assertSee($pr->pr_number);

        $this->post(route('grn.store'), [
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $pr->id,
            'received_qty' => 2,
        ])->assertRedirect(route('grn'));

        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame($pr->id, $grn->purchase_requisition_id);

        $this->get(route('grn'))
            ->assertOk()
            ->assertSee($po->po_number)
            ->assertSee($pr->pr_number);

        $this->get(route('grn.show', $grn))
            ->assertOk()
            ->assertSee($po->po_number)
            ->assertSee($pr->pr_number);
    }

    public function test_grn_rejects_mismatched_pr_and_po(): void
    {
        $po = $this->issuedPo('Finance', 5000, quantity: 4);
        $otherPr = $this->approvedPr('IT', 3000, 1);

        $this->loginAs(Roles::PR_SR_EXEC);

        $this->post(route('grn.store'), [
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $otherPr->id,
            'received_qty' => 1,
        ])->assertSessionHasErrors('purchase_requisition_id');

        $this->assertSame(0, Grn::where('purchase_order_id', $po->id)->count());
    }

    public function test_grn_captures_received_qty_invoice_number_and_quality_remarks(): void
    {
        $po = $this->issuedPo('IT', 4000, quantity: 10);

        $this->loginAs(Roles::PR_SR_EXEC);

        $this->get(route('grn.create'))
            ->assertOk()
            ->assertSee('Received Quantity')
            ->assertSee('Invoice Number')
            ->assertSee('Quality Remarks');

        $this->post(route('grn.store'), [
            'purchase_order_id' => $po->id,
            'received_qty' => 4,
            'invoice_number' => 'INV-2026-8841',
            'receipt_date' => '2026-11-11',
            'quality_remarks' => 'Sealed boxes verified without damage.',
        ])->assertRedirect(route('grn'));

        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame(4, (int) $grn->received_qty);
        $this->assertSame('INV-2026-8841', $grn->invoice_number);
        $this->assertSame('Sealed boxes verified without damage.', $grn->quality_remarks);

        $this->get(route('grn'))
            ->assertOk()
            ->assertSee('INV-2026-8841');

        $this->get(route('grn.show', $grn))
            ->assertOk()
            ->assertSee('Received Quantity')
            ->assertSee('Invoice Number')
            ->assertSee('Quality Remarks')
            ->assertSee('INV-2026-8841')
            ->assertSee('Sealed boxes verified without damage.');
    }

    public function test_grn_locks_all_fields_except_quantity_and_date_after_submission(): void
    {
        $po = $this->issuedPo('IT', 4000, quantity: 10);
        $otherPo = $this->issuedPo('Finance', 3000, quantity: 5);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), [
            'purchase_order_id' => $po->id,
            'received_qty' => 4,
            'invoice_number' => 'INV-ORIG-100',
            'receipt_date' => '2026-11-10',
            'quality_remarks' => 'Original inspection remarks.',
        ])->assertRedirect(route('grn'));

        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();

        // Edit page shows Quantity & Date editable, while locked fields are disabled
        $this->get(route('grn.edit', $grn))
            ->assertOk()
            ->assertSee('name="received_qty"', false)
            ->assertSee('name="receipt_date"', false)
            ->assertDontSee('name="invoice_number"', false)
            ->assertDontSee('name="quality_remarks"', false)
            ->assertDontSee('name="purchase_order_id"', false);

        // Tampering with locked fields during PUT must leave them untouched; only qty & date update
        $this->put(route('grn.update', $grn), [
            'purchase_order_id' => $otherPo->id,
            'purchase_requisition_id' => $otherPo->purchase_requisition_id,
            'received_qty' => 7,
            'receipt_date' => '2026-11-15',
            'invoice_number' => 'INV-TAMPERED',
            'quality_remarks' => 'Tampered remarks.',
        ])->assertRedirect(route('grn.show', $grn));

        $fresh = $grn->fresh();
        $this->assertSame(7, (int) $fresh->received_qty);
        $this->assertSame('2026-11-15', $fresh->receipt_date->format('Y-m-d'));
        $this->assertSame($po->id, $fresh->purchase_order_id);
        $this->assertSame($po->purchase_requisition_id, $fresh->purchase_requisition_id);
        $this->assertSame('INV-ORIG-100', $fresh->invoice_number);
        $this->assertSame('Original inspection remarks.', $fresh->quality_remarks);

        // Over-receipt on edit is still blocked
        $this->put(route('grn.update', $grn), [
            'received_qty' => 11,
            'receipt_date' => '2026-11-15',
        ])->assertSessionHasErrors('received_qty');
        $this->assertSame(7, (int) $grn->fresh()->received_qty);

        // Unauthorized role cannot edit
        $this->loginAs(Roles::BUYER);
        $this->get(route('grn.edit', $grn))->assertForbidden();
        $this->put(route('grn.update', $grn), [
            'received_qty' => 5,
            'receipt_date' => '2026-11-15',
        ])->assertForbidden();

        // Once approved by HOD, GRN is completely sealed
        $this->loginAs(Roles::HOD);
        $this->post(route('grn.approve', $grn))->assertRedirect();

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->get(route('grn.edit', $grn))->assertStatus(422);
        $this->put(route('grn.update', $grn), [
            'received_qty' => 5,
            'receipt_date' => '2026-11-16',
        ])->assertStatus(422);
    }
}
