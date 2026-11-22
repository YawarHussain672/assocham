@extends('layouts.app')

@section('title', $vendor->company_name . ' — Performance History')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Vendor Evaluations</span>
  </a>

  <!-- Executive Header Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0 d-flex align-items-center justify-content-center bg-primary-subtle text-primary border border-primary-subtle" style="width:46px; height:46px; border-radius:10px;">
            <i class="bi bi-graph-up-arrow fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.3rem; line-height: 1.2;">{{ $vendor->company_name }}</span>
              <span class="badge d-inline-flex align-items-center gap-1" style="background:#E6F3EC; color:#2E7D4F; font-size:11.5px; font-weight:600; padding:4px 9px; border-radius:6px;">
                <i class="bi bi-star-fill text-warning"></i> {{ $count > 0 ? $average . ' / 5.0' : 'No Ratings' }}
              </span>
              <span class="badge d-inline-flex align-items-center gap-1" style="background:{{ $trend['bg'] }}; color:{{ $trend['fg'] }}; font-size:11.5px; font-weight:600; padding:4px 9px; border-radius:6px;">
                <i class="bi {{ $trend['icon'] }}"></i>
                <span>{{ $trend['label'] }}@if ($count >= 2 && $trend['delta'] != 0) ({{ $trend['formatted_delta'] }})@endif</span>
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
              <span class="detail-meta-chip"><i class="bi bi-upc-scan"></i> <span class="meta-label">Code:</span> <strong>{{ $vendor->vendor_code }}</strong></span>
              <span class="detail-meta-chip"><i class="bi bi-tag"></i> <span class="meta-label">Category:</span> <strong>{{ $vendor->category ?: 'Travel Partner' }}</strong></span>
              <span class="detail-meta-chip"><i class="bi bi-check2-circle"></i> <span class="meta-label">Trips Evaluated:</span> <strong>{{ $count }} {{ Str::plural('completed trip', $count) }}</strong></span>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <a href="{{ route('empanelment.show', $vendor) }}" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold">
            Vendor Profile
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Historical KPI Strip -->
  <div class="row g-2 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Average Score</span>
            <div class="stat-icon-box icon-primary" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-star-fill"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $average }} <small class="text-muted" style="font-size: 0.85rem;">/ 5.0</small></div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Cumulative partner rating</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Rating Trend</span>
            <div class="stat-icon-box icon-success" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi {{ $trend['icon'] }}"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.25rem; font-weight: 700; color:{{ $trend['fg'] }};">{{ $trend['label'] }}</div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">
              @if ($count >= 2)
                Delta vs prior trips: {{ $trend['formatted_delta'] }}
              @else
                Needs 2+ trips for trend
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Trips Evaluated</span>
            <div class="stat-icon-box icon-warning" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-airplane"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $count }}</div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Completed official trips</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Booked Spend</span>
            <div class="stat-icon-box icon-secondary" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">₹{{ number_format($totalSpend, 2) }}</div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Across {{ $bookingsCount }} {{ Str::plural('booking', $bookingsCount) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Trend Chart + Cumulative Criteria Averages -->
  <div class="row g-4 align-items-stretch mb-4">
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-graph-up text-primary"></i>
            <span>Score Progression Across Completed Trips</span>
          </div>
          <span class="text-muted small" style="font-size:12px;">Scale: 1.0 – 5.0</span>
        </div>
        <div class="card-body p-4">
          @if ($chartSeries->isNotEmpty())
            <div style="height: 240px;">
              <canvas id="vendorTrendChart"></canvas>
            </div>
          @else
            <div class="p-4 text-center text-muted bg-light rounded border">
              No historical evaluations recorded for this partner yet.
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-bar-chart-line text-primary"></i>
            <span>Cumulative Criteria Averages</span>
          </div>
        </div>
        <div class="card-body p-4 d-flex flex-column justify-content-center gap-3">
          @php
            $critList = [
              ['label' => 'Cost Competitiveness', 'val' => $criteriaAverages['cost'], 'icon' => 'bi-cash-coin'],
              ['label' => 'Timeliness & Punctuality', 'val' => $criteriaAverages['time'], 'icon' => 'bi-clock'],
              ['label' => 'Service Quality', 'val' => $criteriaAverages['quality'], 'icon' => 'bi-award'],
              ['label' => 'Vendor Responsiveness', 'val' => $criteriaAverages['responsiveness'], 'icon' => 'bi-chat-heart'],
              ['label' => 'Booking Accuracy', 'val' => $criteriaAverages['accuracy'], 'icon' => 'bi-check2-circle'],
            ];
          @endphp
          @foreach ($critList as $c)
            @php
              $pct = ($c['val'] / 5) * 100;
              $barColor = $c['val'] >= 4 ? '#16a34a' : ($c['val'] >= 3 ? '#d97706' : '#dc2626');
            @endphp
            <div>
              <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:12.5px;">
                <span class="fw-semibold text-dark"><i class="bi {{ $c['icon'] }} text-muted me-1"></i>{{ $c['label'] }}</span>
                <span class="fw-bold" style="color:{{ $count > 0 ? $barColor : '#64748b' }};">{{ $count > 0 ? $c['val'] . ' / 5.0' : '—' }}</span>
              </div>
              <div class="progress" style="height:6px; background:#f1f5f9;">
                <div class="progress-bar" style="width:{{ $pct }}%; background-color:{{ $barColor }};"></div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <!-- Full Historical Trip-by-Trip Evaluations Table -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Trip-by-Trip Evaluation History</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 11px;">
          {{ $count }} {{ Str::plural('Trip', $count) }}
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th class="ps-4" style="width: 24%;">COMPLETED TRF &amp; ROUTE</th>
              <th style="width: 24%;">SCORE &amp; CRITERIA</th>
              <th style="width: 42%;">FEEDBACK &amp; EVALUATOR</th>
              <th class="text-end pe-4" style="width: 10%;">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($evaluations as $eval)
              <tr>
                <td class="ps-4">
                  <a href="{{ route('trf.show', $eval->trf) }}" class="fw-semibold text-primary text-decoration-none">{{ $eval->trf->trf_number }}</a>
                  <div class="text-dark" style="font-size:12px;">{{ $eval->trf->employee->name }}</div>
                  <div class="text-muted" style="font-size:11px;">
                    {{ $eval->trf->route ?: '—' }}
                    @if ($eval->trf->travel_end)
                      &bull; Ended {{ $eval->trf->travel_end->format('d M Y') }}
                    @endif
                  </div>
                </td>
                <td>
                  <span class="badge badge-soft-success d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size:11px;">
                    <i class="bi bi-star-fill text-warning" style="font-size:0.7rem;"></i>
                    <span class="fw-bold">{{ $eval->averageRating() }} / 5.0</span>
                  </span>
                  <div class="text-muted mt-1" style="font-size:11px;">
                    Cost {{ $eval->cost_score }} &bull; Timeliness {{ $eval->time_score }} &bull; Service {{ $eval->quality_score }} &bull; Responsiveness {{ $eval->responsiveness_score }}
                  </div>
                </td>
                <td>
                  <div class="text-dark">{{ $eval->comments ?: 'No qualitative remarks recorded.' }}</div>
                  <div class="text-muted mt-1" style="font-size:11px;">
                    Rated by {{ $eval->rater?->name ?? 'System' }} &bull; {{ optional($eval->created_at)->format('d M Y, H:i') }}
                  </div>
                </td>
                <td class="text-end pe-4 text-nowrap">
                  <a href="{{ route('traveleval.show', $eval) }}" class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size:11.5px; font-weight:500;">
                    <i class="bi bi-eye"></i>
                    <span>View</span>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  No evaluations recorded for {{ $vendor->company_name }} yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Vendor Performance History' });

(function () {
  const canvas = document.getElementById('vendorTrendChart');
  const series = @json($chartSeries);
  if (!canvas || !window.Chart || !series.length) return;

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: series.map((pt) => `${pt.label} (${pt.date})`),
      datasets: [
        {
          label: 'Overall Trip Rating',
          data: series.map((pt) => pt.score),
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.1)',
          fill: true,
          tension: 0.25,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#2563eb',
          borderWidth: 2.5,
        },
        {
          label: 'Target Benchmark (4.0)',
          data: series.map(() => 4.0),
          borderColor: '#94a3b8',
          borderDash: [5, 5],
          borderWidth: 1.5,
          pointRadius: 0,
          fill: false,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          min: 1,
          max: 5,
          ticks: { stepSize: 1 }
        }
      },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { boxWidth: 12, font: { size: 11 } }
        }
      }
    }
  });
})();
@endsection

