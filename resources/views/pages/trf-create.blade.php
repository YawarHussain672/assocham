@extends('layouts.app')

@section('title', 'New Travel Requisition')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('trf') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Requisitions</span>
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
    <!-- Left: Travel Requisition Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-airplane"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">Create Travel Requisition (TRF)</h5>
              <div class="text-muted small">Initiate domestic or international official travel and request advance funds.</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Official Requisition</span>
        </div>

        <form method="POST" action="{{ route('trf.store') }}" id="trfForm">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Booking Type <span class="text-danger">*</span></label>
              <select name="booking_type" class="form-select" id="bookingTypeSelect" required>
                <option value="self" {{ old('booking_type', 'self') === 'self' ? 'selected' : '' }}>Self (My Own Travel)</option>
                <option value="on_behalf" {{ old('booking_type') === 'on_behalf' ? 'selected' : '' }}>On Behalf Of (Admin / Executive)</option>
              </select>
            </div>

            <div class="col-md-6" id="employeeSelectWrap" style="display:none;">
              <label class="form-label fw-medium text-secondary">Traveling Employee <span class="text-danger">*</span></label>
              <select name="employee_id" class="form-select">
                <option value="">Select traveling employee…</option>
                @foreach ($users as $user)
                  <option value="{{ $user->id }}" {{ old('employee_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->roleLabel() }})</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium text-secondary">Official Purpose <span class="text-danger">*</span></label>
              <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="e.g. Annual Partner Summit / Site Inspection" required>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium text-secondary">Travel Itinerary Route <span class="text-danger">*</span></label>
              <input type="text" name="route" class="form-control" value="{{ old('route') }}" placeholder="e.g. New Delhi → Mumbai (BOM) → Bengaluru (BLR) → New Delhi" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Departure Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_start" class="form-control" value="{{ old('travel_start') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Return Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_end" class="form-control" value="{{ old('travel_end') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Requested Advance (₹)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="requested_advance" class="form-control" step="0.01" min="0" value="{{ old('requested_advance', '0.00') }}" placeholder="0.00">
              </div>
              <div class="form-text text-muted small">Disbursed after final approval. Settle within 7 days of trip conclusion.</div>
            </div>

            <div class="col-12 mt-4 pt-3 border-top">
              <label class="form-label fw-semibold text-dark mb-2">Travel Preferences &amp; Scope</label>
              <div class="p-3 rounded-3 bg-light d-flex flex-wrap align-items-center gap-4">
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="intlCheck" name="is_international" value="1" {{ old('is_international') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                  <label class="form-check-label fw-medium text-dark ms-1" for="intlCheck" style="cursor:pointer;">International Trip</label>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="insuranceCheck" name="insurance" value="1" {{ old('insurance') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                  <label class="form-check-label fw-medium text-dark ms-1" for="insuranceCheck" style="cursor:pointer;">Travel Insurance</label>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="accommodationCheck" name="accommodation" value="1" {{ old('accommodation') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                  <label class="form-check-label fw-medium text-dark ms-1" for="accommodationCheck" style="cursor:pointer;">Accommodation Needed</label>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('trf') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-send-check"></i>
              <span>Submit Travel Requisition</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Policy & Routing Sidebar (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <!-- Approval Protocol Card -->
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-diagram-3"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Approval Protocol</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Routing Rules</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <!-- Domestic Trips -->
            <div class="p-3 rounded-3 bg-light d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-primary-subtle text-primary me-2" style="width:38px; height:38px; font-size:14px; flex-shrink:0;">
                  <i class="bi bi-geo-alt"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark" style="font-size:13.5px;">Domestic Trips</div>
                  <div class="text-muted small">Routes to ASG 1 / ASG 2</div>
                </div>
              </div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5">
                ASG Approval
              </span>
            </div>

            <!-- International Trips -->
            <div class="p-3 rounded-3 bg-light d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-warning-subtle text-warning me-2" style="width:38px; height:38px; font-size:14px; flex-shrink:0;">
                  <i class="bi bi-globe"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark" style="font-size:13.5px;">International Trips</div>
                  <div class="text-muted small">Direct clearance by Secretary General</div>
                </div>
              </div>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5">
                SG Clearance
              </span>
            </div>
          </div>
        </div>

        <!-- Travel Governance SLA Card -->
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:32px; height:32px;">
                <i class="bi bi-shield-check"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Travel Governance SLA</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Policy</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-primary border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-clock-history"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Advance Settlement</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">File reimbursement claim within 7 working days of trip end date.</div>
              </div>
            </div>
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-info border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-ticket-perforated"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Boarding Passes</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Upload physical or digital boarding passes to Flight Logistics.</div>
              </div>
            </div>
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-success border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-building-check"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Empanelled Rates</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Booked through approved vendors at negotiated corporate tariffs.</div>
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
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'New Travel Requisition' });

(function () {
  const select = document.getElementById('bookingTypeSelect');
  const wrap = document.getElementById('employeeSelectWrap');
  function sync() { wrap.style.display = select.value === 'on_behalf' ? '' : 'none'; }
  select.addEventListener('change', sync);
  sync();
})();
@endsection
