<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_only_admin_head_sees_users_module_and_can_access_user_management(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        User::create([
            'name' => 'Anmol Kumar',
            'email' => 'anmol.test@assocham.test',
            'password' => 'SecretPass123',
            'role' => 'membership_ops',
            'department' => 'Membership Ops',
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Priyanka Iyer',
            'email' => 'priyanka.test@assocham.test',
            'password' => 'SecretPass123',
            'role' => 'b2b_ops',
            'department' => 'B2B Networking',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($adminHead)
            ->get(route('users'))
            ->assertOk()
            ->assertSee('User &amp; Access Management', false)
            ->assertSee('class="page-header', false)
            ->assertSee('class="page-title"', false)
            ->assertSee('Member Roster')
            ->assertSee('btn-warning text-dark', false)
            ->assertSee('Membership Operations')
            ->assertSee('B2B Networking')
            ->assertDontSee('>membership_ops<', false)
            ->assertDontSee('>b2b_ops<', false)
            ->assertSee('data-module="users"', false);

        $this->actingAs($adminHead)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Add New Member')
            ->assertSee('card-panel', false)
            ->assertSee('Dashboard Always On');

        $nonAdminRoles = [
            Roles::BUYER,
            Roles::PR_SR_EXEC,
            Roles::CFO,
            Roles::SG,
            Roles::HOD,
        ];

        foreach ($nonAdminRoles as $role) {
            $user = User::where('role', $role)->firstOrFail();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('data-module="users"', false);

            $this->actingAs($user)
                ->get(route('users'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('users.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('users.store'), [
                    'name' => 'Blocked User',
                    'email' => "blocked_{$role}@assocham.test",
                    'password' => 'SecretPass123',
                    'password_confirmation' => 'SecretPass123',
                    'role' => Roles::PR_SR_EXEC,
                    'designation' => 'Analyst',
                    'department' => 'IT',
                ])
                ->assertForbidden();
        }
    }

    public function test_admin_head_can_create_member_with_custom_designation_department_and_module_access(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $response = $this->actingAs($adminHead)->post(route('users.store'), [
            'name' => 'Ananya Sharma',
            'email' => 'ananya.sharma@assocham.test',
            'password' => 'Welcome@2026',
            'password_confirmation' => 'Welcome@2026',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Procurement Coordinator',
            'department' => 'Projects',
            'allowed_modules' => ['requisition', 'trf', 'grievance'],
        ]);

        $response->assertRedirect(route('users'));

        $created = User::where('email', 'ananya.sharma@assocham.test')->first();
        $this->assertNotNull($created);
        $this->assertSame('Ananya Sharma', $created->name);
        $this->assertSame(Roles::PR_SR_EXEC, $created->role);
        $this->assertSame('Procurement Coordinator', $created->designation);
        $this->assertSame('Projects', $created->department);
        $this->assertSame(['requisition', 'trf', 'grievance'], $created->allowed_modules);
        $this->assertTrue(Hash::check('Welcome@2026', $created->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'UserManagement',
            'action' => 'created',
            'reference' => 'ananya.sharma@assocham.test',
        ]);

        // Log out admin and sign in as the newly created employee via /login
        $this->post('/logout');

        $loginResponse = $this->post('/login', [
            'email' => 'ananya.sharma@assocham.test',
            'password' => 'Welcome@2026',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($created);

        // Verify sidebar shows ONLY dashboard + chosen modules
        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertOk()
            ->assertSee('Procurement Coordinator')
            ->assertSee('data-module="dashboard"', false)
            ->assertSee('data-module="requisition"', false)
            ->assertSee('data-module="trf"', false)
            ->assertSee('data-module="grievance"', false)
            ->assertDontSee('data-module="po"', false)
            ->assertDontSee('data-module="rfq"', false)
            ->assertDontSee('data-module="grn"', false)
            ->assertDontSee('data-module="checks-balances"', false)
            ->assertDontSee('data-module="audit"', false)
            ->assertDontSee('data-module="notifications"', false)
            ->assertDontSee('data-module="users"', false);

        // Verify allowed module routes work and unselected routes are strictly 403
        $this->get(route('requisition'))->assertOk();
        $this->get(route('trf'))->assertOk();
        $this->get(route('grievance'))->assertOk();

        $this->get(route('po'))->assertForbidden();
        $this->get(route('rfq'))->assertForbidden();
        $this->get(route('grn'))->assertForbidden();
        $this->get(route('checks-balances'))->assertForbidden();
        $this->get(route('audit'))->assertForbidden();
        $this->get(route('notifications'))->assertForbidden();
        $this->get(route('users'))->assertForbidden();
    }

    public function test_admin_head_can_edit_member_modules_department_designation_and_password(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $member = User::create([
            'name' => 'Karan Verma',
            'email' => 'karan.verma@assocham.test',
            'password' => 'InitialPass@1',
            'role' => Roles::BUYER,
            'designation' => 'Junior Buyer',
            'department' => 'IT',
            'allowed_modules' => ['rfq', 'grievance'],
            'email_verified_at' => now(),
        ]);

        $this->actingAs($adminHead)
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('Karan Verma')
            ->assertSee('Junior Buyer');

        $updateResponse = $this->actingAs($adminHead)->put(route('users.update', $member), [
            'name' => 'Karan Verma',
            'email' => 'karan.verma@assocham.test',
            'password' => 'UpdatedPass@2',
            'password_confirmation' => 'UpdatedPass@2',
            'role' => Roles::BUYER,
            'designation' => 'Lead Procurement Buyer',
            'department' => '__custom__',
            'custom_department' => 'Supply Chain',
            'allowed_modules' => ['po', 'empanelment'],
        ]);

        $updateResponse->assertRedirect(route('users'));

        $member->refresh();
        $this->assertSame('Lead Procurement Buyer', $member->designation);
        $this->assertSame('Supply Chain', $member->department);
        $this->assertSame(['empanelment', 'po'], array_values(collect($member->allowed_modules)->sort()->all()));
        $this->assertTrue(Hash::check('UpdatedPass@2', $member->password));

        $this->assertTrue(
            AuditLog::where('module', 'UserManagement')
                ->where('action', 'updated')
                ->where('reference', 'karan.verma@assocham.test')
                ->exists()
        );

        // Old password rejected, new password accepted
        $this->post('/logout');

        $this->from('/login')->post('/login', [
            'email' => 'karan.verma@assocham.test',
            'password' => 'InitialPass@1',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => 'karan.verma@assocham.test',
            'password' => 'UpdatedPass@2',
        ])->assertRedirect(route('dashboard'));

        // Updated module permissions take effect immediately
        $this->get(route('po'))->assertOk();
        $this->get(route('empanelment'))->assertOk();
        $this->get(route('rfq'))->assertForbidden();
        $this->get(route('grievance'))->assertForbidden();
    }

    public function test_admin_head_can_deactivate_and_reactivate_user_with_login_and_session_lockout(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $member = User::create([
            'name' => 'Riya Kapoor',
            'email' => 'riya.kapoor@assocham.test',
            'password' => 'MemberPass@1',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Senior Executive',
            'department' => 'IT',
            'email_verified_at' => now(),
        ]);

        // Deactivate the user
        $this->actingAs($adminHead)
            ->post(route('users.toggle-status', $member))
            ->assertRedirect(route('users'))
            ->assertSessionHas('status');

        $member->refresh();
        $this->assertFalse($member->is_active);
        $this->assertNotNull($member->deactivated_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'UserManagement',
            'action' => 'deactivated',
            'reference' => 'riya.kapoor@assocham.test',
        ]);

        // Roster filter for deactivated shows Riya
        $this->actingAs($adminHead)
            ->get(route('users', ['status' => 'deactivated']))
            ->assertOk()
            ->assertSee('Riya Kapoor')
            ->assertSee('Deactivated');

        // Deactivated user cannot sign in via /login
        $this->post('/logout');
        $this->from('/login')->post('/login', [
            'email' => 'riya.kapoor@assocham.test',
            'password' => 'MemberPass@1',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Deactivated user cannot request forgot-password link
        $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'riya.kapoor@assocham.test',
        ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        // Active session for a deactivated user is immediately logged out by `active` middleware
        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Admin Head reactivates the user
        $this->actingAs($adminHead)
            ->post(route('users.toggle-status', $member))
            ->assertRedirect(route('users'))
            ->assertSessionHas('status');

        $member->refresh();
        $this->assertTrue($member->is_active);
        $this->assertNull($member->deactivated_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'UserManagement',
            'action' => 'reactivated',
            'reference' => 'riya.kapoor@assocham.test',
        ]);

        // Reactivated user can sign in again
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'riya.kapoor@assocham.test',
            'password' => 'MemberPass@1',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($member);
    }

    public function test_admin_head_cannot_deactivate_or_delete_own_account(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $this->actingAs($adminHead)
            ->from(route('users'))
            ->post(route('users.toggle-status', $adminHead))
            ->assertRedirect(route('users'))
            ->assertSessionHas('error');

        $this->actingAs($adminHead)
            ->from(route('users'))
            ->delete(route('users.destroy', $adminHead))
            ->assertRedirect(route('users'))
            ->assertSessionHas('error');

        $adminHead->refresh();
        $this->assertTrue($adminHead->is_active);
        $this->assertDatabaseHas('users', ['id' => $adminHead->id]);
    }

    public function test_admin_head_can_delete_unused_member_and_deleting_member_with_records_retains_all_historical_data(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $unusedMember = User::create([
            'name' => 'Temp Account',
            'email' => 'temp.unused@assocham.test',
            'password' => 'TempPass@1',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Intern',
            'department' => 'IT',
            'email_verified_at' => now(),
        ]);

        // Safe permanent delete succeeds for member with zero operational records
        $this->actingAs($adminHead)
            ->delete(route('users.destroy', $unusedMember))
            ->assertRedirect(route('users'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $unusedMember->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'UserManagement',
            'action' => 'deleted',
            'reference' => 'temp.unused@assocham.test',
        ]);

        // Member with linked operational records (PR, PO, etc.)
        $activeMember = User::create([
            'name' => 'Devika Rao',
            'email' => 'devika.rao@assocham.test',
            'password' => 'DevikaPass@1',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'PR Executive',
            'department' => 'IT',
            'email_verified_at' => now(),
        ]);

        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-DEL-GUARD-01',
            'title' => 'Server Rack Maintenance',
            'department' => 'IT',
            'category' => 'IT Hardware',
            'estimated_value' => 25000,
            'status' => 'pending_l1',
            'created_by' => $activeMember->id,
        ]);

        // Deleting member with operational records succeeds with data retention
        $this->actingAs($adminHead)
            ->from(route('users'))
            ->delete(route('users.destroy', $activeMember))
            ->assertRedirect(route('users'))
            ->assertSessionHas('status');

        // User is soft-deleted, not purged, so DB cascades never fire
        $this->assertSoftDeleted('users', ['id' => $activeMember->id]);
        $this->assertFalse($activeMember->fresh()->isActive());

        // Historical PR and associated data remain completely intact
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
        $refreshedPr = $pr->fresh();
        $this->assertNotNull($refreshedPr);
        $this->assertSame($activeMember->id, $refreshedPr->created_by);
        $this->assertSame('Devika Rao', $refreshedPr->creator->name);

        // Audit log records that user was deleted with operational history safely preserved
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'UserManagement',
            'action' => 'deleted',
            'reference' => 'devika.rao@assocham.test',
        ]);
    }

    public function test_deleted_user_cannot_login_and_all_historical_records_remain_intact_across_modules(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $employee = User::create([
            'name' => 'Vikram Malhotra',
            'email' => 'vikram.malhotra@assocham.test',
            'password' => 'VikramPass@2026',
            'role' => Roles::PR_SR_EXEC,
            'designation' => 'Senior Procurement Specialist',
            'department' => 'Logistics',
            'email_verified_at' => now(),
        ]);

        // Create historical records across multiple modules
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-DATA-RET-01',
            'title' => 'Annual Freight Transport Agreement',
            'department' => 'Logistics',
            'category' => 'Logistics',
            'estimated_value' => 150000,
            'status' => 'approved',
            'created_by' => $employee->id,
        ]);

        $vendor = \App\Models\Vendor::first() ?? \App\Models\Vendor::create([
            'company_name' => 'Test Vendor Data Ret',
            'vendor_code' => 'VEND-RET-01',
            'email' => 'retvendor@test.com',
            'phone' => '9998887776',
            'status' => 'empanelled',
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-DATA-RET-01',
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'created_by' => $employee->id,
            'amount_incl_gst' => 150000,
            'status' => 'issued',
        ]);

        $trf = \App\Models\Trf::create([
            'trf_number' => 'TRF-DATA-RET-01',
            'employee_id' => $employee->id,
            'destination' => 'Mumbai',
            'purpose' => 'Logistics Vendor Audit',
            'status' => 'approved',
        ]);

        $grievance = \App\Models\Grievance::create([
            'ticket_number' => 'GRV-DATA-RET-01',
            'subject' => 'Query regarding reimbursement processing',
            'subject_category' => 'Operational Delay',
            'raised_by' => $employee->id,
            'current_owner_role' => Roles::CFO,
            'status' => 'open',
        ]);

        // Delete the employee
        $this->actingAs($adminHead)
            ->delete(route('users.destroy', $employee))
            ->assertRedirect(route('users'))
            ->assertSessionHas('status');

        // Employee is soft-deleted
        $this->assertSoftDeleted('users', ['id' => $employee->id]);

        // Attempting to log in as deleted employee fails
        $this->post('/logout');
        $this->from('/login')->post('/login', [
            'email' => 'vikram.malhotra@assocham.test',
            'password' => 'VikramPass@2026',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Historical PR, PO, TRF, and Grievance are 100% intact
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id]);
        $this->assertDatabaseHas('trfs', ['id' => $trf->id]);
        $this->assertDatabaseHas('grievances', ['id' => $grievance->id]);

        // Relationships resolve to the soft-deleted user without null pointer exceptions
        $this->assertSame('Vikram Malhotra', $pr->fresh()->creator->name);
        $this->assertSame('Vikram Malhotra', $po->fresh()->creator->name);
        $this->assertSame('Vikram Malhotra', $trf->fresh()->employee->name);
        $this->assertSame('Vikram Malhotra', $grievance->fresh()->raisedBy->name);

        // Attempting to force-delete user with operational records is blocked by model guard
        $this->expectException(\RuntimeException::class);
        $employee->forceDelete();
    }

    public function test_non_admin_cannot_toggle_status_or_delete_user(): void
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $target = User::where('role', Roles::PR_SR_EXEC)->firstOrFail();

        $this->actingAs($buyer)
            ->post(route('users.toggle-status', $target))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->delete(route('users.destroy', $target))
            ->assertForbidden();

        $target->refresh();
        $this->assertTrue($target->is_active);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
