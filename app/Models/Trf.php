<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Trf extends Model
{
    use LogsAuditTrail;

    // Eloquent's default table-name guess pluralizes "trf" as "trves" (the
    // -f→-ves irregular rule, e.g. knife→knives) — same bug class as the
    // migrations' foreignId('trf_id')->constrained() gotcha from Phase 0.
    // Must be set explicitly; don't remove.
    protected $table = 'trfs';

    protected $fillable = [
        'trf_number', 'employee_id', 'booking_type', 'purpose', 'route',
        'travel_mode', 'destinations',
        'is_international', 'travel_start', 'travel_end', 'insurance',
        'visa_required', 'visa_details',
        'accommodation', 'accommodation_details', 'requested_advance', 'status',
    ];

    protected $casts = [
        'is_international' => 'boolean',
        'travel_start' => 'date',
        'travel_end' => 'date',
        'insurance' => 'boolean',
        'visa_required' => 'boolean',
        'accommodation' => 'boolean',
        'requested_advance' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function getDepartmentAttribute(): ?string
    {
        return $this->employee?->department ?? 'General';
    }

    public function flightLogs(): HasMany
    {
        return $this->hasMany(FlightLog::class);
    }

    public function mailTransactions(): HasMany
    {
        return $this->hasMany(BookingMailTransaction::class)->latest('sent_at')->latest('id');
    }

    public function boardingPasses(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(VendorEvaluation::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /** Domestic TRFs are final at ASG (L1); only international/direct-reports need SG (L2). */
    public function requiresSgApproval(): bool
    {
        return $this->is_international;
    }

    public const SETTLEMENT_OVERDUE_DAYS = 30;

    /**
     * Base query for approved trips whose settlement deadline (> 30 days from
     * travel end date) has elapsed without an approved reimbursement claim.
     */
    public static function overdueBaseQuery()
    {
        $cutoff = now()->subDays(self::SETTLEMENT_OVERDUE_DAYS);

        return static::where('status', 'approved')
            ->where(function ($q) use ($cutoff) {
                $q->where('travel_end', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('travel_end')->where('travel_start', '<', $cutoff);
                    });
            })
            ->whereDoesntHave('reimbursements', fn ($q) => $q->where('status', 'approved'));
    }

    /**
     * Deck (p.41, live-portal note): "employees with a settlement pending
     * beyond 30 days from travel end date are auto-flagged Overdue and
     * blocked from raising a new TRF." Returns the offending past trip, or
     * null if the employee is clear. Checked by TrfController::create(),
     * store(), update(), and importExcel().
     */
    public static function findOverdueSettlementFor(int $employeeId): ?self
    {
        return static::overdueBaseQuery()
            ->with(['employee', 'reimbursements'])
            ->where('employee_id', $employeeId)
            ->orderBy('travel_end')
            ->orderBy('travel_start')
            ->first();
    }

    /**
     * Returns the earliest overdue, unsettled TRF keyed by employee_id.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function overdueSettlementsByEmployee()
    {
        return static::overdueBaseQuery()
            ->with(['employee', 'reimbursements'])
            ->orderBy('travel_end')
            ->orderBy('travel_start')
            ->get()
            ->unique('employee_id')
            ->keyBy('employee_id');
    }

    public function settlementReferenceDate(): ?\Illuminate\Support\Carbon
    {
        return $this->travel_end ?? $this->travel_start;
    }

    public function isSettlementOverdue(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $refDate = $this->settlementReferenceDate();
        if (! $refDate || ! $refDate->lt(now()->subDays(self::SETTLEMENT_OVERDUE_DAYS))) {
            return false;
        }

        if ($this->relationLoaded('reimbursements')) {
            return ! $this->reimbursements->contains('status', 'approved');
        }

        return ! $this->reimbursements()->where('status', 'approved')->exists();
    }

    public function overdueDays(): int
    {
        $refDate = $this->settlementReferenceDate();
        if (! $refDate) {
            return 0;
        }

        return max(0, (int) $refDate->diffInDays(now()));
    }
}
