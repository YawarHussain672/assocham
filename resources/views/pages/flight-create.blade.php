@extends('layouts.app')

@section('title', 'Log Flight / Hotel Transaction')

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

  <div class="row g-4 align-items-start">
    <!-- Left: Tight Natural-Height Booking Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 pb-3 mb-3 border-bottom">
          <div>
            <h1 class="h5 mb-1 fw-bold text-dark">Log Flight / Hotel Transaction</h1>
            <div class="text-muted" style="font-size:13px;">Attach travel tickets, hotel vouchers, and invoice costs to an approved TRF.</div>
          </div>
          <span class="text-muted fw-semibold text-uppercase" style="font-size:11px; letter-spacing:0.6px;">Logistics Entry</span>
        </div>

        @if ($trfs->isEmpty())
          <div class="p-4 rounded-3 text-center bg-light border border-dashed">
            <i class="bi bi-airplane text-muted" style="font-size:32px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:15px;">No Approved TRFs Available</div>
            <div class="text-muted mt-1 small mx-auto" style="max-width:460px;">
              Transactions can only be logged against an approved Travel Requisition (TRF). Ensure approval by ASG or Secretary General before booking.
            </div>
            <a href="{{ route('trf') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 fw-semibold">
              <i class="bi bi-calendar2-check"></i>
              <span>View Travel Requisitions</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('flight.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Row 1: Approved TRF & Booking Type -->
            <div class="row g-3 mb-3">
              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold text-dark mb-1" for="trfSelect">
                  Approved Travel Requisition (TRF) <span class="text-danger">*</span>
                </label>
                <select name="trf_id" id="trfSelect" class="form-select @error('trf_id') is-invalid @enderror" required>
                  <option value="">Select approved TRF…</option>
                  @foreach ($trfs as $trf)
                    <option value="{{ $trf->id }}" {{ (string) old('trf_id', request('trf_id')) === (string) $trf->id ? 'selected' : '' }}>
                      {{ $trf->trf_number }} — {{ $trf->employee->name }} ({{ $trf->route ?: 'Route not specified' }})
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="typeSelect">
                  Booking Type <span class="text-danger">*</span>
                </label>
                <select name="type" id="typeSelect" class="form-select @error('type') is-invalid @enderror" required>
                  <option value="Flight ticket" {{ old('type') === 'Flight ticket' ? 'selected' : '' }}>Flight ticket</option>
                  <option value="Hotel" {{ old('type') === 'Hotel' ? 'selected' : '' }}>Hotel</option>
                  <option value="Transport" {{ old('type') === 'Transport' ? 'selected' : '' }}>Local Transport</option>
                  <option value="Other" {{ old('type') === 'Other' ? 'selected' : '' }}>Other Logistics</option>
                </select>
              </div>
            </div>

            <!-- Dynamic Selected TRF Summary Strip -->
            <div id="trfSummaryNotice" class="d-none mb-3"></div>

            <!-- Row 2: Empanelled Vendor & Invoice Cost -->
            <div class="row g-3 mb-3">
              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold text-dark mb-1" for="vendorSelect">
                  Empanelled Vendor <span class="text-muted fw-normal" style="font-size:12px;">(best rated first)</span>
                </label>
                <select name="vendor_id" id="vendorSelect" class="form-select @error('vendor_id') is-invalid @enderror">
                  <option value="">None / Direct Booking</option>
                  @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                      @if ($vendor->effective_rating !== null)
                        {{ $vendor->company_name }} (★ {{ number_format($vendor->effective_rating, 1) }}/5 — {{ $vendor->performance_tier }})
                      @else
                        {{ $vendor->company_name }} (Unrated)
                      @endif
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="costInput">
                  Invoice Cost (₹) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="cost" id="costInput" class="form-control @error('cost') is-invalid @enderror" step="0.01" min="0" value="{{ old('cost') }}" placeholder="0.00" required>
                </div>
              </div>
            </div>

            <!-- Dynamic Vendor Performance Advisory Strip -->
            <div id="vendorRatingNotice" class="d-none mb-3"></div>

            <!-- Row 3: Proof Upload -->
            <div class="mb-3">
              <label class="form-label fw-semibold text-dark mb-1" for="proofFile">
                Ticket / Voucher / Boarding Pass Proof <span class="text-muted fw-normal" style="font-size:12px;">(PDF, JPG, PNG up to 5 MB for claim audit)</span>
              </label>
              <input type="file" name="proof" id="proofFile" class="form-control @error('proof') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <!-- Compact Footer -->
            <div class="pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div class="text-muted" style="font-size:12px;">
                <i class="bi bi-shield-check text-primary me-1"></i>
                1:1 TRF mapping · Completed trips unlock partner ratings
              </div>
              <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="{{ route('flight') }}" class="btn btn-light border px-3 fw-semibold text-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 fw-semibold">
                  <i class="bi bi-check2-circle"></i>
                  <span>Log Transaction</span>
                </button>
              </div>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Top 3 Recommended Partners Panel (col-lg-4) -->
    <div class="col-12 col-lg-4">
      @php
        $lowPerformingVendors = $vendors->filter(fn ($v) => $v->effective_rating !== null && $v->effective_rating < 3.0);
        $featuredVendors = $vendors->take(3);
      @endphp

      <div class="card card-panel shadow-sm border-0 p-0 overflow-hidden">
        <div class="bg-white d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
          <div>
            <h2 class="h6 mb-0 fw-bold text-dark" style="font-size:14px;">Recommended Partners</h2>
            <div class="text-muted" style="font-size:11px;">Best rated first · Avoid &lt; 3.0</div>
          </div>
          <a href="{{ route('traveleval') }}" class="text-decoration-none fw-semibold text-primary" style="font-size:12px;">Leaderboard</a>
        </div>

        @if ($lowPerformingVendors->isNotEmpty())
          <div class="px-4 py-2 border-bottom d-flex align-items-start gap-2" style="background:#FEF2F2; color:#991B1B; font-size:12px; line-height:1.4;">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="margin-top:2px;"></i>
            <span><strong>{{ $lowPerformingVendors->count() }} {{ Str::plural('partner', $lowPerformingVendors->count()) }}</strong> rated below 3.0/5.0 — prefer higher-rated partners.</span>
          </div>
        @endif

        <div class="list-group list-group-flush">
          @forelse ($featuredVendors as $index => $vendor)
            @php
              $rank = $index + 1;
              $isTopPick = $rank === 1 && $vendor->effective_rating !== null && $vendor->effective_rating >= 4.0;
              $tierStyle = match ($vendor->performance_tier_key) {
                'preferred' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0'],
                'satisfactory' => ['bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE'],
                'caution' => ['bg' => '#FFFBEB', 'color' => '#B45309', 'border' => '#FDE68A'],
                'low' => ['bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA'],
                default => ['bg' => '#F1F5F9', 'color' => '#475569', 'border' => '#E2E8F0'],
              };
            @endphp
            <div class="list-group-item px-4 py-2 vendor-guide-row border-light-subtle" data-vendor-card="{{ $vendor->id }}" style="{{ $vendor->performance_tier_key === 'low' ? 'background:#FFFBFB;' : ($isTopPick ? 'background:#FAFFFD;' : '') }}">
              <div class="d-flex justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2" style="min-width:0;">
                  <span class="fw-bold {{ $isTopPick ? 'text-success' : 'text-muted' }}" style="font-size:12px;">#{{ $rank }}</span>
                  <span class="fw-semibold text-dark" style="font-size:13px; line-height:1.35;">{{ $vendor->company_name }}</span>
                </div>
                <span class="badge flex-shrink-0 fw-semibold px-2 py-1" style="font-size:11px; background:{{ $tierStyle['bg'] }}; color:{{ $tierStyle['color'] }}; border:1px solid {{ $tierStyle['border'] }};">
                  @if ($vendor->effective_rating !== null)
                    ★ {{ number_format($vendor->effective_rating, 1) }} · {{ $vendor->performance_tier }}
                  @else
                    Unrated
                  @endif
                </span>
              </div>

              @if ($vendor->evaluation_count > 0 && !empty($vendor->criteria_summary))
                <div class="text-muted mt-1" style="font-size:11px;">
                  {{ $vendor->evaluation_count }} {{ Str::plural('trip', $vendor->evaluation_count) }} ·
                  Cost {{ number_format($vendor->criteria_summary['Cost'], 1) }} ·
                  Time {{ number_format($vendor->criteria_summary['Timeliness'], 1) }} ·
                  Svc {{ number_format($vendor->criteria_summary['Service Quality'], 1) }} ·
                  Resp {{ number_format($vendor->criteria_summary['Responsiveness'], 1) }}
                </div>
              @else
                <div class="text-muted mt-1" style="font-size:11px;">
                  {{ $vendor->vendor_code }}
                </div>
              @endif

              <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                <a href="{{ route('traveleval.vendor-history', $vendor) }}" class="text-decoration-none fw-semibold text-secondary" style="font-size:11px;">
                  History →
                </a>
                @if (!$trfs->isEmpty())
                  <button type="button"
                    class="btn btn-sm py-0 px-2 fw-semibold {{ $vendor->performance_tier_key === 'low' ? 'btn-outline-danger' : 'btn-outline-primary' }}"
                    data-select-vendor="{{ $vendor->id }}"
                    data-default-label="{{ $vendor->performance_tier_key === 'low' ? 'Select Anyway' : 'Use Partner' }}"
                    data-default-class="{{ $vendor->performance_tier_key === 'low' ? 'btn-outline-danger' : 'btn-outline-primary' }}"
                    style="font-size:11px; line-height:1.7;">
                    {{ $vendor->performance_tier_key === 'low' ? 'Select Anyway' : 'Use Partner' }}
                  </button>
                @endif
              </div>
            </div>
          @empty
            <div class="list-group-item px-4 py-3 text-muted small text-center">No empanelled travel vendors configured.</div>
          @endforelse
        </div>

        <div class="bg-light px-4 py-2 border-top d-flex align-items-center justify-content-between" style="font-size:12px;">
          <span class="text-muted">Top {{ $featuredVendors->count() }} of {{ $vendors->count() }}</span>
          <a href="{{ route('traveleval') }}" class="text-decoration-none fw-semibold text-primary">
            All Partners →
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Upload Transaction' });

(function () {
  const trfSummaries = @json($trfSummaries ?? []);
  const vendorInsights = @json($vendorInsights ?? []);
  const trfSelect = document.getElementById('trfSelect');
  const trfNoticeEl = document.getElementById('trfSummaryNotice');
  const vendorSelect = document.getElementById('vendorSelect');
  const noticeEl = document.getElementById('vendorRatingNotice');

  function makeEl(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined && text !== null) el.textContent = text;
    return el;
  }

  function renderTrfNotice(trfId) {
    if (!trfNoticeEl) return;
    const trf = trfSummaries[String(trfId)];
    if (!trfId || !trf) {
      trfNoticeEl.className = 'd-none mb-3';
      trfNoticeEl.replaceChildren();
      return;
    }

    trfNoticeEl.className = 'mb-3 rounded-2 px-3 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2';
    trfNoticeEl.style.background = '#F8FAFC';
    trfNoticeEl.style.borderLeft = '3px solid #64748B';
    trfNoticeEl.style.color = '#334155';

    const meta = [trf.trf_number, trf.scope, trf.mode, trf.dates, trf.purpose].filter(Boolean).join(' · ');
    trfNoticeEl.replaceChildren(
      makeEl('div', 'small', meta),
      Object.assign(makeEl('a', 'small fw-semibold text-decoration-none text-primary', 'View TRF →'), { href: trf.url })
    );
  }

  function renderVendorNotice(vendorId) {
    if (!noticeEl) return;

    document.querySelectorAll('.vendor-guide-row').forEach((row) => {
      const isSelected = String(row.dataset.vendorCard) === String(vendorId);
      row.style.background = isSelected ? '#EFF6FF' : '';
    });

    document.querySelectorAll('[data-select-vendor]').forEach((btn) => {
      const isSelected = String(btn.dataset.selectVendor) === String(vendorId);
      if (isSelected) {
        btn.className = 'btn btn-sm py-0 px-2 fw-semibold btn-primary';
        btn.textContent = 'Selected';
      } else {
        btn.className = 'btn btn-sm py-0 px-2 fw-semibold ' + btn.dataset.defaultClass;
        btn.textContent = btn.dataset.defaultLabel;
      }
    });

    const info = vendorInsights[String(vendorId)];
    if (!vendorId || !info) {
      noticeEl.className = 'd-none mb-3';
      noticeEl.replaceChildren();
      return;
    }

    const palette = {
      low: { bg: '#FEF2F2', accent: '#DC2626', text: '#991B1B', label: 'Low Performing Partner — Avoid If Possible' },
      caution: { bg: '#FFFBEB', accent: '#D97706', text: '#92400E', label: 'Caution — Below Target Benchmark' },
      preferred: { bg: '#ECFDF5', accent: '#059669', text: '#065F46', label: 'Preferred Partner — Recommended' },
      satisfactory: { bg: '#EFF6FF', accent: '#2563EB', text: '#1E40AF', label: 'Satisfactory Partner' },
      unrated: { bg: '#F8FAFC', accent: '#94A3B8', text: '#475569', label: 'Not Yet Evaluated' },
    }[info.tier_key || 'unrated'];

    noticeEl.className = 'mb-3 rounded-2 px-3 py-2';
    noticeEl.style.background = palette.bg;
    noticeEl.style.borderLeft = '3px solid ' + palette.accent;
    noticeEl.style.color = palette.text;

    const topRow = makeEl('div', 'd-flex flex-wrap align-items-center justify-content-between gap-2');
    const summaryLeft = makeEl('div', 'd-flex flex-wrap align-items-center gap-2 small');
    summaryLeft.appendChild(makeEl('strong', null, palette.label));

    if (info.rating !== null) {
      const scoreText = '· ★ ' + Number(info.rating).toFixed(1) + '/5' + (info.trips > 0 ? ' (' + info.trips + (info.trips === 1 ? ' trip' : ' trips') + ')' : '');
      summaryLeft.appendChild(makeEl('span', 'fw-semibold', scoreText));
    }

    if (info.trips > 0 && info.cost !== null) {
      const parts = [
        'Cost ' + Number(info.cost).toFixed(1),
        'Time ' + Number(info.time).toFixed(1),
        'Svc ' + Number(info.quality).toFixed(1),
        'Resp ' + Number(info.responsiveness).toFixed(1),
      ].join(' · ');
      summaryLeft.appendChild(makeEl('span', null, '· ' + parts));
    }

    const historyLink = makeEl('a', 'fw-semibold text-decoration-none small', 'View History →');
    historyLink.href = info.history_url;
    historyLink.style.color = palette.text;

    topRow.appendChild(summaryLeft);
    topRow.appendChild(historyLink);
    noticeEl.replaceChildren(topRow);

    if (info.tier_key === 'low' || info.tier_key === 'caution') {
      const weakNote = info.weak_areas && info.weak_areas.length ? ' Weakest areas: ' + info.weak_areas.join(', ') + '.' : '';
      const msg = info.tier_key === 'low'
        ? 'Past TRF evaluations scored below 3.0/5.0.' + weakNote + ' Prefer a higher-rated partner before confirming.'
        : 'Borderline historical performance (3.0–3.4).' + weakNote;
      noticeEl.appendChild(makeEl('div', 'small mt-1', msg));
    }
  }

  if (trfSelect) {
    trfSelect.addEventListener('change', () => renderTrfNotice(trfSelect.value));
    if (trfSelect.value) {
      renderTrfNotice(trfSelect.value);
    }
  }

  if (vendorSelect) {
    vendorSelect.addEventListener('change', () => renderVendorNotice(vendorSelect.value));
    if (vendorSelect.value) {
      renderVendorNotice(vendorSelect.value);
    }
  }

  document.querySelectorAll('[data-select-vendor]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!vendorSelect) return;
      vendorSelect.value = btn.dataset.selectVendor;
      vendorSelect.dispatchEvent(new Event('change'));
      vendorSelect.focus();
    });
  });
})();
@endsection
