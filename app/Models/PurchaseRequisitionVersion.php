<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionVersion extends Model
{
    protected $fillable = [
        'purchase_requisition_id',
        'version_number',
        'user_id',
        'event',
        'action_title',
        'change_summary',
        'comments',
        'changes',
        'snapshot',
    ];

    protected $casts = [
        'changes' => 'array',
        'snapshot' => 'array',
        'version_number' => 'integer',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
