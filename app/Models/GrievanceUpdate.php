<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceUpdate extends Model
{
    protected $fillable = [
        'grievance_id',
        'user_id',
        'action',
        'owner_role',
        'status',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'filed' => 'Ticket Filed',
            'responded' => 'Owner Response',
            'escalated' => 'Escalated',
            'resolved' => 'Resolved & Closed',
            default => ucfirst($this->action),
        };
    }
}

