<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AnnualBudget extends Model
{
    use LogsAuditTrail;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_CFO = 'pending_cfo';
    public const STATUS_PENDING_SG = 'pending_sg';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ON_HOLD = 'on_hold';

    protected $fillable = [
        'budget_number',
        'department',
        'fiscal_year',
        'submitted_by',
        'proposed_amount',
        'approved_amount',
        'justification',
        'status',
        'current_approval_level',
        'cfo_approved_at',
        'cfo_remarks',
        'sg_approved_at',
        'sg_remarks',
        'finalized_at',
        'department_budget_id',
    ];

    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'current_approval_level' => 'integer',
        'cfo_approved_at' => 'datetime',
        'sg_approved_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
    }

    public function departmentBudget(): BelongsTo
    {
        return $this->belongsTo(DepartmentBudget::class, 'department_budget_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AnnualBudgetItem::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function currentApprovalFor(?User $user = null): ?Approval
    {
        $user = $user ?? auth()->user();
        if (! $user || in_array($this->status, [self::STATUS_REJECTED, self::STATUS_APPROVED], true)) {
            return null;
        }

        $next = $this->approvals()
            ->whereIn('status', ['pending', 'on_hold'])
            ->orderBy('level')
            ->first();

        if (! $next) {
            return null;
        }

        if ($next->role_required === $user->role) {
            return $next;
        }

        return null;
    }

    public function isPendingCfo(): bool
    {
        return $this->status === self::STATUS_PENDING_CFO;
    }

    public function isPendingSg(): bool
    {
        return $this->status === self::STATUS_PENDING_SG;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isFinalized(): bool
    {
        return $this->isApproved() && $this->finalized_at !== null;
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_CFO => 'Pending CFO Approval',
            self::STATUS_PENDING_SG => 'Pending SG Final Approval',
            self::STATUS_APPROVED => 'Approved & Finalized',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DRAFT => 'Draft',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_CFO => 'badge-soft-warning',
            self::STATUS_PENDING_SG => 'badge-soft-info',
            self::STATUS_APPROVED => 'badge-soft-success',
            self::STATUS_ON_HOLD => 'badge-soft-secondary',
            self::STATUS_REJECTED => 'badge-soft-danger',
            default => 'badge-soft-light text-dark',
        };
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        return $this->submitted_by === $user->id && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ON_HOLD, self::STATUS_REJECTED], true);
    }

    public function auditReference(): ?string
    {
        return "{$this->budget_number} ({$this->department} · {$this->fiscal_year})";
    }
}
