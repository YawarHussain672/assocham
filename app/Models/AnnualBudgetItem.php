<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualBudgetItem extends Model
{
    protected $fillable = [
        'annual_budget_id',
        'category',
        'title',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }
}
