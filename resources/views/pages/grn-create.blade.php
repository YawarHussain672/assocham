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
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(5,150,105,0.12),rgba(5,150,105,0.04)); color:#059669; border-color:rgba(5,150,105,0.2);">
            <i class="bi bi-box-arrow-in-down"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Log Inward Goods Receipt (GRN)</h2>
            <div class="text-muted" style="font-size:13px;">Record physical delivery against an issued Purchase Order for departmental verification.</div>
          </div>
        </div>

        @if ($purchaseOrders->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Pending Deliveries Awaiting Receipt</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              All issued Purchase Orders have either been fully delivered or are awaiting approval.
            </div>
            <a href="{{ route('po') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-receipt"></i>
              <span>View Purchase Orders</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('grn.store') }}" class="mt-3">
            @csrf

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-receipt text-muted"></i> Target Purchase Order *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <select name="purchase_order_id" class="form-select" required>
                  <option value="">Select approved PO…</option>
                  @foreach ($purchaseOrders as $po)
                    <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                      {{ $po->po_number }} — {{ $po->vendor->company_name }} ({{ $po->receivedQty() }}/{{ $po->po_qty }} received, {{ $po->remainingQtyToReceive() }} remaining)
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-123 text-muted"></i> Quantity Delivered *</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                  <input type="number" name="received_qty" class="form-control" min="1" value="{{ old('received_qty', 1) }}" required>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-file-earmark-text text-muted"></i> Vendor Invoice / Challan</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-hash"></i></span>
                  <input type="text" name="invoice_number" class="form-control" placeholder="e.g. INV-9082" value="{{ old('invoice_number') }}">
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-calendar3 text-muted"></i> Inspection Date *</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                  <input type="date" name="receipt_date" class="form-control" value="{{ old('receipt_date', now()->toDateString()) }}" required>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label"><i class="bi bi-patch-check text-muted"></i> Quality &amp; Condition Inspection Remarks</label>
                <textarea name="quality_remarks" class="form-control" rows="2" placeholder="e.g. Packages received in sealed condition. Verified serial numbers.">{{ old('quality_remarks') }}</textarea>
              </div>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('grn') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
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
        
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:#059669;"></i>
            <span>Receiving Protocols</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Partial Receipts:</strong> Multiple GRNs can be recorded against a PO until quantity is exhausted.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Mandatory HOD Signoff:</strong> Department Head must verify goods condition before vendor payment release.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Invoicing:</strong> Attach physical delivery challan or invoice number for billing reconciliation.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1" style="font-weight:600;">
            <i class="bi bi-box-seam" style="color:var(--maroon);"></i>
            <span>Inventory Accountancy</span>
          </div>
          <div class="text-muted">Approval of this GRN confirms asset entry into ASSOCHAM inventory and marks the PO as fulfilled.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Log Goods Receipt' });
@endsection
