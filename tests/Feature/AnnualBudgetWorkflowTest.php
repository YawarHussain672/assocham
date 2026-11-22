<?php

namespace Tests\Feature;

use App\Models\AnnualBudget;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualBudgetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hod_can_view_and_submit_annual_budget_with_two_stage_approval(): void
    {
        // 1. Setup HOD user
        $hod = User::where('role', Roles::HOD)->first();
        if (! $hod) {
            $hod = User::create([
                'name' => 'Dr. Rajesh Sharma',
                'email' => 'rajesh.sharma@assocham.test',
                'password' => 'HodPass@2026',
                'role' => Roles::HOD,
                'designation' => 'Head of Department',
                'department' => 'Information Technology',
                'email_verified_at' => now(),
            ]);
        } else {
            $hod->update(['department' => 'Information Technology']);
        }

        // HOD visits annual budget index & create page
        $this->actingAs($hod)
            ->get(route('annual-budget'))
            ->assertOk()
            ->assertSee('Annual Budget Proposals')
            ->assertSee('Submit Annual Budget');

        $this->actingAs($hod)
            ->get(route('annual-budget.create'))
            ->assertOk()
            ->assertSee('Submit Annual Budget Proposal')
            ->assertSee('Information Technology');

        // 2. HOD submits an annual budget proposal
        $response = $this->actingAs($hod)
            ->post(route('annual-budget.store'), [
                'department' => 'Information Technology',
                'fiscal_year' => 'FY27-28',
                'proposed_amount' => 4500000.00,
                'justification' => 'Planned infrastructure upgrade, cybersecurity tooling, and cloud server scalability for FY27-28.',
                'items' => [
                    [
                        'category' => 'Software & Cloud',
                        'title' => 'Cloud Hosting & Storage',
                        'amount' => 2500000.00,
                        'notes' => 'AWS & Google Cloud multi-region cluster',
                    ],
                    [
                        'category' => 'COPEX',
                        'title' => 'Core Switch & Firewall Refresh',
                        'amount' => 2000000.00,
                        'notes' => 'Next-gen perimeter security',
                    ],
                ],
            ]);

        $budget = AnnualBudget::where('department', 'Information Technology')
            ->where('fiscal_year', 'FY27-28')
            ->first();

        $this->assertNotNull($budget);
        $response->assertRedirect(route('annual-budget.show', $budget));
        $this->assertSame(AnnualBudget::STATUS_PENDING_CFO, $budget->status);
        $this->assertSame(1, $budget->current_approval_level);
        $this->assertSame($hod->id, $budget->submitted_by);
        $this->assertEquals(4500000.00, (float) $budget->proposed_amount);

        // Verify two-stage polymorphic approvals exist: Level 1 (CFO) and Level 2 (SG)
        $approvals = $budget->approvals()->orderBy('level')->get();
        $this->assertCount(2, $approvals);
        $this->assertSame(Roles::CFO, $approvals[0]->role_required);
        $this->assertSame('pending', $approvals[0]->status);
        $this->assertSame(Roles::SG, $approvals[1]->role_required);
        $this->assertSame('pending', $approvals[1]->status);

        // 3. Level 1: CFO Review & Endorsement
        $cfo = User::where('role', Roles::CFO)->firstOrFail();

        // Level 2 (SG) cannot approve out-of-order before CFO endorses
        $sg = User::where('role', Roles::SG)->firstOrFail();
        $this->actingAs($sg)
            ->post(route('annual-budget.approve', $budget), [
                'comments' => 'SG trying to approve early',
            ])
            ->assertForbidden();

        // CFO endorses proposal
        $this->actingAs($cfo)
            ->get(route('annual-budget.show', $budget))
            ->assertOk()
            ->assertSee('Level 1: CFO Financial Endorsement')
            ->assertSee('Endorse & Advance to SG (Level 2)', false);

        $this->actingAs($cfo)
            ->post(route('annual-budget.approve', $budget), [
                'comments' => 'Reviewed against historical IT run-rates. Proposed allocation is financially sound.',
            ])
            ->assertRedirect(route('annual-budget.show', $budget))
            ->assertSessionHas('status');

        $budget->refresh();
        $this->assertSame(AnnualBudget::STATUS_PENDING_SG, $budget->status);
        $this->assertSame(2, $budget->current_approval_level);
        $this->assertNotNull($budget->cfo_approved_at);
        $this->assertStringContainsString('financially sound', $budget->cfo_remarks);

        // 4. Level 2: Secretary General (SG) Final Approval & Allocation Finalization
        $this->actingAs($sg)
            ->get(route('annual-budget.show', $budget))
            ->assertOk()
            ->assertSee('Level 2: Secretary General (SG) Final Sanction')
            ->assertSee('Finalize & Activate Budget Allocation', false);

        $this->actingAs($sg)
            ->post(route('annual-budget.approve', $budget), [
                'approved_amount' => 4500000.00,
                'comments' => 'Final institutional approval granted for FY27-28 IT budget.',
            ])
            ->assertRedirect(route('annual-budget.show', $budget))
            ->assertSessionHas('status');

        $budget->refresh();
        $this->assertSame(AnnualBudget::STATUS_APPROVED, $budget->status);
        $this->assertNotNull($budget->sg_approved_at);
        $this->assertNotNull($budget->finalized_at);
        $this->assertEquals(4500000.00, (float) $budget->approved_amount);

        // Verify active DepartmentBudget was created/updated and linked
        $deptBudget = DepartmentBudget::where('department', 'Information Technology')
            ->where('fiscal_year', 'FY27-28')
            ->first();

        $this->assertNotNull($deptBudget);
        $this->assertEquals(4500000.00, (float) $deptBudget->allocated_amount);
        $this->assertSame($deptBudget->id, $budget->department_budget_id);

        // Verify show page now reflects finalization
        $this->actingAs($hod)
            ->get(route('annual-budget.show', $budget))
            ->assertOk()
            ->assertSee('Approved & Finalized')
            ->assertSee('₹4,500,000.00');
    }

    public function test_budget_proposal_can_be_put_on_hold_and_rejected(): void
    {
        $hod = User::where('role', Roles::HOD)->firstOrFail();
        $cfo = User::where('role', Roles::CFO)->firstOrFail();

        $budget = AnnualBudget::create([
            'budget_number' => 'AB-TEST-HOLD-01',
            'department' => 'Marketing',
            'fiscal_year' => 'FY27-28',
            'submitted_by' => $hod->id,
            'proposed_amount' => 2000000.00,
            'justification' => 'Brand campaigns and digital outreach.',
            'status' => AnnualBudget::STATUS_PENDING_CFO,
            'current_approval_level' => 1,
        ]);

        $budget->approvals()->create([
            'level' => 1,
            'role_required' => Roles::CFO,
            'status' => 'pending',
        ]);
        $budget->approvals()->create([
            'level' => 2,
            'role_required' => Roles::SG,
            'status' => 'pending',
        ]);

        // CFO puts on hold
        $this->actingAs($cfo)
            ->post(route('annual-budget.hold', $budget), [
                'comments' => 'Please provide agency rate contracts before approval.',
            ])
            ->assertRedirect(route('annual-budget.show', $budget));

        $budget->refresh();
        $this->assertSame(AnnualBudget::STATUS_ON_HOLD, $budget->status);
        $this->assertSame('on_hold', $budget->approvals()->where('level', 1)->first()->status);

        // CFO rejects
        $this->actingAs($cfo)
            ->post(route('annual-budget.reject', $budget), [
                'comments' => 'Exceeds target growth envelope. Reduce proposed allocation.',
            ])
            ->assertRedirect(route('annual-budget.show', $budget));

        $budget->refresh();
        $this->assertSame(AnnualBudget::STATUS_REJECTED, $budget->status);

        // HOD can edit and resubmit rejected proposal
        $this->actingAs($hod)
            ->get(route('annual-budget.edit', $budget))
            ->assertOk()
            ->assertSee('Revise Annual Budget Proposal');

        $this->actingAs($hod)
            ->put(route('annual-budget.update', $budget), [
                'proposed_amount' => 1500000.00,
                'justification' => 'Revised digital campaign scope to fit target envelope.',
            ])
            ->assertRedirect(route('annual-budget.show', $budget));

        $budget->refresh();
        $this->assertSame(AnnualBudget::STATUS_PENDING_CFO, $budget->status);
        $this->assertEquals(1500000.00, (float) $budget->proposed_amount);
        $this->assertSame('pending', $budget->approvals()->where('level', 1)->first()->status);
    }

    public function test_unauthorized_roles_cannot_access_annual_budget_module(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $prSrExec = User::where('role', Roles::PR_SR_EXEC)->firstOrFail();

        // Admin Head must not see annual-budget in sidebar or access the module
        $this->assertNotContains('annual-budget', Roles::sidebarModulesFor(Roles::ADMIN_HEAD));
        $this->assertNotContains('annual-budget', Roles::modulesFor(Roles::ADMIN_HEAD));

        $this->actingAs($adminHead)
            ->get(route('annual-budget'))
            ->assertForbidden();

        $this->actingAs($adminHead)
            ->get(route('annual-budget.create'))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->get(route('annual-budget'))
            ->assertForbidden();

        $this->actingAs($prSrExec)
            ->get(route('annual-budget.create'))
            ->assertForbidden();
    }
}
