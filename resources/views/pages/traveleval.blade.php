@extends('layouts.app')

@section('title', 'Travel Vendor Evaluation')

@php
  $totalEvals = $evaluations->count();
  $vendorCount = count($leaderboard);
  $topVendor = count($leaderboard) > 0 ? $leaderboard[0] : null;
  $globalAvg = count($leaderboard) > 0 ? round(collect($leaderboard)->avg('average'), 1) : 0;
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Travel Partner Performance &amp; Evaluation</h1>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('traveleval.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-star me-1"></i> Rate a Vendor
        </a>
      </div>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if ($canCreate && !empty($pendingRows) && $pendingRows->isNotEmpty())
    @php
      $pendingPairs = $pendingRows->flatMap(function ($row) {
        return $row['vendors']->map(fn ($v) => ['trf' => $row['trf'], 'vendor' => $v]);
      })->values();
      $pendingCount = $pendingPairs->count();
      $firstPair = $pendingPairs->first();
      $extraPending = max(0, $pendingCount - 2);
    @endphp
    <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3" style="background:#FFFBEB; border-left:4px solid #D97706 !important;">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-white border text-warning" style="width:36px; height:36px;">
            <i class="bi bi-star-fill"></i>
          </div>
          <div style="font-size:13px;">
            <div class="fw-bold text-dark">
              {{ $pendingCount }} Completed {{ Str::plural('Booking', $pendingCount) }} Pending Vendor Rating
            </div>
            <div class="text-muted" style="font-size:12px;">
              Ready to evaluate:
              @foreach ($pendingPairs->take(2) as $pair)
                <a href="{{ route('traveleval.create', ['trf_id' => $pair['trf']->id, 'vendor_id' => $pair['vendor']->id]) }}" class="fw-semibold text-dark text-decoration-underline me-2">
                  {{ $pair['trf']->trf_number }} ({{ $pair['vendor']->company_name }})
                </a>
              @endforeach
              @if ($extraPending > 0)
                <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-warning-emphasis text-decoration-none align-baseline" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#pendingEvalModal">
                  +{{ $extraPending }} more &rarr;
                </button>
              @endif
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          @if ($extraPending > 0)
            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold text-nowrap px-3 py-1" data-bs-toggle="modal" data-bs-target="#pendingEvalModal">
              View All ({{ $pendingCount }})
            </button>
          @endif
          <a href="{{ route('traveleval.create', ['trf_id' => $firstPair['trf']->id, 'vendor_id' => $firstPair['vendor']->id]) }}" class="btn btn-sm btn-warning text-dark fw-semibold text-nowrap px-3 py-1">
            Rate Now
          </a>
        </div>
      </div>
    </div>

    @if ($extraPending > 0)
      <div class="modal fade" id="pendingEvalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-star-fill text-warning"></i>
                <h6 class="modal-title fw-bold mb-0">Pending Vendor Evaluations ({{ $pendingCount }})</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                  <thead class="table-light">
                    <tr style="font-size:11px; text-transform:uppercase; color:#64748b;">
                      <th class="ps-3">TRF No.</th>
                      <th>Traveler &amp; Route</th>
                      <th>Travel Vendor</th>
                      <th>Trip Ended</th>
                      <th class="pe-3 text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($pendingPairs as $pair)
                      <tr>
                        <td class="ps-3 fw-semibold text-primary text-nowrap">{{ $pair['trf']->trf_number }}</td>
                        <td>
                          <div class="fw-medium text-dark">{{ $pair['trf']->employee?->name ?? '—' }}</div>
                          <div class="text-muted" style="font-size:11px;">{{ $pair['trf']->route ?: '—' }}</div>
                        </td>
                        <td class="fw-semibold text-dark">{{ $pair['vendor']->company_name }}</td>
                        <td class="text-muted text-nowrap" style="font-size:11.5px;">
                          {{ optional($pair['trf']->travel_end ?? $pair['trf']->travel_start)->format('d M Y') ?? '—' }}
                        </td>
                        <td class="pe-3 text-end text-nowrap">
                          <a href="{{ route('traveleval.create', ['trf_id' => $pair['trf']->id, 'vendor_id' => $pair['vendor']->id]) }}" class="btn btn-sm btn-warning text-dark fw-semibold px-2 py-1" style="font-size:11.5px;">
                            Rate
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endif

  <!-- KPI Summary Cards -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Evaluations</span>
            <div class="stat-icon-box icon-primary" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-patch-check"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.4rem; font-weight: 700; line-height: 1.2;">{{ $totalEvals }}</div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Completed trip scorecards</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Partners</span>
            <div class="stat-icon-box icon-warning" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.4rem; font-weight: 700; line-height: 1.2;">{{ $vendorCount }}</div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Evaluated travel agencies</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Top Performer</span>
            <div class="stat-icon-box icon-success" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-trophy"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.05rem; font-weight: 700; line-height: 1.25;">
              {{ $topVendor ? $topVendor['vendor']->company_name : '—' }}
            </div>
            <div class="text-success fw-semibold mt-1" style="font-size: 11.5px;">
              @if ($topVendor)
                <i class="bi bi-star-fill text-warning me-1"></i>{{ $topVendor['average'] }} / 5.0 ({{ $topVendor['count'] }} {{ Str::plural('trip', $topVendor['count']) }})
              @else
                No ratings yet
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Avg Quality</span>
            <div class="stat-icon-box icon-secondary" style="width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px;">
              <i class="bi bi-star-half"></i>
            </div>
          </div>
          <div>
            <div class="stat-value mb-0" style="font-size: 1.4rem; font-weight: 700; line-height: 1.2;">{{ $globalAvg }} <small class="text-muted" style="font-size: 0.85rem;">/ 5.0</small></div>
            <div class="text-muted mt-1" style="font-size: 11.5px;">Across all partners</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Performance Leaderboard -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-trophy text-warning fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Travel Partner Performance Leaderboard</h6>
      </div>
      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">Quality Ranked</span>
    </div>
    <div class="card-body p-3">
      <div class="row g-3">
        @forelse ($leaderboard as $idx => $row)
          @php $t = $row['trend']; @endphp
          <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('traveleval.vendor-history', $row['vendor']) }}" class="text-decoration-none d-block h-100">
              <div class="p-3 border rounded-3 bg-white h-100 d-flex flex-column justify-content-between gap-2 card-lift">
                <div class="d-flex align-items-start justify-content-between gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="stat-icon-circle flex-shrink-0" style="background:{{ $idx === 0 ? 'rgba(245, 158, 11, 0.15)' : 'var(--hover-bg)' }}; color:{{ $idx === 0 ? '#B45309' : '#475569' }}; width:34px; height:34px; font-weight:700; font-size:13px;">
                      #{{ $idx + 1 }}
                    </span>
                    <div>
                      <div class="fw-semibold text-dark" style="font-size:13.5px; line-height:1.25;">{{ $row['vendor']->company_name }}</div>
                      <div class="d-flex flex-wrap align-items-center gap-2 mt-0.5" style="font-size:11.5px;">
                        <span class="text-muted">{{ $row['count'] }} {{ Str::plural('trip', $row['count']) }}</span>
                        <span class="badge d-inline-flex align-items-center gap-1 px-1.5 py-0.5" style="background:{{ $t['bg'] }}; color:{{ $t['fg'] }}; font-size:10px; font-weight:600;">
                          <i class="bi {{ $t['icon'] }}"></i>
                          <span>{{ $t['label'] }}@if ($row['count'] >= 2 && $t['delta'] != 0) {{ $t['formatted_delta'] }}@endif</span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <span class="badge badge-soft-success d-inline-flex align-items-center gap-1 py-1 px-2 flex-shrink-0">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span class="fw-bold">{{ $row['average'] }}</span>
                  </span>
                </div>
                <div class="pt-2 border-top d-flex flex-wrap justify-content-between text-muted" style="font-size:11.5px; border-color:#f1f5f9 !important;">
                  <span>Cost <strong class="text-dark">{{ $row['cost_avg'] }}</strong></span>
                  <span>Timeliness <strong class="text-dark">{{ $row['time_avg'] }}</strong></span>
                  <span>Service <strong class="text-dark">{{ $row['quality_avg'] }}</strong></span>
                  <span>Response <strong class="text-dark">{{ $row['resp_avg'] }}</strong></span>
                </div>
              </div>
            </a>
          </div>
        @empty
          <div class="col-12">
            <div class="p-4 text-center text-muted border rounded-3 bg-light">
              No travel partners evaluated yet.
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <style>
    #evalTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #evalTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #64748b;
      vertical-align: middle;
      padding: 10px 10px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #evalTable td {
      vertical-align: top;
      padding: 12px 10px;
      font-size: 12px;
      line-height: 1.45;
      border-bottom: 1px solid #f1f5f9;
      white-space: normal;
    }
  </style>

  <!-- Evaluation Registry Table -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-star-half text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Evaluation Activity Registry</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 11px;">
          {{ $totalEvals }} {{ Str::plural('Record', $totalEvals) }}
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="evalTable">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th class="ps-3" style="width: 18%;">PARTNER AGENCY</th>
              <th style="width: 21%;">COMPLETED TRF</th>
              <th style="width: 23%;">SCORE &amp; CRITERIA</th>
              <th style="width: 29%;">FEEDBACK &amp; EVALUATOR</th>
              <th class="text-end pe-3" style="width: 9%;">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($evaluations as $eval)
              <tr>
                <td class="ps-3">
                  <a href="{{ route('traveleval.vendor-history', $eval->vendor) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $eval->vendor->company_name }}
                  </a>
                  <div class="text-muted" style="font-size: 11px;">
                    <a href="{{ route('traveleval.vendor-history', $eval->vendor) }}" class="text-primary text-decoration-none">View History &rarr;</a>
                  </div>
                </td>
                <td>
                  <a href="{{ route('trf.show', $eval->trf) }}" class="fw-semibold text-primary text-decoration-none">{{ $eval->trf->trf_number }}</a>
                  <div class="text-dark" style="font-size: 11.5px;">{{ $eval->trf->employee->name }}</div>
                  @if ($eval->trf->route)
                    <div class="text-muted" style="font-size: 11px;">{{ $eval->trf->route }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge badge-soft-success d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 11px;">
                    <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                    <span class="fw-bold">{{ $eval->averageRating() }} / 5.0</span>
                  </span>
                  <div class="text-muted mt-1" style="font-size: 11px;">
                    Cost {{ $eval->cost_score }} &bull; Timeliness {{ $eval->time_score }} &bull; Service {{ $eval->quality_score }} &bull; Responsiveness {{ $eval->responsiveness_score }}
                  </div>
                </td>
                <td>
                  <div class="text-dark">{{ $eval->comments ?: 'No qualitative remarks added.' }}</div>
                  <div class="text-muted mt-1" style="font-size: 11px;">
                    Rated by {{ $eval->rater?->name ?? 'System' }} &bull; {{ optional($eval->created_at)->format('d M Y') }}
                  </div>
                </td>
                <td class="text-end pe-3 text-nowrap align-middle">
                  <a href="{{ route('traveleval.show', $eval) }}" class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 500;">
                    <i class="bi bi-eye"></i>
                    <span>View</span>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No vendor evaluations logged yet.</span>
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
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Vendor Evaluation' });
@endsection
