<?php

namespace Tests\Feature;

use App\Mail\GrievanceStatusUpdatedMail;
use App\Models\AuditLog;
use App\Models\Grievance;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use App\Support\PendingItems;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiHodAndBuyerRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function createGrnForDepartment(string $department, User $creator, Vendor $vendor): Grn
    {
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-'.strtoupper(substr($department, 0, 3)).'-'.random_int(100, 999),
            'title' => "{$department} Equipment",
            'department' => $department,
            'category' => 'General',
            'estimated_value' => 50000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $creator->id,
        ]);

        $pr->items()->create([
            'item_name' => "{$department} Workstation",
            'quantity' => 5,
            'unit' => 'Units',
            'unit_cost' => 10000,
            'gst_percent' => 0,
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-'.random_int(1000, 9999),
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 50000,
            'po_qty' => 5,
            'status' => 'approved',
            'created_by' => $creator->id,
        ]);

        $grn = Grn::create([
            'grn_number' => 'GRN-'.random_int(1000, 9999),
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $pr->id,
            'item_description' => "{$department} Workstation",
            'po_qty' => 5,
            'received_qty' => 5,
            'receipt_date' => now()->toDateString(),
            'status' => 'pending_hod',
            'created_by' => $creator->id,
        ]);

        $grn->approvals()->create([
            'level' => 1,
            'role_required' => Roles::HOD,
            'status' => 'pending',
        ]);

        return $grn;
    }

    public function test_single_hod_handles_grns_and_grievances_across_all_departments(): void
    {
        Mail::fake();

        $soleHod = User::factory()->create([
            'name' => 'Sole Org HOD',
            'email' => 'sole.hod@assocham.test',
            'role' => Roles::HOD,
            'department' => 'Operations',
            'is_active' => true,
        ]);
        $itUser = User::factory()->create([
            'role' => Roles::PR_SR_EXEC,
            'department' => 'IT',
        ]);
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-S1',
            'company_name' => 'Single Mode Supplier',
            'status' => 'empanelled',
        ]);

        // GRN from IT is still visible & approvable by the sole active HOD
        $itGrn = $this->createGrnForDepartment('IT', $itUser, $vendor);

        $pending = PendingItems::for($soleHod);
        $this->assertTrue($pending->contains('number', $itGrn->grn_number));

        $this->actingAs($soleHod)
            ->post(route('grn.approve', $itGrn), ['comments' => 'Verified'])
            ->assertRedirect();

        $this->assertSame('approved', $itGrn->fresh()->status);

        // Grievance from IT is routed & emailed to the sole active HOD
        $this->actingAs($itUser)
            ->post(route('grievance.store'), [
                'subject_category' => 'Technical Issue',
                'subject' => 'Printer issue in IT floor',
                'description' => 'Need printer support in IT floor.',
            ])
            ->assertRedirect(route('grievance'));

        $grievance = Grievance::latest('id')->firstOrFail();
        $this->assertTrue(PendingItems::for($soleHod)->contains('number', $grievance->ticket_number));
        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo('sole.hod@assocham.test'));

        $this->actingAs($soleHod)
            ->post(route('grievance.resolve', $grievance), ['resolution_notes' => 'Resolved'])
            ->assertRedirect();
        $this->assertSame('resolved', $grievance->fresh()->status);
    }

    public function test_multiple_hods_are_strictly_department_scoped_for_grn_approvals(): void
    {
        $itHod = User::factory()->create([
            'name' => 'IT Department HOD',
            'role' => Roles::HOD,
            'department' => 'IT',
            'is_active' => true,
        ]);
        $hrHod = User::factory()->create([
            'name' => 'HR Department HOD',
            'role' => Roles::HOD,
            'department' => 'HR',
            'is_active' => true,
        ]);
        $creator = User::factory()->create([
            'role' => Roles::PR_SR_EXEC,
            'department' => 'IT',
        ]);
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-M1',
            'company_name' => 'Multi HOD Supplier',
            'status' => 'empanelled',
        ]);

        $itGrn = $this->createGrnForDepartment('IT', $creator, $vendor);
        $hrGrn = $this->createGrnForDepartment('HR', $creator, $vendor);

        // Pending queues are strictly department-scoped
        $itQueue = PendingItems::for($itHod)->pluck('number')->all();
        $hrQueue = PendingItems::for($hrHod)->pluck('number')->all();

        $this->assertContains($itGrn->grn_number, $itQueue);
        $this->assertNotContains($hrGrn->grn_number, $itQueue);
        $this->assertContains($hrGrn->grn_number, $hrQueue);
        $this->assertNotContains($itGrn->grn_number, $hrQueue);

        // GRN index scopes visible list per HOD
        $this->actingAs($itHod)
            ->get(route('grn'))
            ->assertOk()
            ->assertSee($itGrn->grn_number)
            ->assertDontSee($hrGrn->grn_number);

        // Cross-department approval is strictly forbidden (403)
        $this->actingAs($hrHod)
            ->post(route('grn.approve', $itGrn), ['comments' => 'Cross dept attempt'])
            ->assertForbidden();
        $this->assertSame('pending_hod', $itGrn->fresh()->status);

        // Matching department HOD approves cleanly
        $this->actingAs($itHod)
            ->post(route('grn.approve', $itGrn), ['comments' => 'Verified by IT HOD'])
            ->assertRedirect();
        $this->assertSame('approved', $itGrn->fresh()->status);
    }

    public function test_multiple_hods_are_strictly_department_scoped_for_grievances_and_emails(): void
    {
        Mail::fake();

        $adminHead = User::factory()->create([
            'name' => 'Admin Head',
            'email' => 'admin.head@assocham.test',
            'role' => Roles::ADMIN_HEAD,
            'is_active' => true,
        ]);
        $itHod = User::factory()->create([
            'name' => 'IT HOD',
            'email' => 'it.hod@assocham.test',
            'role' => Roles::HOD,
            'department' => 'IT',
            'is_active' => true,
        ]);
        $inactiveItHod = User::factory()->create([
            'name' => 'Former IT HOD',
            'email' => 'old.it.hod@assocham.test',
            'role' => Roles::HOD,
            'department' => 'IT',
            'is_active' => false,
        ]);
        $hrHod = User::factory()->create([
            'name' => 'HR HOD',
            'email' => 'hr.hod@assocham.test',
            'role' => Roles::HOD,
            'department' => 'HR',
            'is_active' => true,
        ]);
        $itStaff = User::factory()->create([
            'name' => 'IT Staff',
            'email' => 'it.staff@assocham.test',
            'role' => Roles::PR_SR_EXEC,
            'department' => 'IT',
        ]);

        // IT staff raises a grievance
        $this->actingAs($itStaff)
            ->post(route('grievance.store'), [
                'subject_category' => 'Operational Delay',
                'subject' => 'Vendor onboarding delay',
                'description' => 'IT vendor onboarding stuck.',
            ])
            ->assertRedirect(route('grievance'));

        $grievance = Grievance::latest('id')->firstOrFail();

        // Only active IT HOD gets the handler email — NOT HR HOD and NOT deactivated IT HOD
        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo('it.hod@assocham.test'));
        Mail::assertNotSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo('hr.hod@assocham.test'));
        Mail::assertNotSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo('old.it.hod@assocham.test'));

        // Queue & list privacy
        $this->assertTrue(PendingItems::for($itHod)->contains('number', $grievance->ticket_number));
        $this->assertFalse(PendingItems::for($hrHod)->contains('number', $grievance->ticket_number));

        // HR HOD cannot view, respond, escalate, or resolve IT's grievance
        $this->actingAs($hrHod)->get(route('grievance.show', $grievance))->assertForbidden();
        $this->actingAs($hrHod)->post(route('grievance.respond', $grievance), ['response_message' => 'Test'])->assertForbidden();
        $this->actingAs($hrHod)->post(route('grievance.escalate', $grievance))->assertForbidden();
        $this->actingAs($hrHod)->post(route('grievance.resolve', $grievance))->assertForbidden();

        // Admin Head retains global governance visibility
        $this->actingAs($adminHead)
            ->get(route('grievance.show', $grievance))
            ->assertOk()
            ->assertSee($grievance->ticket_number);

        // Active IT HOD responds cleanly
        $this->actingAs($itHod)
            ->post(route('grievance.respond', $grievance), ['response_message' => 'Investigating with IT procurement.'])
            ->assertRedirect();
    }

    public function test_unassigned_department_falls_back_to_admin_head_without_leaking_to_other_hods(): void
    {
        config(['app.allow_admin_approval_override' => false]);

        $adminHead = User::factory()->create([
            'role' => Roles::ADMIN_HEAD,
            'is_active' => true,
        ]);
        $itHod = User::factory()->create([
            'role' => Roles::HOD,
            'department' => 'IT',
            'is_active' => true,
        ]);
        $hrHod = User::factory()->create([
            'role' => Roles::HOD,
            'department' => 'HR',
            'is_active' => true,
        ]);
        $mktCreator = User::factory()->create([
            'role' => Roles::PR_SR_EXEC,
            'department' => 'Marketing',
        ]);
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-FB1',
            'company_name' => 'Fallback Supplier',
            'status' => 'empanelled',
        ]);

        // Marketing has NO active HOD and no General HOD
        $mktGrn = $this->createGrnForDepartment('Marketing', $mktCreator, $vendor);

        // Neither IT HOD nor HR HOD receives Marketing's GRN
        $this->assertFalse(PendingItems::for($itHod)->contains('number', $mktGrn->grn_number));
        $this->assertFalse(PendingItems::for($hrHod)->contains('number', $mktGrn->grn_number));
        $this->actingAs($itHod)->post(route('grn.approve', $mktGrn))->assertForbidden();

        // Admin Head receives deadlock-prevention fallback even with override toggle OFF
        $this->assertTrue(PendingItems::for($adminHead)->contains('number', $mktGrn->grn_number));
        $this->actingAs($adminHead)
            ->post(route('grn.approve', $mktGrn), ['comments' => 'Approved via Admin Head fallback'])
            ->assertRedirect();
        $this->assertSame('approved', $mktGrn->fresh()->status);
    }

    public function test_multi_buyer_auto_rfq_routing_load_balancing_and_reassignment(): void
    {
        Mail::fake();

        $deactivatedItBuyer = User::factory()->create([
            'name' => 'Inactive IT Buyer',
            'role' => Roles::BUYER,
            'department' => 'IT',
            'is_active' => false,
        ]);
        $activeItBuyer = User::factory()->create([
            'name' => 'Active IT Buyer',
            'role' => Roles::BUYER,
            'department' => 'IT',
            'is_active' => true,
        ]);
        $activeEventsBuyer = User::factory()->create([
            'name' => 'Active Events Buyer',
            'role' => Roles::BUYER,
            'department' => 'Events',
            'is_active' => true,
        ]);
        $prCreator = User::factory()->create([
            'role' => Roles::PR_SR_EXEC,
            'department' => 'IT',
        ]);

        // 1) IT PR auto-assigns to active IT Buyer (never deactivated IT Buyer)
        $itPr = PurchaseRequisition::create([
            'pr_number' => 'PR-MB-IT-1',
            'title' => 'IT Firewall',
            'department' => 'IT',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prCreator->id,
        ]);
        $itRfq = $itPr->autoCreateRfq();
        $this->assertNotNull($itRfq);
        $this->assertSame($activeItBuyer->id, (int) $itRfq->poc_user_id);

        // 2) Events PR auto-assigns to active Events Buyer
        $eventsPr = PurchaseRequisition::create([
            'pr_number' => 'PR-MB-EV-1',
            'title' => 'Annual Summit Stage',
            'department' => 'Events',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prCreator->id,
        ]);
        $eventsRfq = $eventsPr->autoCreateRfq();
        $this->assertSame($activeEventsBuyer->id, (int) $eventsRfq->poc_user_id);

        // 3) Unmatched department (Finance) load-balances to the least-loaded active Buyer
        // First give activeItBuyer a second open RFQ so activeEventsBuyer has fewer open RFQs (1 vs 2)
        Rfq::create([
            'rfq_number' => 'RFQ-LOAD-1',
            'sector' => 'IT',
            'project_name' => 'IT Load Seed',
            'purchase_requisition_id' => $itPr->id,
            'requisition_date' => now()->toDateString(),
            'poc_user_id' => $activeItBuyer->id,
            'status' => 'dispatched',
        ]);
        $financePr = PurchaseRequisition::create([
            'pr_number' => 'PR-MB-FIN-1',
            'title' => 'Finance Audit Software',
            'department' => 'Finance',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prCreator->id,
        ]);
        $financeRfq = $financePr->autoCreateRfq();
        $this->assertSame($activeEventsBuyer->id, (int) $financeRfq->poc_user_id);

        // 4) Buyer can reassign RFQ ownership to another active Buyer, audit-logged
        $this->actingAs($activeItBuyer)
            ->post(route('rfq.assign-buyer', $itRfq), [
                'poc_user_id' => $activeEventsBuyer->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame($activeEventsBuyer->id, (int) $itRfq->fresh()->poc_user_id);
        $this->assertTrue(
            AuditLog::where('module', 'Rfq')
                ->where('action', 'reassigned')
                ->where('reference', $itRfq->rfq_number)
                ->exists()
        );

        // 5) Reassigning to a deactivated Buyer is rejected cleanly
        $this->actingAs($activeItBuyer)
            ->from(route('rfq.show', $itRfq))
            ->post(route('rfq.assign-buyer', $itRfq), [
                'poc_user_id' => $deactivatedItBuyer->id,
            ])
            ->assertRedirect(route('rfq.show', $itRfq))
            ->assertSessionHas('error');
        $this->assertSame($activeEventsBuyer->id, (int) $itRfq->fresh()->poc_user_id);

        // 6) Non-Buyer / non-Admin cannot reassign RFQ
        $this->actingAs($prCreator)
            ->post(route('rfq.assign-buyer', $itRfq), [
                'poc_user_id' => $activeItBuyer->id,
            ])
            ->assertForbidden();
    }
}
