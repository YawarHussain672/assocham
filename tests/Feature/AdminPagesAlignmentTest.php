<?php

namespace Tests\Feature;

use App\Models\ExceptionRequest;
use App\Models\FlightBookingLog;
use App\Models\Grievance;
use App\Models\PurchaseRequisition;
use App\Models\Reimbursement;
use App\Models\Trf;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class AdminPagesAlignmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_admin_list_pages_have_standard_page_header_and_subtitles(): void
    {
        // 1. Dashboard (Any user)
        $this->loginAs(Roles::SG);
        $res = $this->get(route('dashboard'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 2. Notifications (Any user)
        $res = $this->get(route('notifications'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 3. Audit (Any user)
        $res = $this->get(route('audit'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 4. Grievance (Any user)
        $res = $this->get(route('grievance'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 5. TRF (Any user)
        $res = $this->get(route('trf'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 6. Reimbursements (Any user)
        $res = $this->get(route('reimb'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 7. Empanelment (Buyer)
        $this->loginAs(Roles::BUYER);
        $res = $this->get(route('empanelment'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 8. Requisition (PR Executive)
        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get(route('requisition'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('page-title', false);

        // 9. RFQ (Buyer)
        $this->loginAs(Roles::BUYER);
        $res = $this->get(route('rfq'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('page-title', false);

        // 10. PO (Buyer)
        $res = $this->get(route('po'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 12. GRN (PR Executive)
        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get(route('grn'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 13. Flight & Boarding (Admin Head)
        $this->loginAs(Roles::ADMIN_HEAD);
        $res = $this->get(route('flight'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 14. Travel Vendor Evaluation (Admin Head)
        $res = $this->get(route('traveleval'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 15. Checks & Balances (CFO)
        $this->loginAs(Roles::CFO);
        $res = $this->get(route('checks-balances'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);

        // 16. Exceptions (Admin Head)
        $this->loginAs(Roles::ADMIN_HEAD);
        $res = $this->get(route('exceptions'));
        $res->assertOk()
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false);
    }

    public function test_all_admin_create_pages_render_cleanly_with_card_panels(): void
    {
        $this->loginAs(Roles::BUYER);
        $this->get(route('onboarding'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('po.create'))->assertOk()->assertSee('card-panel', false);

        $this->loginAs(Roles::PR_SR_EXEC);
        $this->get(route('requisition.create'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('grn.create'))->assertOk()->assertSee('card-panel', false);

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->get(route('exceptions.create'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('flight.create'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('traveleval.create'))->assertOk()->assertSee('card-panel', false);

        $this->loginAs(Roles::BUYER);
        $this->get(route('trf.create'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('reimb.create'))->assertOk()->assertSee('card-panel', false);
        $this->get(route('grievance.create'))->assertOk()->assertSee('card-panel', false);
    }

    public function test_admin_show_pages_render_detail_header_card_and_aligned_grid(): void
    {
        $buyer = $this->loginAs(Roles::BUYER);

        // Seed a sample vendor
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-TEST-001',
            'company_name' => 'Acme Corporation',
            'contact_person' => 'John Doe',
            'email' => 'john@acme.test',
            'phone' => '9876543210',
            'category' => 'IT Services',
            'status' => 'pending_admin_head',
            'submitted_by' => $buyer->id,
        ]);
        $this->get(route('empanelment.show', $vendor))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-2', false);

        // Seed a sample PR
        $exec = $this->loginAs(Roles::PR_SR_EXEC);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-TEST-001',
            'department' => 'Administration',
            'status' => 'pending_l1',
            'created_by' => $exec->id,
        ]);
        $this->get(route('requisition.show', $pr))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-2', false);

        // Seed a sample TRF
        $trf = Trf::create([
            'trf_number' => 'TRF-TEST-001',
            'employee_id' => $exec->id,
            'is_international' => false,
            'status' => 'pending_asg',
            'created_by' => $exec->id,
            'booking_type' => 'self',
        ]);
        $this->get(route('trf.show', $trf))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-2', false);

        // Seed a sample Reimbursement
        $reimb = Reimbursement::create([
            'claim_number' => 'CLM-TEST-001',
            'trf_id' => $trf->id,
            'employee_id' => $exec->id,
            'claim_amount' => 5000,
            'advance_amount' => 0,
            'balance_amount' => 5000,
            'routing_flag' => 'standard',
            'status' => 'pending_cfo',
        ]);
        $this->get(route('reimb.show', $reimb))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-2', false);

        // Seed a sample Grievance
        $grievance = Grievance::create([
            'ticket_number' => 'GRV-TEST-001',
            'raised_by' => $exec->id,
            'subject' => 'Payment Delay Issue',
            'subject_category' => 'Vendor Dispute',
            'current_owner_role' => 'admin_head',
            'status' => 'open',
            'description' => 'Test dispute narrative',
        ]);
        $this->get(route('grievance.show', $grievance))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-1', false);

        // Seed a sample Exception
        $this->loginAs(Roles::ADMIN_HEAD);
        $exception = ExceptionRequest::create([
            'exception_type' => 'single_quotation',
            'justification' => 'Sole OEM vendor constraint',
            'requested_by' => $this->userWithRole(Roles::ADMIN_HEAD)->id,
            'approver_queue' => 'sg',
            'status' => 'pending',
        ]);
        $this->get(route('exceptions.show', $exception))
            ->assertOk()
            ->assertSee('detail-header-card', false)
            ->assertSee('row g-4', false)
            ->assertDontSee('row g-4 mt-1', false);
    }
}
