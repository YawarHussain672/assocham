<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackendWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $prExec;
    private User $adminHead;
    private User $cfo;
    private User $sg;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prExec = User::factory()->create(['role' => Roles::PR_SR_EXEC, 'name' => 'Priya Nair']);
        $this->buyer = User::factory()->create(['role' => Roles::BUYER, 'name' => 'Rohit Verma']);
        $this->adminHead = User::factory()->create(['role' => Roles::ADMIN_HEAD, 'name' => 'Rahul Mehta']);
        $this->cfo = User::factory()->create(['role' => Roles::CFO, 'name' => 'Sanjay Menon']);
        $this->sg = User::factory()->create(['role' => Roles::SG, 'name' => 'Meenakshi Rao']);

        $this->vendor = Vendor::create([
            'vendor_code' => 'VEN-1111',
            'company_name' => 'Sterling Office Supplies',
            'pan' => 'AAACS1234E',
            'gstin' => '07AAACS1234E1ZS',
            'category' => 'Stationery',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $this->buyer->id,
        ]);
    }

    public function test_switch_role_authenticates_and_logs_audit_trail(): void
    {
        // 1. Non-admin users (e.g. Buyer) are strictly forbidden from switching roles and do not see the switcher UI
        $this->actingAs($this->buyer);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="switchRoleForm"', false)
            ->assertSee('"canSwitchRole":false', false);

        $this->post(route('switch-role'), [
            'role' => Roles::ADMIN_HEAD,
        ])->assertForbidden();
        $this->assertAuthenticatedAs($this->buyer);

        // 2. Admin Head can switch to Buyer (audited impersonation) and return back to Admin Head
        $this->actingAs($this->adminHead);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="switchRoleForm"', false)
            ->assertSee('"canSwitchRole":true', false);

        $this->post(route('switch-role'), [
            'role' => Roles::BUYER,
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->buyer);
        $this->assertSame($this->adminHead->id, session('impersonator_admin_id'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'System',
            'action' => 'Switched Role',
            'user_id' => $this->buyer->id,
        ]);

        // While impersonating, Admin Head still retains Return to Admin Head capability
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('"canSwitchRole":true', false)
            ->assertSee('"isImpersonating":true', false);

        $this->post(route('switch-role'), [
            'role' => Roles::ADMIN_HEAD,
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->adminHead);
        $this->assertNull(session('impersonator_admin_id'));

        // 3. When ENABLE_ADMIN_ROLE_SWITCH=false (live production), even Admin Head is blocked
        config(['app.enable_admin_role_switch' => false]);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="switchRoleForm"', false)
            ->assertSee('"canSwitchRole":false', false);

        $this->post(route('switch-role'), [
            'role' => Roles::BUYER,
        ])->assertForbidden();
    }

    public function test_po_edit_resets_approved_po_to_pending(): void
    {
        $budget = DepartmentBudget::create([
            'department' => 'Operations',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 500000,
            'utilised_amount' => 100000,
            'gst_rate' => 18,
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/2026/001',
            'department' => 'Operations',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $this->prExec->id,
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-001',
            'purchase_requisition_id' => $pr->id,
            'created_by' => $this->buyer->id,
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-9999',
            'purchase_requisition_id' => $pr->id,
            'cost_comparative_statement_id' => null,
            'vendor_id' => $this->vendor->id,
            'po_qty' => 10,
            'amount_incl_gst' => 50000,
            'status' => 'approved',
            'created_by' => $this->buyer->id,
        ]);

        $po->approvals()->create([
            'level' => 1,
            'role_required' => Roles::ADMIN_HEAD,
            'approver_id' => $this->adminHead->id,
            'status' => 'approved',
            'acted_at' => now(),
        ]);

        // Buyer edits approved PO delivery date and quantity
        $this->actingAs($this->buyer);
        $response = $this->put(route('po.update', $po), [
            'delivery_date' => now()->addDays(20)->toDateString(),
            'po_qty' => 15,
        ]);

        $response->assertRedirect(route('po.show', $po));
        $po->refresh();

        // Must reset status to pending (Slide 13)
        $this->assertSame('pending', $po->status);
        $this->assertSame(15, $po->po_qty);
        $this->assertSame('pending', $po->approvals()->first()->status);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'PurchaseOrder',
            'action' => 'updated',
            'reference' => 'PO-2026-9999',
        ]);
    }

    public function test_po_cancel_marks_order_cancelled(): void
    {
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/2026/002',
            'department' => 'Operations',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $this->prExec->id,
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-002',
            'purchase_requisition_id' => $pr->id,
            'created_by' => $this->buyer->id,
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-8888',
            'purchase_requisition_id' => $pr->id,
            'cost_comparative_statement_id' => null,
            'vendor_id' => $this->vendor->id,
            'po_qty' => 5,
            'amount_incl_gst' => 20000,
            'status' => 'pending',
            'created_by' => $this->buyer->id,
        ]);

        $po->approvals()->create([
            'level' => 1,
            'role_required' => Roles::ADMIN_HEAD,
            'status' => 'pending',
        ]);

        $this->actingAs($this->buyer);
        $response = $this->post(route('po.cancel', $po));

        $response->assertRedirect(route('po'));
        $po->refresh();

        $this->assertSame('cancelled', $po->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'PurchaseOrder',
            'action' => 'cancelled',
            'reference' => 'PO-2026-8888',
        ]);
    }

    public function test_pr_bulk_excel_import_creates_items_and_reserves_budget(): void
    {
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 1000000,
            'utilised_amount' => 100000,
            'gst_rate' => 18,
        ]);

        $this->actingAs($this->prExec);

        $csvText = "Dell Latitude 5540, 2, Units, 50000, 18\nDell 27-inch Monitor, 4, Units, 12000, 18";

        $response = $this->post(route('requisition.import-excel'), [
            'department' => 'IT',
            'required_by' => now()->addDays(15)->toDateString(),
            'csv_data' => $csvText,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('purchase_requisitions', [
            'department' => 'IT',
            'status' => 'pending_l1',
            'created_by' => $this->prExec->id,
        ]);

        $this->assertDatabaseHas('purchase_requisition_items', [
            'item_name' => 'Dell Latitude 5540',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('purchase_requisition_items', [
            'item_name' => 'Dell 27-inch Monitor',
            'quantity' => 4,
        ]);

        // Budget should be incremented (2*50000*1.18 + 4*12000*1.18 = 118000 + 56640 = 174640)
        $budget = DepartmentBudget::where('department', 'IT')->first();
        $this->assertEquals(274640, (float)$budget->utilised_amount);
    }

    public function test_trf_bulk_excel_import_creates_requisitions(): void
    {
        $this->actingAs($this->buyer);

        $csvText = "Delhi -> Bangalore, Product Review, 2026-08-10, 2026-08-12, 12000, no\nDelhi -> Singapore, Tech Summit, 2026-09-01, 2026-09-05, 55000, yes";

        $response = $this->post(route('trf.import-excel'), [
            'csv_data' => $csvText,
        ]);

        $response->assertRedirect(route('trf'));

        $this->assertDatabaseHas('trfs', [
            'employee_id' => $this->buyer->id,
            'route' => 'Delhi -> Bangalore',
            'is_international' => false,
            'status' => 'pending_asg',
        ]);

        $this->assertDatabaseHas('trfs', [
            'employee_id' => $this->buyer->id,
            'route' => 'Delhi -> Singapore',
            'is_international' => true,
            'status' => 'pending_sg',
        ]);
    }

    public function test_audit_trail_filters_paginates_and_formats_technical_traces_cleanly(): void
    {
        AuditLog::create([
            'user_id' => $this->adminHead->id,
            'module' => 'FlightLog',
            'action' => 'booking_email_failed',
            'reference' => 'FLT-9187',
            'detail' => 'Booking confirmation email for FLT-9187 failed to send to priya.nair@assocham.test: Failed to authenticate on SMTP server with username "info@enseur.in" using the following authenticators and extended diagnostic trace exceeding 180 characters.',
        ]);

        AuditLog::create([
            'user_id' => $this->buyer->id,
            'module' => 'System',
            'action' => 'Switched Role',
            'reference' => 'Buyer',
            'detail' => 'Active user set to Rohit Verma (Buyer)',
        ]);

        AuditLog::create([
            'user_id' => null,
            'module' => 'Vendor',
            'action' => 'created',
            'reference' => 'VEND-TRV-101',
            'detail' => 'Seeded vendor record',
        ]);

        $this->actingAs($this->adminHead);

        // Unfiltered audit page hides dev Switched Role switches and anonymous seed logs
        $this->get(route('audit'))
            ->assertOk()
            ->assertSee('erp-filter-ctrl', false)
            ->assertSee('FLT-9187')
            ->assertDontSee('Active user set to Rohit Verma (Buyer)')
            ->assertDontSee('VEND-TRV-101');

        $this->get(route('audit', ['module' => 'FlightLog', 'q' => 'FLT-9187']))
            ->assertOk()
            ->assertSee('Booking Email Failed')
            ->assertSee('Booking confirmation email for FLT-9187 failed to send to priya.nair@assocham.test')
            ->assertSee('View diagnostic detail')
            ->assertDontSee('Active user set to Rohit Verma (Buyer)');
    }
}
