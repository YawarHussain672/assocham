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
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(30,95,168,0.12),rgba(30,95,168,0.04)); color:var(--maroon); border-color:rgba(30,95,168,0.2);">
            <i class="bi bi-airplane"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Create Travel Requisition (TRF)</h2>
            <div class="text-muted" style="font-size:13px;">Initiate domestic or international official travel and request advance funds.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('trf.store') }}" class="mt-3" id="trfForm">
          @csrf

          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label"><i class="bi bi-person text-muted"></i> Booking Type *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                <select name="booking_type" class="form-select" id="bookingTypeSelect" required>
                  <option value="self" {{ old('booking_type', 'self') === 'self' ? 'selected' : '' }}>Self (My Own Travel)</option>
                  <option value="on_behalf" {{ old('booking_type') === 'on_behalf' ? 'selected' : '' }}>On Behalf Of (Admin / Executive)</option>
                </select>
              </div>
            </div>

            <div class="col-md-7" id="employeeSelectWrap" style="display:none;">
              <label class="form-label"><i class="bi bi-people text-muted"></i> Traveling Employee *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-check"></i></span>
                <select name="employee_id" class="form-select">
                  <option value="">Select traveling employee…</option>
                  @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('employee_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->roleLabel() }})</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label"><i class="bi bi-briefcase text-muted"></i> Official Purpose *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="e.g. Annual Partner Summit / Site Inspection" required>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label"><i class="bi bi-globe2 text-muted"></i> Trip Scope</label>
              <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between" style="min-height:38px;">
                <label class="form-check-label mb-0" for="intlCheck" style="font-size:13px; font-weight:600; cursor:pointer;">
                  International Trip
                </label>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="intlCheck" name="is_international" value="1" {{ old('is_international') ? 'checked' : '' }}>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label"><i class="bi bi-geo-alt text-muted"></i> Travel Itinerary Route *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-signpost-split"></i></span>
                <input type="text" name="route" class="form-control" value="{{ old('route') }}" placeholder="e.g. New Delhi → Mumbai (BOM) → Bengaluru (BLR) → New Delhi" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-calendar-event text-muted"></i> Departure Date *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-plus"></i></span>
                <input type="date" name="travel_start" class="form-control" value="{{ old('travel_start') }}" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-calendar-check text-muted"></i> Return Date *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event-fill"></i></span>
                <input type="date" name="travel_end" class="form-control" value="{{ old('travel_end') }}" required>
              </div>
            </div>

            <div class="col-md-6">
              <div class="p-3 border rounded-3 bg-white d-flex align-items-center justify-content-between shadow-sm">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-shield-check text-primary fs-5"></i>
                  <div>
                    <div style="font-size:13.5px; font-weight:600;">Travel Insurance</div>
                    <div class="text-muted" style="font-size:11.5px;">Include comprehensive policy coverage</div>
                  </div>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="insuranceCheck" name="insurance" value="1" {{ old('insurance') ? 'checked' : '' }}>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="p-3 border rounded-3 bg-white d-flex align-items-center justify-content-between shadow-sm">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-building text-info fs-5"></i>
                  <div>
                    <div style="font-size:13.5px; font-weight:600;">Accommodation Needed</div>
                    <div class="text-muted" style="font-size:11.5px;">Empanelled hotel booking required</div>
                  </div>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="accommodationCheck" name="accommodation" value="1" {{ old('accommodation') ? 'checked' : '' }}>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-cash-stack text-muted"></i> Requested Advance (₹)</label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="requested_advance" class="form-control" step="0.01" min="0" value="{{ old('requested_advance', '0.00') }}" placeholder="0.00">
              </div>
              <div class="text-muted mt-1" style="font-size:11.5px;">Disbursed after final approval. Settle within 7 days of trip conclusion.</div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('trf') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
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
        
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
            <span>Approval Protocol</span>
          </div>
          <div class="p-3">
            <div class="d-flex align-items-start gap-2 mb-3">
              <span class="stat-icon-circle" style="background:#E6F0FB; color:#1E5FA8; width:28px; height:28px; font-size:12px; flex-shrink:0;">
                <i class="bi bi-geo"></i>
              </span>
              <div>
                <div style="font-size:13px; font-weight:600; color:#1e293b;">Domestic Trips</div>
                <div class="text-muted" style="font-size:12px;">Routes to Assistant Secretary General (ASG 1 or ASG 2) for final approval.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="stat-icon-circle" style="background:#FBF0DC; color:#8A5A12; width:28px; height:28px; font-size:12px; flex-shrink:0;">
                <i class="bi bi-globe"></i>
              </span>
              <div>
                <div style="font-size:13px; font-weight:600; color:#1e293b;">International Trips</div>
                <div class="text-muted" style="font-size:12px;">Routes directly to Secretary General (SG) for executive clearance.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>Travel Governance SLA</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-3" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Advance Settlement:</strong> Must file reimbursement claim within 7 working days of trip end date.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Boarding Passes:</strong> Physical or digital boarding passes must be uploaded to the Flight Logistics ledger.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Empanelled Rates:</strong> Flights and hotels are booked through approved vendors at negotiated corporate tariffs.</span>
            </li>
          </ul>
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
