<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps to the "exceptions" table. Named ExceptionRequest (not Exception)
 * to avoid colliding with PHP's built-in \Exception class.
 */
class ExceptionRequest extends Model
{
    use LogsAuditTrail;

    protected $table = 'exceptions';

    protected $fillable = [
        'exception_type', 'reference_type', 'reference_id', 'justification',
        'requested_by', 'approver_queue', 'status', 'decided_by', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }
}
