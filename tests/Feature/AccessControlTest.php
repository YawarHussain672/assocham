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

    public function test_unauthorized_action_buttons_are_removed_from_dashboard(): void
    {
        // Buyer has no access to checks-balances or PR creation
        $this->loginAs(Roles::BUYER);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertDontSee(route('checks-balances'));
        $res->assertDontSee(route('requisition.create'));
        $res->assertSee(route('rfq.create'));
        $res->assertSee(route('onboarding'));

        // CFO has access to checks-balances but not PR creation
        $this->loginAs(Roles::CFO);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertSee(route('checks-balances'));
        $res->assertDontSee(route('requisition.create'));

        // PR Sr. Executive has access to PR creation but not checks-balances
        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertDontSee(route('checks-balances'));
        $res->assertSee(route('requisition.create'));
    }

    public function test_sidebar_only_contains_authorized_module_links_for_each_role(): void
    {
        // Buyer sees only: dashboard, onboarding, empanelment, rfq, ccs, po, notifications
        $this->loginAs(Roles::BUYER);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertSee('data-module="dashboard"', false);
        $res->assertSee('data-module="onboarding"', false);
        $res->assertSee('data-module="empanelment"', false);
        $res->assertSee('data-module="rfq"', false);
        $res->assertSee('data-module="ccs"', false);
        $res->assertSee('data-module="po"', false);
        $res->assertSee('data-module="notifications"', false);

        // Buyer must not see unauthorized items or sections that have 0 visible items
        $res->assertDontSee('data-module="requisition"', false);
        $res->assertDontSee('data-module="grn"', false);
        $res->assertDontSee('data-module="trf"', false);
        $res->assertDontSee('data-module="flight"', false);
        $res->assertDontSee('data-module="reimb"', false);
        $res->assertDontSee('data-module="traveleval"', false);
        $res->assertDontSee('data-module="checks-balances"', false);
        $res->assertDontSee('data-module="exceptions"', false);
        $res->assertDontSee('data-module="audit"', false);
        $res->assertDontSee('Travel &amp; Logistics');
        $res->assertDontSee('Governance &amp; Controls');

        // PR Sr Exec sees only: dashboard, requisition, grn, notifications
        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertSee('data-module="dashboard"', false);
        $res->assertSee('data-module="requisition"', false);
        $res->assertSee('data-module="grn"', false);
        $res->assertSee('data-module="notifications"', false);
        $res->assertDontSee('data-module="po"', false);
        $res->assertDontSee('data-module="rfq"', false);
        $res->assertDontSee('data-module="ccs"', false);
        $res->assertDontSee('data-module="onboarding"', false);
        $res->assertDontSee('data-module="trf"', false);

        // DSG (PR Approver L1) sees only: dashboard, requisition, notifications
        $this->loginAs(Roles::PR_APPROVER_L1);
        $res = $this->get('/index');
        $res->assertOk();
        $res->assertSee('data-module="dashboard"', false);
        $res->assertSee('data-module="requisition"', false);
        $res->assertSee('data-module="notifications"', false);
        $res->assertDontSee('data-module="grn"', false);
        $res->assertDontSee('data-module="po"', false);
        $res->assertDontSee('data-module="rfq"', false);
        $res->assertDontSee('data-module="trf"', false);
    }
}
