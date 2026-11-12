<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vendor extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'vendor_code', 'company_name', 'pan', 'gstin', 'category',
        'contact_person', 'contact_phone', 'contact_email', 'email', 'phone',
        'bank_account_no', 'bank_ifsc', 'msme_status', 'payment_terms',
        'address', 'location', 'rating', 'previous_orders',
        'status', 'compliance_status', 'empanelment_email_sent_at', 'blacklist_reason', 'blacklisted_at', 'blacklisted_by', 'submitted_by',
    ];

    protected $casts = [
        'msme_status' => 'boolean',
        'rating' => 'float',
        'previous_orders' => 'integer',
        'blacklisted_at' => 'datetime',
        'empanelment_email_sent_at' => 'datetime',
    ];

    public function blacklistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(VendorEvaluation::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isEmpanelled(): bool
    {
        return $this->status === 'approved' && $this->compliance_status === 'active';
    }

    public function isBlacklisted(): bool
    {
        return $this->compliance_status === 'blacklisted';
    }

    public function getAverageRatingAttribute(): ?float
    {
        if ($this->relationLoaded('evaluations') && $this->evaluations->isNotEmpty()) {
            return round($this->evaluations->avg(fn ($e) => $e->averageRating()), 1);
        }
        return $this->rating !== null ? (float)$this->rating : null;
    }

    public function getTotalOrdersCountAttribute(): int
    {
        $poCount = $this->relationLoaded('purchaseOrders') ? $this->purchaseOrders->count() : $this->purchaseOrders()->count();
        return (int)($this->previous_orders ?? 0) + $poCount;
    }

    public function currentApprovalFor(?User $user): ?Approval
    {
        if (!$user || $this->status === 'rejected') {
            return null;
        }

        $approvals = $this->relationLoaded('approvals')
            ? $this->approvals->where('status', 'pending')->sortBy('level')
            : $this->approvals()->where('status', 'pending')->orderBy('level')->get();

        $next = $approvals->first();

        if (!$next) {
            return null;
        }

        if ($next->role_required === $user->role || $user->role === \App\Support\Roles::ADMIN_HEAD) {
            return $next;
        }

        return null;
    }

    public function getPendingStageTitleAttribute(): ?string
    {
        $stageTitles = [
            1 => 'Admin Head',
            2 => 'ASG 1',
            3 => 'ASG 2',
            4 => 'CFO',
        ];

        $pending = $this->relationLoaded('approvals')
            ? $this->approvals->where('status', 'pending')->sortBy('level')->first()
            : $this->approvals()->where('status', 'pending')->orderBy('level')->first();

        if (!$pending) {
            return null;
        }

        return $stageTitles[$pending->level] ?? \App\Support\Roles::label($pending->role_required);
    }

    /* Attribute aliases for flexible UI rendering */
    public function getEmailAttribute(): ?string
    {
        return $this->attributes['contact_email'] ?? $this->attributes['email'] ?? null;
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['contact_email'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['contact_phone'] ?? $this->attributes['phone'] ?? null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['contact_phone'] = $value;
    }

    public function getPanNumberAttribute(): ?string
    {
        return $this->pan ?? $this->attributes['pan_number'] ?? null;
    }

    public function getBankAccountAttribute(): ?string
    {
        return $this->bank_account_no ?? $this->attributes['bank_account'] ?? null;
    }

    public function getIfscCodeAttribute(): ?string
    {
        return $this->bank_ifsc ?? $this->attributes['ifsc_code'] ?? null;
    }

    /**
     * Only empanelled, non-blacklisted vendors are selectable on an RFQ or
     * PO (deck p.9, "Why it matters") — the query-level equivalent of
     * isEmpanelled(), for RFQ/PO/GRN vendor pickers.
     */
    public function scopeEmpanelled(Builder $query): Builder
    {
        return $query->where('status', 'approved')->where('compliance_status', 'active');
    }
}
