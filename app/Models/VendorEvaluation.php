<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEvaluation extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'trf_id', 'vendor_id', 'cost_score', 'time_score', 'quality_score',
        'responsiveness_score', 'accuracy_score', 'comments', 'rated_by',
    ];

    public function trf(): BelongsTo
    {
        return $this->belongsTo(Trf::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by')->withTrashed();
    }

    /** Average across the deck's 5 scoring parameters (cost/time/quality/responsiveness/accuracy). */
    public function averageRating(): float
    {
        return round((
            $this->cost_score + $this->time_score + $this->quality_score
            + $this->responsiveness_score + $this->accuracy_score
        ) / 5, 1);
    }
}
