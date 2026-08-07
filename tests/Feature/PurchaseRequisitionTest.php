<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies Purchase Requisition's budget reserve/release + L1-L4 chain
 * (see project memory: erp-laravel-phase2-procurement). Originally
 * verified by hand via curl: within-budget PR reserved the right amount,
 * over-budget PR was blocked pre-persistence, full chain walked in order,
 * rejection released the reserved budget.
 */
class PurchaseRequisitionTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function submitPr(float $unitCost = 10000, int $quantity = 1): \Illuminate\Testing\TestResponse
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        return $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [
                ['item_name' => 'Laptops', 'quantity' => $quantity, 'unit_cost' => $unitCost, 'gst_percent' => 18],
            ],
        ]);
    }

    public function test_within_budget_pr_reserves_the_gst_inclusive_amount(): void
    {
        $budgetBefore = DepartmentBudget::where('department', 'Finance')
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->first();
        $utilisedBefore = (float) $budgetBefore->utilised_amount;

        $this->submitPr(unitCost: 10000)->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('department', 'Finance')->firstOrFail();
        $this->assertEqualsWithDelta(11800.0, $pr->estimatedCostInclGst(), 0.01); // 10000 * 1.18

        $budgetAfter = $budgetBefore->fresh();
        $this->assertEqualsWithDelta($utilisedBefore + 11800, (float) $budgetAfter->utilised_amount, 0.01);
    }

    public function test_over_budget_pr_is_blocked_and_nothing_is_persisted(): void
    {
        // Finance's seeded allocation (900,000) is far below this.
        $this->submitPr(unitCost: 10_000_000)->assertSessionHasErrors('budget');

        $this->assertSame(0, PurchaseRequisition::count());
    }

    public function test_full_l1_l4_chain_approves_in_order(): void
    {
        $this->submitPr();
        $pr = PurchaseRequisition::firstOrFail();

        $this->loginAs(Roles::PR_APPROVER_L1); // Basu
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::PR_APPROVER_L2); // Ambika
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::SG);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->assertSame('approved', $pr->fresh()->status);
    }

    public function test_rejection_releases_the_reserved_budget(): void
    {
        $this->submitPr(unitCost: 5000);
        $pr = PurchaseRequisition::firstOrFail();

        $budget = DepartmentBudget::where('department', 'Finance')
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())->firstOrFail();
        $utilisedAfterSubmit = (float) $budget->fresh()->utilised_amount;

        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.reject', $pr), ['comments' => 'Not needed this quarter'])
            ->assertRedirect();

        $this->assertSame('rejected', $pr->fresh()->status);
        $released = $utilisedAfterSubmit - (float) $budget->fresh()->utilised_amount;
        $this->assertEqualsWithDelta(5900.0, $released, 0.01); // 5000 * 1.18
    }
}
