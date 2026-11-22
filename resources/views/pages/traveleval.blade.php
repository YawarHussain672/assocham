
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
          <a href="{{ route('traveleval.create', ['trf_id' => $firstPair['trf']->id, 'vendor_id' => $firstPair['vendor']->id]) }}" class="btn btn-sm erp-btn-action-view fw-semibold text-nowrap px-3 py-1">
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
                <table class="table table-hover align-middle dash-pipeline-table mb-0">
                  <thead>
                    <tr>
                      <th class="ps-3" style="width: 25%;">TRF NO.</th>
                      <th style="width: 30%;">TRAVELER</th>
                      <th style="width: 20%;">VENDOR</th>
                      <th style="width: 13%;">STATUS</th>
                      <th class="pe-3 text-end" style="width: 12%;">ACTION</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($pendingPairs as $pair)
                      <tr>
                        <td class="ps-3 fw-bold font-monospace text-primary text-nowrap" style="font-size: 13.5px;">{{ $pair['trf']->trf_number }}</td>
                        <td>
                          <span class="fw-semibold text-main" style="font-size: 13.5px;">{{ $pair['trf']->employee?->name ?? '—' }}</span>
                        </td>
                        <td>
                          <span class="text-secondary" style="font-size: 13.5px;">{{ $pair['vendor']->company_name }}</span>
                        </td>
                        <td>
                          <span class="badge badge-soft-warning status-badge-pipeline">Pending Rating</span>
                        </td>
                        <td class="pe-3 text-end text-nowrap">
                          <a href="{{ route('traveleval.create', ['trf_id' => $pair['trf']->id, 'vendor_id' => $pair['vendor']->id]) }}" class="btn btn-primary dash-review-btn">
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

  <!-- Performance Leaderboard -->
  <div class="card shadow-sm border-0 mb-4" id="leaderboardSection">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
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

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="evalSwitchBar">
    <button type="button" class="erp-switch-tab active" onclick="showEvalSection('all')">
      All Evaluations <span class="erp-switch-count">{{ $totalEvals }}</span>
    </button>
    <button type="button" class="erp-switch-tab" onclick="showEvalSection('leaderboard')">
      Leaderboard <span class="erp-switch-count">{{ $vendorCount }}</span>
    </button>
  </div>

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container mb-4" id="evalTableSection">
    <table class="erp-table align-middle mb-0" id="evalTable">
      <thead>
        <tr>
          <th class="ps-3" style="width: 30%;">PARTNER AGENCY</th>
          <th style="width: 25%;">COMPLETED TRF</th>
          <th style="width: 20%;">SCORE</th>
          <th style="width: 13%;">STATUS</th>
          <th class="text-end pe-3" style="width: 12%;">ACTION</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($evaluations as $eval)
          <tr>
            <!-- 1. Partner Agency -->
            <td class="ps-3">
              <span class="fw-semibold text-main" style="font-size: 13.5px;">
                {{ $eval->vendor->company_name }}
              </span>
            </td>

            <!-- 2. Completed TRF -->
            <td>
              <a href="{{ route('trf.show', $eval->trf) }}" class="erp-code-link" style="font-size: 13.5px;">
                {{ $eval->trf->trf_number }}
              </a>
            </td>

            <!-- 3. Score -->
            <td>
              <span class="fw-bold text-dark" style="font-size: 13.5px;">
                <i class="bi bi-star-fill text-warning me-1"></i>{{ $eval->averageRating() }} / 5.0
              </span>
            </td>

            <!-- 4. Status -->
            <td>
              <span class="badge badge-soft-success status-badge-pipeline">Evaluated</span>
            </td>

            <!-- 5. Action -->
            <td class="text-end pe-3">
              <div class="erp-row-actions">
                <a href="{{ route('traveleval.show', $eval) }}" class="erp-btn-action-view">
                  <i class="bi bi-eye"></i> View
                </a>
                <div class="dropdown">
                  <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('traveleval.show', $eval) }}">
                        <i class="bi bi-eye text-secondary"></i> View Scorecard
                      </a>
                    </li>
                    @if ($eval->vendor_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'empanelment'))
                      <li>
                        <a class="dropdown-item" href="{{ route('empanelment.show', $eval->vendor_id) }}">
                          <i class="bi bi-building text-secondary"></i> Vendor Dossier
                        </a>
                      </li>
                    @endif
                  </ul>
                </div>
              </div>
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

<script>
function showEvalSection(section) {
  document.querySelectorAll('#evalSwitchBar .erp-switch-tab').forEach((tab, idx) => {
    tab.classList.toggle('active', (section === 'all' && idx === 0) || (section === 'leaderboard' && idx === 1));
  });
  const tableSec = document.getElementById('evalTableSection');
  const lbSec = document.getElementById('leaderboardSection');
  if (section === 'all') {
    if (tableSec) tableSec.style.display = '';
    if (lbSec) lbSec.style.display = '';
  } else if (section === 'leaderboard') {
    if (tableSec) tableSec.style.display = 'none';
    if (lbSec) lbSec.style.display = '';
  }
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Vendor Evaluation' });
@endsection

