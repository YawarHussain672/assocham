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
    <!-- Left: GRN Post-Submission Edit Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-pencil-square"></i>
            </div>
            <div>
              <h2 class="h5 mb-0 fw-bold text-dark">Edit Goods Receipt ({{ $grn->grn_number }})</h2>
              <div class="text-muted" style="font-size:13px;">Only Quantity and Date remain editable after submission. All other fields are locked.</div>
            </div>
          </div>
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5" style="font-size:11px; font-weight:600;">
            <i class="bi bi-lock-fill me-1"></i>PARTIAL LOCK
          </span>
        </div>

        <form method="POST" action="{{ route('grn.update', $grn) }}">
          @csrf
          @method('PUT')

          <!-- Locked Reference Fields -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
                <span>GRN Number</span>
                <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
              </label>
              <input type="text" class="form-control bg-light text-muted font-monospace" value="{{ $grn->grn_number }}" disabled readonly>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
                <span>Purchase Order (PO)</span>
                <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
              </label>
              <input type="text" class="form-control bg-light text-muted font-monospace" value="{{ $grn->purchaseOrder?->po_number ?? '—' }}" disabled readonly>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
                <span>Originating PR</span>
                <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
              </label>
              <input type="text" class="form-control bg-light text-muted font-monospace" value="{{ $linkedPr?->pr_number ?? '—' }}" disabled readonly>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
                <span>Contracted Vendor</span>
                <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
              </label>
              <input type="text" class="form-control bg-light text-muted" value="{{ $vendorName }}" disabled readonly>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
                <span>Invoice Number</span>
                <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
              </label>
              <input type="text" class="form-control bg-light text-muted" value="{{ $grn->invoice_number ?: 'Not Specified' }}" disabled readonly>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary mb-1.5 d-flex align-items-center justify-content-between">
              <span>Quality Remarks</span>
              <i class="bi bi-lock-fill text-muted" style="font-size:11px;" title="Locked after submission"></i>
            </label>
            <textarea class="form-control bg-light text-muted" rows="2" disabled readonly>{{ $grn->quality_remarks ?: 'No quality remarks recorded.' }}</textarea>
          </div>

          <hr class="my-3">

          <!-- Editable Fields: ONLY Quantity and Date -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-dark mb-1.5" for="grnEditReceivedQtyInput">
                Received Quantity <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-white text-primary"><i class="bi bi-box"></i></span>
                <input type="number" name="received_qty" id="grnEditReceivedQtyInput" class="form-control" min="1" max="{{ $maxEditableQty }}" value="{{ old('received_qty', $grn->received_qty) }}" required>
              </div>
              <div class="form-text text-muted" style="font-size:11.5px;">Editable (max {{ $maxEditableQty }} available on {{ $grn->purchaseOrder?->po_number }}).</div>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-dark mb-1.5" for="grnEditReceiptDateInput">
                Inspection Date <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-white text-primary"><i class="bi bi-calendar3"></i></span>
                <input type="date" name="receipt_date" id="grnEditReceiptDateInput" class="form-control" value="{{ old('receipt_date', optional($grn->receipt_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
              </div>
              <div class="form-text text-muted" style="font-size:11.5px;">Editable physical receipt / inspection date.</div>
            </div>
          </div>

          <div class="pt-3 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('grn.show', $grn) }}" class="btn btn-light border px-4 fw-semibold text-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-check2-circle"></i>
              <span>Update Quantity &amp; Date</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Post-Submission Lock Policy (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="card shadow-sm border-0 p-3">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <i class="bi bi-shield-lock text-primary fs-5"></i>
            <span class="fw-bold text-dark" style="font-size: 14px;">Post-Submission Lock Rules</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Editable Fields:</strong> Only <strong>Received Quantity</strong> and <strong>Inspection Date</strong> can be revised before HOD signoff.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-lock-fill text-secondary mt-1 flex-shrink-0"></i>
              <span><strong>Locked Fields:</strong> Linked PO, PR, Vendor, Invoice Number, and Quality Remarks are permanently locked upon submission.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
              <span><strong>Final Seal:</strong> Once verified or rejected by the Department Head, no further edits are permitted.</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Edit Goods Receipt' });
@endsection

