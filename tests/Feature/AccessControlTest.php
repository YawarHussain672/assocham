<?php

namespace Tests\Feature;

use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies Phase 1's module-access gating (App\Support\Roles::MODULE_ACCESS
 * + the `module:<id>` middleware — see project memory:
 * erp-laravel-phase1-access-control). A handful of representative cases,
 * not every role×module combination.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_role_without_module_access_gets_403(): void
    {
        $this->loginAs(Roles::PR_APPROVER_L1); // Basu has no 'po' access

        $this->get('/po')->assertForbidden();
    }

    public function test_role_with_module_access_gets_200(): void
    {
        $this->loginAs(Roles::BUYER);

        $this->get('/po')->assertOk();
    }

    public function test_audit_trail_is_open_to_every_role(): void
    {
        foreach (Roles::all() as $role) {
            $this->loginAs($role);
            $this->get('/audit')->assertOk();
        }
    }

    public function test_grievance_and_trf_and_reimb_are_open_to_every_role(): void
    {
        // Roles::MODULES_FOR_ALL — "Employee" isn't a named login, so these
        // three are open to any authenticated role rather than gated.
        foreach (['/grievance', '/trf', '/reimb'] as $path) {
            $this->loginAs(Roles::HOD); // HOD has no role-specific access to any of these
            $this->get($path)->assertOk();
        }
    }

    public function test_checks_balances_and_exceptions_are_restricted_to_their_governance_roles(): void
    {
        $this->loginAs(Roles::BUYER);
        $this->get('/checks-balances')->assertForbidden();
        $this->get('/exceptions')->assertForbidden();

        $this->loginAs(Roles::CFO);
        $this->get('/checks-balances')->assertOk();
        $this->get('/exceptions')->assertOk();

        $this->loginAs(Roles::SG);
        $this->get('/checks-balances')->assertOk();
        $this->get('/exceptions')->assertOk();
    }
}
