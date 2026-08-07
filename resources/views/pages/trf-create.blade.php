@extends('layouts.app')

@section('title', 'New Travel Requisition')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('trf') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Travel Requisition
  </a>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-panel p-4">
    <h2 class="h5 mb-1">New Travel Requisition</h2>
    <p class="page-sub" style="margin-top:0;">Domestic trips route to either ASG for final approval. International trips route to the Secretary General instead.</p>

    <form method="POST" action="{{ route('trf.store') }}" class="mt-3" id="trfForm">
      @csrf

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Booking Type *</label>
          <select name="booking_type" class="form-select" id="bookingTypeSelect" required>
            <option value="self" {{ old('booking_type', 'self') === 'self' ? 'selected' : '' }}>Self</option>
            <option value="on_behalf" {{ old('booking_type') === 'on_behalf' ? 'selected' : '' }}>On Behalf Of (Admin)</option>
          </select>
        </div>
        <div class="col-md-8" id="employeeSelectWrap" style="display:none;">
          <label class="form-label">Traveling Employee</label>
          <select name="employee_id" class="form-select">
            <option value="">Select employee…</option>
            @foreach ($users as $user)
              <option value="{{ $user->id }}" {{ old('employee_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->roleLabel() }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-8">
          <label class="form-label">Purpose</label>
          <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="e.g. Client Site Kickoff">
        </div>
        <div class="col-md-4">
          <div class="form-check mt-4 pt-1">
            <input class="form-check-input" type="checkbox" id="intlCheck" name="is_international" value="1" {{ old('is_international') ? 'checked' : '' }}>
            <label class="form-check-label" for="intlCheck">International Travel</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Route</label>
          <input type="text" name="route" class="form-control" value="{{ old('route') }}" placeholder="e.g. Delhi → Mumbai → Bengaluru">
        </div>

        <div class="col-md-6">
          <label class="form-label">Travel Start</label>
          <input type="date" name="travel_start" class="form-control" value="{{ old('travel_start') }}">
        </div>
        <div class="col-md-6">
          <label class="form-label">Travel End</label>
          <input type="date" name="travel_end" class="form-control" value="{{ old('travel_end') }}">
        </div>

        <div class="col-md-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="insuranceCheck" name="insurance" value="1" {{ old('insurance') ? 'checked' : '' }}>
            <label class="form-check-label" for="insuranceCheck">Travel Insurance</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="accommodationCheck" name="accommodation" value="1" {{ old('accommodation') ? 'checked' : '' }}>
            <label class="form-check-label" for="accommodationCheck">Accommodation Needed</label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Requested Advance (₹)</label>
          <input type="number" name="requested_advance" class="form-control" step="0.01" min="0" value="{{ old('requested_advance') }}">
        </div>
      </div>

      <button type="submit" class="btn-brand mt-4">Submit Travel Requisition</button>
    </form>
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
