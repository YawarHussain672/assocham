@extends('layouts.app')

@section('title', 'Edit ' . $trf->trf_number)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('trf.show', $trf) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to {{ $trf->trf_number }}</span>
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

  @if ($trf->status === 'pushed_back' && $latestPushback)
    <div class="alert border-0 shadow-sm mb-4 p-3.5 rounded-3" style="background: #FFF7ED; border-left: 4px solid #EA580C !important;">
      <div class="d-flex align-items-start gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px; background:#FFEDD5; color:#C2410C;">
          <i class="bi bi-arrow-return-left fs-5"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span class="fw-bold" style="color:#9A3412; font-size:14px;">Returned for Revision (Pushback)</span>
            <span class="badge bg-white text-dark border" style="font-size:11px;">
              By {{ $latestPushback->approver?->name ?? 'Approver' }} · {{ optional($latestPushback->acted_at)->format('d M Y, H:i') }}
            </span>
          </div>
          <div class="text-dark" style="font-size:13px;">
            &ldquo;{{ $latestPushback->comments }}&rdquo;
          </div>
          <div class="text-muted small mt-1" style="font-size:11.5px;">
            Update the travel details below and resubmit for approval. Your TRF number <strong>{{ $trf->trf_number }}</strong> will remain unchanged.
          </div>
        </div>
      </div>
    </div>
  @endif

  @php
    $allKnownCities = array_merge($domesticCities ?? [], $internationalCities ?? []);
    $selFrom = old('from_city', $fromCity);
    $selTo = old('to_city', $toCity);
    $selBooking = old('booking_type', $trf->booking_type ?: 'self');
  @endphp

  <div class="row g-4">
    <!-- Left: Edit Travel Requisition Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-pencil-square"></i>
            </div>
            <div>
              <h2 class="h5 mb-0 fw-bold text-dark">
                {{ $trf->status === 'pushed_back' ? 'Edit & Resubmit' : 'Edit' }} {{ $trf->trf_number }}
              </h2>
              <div class="text-muted" style="font-size:13px;">Modify itinerary details, travel dates, advance, or logistics preferences.</div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="font-size:11px; font-weight:600;">{{ $trf->trf_number }}</span>
        </div>

        <form method="POST" action="{{ route('trf.update', $trf) }}" id="trfEditForm">
          @csrf
          @method('PUT')

          <!-- 1. Booking Type & Employee -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark mb-1.5" for="bookingTypeSelect">Booking Type <span class="text-danger">*</span></label>
              <select name="booking_type" class="form-select" id="bookingTypeSelect" required>
                <option value="self" {{ $selBooking === 'self' ? 'selected' : '' }}>Self (My Own Travel)</option>
                <option value="on_behalf" {{ $selBooking === 'on_behalf' ? 'selected' : '' }}>On Behalf Of (Admin / Executive)</option>
              </select>
            </div>

            <div class="col-md-6" id="employeeSelectWrap" style="{{ $selBooking === 'on_behalf' ? '' : 'display:none;' }}">
              <label class="form-label fw-semibold text-dark mb-1.5" for="employeeIdSelect">Traveling Employee <span class="text-danger">*</span></label>
              <select name="employee_id" id="employeeIdSelect" class="form-select">
                <option value="">Select traveling employee…</option>
                @foreach ($users as $user)
                  <option value="{{ $user->id }}" {{ old('employee_id', $trf->employee_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->roleLabel() }})</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- 2. Purpose & Travel Mode -->
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold text-dark mb-1.5" for="trfPurposeInput">Official Purpose <span class="text-danger">*</span></label>
              <input type="text" name="purpose" id="trfPurposeInput" class="form-control" value="{{ old('purpose', $trf->purpose) }}" placeholder="e.g. Annual Partner Summit / Site Inspection" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelModeSelect">Travel Mode <span class="text-danger">*</span></label>
              <select name="travel_mode" id="travelModeSelect" class="form-select" required>
                @foreach ($travelModes ?? \App\Http\Controllers\TrfController::TRAVEL_MODES as $mode)
                  <option value="{{ $mode }}" {{ old('travel_mode', $trf->travel_mode ?: 'Flight') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- 3. Travel Itinerary Route (From / To City + Optional Stopovers) -->
          <div class="mb-3 p-3 rounded-3 border bg-light-subtle" style="border-color:#e2e8f0 !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold text-dark" style="font-size:13px;"><i class="bi bi-signpost-split text-primary me-1"></i> Travel Itinerary Route</span>
              <button type="button" id="swapCitiesBtn" class="btn btn-link btn-sm text-decoration-none p-0 fw-medium" style="font-size:12px;">
                <i class="bi bi-arrow-left-right me-1"></i>Swap From / To
              </button>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <label class="form-label small fw-medium text-secondary mb-1" for="fromCitySelect">From City <span class="text-danger">*</span></label>
                <select name="from_city" id="fromCitySelect" class="form-select" required style="font-size:13px; text-overflow:ellipsis;">
                  <option value="">Select departure city…</option>
                  @if ($selFrom && ! in_array($selFrom, $allKnownCities, true))
                    <option value="{{ $selFrom }}" selected>{{ $selFrom }} (Existing)</option>
                  @endif
                  <optgroup label="Domestic (India)">
                    @foreach ($domesticCities as $city)
                      <option value="{{ $city }}" data-scope="domestic" {{ $selFrom === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                  <optgroup label="International">
                    @foreach ($internationalCities as $city)
                      <option value="{{ $city }}" data-scope="international" {{ $selFrom === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium text-secondary mb-1" for="toCitySelect">To City <span class="text-danger">*</span></label>
                <select name="to_city" id="toCitySelect" class="form-select" required style="font-size:13px; text-overflow:ellipsis;">
                  <option value="">Select destination city…</option>
                  @if ($selTo && ! in_array($selTo, $allKnownCities, true))
                    <option value="{{ $selTo }}" selected>{{ $selTo }} (Existing)</option>
                  @endif
                  <optgroup label="Domestic (India)">
                    @foreach ($domesticCities as $city)
                      <option value="{{ $city }}" data-scope="domestic" {{ $selTo === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                  <optgroup label="International">
                    @foreach ($internationalCities as $city)
                      <option value="{{ $city }}" data-scope="international" {{ $selTo === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                </select>
              </div>
            </div>

            <!-- Optional Additional Destinations -->
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
              <select id="extraStopSelect" class="form-select form-select-sm" style="max-width:260px; text-overflow:ellipsis;">
                <option value="">+ Add intermediate destination (optional)…</option>
                <optgroup label="Domestic (India)">
                  @foreach ($domesticCities as $city)
                    <option value="{{ $city }}" data-scope="domestic">{{ $city }}</option>
                  @endforeach
                </optgroup>
                <optgroup label="International">
                  @foreach ($internationalCities as $city)
                    <option value="{{ $city }}" data-scope="international">{{ $city }}</option>
                  @endforeach
                </optgroup>
              </select>
              <button type="button" id="addStopBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i> Add Stop
              </button>
              <span class="text-muted ms-auto" id="routePreviewHint" style="font-size:12px;">{{ $trf->route }}</span>
            </div>

            <div id="extraStopsChips" class="d-flex flex-wrap gap-1.5 mt-2"></div>

            <input type="hidden" name="route" id="routeHiddenInput" value="{{ old('route', $trf->route) }}">
            <input type="hidden" name="destinations" id="destinationsHiddenInput" value="{{ old('destinations', implode(', ', $extraStops ?? [])) }}">
          </div>

          <!-- 4. Dates & Advance -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelStartInput">Departure Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_start" id="travelStartInput" class="form-control" value="{{ old('travel_start', optional($trf->travel_start)->format('Y-m-d')) }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelEndInput">Return Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_end" id="travelEndInput" class="form-control" value="{{ old('travel_end', optional($trf->travel_end)->format('Y-m-d')) }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="requestedAdvanceInput">Requested Advance (₹)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="requested_advance" id="requestedAdvanceInput" class="form-control" step="0.01" min="0" value="{{ old('requested_advance', number_format((float)$trf->requested_advance, 2, '.', '')) }}" placeholder="0.00">
              </div>
            </div>
          </div>

          <!-- 5. Stay, Visa & Travel Preferences -->
          <div class="pt-3 mt-3 border-top">
            <label class="form-label fw-semibold text-dark mb-2">Stay, Visa &amp; Travel Preferences</label>
            <div class="p-3 rounded-3 border bg-light d-flex flex-wrap align-items-center gap-4 mb-3" style="border-color:#e2e8f0 !important;">
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="intlCheck" name="is_international" value="1" {{ old('is_international', $trf->is_international) ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="intlCheck" style="cursor:pointer; font-size:13px;">International Trip</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="accommodationCheck" name="accommodation" value="1" {{ old('accommodation', $trf->accommodation) ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="accommodationCheck" style="cursor:pointer; font-size:13px;">Accommodation Needed</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="visaCheck" name="visa_required" value="1" {{ old('visa_required', $trf->visa_required) ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="visaCheck" style="cursor:pointer; font-size:13px;">Visa Required</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="insuranceCheck" name="insurance" value="1" {{ old('insurance', $trf->insurance) ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="insuranceCheck" style="cursor:pointer; font-size:13px;">Travel Insurance</label>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6 {{ old('accommodation', $trf->accommodation) ? '' : 'd-none' }}" id="accommodationDetailsWrap">
                <label class="form-label fw-medium text-secondary small mb-1" for="accommodationDetailsInput">Accommodation / Stay Details</label>
                <input type="text" name="accommodation_details" id="accommodationDetailsInput" class="form-control form-control-sm" value="{{ old('accommodation_details', $trf->accommodation_details) }}" placeholder="e.g. 2 nights hotel near venue, single occupancy">
              </div>
              <div class="col-md-6 {{ old('visa_required', $trf->visa_required) ? '' : 'd-none' }}" id="visaDetailsWrap">
                <label class="form-label fw-medium text-secondary small mb-1" for="visaDetailsInput">Visa Requirement Notes</label>
                <input type="text" name="visa_details" id="visaDetailsInput" class="form-control form-control-sm" value="{{ old('visa_details', $trf->visa_details) }}" placeholder="e.g. Schengen Business Visa needed">
              </div>
            </div>
          </div>

          <!-- 6. Revision / Resubmission Notes -->
          <div class="pt-3 mt-3 border-top">
            <label class="form-label fw-semibold text-dark mb-1" for="resubmitNotesInput">
              <i class="bi bi-chat-left-text text-primary me-1"></i> Revision Notes for Approver (Optional)
            </label>
            <textarea name="resubmit_notes" id="resubmitNotesInput" rows="2" class="form-control form-control-sm" placeholder="e.g. Adjusted requested advance to ₹10,000 as advised and updated travel dates.">{{ old('resubmit_notes') }}</textarea>
          </div>

          <div class="pt-3 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('trf.show', $trf) }}" class="btn btn-light border px-4 fw-semibold text-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-arrow-repeat"></i>
              <span>{{ $trf->status === 'pushed_back' ? 'Save & Resubmit for Approval' : 'Update Travel Requisition' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Policy & Revision Context (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="card shadow-sm border-0 p-3">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <i class="bi bi-shield-check text-primary fs-5"></i>
            <span class="fw-bold text-dark" style="font-size: 14px;">Resubmission Routing</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Preserved Audit Trail:</strong> All prior review feedback remains logged on the requisition timeline.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Auto Re-Routing:</strong> Resubmitting routes to ASG 1/2 (Domestic) or Secretary General (International).</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'Edit Travel Requisition' });

(function () {
  const select = document.getElementById('bookingTypeSelect');
  const wrap = document.getElementById('employeeSelectWrap');
  const empSelect = document.getElementById('employeeIdSelect');
  function syncBookingType() {
    const onBehalf = select.value === 'on_behalf';
    wrap.style.display = onBehalf ? '' : 'none';
    if (empSelect) empSelect.required = onBehalf;
  }
  if (select) {
    select.addEventListener('change', syncBookingType);
    syncBookingType();
  }

  const fromSelect = document.getElementById('fromCitySelect');
  const toSelect = document.getElementById('toCitySelect');
  const routeHidden = document.getElementById('routeHiddenInput');
  const destHidden = document.getElementById('destinationsHiddenInput');
  const extraStopSelect = document.getElementById('extraStopSelect');
  const addStopBtn = document.getElementById('addStopBtn');
  const extraStopsChips = document.getElementById('extraStopsChips');
  const previewHint = document.getElementById('routePreviewHint');
  const swapBtn = document.getElementById('swapCitiesBtn');
  const intlCheck = document.getElementById('intlCheck');
  const visaCheck = document.getElementById('visaCheck');
  const accomCheck = document.getElementById('accommodationCheck');
  const accomWrap = document.getElementById('accommodationDetailsWrap');
  const visaWrap = document.getElementById('visaDetailsWrap');
  const startInput = document.getElementById('travelStartInput');
  const endInput = document.getElementById('travelEndInput');

  if (startInput && endInput) {
    startInput.addEventListener('change', () => {
      if (startInput.value) {
        endInput.min = startInput.value;
        if (endInput.value && endInput.value < startInput.value) {
          endInput.value = startInput.value;
        }
      }
    });
  }

  function syncConditionalInputs() {
    if (accomWrap && accomCheck) {
      accomWrap.classList.toggle('d-none', !accomCheck.checked);
    }
    if (visaWrap && visaCheck) {
      visaWrap.classList.toggle('d-none', !visaCheck.checked);
    }
  }
  if (accomCheck) accomCheck.addEventListener('change', syncConditionalInputs);
  if (visaCheck) visaCheck.addEventListener('change', () => {
    visaCheck.dataset.touched = '1';
    syncConditionalInputs();
  });

  let extraStops = [];
  if (destHidden && destHidden.value) {
    extraStops = destHidden.value.split(',').map(s => s.trim()).filter(Boolean);
  }

  function renderChips() {
    if (!extraStopsChips) return;
    extraStopsChips.innerHTML = '';
    extraStops.forEach((city, idx) => {
      const chip = document.createElement('span');
      chip.className = 'badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1.5 px-2 py-1';
      chip.style.fontSize = '11.5px';
      chip.innerHTML = `<span>${city}</span><button type="button" class="btn-close" style="font-size:8px;" aria-label="Remove"></button>`;
      chip.querySelector('button').addEventListener('click', () => {
        extraStops.splice(idx, 1);
        renderChips();
        syncRoute(true);
      });
      extraStopsChips.appendChild(chip);
    });
  }

  function syncRoute(autoToggleIntl = false) {
    if (!fromSelect || !toSelect || !routeHidden) return;
    const fromVal = fromSelect.value;
    const toVal = toSelect.value;

    const filteredStops = extraStops.filter(s => s !== fromVal && s !== toVal);
    if (destHidden) {
      const allDests = [];
      if (toVal) allDests.push(toVal);
      filteredStops.forEach(s => { if (!allDests.includes(s)) allDests.push(s); });
      destHidden.value = allDests.join(', ');
    }

    if (fromVal && toVal) {
      const parts = [fromVal, ...filteredStops, toVal];
      const routeText = parts.join(' → ');
      routeHidden.value = routeText;
      if (previewHint) {
        previewHint.innerHTML = `<strong class="text-primary">${routeText}</strong>`;
      }
    } else if (autoToggleIntl) {
      routeHidden.value = '';
      if (previewHint) {
        previewHint.textContent = 'Select departure & destination cities';
      }
    }

    if (autoToggleIntl && intlCheck) {
      const fromScope = fromSelect.options[fromSelect.selectedIndex]?.dataset?.scope;
      const toScope = toSelect.options[toSelect.selectedIndex]?.dataset?.scope;
      const anyIntl = fromScope === 'international' || toScope === 'international';
      if (anyIntl) {
        intlCheck.checked = true;
        if (visaCheck && !visaCheck.dataset.touched) visaCheck.checked = true;
      } else if (fromScope === 'domestic' && toScope === 'domestic' && filteredStops.length === 0) {
        intlCheck.checked = false;
      }
      syncConditionalInputs();
    }
  }

  if (addStopBtn && extraStopSelect) {
    addStopBtn.addEventListener('click', () => {
      const val = extraStopSelect.value;
      if (!val || extraStops.includes(val) || val === fromSelect?.value || val === toSelect?.value) return;
      extraStops.push(val);
      extraStopSelect.value = '';
      renderChips();
      syncRoute(true);
    });
  }

  if (fromSelect && toSelect) {
    fromSelect.addEventListener('change', () => syncRoute(true));
    toSelect.addEventListener('change', () => syncRoute(true));
    if (swapBtn) {
      swapBtn.addEventListener('click', () => {
        const tmp = fromSelect.value;
        fromSelect.value = toSelect.value;
        toSelect.value = tmp;
        syncRoute(true);
      });
    }
    renderChips();
    syncRoute(false);
  }
  syncConditionalInputs();
})();
@endsection

