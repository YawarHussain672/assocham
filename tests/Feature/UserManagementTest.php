<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
}
