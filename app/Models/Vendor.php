<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vendor extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'vendor_code', 'company_name', 'pan', 'gstin', 'category',
        'contact_person', 'contact_phone', 'contact_email',
        'bank_account_no', 'bank_ifsc', 'msme_status', 'payment_terms',
        'address', 'status', 'compliance_status', 'submitted_by',
    ];

    protected $casts = [
        'msme_status' => 'boolean',
    ];

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(VendorEvaluation::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isEmpanelled(): bool
    {
        return $this->status === 'approved' && $this->compliance_status === 'active';
    }

    /**
     * Only empanelled, non-blacklisted vendors are selectable on an RFQ or
     * PO (deck p.9, "Why it matters") — the query-level equivalent of
     * isEmpanelled(), for RFQ/PO/GRN vendor pickers.
     */
    public function scopeEmpanelled(Builder $query): Builder
    {
        return $query->where('status', 'approved')->where('compliance_status', 'active');
    }
}
