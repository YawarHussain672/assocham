@extends('layouts.app')

@section('title', 'Audit Trail')

@php
  $totalLogs = $logs->count();
  $uniqueModules = $logs->pluck('module')->unique()->filter()->values();
  $todayLogs = $logs->filter(fn($l) => $l->created_at && $l->created_at->isToday())->count();
  $uniqueUsers = $logs->pluck('user_id')->unique()->filter()->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">System Audit Trail &amp; Governance Ledger</h1>
      <p class="page-subtitle">Centralized immutable chronological records of procurement, approvals, and financial transactions.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
        <i class="bi bi-shield-check me-1"></i> 100% Immutable
      </span>
      <span class="badge bg-light text-dark border px-3 py-2">
        <i class="bi bi-clock-history me-1 text-primary"></i> Live Stream
      </span>
    </div>
  </div>

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Events Logged</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalLogs }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-journal-text"></i>
            <span>Chronological ledger entries</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Today's Activity</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-calendar-check"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $todayLogs }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-check2-circle"></i>
            <span>Recorded in past 24h</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Monitored Modules</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-grid"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $uniqueModules->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-info small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>PR, PO, CCS, GRN, TRF, etc.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Active Officers</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-person-check"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $uniqueUsers }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-shield-check"></i>
            <span>Authorized actors</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Audit Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between py-3 gap-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:34px; height:34px;">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <h6 class="mb-0 fw-bold text-dark">Transaction Log Feed</h6>
          <div class="text-muted small">Real-time immutable audit trail across all workflow stages</div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-light text-dark border px-2 py-1">{{ $totalLogs }} Records</span>
        <div class="d-flex gap-1 flex-wrap ms-md-2" id="auditFilters">
          <button type="button" class="btn btn-sm btn-primary active py-1 px-3" data-module="all">All Modules</button>
          @foreach ($uniqueModules as $mod)
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3" data-module="{{ strtolower($mod) }}">{{ strtoupper($mod) }}</button>
          @endforeach
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="auditTable">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width:190px;">TIMESTAMP</th>
            <th style="width:220px;">OFFICER / ACTOR</th>
            <th>ACTION TAKEN</th>
            <th class="pe-3" style="width:200px;">MODULE &amp; REFERENCE</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            @php
              $actionLower = strtolower($log->action);
              $actionIcon = 'bi-circle';
              $badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';

              if (str_contains($actionLower, 'approve') || str_contains($actionLower, 'settle') || str_contains($actionLower, 'resolve')) {
                $actionIcon = 'bi-check-circle-fill';
                $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
              } elseif (str_contains($actionLower, 'create') || str_contains($actionLower, 'store') || str_contains($actionLower, 'dispatch')) {
                $actionIcon = 'bi-plus-circle-fill';
                $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
              } elseif (str_contains($actionLower, 'reject') || str_contains($actionLower, 'close') || str_contains($actionLower, 'reverse')) {
                $actionIcon = 'bi-x-circle-fill';
                $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
              } elseif (str_contains($actionLower, 'pushback') || str_contains($actionLower, 'escalat')) {
                $actionIcon = 'bi-arrow-counterclockwise';
                $badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
              }
            @endphp
            <tr class="audit-item" data-module="{{ strtolower($log->module ?? '') }}">
              <td class="ps-3">
                <div class="d-flex align-items-center gap-2 text-muted small font-monospace">
                  <i class="bi bi-clock me-1 text-secondary"></i>
                  <span>{{ $log->created_at?->format('d M Y, H:i:s') }}</span>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-light text-secondary border d-flex align-items-center justify-content-center me-1 flex-shrink-0" style="width:32px; height:32px; font-size:13px;">
                    <i class="bi bi-person-fill text-muted"></i>
                  </div>
                  <div>
                    <div class="fw-semibold text-dark small">{{ $log->user?->name ?? 'System Automated' }}</div>
                    <div class="text-muted" style="font-size:11px;">{{ $log->user?->role ? \App\Support\Roles::label($log->user->role) : 'System Trigger' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge {{ $badgeClass }} px-2 py-1 d-inline-flex align-items-center">
                    <i class="bi {{ $actionIcon }} me-1"></i>
                    <span>{{ ucfirst($log->action) }}</span>
                  </span>
                  @if ($log->detail)
                    <span class="text-muted small ms-1">— {{ $log->detail }}</span>
                  @endif
                </div>
              </td>
              <td class="pe-3">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                    {{ strtoupper($log->module) }}
                  </span>
                  @if ($log->reference)
                    <span class="text-muted small font-monospace">
                      {{ $log->reference }}
                    </span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No audit activity recorded yet.
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
Shell.init({ activeNav: 'audit', crumb: 'Governance', title: 'Audit Trail' });

(function () {
  const filterBtns = document.querySelectorAll('#auditFilters button');
  const rows = document.querySelectorAll('#auditTable tbody tr.audit-item');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-secondary');
      });
      btn.classList.remove('btn-outline-secondary');
      btn.classList.add('btn-primary', 'active');

      const mod = btn.dataset.module;
      rows.forEach(r => {
        if (mod === 'all' || r.dataset.module === mod) {
          r.style.display = '';
        } else {
          r.style.display = 'none';
        }
      });
    });
  });
})();
@endsection
