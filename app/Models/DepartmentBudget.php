<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    use LogsAuditTrail;

    public const ALERT_THRESHOLD_PERCENT = 90.0;

    protected $fillable = [
        'department', 'fiscal_year', 'allocated_amount', 'utilised_amount', 'gst_rate',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'utilised_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
    ];

    /**
     * Lock (or initialize with ₹0 limit) the budget row inside an active DB
     * transaction so concurrent approvals/submissions cannot race past limits.
     */
    public static function lockOrCreate(string $department, ?string $fiscalYear = null): self
    {
        $fy = $fiscalYear ?: self::currentFiscalYear();

        $budget = self::firstOrCreate(
            ['department' => $department, 'fiscal_year' => $fy],
            ['allocated_amount' => 0, 'utilised_amount' => 0, 'gst_rate' => 18.00]
        );

        return self::whereKey($budget->id)->lockForUpdate()->firstOrFail();
    }

    public function utilisationPercent(): float
    {
        if ((float) $this->allocated_amount <= 0) {
            return 0.0;
        }

        return round(((float) $this->utilised_amount / (float) $this->allocated_amount) * 100, 1);
    }

    public function rawRemainingAmount(): float
    {
        return round((float) $this->allocated_amount - (float) $this->utilised_amount, 2);
    }

    public function remainingAmount(): float
    {
        return max(0.0, $this->rawRemainingAmount());
    }

    public function hasHeadroomFor(float $additionalAmount): bool
    {
        if ($additionalAmount <= 0) {
            return true;
        }

        return $additionalAmount <= ($this->rawRemainingAmount() + 0.0001);
    }

    public function isNearLimit(): bool
    {
        return $this->utilisationPercent() >= self::ALERT_THRESHOLD_PERCENT;
    }

    public function crossedAlertThreshold(float $previousUtilised): bool
    {
        $allocated = (float) $this->allocated_amount;
        if ($allocated <= 0) {
            return false;
        }

        $previousPct = round(($previousUtilised / $allocated) * 100, 1);

        return $previousPct < self::ALERT_THRESHOLD_PERCENT && $this->isNearLimit();
    }

    public function alertMessage(): string
    {
        $pct = $this->utilisationPercent();
        $remaining = number_format($this->remainingAmount(), 2);

        return "90% Budget Utilisation Alert: {$this->department} ({$this->fiscal_year}) is at {$pct}% utilisation with ₹{$remaining} remaining before budget overrun.";
    }

    /**
     * Atomically apply a signed delta to utilised_amount (clamped >= 0) without
     * firing noisy generic model-updated audit rows on every PR, then log a
     * dedicated threshold_alert if crossing >= 90%.
     */
    public function applyDelta(float $delta, ?string $triggerContext = null): ?string
    {
        $previousUtilised = round((float) $this->utilised_amount, 2);
        $nextUtilised = max(0.0, round($previousUtilised + $delta, 2));

        self::whereKey($this->id)->update(['utilised_amount' => $nextUtilised]);
        $this->utilised_amount = number_format($nextUtilised, 2, '.', '');

        if ($delta <= 0) {
            return null;
        }

        return $this->recordThresholdAlertIfNeeded($previousUtilised, $triggerContext);
    }

    /**
     * Evaluate after spend reservation or ceiling revision; logs a persistent
     * audit alert when crossing the 90% threshold and returns the alert copy
     * whenever the department sits at or above 90%.
     */
    public function recordThresholdAlertIfNeeded(float $previousUtilised, ?string $triggerContext = null): ?string
    {
        $this->refresh();
        if (! $this->isNearLimit()) {
            return null;
        }

        $message = $this->alertMessage();
        if ($this->crossedAlertThreshold($previousUtilised)) {
            $detail = $triggerContext ? "{$message} Trigger: {$triggerContext}." : $message;
            $this->writeAuditEntry('threshold_alert', $detail);
        }

        return $message;
    }

    public function auditReference(): ?string
    {
        return "{$this->department} ({$this->fiscal_year})";
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
