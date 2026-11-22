<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequisition extends Model
{
    use LogsAuditTrail;

    protected $fillable = [
        'pr_number', 'title', 'event_name', 'project_budget_id', 'department', 'region', 'required_by', 'sole_source', 'expense_type',
        'description', 'special_instructions', 'status', 'current_level', 'created_by',
    ];

    protected $casts = [
        'required_by' => 'date',
        'sole_source' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PurchaseRequisition $pr) {
            $pr->approvals()->delete();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function projectBudget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function approvedItems(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class)->where('status', 'approved');
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function rfq(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Rfq::class)->latestOfMany();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(Grn::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionVersion::class)->orderBy('version_number', 'desc');
    }

    public function recordVersion(string $event, string $actionTitle, ?string $changeSummary = null, ?string $comments = null, ?array $changes = null): PurchaseRequisitionVersion
    {
        $this->loadMissing(['items', 'creator']);
        $nextVersion = ($this->versions()->max('version_number') ?? 0) + 1;

        $snapshot = [
            'pr_number' => $this->pr_number,
            'title' => $this->title,
            'event_name' => $this->event_name,
            'department' => $this->department,
            'region' => $this->region,
            'required_by' => optional($this->required_by)->format('Y-m-d'),
            'sole_source' => $this->sole_source,
            'expense_type' => $this->expense_type,
            'description' => $this->description,
            'special_instructions' => $this->special_instructions,
            'status' => $this->status,
            'current_level' => $this->current_level,
            'estimated_cost' => $this->estimatedCostInclGst(),
            'items' => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'item_name' => $i->item_name,
                'description' => $i->description,
                'expense_type' => $i->expense_type,
                'quantity' => $i->quantity,
                'unit' => $i->unit,
                'unit_cost' => (float) $i->unit_cost,
                'gst_percent' => (float) $i->gst_percent,
                'line_total' => $i->lineTotalInclGst(),
            ])->toArray(),
        ];

        return $this->versions()->create([
            'version_number' => $nextVersion,
            'user_id' => auth()->id() ?? $this->created_by,
            'event' => $event,
            'action_title' => $actionTitle,
            'change_summary' => $changeSummary,
            'comments' => $comments,
            'changes' => $changes,
            'snapshot' => $snapshot,
        ]);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isCopex(): bool
    {
        if (in_array(strtolower((string) ($this->expense_type ?? '')), ['capex', 'copex'], true)) {
            return true;
        }

        return $this->items->contains(fn (PurchaseRequisitionItem $item) => $item->isCopex());
    }

    public function isCapex(): bool
    {
        return $this->isCopex();
    }

    public function isOpex(): bool
    {
        return ! $this->isCopex();
    }

    /** Sum of item costs, GST-inclusive — the figure budget checks run against. */
    public function estimatedCostInclGst(bool $onlyApprovedIfApplicable = false): float
    {
        if ($onlyApprovedIfApplicable && in_array($this->status, ['approved', 'partially_approved'], true)) {
            $items = $this->items->where('status', 'approved');
            if ($items->isEmpty()) {
                $items = $this->items->where('status', '!=', 'rejected');
            }
            return (float) $items->sum(fn (PurchaseRequisitionItem $item) => $item->lineTotalInclGst());
        }

        return (float) $this->items->sum(
            fn (PurchaseRequisitionItem $item) => $item->lineTotalInclGst()
        );
    }

    public function currentApprovalFor(?User $user = null): ?Approval
    {
        $user = $user ?? auth()->user();
        if (!$user || in_array($this->status, ['rejected', 'approved'], true)) {
            return null;
        }

        $next = $this->approvals->whereIn('status', ['pending', 'on_hold'])->sortBy('level')->first();
        if (!$next) {
            return null;
        }

        $canAdminOverride = $user->role === \App\Support\Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        if ($next->role_required === $user->role || $user->role === \App\Support\Roles::SG || $canAdminOverride) {
            return $next;
        }

        return null;
    }

    /**
     * Automatically creates an RFQ on behalf of this PR if approved and not yet linked.
     */
    public function autoCreateRfq(): ?Rfq
    {
        if ($this->rfqs()->exists()) {
            return $this->rfq;
        }

        if (! in_array($this->status, ['approved', 'partially_approved'], true)) {
            return null;
        }

        $buyer = \App\Support\DepartmentRouting::selectBuyerForDepartment($this->department, $this->creator);

        do {
            $number = 'RFQ-' . random_int(1000, 9999);
        } while (Rfq::where('rfq_number', $number)->exists());

        $this->loadMissing('projectBudget');
        $defaultProject = $this->projectBudget?->project_name
            ?: ($this->event_name ?: ($this->title ?: ($this->pr_number . ' Project')));
        $projectName = $defaultProject;
        $counter = 1;
        while (Rfq::where('project_name', $projectName)->exists()) {
            $projectName = $defaultProject . ' (' . ++$counter . ')';
        }

        $rfq = Rfq::create([
            'rfq_number' => $number,
            'sector' => $this->department,
            'project_name' => $projectName,
            'purchase_requisition_id' => $this->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => $this->required_by ? $this->required_by->toDateString() : now()->addDays(7)->toDateString(),
            'poc_user_id' => $buyer?->id,
            'status' => 'dispatched',
            'email_dispatched_at' => now(),
        ]);

        // Attach ALL empanelled vendors first so an SMTP failure/timeout never leaves an RFQ partially populated.
        $vendors = Vendor::empanelled()->get();
        $rfqVendorRows = [];
        foreach ($vendors as $vendor) {
            $rfqVendorRows[$vendor->id] = $rfq->rfqVendors()->create([
                'vendor_id' => $vendor->id,
                'response_status' => 'awaiting',
                'email_sent_at' => null,
            ]);
        }

        $rfq->load(['purchaseRequisition.items', 'attachments']);

        $emailedCount = 0;
        $mailTransportFailed = false;
        foreach ($vendors as $vendor) {
            if ($mailTransportFailed || empty($vendor->email)) {
                continue;
            }

            try {
                \Illuminate\Support\Facades\Mail::to($vendor->email)->send(new \App\Mail\RfqDispatchedMail($rfq, $vendor));
                $rfqVendorRows[$vendor->id]->update(['email_sent_at' => now()]);
                $emailedCount++;
            } catch (\Throwable $e) {
                $mailTransportFailed = true;
                \Illuminate\Support\Facades\Log::error("Failed auto-dispatching RFQ email to vendor {$vendor->id} (aborting remaining SMTP sends for {$rfq->rfq_number}): " . $e->getMessage());
            }
        }

        $rfq->writeAuditEntry('created', "RFQ {$rfq->rfq_number} automatically created on behalf of approved PR {$this->pr_number} and emailed to {$emailedCount} vendor(s).");

        return $rfq;
    }
}
