<?php

namespace Tests\Feature;

use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies the Notifications page and the sidebar/topbar badge counts
 * (see project memory: erp-laravel-phase5-polish) — both driven by
 * App\Support\PendingItems, the same aggregation the Dashboard uses.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_role_with_nothing_pending_sees_the_empty_state_and_a_zero_badge(): void
    {
        $this->loginAs(Roles::HOD);

        $this->get(route('notifications'))->assertSee('Nothing waiting on you right now');
        $this->get(route('dashboard'))->assertSee('"pendingCount":0', false);
    }

    public function test_a_role_with_a_pending_item_sees_it_listed_and_counted(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [['item_name' => 'Item', 'quantity' => 1, 'unit_cost' => 1000, 'gst_percent' => 18]],
        ]);
        $pr = \App\Models\PurchaseRequisition::latest('id')->firstOrFail();

        $this->loginAs(Roles::PR_APPROVER_L1); // Basu — holds level 1 right now

        $this->get(route('notifications'))->assertSee($pr->pr_number);
        $this->get(route('dashboard'))->assertSee('"pendingCount":1', false);
    }

    public function test_unreviewed_notifications_never_expire_while_reviewed_notifications_clear_after_48_hours(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [['item_name' => 'Old Unreviewed Item', 'quantity' => 1, 'unit_cost' => 1000, 'gst_percent' => 18]],
        ]);
        $prOld = \App\Models\PurchaseRequisition::latest('id')->firstOrFail();

        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [['item_name' => 'Reviewed Item', 'quantity' => 1, 'unit_cost' => 1000, 'gst_percent' => 18]],
        ]);
        $prReviewed = \App\Models\PurchaseRequisition::latest('id')->firstOrFail();

        // Age the unreviewed approval beyond 48 hours (72h old) — it MUST NOT auto-clear
        $prOld->approvals()->where('level', 1)->update(['created_at' => now()->subHours(72)]);

        $this->loginAs(Roles::PR_APPROVER_L1);

        // Approve the second PR — moves it into Recently Reviewed (retained for 48h)
        $this->post(route('requisition.approve', $prReviewed))->assertRedirect();

        // Within 48 hours of review:
        // - Unreviewed 72h-old PR is still pending (pendingCount = 1)
        // - Reviewed PR appears in Approved section
        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee($prOld->pr_number)
            ->assertSee('Approved')
            ->assertSee($prReviewed->pr_number);

        $this->get(route('dashboard'))->assertSee('"pendingCount":1', false);

        // Once 49 hours pass after review:
        // - Reviewed PR auto-clears from Notifications
        // - Unreviewed 72h-old PR STILL remains pending!
        $prReviewed->approvals()->where('level', 1)->update(['acted_at' => now()->subHours(49)]);

        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee($prOld->pr_number)
            ->assertDontSee($prReviewed->pr_number);
    }
}
