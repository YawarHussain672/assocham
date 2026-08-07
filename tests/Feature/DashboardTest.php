<?php

namespace Tests\Feature;

use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies the Dashboard's cross-module "Items Awaiting My Approval" feed
 * (see project memory: erp-laravel-phase5-polish) — the first place in the
 * app reading across every module's `approvals` table at once. The key
 * behavior under test: only the *lowest pending level* per record is
 * "actionable", even though every level's row already exists as `pending`
 * from the moment the chain was seeded.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function prPendingAtLevel2(): PurchaseRequisition
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [['item_name' => 'Item', 'quantity' => 1, 'unit_cost' => 1000, 'gst_percent' => 18]],
        ]);
        $pr = PurchaseRequisition::latest('id')->firstOrFail();

        $this->loginAs(Roles::PR_APPROVER_L1); // Basu clears level 1
        $this->post(route('requisition.approve', $pr));

        return $pr->fresh(); // now sitting at level 2 (Ambika); levels 3-4 exist as pending too
    }

    public function test_the_role_holding_the_lowest_pending_level_sees_it(): void
    {
        $pr = $this->prPendingAtLevel2();

        $this->loginAs(Roles::PR_APPROVER_L2); // Ambika
        $this->get(route('dashboard'))->assertSee($pr->pr_number);
    }

    public function test_a_role_whose_level_already_passed_does_not_see_it(): void
    {
        $pr = $this->prPendingAtLevel2();

        // assertDontSee($pr->pr_number) would be too broad — the PR number
        // legitimately also appears in the "Recent Activity" feed (audit
        // visibility is open to everyone), so it checks the pending-approval
        // panel's actual link URL specifically instead.
        $this->loginAs(Roles::PR_APPROVER_L1); // Basu — already acted
        $this->get(route('dashboard'))->assertDontSee(route('requisition.show', $pr));
    }

    public function test_a_role_whose_level_has_not_been_reached_yet_does_not_see_it(): void
    {
        $pr = $this->prPendingAtLevel2();

        $this->loginAs(Roles::CFO); // level 3 — not reached until level 2 clears
        $this->get(route('dashboard'))->assertDontSee(route('requisition.show', $pr));
    }

    public function test_both_interchangeable_roles_see_the_same_pending_trf(): void
    {
        $this->loginAs(Roles::HOD);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Mumbai',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ]);
        $trf = \App\Models\Trf::latest('id')->firstOrFail();

        $this->loginAs(Roles::ASG1);
        $this->get(route('dashboard'))->assertSee($trf->trf_number);

        $this->loginAs(Roles::ASG2);
        $this->get(route('dashboard'))->assertSee($trf->trf_number);
    }

    public function test_budget_panel_is_gated_the_same_as_checks_balances(): void
    {
        $this->loginAs(Roles::CFO);
        $this->get(route('dashboard'))->assertSee('Department Budgets');

        $this->loginAs(Roles::BUYER);
        $this->get(route('dashboard'))->assertDontSee('Department Budgets');
    }
}
