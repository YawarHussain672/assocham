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
}
