<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CcsQuote extends Model
{
    protected $fillable = [
        'cost_comparative_statement_id', 'vendor_id', 'quoted_cost',
        'delivery_days', 'remarks', 'rank',
    ];

    protected $casts = [
        'quoted_cost' => 'decimal:2',
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
