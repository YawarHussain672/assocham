<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CostComparativeStatement extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'ccs_number', 'rfq_id', 'selected_vendor_id', 'selection_remarks',
        'selected_by', 'selected_at', 'award_email_sent_at', 'status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'selected_at' => 'datetime',
        'award_email_sent_at' => 'datetime',
    ];

    public function selectedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'selected_vendor_id');
    }

    public function selectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(CcsQuote::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /** Effective winning vendor: manually chosen vendor if specified, otherwise lowest quote (L1). */
    public function winningQuote(): ?CcsQuote
    {
        if ($this->selected_vendor_id) {
            $chosen = $this->quotes->firstWhere('vendor_id', $this->selected_vendor_id);
            if ($chosen) {
                return $chosen;
            }
        }

        return $this->lowestQuote();
    }

    /** Lowest quoted cost — the L1 vendor per the deck's ranking rule. */
    public function lowestQuote(): ?CcsQuote
    {
        return $this->quotes->where('quoted_cost', '>', 0)->sortBy('quoted_cost')->first() ?? $this->quotes->sortBy('quoted_cost')->first();
    }
}
