@extends('layouts.app')

@section('title', 'Edit Purchase Order — ' . $po->po_number)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po.show', $po) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to PO {{ $po->po_number }}</span>
  </a>

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-12">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-pencil-square"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Edit Purchase Order</h1>
              <div class="text-secondary small mt-0.5">Revision for PO <span class="fw-semibold text-dark">{{ $po->po_number }}</span></div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600; border-color: #e2e8f0 !important;">
            Order Revision
          </span>
        </div>

        <form method="POST" action="{{ route('po.update', $po) }}">
          @csrf
          @method('PUT')

          <!-- Section 1: Order & Supplier Reference (Locked) -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Requisition &amp; Vendor Reference</h2>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="vendor-form-label text-secondary">Purchase Order Number</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted font-monospace" value="{{ $po->po_number }}" disabled readonly>
              </div>

              <div class="col-12 col-md-4">
                <label class="vendor-form-label text-secondary">Awarded Vendor</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted" value="{{ $po->vendor->company_name }}" disabled readonly>
              </div>

              <div class="col-12 col-md-4">
                <label class="vendor-form-label text-secondary">Linked Requisition</label>
                <input type="text" class="form-control vendor-form-control bg-light text-muted font-monospace" value="{{ $po->purchaseRequisition->pr_number }}" disabled readonly>
              </div>
            </div>
          </div>

          <!-- Section 2: Order Quantity & Fulfillment Target (Editable) -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-box-seam"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Order Quantity &amp; Fulfillment Schedule</h2>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="poQtyInput" class="vendor-form-label">Total Order Quantity <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted fw-bold" style="border: 1.5px solid #e2e8f0; border-right: none;">Qty</span>
                  <input type="number" name="po_qty" id="poQtyInput" class="form-control vendor-form-control" value="{{ old('po_qty', $po->po_qty) }}" min="1" required style="border-left: none;">
                </div>
                <div class="text-muted mt-1" style="font-size: 12px;">Must be sufficient to fulfill the linked PR line items.</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="deliveryDateInput" class="vendor-form-label">Target Delivery Date</label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted" style="border: 1.5px solid #e2e8f0; border-right: none;"><i class="bi bi-calendar"></i></span>
                  <input type="date" name="delivery_date" id="deliveryDateInput" class="form-control vendor-form-control" value="{{ old('delivery_date', optional($po->delivery_date)->format('Y-m-d')) }}" style="border-left: none;">
                </div>
                <div class="text-muted mt-1" style="font-size: 12px;">Contractual delivery timeline.</div>
              </div>
            </div>
          </div>

          <!-- Section 3: Commercial Terms & Legal Clauses -->
          @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
            <div class="mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="vendor-form-icon-box">
                    <i class="bi bi-shield-check"></i>
                  </div>
                  <h2 class="vendor-form-section-title mb-0">Commercial Terms &amp; Legal Clauses</h2>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 11px;">Admin Head Editable</span>
              </div>

              <div class="row g-3">
                <div class="col-12">
                  <label class="vendor-form-label">Event Name / Project Reference</label>
                  <input type="text" name="event_name" class="form-control vendor-form-control" value="{{ old('event_name', $po->event_name) }}" placeholder="e.g. Annual Convention / Summit / Project">
                </div>

                <div class="col-12 col-md-6">
                  <label class="vendor-form-label">Payment Terms</label>
                  <input type="text" name="payment_terms" class="form-control vendor-form-control" value="{{ old('payment_terms', $po->effective_payment_terms) }}">
                </div>
                <div class="col-12 col-md-6">
                  <label class="vendor-form-label">Delivery Terms</label>
                  <input type="text" name="delivery_terms" class="form-control vendor-form-control" value="{{ old('delivery_terms', $po->effective_delivery_terms) }}">
                </div>

                <div class="col-12 col-md-6">
                  <label class="vendor-form-label">Contact Person</label>
                  <input type="text" name="contact_person" class="form-control vendor-form-control" value="{{ old('contact_person', $po->contact_person) }}">
                </div>
                <div class="col-12 col-md-6">
                  <label class="vendor-form-label">Contact Number / Email</label>
                  <input type="text" name="contact_number" class="form-control vendor-form-control" value="{{ old('contact_number', $po->contact_number) }}">
                </div>

                <div class="col-12">
                  <label class="vendor-form-label">Delivery Address</label>
                  <textarea name="delivery_address" class="form-control vendor-form-control" rows="2">{{ old('delivery_address', $po->delivery_address) }}</textarea>
                </div>

                <div class="col-12">
                  <label class="vendor-form-label">Terms &amp; Conditions (Contractual Clauses)</label>
                  <textarea name="terms_and_conditions" class="form-control vendor-form-control font-monospace" rows="7" style="font-size: 13px; line-height: 1.5;">{{ old('terms_and_conditions', $po->effective_terms) }}</textarea>
                  <div class="text-muted mt-1.5" style="font-size: 12px;">Standard 8 legal clauses embedded into the official Purchase Order.</div>
                </div>
              </div>
            </div>
          @endif

          <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
            <a href="{{ route('po.show', $po) }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
              Save Changes &amp; Resubmit
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'Edit Purchase Order' });
@endsection
