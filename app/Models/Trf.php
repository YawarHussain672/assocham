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
        'is_international', 'travel_start', 'travel_end', 'insurance',
        'accommodation', 'requested_advance', 'status',
    ];

    protected $casts = [
        'is_international' => 'boolean',
        'travel_start' => 'date',
        'travel_end' => 'date',
        'insurance' => 'boolean',
        'accommodation' => 'boolean',
        'requested_advance' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function flightLogs(): HasMany
    {
        return $this->hasMany(FlightLog::class);
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

    /** Domestic TRFs are final at ASG (L1); only international/direct-reports need SG (L2). */
    public function requiresSgApproval(): bool
    {
        return $this->is_international;
    }

    /**
     * Deck (p.41, live-portal note): "employees with a settlement pending
     * beyond 30 days from travel end date are auto-flagged Overdue and
     * blocked from raising a new TRF." Returns the offending past trip, or
     * null if the employee is clear. Checked by TrfController::store()
     * before allowing a new TRF.
     */
    public static function findOverdueSettlementFor(int $employeeId): ?self
    {
        return static::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('travel_end', '<', now()->subDays(30))
            ->whereDoesntHave('reimbursements', fn ($q) => $q->where('status', 'approved'))
            ->orderBy('travel_end')
            ->first();
    }
}
