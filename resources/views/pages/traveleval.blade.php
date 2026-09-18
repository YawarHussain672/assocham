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

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Travel Partner Performance &amp; Vendor Rating</h1>
      <div class="page-sub" style="margin-top:0;">Vendors are objectively evaluated per completed trip across cost, punctuality, quality, responsiveness, and accuracy to drive preferred empanelment allocations.</div>
    </div>
    @if ($canCreate)
      <a href="{{ route('traveleval.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
        <i class="bi bi-star"></i>
        <span>Rate a Vendor</span>
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Evaluations Logged</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalEvals }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Completed trip feedback</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-patch-check fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Rated Travel Partners</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $vendorCount }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Active corporate agencies</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-building fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Top Ranked Partner</div>
            <div class="mt-2 text-truncate" style="font-size:18px; font-weight:600; color:#1e293b; max-width:180px;">
              {{ $topVendor ? $topVendor['vendor']->company_name : 'No Ratings' }}
            </div>
            <div class="mt-1" style="font-size:11.5px; color:#2e7d4f; font-weight:600;">
              {{ $topVendor ? $topVendor['average'].' / 5.0 Rating' : 'Pending evaluations' }}
            </div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-trophy fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Network Quality Score</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $globalAvg }} / 5.0</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Average across all vendors</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-star-half fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Performance Ranking Cards -->
  <div class="card-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h6 mb-0 d-flex align-items-center gap-2" style="font-weight:600;">
        <i class="bi bi-trophy text-warning"></i>
        <span>Travel Partner Performance Leaderboard</span>
      </h2>
      <span class="text-muted" style="font-size:12px;">Ranked by cumulative weighted score</span>
    </div>

    <div class="row g-3">
      @forelse ($leaderboard as $idx => $row)
        <div class="col-12 col-md-6 col-xl-4">
          <div class="p-3 border rounded-3 bg-white d-flex justify-content-between align-items-center shadow-xs">
            <div class="d-flex align-items-center gap-3">
              <span class="stat-icon-circle" style="background:{{ $idx === 0 ? '#FEF3C7' : '#F1F5F9' }}; color:{{ $idx === 0 ? '#B45309' : '#475569' }}; width:36px; height:36px; font-weight:600; font-size:14px;">
                #{{ $idx + 1 }}
              </span>
              <div>
                <div class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $row['vendor']->company_name }}</div>
                <div class="text-muted" style="font-size:12px;">{{ $row['count'] }} {{ Str::plural('evaluation', $row['count']) }} recorded</div>
              </div>
            </div>
            <span class="badge-soft d-inline-flex align-items-center gap-1" style="background:#E6F3EC; color:#2E7D4F; font-size:13px; font-weight:600; padding:6px 12px;">
              <i class="bi bi-star-fill text-warning" style="font-size:11px;"></i>
              <span>{{ $row['average'] }}</span>
            </span>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="p-4 text-center text-muted border rounded-3" style="background:#f8fafc;">
            No travel partners evaluated yet.
          </div>
        </div>
      @endforelse
    </div>
  </div>

  <!-- Evaluation Registry Table -->
  <div class="card-panel p-0 overflow-hidden">
    <div class="table-toolbar px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="table-toolbar-title d-flex align-items-center gap-2">
        <i class="bi bi-journal-text text-primary"></i>
        <span>Evaluation Activity Registry</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalEvals }} Records</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Partner Agency</div>
        <div>Linked TRF &amp; Traveler</div>
        <div>Rating Score</div>
        <div>Evaluated By</div>
        <div>Feedback Notes</div>
      </div>

      @forelse ($evaluations as $eval)
        <a href="{{ route('traveleval.show', $eval) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi bi-building text-muted"></i>
            <span>{{ $eval->vendor->company_name }}</span>
          </div>
          <div class="module-row-c1">{{ $eval->trf->trf_number }} · {{ $eval->trf->employee->name }}</div>
          <div class="module-row-c2">
            <span class="badge-soft d-inline-flex align-items-center gap-1" style="background:#E6F3EC; color:#2E7D4F; font-weight:600;">
              <i class="bi bi-star-fill text-warning" style="font-size:10px;"></i>
              <span>{{ $eval->averageRating() }} / 5.0</span>
            </span>
          </div>
          <div class="module-row-c3">{{ $eval->rater?->name ?? 'System' }}</div>
          <div class="module-row-c3 text-muted">{{ \Illuminate\Support\Str::limit($eval->comments, 45) ?: '—' }}</div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No vendor evaluations logged yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Travel Vendor Evaluation' });
@endsection
