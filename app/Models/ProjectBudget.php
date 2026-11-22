<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends Model
{
    use LogsAuditTrail;

    public const ALERT_THRESHOLD_PERCENT = 90.0;

    protected $fillable = [
        'project_name',
        'department',
        'council',
        'cluster_head',
        'hod',
        'category',
        'location',
        'event_month',
        'fiscal_year',
        'allocated_amount',
        'utilised_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'utilised_amount' => 'decimal:2',
    ];

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class, 'project_budget_id');
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

        return "90% Project Budget Alert: {$this->project_name} ({$this->department} - {$this->fiscal_year}) is at {$pct}% utilisation with ₹{$remaining} headroom remaining.";
    }

    /**
     * Atomically apply a signed delta to utilised_amount (clamped >= 0).
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
        return "{$this->project_name} ({$this->department} - {$this->fiscal_year})";
    }

    public static function currentFiscalYear(): string
    {
        return DepartmentBudget::currentFiscalYear();
    }
}
