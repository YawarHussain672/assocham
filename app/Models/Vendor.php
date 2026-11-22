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
        'contact_person', 'contact_phone', 'contact_email', 'email', 'phone', 'id_number',
        'bank_name', 'bank_branch', 'bank_account_holder', 'bank_account_no', 'bank_account_type', 'bank_ifsc',
        'msme_status', 'msme_number', 'incorporation_certificate_no', 'partnership_deed_no', 'payment_terms',
        'address', 'location', 'references', 'rating', 'previous_orders',
        'status', 'compliance_status', 'empanelment_email_sent_at', 'blacklist_reason', 'blacklisted_at', 'blacklisted_by', 'submitted_by',
        'declaration_accepted', 'declaration_signatory_name', 'declaration_designation', 'declaration_date',
    ];

    protected $casts = [
        'msme_status' => 'boolean',
        'declaration_accepted' => 'boolean',
        'declaration_date' => 'date',
        'rating' => 'float',
        'previous_orders' => 'integer',
        'blacklisted_at' => 'datetime',
        'empanelment_email_sent_at' => 'datetime',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            $record = $this->where('id', $value)->first();
            if ($record) {
                return $record;
            }
        }

        return $this->where('vendor_code', $value)->first();
    }

    public function blacklistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blacklisted_by')->withTrashed();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
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

    public function signatureAttachment(): ?Attachment
    {
        $atts = $this->relationLoaded('attachments') ? $this->attachments : $this->attachments()->get();
        return $atts
            ->filter(function ($a) {
                $name = strtolower($a->original_name);
                return str_contains($name, 'signature') 
                    || str_contains($name, 'stamp') 
                    || str_contains($name, 'declaration');
            })
            ->sortByDesc('id')
            ->first();
    }

    public function isEmpanelled(): bool
    {
        return in_array($this->status, ['approved', 'empanelled'], true)
            && in_array($this->compliance_status, ['active', null], true)
            && ! $this->isBlacklisted();
    }

    public function isBlacklisted(): bool
    {
        return $this->compliance_status === 'blacklisted' || $this->status === 'blacklisted';
    }

    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    public function isEligibleForTransactions(): bool
    {
        return $this->isEmpanelled() && ! $this->isBlacklisted();
    }

    public function getAverageRatingAttribute(): ?float
    {
        $evals = $this->relationLoaded('evaluations') ? $this->evaluations : $this->evaluations()->get();
        if ($evals->isNotEmpty()) {
            return round($evals->avg(fn ($e) => $e->averageRating()), 1);
        }
        return null;
    }

    public function getReviewsCountAttribute(): int
    {
        if ($this->relationLoaded('evaluations')) {
            return $this->evaluations->count();
        }
        return $this->evaluations()->count();
    }

    public function getLatestReviewCommentAttribute(): ?string
    {
        $evals = $this->relationLoaded('evaluations') ? $this->evaluations : $this->evaluations()->get();
        return $evals->whereNotNull('comments')->sortByDesc('id')->first()?->comments;
    }

    public function isTopRated(): bool
    {
        return ($this->average_rating ?? 0.0) >= 4.5;
    }

    public function isRecommended(): bool
    {
        return ($this->average_rating ?? 0.0) >= 4.0;
    }

    public function getTotalOrdersCountAttribute(): int
    {
        $poCount = $this->relationLoaded('purchaseOrders') ? $this->purchaseOrders->count() : $this->purchaseOrders()->count();
        return (int)($this->previous_orders ?? 0) + $poCount;
    }

    public function currentApprovalFor(?User $user): ?Approval
    {
        if (! $user || $this->status === 'rejected' || $this->isBlacklisted()) {
            return null;
        }

        $approvals = $this->relationLoaded('approvals')
            ? $this->approvals->whereIn('status', ['pending', 'on_hold'])->sortBy('level')
            : $this->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->get();

        $next = $approvals->first();

        if (! $next) {
            return null;
        }

        $canAdminOverride = $user->role === \App\Support\Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        if ($next->role_required === $user->role || $canAdminOverride) {
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
            ? $this->approvals->whereIn('status', ['pending', 'on_hold'])->sortBy('level')->first()
            : $this->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->first();

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
        return $query->where(function ($q) {
            $q->where('status', 'approved')->orWhere('status', 'empanelled');
        })->where(function ($q) {
            $q->where('compliance_status', 'active')->orWhereNull('compliance_status');
        })->where('status', '!=', 'blacklisted')
          ->where('status', '!=', 'on_hold')
          ->where(function ($q) {
              $q->where('compliance_status', '!=', 'blacklisted')->orWhereNull('compliance_status');
          });
    }
}
