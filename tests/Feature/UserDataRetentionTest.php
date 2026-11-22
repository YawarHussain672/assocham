<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\CostComparativeStatement;
use App\Models\ExceptionRequest;
use App\Models\FlightLog;
use App\Models\Grievance;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Reimbursement;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_deletion_safely_retains_pr_po_grn_trf_and_audit_history(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        // 1. Create a user who performs work across modules
        $worker = User::create([
            'name' => 'Kavita Krishnan',
            'email' => 'kavita.krishnan@assocham.test',
            'password' => 'SecurePass@2026',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Executive Procurement',
            'department' => 'Operations',
            'email_verified_at' => now(),
        ]);

        $vendor = Vendor::first() ?? Vendor::create([
            'company_name' => 'Global Logistics Corp',
            'vendor_code' => 'VEND-RET-99',
            'email' => 'vendor99@test.com',
            'status' => 'empanelled',
        ]);

        // 2. Create historical PR
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-RET-101',
            'title' => 'Warehouse Safety Equipment',
            'department' => 'Operations',
            'category' => 'Safety',
            'estimated_value' => 75000,
            'status' => 'approved',
            'created_by' => $worker->id,
        ]);

        // 3. Create historical PO
        $po = PurchaseOrder::create([
            'po_number' => 'PO-RET-101',
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'created_by' => $worker->id,
            'amount_incl_gst' => 75000,
            'status' => 'issued',
        ]);

        // 4. Create historical GRN
        $grn = Grn::create([
            'grn_number' => 'GRN-RET-101',
            'purchase_order_id' => $po->id,
            'item_description' => 'Warehouse Safety Equipment',
            'po_qty' => 10,
            'received_qty' => 10,
            'created_by' => $worker->id,
            'status' => 'approved',
            'receipt_date' => now()->toDateString(),
            'quality_remarks' => 'All items received in good condition.',
        ]);

        // 5. Create historical TRF
        $trf = Trf::create([
            'trf_number' => 'TRF-RET-101',
            'employee_id' => $worker->id,
            'destination' => 'Bengaluru',
            'purpose' => 'Warehouse Inspection',
            'status' => 'approved',
        ]);

        // 6. Create historical Reimbursement
        $reimb = Reimbursement::create([
            'claim_number' => 'CLM-RET-101',
            'employee_id' => $worker->id,
            'trf_id' => $trf->id,
            'claim_amount' => 4500,
            'status' => 'submitted',
        ]);

        // 7. Create historical Grievance
        $grievance = Grievance::create([
            'ticket_number' => 'GRV-RET-101',
            'subject' => 'Delayed flight baggage reimbursement',
            'subject_category' => 'Operational Delay',
            'raised_by' => $worker->id,
            'current_owner_role' => Roles::ADMIN_HEAD,
            'status' => 'under_review',
        ]);

        // 8. Create historical Audit Log
        $auditLog = AuditLog::create([
            'user_id' => $worker->id,
            'module' => 'Procurement',
            'action' => 'created',
            'reference' => 'PR-RET-101',
            'detail' => 'Raised requisition for warehouse equipment.',
        ]);

        // 9. Admin deletes the user account
        $response = $this->actingAs($adminHead)
            ->delete(route('users.destroy', $worker));

        $response->assertRedirect(route('users'))
            ->assertSessionHas('status');

        // 10. Verify User is soft-deleted, not purged
        $worker->refresh();
        $this->assertTrue($worker->trashed());
        $this->assertFalse($worker->isActive());
        $this->assertDatabaseHas('users', ['id' => $worker->id]);

        // 11. Verify all operational records remain 100% intact
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id]);
        $this->assertDatabaseHas('grns', ['id' => $grn->id]);
        $this->assertDatabaseHas('trfs', ['id' => $trf->id]);
        $this->assertDatabaseHas('reimbursements', ['id' => $reimb->id]);
        $this->assertDatabaseHas('grievances', ['id' => $grievance->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $auditLog->id]);

        // 12. Verify all belongsTo relations resolve the soft-deleted user cleanly
        $this->assertSame('Kavita Krishnan', $pr->fresh()->creator->name);
        $this->assertSame('Kavita Krishnan', $po->fresh()->creator->name);
        $this->assertSame('Kavita Krishnan', $grn->fresh()->creator->name);
        $this->assertSame('Kavita Krishnan', $trf->fresh()->employee->name);
        $this->assertSame('Kavita Krishnan', $reimb->fresh()->employee->name);
        $this->assertSame('Kavita Krishnan', $grievance->fresh()->raisedBy->name);
        $this->assertSame('Kavita Krishnan', $auditLog->fresh()->user->name);

        // 13. Verify pages render without errors when displaying deleted user's records
        $this->get(route('requisition.show', $pr))
            ->assertOk()
            ->assertSee('Kavita Krishnan');

        $this->get(route('po.show', $po))
            ->assertOk()
            ->assertSee('Kavita Krishnan');

        $this->get(route('trf.show', $trf))
            ->assertOk()
            ->assertSee('Kavita Krishnan');

        $this->get(route('reimb.show', $reimb))
            ->assertOk()
            ->assertSee('Kavita Krishnan');

        // 14. Verify deleted user appears in user roster when filtered by 'deleted'
        $usersPage = $this->get(route('users', ['status' => 'deleted']));
        $usersPage->assertOk()
            ->assertSee('Kavita Krishnan')
            ->assertSee('Deleted / Archived')
            ->assertSee('Archived');
    }

    public function test_force_delete_is_prevented_when_operational_records_exist(): void
    {
        $user = User::create([
            'name' => 'Protected Member',
            'email' => 'protected@assocham.test',
            'password' => 'TestPass@1',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Executive',
            'department' => 'IT',
        ]);

        PurchaseRequisition::create([
            'pr_number' => 'PR-GUARD-99',
            'title' => 'Protected PR',
            'department' => 'IT',
            'category' => 'Hardware',
            'estimated_value' => 10000,
            'status' => 'pending_l1',
            'created_by' => $user->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot force delete user Protected Member');

        $user->forceDelete();
    }
}
