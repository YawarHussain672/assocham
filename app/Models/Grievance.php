<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Grievance extends Model
{
    use LogsAuditTrail;

    /** Fixed escalation order per the deck: HOD -> Admin Head -> CFO -> SG. */
    public const ESCALATION_ORDER = ['hod', 'admin_head', 'cfo', 'sg'];

    protected $fillable = [
        'ticket_number', 'subject_category', 'subject', 'description',
        'reimbursement_id', 'raised_by', 'current_owner_role',
        'status', 'resolved_by', 'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by')->withTrashed();
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by')->withTrashed();
    }

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class, 'reimbursement_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(GrievanceUpdate::class)->orderBy('created_at')->orderBy('id');
    }

    public function auditReference(): ?string
    {
        return $this->ticket_number;
    }

    public function nextOwnerRole(): ?string
    {
        $position = array_search($this->current_owner_role, self::ESCALATION_ORDER, true);

        return $position === false ? null : (self::ESCALATION_ORDER[$position + 1] ?? null);
    }
}
