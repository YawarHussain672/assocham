<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies PO Creation's *dynamic* approval routing — the first module
 * with a chain built per-record from department + value rather than a
 * fixed level map (see project memory: erp-laravel-phase2-procurement).
 * Originally verified by hand via curl across three scenarios; same three
 * here, checked against the actual `approvals` rows, not just HTTP codes.
 */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;
    use BuildsProcurementChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createPo(string $department, float $quotedCost): PurchaseOrder
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr($department, $quotedCost, 1);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => $quotedCost,
        ]);

        return PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
    }

    public function test_non_it_low_value_po_routes_to_admin_head_only(): void
    {
        $po = $this->createPo('Finance', 42000);

        $this->assertSame(
            [Roles::ADMIN_HEAD],
            $po->approvals()->orderBy('level')->pluck('role_required')->all()
        );
    }

    public function test_non_it_high_value_po_routes_admin_head_then_cfo_then_sg(): void
    {
        $po = $this->createPo('Projects', 1_200_000);

        $this->assertSame(
            [Roles::ADMIN_HEAD, Roles::CFO, Roles::SG],
            $po->approvals()->orderBy('level')->pluck('role_required')->all()
        );
    }

    public function test_it_low_value_po_falls_back_to_admin_head_only(): void
    {
        // IT would normally skip Admin Head, and low value skips CFO/SG —
        // the safety net must still leave exactly one approver, never zero.
        $po = $this->createPo('IT', 5000);

        $this->assertSame(
            [Roles::ADMIN_HEAD],
            $po->approvals()->orderBy('level')->pluck('role_required')->all()
        );
    }

    public function test_cfo_cannot_jump_ahead_of_admin_head_on_a_high_value_po(): void
    {
        $po = $this->createPo('Projects', 1_200_000);

        $this->loginAs(Roles::CFO);
        $this->post(route('po.approve', $po))->assertForbidden();
    }

    public function test_vendor_and_amount_are_populated_on_po_creation(): void
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr('Finance', 1000, 1);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 7500,
        ]);

        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
        $this->assertSame($vendor->id, $po->vendor_id);
        $this->assertEqualsWithDelta(7500.0, (float) $po->amount_incl_gst, 0.01);
    }

    public function test_a_pr_cannot_be_used_for_a_second_po(): void
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr('Finance', 1000, 1);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 5000,
        ])->assertRedirect();

        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 5000,
        ])->assertStatus(422);

        $this->assertSame(1, PurchaseOrder::where('purchase_requisition_id', $pr->id)->count());
    }

    public function test_editing_approved_po_resets_status_to_pending_and_enables_regenerate_after_reapproval(): void
    {
        $po = $this->createPo('Finance', 42000);

        // Initial approval by Admin Head
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('po.approve', $po))->assertRedirect();
        $po->refresh();
        $this->assertSame('approved', $po->status);
        $this->assertFalse($po->canRegenerate());

        // Buyer edits the approved PO -> status & approval steps must reset to pending
        $this->loginAs(Roles::BUYER);
        $this->put(route('po.update', $po), [
            'po_qty' => 3,
            'delivery_date' => now()->addDays(10)->toDateString(),
        ])->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame('pending', $po->status);
        $this->assertTrue((bool) $po->needs_regeneration);
        $this->assertFalse($po->canRegenerate());
        $this->assertSame('pending', $po->approvals()->first()->status);

        // Regenerate PO is blocked while pending
        $this->post(route('po.regenerate', $po))->assertStatus(422);
        $this->get(route('po.show', $po))->assertOk()->assertDontSee('Regenerate PO');

        // Admin Head re-approves the edited PO
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('po.approve', $po))->assertRedirect();

        $po->refresh();
        $this->assertSame('approved', $po->status);
        $this->assertTrue($po->canRegenerate());

        // Regenerate PO option is now visible on detail and table pages
        $this->loginAs(Roles::BUYER);
        $this->get(route('po.show', $po))->assertOk()->assertSee('Regenerate PO');
        $this->get(route('po'))->assertOk()->assertSee('Regenerate PO');

        // Trigger Regenerate PO
        $this->post(route('po.regenerate', $po))->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertFalse((bool) $po->needs_regeneration);
        $this->assertSame(1, (int) $po->revision_number);
        $this->assertNotNull($po->regenerated_at);

        $this->get(route('po.show', $po))->assertOk()->assertSee('Rev 1');
    }

    public function test_editing_approved_po_terms_resets_status_to_pending(): void
    {
        $po = $this->createPo('Finance', 42000);

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('po.approve', $po))->assertRedirect();
        $this->assertSame('approved', $po->fresh()->status);

        $this->post(route('po.update-terms', $po), [
            'payment_terms' => '100% within 30 days',
        ])->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame('pending', $po->status);
        $this->assertTrue((bool) $po->needs_regeneration);
        $this->assertSame('pending', $po->approvals()->first()->status);
    }
}
