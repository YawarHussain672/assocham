<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\CostComparativeStatement;
use App\Models\ExceptionRequest;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\Reimbursement;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class ApprovalStatusesAndBlacklistTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createPendingVendor(): Vendor
    {
        $this->loginAs(Roles::BUYER);
        $this->post(route('onboarding.store'), [
            'company_name' => 'Apex Test Supplies Ltd',
            'pan' => 'AAACT9999E',
            'category' => 'Hardware',
        ]);

        return Vendor::where('company_name', 'Apex Test Supplies Ltd')->firstOrFail();
    }

    public function test_vendor_onboarding_can_be_put_on_hold_approved_and_rejected(): void
    {
        $vendor = $this->createPendingVendor();
        $this->assertSame('pending_admin_head', $vendor->status);

        // 1. Put On Hold by current approver (Admin Head)
        $this->loginAs(Roles::ADMIN_HEAD);
        $resHold = $this->from(route('empanelment.show', $vendor))
            ->post(route('empanelment.hold', $vendor), [
                'comments' => 'Awaiting tax clearance document',
            ]);
        $resHold->assertRedirect(route('empanelment.show', $vendor));

        $vendor->refresh();
        $this->assertSame('on_hold', $vendor->status);
        $currentApproval = $vendor->currentApprovalFor(auth()->user());
        $this->assertNotNull($currentApproval);
        $this->assertSame(Approval::STATUS_ON_HOLD, $currentApproval->status);
        $this->assertTrue($vendor->isOnHold());

        // 2. Resume & Approve by current approver from On Hold
        $resApprove = $this->from(route('empanelment.show', $vendor))
            ->post(route('empanelment.approve', $vendor), [
                'comments' => 'Tax clearance verified, advancing to next step',
            ]);
        $resApprove->assertRedirect(route('empanelment.show', $vendor));

        $vendor->refresh();
        $level1Approval = $vendor->approvals()->where('level', 1)->first();
        $this->assertSame(Approval::STATUS_APPROVED, $level1Approval->status);

        // Next pending step
        $nextStep = $vendor->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->first();
        $this->assertNotNull($nextStep);

        // 3. Next approver can reject
        $this->loginAs($nextStep->role_required);
        $resReject = $this->from(route('empanelment.show', $vendor))
            ->post(route('empanelment.reject', $vendor), [
                'comments' => 'Vendor background check failed',
            ]);
        $resReject->assertRedirect(route('empanelment.show', $vendor));

        $vendor->refresh();
        $this->assertSame('rejected', $vendor->status);
        $this->assertFalse($vendor->isEmpanelled());
    }

    public function test_vendor_can_be_blacklisted_and_disqualified_from_further_transactions(): void
    {
        $vendor = $this->createPendingVendor();
        $this->assertFalse($vendor->isBlacklisted());

        // Blacklist by Admin Head
        $this->loginAs(Roles::ADMIN_HEAD);
        $resBlacklist = $this->from(route('empanelment.show', $vendor))
            ->post(route('empanelment.blacklist', $vendor), [
                'reason' => 'Submitted fraudulent MSME certificate and falsified GST',
            ]);
        $resBlacklist->assertRedirect(route('empanelment.show', $vendor));

        $vendor->refresh();
        $this->assertSame('blacklisted', $vendor->status);
        $this->assertSame('blacklisted', $vendor->compliance_status);
        $this->assertTrue($vendor->isBlacklisted());
        $this->assertFalse($vendor->isEmpanelled());

        // Any pending approvals are cancelled/rejected
        $activeApprovals = $vendor->approvals()->whereIn('status', ['pending', 'on_hold'])->count();
        $this->assertSame(0, $activeApprovals);

        // Create an approved PR to test transactions
        $this->loginAs(Roles::PR_SR_EXEC);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-BLACK-TEST-001',
            'created_by' => auth()->id(),
            'department' => 'IT',
            'title' => 'Hardware Purchase',
            'estimated_cost' => 50000,
            'status' => 'approved',
        ]);

        // Disqualified from RFQ invitations
        $this->loginAs(Roles::BUYER);
        $rfqRes = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT',
            'project_name' => 'IT Infrastructure Unique ' . uniqid(),
            'vendor_ids' => [$vendor->id],
            'submission_deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);
        $rfqRes->assertStatus(422);

        // Disqualified from PO creation
        $poRes = $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'payment_terms' => 'Net 30',
            'delivery_schedule' => 'Immediate',
        ]);
        $poRes->assertStatus(422);
    }

    public function test_purchase_requisition_hold_approve_and_reject_workflow(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-TEST-HOLD-001',
            'created_by' => auth()->id(),
            'department' => 'IT',
            'title' => 'Test Hardware Upgrade',
            'estimated_cost' => 45000,
            'status' => 'pending_l1',
        ]);

        $appL1 = Approval::create([
            'approvable_type' => PurchaseRequisition::class,
            'approvable_id' => $pr->id,
            'level' => 1,
            'role_required' => Roles::PR_APPROVER_L1,
            'status' => 'pending',
        ]);

        // L1 Approver puts PR On Hold
        $this->loginAs(Roles::PR_APPROVER_L1);
        $resHold = $this->from(route('requisition.show', $pr))
            ->post(route('requisition.hold', $pr), [
                'comments' => 'Pending revised quote comparison',
            ]);
        $resHold->assertRedirect(route('requisition.show', $pr));

        $pr->refresh();
        $this->assertSame('on_hold', $pr->status);
        $appL1->refresh();
        $this->assertSame('on_hold', $appL1->status);

        // L1 Approver can approve from On Hold
        $resApprove = $this->from(route('requisition.show', $pr))
            ->post(route('requisition.approve', $pr), [
                'comments' => 'Quote confirmed, approved',
            ]);
        $resApprove->assertRedirect(route('requisition.show', $pr));

        $pr->refresh();
        $appL1->refresh();
        $this->assertSame('approved', $appL1->status);
        $this->assertSame('approved', $pr->status); // Single level chain completes
    }

    public function test_purchase_order_hold_workflow(): void
    {
        $this->loginAs(Roles::BUYER);
        $vendor = Vendor::where('status', 'approved')->first() ?? Vendor::create([
            'vendor_code' => 'VEND-TST-1',
            'company_name' => 'Active Approved Vendor',
            'status' => 'approved',
            'compliance_status' => 'empanelled',
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-PO-TEST-001',
            'created_by' => auth()->id(),
            'department' => 'IT',
            'title' => 'PO Requisition',
            'estimated_cost' => 20000,
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-HOLD-001',
            'created_by' => auth()->id(),
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'po_qty' => 10,
            'amount_incl_gst' => 20000,
            'status' => 'pending_asg',
        ]);

        $app = Approval::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'level' => 1,
            'role_required' => Roles::ADMIN_HEAD,
            'status' => 'pending',
        ]);

        // Approver puts PO on hold
        $this->loginAs(Roles::ADMIN_HEAD);
        $res = $this->from(route('po.show', $po))
            ->post(route('po.hold', $po), [
                'comments' => 'Awaiting confirmation of shipping timeline',
            ]);
        $res->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame('on_hold', $po->status);
        $app->refresh();
        $this->assertSame('on_hold', $app->status);

        // Resume and approve from hold
        $resApprove = $this->from(route('po.show', $po))
            ->post(route('po.approve', $po), [
                'comments' => 'Timeline confirmed, PO released',
            ]);
        $resApprove->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame('approved', $po->status);
    }

    public function test_trf_hold_and_reject_workflow(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);
        $trf = Trf::create([
            'trf_number' => 'TRF-TEST-HOLD-001',
            'employee_id' => auth()->id(),
            'department' => 'Operations',
            'travel_start' => now()->addDays(2),
            'travel_end' => now()->addDays(5),
            'status' => 'pending_asg',
        ]);

        $app = Approval::create([
            'approvable_type' => Trf::class,
            'approvable_id' => $trf->id,
            'level' => 1,
            'role_required' => Roles::ASG1,
            'status' => 'pending',
        ]);

        $this->loginAs(Roles::ASG1);

        // Put on hold
        $res = $this->from(route('trf.show', $trf))
            ->post(route('trf.hold', $trf), [
                'comments' => 'Clarification required regarding hotel rates',
            ]);
        $res->assertRedirect(route('trf.show', $trf));

        $trf->refresh();
        $this->assertSame('on_hold', $trf->status);
        $app->refresh();
        $this->assertSame('on_hold', $app->status);

        // Reject
        $resReject = $this->from(route('trf.show', $trf))
            ->post(route('trf.reject', $trf), [
                'comments' => 'Travel not justified at this time',
            ]);
        $resReject->assertRedirect(route('trf.show', $trf));

        $trf->refresh();
        $this->assertSame('rejected', $trf->status);
    }

    public function test_exception_request_hold_approve_reject(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);
        $exception = ExceptionRequest::create([
            'exception_type' => 'budget_exceed',
            'requested_by' => auth()->id(),
            'justification' => 'Urgent critical infrastructure renewal',
            'approver_queue' => Roles::CFO,
            'status' => 'pending',
        ]);

        // CFO puts on hold
        $this->loginAs(Roles::CFO);
        $resHold = $this->from(route('exceptions.show', $exception))
            ->post(route('exceptions.hold', $exception), [
                'comments' => 'Consulting board on budget reallocation',
            ]);
        $resHold->assertRedirect(route('exceptions.show', $exception));

        $exception->refresh();
        $this->assertSame('on_hold', $exception->status);

        // CFO approves from on hold
        $resApprove = $this->from(route('exceptions.show', $exception))
            ->post(route('exceptions.approve', $exception));
        $resApprove->assertRedirect(route('exceptions.show', $exception));

        $exception->refresh();
        $this->assertSame('approved', $exception->status);
        $this->assertSame(auth()->id(), $exception->decided_by);
    }

    public function test_grn_hold_and_approve_workflow(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $vendor = Vendor::where('status', 'approved')->first() ?? Vendor::create([
            'vendor_code' => 'VEND-TST-GRN',
            'company_name' => 'GRN Hardware Vendor',
            'status' => 'approved',
            'compliance_status' => 'empanelled',
        ]);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-GRN-TEST-001',
            'created_by' => auth()->id(),
            'department' => 'Operations',
            'title' => 'Operations Gear',
            'estimated_cost' => 15000,
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-GRN-TEST-001',
            'created_by' => auth()->id(),
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'po_qty' => 5,
            'amount_incl_gst' => 15000,
            'status' => 'approved',
        ]);

        $grn = Grn::create([
            'grn_number' => 'GRN-TEST-HOLD-001',
            'purchase_order_id' => $po->id,
            'po_qty' => 5,
            'received_qty' => 5,
            'created_by' => auth()->id(),
            'status' => 'pending_hod',
        ]);

        $app = Approval::create([
            'approvable_type' => Grn::class,
            'approvable_id' => $grn->id,
            'level' => 1,
            'role_required' => Roles::HOD,
            'status' => 'pending',
        ]);

        // HOD puts GRN on hold
        $this->loginAs(Roles::HOD);
        $resHold = $this->from(route('grn.show', $grn))
            ->post(route('grn.hold', $grn), [
                'comments' => 'Physical inspection of items required before closing',
            ]);
        $resHold->assertRedirect(route('grn.show', $grn));

        $grn->refresh();
        $this->assertSame('on_hold', $grn->status);
        $app->refresh();
        $this->assertSame('on_hold', $app->status);

        // HOD approves GRN from hold
        $resApprove = $this->from(route('grn.show', $grn))
            ->post(route('grn.approve', $grn), [
                'comments' => 'Inspection completed, items in good order',
            ]);
        $resApprove->assertRedirect(route('grn.show', $grn));

        $grn->refresh();
        $this->assertSame('approved', $grn->status);
    }

    public function test_reimbursement_hold_and_approve_workflow(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $reimb = Reimbursement::create([
            'claim_number' => 'REIMB-TEST-HOLD-001',
            'employee_id' => auth()->id(),
            'claim_type' => 'travel_advance',
            'claim_amount' => 8500,
            'status' => 'pending_cfo',
        ]);

        $app = Approval::create([
            'approvable_type' => Reimbursement::class,
            'approvable_id' => $reimb->id,
            'level' => 1,
            'role_required' => Roles::CFO,
            'status' => 'pending',
        ]);

        // CFO puts reimbursement on hold
        $this->loginAs(Roles::CFO);
        $resHold = $this->from(route('reimb.show', $reimb))
            ->post(route('reimb.hold', $reimb), [
                'comments' => 'Awaiting boarding pass submission',
            ]);
        $resHold->assertRedirect(route('reimb.show', $reimb));

        $reimb->refresh();
        $this->assertSame('on_hold', $reimb->status);
        $app->refresh();
        $this->assertSame('on_hold', $app->status);

        // CFO approves from on hold
        $resApprove = $this->from(route('reimb.show', $reimb))
            ->post(route('reimb.approve', $reimb), [
                'comments' => 'Boarding pass verified, advance approved',
            ]);
        $resApprove->assertRedirect(route('reimb.show', $reimb));

        $reimb->refresh();
        $this->assertSame('approved', $reimb->status);
    }
}
