<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RFQ Creation — third Phase 2 module (see project memory:
 * erp-laravel-phase2-procurement). Simpler than Vendor Empanelment/PR: no
 * multi-level approval chain in the deck (p.11) — just "locked to an
 * approved PR" + multi-vendor dispatch, then it feeds Cost Comparative
 * Statement (not built yet) once quotes are back.
 */
class RfqController extends Controller
{
    public function index(): View
    {
        $rfqs = Rfq::with(['purchaseRequisition', 'rfqVendors.vendor'])->latest()->get();

        return view('pages.rfq', [
            'rfqs' => $rfqs,
            'canCreate' => request()->user()->role === Roles::BUYER,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::BUYER, 403, 'Only the Buyer creates RFQs.');

        return view('pages.rfq-create', [
            'purchaseRequisitions' => PurchaseRequisition::where('status', 'approved')->orderBy('pr_number')->get(),
            'vendors' => Vendor::empanelled()->orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::BUYER, 403, 'Only the Buyer creates RFQs.');

        $data = $request->validate([
            'purchase_requisition_id' => ['required', 'exists:purchase_requisitions,id'],
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['exists:vendors,id'],
        ]);

        $pr = PurchaseRequisition::findOrFail($data['purchase_requisition_id']);
        abort_unless($pr->status === 'approved', 422, 'RFQs can only be raised against an approved PR.');

        $rfq = Rfq::create([
            'rfq_number' => $this->nextRfqNumber(),
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'poc_user_id' => $request->user()->id,
            'status' => 'dispatched',
        ]);

        foreach ($data['vendor_ids'] as $vendorId) {
            $rfq->rfqVendors()->create(['vendor_id' => $vendorId, 'response_status' => 'awaiting']);
        }

        return redirect()->route('rfq')
            ->with('status', "RFQ {$rfq->rfq_number} dispatched to ".count($data['vendor_ids']).' vendor(s).');
    }

    public function show(Rfq $rfq): View
    {
        $rfq->load(['purchaseRequisition.items', 'rfqVendors.vendor', 'costComparativeStatement']);

        return view('pages.rfq-show', [
            'rfq' => $rfq,
            'canManage' => request()->user()->role === Roles::BUYER,
        ]);
    }

    public function markQuotesReceived(Rfq $rfq): RedirectResponse
    {
        abort_unless(request()->user()->role === Roles::BUYER, 403, 'Only the Buyer manages RFQs.');
        abort_unless($rfq->status === 'dispatched', 422, 'Only a dispatched RFQ can be marked as having quotes received.');

        $rfq->update(['status' => 'quotes_received']);

        return back()->with('status', "RFQ {$rfq->rfq_number} marked quotes received — ready for a Cost Comparative Statement.");
    }

    private function nextRfqNumber(): string
    {
        do {
            $number = 'RFQ-'.random_int(1000, 9999);
        } while (Rfq::where('rfq_number', $number)->exists());

        return $number;
    }
}
