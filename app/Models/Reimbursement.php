<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reimbursement extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'claim_number', 'trf_id', 'employee_id', 'claim_amount',
        'advance_amount', 'balance_amount', 'routing_flag', 'reason', 'status', 'submitted_at',
    ];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function trf(): BelongsTo
    {
        return $this->belongsTo(Trf::class, 'trf_id');
    }

    /** Alias for trf relationship to prevent undefined property errors */
    public function travelRequisition(): BelongsTo
    {
        return $this->belongsTo(Trf::class, 'trf_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id')->withTrashed();
    }

    public function getDepartmentAttribute(): ?string
    {
        return $this->employee?->department ?? ($this->trf?->employee?->department ?? 'General');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class)->orderBy('expense_date')->orderBy('id');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function grievances(): HasMany
    {
        return $this->hasMany(Grievance::class, 'reimbursement_id')->latest();
    }

    /** Off-TRF claims (no linked TRF) bypass the TRF link but need an SG override reason. */
    public function isOffTrf(): bool
    {
        return is_null($this->trf_id);
    }

    /** Net balance settlement amount (payable or refundable). */
    public function netPayable(): float
    {
        return (float) ($this->balance_amount ?? 0);
    }
}
