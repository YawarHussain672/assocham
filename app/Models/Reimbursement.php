<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->belongsTo(Trf::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /** Off-TRF claims (no linked TRF) bypass the TRF link but need an SG override reason. */
    public function isOffTrf(): bool
    {
        return is_null($this->trf_id);
    }
}
