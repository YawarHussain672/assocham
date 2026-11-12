@extends('layouts.app')

@section('title', 'Log Goods Receipt')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grn') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Goods Receipts</span>
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
    <!-- Left: GRN Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-box-seam"></i>
            </div>
            <div>
              <h2 class="h5 mb-0 fw-bold text-dark">Log Inward Goods Receipt (GRN)</h2>
              <div class="text-muted" style="font-size:13px;">Record physical delivery against an issued Purchase Order and originating Requisition.</div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:11px; font-weight:600; letter-spacing:0.3px;">GOODS ENTRY</span>
        </div>

        @if ($purchaseOrders->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px;">No Pending Deliveries Awaiting Receipt</div>
            <div class="text-muted mt-1 small" style="max-width:480px; margin:0 auto;">
              All issued Purchase Orders have either been fully delivered or are awaiting approval.
            </div>
            <a href="{{ route('po') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 fw-semibold">
              <i class="bi bi-receipt"></i>
              <span>View Purchase Orders</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('grn.store') }}">
            @csrf

            @php
              $initialPoId = old('purchase_order_id', $selectedPoId ?? (request('po_id') ?? request('po')));
              $initialPrId = old('purchase_requisition_id', $selectedPrId ?? request('pr_id'));
              if (!$initialPoId && $purchaseOrders->count() === 1) {
                $initialPoId = $purchaseOrders->first()->id;
                $initialPrId = $purchaseOrders->first()->purchase_requisition_id;
              }
            @endphp

            <!-- 1. Target Purchase Order (Primary) -->
            <div class="mb-3">
              <label class="form-label fw-semibold text-dark d-flex align-items-center justify-content-between mb-1.5">
                <span><i class="bi bi-receipt text-primary me-1"></i> Target Purchase Order (PO) <span class="text-danger">*</span></span>
                <span class="text-muted fw-normal" style="font-size:11.5px;">Select approved &amp; issued PO</span>
              </label>
              <select name="purchase_order_id" id="grnPoSelect" class="form-select" required style="font-size:13px; text-overflow:ellipsis;">
                <option value="">Select approved PO…</option>
                @foreach ($purchaseOrders as $po)
                  @php
                    $pr = $po->purchaseRequisition;
                    $itemsList = $pr?->items?->pluck('item_name')->implode(', ') ?: '—';
                  @endphp
                  <option value="{{ $po->id }}"
                          data-pr-id="{{ $po->purchase_requisition_id }}"
                          data-pr-number="{{ $pr?->pr_number ?? '—' }}"
                          data-pr-title="{{ $pr?->title ?: ($pr?->department . ' Requisition') }}"
                          data-pr-url="{{ $pr ? route('requisition.show', $pr) : '#' }}"
                          data-po-number="{{ $po->po_number }}"
                          data-po-url="{{ route('po.show', $po) }}"
                          data-dept="{{ $pr?->department ?? 'General' }}"
                          data-vendor="{{ $po->vendor->company_name }}"
                          data-ordered="{{ $po->po_qty }}"
                          data-received="{{ $po->receivedQty() }}"
                          data-remaining="{{ $po->remainingQtyToReceive() }}"
                          data-items="{{ $itemsList }}"
                          {{ (string) $initialPoId === (string) $po->id ? 'selected' : '' }}>
                    {{ $po->po_number }} &middot; {{ $po->vendor->company_name }} &middot; ({{ $po->remainingQtyToReceive() }}/{{ $po->po_qty }} remaining)
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 2. Originating Purchase Requisition (Auto-Linked) -->
            <div class="mb-3">
              <label class="form-label fw-semibold text-dark d-flex align-items-center justify-content-between mb-1.5">
                <span><i class="bi bi-file-earmark-text text-primary me-1"></i> Originating Purchase Requisition (PR)</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10.5px; font-weight:500;">Auto-Linked</span>
              </label>
              <select name="purchase_requisition_id" id="grnPrSelect" class="form-select" style="font-size:13px; text-overflow:ellipsis;">
                <option value="">Select or auto-link PR…</option>
                @foreach ($purchaseRequisitions as $pr)
                  <option value="{{ $pr->id }}" {{ (string) $initialPrId === (string) $pr->id ? 'selected' : '' }}>
                    {{ $pr->pr_number }} &middot; {{ $pr->title ?: ($pr->department . ' Requisition') }} ({{ $pr->department }})
                  </option>
                @endforeach
              </select>
              <div class="text-muted mt-1" style="font-size:11.5px;">Auto-populates when PO is selected, or pick PR to auto-select the matching PO.</div>
            </div>

            <!-- Minimal Context Summary Card -->
            <div id="grnLinkedSummaryCard" class="card border rounded-3 p-3 mb-4 bg-light-subtle d-none" style="border-color:#e2e8f0 !important;">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom" style="border-color:#e2e8f0 !important;">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary text-white px-2 py-1" style="font-size:10px; letter-spacing:0.4px;">LINKED REFERENCE</span>
                  <span class="fw-semibold text-dark" style="font-size:12.5px;">Order &amp; Requisition Details</span>
                </div>
                <span class="badge bg-white text-secondary border font-monospace" id="grnSummaryQtyBadge" style="font-size:11px; font-weight:600;"></span>
              </div>
              <div class="row g-2" style="font-size:12.5px;">
                <div class="col-12 col-sm-4">
                  <div class="text-muted" style="font-size:11px;">Purchase Order</div>
                  <a href="#" id="grnSummaryPoLink" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none"></a>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-muted" style="font-size:11px;">Originating PR</div>
                  <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                    <a href="#" id="grnSummaryPrLink" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none"></a>
                    <span class="badge bg-light text-secondary border" id="grnSummaryDeptBadge" style="font-size:10px;"></span>
                  </div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-muted" style="font-size:11px;">Contracted Vendor</div>
                  <div class="fw-semibold text-dark text-truncate" id="grnSummaryVendor"></div>
                </div>
                <div class="col-12 pt-1 border-top" style="border-color:#edf2f7 !important;">
                  <span class="text-muted" style="font-size:11px;">Line Items:</span>
                  <span class="text-dark fw-medium ms-1" id="grnSummaryItems"></span>
                </div>
              </div>
            </div>

            <!-- Inward Receipt Fields -->
            <div class="row g-3 mb-3">
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1.5" for="grnReceivedQtyInput">Received Quantity <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-box"></i></span>
                  <input type="number" name="received_qty" id="grnReceivedQtyInput" class="form-control" min="1" value="{{ old('received_qty', 1) }}" required>
                </div>
                <div class="form-text text-muted" style="font-size:11.5px;">Max: balance remaining on PO.</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1.5" for="grnInvoiceInput">Invoice Number</label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-receipt"></i></span>
                  <input type="text" name="invoice_number" id="grnInvoiceInput" class="form-control" placeholder="e.g. INV-9082" value="{{ old('invoice_number') }}">
                </div>
                <div class="form-text text-muted" style="font-size:11.5px;">Vendor invoice or challan number.</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1.5" for="grnReceiptDateInput">Inspection Date <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar3"></i></span>
                  <input type="date" name="receipt_date" id="grnReceiptDateInput" class="form-control" value="{{ old('receipt_date', now()->toDateString()) }}" required>
                </div>
                <div class="form-text text-muted" style="font-size:11.5px;">Physical inspection date.</div>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold text-dark mb-1.5" for="grnQualityRemarksInput">Quality Remarks</label>
                <textarea name="quality_remarks" id="grnQualityRemarksInput" class="form-control" rows="2" placeholder="e.g. Packages received in sealed condition. Physical count and quality verified.">{{ old('quality_remarks') }}</textarea>
                <div class="form-text text-muted" style="font-size:11.5px;">Record physical condition, damage check, and inspection notes.</div>
              </div>
            </div>

            <div class="pt-3 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('grn') }}" class="btn btn-light border px-4 fw-semibold text-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
                <i class="bi bi-check2-circle"></i>
                <span>Submit for HOD Verification</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Receipt Protocols & Inventory Guidance (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <div class="card shadow-sm border-0 p-3">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <i class="bi bi-shield-check text-primary fs-5"></i>
            <span class="fw-bold text-dark" style="font-size: 14px;">Receiving Protocols</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Partial Receipts:</strong> Multiple GRNs can be logged against a single PO until the total ordered quantity is exhausted.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Mandatory HOD Signoff:</strong> Originating Department Head must inspect quality and approve this GRN before vendor payment release.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Invoice &amp; Challan:</strong> Enter physical delivery challan or invoice number for seamless accounts reconciliation.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Inventory Accountancy:</strong> Approval confirms asset entry into ASSOCHAM inventory and marks the PO balance.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
            <i class="bi bi-diagram-2 text-primary"></i>
            <span>Procurement Pipeline Stage</span>
          </div>
          <div class="text-muted">You are verifying physical delivery against a sanctioned PO and creating the inward asset record for HOD signoff.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Log Goods Receipt' });

(function() {
  const poSelect = document.getElementById('grnPoSelect');
  const prSelect = document.getElementById('grnPrSelect');
  const qtyInput = document.getElementById('grnReceivedQtyInput');
  const summaryCard = document.getElementById('grnLinkedSummaryCard');

  if (!poSelect || !prSelect) return;

  function syncSummaryFromPo() {
    const opt = poSelect.options[poSelect.selectedIndex];
    if (!opt || !opt.value) {
      if (summaryCard) summaryCard.classList.add('d-none');
      if (qtyInput) qtyInput.removeAttribute('max');
      return;
    }

    const prId = opt.dataset.prId || '';
    if (prId && prSelect.value !== prId) {
      prSelect.value = prId;
    }

    if (summaryCard) {
      summaryCard.classList.remove('d-none');
      document.getElementById('grnSummaryPoLink').textContent = opt.dataset.poNumber || '';
      document.getElementById('grnSummaryPoLink').href = opt.dataset.poUrl || '#';
      document.getElementById('grnSummaryPrLink').textContent = opt.dataset.prNumber || '';
      document.getElementById('grnSummaryPrLink').href = opt.dataset.prUrl || '#';
      document.getElementById('grnSummaryDeptBadge').textContent = opt.dataset.dept || '';
      document.getElementById('grnSummaryVendor').textContent = opt.dataset.vendor || '';
      document.getElementById('grnSummaryItems').textContent = opt.dataset.items || '—';
      document.getElementById('grnSummaryQtyBadge').textContent =
        `Recv ${opt.dataset.received} / ${opt.dataset.ordered} (${opt.dataset.remaining} remaining)`;
    }

    const remaining = parseInt(opt.dataset.remaining || '0', 10);
    if (qtyInput && remaining > 0) {
      qtyInput.max = remaining;
      if (parseInt(qtyInput.value || '0', 10) > remaining) {
        qtyInput.value = remaining;
      }
    }
  }

  poSelect.addEventListener('change', syncSummaryFromPo);

  prSelect.addEventListener('change', function() {
    const selectedPr = prSelect.value;
    if (!selectedPr) return;

    const matchingOption = Array.from(poSelect.options).find(
      o => o.value && o.dataset.prId === selectedPr
    );
    if (matchingOption) {
      poSelect.value = matchingOption.value;
      syncSummaryFromPo();
    }
  });

  syncSummaryFromPo();
})();
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Log Goods Receipt' });

(function() {
  const poSelect = document.getElementById('grnPoSelect');
  const prSelect = document.getElementById('grnPrSelect');
  const qtyInput = document.getElementById('grnReceivedQtyInput');
  const summaryCard = document.getElementById('grnLinkedSummaryCard');

  if (!poSelect || !prSelect) return;

  function syncSummaryFromPo() {
    const opt = poSelect.options[poSelect.selectedIndex];
    if (!opt || !opt.value) {
      if (summaryCard) summaryCard.classList.add('d-none');
      if (qtyInput) qtyInput.removeAttribute('max');
      return;
    }

    const prId = opt.dataset.prId || '';
    if (prId && prSelect.value !== prId) {
      prSelect.value = prId;
    }

    if (summaryCard) {
      summaryCard.classList.remove('d-none');
      document.getElementById('grnSummaryPoLink').textContent = opt.dataset.poNumber || '';
      document.getElementById('grnSummaryPoLink').href = opt.dataset.poUrl || '#';
      document.getElementById('grnSummaryPrLink').textContent = opt.dataset.prNumber || '';
      document.getElementById('grnSummaryPrLink').href = opt.dataset.prUrl || '#';
      document.getElementById('grnSummaryDeptBadge').textContent = opt.dataset.dept || '';
      document.getElementById('grnSummaryVendor').textContent = opt.dataset.vendor || '';
      document.getElementById('grnSummaryItems').textContent = opt.dataset.items || '—';
      document.getElementById('grnSummaryQtyBadge').textContent =
        `Recv ${opt.dataset.received} / ${opt.dataset.ordered} (${opt.dataset.remaining} remaining)`;
    }

    const remaining = parseInt(opt.dataset.remaining || '0', 10);
    if (qtyInput && remaining > 0) {
      qtyInput.max = remaining;
      if (parseInt(qtyInput.value || '0', 10) > remaining) {
        qtyInput.value = remaining;
      }
    }
  }

  poSelect.addEventListener('change', syncSummaryFromPo);

  prSelect.addEventListener('change', function() {
    const selectedPr = prSelect.value;
    if (!selectedPr) return;

    const matchingOption = Array.from(poSelect.options).find(
      o => o.value && o.dataset.prId === selectedPr
    );
    if (matchingOption) {
      poSelect.value = matchingOption.value;
      syncSummaryFromPo();
    }
  });

  syncSummaryFromPo();
})();
@endsection
