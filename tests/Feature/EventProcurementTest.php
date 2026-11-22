<?php

namespace Tests\Feature;

use App\Models\ProjectBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class EventProcurementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authorized_users_can_view_event_procurement_index(): void
    {
        $this->loginAs(Roles::CFO);

        $response = $this->get(route('events.index'));
        $response->assertOk();
        $response->assertSee('Event-Wise PR &amp; PO Management', false);
        $response->assertSee('Event / Project Name');
    }

    public function test_event_procurement_dossier_shows_linked_prs_and_pos(): void
    {
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $event = ProjectBudget::first();
        if (!$event) {
            $event = ProjectBudget::create([
                'project_name' => 'Annual National Conclave 2026',
                'event_name' => 'Annual National Conclave 2026',
                'allocated_budget' => 2500000,
                'sector' => 'Policy & Governance',
                'fiscal_year' => 'FY26-27',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'status' => 'active',
            ]);
        }

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/FY26-27/EVT-001',
            'project_budget_id' => $event->id,
            'event_name' => $event->event_name,
            'department' => 'Events',
            'category' => 'COOPEX',
            'title' => 'Stage & AV Infrastructure',
            'justification' => 'Conclave setup and broadcasting',
            'estimated_total' => 450000,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $vendor = Vendor::first() ?? Vendor::create([
            'vendor_code' => 'VEND-EVT-01',
            'company_name' => 'Apex Event Staging Ltd',
            'contact_person' => 'Rajeev Malhotra',
            'contact_email' => 'contact@apexstaging.test',
            'category' => 'Events & Production',
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO/FY26-27/EVT-001',
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'event_name' => $event->event_name,
            'amount_incl_gst' => 420000,
            'status' => 'approved',
            'created_by' => $admin->id,
            'delivery_address' => 'Vigyan Bhawan, New Delhi',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->get(route('events.show', $event));
        $response->assertOk();
        $response->assertSee($event->event_name);
        $response->assertSee($pr->pr_number);
        $response->assertSee($po->po_number);
        $response->assertSee('Consolidated PDF');
        $response->assertSee('Download All ZIP');
        $response->assertSee('Export CSV');
    }

    public function test_can_download_event_dossier_pdf(): void
    {
        $this->loginAs(Roles::CFO);

        $event = ProjectBudget::first();
        if (!$event) {
            $event = ProjectBudget::create([
                'project_name' => 'Global AI Summit 2026',
                'event_name' => 'Global AI Summit 2026',
                'allocated_budget' => 1000000,
                'sector' => 'IT & Technology',
                'fiscal_year' => 'FY26-27',
                'status' => 'active',
            ]);
        }

        $response = $this->get(route('events.pdf', $event));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_can_download_event_zip_pack(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $event = ProjectBudget::first();
        if (!$event) {
            $event = ProjectBudget::create([
                'project_name' => 'Energy Transition Round Table',
                'event_name' => 'Energy Transition Round Table',
                'allocated_budget' => 500000,
                'sector' => 'Energy',
                'fiscal_year' => 'FY26-27',
                'status' => 'active',
            ]);
        }

        $response = $this->get(route('events.zip', $event));
        $response->assertOk();
        $this->assertStringContainsString('application/zip', $response->headers->get('content-type'));
    }

    public function test_can_download_event_spreadsheet_csv(): void
    {
        $this->loginAs(Roles::CFO);

        $event = ProjectBudget::first();
        if (!$event) {
            $event = ProjectBudget::create([
                'project_name' => 'MSME Banking Forum',
                'event_name' => 'MSME Banking Forum',
                'allocated_budget' => 750000,
                'sector' => 'Banking',
                'fiscal_year' => 'FY26-27',
                'status' => 'active',
            ]);
        }

        $response = $this->get(route('events.sheet', $event));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_can_download_standalone_purchase_requisition_pdf(): void
    {
        $user = $this->loginAs(Roles::PR_SR_EXEC);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/FY26-27/DL-999',
            'department' => 'IT',
            'category' => 'CAPEX',
            'title' => 'Server Equipment',
            'justification' => 'Replacement servers',
            'estimated_total' => 150000,
            'status' => 'approved',
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('requisition.pdf', $pr));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_authorized_user_can_delete_unused_event(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $event = ProjectBudget::create([
            'project_name' => 'Standalone Temp Summit',
            'department' => 'IT',
            'allocated_amount' => 100000,
            'utilised_amount' => 0,
            'fiscal_year' => 'FY26-27',
        ]);

        $response = $this->delete(route('events.destroy', $event));
        $response->assertRedirect();
        $this->assertDatabaseMissing('project_budgets', ['id' => $event->id]);
    }

    public function test_cannot_delete_event_with_linked_prs_or_spend(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $event = ProjectBudget::first();
        if ($event) {
            $event->update(['utilised_amount' => 50000]);
            $response = $this->delete(route('events.destroy', $event));
            $response->assertRedirect();
            $this->assertDatabaseHas('project_budgets', ['id' => $event->id]);
        }
    }
}
