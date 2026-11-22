<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\ProjectBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class TesterIssuesVerificationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;
    use BuildsProcurementChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createEmpanelledVendor(string $name, string $category = 'General'): Vendor
    {
        static $counter = 100;
        $counter++;
        return Vendor::create([
            'company_name' => $name,
            'vendor_code' => "VEND-{$counter}",
            'status' => 'approved',
            'compliance_status' => 'active',
            'category' => $category,
            'pan' => 'AAACT' . $counter . 'F',
            'gstin' => '07AAACT' . $counter . 'F1Z5',
            'email' => "vendor{$counter}@example.com",
        ]);
    }

    protected function createRequisitionCustom(array $attributes, array $items): PurchaseRequisition
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $payload = array_merge([
            'department' => 'Finance',
            'title' => 'Test Requisition',
            'required_by' => now()->addDays(14)->format('Y-m-d'),
            'items' => $items,
        ], $attributes);

        $this->post(route('requisition.store'), $payload);
        return PurchaseRequisition::latest('id')->firstOrFail();
    }

    protected function approveRequisitionFullyCustom(PurchaseRequisition $pr): void
    {
        while ($approval = $pr->fresh()->approvals()->where('status', 'pending')->orderBy('level')->first()) {
            $this->loginAs($approval->role_required);
            $this->post(route('requisition.approve', $pr));
        }
    }

    public function test_rfq_is_not_automatically_created_on_pr_approval_or_index(): void
    {
        $v1 = $this->createEmpanelledVendor('Apex Technologies', 'Finance');
        $v2 = $this->createEmpanelledVendor('Blue Horizon Services', 'Finance');
        $v3 = $this->createEmpanelledVendor('Crown Enterprises', 'Finance');

        // 1. Create a PR with 2 items
        $pr = $this->createRequisitionCustom([
            'department' => 'Finance',
            'title' => 'Financial Auditing Software and Support',
            'required_by' => now()->addDays(14)->format('Y-m-d'),
        ], [
            ['item_name' => 'Audit Pro Enterprise Suite', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18],
            ['item_name' => '1-Year Priority Support SLA', 'quantity' => 1, 'unit_cost' => 50000, 'gst_percent' => 18],
        ]);

        // 2. Fully approve the PR through the DoA chain
        $this->approveRequisitionFullyCustom($pr);
        $pr->refresh();

        $this->assertEquals('approved', $pr->status);

        // Assert no RFQ was automatically created behind the scenes
        $this->assertEquals(0, $pr->rfqs()->count());
        $this->assertNull($pr->rfq);

        // 3. Buyer visits the RFQ index page
        $this->loginAs(Roles::BUYER);
        $indexResponse = $this->get(route('rfq'));
        $indexResponse->assertOk();

        // Still no RFQ auto-created
        $this->assertEquals(0, $pr->rfqs()->count());

        // 4. Admin Head / Buyer explicitly dispatches RFQ with only 2 selected vendors
        $selectedVendorIds = [$v1->id, $v2->id];
        $excludedVendorId = $v3->id;

        $dispatchResponse = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'Finance',
            'project_name' => 'Targeted Financial Audit RFQ 2026',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'vendor_ids' => $selectedVendorIds,
        ]);

        $dispatchResponse->assertRedirect(route('rfq'));

        // Assert RFQ exists and invited ONLY the explicitly selected vendors
        $rfq = Rfq::where('purchase_requisition_id', $pr->id)->first();
        $this->assertNotNull($rfq);
        $this->assertCount(2, $rfq->rfqVendors);
        $this->assertTrue($rfq->rfqVendors->pluck('vendor_id')->contains($selectedVendorIds[0]));
        $this->assertTrue($rfq->rfqVendors->pluck('vendor_id')->contains($selectedVendorIds[1]));
        $this->assertFalse($rfq->rfqVendors->pluck('vendor_id')->contains($excludedVendorId));
    }

    public function test_item_wise_vendor_assignment_strictly_isolates_items_per_po(): void
    {
        $vendorA = $this->createEmpanelledVendor('Hardware Solutions India', 'IT & Telecom');
        $vendorB = $this->createEmpanelledVendor('Comfort Furniture World', 'Furniture');

        // 1. Create a PR with 2 distinct line items
        $pr = $this->createRequisitionCustom([
            'department' => 'Finance',
            'title' => 'Split Vendor Hardware and Office Supplies',
            'required_by' => now()->addDays(14)->format('Y-m-d'),
        ], [
            ['item_name' => 'Server Rack Cabinet 42U', 'quantity' => 2, 'unit_cost' => 40000, 'gst_percent' => 18],
            ['item_name' => 'Ergonomic Mesh Task Chairs', 'quantity' => 10, 'unit_cost' => 8000, 'gst_percent' => 18],
        ]);

        $this->approveRequisitionFullyCustom($pr);
        $pr->refresh();

        $items = $pr->items()->orderBy('id')->get();
        $item1 = $items[0];
        $item2 = $items[1];

        // 2. Buyer issues item-wise split PO: item1 -> vendorA, item2 -> vendorB
        $this->loginAs(Roles::BUYER);

        $storeResponse = $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'item_vendors' => [
                $item1->id => $vendorA->id,
                $item2->id => $vendorB->id,
            ],
            'event_name' => 'Office Expansion Project',
            'delivery_date' => now()->addDays(14)->format('Y-m-d'),
        ]);

        $storeResponse->assertRedirect(route('po'));

        // 3. Verify that 2 separate POs were generated
        $poA = PurchaseOrder::where('purchase_requisition_id', $pr->id)->where('vendor_id', $vendorA->id)->first();
        $poB = PurchaseOrder::where('purchase_requisition_id', $pr->id)->where('vendor_id', $vendorB->id)->first();

        $this->assertNotNull($poA);
        $this->assertNotNull($poB);

        // 4. Verify item isolation: poA has ONLY item1, poB has ONLY item2
        $poAItems = $poA->effective_items;
        $poBItems = $poB->effective_items;

        $this->assertCount(1, $poAItems);
        $this->assertCount(1, $poBItems);

        $this->assertEquals('Server Rack Cabinet 42U', $poAItems->first()->item_name);
        $this->assertEquals('Ergonomic Mesh Task Chairs', $poBItems->first()->item_name);

        $this->assertFalse($poAItems->pluck('item_name')->contains('Ergonomic Mesh Task Chairs'));
        $this->assertFalse($poBItems->pluck('item_name')->contains('Server Rack Cabinet 42U'));

        // 5. Check PO show page
        $showResponseA = $this->get(route('po.show', $poA));
        $showResponseA->assertOk();
        $showResponseA->assertSee('Server Rack Cabinet 42U');
        $showResponseA->assertDontSee('Ergonomic Mesh Task Chairs');

        $showResponseB = $this->get(route('po.show', $poB));
        $showResponseB->assertOk();
        $showResponseB->assertSee('Ergonomic Mesh Task Chairs');
        $showResponseB->assertDontSee('Server Rack Cabinet 42U');
    }

    public function test_budget_updates_and_tracks_committed_spend_on_po_creation(): void
    {
        $vendor = $this->createEmpanelledVendor('SecureNet Technologies', 'Security');

        // 1. Create a Project Budget
        $projectBudget = ProjectBudget::create([
            'project_name' => 'Cyber Security Conclave 2026',
            'department' => 'Finance',
            'fiscal_year' => 'FY26-27',
            'allocated_amount' => 500000.00,
            'utilised_amount' => 0.00,
        ]);

        $initialHeadroom = $projectBudget->remainingAmount();
        $this->assertEquals(500000.00, $initialHeadroom);

        // 2. Create PR linked to this project budget
        $pr = $this->createRequisitionCustom([
            'department' => 'Finance',
            'title' => 'Firewall and Gateway Security Hardware',
            'project_budget_id' => $projectBudget->id,
            'event_name' => 'Cyber Security Conclave 2026',
            'required_by' => now()->addDays(14)->format('Y-m-d'),
        ], [
            ['item_name' => 'Next-Gen Perimeter Firewall', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18], // 118,000
            ['item_name' => 'SSL Inspection Appliance', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18],     // 118,000
        ]);

        $projectBudget->refresh();
        $this->assertEquals(236000.00, (float) $projectBudget->utilised_amount);

        $this->approveRequisitionFullyCustom($pr);
        $pr->refresh();

        $items = $pr->items()->orderBy('id')->get();

        // 3. Issue PO for only the first item (item 1: 118,000)
        $this->loginAs(Roles::BUYER);
        $response = $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'item_vendors' => [
                $items[0]->id => $vendor->id,
            ],
            'event_name' => 'Cyber Security Conclave 2026',
            'delivery_date' => now()->addDays(14)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('po'));

        // Budget utilised should NOT drop or be cleared because the remaining item estimate is preserved!
        $projectBudget->refresh();
        $this->assertEquals(236000.00, (float) $projectBudget->utilised_amount);

        // 4. Verify Event Dossier displays updated metrics
        $this->loginAs(Roles::ADMIN_HEAD);
        $eventShowResponse = $this->get(route('events.show', $projectBudget));
        $eventShowResponse->assertOk();
        $eventShowResponse->assertSee('Project Budget');
        $eventShowResponse->assertSee('Project Expense');
        $eventShowResponse->assertSee('Project Surplus');
        $eventShowResponse->assertSee('Committed PO Spend');
        $eventShowResponse->assertSee('118,000.00');
    }
}
