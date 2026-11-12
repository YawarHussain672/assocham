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
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-airplane"></i>
            </div>
            <div>
              <h2 class="h5 mb-0 fw-bold text-dark">Create Travel Requisition (TRF)</h2>
              <div class="text-muted" style="font-size:13px;">Initiate domestic or international official travel and request advance funds.</div>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:11px; font-weight:600; letter-spacing:0.3px;">OFFICIAL TRAVEL</span>
        </div>

        @php
          $initialBookingType = old('booking_type', 'self');
          $initialEmpId = $initialBookingType === 'on_behalf' ? old('employee_id') : auth()->id();
          $initialOverdue = $initialEmpId ? ($overdueDetails[$initialEmpId] ?? null) : null;
        @endphp

        <!-- Overdue Unsettled Advance Block Banner -->
        <div id="overdueAdvanceBanner" class="alert border-0 shadow-xs mb-4 p-3.5 rounded-3 {{ $initialOverdue ? '' : 'd-none' }}" style="background:#FEF2F2; border-left:4px solid #DC2626 !important;">
          <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px; height:38px; background:#FEE2E2; color:#B91C1C;">
                <i class="bi bi-shield-lock-fill fs-5"></i>
              </div>
              <div>
                <div class="fw-bold text-danger" style="font-size:14px;">Travel Creation Blocked — Overdue Unsettled Advance</div>
                <div class="text-dark mt-1" style="font-size:13px; line-height:1.45;" id="overdueBannerBody">
                  @if ($initialOverdue)
                    <strong>{{ $initialOverdue['employee_name'] }}</strong> has an unsettled travel advance (<strong>{{ $initialOverdue['advance_formatted'] }}</strong>) from <a href="{{ $initialOverdue['trf_url'] }}" class="fw-semibold text-danger text-decoration-underline">{{ $initialOverdue['trf_number'] }}</a> ({{ $initialOverdue['route'] }}, ended {{ $initialOverdue['travel_end'] }}, {{ $initialOverdue['days_overdue'] }} days ago). {{ $initialOverdue['reimb_status_text'] }}
                  @endif
                </div>
              </div>
            </div>
            <div class="flex-shrink-0">
              <a href="{{ $initialOverdue ? ($initialOverdue['employee_id'] === auth()->id() ? $initialOverdue['reimb_url'] : $initialOverdue['trf_url']) : '#' }}" id="overdueActionBtn" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1.5 fw-semibold text-nowrap px-3 py-1.5">
                <i class="bi bi-receipt-cutoff"></i>
                <span id="overdueActionLabel">{{ $initialOverdue && $initialOverdue['employee_id'] === auth()->id() ? 'Reconcile via Reimbursement' : 'View Overdue TRF' }}</span>
              </a>
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('trf.store') }}" id="trfForm">
          @csrf

          <!-- 1. Booking Type & Employee -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark mb-1.5" for="bookingTypeSelect">Booking Type <span class="text-danger">*</span></label>
              <select name="booking_type" class="form-select" id="bookingTypeSelect" required>
                <option value="self" {{ old('booking_type', 'self') === 'self' ? 'selected' : '' }}>Self (My Own Travel)</option>
                <option value="on_behalf" {{ old('booking_type') === 'on_behalf' ? 'selected' : '' }}>On Behalf Of (Admin / Executive)</option>
              </select>
            </div>

            <div class="col-md-6" id="employeeSelectWrap" style="display:none;">
              <label class="form-label fw-semibold text-dark mb-1.5" for="employeeIdSelect">Traveling Employee <span class="text-danger">*</span></label>
              <select name="employee_id" id="employeeIdSelect" class="form-select">
                <option value="">Select traveling employee…</option>
                @foreach ($users as $user)
                  @php $empOverdue = $overdueDetails[$user->id] ?? null; @endphp
                  <option value="{{ $user->id }}" {{ old('employee_id') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->roleLabel() }}){{ $empOverdue ? ' — [Blocked: Overdue Advance]' : '' }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- 2. Purpose & Travel Mode -->
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold text-dark mb-1.5" for="trfPurposeInput">Official Purpose <span class="text-danger">*</span></label>
              <input type="text" name="purpose" id="trfPurposeInput" class="form-control" value="{{ old('purpose') }}" placeholder="e.g. Annual Partner Summit / Site Inspection" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelModeSelect">Travel Mode <span class="text-danger">*</span></label>
              <select name="travel_mode" id="travelModeSelect" class="form-select" required>
                @foreach ($travelModes ?? \App\Http\Controllers\TrfController::TRAVEL_MODES as $mode)
                  <option value="{{ $mode }}" {{ old('travel_mode', 'Flight') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
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
                  <optgroup label="Domestic (India)">
                    @foreach ($domesticCities ?? \App\Http\Controllers\TrfController::DOMESTIC_CITIES as $city)
                      <option value="{{ $city }}" data-scope="domestic" {{ old('from_city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                  <optgroup label="International">
                    @foreach ($internationalCities ?? \App\Http\Controllers\TrfController::INTERNATIONAL_CITIES as $city)
                      <option value="{{ $city }}" data-scope="international" {{ old('from_city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium text-secondary mb-1" for="toCitySelect">To City <span class="text-danger">*</span></label>
                <select name="to_city" id="toCitySelect" class="form-select" required style="font-size:13px; text-overflow:ellipsis;">
                  <option value="">Select destination city…</option>
                  <optgroup label="Domestic (India)">
                    @foreach ($domesticCities ?? \App\Http\Controllers\TrfController::DOMESTIC_CITIES as $city)
                      <option value="{{ $city }}" data-scope="domestic" {{ old('to_city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                  </optgroup>
                  <optgroup label="International">
                    @foreach ($internationalCities ?? \App\Http\Controllers\TrfController::INTERNATIONAL_CITIES as $city)
                      <option value="{{ $city }}" data-scope="international" {{ old('to_city') === $city ? 'selected' : '' }}>{{ $city }}</option>
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
                  @foreach ($domesticCities ?? \App\Http\Controllers\TrfController::DOMESTIC_CITIES as $city)
                    <option value="{{ $city }}" data-scope="domestic">{{ $city }}</option>
                  @endforeach
                </optgroup>
                <optgroup label="International">
                  @foreach ($internationalCities ?? \App\Http\Controllers\TrfController::INTERNATIONAL_CITIES as $city)
                    <option value="{{ $city }}" data-scope="international">{{ $city }}</option>
                  @endforeach
                </optgroup>
              </select>
              <button type="button" id="addStopBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i> Add Stop
              </button>
              <span class="text-muted ms-auto" id="routePreviewHint" style="font-size:12px;">Select departure &amp; destination cities</span>
            </div>

            <div id="extraStopsChips" class="d-flex flex-wrap gap-1.5 mt-2"></div>

            <input type="hidden" name="route" id="routeHiddenInput" value="{{ old('route') }}">
            <input type="hidden" name="destinations" id="destinationsHiddenInput" value="{{ old('destinations') }}">
          </div>

          <!-- 4. Dates & Advance -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelStartInput">Departure Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_start" id="travelStartInput" class="form-control" value="{{ old('travel_start') }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="travelEndInput">Return Date <span class="text-danger">*</span></label>
              <input type="date" name="travel_end" id="travelEndInput" class="form-control" value="{{ old('travel_end') }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1.5" for="requestedAdvanceInput">Requested Advance (₹)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="requested_advance" id="requestedAdvanceInput" class="form-control" step="0.01" min="0" value="{{ old('requested_advance', '0.00') }}" placeholder="0.00">
              </div>
            </div>
          </div>

          <!-- 5. Stay, Visa & Travel Preferences -->
          <div class="pt-3 mt-3 border-top">
            <label class="form-label fw-semibold text-dark mb-2">Stay, Visa &amp; Travel Preferences</label>
            <div class="p-3 rounded-3 border bg-light d-flex flex-wrap align-items-center gap-4 mb-3" style="border-color:#e2e8f0 !important;">
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="intlCheck" name="is_international" value="1" {{ old('is_international') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="intlCheck" style="cursor:pointer; font-size:13px;">International Trip</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="accommodationCheck" name="accommodation" value="1" {{ old('accommodation') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="accommodationCheck" style="cursor:pointer; font-size:13px;">Accommodation Needed</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="visaCheck" name="visa_required" value="1" {{ old('visa_required') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="visaCheck" style="cursor:pointer; font-size:13px;">Visa Required</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="insuranceCheck" name="insurance" value="1" {{ old('insurance') ? 'checked' : '' }} role="switch" style="cursor:pointer;">
                <label class="form-check-label fw-medium text-dark ms-1" for="insuranceCheck" style="cursor:pointer; font-size:13px;">Travel Insurance</label>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6 {{ old('accommodation') ? '' : 'd-none' }}" id="accommodationDetailsWrap">
                <label class="form-label fw-medium text-secondary small mb-1" for="accommodationDetailsInput">Accommodation / Stay Details</label>
                <input type="text" name="accommodation_details" id="accommodationDetailsInput" class="form-control form-control-sm" value="{{ old('accommodation_details') }}" placeholder="e.g. 2 nights hotel near venue, single occupancy">
              </div>
              <div class="col-md-6 {{ old('visa_required') ? '' : 'd-none' }}" id="visaDetailsWrap">
                <label class="form-label fw-medium text-secondary small mb-1" for="visaDetailsInput">Visa Requirement Notes</label>
                <input type="text" name="visa_details" id="visaDetailsInput" class="form-control form-control-sm" value="{{ old('visa_details') }}" placeholder="e.g. Schengen Business Visa needed">
              </div>
            </div>
          </div>

          <div class="pt-3 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('trf') }}" class="btn btn-light border px-4 fw-semibold text-secondary">Cancel</a>
            <button type="submit" id="submitTrfBtn" class="btn {{ $initialOverdue ? 'btn-secondary' : 'btn-primary' }} d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold" {{ $initialOverdue ? 'disabled' : '' }}>
              <i class="bi {{ $initialOverdue ? 'bi-lock-fill' : 'bi-send-check' }}" id="submitTrfIcon"></i>
              <span id="submitTrfLabel">{{ $initialOverdue ? 'Blocked — Reconcile Overdue Advance' : 'Submit Travel Requisition' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Structured Policy & Governance Guide (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">

        <div class="card shadow-sm border-0 p-3">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <i class="bi bi-shield-check text-primary fs-5"></i>
            <span class="fw-bold text-dark" style="font-size: 14px;">Travel Policy &amp; Routing</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Domestic Trips:</strong> Routed to ASG 1 / ASG 2 for single-tier departmental clearance.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>International &amp; Visa:</strong> Auto-routes directly to the Secretary General (SG) for approval.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Advance Settlement:</strong> File reimbursement claim within 7 working days of trip end date (blocked past 30 days).</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
              <span><strong>Bookings &amp; Vouchers:</strong> Approved TRFs unlock ticket/stay logging through empanelled travel vendors.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
            <i class="bi bi-diagram-2 text-primary"></i>
            <span>Procurement Pipeline Stage</span>
          </div>
          <div class="text-muted">Submit your itinerary and stay/visa needs for executive sanction prior to travel booking.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'New Travel Requisition' });

(function () {
  const overdueByEmployee = @json($overdueDetails ?? []);
  const currentUserId = {{ auth()->id() ?? 'null' }};

  const select = document.getElementById('bookingTypeSelect');
  const wrap = document.getElementById('employeeSelectWrap');
  const empSelect = document.getElementById('employeeIdSelect');
  const overdueBanner = document.getElementById('overdueAdvanceBanner');
  const overdueBody = document.getElementById('overdueBannerBody');
  const overdueActionBtn = document.getElementById('overdueActionBtn');
  const overdueActionLabel = document.getElementById('overdueActionLabel');
  const submitBtn = document.getElementById('submitTrfBtn');
  const submitIcon = document.getElementById('submitTrfIcon');
  const submitLabel = document.getElementById('submitTrfLabel');

  function syncOverdueBlock() {
    if (!overdueBanner || !submitBtn) return;
    const onBehalf = select && select.value === 'on_behalf';
    const activeId = onBehalf ? (empSelect ? empSelect.value : '') : String(currentUserId || '');
    const info = activeId ? overdueByEmployee[activeId] : null;

    if (info) {
      overdueBody.textContent = '';
      const strongEmp = document.createElement('strong');
      strongEmp.textContent = info.employee_name;
      overdueBody.appendChild(strongEmp);
      overdueBody.append(' has an unsettled travel advance (');

      const strongAdv = document.createElement('strong');
      strongAdv.textContent = info.advance_formatted;
      overdueBody.appendChild(strongAdv);
      overdueBody.append(') from ');

      const link = document.createElement('a');
      link.href = info.trf_url;
      link.className = 'fw-semibold text-danger text-decoration-underline';
      link.textContent = info.trf_number;
      overdueBody.appendChild(link);
      overdueBody.append(` (${info.route}, ended ${info.travel_end}, ${info.days_overdue} days ago). ${info.reimb_status_text}`);

      const isSelf = Number(info.employee_id) === Number(currentUserId);
      overdueActionBtn.href = isSelf ? info.reimb_url : info.trf_url;
      overdueActionLabel.textContent = isSelf ? 'Reconcile via Reimbursement' : 'View Overdue TRF';

      overdueBanner.classList.remove('d-none');
      submitBtn.disabled = true;
      submitBtn.classList.remove('btn-primary');
      submitBtn.classList.add('btn-secondary');
      if (submitIcon) submitIcon.className = 'bi bi-lock-fill';
      if (submitLabel) submitLabel.textContent = 'Blocked — Reconcile Overdue Advance';
    } else {
      overdueBanner.classList.add('d-none');
      submitBtn.disabled = false;
      submitBtn.classList.remove('btn-secondary');
      submitBtn.classList.add('btn-primary');
      if (submitIcon) submitIcon.className = 'bi bi-send-check';
      if (submitLabel) submitLabel.textContent = 'Submit Travel Requisition';
    }
  }

  function syncBookingType() {
    const onBehalf = select.value === 'on_behalf';
    wrap.style.display = onBehalf ? '' : 'none';
    if (empSelect) empSelect.required = onBehalf;
    syncOverdueBlock();
  }
  select.addEventListener('change', syncBookingType);
  if (empSelect) empSelect.addEventListener('change', syncOverdueBlock);
  syncBookingType();

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
    } else {
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
