@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Orders</span>
  </a>

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <ul class="mb-0 ps-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left: PO Creation Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
          <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width: 44px; height: 44px; font-size: 1.25rem;">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div>
            <h2 class="h5 mb-1 fw-bold text-dark">Issue Purchase Order</h2>
            <div class="text-muted" style="font-size:13px;">Generate legally binding purchase order against an approved Purchase Requisition.</div>
          </div>
        </div>

        @if ($requisitions->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px;">No Approved Requisitions Awaiting PO</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              All current approved Purchase Requisitions have already been converted to Purchase Orders.
            </div>
            <a href="{{ route('requisition') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600;">
              <i class="bi bi-file-earmark-text"></i>
              <span>View Requisitions</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('po.store') }}">
            @csrf

            <!-- 1. Approved Purchase Requisition -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><i class="bi bi-file-earmark-check text-primary me-1"></i> Approved Purchase Requisition *</label>
              <select name="purchase_requisition_id" class="form-select" required id="prSelect" onchange="updatePrDefaults(true)">
                <option value="">Select approved PR…</option>
                @foreach ($requisitions as $pr)
                  @php
                    $est = $pr->estimatedCostInclGst();
                    $firstItem = $pr->items->first()?->item_name ?? 'Procurement';
                    $reqVendorId = request('vendor_id');
                    $ccs = $reqVendorId
                      ? ($pr->rfqs->map->costComparativeStatement->filter()->first(fn ($c) => (int) $c->selected_vendor_id === (int) $reqVendorId || $c->quotes->contains('vendor_id', (int) $reqVendorId)) ?? $pr->rfq?->costComparativeStatement)
                      : $pr->rfq?->costComparativeStatement;
                    $winningQuote = $reqVendorId
                      ? ($ccs?->quotes?->firstWhere('vendor_id', (int) $reqVendorId) ?? $ccs?->winningQuote())
                      : $ccs?->winningQuote();
                    $selectedVendorId = $winningQuote?->vendor_id ?? $ccs?->selected_vendor_id;
                    $selectedAmount = ($winningQuote && (float)$winningQuote->quoted_cost > 0) ? (float)$winningQuote->quoted_cost : $est;
                  @endphp
                  <option value="{{ $pr->id }}" 
                          data-amount="{{ $selectedAmount }}" 
                          data-vendor-id="{{ $selectedVendorId ?? '' }}" 
                          {{ (old('purchase_requisition_id', request('pr_id') ?? request('pr')) == $pr->id) ? 'selected' : '' }}>
                    {{ $pr->pr_number }} — {{ $pr->department }} ({{ $firstItem }}) — Est: ₹{{ number_format($selectedAmount, 2) }}
                  </option>
                @endforeach
              </select>
              <div class="text-muted mt-1" style="font-size:12px;">Selecting an approved PR locks the order to a sanctioned requirement.</div>
            </div>

            <!-- 2. Awarded Vendor -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><i class="bi bi-building-check text-primary me-1"></i> Awarded Vendor *</label>
              <select name="vendor_id" id="vendorSelect" class="form-select" required>
                <option value="">Select empanelled vendor…</option>
                @foreach ($vendors as $vendor)
                  <option value="{{ $vendor->id }}" {{ old('vendor_id', request('vendor_id')) == $vendor->id ? 'selected' : '' }}>
                    {{ $vendor->company_name }} — {{ $vendor->category ?: 'General' }} ({{ $vendor->vendor_code ?: 'V-'.$vendor->id }})
                  </option>
                @endforeach
              </select>
              <div class="text-muted mt-1" style="font-size:12px;">Only fully empanelled, verified suppliers are eligible to receive a PO.</div>
            </div>

            <!-- 3. Total Order Amount (GST Included) -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><i class="bi bi-currency-rupee text-primary me-1"></i> Total PO Amount (GST Included) *</label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" min="0" name="amount_incl_gst" id="amountInput" class="form-control" value="{{ old('amount_incl_gst') }}" required placeholder="e.g. 50000.00">
              </div>
              <div class="text-muted mt-1" style="font-size:12px;">Auto-defaults to PR estimated cost or enter negotiated amount.</div>
            </div>

            <!-- 4. Expected Delivery Target Date -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><i class="bi bi-calendar-event text-primary me-1"></i> Expected Delivery Target Date</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', now()->addDays(14)->toDateString()) }}">
              </div>
              <div class="text-muted mt-1" style="font-size:12px;">Defaults to 14 days from today if not specified.</div>
            </div>

            <!-- 5. Commercial Parameters & Terms (ASSOCHAM Format) -->
            <div class="card border rounded-3 mb-3 p-3 bg-light-subtle">
              <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                <div class="fw-bold text-dark" style="font-size: 13.5px;">
                  <i class="bi bi-file-earmark-text text-primary me-1"></i> Commercial Parameters &amp; PO Specifications
                </div>
                <span class="badge bg-white border text-secondary" style="font-size: 11px;">Client Approved Format</span>
              </div>

              <!-- Event Name -->
              <div class="mb-2.5">
                <label class="form-label small fw-semibold mb-1">Event Name / Project Reference</label>
                <input type="text" name="event_name" class="form-control form-control-sm" value="{{ old('event_name') }}" placeholder="e.g. Annual Convention / Summit / Project">
              </div>

              <div class="row g-2 mb-2.5">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1">Payment Terms</label>
                  <input type="text" name="payment_terms" class="form-control form-control-sm" value="{{ old('payment_terms', '100% Against Delivery / 30 Days from Invoice') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1">Delivery Terms</label>
                  <input type="text" name="delivery_terms" class="form-control form-control-sm" value="{{ old('delivery_terms', 'Door delivery within stipulated delivery schedule') }}">
                </div>
              </div>

              <!-- Delivery Address -->
              <div class="mb-2.5">
                <label class="form-label small fw-semibold mb-1">Delivery Address</label>
                <textarea name="delivery_address" class="form-control form-control-sm" rows="2">{{ old('delivery_address', "THE ASSOCIATED CHAMBERS OF COMMERCE & INDUSTRY OF INDIA\nASSOCHAM Central Stores / Delhi Office, YMCA Building, New Delhi – 110001") }}</textarea>
              </div>

              <!-- Contact Person & Number -->
              <div class="row g-2 mb-2.5">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1">Contact Person</label>
                  <input type="text" name="contact_person" class="form-control form-control-sm" value="{{ old('contact_person') }}" placeholder="Indentor name">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1">Contact Number / Email</label>
                  <input type="text" name="contact_number" class="form-control form-control-sm" value="{{ old('contact_number') }}" placeholder="Phone / email">
                </div>
              </div>

              <!-- Contractual Terms & Conditions -->
              @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                <div>
                  <label class="form-label small fw-semibold mb-1">Terms &amp; Conditions (Admin Head Editable)</label>
                  <textarea name="terms_and_conditions" class="form-control form-control-sm font-monospace" rows="6" style="font-size: 11.5px;">{{ old('terms_and_conditions', \App\Models\PurchaseOrder::defaultTermsAndConditions()) }}</textarea>
                  <div class="text-muted mt-1" style="font-size: 11px;">Pre-filled with ASSOCHAM 8 standard approved legal clauses.</div>
                </div>
              @else
                <div>
                  <label class="form-label small fw-semibold mb-1 text-secondary">Terms &amp; Conditions (Standard Master Copy — Locked)</label>
                  <div class="p-2.5 rounded bg-white border small text-muted font-monospace" style="max-height: 100px; overflow-y: auto; font-size: 11px; line-height: 1.4;">
                    {!! nl2br(e(\App\Models\PurchaseOrder::defaultTermsAndConditions())) !!}
                  </div>
                  <div class="text-muted mt-1 small" style="font-size: 11.5px;">
                    <i class="bi bi-shield-lock-fill me-1 text-primary"></i>Standard 8 approved legal clauses applied automatically. Contractual terms can only be amended by the Admin Head.
                  </div>
                </div>
              @endif
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('po') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
                <i class="bi bi-check2-circle"></i>
                <span>Generate Purchase Order</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Governance & Policy Guide (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <div class="card shadow-sm border-0 p-3">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <i class="bi bi-shield-check text-primary fs-5"></i>
            <span class="fw-bold text-dark" style="font-size: 14px;">PO Governance Standards</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Approved PR Anchored:</strong> Every purchase order must be traced back to an executive-approved PR.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Thresholds:</strong> POs under ₹10 Lakhs route to Admin Head; orders &ge; ₹10 Lakhs route to CFO &amp; SG signoff.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Inventory Receipt:</strong> Generating a PO unlocks partial or complete GRN receipts by the Store Exec.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
            <i class="bi bi-diagram-2 text-primary"></i>
            <span>Procurement Pipeline Stage</span>
          </div>
          <div class="text-muted">You are converting a sanctioned need into an executive legal commitment with the vendor.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'New Purchase Order' });

window.updatePrDefaults = function(forceUpdate = false) {
  const prSelect = document.getElementById('prSelect');
  const amountInput = document.getElementById('amountInput');
  const vendorSelect = document.getElementById('vendorSelect');
  if (prSelect) {
    const selectedOpt = prSelect.options[prSelect.selectedIndex];
    const amount = selectedOpt?.getAttribute('data-amount');
    const vendorId = selectedOpt?.getAttribute('data-vendor-id');

    if (amountInput && amount && (forceUpdate || !amountInput.value || amountInput.value == '0')) {
      amountInput.value = parseFloat(amount).toFixed(2);
    }
    if (vendorSelect && vendorId && (forceUpdate || !vendorSelect.value || vendorSelect.value == '')) {
      vendorSelect.value = vendorId;
    }
  }
};

if (document.getElementById('prSelect')?.value) {
  updatePrDefaults(false);
}
@endsection
