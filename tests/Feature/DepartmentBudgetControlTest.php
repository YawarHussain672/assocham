<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\ProjectBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
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
            ->assertDontSee('Add Department Budget')
            ->assertSee('Edit Limit');

        // Department budgets cannot be created manually in Budget Master (only through approved Annual Budget)
        $this->post(route('checks-balances.budgets.store'), [
            'department' => 'Legal & Compliance',
            'fiscal_year' => $fy,
            'allocated_amount' => 750000,
            'gst_rate' => 18,
        ])->assertForbidden();

        // Create approved budget directly to test limit revision
        $budget = DepartmentBudget::create([
            'department' => 'Legal & Compliance',
            'fiscal_year' => $fy,
            'allocated_amount' => 750000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $this->assertEquals(750000.00, (float) $budget->allocated_amount);
        $this->assertEquals(0.00, (float) $budget->utilised_amount);

        // Newly added department immediately appears on PR creation
        $this->get(route('requisition.create'))
            ->assertOk()
            ->assertSee('Legal & Compliance');

        // Editing limit is strictly blocked while budget is within comfortable limits (<90% utilized)
        $this->put(route('checks-balances.budgets.update', $budget), [
            'allocated_amount' => 1250000,
            'gst_rate' => 12,
        ])->assertSessionHas('error');

        // Once limit is reached (>=90% utilized or headroom exhausted), Admin Head can revise the limit
        $budget->update(['utilised_amount' => 700000]); // 93.3% utilized -> limit reached
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

        // Department budgets cannot be created manually in Budget Master
        $this->post(route('checks-balances.budgets.store'), [
            'department' => 'marketing',
            'fiscal_year' => $fy,
            'allocated_amount' => 2000000,
        ])->assertForbidden();

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
            ->assertSee('Operations')
            ->assertSee('93.6%');

        // Budget alerts removed from notifications page as requested
        $this->get(route('notifications'))
            ->assertOk()
            ->assertDontSee('90% Budget Utilisation Alerts');

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

        // IT starts at 1,430,000 / 2,200,000 = 65% (within limit, so editing is initially blocked)
        $itBudget = DepartmentBudget::where('department', 'IT')
            ->where('fiscal_year', $fy)
            ->firstOrFail();

        $this->put(route('checks-balances.budgets.update', $itBudget), [
            'allocated_amount' => 1550000,
            'gst_rate' => 18,
        ])->assertSessionHas('error');

        // Once IT budget limit is reached (>=90%), revision is permitted and threshold alert is logged
        $itBudget->update(['utilised_amount' => 2000000]); // 2,000,000 / 2,200,000 = 90.9%
        $this->put(route('checks-balances.budgets.update', $itBudget), [
            'allocated_amount' => 2100000,
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

    public function test_head_admin_can_manage_project_wise_budgets(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);
        $fy = DepartmentBudget::currentFiscalYear();

        // Page surfaces both Department-wise and Project-wise budget tables
        $res = $this->get(route('checks-balances'));
        $res->assertOk()
            ->assertSee('Department-wise Budget')
            ->assertSee('Project-wise Budget')
            ->assertSee('Add Project Budget');

        // Admin Head creates a new Project Budget
        $this->post(route('checks-balances.project-budgets.store'), [
            'project_name' => 'National AI & Robotics Expo',
            'department' => 'Projects',
            'fiscal_year' => $fy,
            'allocated_amount' => 4500000,
        ])->assertRedirect(route('checks-balances', ['fy' => $fy, 'view' => 'project']));

        $project = \App\Models\ProjectBudget::where('project_name', 'National AI & Robotics Expo')->where('fiscal_year', $fy)->firstOrFail();
        $this->assertSame('Projects', $project->department);
        $this->assertEqualsWithDelta(4500000.0, (float) $project->allocated_amount, 0.01);

        // Editing Project Budget limit is blocked while spend is within limit
        $this->put(route('checks-balances.project-budgets.update', $project), [
            'allocated_amount' => 5000000,
        ])->assertSessionHas('error');

        // Once project budget limit is reached (>=90%), revision is permitted
        $project->update(['utilised_amount' => 4100000]); // 4,100,000 / 4,500,000 = 91.1%
        $this->put(route('checks-balances.project-budgets.update', $project), [
            'allocated_amount' => 5000000,
        ])->assertRedirect(route('checks-balances', ['fy' => $fy, 'view' => 'project']));
        $this->assertEqualsWithDelta(5000000.0, (float) $project->fresh()->allocated_amount, 0.01);

        $project->update(['utilised_amount' => 0]);

        // Delete unused Project Budget
        $this->delete(route('checks-balances.project-budgets.destroy', $project))
            ->assertRedirect(route('checks-balances', ['fy' => $fy, 'view' => 'project']));
        $this->assertNull(\App\Models\ProjectBudget::find($project->id));

        // Non-admin head (e.g. CFO) cannot mutate project budgets
        $this->loginAs(Roles::CFO);
        $this->post(route('checks-balances.project-budgets.store'), [
            'project_name' => 'Unauthorized Summit',
            'department' => 'Projects',
            'fiscal_year' => $fy,
            'allocated_amount' => 100000,
        ])->assertForbidden();
    }

    public function test_pr_linked_to_project_budget_deducts_and_releases_spend_automatically(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $this->loginAs(Roles::PR_SR_EXEC);

        // Pick a project with known headroom
        $project = \App\Models\ProjectBudget::where('project_name', 'Annual Strategy Conclave')->firstOrFail();
        $initialProjectUtilised = (float) $project->utilised_amount;
        $initialDept = DepartmentBudget::where('department', 'Projects')->where('fiscal_year', $fy)->firstOrFail();
        $initialDeptUtilised = (float) $initialDept->utilised_amount;

        // 1. Submit PR linked to this project (Cost: 100,000 + 18% GST = 118,000)
        $res = $this->post(route('requisition.store'), [
            'title' => 'Conclave Stage & Sound Setup',
            'department' => 'Projects',
            'project_budget_id' => $project->id,
            'items' => [
                ['item_name' => 'Audio System', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18],
            ],
        ]);

        $res->assertRedirect(route('requisition'));
        $pr = PurchaseRequisition::where('title', 'Conclave Stage & Sound Setup')->firstOrFail();
        $this->assertSame($project->id, $pr->project_budget_id);
        $this->assertSame('Annual Strategy Conclave', $pr->event_name);

        // Verify project headroom was deducted
        $project->refresh();
        $this->assertEqualsWithDelta($initialProjectUtilised + 118000, (float) $project->utilised_amount, 0.01);

        // Verify department headroom was deducted
        $initialDept->refresh();
        $this->assertEqualsWithDelta($initialDeptUtilised + 118000, (float) $initialDept->utilised_amount, 0.01);

        // 2. Reject PR and verify both department and project budgets are released
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.reject', $pr), [
            'comments' => 'Budget reallocated',
        ])->assertRedirect();

        $project->refresh();
        $this->assertEqualsWithDelta($initialProjectUtilised, (float) $project->utilised_amount, 0.01);
        $initialDept->refresh();
        $this->assertEqualsWithDelta($initialDeptUtilised, (float) $initialDept->utilised_amount, 0.01);
    }

    public function test_pr_exceeding_project_headroom_is_blocked(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        $project = \App\Models\ProjectBudget::where('project_name', 'Conference on Building a Resilient Critical Minerals Supply Chain in India')->firstOrFail();
        $availableHeadroom = $project->remainingAmount(); // e.g. 1,080,000

        // Attempt PR that exceeds project headroom (e.g. 2,000,000 + GST = 2,360,000)
        $res = $this->from(route('requisition.create'))->post(route('requisition.store'), [
            'title' => 'Massive Overbudget Project Spend',
            'department' => 'Projects',
            'project_budget_id' => $project->id,
            'items' => [
                ['item_name' => 'Overbudget Mineral Tech', 'quantity' => 1, 'unit_cost' => 2000000, 'gst_percent' => 18],
            ],
        ]);

        $res->assertRedirect(route('requisition.create'))
            ->assertSessionHasErrors(['budget']);

        $this->assertDatabaseMissing('purchase_requisitions', [
            'title' => 'Massive Overbudget Project Spend',
        ]);
    }

    public function test_project_budget_syncs_across_rfq_po_delta_cancel_and_views(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();

        $dept = DepartmentBudget::where('department', 'Projects')->where('fiscal_year', $fy)->firstOrFail();
        $dept->update(['allocated_amount' => 10000000, 'utilised_amount' => 1000000]);

        $project = \App\Models\ProjectBudget::create([
            'project_name' => 'National Sustainability Forum 2026',
            'department' => 'Projects',
            'council' => 'Sustainability',
            'fiscal_year' => $fy,
            'allocated_amount' => 300000,
            'utilised_amount' => 0,
        ]);

        // 1. PR Sr Exec raises PR linked to project (100,000 + 18% GST = 118,000)
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'title' => 'Sustainability Stage Fabrication',
            'department' => 'Projects',
            'project_budget_id' => $project->id,
            'items' => [
                ['item_name' => 'Stage & Backdrop', 'quantity' => 1, 'unit_cost' => 100000, 'gst_percent' => 18],
            ],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Sustainability Stage Fabrication')->firstOrFail();
        $this->assertSame($project->id, $pr->project_budget_id);
        $this->assertEqualsWithDelta(118000, (float) $project->fresh()->utilised_amount, 0.01);

        // PR index shows project badge
        $this->get(route('requisition'))
            ->assertOk()
            ->assertSee('National Sustainability Forum 2026');

        // 2. Walk full approval chain
        while ($approval = $pr->fresh()->approvals()->where('status', 'pending')->orderBy('level')->first()) {
            $this->loginAs($approval->role_required);
            $this->post(route('requisition.approve', $pr))->assertRedirect();
        }
        $pr->refresh();
        $this->assertSame('approved', $pr->status);

        // Buyer creates RFQ for this PR inheriting project name from ProjectBudget
        $rfq = $pr->autoCreateRfq();
        $this->assertNotNull($rfq);
        $this->assertSame('National Sustainability Forum 2026', $rfq->project_name);

        // 3. Buyer creates PO — over-project-headroom negotiated PO (350,000 > 300,000) is BLOCKED
        $buyer = $this->loginAs(Roles::BUYER);
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-PROJ-01',
            'company_name' => 'EcoStage Solutions',
            'category' => 'Events',
            'contact_person' => 'Riya',
            'email' => 'riya@ecostage.test',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $buyer->id,
        ]);

        $this->from(route('po.create'))->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 350000,
        ])->assertRedirect(route('po.create'))
            ->assertSessionHasErrors(['budget']);

        $this->assertDatabaseCount('purchase_orders', 0);

        // 4. Buyer creates PO within project headroom with +50,000 delta (168,000 total)
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'amount_incl_gst' => 168000,
        ])->assertRedirect(route('po'));

        $po = PurchaseOrder::latest('id')->firstOrFail();
        $this->assertSame('National Sustainability Forum 2026', $po->event_name);
        $this->assertEqualsWithDelta(168000, (float) $project->fresh()->utilised_amount, 0.01);
        $this->assertEqualsWithDelta(1168000, (float) $dept->fresh()->utilised_amount, 0.01);

        // Buyer sees project name on RFQ show, PO index, and PO show (without clickable budget master link)
        $this->get(route('rfq.show', $rfq))
            ->assertOk()
            ->assertSee('National Sustainability Forum 2026')
            ->assertDontSee(route('checks-balances', ['view' => 'project']), false);

        $this->get(route('po'))
            ->assertOk()
            ->assertSee('National Sustainability Forum 2026');

        $this->get(route('po.show', $po))
            ->assertOk()
            ->assertSee('National Sustainability Forum 2026')
            ->assertDontSee(route('checks-balances', ['view' => 'project']), false);

        // 5. Cancel PO -> BOTH DepartmentBudget and ProjectBudget are refunded
        $this->post(route('po.cancel', $po))->assertRedirect(route('po'));
        $this->assertEqualsWithDelta(0, (float) $project->fresh()->utilised_amount, 0.01);
        $this->assertEqualsWithDelta(1000000, (float) $dept->fresh()->utilised_amount, 0.01);
    }

    public function test_project_90_percent_alert_surfaces_only_to_governance_roles_respecting_privacy(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();

        $project = \App\Models\ProjectBudget::create([
            'project_name' => 'Critical 90pct Governance Summit',
            'department' => 'Projects',
            'fiscal_year' => $fy,
            'allocated_amount' => 100000,
            'utilised_amount' => 92000,
        ]);

        // Governance roles (Admin Head, CFO, SG) see the 90% project alert on Dashboard & Budget Master
        foreach ([Roles::ADMIN_HEAD, Roles::CFO, Roles::SG] as $govRole) {
            $this->loginAs($govRole);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee('90% Project Budget Alert')
                ->assertSee('Critical 90pct Governance Summit')
                ->assertSee('Budget Utilised (GST-Incl)');

            $this->get(route('checks-balances'))
                ->assertOk()
                ->assertSee('90% Project Budget Alert')
                ->assertSee('Critical 90pct Governance Summit');
        }

        // Non-governance roles MUST NOT see org budget utilisation or project alert banners
        foreach ([Roles::BUYER, Roles::PR_SR_EXEC, Roles::HOD] as $nonGovRole) {
            $this->loginAs($nonGovRole);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('90% Project Budget Alert')
                ->assertDontSee('Critical 90pct Governance Summit')
                ->assertDontSee('Budget Utilised (GST-Incl)');
        }
    }

    public function test_pr_excel_import_links_project_budget_and_enforces_project_headroom(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $this->loginAs(Roles::PR_SR_EXEC);

        $project = \App\Models\ProjectBudget::create([
            'project_name' => 'Excel Synced Summit 2026',
            'department' => 'Projects',
            'fiscal_year' => $fy,
            'allocated_amount' => 200000,
            'utilised_amount' => 0,
        ]);

        $csv = implode("\n", [
            'PR NO.,PR TITLE,DEPARTMENT,ITEM NAME(S),QTY,UNIT,UNIT COST,GST%,SPECS,REQUIRED BY,PURPOSE,SOLE SOURCE,STATUS,EVENT / PROJECT',
            'PR-XLS-PROJ-1,Summit Print Kits,Projects,Delegate Kits,100,Sets,1000,18,Recycled paper,2026-12-10,Summit materials,No,Pending,Excel Synced Summit 2026',
            'PR-XLS-PROJ-2,Overbudget LED Wall,Projects,Giant LED Wall,1,Unit,200000,18,4K LED,2026-12-10,Too expensive,No,Pending,Excel Synced Summit 2026',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr-project-import.csv', $csv);

        $this->post(route('requisition.import-excel'), ['file' => $file])
            ->assertRedirect(route('requisition'));

        $imported = PurchaseRequisition::where('pr_number', 'PR-XLS-PROJ-1')->firstOrFail();
        $this->assertSame($project->id, $imported->project_budget_id);
        $this->assertSame('Excel Synced Summit 2026', $imported->event_name);
        $this->assertEqualsWithDelta(118000, (float) $project->fresh()->utilised_amount, 0.01);

        // Second row exceeded remaining project headroom (82,000) and was rejected
        $this->assertDatabaseMissing('purchase_requisitions', ['pr_number' => 'PR-XLS-PROJ-2']);
    }

    public function test_new_department_added_in_budget_master_appears_dynamically_in_pr_forms_and_filters(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $this->loginAs(Roles::ADMIN_HEAD);

        DepartmentBudget::create([
            'department' => 'Legal & Policy',
            'fiscal_year' => $fy,
            'allocated_amount' => 950000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $this->get(route('requisition.create'))
            ->assertOk()
            ->assertSee('Legal &amp; Policy', false);

        $this->get(route('requisition'))
            ->assertOk()
            ->assertSee('Legal &amp; Policy', false)
            ->assertSee('Legal &amp; Policy Procurement', false);
    }

    public function test_pr_blocks_cross_department_project_budget_mismatch_on_create_and_update(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $this->loginAs(Roles::PR_SR_EXEC);

        $itProject = ProjectBudget::create([
            'project_name' => 'IT Cyber Defense Conclave',
            'department' => 'IT',
            'fiscal_year' => $fy,
            'allocated_amount' => 500000,
            'utilised_amount' => 0,
        ]);

        // Attempt to link an IT Project Budget to a Marketing PR -> strictly blocked
        $this->from(route('requisition.create'))
            ->post(route('requisition.store'), [
                'title' => 'Mismatched PR',
                'department' => 'Marketing',
                'project_budget_id' => $itProject->id,
                'items' => [
                    ['item_name' => 'Banner', 'quantity' => 1, 'unit' => 'Unit', 'unit_cost' => 10000, 'gst_percent' => 18],
                ],
            ])
            ->assertRedirect(route('requisition.create'))
            ->assertSessionHasErrors('budget');

        $this->assertDatabaseMissing('purchase_requisitions', ['title' => 'Mismatched PR']);
        $this->assertEquals(0.0, (float) $itProject->fresh()->utilised_amount);

        // Creating under matching IT department succeeds cleanly
        $this->post(route('requisition.store'), [
            'title' => 'Matched IT PR',
            'department' => 'IT',
            'project_budget_id' => $itProject->id,
            'items' => [
                ['item_name' => 'Firewall', 'quantity' => 1, 'unit' => 'Unit', 'unit_cost' => 10000, 'gst_percent' => 18],
            ],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Matched IT PR')->firstOrFail();

        // Attempting to switch PR department to Marketing while keeping IT project -> strictly blocked
        $this->from(route('requisition.edit', $pr))
            ->put(route('requisition.update', $pr), [
                'title' => 'Matched IT PR',
                'department' => 'Marketing',
                'project_budget_id' => $itProject->id,
                'items' => [
                    ['item_name' => 'Firewall', 'quantity' => 1, 'unit' => 'Unit', 'unit_cost' => 10000, 'gst_percent' => 18],
                ],
            ])
            ->assertRedirect(route('requisition.edit', $pr))
            ->assertSessionHasErrors('budget');

        $this->assertSame('IT', $pr->fresh()->department);
    }
}

