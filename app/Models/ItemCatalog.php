<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCatalog extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'item_code',
        'name',
        'category',
        'expense_type',
        'fixed_price',
        'unit',
        'gst_percent',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'fixed_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'item_catalog_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOpex(Builder $query): Builder
    {
        return $query->where('expense_type', 'opex');
    }

    public function scopeCopex(Builder $query): Builder
    {
        return $query->where('expense_type', 'copex');
    }

    public function isCopex(): bool
    {
        return strtolower((string) $this->expense_type) === 'copex';
    }

    public function isOpex(): bool
    {
        return strtolower((string) $this->expense_type) === 'opex';
    }

    public function totalWithGst(): float
    {
        $price = (float) $this->fixed_price;
        $gst = (float) $this->gst_percent;

        return round($price * (1 + ($gst / 100)), 2);
    }

    public function formattedPrice(): string
    {
        return '₹' . number_format((float) $this->fixed_price, 2);
    }

    public function formattedTotalWithGst(): string
    {
        return '₹' . number_format($this->totalWithGst(), 2);
    }
}
