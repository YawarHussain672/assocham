<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightLog extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'ref', 'trf_id', 'type', 'vendor_id', 'cost', 'file_path', 'uploaded_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function trf(): BelongsTo
    {
        return $this->belongsTo(Trf::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function mailTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingMailTransaction::class)->latest('sent_at')->latest('id');
    }
}
