@extends('layouts.app')

@section('title', 'Edit Goods Receipt — ' . $grn->grn_number)

@php
  $linkedPr = $grn->purchaseRequisition ?? $grn->purchaseOrder?->purchaseRequisition;
  $vendorName = $grn->purchaseOrder?->vendor?->company_name ?? '—';
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grn.show', $grn) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to {{ $grn->grn_number }}</span>
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
    <!-- Left: GRN Post-Submission Edit Form (col-lg-12) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-pencil-square"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Edit Goods Receipt</h1>
              <div class="text-secondary small mt-0.5">Revision for GRN <span class="fw-semibold text-dark">{{ $grn->grn_number }}</span></div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600; border-color:#e2e8f0 !important;">
            Pre-Verification Revision
          </span>
        </div>

        <form method="POST" action="{{ route('grn.update', $grn) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <!-- Section 1: Locked Reference Information -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Order &amp; Requisition Reference</h2>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-3">
                <label class="vendor-form-label text-secondary">GRN Number</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted font-monospace" value="{{ $grn->grn_number }}" disabled readonly>
              </div>

              <div class="col-12 col-md-3">
                <label class="vendor-form-label text-secondary">Purchase Order (PO)</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted font-monospace" value="{{ $grn->purchaseOrder?->po_number ?? '—' }}" disabled readonly>
              </div>

              <div class="col-12 col-md-3">
                <label class="vendor-form-label text-secondary">Originating PR</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted font-monospace" value="{{ $linkedPr?->pr_number ?? '—' }}" disabled readonly>
              </div>

              <div class="col-12 col-md-3">
                <label class="vendor-form-label text-secondary">Contracted Vendor</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted" value="{{ $vendorName }}" disabled readonly>
              </div>

              <div class="col-12 col-md-4">
                <label class="vendor-form-label text-secondary">Invoice / Challan Number</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted" value="{{ $grn->invoice_number ?: 'Not Specified' }}" disabled readonly>
              </div>

              <div class="col-12 col-md-8">
                <label class="vendor-form-label text-secondary">Quality &amp; Inspection Remarks</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted" value="{{ $grn->quality_remarks ?: 'No quality remarks recorded.' }}" disabled readonly>
              </div>
            </div>
          </div>

          <!-- Section 2: Editable Parameters (Quantity & Inspection Date) -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-clipboard-check"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Editable Quantities &amp; Inspection Schedule</h2>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="vendor-form-label" for="grnEditReceivedQtyInput">
                  Received Quantity <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted fw-bold" style="border: 1.5px solid #e2e8f0; border-right: none;">Qty</span>
                  <input type="number" name="received_qty" id="grnEditReceivedQtyInput" class="form-control vendor-form-control" min="1" max="{{ $maxEditableQty }}" value="{{ old('received_qty', $grn->received_qty) }}" required style="border-left: none;">
                </div>
                <div class="text-muted mt-1" style="font-size:12px;">Max {{ $maxEditableQty }} available on {{ $grn->purchaseOrder?->po_number }}.</div>
              </div>

              <div class="col-12 col-md-6">
                <label class="vendor-form-label" for="grnEditReceiptDateInput">
                  Physical Inspection Date <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted" style="border: 1.5px solid #e2e8f0; border-right: none;"><i class="bi bi-calendar3"></i></span>
                  <input type="date" name="receipt_date" id="grnEditReceiptDateInput" class="form-control vendor-form-control" value="{{ old('receipt_date', optional($grn->receipt_date)->format('Y-m-d') ?? now()->toDateString()) }}" required style="border-left: none;">
                </div>
                <div class="text-muted mt-1" style="font-size:12px;">Physical receipt / inspection date.</div>
              </div>

              <!-- Upload / Replace Invoice Document using Master Upload Card -->
              <div class="col-12">
                <div class="vendor-upload-card">
                  <div class="vendor-upload-title">
                    <i class="bi bi-file-earmark-arrow-up text-primary fs-5"></i>
                    <span>{{ $grn->attachments->isNotEmpty() ? 'Replace Invoice / Delivery Challan Document' : 'Upload Vendor Invoice / Delivery Challan' }}</span>
                  </div>
                  <div class="vendor-upload-hint">Attach signed delivery challan or invoice copy for finance and inventory audit (PDF, JPG, PNG — Max 10MB).</div>
                  <input type="file" name="invoice_document" id="grnEditInvoiceDocumentInput" class="form-control vendor-upload-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                  @if ($grn->attachments->isNotEmpty())
                    <div class="vendor-upload-meta mt-1.5">
                      Current attached file: <a href="{{ $grn->attachments->first()->url }}" target="_blank" class="fw-semibold text-primary text-decoration-none">{{ $grn->attachments->first()->original_name }}</a>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
            <a href="{{ route('grn.show', $grn) }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
              Update Goods Receipt
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Edit Goods Receipt' });
@endsection

