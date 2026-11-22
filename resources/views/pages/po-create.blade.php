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
    <!-- Left: PO Creation Form (col-lg-12) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-file-earmark-plus"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Issue Purchase Order</h1>
            </div>
          </div>
        </div>

        @if ($requisitions->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px;">No Approved Requisitions Awaiting PO</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              All current approved Purchase Requisitions have already been converted to Purchase Orders.
            </div>
            @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
              <a href="{{ route('requisition') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600;">
                <i class="bi bi-file-earmark-text"></i>
                <span>View Requisitions</span>
              </a>
            @else
              <a href="{{ route('po') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600;">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Purchase Orders</span>
              </a>
            @endif
          </div>
        @else
          <form method="POST" action="{{ route('po.store') }}">
            @csrf

            <!-- Section 1: Requisition & Vendor Details -->
            <div class="mb-4">
              <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-file-earmark-text"></i>
                </div>
                <h2 class="vendor-form-section-title mb-0">Requisition &amp; Vendor Allocation</h2>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="vendor-form-label">Approved Purchase Requisition <span class="text-danger">*</span></label>
                  <select name="purchase_requisition_id" class="form-select vendor-form-control" required id="prSelect" onchange="updatePrDefaults(true)">
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
                        $prEventRef = $pr->projectBudget?->project_name ?: ($pr->event_name ?: '');
                      @endphp
                      <option value="{{ $pr->id }}" 
                              data-amount="{{ $selectedAmount }}" 
                              data-vendor-id="{{ $selectedVendorId ?? '' }}" 
                              data-event="{{ $prEventRef }}"
                              {{ (old('purchase_requisition_id', request('purchase_requisition_id') ?? (request('pr_id') ?? request('pr'))) == $pr->id) ? 'selected' : '' }}>
                        {{ $pr->pr_number }} — {{ $pr->department }}@if($pr->title) · {{ $pr->title }}@elseif($prEventRef) · {{ $prEventRef }}@endif ({{ $firstItem }}) — Est: ₹{{ number_format($selectedAmount, 2) }}
                      </option>
                    @endforeach
                  </select>
                  <div class="text-muted mt-1" style="font-size:12px;">Locks order to a sanctioned requirement.</div>
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Awarded Vendor <span class="text-danger">*</span></label>
                  <select name="vendor_id" id="vendorSelect" class="form-select vendor-form-control" required>
                    <option value="">Select empanelled vendor…</option>
                    @foreach ($vendors as $vendor)
                      <option value="{{ $vendor->id }}" {{ old('vendor_id', request('vendor_id')) == $vendor->id ? 'selected' : '' }}>
                        {{ $vendor->company_name }} — {{ $vendor->category ?: 'General' }} ({{ $vendor->vendor_code ?: 'V-'.$vendor->id }})
                      </option>
                    @endforeach
                  </select>
                  <div class="text-muted mt-1" style="font-size:12px;">Only fully empanelled, verified suppliers are eligible.</div>
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Total PO Amount (GST Included) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted fw-bold" style="border: 1.5px solid #e2e8f0; border-right: none;">₹</span>
                    <input type="number" step="0.01" min="0" name="amount_incl_gst" id="amountInput" class="form-control vendor-form-control" value="{{ old('amount_incl_gst') }}" required placeholder="e.g. 50000.00" style="border-left: none;">
                  </div>
                  <div class="text-muted mt-1" style="font-size:12px;">Auto-defaults from winning quotation or negotiated amount.</div>
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Expected Delivery Target Date</label>
                  <input type="date" name="delivery_date" class="form-control vendor-form-control" value="{{ old('delivery_date', now()->addDays(14)->toDateString()) }}">
                  <div class="text-muted mt-1" style="font-size:12px;">Defaults to 14 days from today if not specified.</div>
                </div>
              </div>
            </div>

            <!-- Section 1.5: Item-Wise Vendor Assignment -->
            <div class="mb-4">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="vendor-form-icon-box">
                    <i class="bi bi-diagram-3-fill"></i>
                  </div>
                  <div>
                    <h2 class="vendor-form-section-title mb-0">Item-Wise Vendor Assignment</h2>
                    <div class="text-secondary small mt-0.5">Assign different vendors to individual items. The system will automatically generate separate POs for each respective vendor.</div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" onclick="applyMainVendorToAllItems()">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Apply Awarded Vendor to All</span>
                  </button>
                </div>
              </div>

              @foreach ($requisitions as $pr)
                <div class="pr-items-container" id="prItemsSection-{{ $pr->id }}" style="display: none;">
                  @if ($pr->items->isEmpty())
                    <div class="text-muted small p-3 bg-light rounded border text-center">
                      No line items recorded for this requisition. Default vendor assignment will apply.
                    </div>
                  @else
                    <div class="table-responsive border rounded bg-white">
                      <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3" style="width: 5%;">#</th>
                            <th style="width: 30%;">Item Details</th>
                            <th style="width: 12%;">Type</th>
                            <th class="text-center" style="width: 12%;">Qty &amp; Unit</th>
                            <th class="text-end" style="width: 13%;">Line Total (₹)</th>
                            <th style="width: 28%;" class="pe-3">Assigned Vendor <span class="text-danger">*</span></th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($pr->items as $idx => $item)
                            @php
                              $isAlreadyAssigned = !empty($item->purchase_order_id);
                              $assignedPo = $isAlreadyAssigned ? ($item->purchaseOrder ?? \App\Models\PurchaseOrder::find($item->purchase_order_id)) : null;
                            @endphp
                            <tr class="{{ $isAlreadyAssigned ? 'table-light opacity-75' : '' }}">
                              <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                              <td>
                                <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                                @if ($item->description)
                                  <div class="text-muted small text-truncate" style="max-width: 260px;" title="{{ $item->description }}">{{ $item->description }}</div>
                                @endif
                              </td>
                              <td>
                                <span class="badge {{ $item->isCopex() ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }} border" style="font-size: 10.5px;">
                                  {{ $item->isCopex() ? 'CapEx' : ($item->expense_type === 'opex' ? 'OpEx' : 'General') }}
                                </span>
                              </td>
                              <td class="text-center">
                                <span class="fw-semibold font-monospace">{{ $item->quantity }}</span> {{ $item->unit ?: 'Unit' }}
                              </td>
                              <td class="text-end font-monospace fw-semibold text-dark">
                                ₹{{ number_format($item->lineTotalInclGst(), 2) }}
                              </td>
                              <td class="pe-3">
                                @if ($isAlreadyAssigned)
                                  <span class="badge bg-light text-secondary border px-2 py-1.5 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Ordered: <strong>{{ $assignedPo?->po_number ?? 'PO #' . $item->purchase_order_id }}</strong> ({{ $item->vendor?->company_name ?? 'Awarded' }})</span>
                                  </span>
                                @else
                                  <select name="item_vendors[{{ $item->id }}]" 
                                          class="form-select form-select-sm item-vendor-select pr-item-vendor-{{ $pr->id }}" 
                                          data-pr-id="{{ $pr->id }}"
                                          data-item-id="{{ $item->id }}" 
                                          data-line-total="{{ $item->lineTotalInclGst() }}" 
                                          onchange="onItemVendorSelectionChanged({{ $pr->id }})">
                                    <option value="">Select Vendor…</option>
                                    @foreach ($vendors as $v)
                                      <option value="{{ $v->id }}" {{ (old("item_vendors.{$item->id}") == $v->id) ? 'selected' : '' }}>
                                        {{ $v->company_name }} ({{ $v->category ?: 'General' }})
                                      </option>
                                    @endforeach
                                  </select>
                                @endif
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>

                    {{-- Dynamic Multi-PO Generation Indicator --}}
                    <div class="alert alert-info py-2.5 px-3 mt-2 mb-0 border-0 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2" id="prItemSplitAlert-{{ $pr->id }}" style="background: #fdf2f4; border-left: 4px solid #c8102e !important; border-radius: 6px;">
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5" style="color: #c8102e;"></i>
                        <div id="prItemSplitSummary-{{ $pr->id }}" style="font-size: 13px;">
                          All items assigned to the same vendor will generate <strong>1 Purchase Order</strong>.
                        </div>
                      </div>
                      <span class="badge text-white text-nowrap" style="background: #c8102e;" id="prItemSplitBadge-{{ $pr->id }}">1 PO</span>
                    </div>
                  @endif
                </div>
              @endforeach
            </div>

            <!-- Section 2: Commercial Parameters & Delivery Logistics -->
            <div class="mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="vendor-form-icon-box">
                    <i class="bi bi-truck"></i>
                  </div>
                  <h2 class="vendor-form-section-title mb-0">Commercial Parameters &amp; Delivery Logistics</h2>
                </div>
                <span class="badge bg-light text-secondary border" style="font-size: 11px;">Client Approved Format</span>
              </div>

              <div class="row g-3">
                <div class="col-md-12">
                  <label class="vendor-form-label">Event Name / Project Reference <span class="text-muted fw-normal">(Optional)</span></label>
                  <input type="text" name="event_name" class="form-control vendor-form-control" value="{{ old('event_name') }}" placeholder="e.g. Annual Convention / Summit / Project">
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Payment Terms</label>
                  <input type="text" name="payment_terms" class="form-control vendor-form-control" value="{{ old('payment_terms', '100% Against Delivery / 30 Days from Invoice') }}">
                </div>
                <div class="col-md-6">
                  <label class="vendor-form-label">Delivery Terms</label>
                  <input type="text" name="delivery_terms" class="form-control vendor-form-control" value="{{ old('delivery_terms', 'Door delivery within stipulated delivery schedule') }}">
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Contact Person (Indentor)</label>
                  <input type="text" name="contact_person" class="form-control vendor-form-control" value="{{ old('contact_person') }}" placeholder="e.g. Indentor name">
                </div>
                <div class="col-md-6">
                  <label class="vendor-form-label">Contact Phone / Email</label>
                  <input type="text" name="contact_number" class="form-control vendor-form-control" value="{{ old('contact_number') }}" placeholder="e.g. +91 98765 43210 / email@assocham.com">
                </div>

                <div class="col-md-12">
                  <label class="vendor-form-label">Delivery Address</label>
                  <textarea name="delivery_address" class="form-control vendor-form-control" rows="2">{{ old('delivery_address', "THE ASSOCIATED CHAMBERS OF COMMERCE & INDUSTRY OF INDIA\nASSOCHAM Central Stores / Delhi Office, YMCA Building, New Delhi – 110001") }}</textarea>
                </div>
              </div>
            </div>

            <!-- Section 3: Legal Terms & Standard Clauses -->
            <div class="mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="vendor-form-icon-box">
                    <i class="bi bi-shield-check"></i>
                  </div>
                  <h2 class="vendor-form-section-title mb-0">Terms &amp; Conditions (Contractual Clauses)</h2>
                </div>
                @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 11px;">Admin Head Editable</span>
                @else
                  <span class="badge bg-light text-secondary border" style="font-size: 11px;">Standard Master Copy</span>
                @endif
              </div>

              @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                <div>
                  <textarea name="terms_and_conditions" class="form-control vendor-form-control font-monospace" rows="7" style="font-size: 13px; line-height: 1.5;">{{ old('terms_and_conditions', \App\Models\PurchaseOrder::defaultTermsAndConditions()) }}</textarea>
                  <div class="text-muted mt-1.5" style="font-size: 12px;">Pre-filled with ASSOCHAM 8 standard approved legal clauses. Modifications apply specifically to this PO.</div>
                </div>
              @else
                <div>
                  <div class="p-3 rounded-3 bg-light border font-monospace text-secondary" style="max-height: 140px; overflow-y: auto; font-size: 12px; line-height: 1.5; border-color: #e2e8f0 !important;">
                    {!! nl2br(e(\App\Models\PurchaseOrder::defaultTermsAndConditions())) !!}
                  </div>
                  <div class="text-muted mt-1.5" style="font-size: 12px;">
                    Standard 8 approved legal clauses apply automatically. Contractual amendments are reserved for the Admin Head.
                  </div>
                </div>
              @endif
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
              <a href="{{ route('po') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
              <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
                Generate Purchase Order
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'New Purchase Order' });

window.applyMainVendorToAllItems = function() {
  const prSelect = document.getElementById('prSelect');
  const vendorSelect = document.getElementById('vendorSelect');
  if (!prSelect || !vendorSelect || !vendorSelect.value) return;

  const prId = prSelect.value;
  const mainVendorId = vendorSelect.value;
  const dropdowns = document.querySelectorAll(`.pr-item-vendor-${prId}`);
  dropdowns.forEach(dd => {
    dd.value = mainVendorId;
  });
  onItemVendorSelectionChanged(prId);
};

window.onItemVendorSelectionChanged = function(prId) {
  const dropdowns = document.querySelectorAll(`.pr-item-vendor-${prId}`);
  const summaryEl = document.getElementById(`prItemSplitSummary-${prId}`);
  const badgeEl = document.getElementById(`prItemSplitBadge-${prId}`);
  const amountInput = document.getElementById('amountInput');
  const alertEl = document.getElementById(`prItemSplitAlert-${prId}`);

  if (!dropdowns.length || !summaryEl || !badgeEl) return;

  const vendorMap = {};
  let totalSelectedAmount = 0;

  dropdowns.forEach(dd => {
    const vId = dd.value;
    const lineTotal = parseFloat(dd.getAttribute('data-line-total') || '0');
    totalSelectedAmount += lineTotal;
    if (vId) {
      const vName = dd.options[dd.selectedIndex]?.text?.split('(')[0]?.trim() || `Vendor #${vId}`;
      if (!vendorMap[vId]) {
        vendorMap[vId] = { name: vName, count: 0, amount: 0 };
      }
      vendorMap[vId].count++;
      vendorMap[vId].amount += lineTotal;
    }
  });

  const distinctVendors = Object.keys(vendorMap);
  const vendorCount = distinctVendors.length;

  if (vendorCount <= 1) {
    const singleV = distinctVendors[0] ? vendorMap[distinctVendors[0]] : null;
    badgeEl.textContent = '1 PO';
    badgeEl.className = 'badge text-white text-nowrap';
    badgeEl.style.backgroundColor = '#c8102e';
    if (alertEl) {
      alertEl.style.borderLeftColor = '#c8102e';
      alertEl.style.background = '#fdf2f4';
    }
    if (singleV) {
      summaryEl.innerHTML = `All items assigned to <strong>${singleV.name}</strong> will be combined into <strong>1 Purchase Order</strong> for <strong>₹${totalSelectedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>.`;
    } else {
      summaryEl.innerHTML = 'Select vendors for the line items above or use the main Awarded Vendor.';
    }
  } else {
    badgeEl.textContent = `${vendorCount} Separate POs`;
    badgeEl.className = 'badge bg-success text-white text-nowrap';
    if (alertEl) {
      alertEl.style.borderLeftColor = '#10b981';
      alertEl.style.background = '#ecfdf5';
    }
    const breakdown = distinctVendors.map((vId, idx) => {
      const v = vendorMap[vId];
      return `<strong>PO ${idx + 1} (${v.name}):</strong> ${v.count} item(s) — ₹${v.amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }).join(' &nbsp;|&nbsp; ');

    summaryEl.innerHTML = `<strong>Item-Wise Split Active (${vendorCount} Vendors):</strong> The system will automatically generate <strong>${vendorCount} separate Purchase Orders</strong>:<br><span class="text-dark small">${breakdown}</span>`;
  }

  if (amountInput && totalSelectedAmount > 0) {
    amountInput.value = totalSelectedAmount.toFixed(2);
  }
};

window.updatePrDefaults = function(forceUpdate = false) {
  const prSelect = document.getElementById('prSelect');
  const amountInput = document.getElementById('amountInput');
  const vendorSelect = document.getElementById('vendorSelect');
  const eventInput = document.querySelector('input[name="event_name"]');

  // Hide all PR item sections
  document.querySelectorAll('.pr-items-container').forEach(c => c.style.display = 'none');

  if (prSelect && prSelect.value) {
    const selectedOpt = prSelect.options[prSelect.selectedIndex];
    const prId = prSelect.value;
    const amount = selectedOpt?.getAttribute('data-amount');
    const vendorId = selectedOpt?.getAttribute('data-vendor-id');
    const eventName = selectedOpt?.getAttribute('data-event') || '';

    // Show selected PR items container
    const activeSection = document.getElementById(`prItemsSection-${prId}`);
    if (activeSection) {
      activeSection.style.display = 'block';
    }

    if (vendorSelect && vendorId && (forceUpdate || !vendorSelect.value || vendorSelect.value == '')) {
      vendorSelect.value = vendorId;
    }

    // Default item dropdowns to selected vendor if empty
    const currentVendor = vendorSelect ? vendorSelect.value : vendorId;
    if (currentVendor) {
      document.querySelectorAll(`.pr-item-vendor-${prId}`).forEach(dd => {
        if (!dd.value || forceUpdate) {
          dd.value = currentVendor;
        }
      });
    }

    onItemVendorSelectionChanged(prId);

    if (amountInput && amount && (forceUpdate || !amountInput.value || amountInput.value == '0')) {
      amountInput.value = parseFloat(amount).toFixed(2);
    }
    if (eventInput && eventName && (forceUpdate || !eventInput.value)) {
      eventInput.value = eventName;
    }
  }
};

document.getElementById('vendorSelect')?.addEventListener('change', function() {
  const prSelect = document.getElementById('prSelect');
  if (prSelect && prSelect.value) {
    const prId = prSelect.value;
    const selectedVendor = this.value;
    if (selectedVendor) {
      document.querySelectorAll(`.pr-item-vendor-${prId}`).forEach(dd => {
        if (!dd.value) {
          dd.value = selectedVendor;
        }
      });
      onItemVendorSelectionChanged(prId);
    }
  }
});

if (document.getElementById('prSelect')?.value) {
  updatePrDefaults(false);
}
@endsection
