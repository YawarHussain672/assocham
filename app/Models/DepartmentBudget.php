<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    protected $fillable = [
        'department', 'fiscal_year', 'allocated_amount', 'utilised_amount', 'gst_rate',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'utilised_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
    ];

    public function utilisationPercent(): float
    {
        if ((float) $this->allocated_amount <= 0) {
            return 0.0;
        }

        return round(((float) $this->utilised_amount / (float) $this->allocated_amount) * 100, 1);
    }

    public function isNearLimit(): bool
    {
        return $this->utilisationPercent() >= 90.0;
    }

    /**
     * Indian fiscal year (Apr–Mar), e.g. "FY26-27". Was previously
     * duplicated inline in PurchaseRequisitionController; extracted here
     * once ChecksBalancesController needed the same calculation.
     */
    public static function currentFiscalYear(): string
    {
        $now = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;

        return 'FY'.substr((string) $startYear, 2, 2).'-'.substr((string) ($startYear + 1), 2, 2);
    }
}
