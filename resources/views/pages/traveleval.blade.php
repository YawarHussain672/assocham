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
      <p class="page-subtitle">Score travel agency service delivery, SLA metrics, and rate fulfillment quality.</p>
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

  <!-- Top 4 Pastel KPI Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Evaluations Logged</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-patch-check"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalEvals }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Completed trip feedback</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Rated Partners</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $vendorCount }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-geo-alt"></i>
            <span>Active corporate agencies</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Top Partner</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-trophy"></i>
            </div>
          </div>
          <div class="stat-value mb-1 text-truncate" style="font-size: 1.35rem;" title="{{ $topVendor ? $topVendor['vendor']->company_name : 'No Ratings' }}">
            {{ $topVendor ? $topVendor['vendor']->company_name : 'No Ratings' }}
          </div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-star-fill text-warning"></i>
            <span>{{ $topVendor ? $topVendor['average'].' / 5.0 Rating' : 'Pending ratings' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Network Quality</span>
            <div class="stat-icon-box icon-secondary">
              <i class="bi bi-star-half"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $globalAvg }} <small class="text-muted" style="font-size: 0.9rem;">/ 5.0</small></div>
          <div class="d-flex align-items-center gap-1 text-secondary small fw-semibold">
            <i class="bi bi-check2-circle"></i>
            <span>Average across agencies</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Performance Leaderboard Grid -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Travel Partner Performance Leaderboard</h6>
        <small class="text-muted">Ranked by cumulative weighted quality scoring</small>
      </div>
      <span class="badge badge-soft-warning"><i class="bi bi-trophy-fill me-1"></i> Quality Ranked</span>
    </div>
    <div class="card-body p-3">
      <div class="row g-3">
        @forelse ($leaderboard as $idx => $row)
          <div class="col-12 col-md-6 col-xl-4">
            <div class="p-3 border rounded-3 bg-white d-flex justify-content-between align-items-center card-lift">
              <div class="d-flex align-items-center gap-3">
                <span class="stat-icon-circle" style="background:{{ $idx === 0 ? 'rgba(245, 158, 11, 0.15)' : 'var(--hover-bg)' }}; color:{{ $idx === 0 ? '#B45309' : '#475569' }}; width:38px; height:38px; font-weight:700; font-size:14px;">
                  #{{ $idx + 1 }}
                </span>
                <div>
                  <div class="fw-semibold text-main">{{ $row['vendor']->company_name }}</div>
                  <small class="text-muted">{{ $row['count'] }} {{ Str::plural('evaluation', $row['count']) }} recorded</small>
                </div>
              </div>
              <span class="badge badge-soft-success d-inline-flex align-items-center gap-1 py-1 px-2.5">
                <i class="bi bi-star-fill text-warning"></i>
                <span class="fw-bold">{{ $row['average'] }}</span>
              </span>
            </div>
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

  <!-- Evaluation Registry Table -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Evaluation Activity Registry</h6>
        <small class="text-muted">Detailed review submissions per completed trip</small>
      </div>
      <span class="badge badge-soft-primary">{{ $totalEvals }} Records</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">PARTNER AGENCY</th>
              <th>LINKED TRF &amp; TRAVELER</th>
              <th>RATING SCORE</th>
              <th>EVALUATED BY</th>
              <th>FEEDBACK NOTES</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($evaluations as $eval)
              <tr>
                <td class="ps-3">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-building text-primary"></i>
                    <span class="fw-semibold text-main">{{ $eval->vendor->company_name }}</span>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $eval->trf->trf_number }}</span>
                  <small class="text-muted d-block">{{ $eval->trf->employee->name }}</small>
                </td>
                <td>
                  <span class="badge badge-soft-success d-inline-flex align-items-center gap-1">
                    <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                    <span class="fw-bold">{{ $eval->averageRating() }} / 5.0</span>
                  </span>
                </td>
                <td>
                  <span class="text-muted">{{ $eval->rater?->name ?? 'System' }}</span>
                </td>
                <td>
                  <span class="text-muted">{{ \Illuminate\Support\Str::limit($eval->comments, 45) ?: '—' }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('traveleval.show', $eval) }}" class="btn btn-sm btn-outline-custom">
                    View Dossier <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
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
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Travel Vendor Evaluation' });
@endsection
