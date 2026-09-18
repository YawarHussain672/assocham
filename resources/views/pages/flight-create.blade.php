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
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(30,95,168,0.12),rgba(30,95,168,0.04)); color:#1e5fa8; border-color:rgba(30,95,168,0.2);">
            <i class="bi bi-ticket-detailed"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Log Flight / Hotel Transaction</h2>
            <div class="text-muted" style="font-size:13px;">Attach travel tickets, hotel vouchers, and boarding passes to approved TRFs.</div>
          </div>
        </div>

        @if ($trfs->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-airplane text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Approved TRFs Available</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              Transactions can only be logged against an approved Travel Requisition (TRF).
            </div>
            <a href="{{ route('trf') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-calendar2-check"></i>
              <span>View Travel Requisitions</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('flight.store') }}" class="mt-3" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-link-45deg text-muted"></i> Approved Travel Requisition (TRF) *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-airplane"></i></span>
                <select name="trf_id" class="form-select" required>
                  <option value="">Select approved TRF…</option>
                  @foreach ($trfs as $trf)
                    <option value="{{ $trf->id }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
                      {{ $trf->trf_number }} — {{ $trf->employee->name }} ({{ $trf->route ?: 'route not specified' }})
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-tags text-muted"></i> Booking Type *</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-card-checklist"></i></span>
                  <select name="type" class="form-select" required>
                    <option value="Flight ticket" {{ old('type') === 'Flight ticket' ? 'selected' : '' }}>Flight ticket</option>
                    <option value="Hotel" {{ old('type') === 'Hotel' ? 'selected' : '' }}>Hotel</option>
                    <option value="Transport" {{ old('type') === 'Transport' ? 'selected' : '' }}>Local Transport</option>
                    <option value="Other" {{ old('type') === 'Other' ? 'selected' : '' }}>Other Logistics</option>
                  </select>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-shop text-muted"></i> Empanelled Vendor</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-building"></i></span>
                  <select name="vendor_id" class="form-select">
                    <option value="">None / Direct Booking</option>
                    @foreach ($vendors as $vendor)
                      <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->company_name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label"><i class="bi bi-cash-stack text-muted"></i> Invoice Cost (₹) *</label>
                <div class="input-group">
                  <span class="input-group-text">₹</span>
                  <input type="number" name="cost" class="form-control" step="0.01" min="0" value="{{ old('cost') }}" placeholder="0.00" required>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label"><i class="bi bi-file-earmark-arrow-up text-muted"></i> Transaction Voucher / Boarding Pass Proof</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-paperclip"></i></span>
                  <input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="text-muted mt-1" style="font-size:11.5px;">PDF or image file up to 5MB. Highly recommended for audit clearance.</div>
              </div>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('flight') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
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
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-airplane-engines" style="color:var(--maroon);"></i>
            <span>Logistics Rules</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-3" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Cost Mapping:</strong> All booked tickets and room charges map one-to-one against the specific TRF number.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Boarding Verification:</strong> Retain physical/digital boarding passes for mandatory submission.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Vendor Rating:</strong> Completed trips unlock the Vendor Performance Evaluation scorecard.</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Upload Transaction' });
@endsection

