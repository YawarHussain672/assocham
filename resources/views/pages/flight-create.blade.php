@extends('layouts.app')

@section('title', 'Upload Flight & Boarding Transaction')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Flight &amp; Boarding Logistics</span>
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
    <!-- Left: Flight Transaction Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-ticket-detailed"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">Log Flight / Hotel Transaction</h5>
              <div class="text-muted small">Attach travel tickets, hotel vouchers, and boarding passes to approved TRFs.</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Logistics Entry</span>
        </div>

        @if ($trfs->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-airplane text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px;">No Approved TRFs Available</div>
            <div class="text-muted mt-1 small" style="max-width:480px; margin:0 auto;">
              Transactions can only be logged against an approved Travel Requisition (TRF).
            </div>
            <a href="{{ route('trf') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 fw-semibold">
              <i class="bi bi-calendar2-check"></i>
              <span>View Travel Requisitions</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('flight.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
              <label class="form-label fw-medium text-secondary">Approved Travel Requisition (TRF) <span class="text-danger">*</span></label>
              <select name="trf_id" class="form-select" required>
                <option value="">Select approved TRF…</option>
                @foreach ($trfs as $trf)
                  <option value="{{ $trf->id }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
                    {{ $trf->trf_number }} — {{ $trf->employee->name }} ({{ $trf->route ?: 'route not specified' }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Booking Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                  <option value="Flight ticket" {{ old('type') === 'Flight ticket' ? 'selected' : '' }}>Flight ticket</option>
                  <option value="Hotel" {{ old('type') === 'Hotel' ? 'selected' : '' }}>Hotel</option>
                  <option value="Transport" {{ old('type') === 'Transport' ? 'selected' : '' }}>Local Transport</option>
                  <option value="Other" {{ old('type') === 'Other' ? 'selected' : '' }}>Other Logistics</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Empanelled Vendor</label>
                <select name="vendor_id" class="form-select">
                  <option value="">None / Direct Booking</option>
                  @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->company_name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-medium text-secondary">Invoice Cost (₹) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="cost" class="form-control" step="0.01" min="0" value="{{ old('cost') }}" placeholder="0.00" required>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-medium text-secondary">Transaction Voucher / Boarding Pass Proof</label>
                <input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text text-muted small">PDF or image file up to 5MB. Highly recommended for audit clearance.</div>
              </div>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('flight') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
                <i class="bi bi-cloud-check"></i>
                <span>Log Transaction</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Logistics Guidelines (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-airplane-engines"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Logistics Rules</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Policy</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-primary border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-link-45deg fs-5"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Cost Mapping</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">All tickets and room charges map one-to-one against the specific TRF number.</div>
              </div>
            </div>
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-info border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-ticket-perforated fs-5"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Boarding Verification</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Retain physical or digital boarding passes for mandatory claim audit.</div>
              </div>
            </div>
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-warning border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-star-half fs-5"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Vendor Rating</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Completed trips unlock the Vendor Performance Evaluation scorecard.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Upload Transaction' });
@endsection

