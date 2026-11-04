@extends('layouts.app')

@section('title', 'Grievance Redressal')

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'badge' => 'warning'],
    'admin_head' => ['label' => 'With Admin Head', 'badge' => 'warning'],
    'cfo'        => ['label' => 'With CFO', 'badge' => 'warning'],
    'sg'         => ['label' => 'With SG', 'badge' => 'warning'],
  ];

  $totalGrievances = $grievances->count();
  $activeGrievances = $grievances->where('status', '!=', 'resolved')->count();
  $resolvedGrievances = $grievances->where('status', 'resolved')->count();
  $seniorQueue = $grievances->whereIn('current_owner_role', ['cfo', 'sg'])->where('status', '!=', 'resolved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Grievance Redressal &amp; Incident Tracker</h1>
      <p class="page-subtitle">Report vendor disputes, invoice discrepancies, and escalate organizational tickets.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('grievance.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-ticket-perforated me-1"></i> Raise Ticket
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>{{ session('status') }}</div>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Tickets</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-ticket-detailed"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalGrievances }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-chat-left-text"></i>
            <span>All time incident reports</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Active Queue</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $activeGrievances }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-clock-history"></i>
            <span>In escalation chain</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Executive Escalations</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-person-badge"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $seniorQueue }}</div>
          <div class="d-flex align-items-center gap-1 text-info small fw-semibold">
            <i class="bi bi-shield-lock"></i>
            <span>With CFO or SG</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Resolved Cases</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $resolvedGrievances }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-patch-check"></i>
            <span>Successfully mediated</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grievance Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold">Grievance Incident Roster</h6>
        <span class="badge bg-light text-dark">{{ $totalGrievances }} tickets</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Ticket No.</th>
            <th>Subject Narrative</th>
            <th>Category</th>
            <th>Raised By</th>
            <th>Status / Current Owner</th>
            <th class="text-end pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($grievances as $g)
            @php
              $isResolved = $g->status === 'resolved';
              $badgeType = $isResolved ? 'success' : ($ownerMeta[$g->current_owner_role]['badge'] ?? 'warning');
              $badgeText = $isResolved ? 'Resolved' : ($ownerMeta[$g->current_owner_role]['label'] ?? 'Pending');
            @endphp
            <tr onclick="window.location='{{ route('grievance.show', $g) }}'" style="cursor:pointer;">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-ticket text-primary"></i>
                  <span class="fw-bold font-monospace text-dark">{{ $g->ticket_number }}</span>
                </div>
              </td>
              <td>
                <span class="fw-semibold text-dark">{{ $g->subject }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $g->subject_category }}</span>
              </td>
              <td>
                <span class="small fw-medium">{{ $g->raisedBy->name }}</span>
              </td>
              <td>
                <span class="badge bg-{{ $badgeType }}-subtle text-{{ $badgeType }} border border-{{ $badgeType }}-subtle px-2 py-1">
                  {{ $badgeText }}
                </span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('grievance.show', $g) }}" class="btn btn-sm btn-outline-secondary">
                  View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-chat-left-check fs-2 d-block mb-2"></i>
                No grievances raised yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Redressal' });
@endsection
