<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false; // append-only: created_at is set explicitly, no updated_at

    protected $fillable = [
        'user_id', 'module', 'action', 'reference', 'auditable_type', 'auditable_id', 'detail',
    ];

    // $timestamps = false means Eloquent won't auto-cast created_at to Carbon
    // the way it does for normal timestamp columns — must be explicit, or
    // ->format() calls on it fail with "Call to a member function format() on string".
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $log) => $log->created_at ??= now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Exclude dev/demo persona-switch traces and anonymous bulk-seeder model events
     * so Dashboard and Audit Trail display the exact same operational governance ledger.
     */
    public function scopeOperational(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('module', '!=', 'System')
                    ->orWhere('action', '!=', 'Switched Role');
            })
            ->where(function (Builder $q) {
                $q->whereNotNull('user_id')
                    ->orWhereNotIn('action', ['created', 'updated']);
            });
    }
}
