<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequisition extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'pr_number', 'department', 'required_by', 'sole_source',
        'status', 'current_level', 'created_by',
    ];

    protected $casts = [
        'required_by' => 'date',
        'sole_source' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PurchaseRequisition $pr) {
            $pr->approvals()->delete();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function rfq(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Rfq::class)->latestOfMany();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** Sum of item costs, GST-inclusive — the figure budget checks run against. */
    public function estimatedCostInclGst(): float
    {
        return (float) $this->items->sum(
            fn (PurchaseRequisitionItem $item) => $item->lineTotalInclGst()
        );
    }
}
