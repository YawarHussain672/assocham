<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'po_number', 'purchase_requisition_id', 'cost_comparative_statement_id', 'vendor_id', 'po_qty',
        'delivery_date', 'amount_incl_gst', 'status', 'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'amount_incl_gst' => 'decimal:2',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function costComparativeStatement(): BelongsTo
    {
        return $this->belongsTo(CostComparativeStatement::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(Grn::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isHighValue(): bool
    {
        return (float) $this->amount_incl_gst >= 1_000_000; // >= ₹10L per deck's DoA threshold
    }

    /** Lowest-level pending approval step, or null if none/fully decided. */
    public function pendingApproval(): ?Approval
    {
        return $this->approvals->where('status', 'pending')->sortBy('level')->first();
    }

    /**
     * Deck (p.43, live-portal note): "Multiple GRNs can be raised against a
     * single PO until the ordered quantity is fully delivered." Rejected
     * GRNs don't count against the total, so a rejected receipt can be
     * re-logged.
     */
    public function receivedQty(): int
    {
        return (int) $this->grns->whereIn('status', ['pending_hod', 'approved'])->sum('received_qty');
    }

    public function remainingQtyToReceive(): int
    {
        return max($this->po_qty - $this->receivedQty(), 0);
    }
}
