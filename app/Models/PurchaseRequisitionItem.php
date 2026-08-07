<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'purchase_requisition_id', 'item_name', 'quantity', 'unit',
        'unit_cost', 'gst_percent', 'status',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'gst_percent' => 'decimal:2',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function lineTotalInclGst(): float
    {
        $base = (float) $this->unit_cost * (int) $this->quantity;

        return round($base * (1 + ((float) $this->gst_percent / 100)), 2);
    }
}
