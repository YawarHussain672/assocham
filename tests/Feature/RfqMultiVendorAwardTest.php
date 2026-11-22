<?php

namespace Tests\Feature;

use App\Models\CostComparativeStatement;
use App\Models\DepartmentBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqMultiVendorAwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_rfq_page_renders_item_wise_vendor_selection_and_multi_po_controls(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-MULT-A',
            'company_name' => 'Tech Solutions India',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'tech@solutions.test',
        ]);

        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-MULT-B',
            'company_name' => 'Apex Event Staging',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'apex@staging.test',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-MULTI-001',
            'department' => 'IT',
            'title' => 'Annual Summit Infrastructure',
            'estimated_value' => 250000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        $item1 = $pr->items()->create([
            'item_name' => 'High-End Laptops',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 60000,
            'gst_percent' => 18,
            'expense_type' => 'copex',
            'status' => 'approved',
        ]);

        $item2 = $pr->items()->create([
            'item_name' => 'Stage LED Wall',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 80000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-MULTI-001',
            'sector' => 'IT',
            'project_name' => 'Annual Summit Infrastructure',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'quotes_received',
        ]);

        $rfq->rfqVendors()->create(['vendor_id' => $vendorA->id, 'response_status' => 'submitted']);
        $rfq->rfqVendors()->create(['vendor_id' => $vendorB->id, 'response_status' => 'submitted']);

        $ccs = CostComparativeStatement::create([
            'ccs_number' => 'CCS-MULTI-001',
            'rfq_id' => $rfq->id,
            'status' => 'approved',
        ]);

        $ccs->quotes()->create([
            'vendor_id' => $vendorA->id,
            'quoted_cost' => 141600,
            'basic_price' => 120000,
            'gst_amount' => 21600,
            'rank' => 'L1',
        ]);

        $ccs->quotes()->create([
            'vendor_id' => $vendorB->id,
            'quoted_cost' => 160000,
            'basic_price' => 135593.22,
            'gst_amount' => 24406.78,
            'rank' => 'L2',
        ]);

        $response = $this->actingAs($buyer)->get(route('rfq.show', $rfq));
        $response->assertOk();
        $response->assertSee('Multi-Vendor Awards');
        $response->assertSee('AWARDED VENDOR &amp; PO', false);
        $response->assertSee("item_vendors[{$item1->id}]", false);
        $response->assertSee("item_vendors[{$item2->id}]", false);
        $response->assertSee('Generate Separate POs');
    }

    public function test_rfq_generates_separate_pos_for_each_vendor_covering_only_their_awarded_items(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $fy = DepartmentBudget::currentFiscalYear();
        DepartmentBudget::firstOrCreate(
            ['department' => 'IT', 'fiscal_year' => $fy],
            ['allocated_amount' => 5000000, 'utilised_amount' => 100000, 'gst_rate' => 18]
        );

        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-SEPARATE-A',
            'company_name' => 'Dell Enterprise India',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'dell@enterprise.test',
        ]);

        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-SEPARATE-B',
            'company_name' => 'Sony AV Solutions',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'sony@av.test',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-SPLIT-RFQ-01',
            'department' => 'IT',
            'title' => 'Leadership Summit Tech Setup',
            'estimated_value' => 300000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        // Item 1: Laptops (Awarded to Vendor A)
        // 2 * 50,000 * 1.18 = 118,000
        $item1 = $pr->items()->create([
            'item_name' => 'Precision Laptops',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 50000,
            'gst_percent' => 18,
            'expense_type' => 'copex',
            'status' => 'approved',
        ]);

        // Item 2: Projector (Awarded to Vendor B)
        // 1 * 80,000 * 1.18 = 94,400
        $item2 = $pr->items()->create([
            'item_name' => '4K Laser Projector',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 80000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        // Item 3: Accessories (Awarded to Vendor A)
        // 5 * 2,000 * 1.18 = 11,800
        $item3 = $pr->items()->create([
            'item_name' => 'Wireless Presenter & Cables',
            'quantity' => 5,
            'unit' => 'Sets',
            'unit_cost' => 2000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-SPLIT-001',
            'sector' => 'IT',
            'project_name' => 'Leadership Summit Tech Setup',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'quotes_received',
        ]);

        $rfq->rfqVendors()->create(['vendor_id' => $vendorA->id, 'response_status' => 'submitted']);
        $rfq->rfqVendors()->create(['vendor_id' => $vendorB->id, 'response_status' => 'submitted']);

        $ccs = CostComparativeStatement::create([
            'ccs_number' => 'CCS-SPLIT-001',
            'rfq_id' => $rfq->id,
            'status' => 'approved',
        ]);

        $ccs->quotes()->create([
            'vendor_id' => $vendorA->id,
            'quoted_cost' => 129800,
            'basic_price' => 110000,
            'gst_amount' => 19800,
            'rank' => 'L1',
        ]);

        $ccs->quotes()->create([
            'vendor_id' => $vendorB->id,
            'quoted_cost' => 94400,
            'basic_price' => 80000,
            'gst_amount' => 14400,
            'rank' => 'L1',
        ]);

        // Submit the multi-vendor award directly from the RFQ page
        $response = $this->actingAs($buyer)->post(route('rfq.generate-pos', $rfq), [
            'item_vendors' => [
                $item1->id => $vendorA->id,
                $item2->id => $vendorB->id,
                $item3->id => $vendorA->id,
            ],
            'delivery_date' => now()->addDays(10)->toDateString(),
            'payment_terms' => 'Within 30 days of verified delivery',
            'selection_remarks' => 'Split award: Vendor A lowest for laptops, Vendor B lowest for AV',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Exactly 2 separate POs must be generated
        $pos = PurchaseOrder::where('purchase_requisition_id', $pr->id)->get();
        $this->assertCount(2, $pos, 'Exactly 2 separate POs should be generated for the two awarded vendors.');

        $poA = $pos->firstWhere('vendor_id', $vendorA->id);
        $poB = $pos->firstWhere('vendor_id', $vendorB->id);

        $this->assertNotNull($poA, 'PO for Vendor A must be generated.');
        $this->assertNotNull($poB, 'PO for Vendor B must be generated.');

        // Verify PO A covers only Item 1 and Item 3
        // 118,000 + 11,800 = 129,800
        $this->assertEqualsWithDelta(129800.00, (float) $poA->amount_incl_gst, 0.01);
        $this->assertSame(7, (int) $poA->po_qty); // 2 + 5

        // Verify PO B covers only Item 2
        // 94,400
        $this->assertEqualsWithDelta(94400.00, (float) $poB->amount_incl_gst, 0.01);
        $this->assertSame(1, (int) $poB->po_qty);

        // Verify item linkages
        $this->assertSame($poA->id, $item1->fresh()->purchase_order_id);
        $this->assertSame($vendorA->id, $item1->fresh()->vendor_id);

        $this->assertSame($poB->id, $item2->fresh()->purchase_order_id);
        $this->assertSame($vendorB->id, $item2->fresh()->vendor_id);

        $this->assertSame($poA->id, $item3->fresh()->purchase_order_id);
        $this->assertSame($vendorA->id, $item3->fresh()->vendor_id);

        // Verify both POs have approval chains
        $this->assertNotEmpty($poA->approvals);
        $this->assertNotEmpty($poB->approvals);

        // Verify RFQ status is approved/awarded
        $this->assertSame('approved', $rfq->fresh()->status);

        // Verify RFQ page now displays both generated PO numbers
        $rfqPageResponse = $this->actingAs($buyer)->get(route('rfq.show', $rfq));
        $rfqPageResponse->assertOk();
        $rfqPageResponse->assertSee($poA->po_number);
        $rfqPageResponse->assertSee($poB->po_number);
        $rfqPageResponse->assertSee('2 POs Generated');
    }

    public function test_auto_populate_quotes_generates_ranks_and_enables_comparison_and_edit_views(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-AP-1',
            'company_name' => 'Alpha Suppliers',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'alpha@suppliers.test',
        ]);

        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-AP-2',
            'company_name' => 'Beta Trading',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'email' => 'beta@trading.test',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-AP-001',
            'department' => 'IT',
            'title' => 'Hardware Procurement',
            'estimated_value' => 200000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        $pr->items()->create([
            'item_name' => 'Workstations',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 80000,
            'gst_percent' => 18,
            'status' => 'approved',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-AP-001',
            'sector' => 'IT',
            'project_name' => 'IT Refresh 2026',
            'purchase_requisition_id' => $pr->id,
            'status' => 'dispatched',
            'poc_user_id' => $buyer->id,
        ]);

        $rfq->rfqVendors()->create(['vendor_id' => $vendorA->id, 'response_status' => 'invited']);
        $rfq->rfqVendors()->create(['vendor_id' => $vendorB->id, 'response_status' => 'invited']);

        // Post to auto-populate quotes
        $res = $this->actingAs($buyer)->post(route('rfq.auto-populate-quotes', $rfq));
        $res->assertRedirect();
        $res->assertSessionHas('status');

        $rfq->refresh();
        $this->assertSame('quotes_received', $rfq->status);

        $ccs = $rfq->costComparativeStatement;
        $this->assertNotNull($ccs);
        $this->assertCount(2, $ccs->quotes);

        $l1 = $ccs->quotes()->where('rank', 'L1')->first();
        $l2 = $ccs->quotes()->where('rank', 'L2')->first();
        $this->assertNotNull($l1);
        $this->assertNotNull($l2);
        $this->assertTrue((float)$l1->quoted_cost <= (float)$l2->quoted_cost);

        // Verify page displays Comparison Statement and Edit Option
        $page = $this->actingAs($buyer)->get(route('rfq.show', $rfq));
        $page->assertOk();
        $page->assertSee('Edit Quotations');
        $page->assertSee('L1');
        $page->assertSee('Lowest Evaluated (L1)');
    }
}
