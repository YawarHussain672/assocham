<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemWiseVendorSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_po_create_page_renders_item_wise_vendor_selection_table(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-ITEM-001',
            'department' => 'IT',
            'title' => 'Conclave IT Infrastructure',
            'estimated_value' => 200000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        $item1 = $pr->items()->create([
            'item_name' => 'High-Performance Laptop',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 60000,
            'gst_percent' => 18,
            'expense_type' => 'copex',
            'status' => 'approved',
        ]);

        $item2 = $pr->items()->create([
            'item_name' => 'Conference Projector',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 50000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($buyer)->get(route('po.create', ['pr_id' => $pr->id]));
        $response->assertOk();
        $response->assertSee('Item-Wise Vendor Assignment');
        $response->assertSee('High-Performance Laptop');
        $response->assertSee('Conference Projector');
        $response->assertSee("item_vendors[{$item1->id}]", false);
        $response->assertSee("item_vendors[{$item2->id}]", false);
    }

    public function test_item_wise_vendor_selection_automatically_generates_separate_pos_for_each_vendor(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $fy = DepartmentBudget::currentFiscalYear();
        DepartmentBudget::firstOrCreate(
            ['department' => 'IT', 'fiscal_year' => $fy],
            ['allocated_amount' => 5000000, 'utilised_amount' => 100000, 'gst_rate' => 18]
        );

        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-IT-A',
            'company_name' => 'Hardware Systems Corp',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);

        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-IT-B',
            'company_name' => 'Audio Visual Pro Ltd',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-SPLIT-001',
            'department' => 'IT',
            'title' => 'Annual Summit Equipment',
            'estimated_value' => 300000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        // Item 1: Laptops (Assigned to Vendor A)
        // 2 * 50,000 * 1.18 = 118,000
        $item1 = $pr->items()->create([
            'item_name' => 'Workstation Laptop',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 50000,
            'gst_percent' => 18,
            'expense_type' => 'copex',
            'status' => 'approved',
        ]);

        // Item 2: Projector (Assigned to Vendor B)
        // 1 * 80,000 * 1.18 = 94,400
        $item2 = $pr->items()->create([
            'item_name' => 'Laser Stage Projector',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 80000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        // Item 3: HDMI & Power Cables (Assigned to Vendor A)
        // 10 * 1,000 * 1.18 = 11,800
        $item3 = $pr->items()->create([
            'item_name' => 'High-Speed HDMI Cables',
            'quantity' => 10,
            'unit' => 'Units',
            'unit_cost' => 1000,
            'gst_percent' => 18,
            'expense_type' => 'opex',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($buyer)->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'item_vendors' => [
                $item1->id => $vendorA->id,
                $item2->id => $vendorB->id,
                $item3->id => $vendorA->id,
            ],
            'event_name' => 'Annual Cyber Summit',
        ]);

        $response->assertRedirect(route('po'));
        $response->assertSessionHas('status');

        // Verify that exactly 2 separate POs were generated
        $pos = PurchaseOrder::where('purchase_requisition_id', $pr->id)->get();
        $this->assertCount(2, $pos, 'Exactly 2 separate POs should be generated for the two vendors.');

        $poA = $pos->firstWhere('vendor_id', $vendorA->id);
        $poB = $pos->firstWhere('vendor_id', $vendorB->id);

        $this->assertNotNull($poA, 'PO for Vendor A must exist.');
        $this->assertNotNull($poB, 'PO for Vendor B must exist.');

        // Verify PO A amounts and items
        // 118,000 + 11,800 = 129,800
        $this->assertEqualsWithDelta(129800.00, (float) $poA->amount_incl_gst, 0.01);
        $this->assertSame(12, (int) $poA->po_qty);
        $this->assertSame('Annual Cyber Summit', $poA->event_name);

        // Verify PO B amounts and items
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
        $this->assertGreaterThan(0, $poA->approvals()->count());
        $this->assertGreaterThan(0, $poB->approvals()->count());

        // Verify that PO A show page renders only items 1 & 3
        $poAShow = $this->actingAs($buyer)->get(route('po.show', $poA))->assertOk()->getContent();
        $this->assertStringContainsString('Workstation Laptop', $poAShow);
        $this->assertStringContainsString('High-Speed HDMI Cables', $poAShow);
        $this->assertStringNotContainsString('Laser Stage Projector', $poAShow);
        $this->assertStringContainsString('Multi-Vendor Order Split', $poAShow);
        $this->assertStringContainsString($poB->po_number, $poAShow);

        // Verify that PO B show page renders only item 2
        $poBShow = $this->actingAs($buyer)->get(route('po.show', $poB))->assertOk()->getContent();
        $this->assertStringContainsString('Laser Stage Projector', $poBShow);
        $this->assertStringNotContainsString('Workstation Laptop', $poBShow);
        $this->assertStringContainsString('Multi-Vendor Order Split', $poBShow);
        $this->assertStringContainsString($poA->po_number, $poBShow);
    }

    public function test_all_items_assigned_to_same_vendor_generates_single_po(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $vendor = Vendor::create([
            'vendor_code' => 'VEND-UNI-1',
            'company_name' => 'Unified Supply Partners',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-UNI-001',
            'department' => 'IT',
            'title' => 'Unified Order Requisition',
            'estimated_value' => 150000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        $item1 = $pr->items()->create([
            'item_name' => 'Office Monitor',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 25000,
            'gst_percent' => 18,
            'status' => 'approved',
        ]);

        $item2 = $pr->items()->create([
            'item_name' => 'Wireless Keyboard Combo',
            'quantity' => 5,
            'unit' => 'Units',
            'unit_cost' => 2000,
            'gst_percent' => 18,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($buyer)->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'item_vendors' => [
                $item1->id => $vendor->id,
                $item2->id => $vendor->id,
            ],
        ]);

        $response->assertRedirect(route('po'));
        $pos = PurchaseOrder::where('purchase_requisition_id', $pr->id)->get();
        $this->assertCount(1, $pos, 'Only 1 PO should be created when all items belong to the same vendor.');
        $this->assertSame($vendor->id, $pos->first()->vendor_id);
    }

    public function test_item_wise_vendor_selection_blocks_blacklisted_vendor(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();

        $validVendor = Vendor::create([
            'vendor_code' => 'VEND-VAL-1',
            'company_name' => 'Valid Tech Supplies',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);

        $blacklistedVendor = Vendor::create([
            'vendor_code' => 'VEND-BLK-1',
            'company_name' => 'Fraudulent Agency',
            'status' => 'empanelled',
            'compliance_status' => 'blacklisted',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-BLK-CHK',
            'department' => 'IT',
            'title' => 'Test Blacklist Item Split',
            'estimated_value' => 50000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $buyer->id,
        ]);

        $item1 = $pr->items()->create([
            'item_name' => 'Item Alpha',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 20000,
            'gst_percent' => 0,
            'status' => 'approved',
        ]);

        $item2 = $pr->items()->create([
            'item_name' => 'Item Beta',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 30000,
            'gst_percent' => 0,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($buyer)->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'item_vendors' => [
                $item1->id => $validVendor->id,
                $item2->id => $blacklistedVendor->id,
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('purchase_orders', ['purchase_requisition_id' => $pr->id]);
    }
}
