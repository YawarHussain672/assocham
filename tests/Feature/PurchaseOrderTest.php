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
        $ccs = $this->approvedCcs($pr, $vendor, $quotedCost);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), ['cost_comparative_statement_id' => $ccs->id]);

        return PurchaseOrder::where('cost_comparative_statement_id', $ccs->id)->firstOrFail();
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

    public function test_vendor_and_amount_are_auto_populated_from_the_ccs_l1_quote(): void
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr('Finance', 1000, 1);
        $ccs = $this->approvedCcs($pr, $vendor, 7500);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), ['cost_comparative_statement_id' => $ccs->id]);

        $po = PurchaseOrder::where('cost_comparative_statement_id', $ccs->id)->firstOrFail();
        $this->assertSame($vendor->id, $po->vendor_id);
        $this->assertEqualsWithDelta(7500.0, (float) $po->amount_incl_gst, 0.01);
    }

    public function test_a_ccs_cannot_be_used_for_a_second_po(): void
    {
        $vendor = $this->empanelledVendor();
        $pr = $this->approvedPr('Finance', 1000, 1);
        $ccs = $this->approvedCcs($pr, $vendor, 5000);

        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), ['cost_comparative_statement_id' => $ccs->id])->assertRedirect();
        $this->post(route('po.store'), ['cost_comparative_statement_id' => $ccs->id])->assertStatus(422);

        $this->assertSame(1, PurchaseOrder::where('cost_comparative_statement_id', $ccs->id)->count());
    }
}
