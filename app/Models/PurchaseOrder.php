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
        'delivery_date', 'amount_incl_gst', 'status', 'needs_regeneration', 'revision_number', 'regenerated_at', 'created_by',
        'event_name', 'payment_terms', 'delivery_terms', 'billing_address', 'delivery_address',
        'contact_person', 'contact_number', 'terms_and_conditions',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'amount_incl_gst' => 'decimal:2',
        'needs_regeneration' => 'boolean',
        'revision_number' => 'integer',
        'regenerated_at' => 'datetime',
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

    /** Whether this PO was edited post-approval, re-approved, and is ready to regenerate. */
    public function canRegenerate(): bool
    {
        return $this->status === 'approved' && (bool) $this->needs_regeneration;
    }

    /** Lowest-level pending approval step, or null if none/fully decided. */
    public function pendingApproval(): ?Approval
    {
        return $this->approvals->where('status', 'pending')->sortBy('level')->first();
    }

    /**
     * Determine if a given user can act on the current pending approval step of this PO.
     */
    public function currentApprovalFor(?User $user = null): ?Approval
    {
        $user = $user ?? auth()->user();
        if (!$user || in_array($this->status, ['rejected', 'approved', 'cancelled'], true)) {
            return null;
        }

        $next = $this->approvals->where('status', 'pending')->sortBy('level')->first();
        if (!$next) {
            return null;
        }

        if ($next->role_required === $user->role || in_array($user->role, [\App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::SG], true)) {
            return $next;
        }

        return null;
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

    /**
     * Default ASSOCHAM Registered Billing Address & GSTIN
     */
    public static function defaultBillingAddress(): string
    {
        return "THE ASSOCIATED CHAMBERS OF COMMERCE & INDUSTRY OF INDIA (ASSOCHAM DELHI)\n4th Floor, Gate Number 2, YMCA CCL BUILDING,\n01, Jai Singh Road, New Delhi - 110001\nGST: 07AAATT4704C1ZV";
    }

    /**
     * Default 8-Clause Standard Legal Terms from Approved PO Format
     */
    public static function defaultTermsAndConditions(): string
    {
        return "1. This Purchase Order (PO) number must appear on all Invoices, Packing Lists and any other correspondence associated with this order. Vendor assumes all risk of loss until receipt by ASSOCHAM.\n"
            . "2. Vendor must submit invoices within 07 (seven) days of completion of services or delivery of goods. ASSOCHAM may reject all incorrect, incomplete, or improperly submitted invoices.\n"
            . "3. Vendor shall indemnify and hold ASSOCHAM, its affiliates, employees and agents harmless from and against all third-party claims, demands, litigation, or proceedings and from and against all damages of every kind, arising from or relating to Vendor's negligence, wilful misconduct or violation of any third-party intellectual property right in connection with the products or services purchased under this PO.\n"
            . "4. Unless expressly stated in the Purchase Order, the price specified in the Purchase Order is inclusive of all applicable taxes, freight, packaging, insurance, handling, permits, approvals, licenses and other charges.\n"
            . "5. Purchaser may terminate or modify all or any portion of this Purchase Order prior to shipment of any Goods or Supplier's commencement of performance of Services at no charge and without liability by giving Supplier written notice of such termination or change.\n"
            . "6. Supplier, its Affiliates and its Personnel will (a) keep confidential the terms and existence of this Purchase Order and all information obtained from Purchaser that is identified as confidential or proprietary or that, given the nature of such information or the manner of its disclosure, reasonably should be considered confidential or proprietary and (b) use such information only for the purposes of this Purchase Order. Purchaser's confidential information includes, without limitation, all supplies, blueprints, sketches, drawings, specifications and other technical or commercial information furnished by or developed for Purchaser in connection with the Purchase Order, including Work Product of Supplier. All such information is Purchaser's exclusive property. Supplier will not use any trade name, trademark, service mark, logo or commercial symbol, or any other proprietary rights of Purchaser or any of its Affiliates in any manner without prior written authorization.\n"
            . "7. Supplier represents and warrants that it complies with all export control, anti-money laundering, anti-bribery, data privacy, child labour and other laws and regulations applicable to it, its products and services.\n"
            . "8. All disputes are subject to Delhi jurisdiction.";
    }

    /**
     * Returns editable or default terms
     */
    public function getEffectiveTermsAttribute(): string
    {
        return !empty(trim((string)$this->terms_and_conditions))
            ? (string)$this->terms_and_conditions
            : self::defaultTermsAndConditions();
    }

    /**
     * Returns terms split into a list of clauses
     */
    public function getTermsListAttribute(): array
    {
        $raw = $this->effective_terms;
        $lines = preg_split('/(\r\n|\n|\r)/', $raw);
        $clauses = [];
        $current = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            // Check if line starts with a number or bullet
            if (preg_match('/^(\d+[\.\)]|\*|\-)\s*(.+)$/', $trimmed, $m)) {
                if ($current !== '') {
                    $clauses[] = $current;
                }
                $current = $m[2];
            } else {
                $current .= ($current !== '' ? ' ' : '') . $trimmed;
            }
        }
        if ($current !== '') {
            $clauses[] = $current;
        }

        return $clauses ?: [self::defaultTermsAndConditions()];
    }

    /**
     * Effective billing address
     */
    public function getEffectiveBillingAddressAttribute(): string
    {
        return !empty(trim((string)$this->billing_address))
            ? (string)$this->billing_address
            : self::defaultBillingAddress();
    }

    /**
     * Effective payment terms
     */
    public function getEffectivePaymentTermsAttribute(): string
    {
        return !empty(trim((string)$this->payment_terms))
            ? (string)$this->payment_terms
            : ($this->vendor?->payment_terms ?: 'Within 30 days from date of verified invoice and physical verification of goods/services.');
    }

    /**
     * Effective delivery terms
     */
    public function getEffectiveDeliveryTermsAttribute(): string
    {
        return !empty(trim((string)$this->delivery_terms))
            ? (string)$this->delivery_terms
            : ($this->delivery_date ? 'Delivery on or before ' . $this->delivery_date->format('d M Y') : 'Immediate / On schedule');
    }

    /**
     * Final authorized approval step
     */
    public function finalApproval(): ?Approval
    {
        return $this->approvals()
            ->where('status', 'approved')
            ->orderByDesc('level')
            ->first();
    }

    /**
     * Whether the PO is officially E-Signed
     */
    public function isESigned(): bool
    {
        return $this->status === 'approved' && $this->finalApproval() !== null;
    }

    /**
     * E-Signed Signer User
     */
    public function eSignedBy(): ?User
    {
        return $this->finalApproval()?->approver;
    }

    /**
     * Role of the E-Signer
     */
    public function eSignedRoleLabel(): string
    {
        $approval = $this->finalApproval();
        if (!$approval) return 'Authorized Signatory';
        return \App\Support\Roles::label($approval->role_required);
    }

    /**
     * Digital E-Signature verification hash code
     */
    public function eSignVerificationHash(): string
    {
        $approval = $this->finalApproval();
        if (!$approval) return 'PENDING-APPROVAL';
        $seed = "ASSOCHAM-{$this->id}-{$this->po_number}-{$approval->approver_id}-" . optional($approval->acted_at)->timestamp;
        $hash = strtoupper(substr(hash('sha256', $seed), 0, 16));
        return 'ASSOCHAM-ESIGN-' . substr($hash, 0, 4) . '-' . substr($hash, 4, 4) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    }

    /**
     * Amount in Indian Words format (e.g. INR Ninety Only)
     */
    public function amountInWords(): string
    {
        $amount = (float)$this->amount_incl_gst;
        return self::convertAmountToIndianWords($amount);
    }

    public static function convertAmountToIndianWords(float $num): string
    {
        $no = floor($num);
        $point = (int)round(($num - $no) * 100);
        $hundred = null;
        $digits_1 = strlen((string)$no);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_1) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;
            if ($number) {
                $counter = count($str);
                $plural = ($counter && $number > 9) ? '' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21)
                    ? $words[$number] . ' ' . ($digits[$counter] ?? '') . $plural . ' ' . $hundred
                    : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . ($digits[$counter] ?? '') . $plural . ' ' . $hundred;
            } else {
                $str[] = null;
            }
        }

        $rupees = implode('', array_reverse(array_filter($str)));
        $paise = ($point > 0)
            ? ' and ' . ($words[floor($point / 10) * 10] . ' ' . $words[$point % 10]) . ' Paise'
            : '';

        $res = preg_replace('/\s+/', ' ', trim($rupees) ?: 'Zero');
        return 'INR ' . $res . $paise . ' Only';
    }

}
