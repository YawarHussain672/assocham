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
    <!-- Left: GRN Form (col-lg-12) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-box-seam"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Log Inward Goods Receipt (GRN)</h1>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:11px; font-weight:600; letter-spacing:0.3px; border-color:#e2e8f0 !important;">GOODS ENTRY</span>
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
          <form method="POST" action="{{ route('grn.store') }}" enctype="multipart/form-data">
            @csrf

            @php
              $initialPoId = old('purchase_order_id', $selectedPoId ?? (request('po_id') ?? request('po')));
              $initialPrId = old('purchase_requisition_id', $selectedPrId ?? request('pr_id'));
              if (!$initialPoId && $purchaseOrders->count() === 1) {
                $initialPoId = $purchaseOrders->first()->id;
                $initialPrId = $purchaseOrders->first()->purchase_requisition_id;
              }
            @endphp

            <!-- Section 1: Order & Requisition Linking -->
            <div class="mb-4">
              <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-file-earmark-text"></i>
                </div>
                <h2 class="vendor-form-section-title mb-0">Order &amp; Requisition Reference</h2>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="vendor-form-label">Target Purchase Order (PO) <span class="text-danger">*</span></label>
                  <select name="purchase_order_id" id="grnPoSelect" class="form-select vendor-form-control" required>
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
                  <div class="text-muted mt-1" style="font-size:12px;">Select approved &amp; issued purchase order.</div>
                </div>

                <div class="col-md-6">
                  <label class="vendor-form-label">Originating Purchase Requisition (PR)</label>
                  <select name="purchase_requisition_id" id="grnPrSelect" class="form-select vendor-form-control">
                    <option value="">Select or auto-link PR…</option>
                    @foreach ($purchaseRequisitions as $pr)
                      <option value="{{ $pr->id }}" {{ (string) $initialPrId === (string) $pr->id ? 'selected' : '' }}>
                        {{ $pr->pr_number }} &middot; {{ $pr->title ?: ($pr->department . ' Requisition') }} ({{ $pr->department }})
                      </option>
                    @endforeach
                  </select>
                  <div class="text-muted mt-1" style="font-size:12px;">Auto-links when PO is chosen.</div>
                </div>
              </div>

              <!-- Refined PO Details Context Card -->
              <div id="grnLinkedSummaryCard" class="card border rounded-3 p-3 mt-3 bg-light-subtle d-none" style="border-color:#e2e8f0 !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2.5 border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size:10.5px; font-weight:600;">LINKED REFERENCE</span>
                    <span class="fw-semibold text-dark" style="font-size:13px;">Purchase Order &amp; Fulfillment Summary</span>
                  </div>
                  <span class="badge bg-white text-secondary border font-monospace" id="grnSummaryQtyBadge" style="font-size:11px; font-weight:600;"></span>
                </div>
                <div class="row g-2.5" style="font-size:12.5px;">
                  <div class="col-12 col-md-4">
                    <div class="text-muted" style="font-size:11px;">Purchase Order</div>
                    <a href="#" id="grnSummaryPoLink" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none"></a>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="text-muted" style="font-size:11px;">Originating PR</div>
                    <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
                      <a href="#" id="grnSummaryPrLink" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none"></a>
                      <span class="badge bg-light text-secondary border" id="grnSummaryDeptBadge" style="font-size:10px;"></span>
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="text-muted" style="font-size:11px;">Contracted Vendor</div>
                    <div class="fw-semibold text-dark text-truncate" id="grnSummaryVendor"></div>
                  </div>
                  <div class="col-12 pt-2 border-top">
                    <span class="text-muted" style="font-size:11px;">Line Items:</span>
                    <span class="text-dark fw-medium ms-1" id="grnSummaryItems"></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 2: Physical Inspection & Delivery Particulars -->
            <div class="mb-4">
              <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-clipboard-check"></i>
                </div>
                <h2 class="vendor-form-section-title mb-0">Physical Inspection &amp; Delivery Particulars</h2>
              </div>

              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="vendor-form-label" for="grnReceivedQtyInput">Received Quantity <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted" style="border: 1.5px solid #e2e8f0; border-right: none;"><i class="bi bi-boxes"></i></span>
                    <input type="number" name="received_qty" id="grnReceivedQtyInput" class="form-control vendor-form-control" min="1" value="{{ old('received_qty', 1) }}" required style="border-left: none;">
                  </div>
                  <div class="text-muted mt-1" style="font-size:12px;">Max: balance remaining on PO.</div>
                </div>

                <div class="col-12 col-md-4">
                  <label class="vendor-form-label" for="grnInvoiceInput">Invoice Number / Challan</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted" style="border: 1.5px solid #e2e8f0; border-right: none;"><i class="bi bi-receipt"></i></span>
                    <input type="text" name="invoice_number" id="grnInvoiceInput" class="form-control vendor-form-control" placeholder="e.g. INV-9082 / CH-4321" value="{{ old('invoice_number') }}" style="border-left: none;">
                  </div>
                  <div class="text-muted mt-1" style="font-size:12px;">Vendor delivery challan or bill number.</div>
                </div>

                <div class="col-12 col-md-4">
                  <label class="vendor-form-label" for="grnReceiptDateInput">Physical Inspection Date <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted" style="border: 1.5px solid #e2e8f0; border-right: none;"><i class="bi bi-calendar3"></i></span>
                    <input type="date" name="receipt_date" id="grnReceiptDateInput" class="form-control vendor-form-control" value="{{ old('receipt_date', now()->toDateString()) }}" required style="border-left: none;">
                  </div>
                  <div class="text-muted mt-1" style="font-size:12px;">Date goods were physically inspected.</div>
                </div>

                <!-- Invoice / Delivery Challan Document Upload using Master Upload Card -->
                <div class="col-12">
                  <div class="vendor-upload-card">
                    <div class="vendor-upload-title">
                      <i class="bi bi-file-earmark-arrow-up text-primary fs-5"></i>
                      <span>Upload Vendor Invoice / Delivery Challan</span>
                    </div>
                    <div class="vendor-upload-hint">Attach signed delivery challan or invoice copy for finance and inventory audit (PDF, JPG, PNG — Max 10MB).</div>
                    <input type="file" name="invoice_document" id="grnInvoiceDocumentInput" class="form-control vendor-upload-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                  </div>
                </div>

                <div class="col-12">
                  <label class="vendor-form-label" for="grnQualityRemarksInput">Quality Remarks &amp; Condition Check</label>
                  <textarea name="quality_remarks" id="grnQualityRemarksInput" class="form-control vendor-form-control" rows="3" placeholder="e.g. Packages received in sealed condition. Physical count and specifications verified without defects.">{{ old('quality_remarks') }}</textarea>
                  <div class="text-muted mt-1" style="font-size:12px;">Record physical condition, damage check, and inspection notes.</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
              <a href="{{ route('grn') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
              <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
                Submit for HOD Verification
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
