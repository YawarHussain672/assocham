<?php

namespace Database\Seeders;

use App\Models\DepartmentBudget;
use Illuminate\Database\Seeder;

/**
 * Matches the six departments shown in the deck's dashboard screenshots
 * (page 31/43), same fiscal year and allocations, so the Checks & Balances
 * module (Phase 4) has real data to run its 90% GST-inclusive alert against.
 */
class DepartmentBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $fiscalYear = 'FY26-27';

        $budgets = [
            ['department' => 'Projects', 'allocated_amount' => 8_500_000, 'utilised_amount' => 6_120_000],
            ['department' => 'IT', 'allocated_amount' => 2_200_000, 'utilised_amount' => 1_430_000],
            ['department' => 'Marketing', 'allocated_amount' => 1_500_000, 'utilised_amount' => 1_380_000],
            ['department' => 'Finance', 'allocated_amount' => 900_000, 'utilised_amount' => 310_000],
            ['department' => 'Operations', 'allocated_amount' => 3_200_000, 'utilised_amount' => 2_760_000],
            ['department' => 'HR', 'allocated_amount' => 1_200_000, 'utilised_amount' => 450_000],
        ];

        foreach ($budgets as $budget) {
            DepartmentBudget::updateOrCreate(
                ['department' => $budget['department'], 'fiscal_year' => $fiscalYear],
                ['allocated_amount' => $budget['allocated_amount'], 'utilised_amount' => $budget['utilised_amount'], 'gst_rate' => 18.00]
            );
        }
    }
}
