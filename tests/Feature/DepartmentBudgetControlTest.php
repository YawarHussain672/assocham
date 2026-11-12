<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProcurementChain;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class DepartmentBudgetControlTest extends TestCase
{
    use BuildsProcurementChain;
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_head_admin_can_create_and_update_department_budget_limits_with_audit_trail(): void
    {
        $adminHead = $this->loginAs(Roles::ADMIN_HEAD);
        $fy = DepartmentBudget::currentFiscalYear();

        $this->get(route('checks-balances'))
            ->assertOk()
            ->assertSee('Departmental Budget Master')
            ->assertSee('Add Department Budget')
            ->assertSee('Edit Limit');

        // Create a brand new department budget limit
        $this->post(route('checks-balances.budgets.store'), [
            'department' => 'Legal & Compliance',
            'fiscal_year' => $fy,
            'allocated_amount' => 750000,
            'gst_rate' => 18,
        ])->assertRedirect(route('checks-balances', ['fy' => $fy]));

        $budget = DepartmentBudget::where('department', 'Legal & Compliance')
            ->where('fiscal_year', $fy)
            ->firstOrFail();

        $this->assertEquals(750000.00, (float) $budget->allocated_amount);
        $this->assertEquals(0.00, (float) $budget->utilised_amount);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'DepartmentBudget',
            'action' => 'created',
            'reference' => "Legal & Compliance ({$fy})",
        ]);

        // Newly added department immediately appears on PR creation
        $this->get(route('requisition.create'))
            ->assertOk()
            ->assertSee('Legal & Compliance');

        // Update existing department budget limit
        $this->put(route('checks-balances.budgets.update', $budget), [
            'allocated_amount' => 1250000,
            'gst_rate' => 12,
        ])->assertRedirect(route('checks-balances', ['fy' => $fy]));

        $budget->refresh();
        $this->assertEquals(1250000.00, (float) $budget->allocated_amount);
        $this->assertEquals(12.00, (float) $budget->gst_rate);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'DepartmentBudget',
            'action' => 'updated',
            'reference' => "Legal & Compliance ({$fy})",
        ]);
    }

    public function test_budget_master_blocks_duplicates_and_limits_below_utilised_spend(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);
        $fy = DepartmentBudget::currentFiscalYear();

        $existing = DepartmentBudget::where('department', 'Marketing')
            ->where('fiscal_year', $fy)
            ->firstOrFail();

        // Duplicate (case-insensitive) department for same FY rejected
        $this->post(route('checks-balances.budgets.store'), [
            'department' => 'marketing',
            'fiscal_year' => $fy,
            'allocated_amount' => 2000000,
        ])->assertSessionHasErrors('department');

        // Cannot reduce limit below utilised_amount
        $tooLow = max(0, (float) $existing->utilised_amount - 1000);
        $this->put(route('checks-balances.budgets.update', $existing), [
            'allocated_amount' => $tooLow,
            'gst_rate' => 18,
        ])->assertSessionHasErrors('allocated_amount');

        $this->assertNotEquals($tooLow, (float) $existing->fresh()->allocated_amount);
    }

    public function test_unused_budget_can_be_removed_only_when_no_spend_or_prs_exist(): void
    {
        $adminHead = $this->loginAs(Roles::ADMIN_HEAD);
        $fy = DepartmentBudget::currentFiscalYear();

        $unused = DepartmentBudget::create([
            'department' => 'Temporary Events Cell',
            'fiscal_year' => $fy,
            'allocated_amount' => 300000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $this->delete(route('checks-balances.budgets.destroy', $unused))
            ->assertRedirect(route('checks-balances', ['fy' => $fy]));
        $this->assertDatabaseMissing('department_budgets', ['id' => $unused->id]);

        // Seeded Marketing has utilised spend — deletion blocked
        $used = DepartmentBudget::where('department', 'Marketing')->where('fiscal_year', $fy)->firstOrFail();
        $this->delete(route('checks-balances.budgets.destroy', $used))
            ->assertRedirect();
        $this->assertDatabaseHas('department_budgets', ['id' => $used->id]);

        // Department with linked PR cannot be deleted even if utilised_amount is 0
        $withPr = DepartmentBudget::create([
            'department' => 'Research Desk',
            'fiscal_year' => $fy,
            'allocated_amount' => 400000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);
        PurchaseRequisition::create([
            'pr_number' => 'PR-BUD-TEST-01',
            'department' => 'Research Desk',
            'status' => 'rejected',
            'current_level' => 1,
            'created_by' => $adminHead->id,
        ]);

        $this->delete(route('checks-balances.budgets.destroy', $withPr))
            ->assertRedirect();
        $this->assertDatabaseHas('department_budgets', ['id' => $withPr->id]);
    }

    public function test_only_head_admin_can_manage_budget_values_while_cfo_and_sg_remain_read_only(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $budget = DepartmentBudget::where('fiscal_year', $fy)->firstOrFail();

        foreach ([Roles::CFO, Roles::SG] as $role) {
            $this->loginAs($role);

            $this->get(route('checks-balances'))
                ->assertOk()
                ->assertSee('Read-Only View')
                ->assertDontSee('Add Department Budget')
                ->assertDontSee('Edit Limit');

            $this->post(route('checks-balances.budgets.store'), [
                'department' => 'Unauthorized Dept',
                'fiscal_year' => $fy,
                'allocated_amount' => 100000,
            ])->assertForbidden();

            $this->put(route('checks-balances.budgets.update', $budget), [
                'allocated_amount' => 9999999,
            ])->assertForbidden();

            $this->delete(route('checks-balances.budgets.destroy', $budget))
                ->assertForbidden();
        }

        foreach ([Roles::BUYER, Roles::PR_SR_EXEC] as $role) {
            $this->loginAs($role);

            $this->get(route('checks-balances'))->assertForbidden();
            $this->post(route('checks-balances.budgets.store'), [
                'department' => 'Blocked Dept',
                'fiscal_year' => $fy,
                'allocated_amount' => 100000,
            ])->assertForbidden();
        }
    }

    public function test_90_percent_utilisation_alert_fires_before_overrun_and_surfaces_across_governance_views(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();

        // Operations starts at 2,760,000 / 3,200,000 = 86.25% (below 90%)
        $operations = DepartmentBudget::where('department', 'Operations')
            ->where('fiscal_year', $fy)
            ->firstOrFail();

        $this->assertFalse($operations->isNearLimit());

        // Raise a within-budget PR costing 236,000 (200,000 + 18% GST) -> 2,996,000 / 3,200,000 = 93.6% (before overrun)
        $this->loginAs(Roles::PR_SR_EXEC);
        $response = $this->post(route('requisition.store'), [
            'department' => 'Operations',
            'items' => [
                ['item_name' => 'Server Rack Upgrade', 'quantity' => 1, 'unit_cost' => 200000, 'gst_percent' => 18],
            ],
        ]);

        $response->assertRedirect(route('requisition'))
            ->assertSessionHas('warning', fn (string $msg) => str_contains($msg, '90% Budget Utilisation Alert') && str_contains($msg, 'Operations'));

        $operations->refresh();
        $this->assertTrue($operations->isNearLimit());
        $this->assertSame(93.6, $operations->utilisationPercent());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'DepartmentBudget',
            'action' => 'threshold_alert',
            'reference' => "Operations ({$fy})",
        ]);

        // Governance roles see flagged department on Dashboard, Checks & Balances, and Notifications
        $this->loginAs(Roles::CFO);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('90% Budget Utilisation Alert')
            ->assertSee('Operations')
            ->assertSee('93.6% used');

        $this->get(route('checks-balances'))
            ->assertOk()
            ->assertSee('90% Budget Utilisation Alert')
            ->assertSee('Operations')
            ->assertSee('93.6%');

        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('90% Budget Utilisation Alerts')
            ->assertSee('Operations')
            ->assertSee('93.6% Utilised');

        // PR create page contains pre-overrun 90% alert check logic
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->get(route('requisition.create'))
            ->assertOk()
            ->assertSee('90% Budget Utilisation Alert');

        // Non-governance role does not see governance budget alerts on Notifications
        $this->loginAs(Roles::HOD);
        $this->get(route('notifications'))
            ->assertOk()
            ->assertDontSee('90% Budget Utilisation Alerts');
    }

    public function test_budget_limit_revision_into_90_percent_zone_triggers_threshold_alert(): void
    {
        $adminHead = $this->loginAs(Roles::ADMIN_HEAD);
        $fy = DepartmentBudget::currentFiscalYear();

        // IT starts at 1,430,000 / 2,200,000 = 65%
        $itBudget = DepartmentBudget::where('department', 'IT')
            ->where('fiscal_year', $fy)
            ->firstOrFail();

        // Tighten ceiling to 1,550,000 -> 1,430,000 / 1,550,000 = 92.3%
        $this->put(route('checks-balances.budgets.update', $itBudget), [
            'allocated_amount' => 1550000,
            'gst_rate' => 18,
        ])->assertRedirect(route('checks-balances', ['fy' => $fy]))
          ->assertSessionHas('warning', fn (string $msg) => str_contains($msg, '90% Budget Utilisation Alert') && str_contains($msg, 'IT'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminHead->id,
            'module' => 'DepartmentBudget',
            'action' => 'threshold_alert',
            'reference' => "IT ({$fy})",
        ]);
    }

    public function test_switching_department_on_pr_edit_transfers_budget_reservation_atomically(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $it = DepartmentBudget::where('department', 'IT')->where('fiscal_year', $fy)->firstOrFail();
        $finance = DepartmentBudget::where('department', 'Finance')->where('fiscal_year', $fy)->firstOrFail();

        $initialItUtilised = (float) $it->utilised_amount;
        $initialFinanceUtilised = (float) $finance->utilised_amount;

        // Create PR in IT for 118,000 (100,000 + 18% GST)
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'IT',
            'items' => [['item_name' => 'Switches', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18]],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::latest('id')->firstOrFail();
        $this->assertEqualsWithDelta($initialItUtilised + 118000, (float) $it->fresh()->utilised_amount, 0.01);

        // Moving PR to a department whose remaining headroom cannot cover it (Marketing has 120k remaining; try 150k)
        $this->put(route('requisition.update', $pr), [
            'department' => 'Marketing',
            'items' => [['item_name' => 'Campaign', 'quantity' => 1, 'unit_cost' => 130000, 'gst_percent' => 18]],
        ])->assertSessionHasErrors('budget');
        $this->assertSame('IT', $pr->fresh()->department);

        // Move PR cleanly from IT -> Finance for 59,000 (50,000 + 18% GST)
        $this->put(route('requisition.update', $pr), [
            'department' => 'Finance',
            'items' => [['item_name' => 'Audit Software', 'quantity' => 1, 'unit_cost' => 50000, 'gst_percent' => 18]],
        ])->assertRedirect(route('requisition.show', $pr));

        $this->assertEqualsWithDelta($initialItUtilised, (float) $it->fresh()->utilised_amount, 0.01);
        $this->assertEqualsWithDelta($initialFinanceUtilised + 59000, (float) $finance->fresh()->utilised_amount, 0.01);
    }

    public function test_po_issuance_checks_budget_delta_triggers_90_percent_alert_and_cancel_releases_budget(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $vendor = $this->empanelledVendor();

        // Marketing has 1,380,000 / 1,500,000 -> 120,000 headroom remaining
        $marketing = DepartmentBudget::where('department', 'Marketing')->where('fiscal_year', $fy)->firstOrFail();
        $marketing->update(['allocated_amount' => 1600000]); // headroom now 220,000 (86.3%)

        // Approve PR for 59,000 (50,000 + 18% GST) -> Marketing utilised = 1,439,000 (remaining = 161,000)
        $pr = $this->approvedPr('Marketing', 50000, 1);

        // Over-budget PO amount (300,000 -> delta +241,000 > 161,000 headroom) must be blocked
        $this->loginAs(Roles::BUYER);
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 300000,
        ])->assertSessionHasErrors('budget');
        $this->assertSame(0, PurchaseOrder::where('purchase_requisition_id', $pr->id)->count());

        // Higher within-headroom PO amount (150,000 -> delta +91,000 -> utilised 1,530,000 = 95.6%) succeeds and fires 90% alert
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 150000,
        ])->assertRedirect(route('po'))
          ->assertSessionHas('warning', fn (string $msg) => str_contains($msg, '90% Budget Utilisation Alert') && str_contains($msg, 'Marketing'));

        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
        $this->assertEqualsWithDelta(1530000.0, (float) $marketing->fresh()->utilised_amount, 0.01);

        // Cancelling the PO releases the committed PO funds (150,000) back to Marketing
        $this->post(route('po.cancel', $po))->assertRedirect(route('po'));
        $this->assertSame('cancelled', $po->fresh()->status);
        $this->assertEqualsWithDelta(1380000.0, (float) $marketing->fresh()->utilised_amount, 0.01);
    }
}

