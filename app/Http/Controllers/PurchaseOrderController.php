<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\DepartmentBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Support\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PO Creation & Approval — fifth Phase 2 module (see project memory:
 * erp-laravel-phase2-procurement). Workflow (deck p.13): Create PO (Buyer)
 * -> Admin Head Review (non-IT items) -> CFO Approval (per DoA/value) ->
 * SG Approval (where applicable) -> PO Issued.
 *
 * Unlike every prior module, the approval chain isn't fixed — it's built
 * per PO from department + value, since the deck's permission matrix (p.7)
 * gives Admin Head "Approve (Non-IT Items)" and CFO "Approve ≥₹10L" as
 * conditional grants, not a flat 4-level ladder. See buildChain() for the
 * exact synthesis (including a documented judgment call on what "SG —
 * where applicable" means, since the deck doesn't spell that out).
 */
class PurchaseOrderController extends Controller
{
    /** ₹10,00,000 — the deck's high-value DoA threshold (matches PurchaseOrder::isHighValue()). */
    private const HIGH_VALUE_THRESHOLD = 1_000_000;

    public function index(): View
    {
        $pos = PurchaseOrder::with(['vendor', 'purchaseRequisition.items', 'approvals', 'grns'])->latest()->get();

        return view('pages.po', [
            'pos' => $pos,
            'canCreate' => in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function create(): View
    {
        abort_unless(in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head creates Purchase Orders.');

        $requisitions = PurchaseRequisition::where('status', 'approved')
            ->whereDoesntHave('purchaseOrders')
            ->with(['items', 'rfq.costComparativeStatement.quotes.vendor', 'rfqs.costComparativeStatement.quotes.vendor'])
            ->get();

        $vendors = Vendor::empanelled()->orderBy('company_name')->get();

        return view('pages.po-create', [
            'requisitions' => $requisitions,
            'vendors' => $vendors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head creates Purchase Orders.');

        $data = $request->validate([
            'purchase_requisition_id' => ['required', 'exists:purchase_requisitions,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'amount_incl_gst' => ['nullable', 'numeric', 'min:0'],
            'delivery_date' => ['nullable', 'date'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'terms_and_conditions' => ['nullable', 'string'],
        ]);

        $pr = PurchaseRequisition::with(['items', 'rfqs.costComparativeStatement.quotes', 'rfq.costComparativeStatement'])->findOrFail($data['purchase_requisition_id']);
        abort_unless($pr->status === 'approved', 422, 'A PO can only be raised from an approved Purchase Requisition.');
        abort_if($pr->purchaseOrders()->exists(), 422, 'This PR already has a Purchase Order.');

        $vendor = Vendor::findOrFail($data['vendor_id']);
        $matchedCcs = $pr->rfqs
            ->map->costComparativeStatement
            ->filter()
            ->first(fn ($ccs) => (int) $ccs->selected_vendor_id === (int) $vendor->id || $ccs->quotes->contains('vendor_id', $vendor->id))
            ?? $pr->rfq?->costComparativeStatement;

        $prReserved = round($pr->estimatedCostInclGst(true), 2);
        $amount = round(! empty($data['amount_incl_gst']) ? (float) $data['amount_incl_gst'] : $prReserved, 2);
        $delta = round($amount - $prReserved, 2);
        $budgetError = null;
        $alertWarning = null;
        $po = null;

        DB::transaction(function () use ($request, $data, $pr, $vendor, $matchedCcs, $amount, $delta, &$budgetError, &$alertWarning, &$po) {
            $budget = DepartmentBudget::lockOrCreate($pr->department);

            if ($delta > 0 && ! $budget->hasHeadroomFor($delta)) {
                $budgetError = 'PO amount (₹'.number_format($amount, 2).") exceeds PR estimate by ₹".number_format($delta, 2)." and surpasses {$pr->department}'s remaining {$budget->fiscal_year} budget (₹".number_format($budget->remainingAmount(), 2).').';

                return;
            }

            $po = PurchaseOrder::create([
                'po_number' => $this->nextPoNumber(),
                'purchase_requisition_id' => $pr->id,
                'cost_comparative_statement_id' => $matchedCcs?->id,
                'vendor_id' => $vendor->id,
                'po_qty' => max((int) $pr->items->sum('quantity'), 1),
                'delivery_date' => $data['delivery_date'] ?? now()->addDays(14)->toDateString(),
                'amount_incl_gst' => $amount,
                'status' => 'pending',
                'created_by' => $request->user()->id,
                'event_name' => $data['event_name'] ?? ($pr->department . ' Requisition Procurement'),
                'payment_terms' => $data['payment_terms'] ?? ($vendor->payment_terms ?: 'Within 30 days from date of verified invoice and physical verification of goods/services.'),
                'delivery_terms' => $data['delivery_terms'] ?? ('Delivery on or before ' . ($data['delivery_date'] ?? now()->addDays(14)->format('d M Y'))),
                'billing_address' => $data['billing_address'] ?? PurchaseOrder::defaultBillingAddress(),
                'delivery_address' => $data['delivery_address'] ?? ($vendor->address ?: 'ASSOCHAM Central Store, YMCA CCL Building, New Delhi – 110001'),
                'contact_person' => $data['contact_person'] ?? ($pr->creator?->name ?? 'Purchase Indentor'),
                'contact_number' => $data['contact_number'] ?? ($pr->creator?->email ?? ''),
                'terms_and_conditions' => ($request->user()->role === Roles::ADMIN_HEAD && !empty($data['terms_and_conditions']))
                    ? $data['terms_and_conditions']
                    : PurchaseOrder::defaultTermsAndConditions(),
            ]);

            foreach (array_values($this->buildChain($po, $pr)) as $i => $role) {
                $po->approvals()->create(['level' => $i + 1, 'role_required' => $role, 'status' => 'pending']);
            }

            if ($delta != 0.0) {
                $alertWarning = $budget->applyDelta($delta, "PO {$po->po_number}");
            }
        });

        if ($budgetError !== null) {
            return back()->withErrors(['budget' => $budgetError])->withInput();
        }

        $redirect = redirect()->route('po')
            ->with('status', "PO {$po->po_number} created for {$vendor->company_name} — routing for approval.");

        if ($alertWarning !== null) {
            $redirect->with('warning', $alertWarning);
        }

        return $redirect;
    }

    public function show(PurchaseOrder $po): View
    {
        $po->load([
            'vendor',
            'purchaseRequisition.items',
            'purchaseRequisition.creator',
            'costComparativeStatement.quotes',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'grns',
            'creator',
        ]);

        return view('pages.po-show', [
            'po' => $po,
            'pendingApproval' => $this->currentApprovalFor($po, request()->user()),
        ]);
    }

    public function approve(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $approval = $this->currentApprovalFor($po, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PO matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $next = $po->approvals()->where('status', 'pending')->orderBy('level')->first();
        $po->update(['status' => $next ? 'pending' : 'approved']);

        if (!$next) {
            $freshPo = $po->fresh();
            $this->getOrGeneratePdf($freshPo, true);
            $message = $freshPo->needs_regeneration
                ? "PO {$po->po_number} re-approved — you can now click Regenerate PO to issue the updated revision."
                : "PO {$po->po_number} is fully approved and issued to the vendor.";
        } else {
            $message = 'Approved. Now awaiting '.Roles::label($next->role_required).'.';
        }

        return back()->with('status', $message);
    }

    public function reject(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($po, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PO matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $po->update(['status' => 'rejected']);

        return back()->with('status', "PO {$po->po_number} rejected.");
    }

    public function edit(PurchaseOrder $po): View
    {
        abort_unless(in_array(request()->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head may edit Purchase Orders.');

        $po->load(['vendor', 'purchaseRequisition']);

        return view('pages.po-edit', ['po' => $po]);
    }

    public function update(Request $request, PurchaseOrder $po): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head may edit Purchase Orders.');

        $data = $request->validate([
            'delivery_date' => ['nullable', 'date'],
            'po_qty' => ['nullable', 'integer', 'min:1'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'terms_and_conditions' => ['nullable', 'string'],
        ]);

        $wasApproved = $po->status === 'approved';
        $wasRejected = $po->status === 'rejected';

        $updatePayload = [
            'delivery_date' => $data['delivery_date'] ?? $po->delivery_date,
            'po_qty' => $data['po_qty'] ?? $po->po_qty,
            'status' => 'pending',
        ];
        if ($wasApproved) {
            $updatePayload['needs_regeneration'] = true;
        }

        // Commercial parameters and legal terms & conditions can ONLY be edited by the Admin Head
        if ($request->user()->role === Roles::ADMIN_HEAD) {
            if (array_key_exists('event_name', $data)) $updatePayload['event_name'] = $data['event_name'];
            if (array_key_exists('payment_terms', $data)) $updatePayload['payment_terms'] = $data['payment_terms'];
            if (array_key_exists('delivery_terms', $data)) $updatePayload['delivery_terms'] = $data['delivery_terms'];
            if (array_key_exists('billing_address', $data)) $updatePayload['billing_address'] = $data['billing_address'];
            if (array_key_exists('delivery_address', $data)) $updatePayload['delivery_address'] = $data['delivery_address'];
            if (array_key_exists('contact_person', $data)) $updatePayload['contact_person'] = $data['contact_person'];
            if (array_key_exists('contact_number', $data)) $updatePayload['contact_number'] = $data['contact_number'];
            if (array_key_exists('terms_and_conditions', $data)) $updatePayload['terms_and_conditions'] = $data['terms_and_conditions'];
        }

        $po->update($updatePayload);
        $this->getOrGeneratePdf($po->fresh(), true);

        if ($wasApproved || $wasRejected || !$po->approvals()->where('status', 'pending')->exists()) {
            $this->resetApprovalsForReentry($po, $wasApproved);
        }

        if ($wasApproved) {
            $po->writeAuditEntry('updated', "PO {$po->po_number} edited post-approval — status reset to pending and routed for re-approval.");

            return redirect()->route('po.show', $po)
                ->with('status', "PO {$po->po_number} updated. Since it was previously approved, governance rules have reset it to pending for re-approval.");
        }

        $po->writeAuditEntry('updated', "PO {$po->po_number} details updated.");

        return redirect()->route('po.show', $po)->with('status', "PO {$po->po_number} updated successfully.");
    }

    /**
     * Dedicated in-place update for Terms and Conditions & Commercial Parameters
     */
    public function updateTerms(Request $request, PurchaseOrder $po)
    {
        // Governance Policy: Only the Admin Head is authorized to edit contractual legal terms and conditions
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head is authorized to edit contractual terms and conditions.');

        $data = $request->validate([
            'event_name' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'terms_and_conditions' => ['nullable', 'string'],
        ]);

        $wasApproved = $po->status === 'approved';
        $wasRejected = $po->status === 'rejected';

        if ($wasApproved || $wasRejected) {
            $data['status'] = 'pending';
            if ($wasApproved) {
                $data['needs_regeneration'] = true;
            }
        }

        $po->update($data);

        if ($wasApproved || $wasRejected) {
            $this->resetApprovalsForReentry($po, $wasApproved);
        }

        $po->writeAuditEntry(
            'updated',
            $wasApproved
                ? "PO {$po->po_number} terms edited post-approval — status reset to pending for re-approval."
                : "PO {$po->po_number} terms and conditions updated."
        );

        // Invalidate and pre-warm the optimized PDF cache
        $this->getOrGeneratePdf($po->fresh(), true);

        if ($request->wantsJson() || $request->ajax()) {
            $fresh = $po->fresh();
            return response()->json([
                'success' => true,
                'message' => $wasApproved
                    ? "Terms updated. PO {$po->po_number} has been reset to Pending for re-approval."
                    : "Terms and conditions for {$po->po_number} updated and official PO PDF regenerated successfully.",
                'po' => [
                    'event_name' => $fresh->event_name ?: ($fresh->purchaseRequisition->department . ' Requisition Procurement'),
                    'payment_terms' => $fresh->effective_payment_terms,
                    'delivery_terms' => $fresh->effective_delivery_terms,
                    'billing_address' => nl2br(e($fresh->effective_billing_address)),
                    'delivery_address' => nl2br(e($fresh->delivery_address ?: "THE ASSOCIATED CHAMBERS OF COMMERCE & INDUSTRY OF INDIA\nASSOCHAM Central Stores / Delhi Office, YMCA Building, New Delhi – 110001")),
                    'contact_person' => $fresh->contact_person ?: ($fresh->purchaseRequisition->creator?->name ?? 'Purchase Indentor'),
                    'contact_number' => $fresh->contact_number ?: ($fresh->purchaseRequisition->creator?->email ?? ''),
                    'terms_list' => $fresh->terms_list,
                ],
            ]);
        }

        return redirect()->route('po.show', $po)
            ->with(
                'status',
                $wasApproved
                    ? "Terms updated. Since {$po->po_number} was previously approved, its status has been reset to Pending for re-approval."
                    : "Terms and conditions for {$po->po_number} updated successfully."
            );
    }

    /**
     * Regenerate a re-approved Purchase Order after post-approval edits.
     */
    public function regenerate(Request $request, PurchaseOrder $po): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head may regenerate Purchase Orders.');
        abort_unless($po->status === 'approved', 422, 'Only an approved Purchase Order can be regenerated.');

        $nextRevision = ((int) $po->revision_number) + 1;
        $po->update([
            'needs_regeneration' => false,
            'revision_number' => $nextRevision,
            'regenerated_at' => now(),
        ]);

        $fresh = $po->fresh();
        $this->getOrGeneratePdf($fresh, true);
        $fresh->writeAuditEntry('updated', "PO {$fresh->po_number} regenerated (Rev {$nextRevision}) after re-approval.");

        return redirect()->route('po.show', $fresh)
            ->with('status', "PO {$fresh->po_number} regenerated successfully (Rev {$nextRevision}) and official PDF updated.");
    }

    /**
     * Reset existing approval steps to pending (or create the chain if none exist).
     */
    private function resetApprovalsForReentry(PurchaseOrder $po, bool $wasApproved): void
    {
        if ($po->approvals()->count() === 0) {
            $pr = $po->purchaseRequisition;
            foreach (array_values($this->buildChain($po, $pr)) as $i => $role) {
                $po->approvals()->create([
                    'level' => $i + 1,
                    'role_required' => $role,
                    'status' => 'pending',
                    'comments' => $wasApproved ? 'Reset to pending due to post-approval edit.' : null,
                ]);
            }
            return;
        }

        // Deck slide 13: "Edit-after-approval resets to pending"
        $po->approvals()->update([
            'status' => 'pending',
            'approver_id' => null,
            'acted_at' => null,
            'comments' => $wasApproved ? 'Reset to pending due to post-approval edit.' : null,
        ]);
    }

    /**
     * Optimized PDF generator with font subsetting, local asset inlining and disk caching
     */
    private function getOrGeneratePdf(PurchaseOrder $po, bool $forceRegenerate = false): string
    {
        $cacheDir = storage_path('app/po-cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $timestamp = $po->updated_at ? $po->updated_at->timestamp : time();
        $cachePath = "{$cacheDir}/PO-{$po->id}-{$timestamp}.pdf";

        if (!$forceRegenerate && file_exists($cachePath) && filesize($cachePath) > 0) {
            return $cachePath;
        }

        // Clean up previous cached revisions for this PO
        foreach (glob("{$cacheDir}/PO-{$po->id}-*.pdf") as $oldFile) {
            if ($oldFile !== $cachePath) {
                @unlink($oldFile);
            }
        }

        $po->loadMissing([
            'vendor',
            'purchaseRequisition.items',
            'purchaseRequisition.creator',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'creator',
        ]);

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'po' => $po,
            'items' => $po->purchaseRequisition->items ?? collect(),
        ])->setPaper('a4', 'portrait')
          ->setOptions([
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled' => false,
              'isFontSubsettingEnabled' => true,
              'defaultFont' => 'DejaVu Sans',
          ]);

        file_put_contents($cachePath, $pdf->output());

        return $cachePath;
    }

    /**
     * Download Approved PO PDF formatted per ASSOCHAM official Excel template
     */
    public function downloadPdf(Request $request, PurchaseOrder $po)
    {
        $force = $request->boolean('regenerate');
        $filePath = $this->getOrGeneratePdf($po, $force);

        return response()->download($filePath, "PO-{$po->po_number}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Preview PO PDF directly in browser
     */
    public function previewPdf(Request $request, PurchaseOrder $po)
    {
        $force = $request->boolean('regenerate');
        $filePath = $this->getOrGeneratePdf($po, $force);

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"PO-{$po->po_number}.pdf\"",
        ]);
    }

    public function cancel(Request $request, PurchaseOrder $po): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::BUYER, Roles::ADMIN_HEAD], true), 403, 'Only the Buyer or Admin Head may cancel Purchase Orders.');
        
        if ($po->grns()->exists()) {
            return redirect()->route('po.show', $po)->with('error', 'Cannot cancel a Purchase Order that already has Goods Receipt Notes logged against it.');
        }

        if ($po->status === 'cancelled') {
            return redirect()->route('po.show', $po)->with('error', 'This Purchase Order is already cancelled.');
        }

        DB::transaction(function () use ($request, $po) {
            $po->update(['status' => 'cancelled']);
            $po->approvals()->where('status', 'pending')->update([
                'status' => 'rejected',
                'comments' => 'PO cancelled by ' . $request->user()->name,
                'acted_at' => now(),
            ]);

            $dept = $po->purchaseRequisition?->department;
            if ($dept && (float) $po->amount_incl_gst > 0) {
                $budget = DepartmentBudget::where('department', $dept)
                    ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
                    ->lockForUpdate()
                    ->first();
                $budget?->applyDelta(-round((float) $po->amount_incl_gst, 2));
            }
        });

        $po->writeAuditEntry('cancelled', "PO {$po->po_number} cancelled and committed budget released.");

        return redirect()->route('po')->with('status', "PO {$po->po_number} has been cancelled and budget headroom released.");
    }

    /**
     * Synthesized from the deck's permission matrix (p.7) + the "Governance
     * Authority Matrix" reference screenshot (p.43, high-value procurement
     * requires "CFO budget clearing & SG sign-off"):
     *   - Admin Head reviews unless the PR's department is IT.
     *   - CFO + SG both approve when the PO is high-value (≥₹10L) — "SG
     *     where applicable" is read as "applicable at the same threshold
     *     CFO's approval kicks in", since the deck doesn't define "where
     *     applicable" more precisely.
     *   - Safety net: if department is IT AND the value is below the
     *     threshold, the rules above would leave zero approvers — that's
     *     never allowed to happen, so Admin Head is added as a fallback
     *     single reviewer rather than auto-issuing a PO with no sign-off.
     *
     * @return list<string> role slugs in approval order
     */
    private function buildChain(PurchaseOrder $po, PurchaseRequisition $pr): array
    {
        $steps = [];

        if ($pr->department !== 'IT') {
            $steps[] = Roles::ADMIN_HEAD;
        }

        if ((float) $po->amount_incl_gst >= self::HIGH_VALUE_THRESHOLD) {
            $steps[] = Roles::CFO;
            $steps[] = Roles::SG;
        }

        return $steps ?: [Roles::ADMIN_HEAD];
    }

    private function currentApprovalFor(PurchaseOrder $po, $user): ?Approval
    {
        return $po->currentApprovalFor($user);
    }

    private function nextPoNumber(): string
    {
        do {
            $number = 'PO-'.now()->format('Y').'-'.random_int(1000, 9999);
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }
}
