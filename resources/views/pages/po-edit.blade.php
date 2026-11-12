@extends('layouts.app')

@section('title', 'Edit Purchase Order — ' . $po->po_number)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po.show', $po) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to PO {{ $po->po_number }}</span>
  </a>

  <!-- Page Header -->
  <div class="page-header mb-4">
    <h1 class="page-title">Edit Purchase Order</h1>
    <p class="page-subtitle">Update contractual fulfillment dates or quantity specifications. 
      @if ($po->status === 'approved')
        <strong class="text-warning">Important: Updating an approved PO will reset its status to pending and require full re-authorization.</strong>
      @endif
    </p>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <div class="row">
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4 shadow-sm border-0">
        <form method="POST" action="{{ route('po.update', $po) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Purchase Order Number</label>
            <input type="text" class="form-control" value="{{ $po->po_number }}" disabled readonly>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Vendor</label>
              <input type="text" class="form-control" value="{{ $po->vendor->company_name }}" disabled readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Linked PR</label>
              <input type="text" class="form-control" value="{{ $po->purchaseRequisition->pr_number }}" disabled readonly>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="poQtyInput" class="form-label fw-semibold">Total Order Quantity <span class="text-danger">*</span></label>
              <input type="number" name="po_qty" id="poQtyInput" class="form-control" value="{{ old('po_qty', $po->po_qty) }}" min="1" required>
              <div class="form-text">Must be sufficient to fulfill the linked PR line items.</div>
            </div>

            <div class="col-md-6">
              <label for="deliveryDateInput" class="form-label fw-semibold">Target Delivery Date</label>
              <input type="date" name="delivery_date" id="deliveryDateInput" class="form-control" value="{{ old('delivery_date', optional($po->delivery_date)->format('Y-m-d')) }}">
              <div class="form-text">Revised contractual delivery timeline.</div>
            </div>
          </div>

          @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
            <hr class="my-4">
            <h6 class="fw-bold mb-3 text-primary d-flex align-items-center gap-2">
              <i class="bi bi-sliders"></i> Commercial Terms &amp; Legal Clauses (Admin Head Only)
            </h6>

            <div class="mb-3">
              <label class="form-label fw-semibold">Event Name / Project Reference</label>
              <input type="text" name="event_name" class="form-control" value="{{ old('event_name', $po->event_name) }}" placeholder="e.g. Annual Convention / Summit / Project">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Terms</label>
                <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $po->effective_payment_terms) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Delivery Terms</label>
                <input type="text" name="delivery_terms" class="form-control" value="{{ old('delivery_terms', $po->effective_delivery_terms) }}">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Delivery Address</label>
              <textarea name="delivery_address" class="form-control" rows="2">{{ old('delivery_address', $po->delivery_address) }}</textarea>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $po->contact_person) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Contact Number / Email</label>
                <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $po->contact_number) }}">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Terms &amp; Conditions (Numbered Clauses)</label>
              <textarea name="terms_and_conditions" class="form-control font-monospace" rows="8" style="font-size: 12px; line-height: 1.5;">{{ old('terms_and_conditions', $po->effective_terms) }}</textarea>
              <div class="form-text text-muted">Legal clauses embedded directly into the official ASSOCHAM Purchase Order PDF.</div>
            </div>
          @endif

          <div class="d-flex align-items-center justify-content-between pt-3 border-top">
            <a href="{{ route('po.show', $po) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> Save Changes &amp; Resubmit
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-panel p-4 shadow-sm border-0 bg-light">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-shield-check text-primary"></i> Governance Rule (Slide 13)
        </h6>
        <p class="small text-muted mb-3">
          To maintain strict procurement auditability, any modification made to a purchase order after initial approval automatically invalidates previous digital signatures and resets the order to <strong>Pending Approval</strong>.
        </p>
        <div class="p-2 rounded bg-white border small text-secondary">
          <i class="bi bi-info-circle me-1 text-primary"></i>
          Approvers will be alerted to re-verify updated contractual timelines.
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'Edit Purchase Order' });
@endsection
