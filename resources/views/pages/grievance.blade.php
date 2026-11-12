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

  <!-- Status KPI Tab Cards (Total, Active, Escalated, Resolved) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-ticket-detailed"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalGrievances }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Active</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $activeGrievances }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Escalated</span>
            <div class="stat-icon-box icon-info" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-person-badge"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $seniorQueue }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Resolved</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $resolvedGrievances }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grievance Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h6 class="mb-0 fw-bold">Grievance Incident Roster</h6>
        <span class="badge bg-light text-dark">{{ $totalGrievances }} tickets</span>
        <span class="badge {{ !empty($isGovernanceView) ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}" style="font-size:11px;">
          <i class="bi {{ !empty($isGovernanceView) ? 'bi-shield-check' : 'bi-lock' }} me-1"></i>
          {{ !empty($isGovernanceView) ? 'Handler View · All Employee Tickets' : 'Private View · Your Raised Tickets Only' }}
        </span>
      </div>
      <form method="GET" action="{{ route('grievance') }}" class="d-flex align-items-center gap-2">
        <select name="category" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" aria-label="Filter by category">
          <option value="">All Categories ({{ $allGrievancesCount ?? $totalGrievances }})</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat }}" {{ ($activeCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </form>
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
              $unreadUpdates = (int) ($g->unread_updates_count ?? 0);
              $latestUpdate = $g->latest_update;
            @endphp
            <tr onclick="window.location='{{ route('grievance.show', $g) }}'" style="cursor:pointer;">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-ticket text-primary"></i>
                  <span class="fw-bold font-monospace text-dark">{{ $g->ticket_number }}</span>
                </div>
                @if ($unreadUpdates > 0)
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size:10.5px;">
                    {{ $unreadUpdates }} New {{ Str::plural('Update', $unreadUpdates) }}
                  </span>
                @endif
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ $g->subject }}</div>
                @if ($g->reimbursement)
                  <div class="text-muted mt-1" style="font-size:11.5px;">
                    <i class="bi bi-wallet2 me-1 text-primary"></i>Linked Claim: <span class="font-monospace fw-semibold text-dark">{{ $g->reimbursement->claim_number }}</span>
                  </div>
                @endif
                @if ($latestUpdate)
                  <div class="text-muted mt-1" style="font-size:11.5px;">
                    <span class="fw-semibold text-secondary">Latest:</span> {{ $latestUpdate->actionLabel() }} · {{ $latestUpdate->actor?->name ?? 'System' }} ({{ $latestUpdate->created_at?->format('d M, H:i') }})
                  </div>
                @endif
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
