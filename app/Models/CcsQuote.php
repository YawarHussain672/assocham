<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CcsQuote extends Model
{
    protected $fillable = [
        'cost_comparative_statement_id', 'vendor_id', 'quoted_cost',
        'basic_price', 'gst_amount', 'transportation_cost',
        'delivery_days', 'warranty', 'payment_terms',
        'technical_compliance', 'quotation_validity',
        'remarks', 'rank', 'is_selected', 'selection_remarks',
    ];

    protected $casts = [
        'quoted_cost' => 'decimal:2',
        'basic_price' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'transportation_cost' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    public function costComparativeStatement(): BelongsTo
    {
        return $this->belongsTo(CostComparativeStatement::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
