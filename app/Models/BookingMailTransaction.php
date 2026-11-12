<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingMailTransaction extends Model
{
    protected $fillable = [
        'trf_id',
        'flight_log_id',
        'mail_type',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_html',
        'attachment_path',
        'status',
        'error_message',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function trf(): BelongsTo
    {
        return $this->belongsTo(Trf::class);
    }

    public function flightLog(): BelongsTo
    {
        return $this->belongsTo(FlightLog::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

