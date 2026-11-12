<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Rfq extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'rfq_number', 'sector', 'project_name', 'purchase_requisition_id', 'requisition_date',
        'due_date', 'poc_user_id', 'status', 'email_dispatched_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'due_date' => 'date',
        'email_dispatched_at' => 'datetime',
    ];

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function poc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poc_user_id');
    }

    public function rfqVendors(): HasMany
    {
        return $this->hasMany(RfqVendor::class);
    }

    public function costComparativeStatement(): HasOne
    {
        return $this->hasOne(CostComparativeStatement::class);
    }
}
