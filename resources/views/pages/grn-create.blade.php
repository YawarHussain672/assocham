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
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">Log Inward Goods Receipt (GRN)</h5>
              <div class="text-muted small">Record physical delivery against an issued Purchase Order for departmental verification.</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Goods Entry</span>
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

            <div class="mb-3">
              <label class="form-label fw-medium text-secondary">Target Purchase Order <span class="text-danger">*</span></label>
              <select name="purchase_order_id" class="form-select" required>
                <option value="">Select approved PO…</option>
                @foreach ($purchaseOrders as $po)
                  <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                    {{ $po->po_number }} — {{ $po->vendor->company_name }} ({{ $po->receivedQty() }}/{{ $po->po_qty }} received, {{ $po->remainingQtyToReceive() }} remaining)
                  </option>
                @endforeach
              </select>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Quantity Delivered <span class="text-danger">*</span></label>
                <input type="number" name="received_qty" class="form-control" min="1" value="{{ old('received_qty', 1) }}" required>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Vendor Invoice / Challan</label>
                <input type="text" name="invoice_number" class="form-control" placeholder="e.g. INV-9082" value="{{ old('invoice_number') }}">
              </div>

              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Inspection Date <span class="text-danger">*</span></label>
                <input type="date" name="receipt_date" class="form-control" value="{{ old('receipt_date', now()->toDateString()) }}" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-medium text-secondary">Quality &amp; Condition Inspection Remarks</label>
                <textarea name="quality_remarks" class="form-control" rows="2" placeholder="e.g. Packages received in sealed condition. Verified serial numbers.">{{ old('quality_remarks') }}</textarea>
              </div>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('grn') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
                <i class="bi bi-send-check"></i>
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
        
        <!-- Receiving Protocols Card -->
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:32px; height:32px;">
                <i class="bi bi-shield-check"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Receiving Protocols</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Policy</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-success border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-boxes fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Partial Receipts</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Multiple GRNs can be recorded against a PO until quantity is exhausted.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-primary border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-person-check fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Mandatory HOD Signoff</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Department Head must verify goods condition before vendor payment release.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-info border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-receipt fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Billing Reconciliation</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Attach physical delivery challan or invoice number for billing reconciliation.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Inventory Accountancy Card -->
        <div class="card-panel p-3">
          <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary me-1 flex-shrink-0" style="width:28px; height:28px;">
                <i class="bi bi-box-seam"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Inventory Accountancy</h6>
            </div>
            <div class="text-muted small" style="font-size:12.5px; line-height:1.5;">
              Approval of this GRN confirms asset entry into ASSOCHAM inventory and marks the PO as fulfilled.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Log Goods Receipt' });
@endsection
