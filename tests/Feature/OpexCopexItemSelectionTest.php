<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\ItemCatalog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class OpexCopexItemSelectionTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;
    use BuildsProcurementChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_head_and_buyer_can_create_update_and_manage_master_catalog_items(): void
    {
        // 1. Admin Head can configure new master catalog items and fixed pricing
        $this->loginAs(Roles::ADMIN_HEAD);

        $response = $this->post(route('item-catalogs.store'), [
            'name' => 'High-Performance Workstation Z8',
            'category' => 'IT Infrastructure',
            'expense_type' => 'copex',
            'fixed_price' => 185000.00,
            'unit' => 'Units',
            'gst_percent' => 18,
            'description' => 'Dual Intel Xeon, 128GB ECC RAM, RTX 6000 Ada 48GB GPU',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('checks-balances', ['view' => 'items']));

        $this->assertDatabaseHas('item_catalogs', [
            'name' => 'High-Performance Workstation Z8',
            'expense_type' => 'copex',
            'fixed_price' => 185000.00,
            'gst_percent' => 18,
            'is_active' => 1,
        ]);

        $catalogItem = ItemCatalog::where('name', 'High-Performance Workstation Z8')->firstOrFail();
        $this->assertTrue($catalogItem->isCopex());
        $this->assertFalse($catalogItem->isOpex());

        // 2. Buyer can update existing master items and adjust pricing
        $this->loginAs(Roles::BUYER);

        $updateResponse = $this->put(route('item-catalogs.update', $catalogItem), [
            'name' => 'High-Performance Workstation Z8 G5',
            'category' => 'IT Infrastructure',
            'expense_type' => 'copex',
            'fixed_price' => 175000.00,
            'unit' => 'Sets',
            'gst_percent' => 18,
            'description' => 'Negotiated rate for bulk rollout',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('checks-balances', ['view' => 'items']));

        $catalogItem->refresh();
        $this->assertSame('High-Performance Workstation Z8 G5', $catalogItem->name);
        $this->assertEquals(175000.00, (float) $catalogItem->fixed_price);

        // 3. Non-authorized users cannot create master catalog items (403 Forbidden)
        $this->loginAs(Roles::PR_SR_EXEC);

        $forbiddenResponse = $this->post(route('item-catalogs.store'), [
            'name' => 'Unauthorized Item',
            'expense_type' => 'opex',
            'fixed_price' => 500,
            'gst_percent' => 18,
        ]);

        $forbiddenResponse->assertForbidden();
    }

    public function test_api_item_catalogs_returns_active_predefined_items_with_fixed_prices(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        $response = $this->getJson(route('api.item-catalogs'));

        $response->assertOk();
        $items = $response->json('data');
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);

        $first = $items[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('item_code', $first);
        $this->assertArrayHasKey('expense_type', $first);
        $this->assertArrayHasKey('fixed_price', $first);
        $this->assertArrayHasKey('unit', $first);
        $this->assertArrayHasKey('gst_percent', $first);
    }

    public function test_pr_with_pure_opex_under_5_lakh_requires_only_3_approval_levels_and_bypasses_sg(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Submit PR with an OPEX item under ₹5 lakh (e.g. ₹20,000 + 18% GST = ₹23,600)
        $response = $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'Annual Office Supplies Restock',
            'required_by' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'item_name' => 'Corporate Stationery Kit',
                    'expense_type' => 'opex',
                    'quantity' => 10,
                    'unit_cost' => 2000,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $response->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Annual Office Supplies Restock')->firstOrFail();
        $this->assertSame('opex', $pr->expense_type);
        $this->assertTrue($pr->isOpex());
        $this->assertFalse($pr->isCopex());
        $this->assertLessThan(500000, $pr->estimatedCostInclGst());

        // Chain must have only 3 levels: Level 1 (DSG), Level 2 (Head of States), Level 3 (CFO)
        $approvals = $pr->approvals()->orderBy('level')->get();
        $this->assertCount(3, $approvals);
        $this->assertSame([Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO], $approvals->pluck('role_required')->all());
        $this->assertNotContains(Roles::SG, $approvals->pluck('role_required')->all());

        // Level 1 approves
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr))->assertRedirect();
        $this->assertSame('pending_l2', $pr->fresh()->status);

        // Level 2 approves
        $this->loginAs(Roles::PR_APPROVER_L2);
        $this->post(route('requisition.approve', $pr))->assertRedirect();
        $this->assertSame('pending_l3', $pr->fresh()->status);

        // Level 3 (CFO) approves -> PR is finalized and fully approved without needing SG!
        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $freshPr = $pr->fresh();
        $this->assertSame('approved', $freshPr->status);
        $this->assertSame(3, $freshPr->current_level);
    }

    public function test_pr_with_copex_under_5_lakh_mandates_sg_approval_level_4(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Submit PR with a COPEX item whose cost is well UNDER ₹5 lakh (e.g. ₹55,000 + 18% GST = ₹64,900)
        $response = $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'Finance Field Laptop Replacement',
            'required_by' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'item_name' => 'Dell Latitude 5540 Laptop',
                    'expense_type' => 'copex',
                    'quantity' => 1,
                    'unit_cost' => 55000,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $response->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Finance Field Laptop Replacement')->firstOrFail();
        $this->assertSame('copex', $pr->expense_type);
        $this->assertTrue($pr->isCopex());
        $this->assertLessThan(500000, $pr->estimatedCostInclGst());

        // For COPEX, SG approval is mandatory even if the amount is < ₹5 lakh.
        // Therefore, approval chain MUST have 4 levels: L1, L2, CFO, and SG.
        $approvals = $pr->approvals()->orderBy('level')->get();
        $this->assertCount(4, $approvals);
        $this->assertSame(
            [Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO, Roles::SG],
            $approvals->pluck('role_required')->all()
        );

        // Walk through Level 1, 2, 3
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr))->assertRedirect();
        $this->assertSame('pending_l2', $pr->fresh()->status);

        $this->loginAs(Roles::PR_APPROVER_L2);
        $this->post(route('requisition.approve', $pr))->assertRedirect();
        $this->assertSame('pending_l3', $pr->fresh()->status);

        // CFO approves -> Since it's COPEX, status must advance to pending_l4 for SG, NOT finalized!
        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $cfoApprovedPr = $pr->fresh();
        $this->assertSame('pending_l4', $cfoApprovedPr->status);
        $this->assertNotSame('approved', $cfoApprovedPr->status);

        // SG approves -> Now PR is finalized and fully approved
        $this->loginAs(Roles::SG);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $finalPr = $pr->fresh();
        $this->assertSame('approved', $finalPr->status);
        $this->assertSame(4, $finalPr->current_level);
    }

    public function test_pr_with_opex_over_5_lakh_escalates_to_sg_approval(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Allocate budget for IT department
        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 2000000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        // OPEX item with total cost > ₹5 lakh (e.g. ₹500,000 + 18% GST = ₹590,000)
        $response = $this->post(route('requisition.store'), [
            'department' => 'IT',
            'title' => 'Annual Cloud Infrastructure Licensing',
            'required_by' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'item_name' => 'Enterprise Cloud Hosting Service',
                    'expense_type' => 'opex',
                    'quantity' => 1,
                    'unit_cost' => 500000,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $response->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Annual Cloud Infrastructure Licensing')->firstOrFail();
        $this->assertSame('opex', $pr->expense_type);
        $this->assertGreaterThanOrEqual(500000, $pr->estimatedCostInclGst());

        // Because OPEX is >= ₹5 lakh, chain must include SG (4 levels)
        $approvals = $pr->approvals()->orderBy('level')->get();
        $this->assertCount(4, $approvals);
        $this->assertSame(
            [Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO, Roles::SG],
            $approvals->pluck('role_required')->all()
        );
    }

    public function test_user_can_select_predefined_item_with_fixed_price_and_specs(): void
    {
        $catalogItem = ItemCatalog::where('expense_type', 'copex')->firstOrFail();

        $this->loginAs(Roles::PR_SR_EXEC);

        $response = $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'Catalog Predefined Item Selection',
            'required_by' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'item_catalog_id' => $catalogItem->id,
                    'item_name' => $catalogItem->name,
                    'expense_type' => $catalogItem->expense_type,
                    'quantity' => 2,
                    'unit' => $catalogItem->unit,
                    'unit_cost' => $catalogItem->fixed_price,
                    'gst_percent' => $catalogItem->gst_percent,
                    'description' => $catalogItem->description,
                ],
            ],
        ]);

        $response->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Catalog Predefined Item Selection')->with('items')->firstOrFail();
        $this->assertSame('copex', $pr->expense_type);
        $this->assertCount(1, $pr->items);

        $prItem = $pr->items->first();
        $this->assertSame($catalogItem->id, $prItem->item_catalog_id);
        $this->assertSame($catalogItem->name, $prItem->item_name);
        $this->assertEquals((float) $catalogItem->fixed_price, (float) $prItem->unit_cost);
        $this->assertSame('copex', $prItem->expense_type);
        $this->assertSame($catalogItem->description, $prItem->description);
    }

    public function test_approved_pr_provides_direct_option_to_generate_purchase_order(): void
    {
        // 1. Create and fully approve a PR
        $this->loginAs(Roles::PR_SR_EXEC);

        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'PO Ready Approved Requisition',
            'items' => [
                [
                    'item_name' => 'Office Refreshment Pack',
                    'expense_type' => 'opex',
                    'quantity' => 5,
                    'unit_cost' => 1000,
                    'gst_percent' => 18,
                ],
            ],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'PO Ready Approved Requisition')->firstOrFail();

        // Walk 3-step OPEX approval to completion
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::PR_APPROVER_L2);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $pr->refresh();
        $this->assertSame('approved', $pr->status);

        // 2. Admin Head navigates to PR show page and sees "Generate Purchase Order (PO)" action
        $this->loginAs(Roles::ADMIN_HEAD);
        $showRes = $this->get(route('requisition.show', $pr));
        $showRes->assertOk();
        $showRes->assertSee('Generate Purchase Order (PO)');
        $showRes->assertSee(route('po.create', ['purchase_requisition_id' => $pr->id, 'pr' => $pr->id]));

        // 3. Buyer navigates directly to PO creation from this approved PR
        $vendor = $this->empanelledVendor('Elite Suppliers Ltd');

        $this->loginAs(Roles::BUYER);
        $poCreateRes = $this->get(route('po.create', ['purchase_requisition_id' => $pr->id]));
        $poCreateRes->assertOk();
        $poCreateRes->assertSee($pr->pr_number);
        $poCreateRes->assertSee('PO Ready Approved Requisition');

        // 4. Buyer generates the Purchase Order directly
        $poStoreRes = $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => $pr->estimatedCostInclGst(),
        ]);

        $poStoreRes->assertRedirect();

        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
        $this->assertNotNull($po);
        $this->assertSame($vendor->id, $po->vendor_id);
    }

    public function test_item_catalog_and_pr_expense_type_is_not_compulsory_and_capex_is_supported(): void
    {
        // 1. Master item can be created without expense_type (not compulsory)
        $this->loginAs(Roles::ADMIN_HEAD);

        $response = $this->post(route('item-catalogs.store'), [
            'name' => 'General Office Consumable Item',
            'category' => 'General Admin',
            'expense_type' => '', // Not compulsory!
            'fixed_price' => 250.00,
            'unit' => 'Packs',
            'gst_percent' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('checks-balances', ['view' => 'items']));

        $item = ItemCatalog::where('name', 'General Office Consumable Item')->firstOrFail();
        $this->assertNull($item->expense_type);
        $this->assertFalse($item->isCopex());
        $this->assertFalse($item->isCapex());

        // 2. Master item can also be created with 'capex'
        $capexRes = $this->post(route('item-catalogs.store'), [
            'name' => 'High End Server Rack 42U',
            'category' => 'IT Infrastructure',
            'expense_type' => 'capex', // CapEx
            'fixed_price' => 75000.00,
            'unit' => 'Units',
            'gst_percent' => 18,
            'is_active' => 1,
        ]);

        $capexRes->assertRedirect(route('checks-balances', ['view' => 'items']));

        $capexItem = ItemCatalog::where('name', 'High End Server Rack 42U')->firstOrFail();
        $this->assertTrue($capexItem->isCapex());
        $this->assertTrue($capexItem->isCopex());

        // 3. Purchase Requisition can be created without choosing expense_type (not compulsory)
        $this->loginAs(Roles::PR_SR_EXEC);

        $prRes = $this->post(route('requisition.store'), [
            'title' => 'Unclassified PR Without Compulsory Expense Type',
            'department' => 'Finance',
            'expense_type' => '', // Not chosen
            'items' => [
                [
                    'item_name' => 'Unclassified Custom Office Item',
                    'expense_type' => '', // Not compulsory!
                    'quantity' => 10,
                    'unit_cost' => 500,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $prRes->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Unclassified PR Without Compulsory Expense Type')->firstOrFail();
        $this->assertFalse($pr->isCopex());
        $this->assertFalse($pr->isCapex());

        // Under 5L without CapEx routes to L3 CFO (3 levels: L1, L2, L3)
        $this->assertCount(3, $pr->approvals);
        $this->assertFalse($pr->approvals->contains('role_required', Roles::SG));

        // 4. PR with 'capex' item mandates SG Level 4 approval even under 5L
        $capexPrRes = $this->post(route('requisition.store'), [
            'title' => 'CapEx Requisition Under 5 Lakh',
            'department' => 'Finance',
            'expense_type' => 'capex',
            'items' => [
                [
                    'item_name' => 'Small Server Accessory',
                    'expense_type' => 'capex',
                    'quantity' => 1,
                    'unit_cost' => 25000,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $capexPrRes->assertRedirect(route('requisition'));

        $capexPr = PurchaseRequisition::where('title', 'CapEx Requisition Under 5 Lakh')->firstOrFail();
        $this->assertTrue($capexPr->isCapex());
        $this->assertTrue($capexPr->isCopex());
        $this->assertCount(4, $capexPr->approvals);
        $this->assertTrue($capexPr->approvals->contains('role_required', Roles::SG));
    }
}
