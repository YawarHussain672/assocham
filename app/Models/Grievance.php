<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Grievance extends Model
{
    use LogsAuditTrail;

    /** Fixed escalation order per the deck: HOD -> Admin Head -> CFO -> SG. */
    public const ESCALATION_ORDER = ['hod', 'admin_head', 'cfo', 'sg'];

    protected $fillable = [
        'ticket_number', 'subject_category', 'subject', 'description',
        'raised_by', 'current_owner_role', 'status', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function nextOwnerRole(): ?string
    {
        $position = array_search($this->current_owner_role, self::ESCALATION_ORDER, true);

        return $position === false ? null : (self::ESCALATION_ORDER[$position + 1] ?? null);
    }
}
