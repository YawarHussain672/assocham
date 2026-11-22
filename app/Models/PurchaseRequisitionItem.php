<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'purchase_requisition_id', 'purchase_order_id', 'vendor_id', 'item_name', 'description', 'quantity', 'unit',
        'unit_cost', 'gst_percent', 'expense_type', 'item_catalog_id', 'status',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'gst_percent' => 'decimal:2',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function itemCatalog(): BelongsTo
    {
        return $this->belongsTo(ItemCatalog::class, 'item_catalog_id');
    }

    public function isCopex(): bool
    {
        return in_array(strtolower((string) ($this->expense_type ?? '')), ['capex', 'copex'], true);
    }

    public function isCapex(): bool
    {
        return $this->isCopex();
    }

    public function isOpex(): bool
    {
        return strtolower((string) ($this->expense_type ?? '')) === 'opex';
    }

    public function lineTotalInclGst(): float
    {
        $base = (float) $this->unit_cost * (int) $this->quantity;

        return round($base * (1 + ((float) $this->gst_percent / 100)), 2);
    }
}
