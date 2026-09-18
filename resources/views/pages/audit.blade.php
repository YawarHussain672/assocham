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

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">System Audit Trail &amp; Governance Ledger</h1>
      <div class="page-sub" style="margin-top:0;">Immutable, time-stamped transaction logs automatically recorded across every procurement, financial, and logistics action. Showing the latest {{ $totalLogs }} ledger entries.</div>
    </div>
  </div>

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Events Logged</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalLogs }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Latest chronological records</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-clock-history fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Today's Activity</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $todayLogs }}</div>
            <div class="mt-1" style="font-size:11.5px; color:#2e7d4f; font-weight:600;">Recorded in current 24h</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-calendar-check fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Monitored Modules</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $uniqueModules->count() }} Subsystems</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">PR, PO, CCS, GRN, TRF, etc.</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-grid fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Ledger Security</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">100% Immutable</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">System-signed, read-only</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-shield-check fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Audit Table Panel -->
  <div class="card-panel p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <span class="stat-icon-circle" style="background:#f8fafc; color:#1e293b; width:34px; height:34px; font-size:14px;">
          <i class="bi bi-list-check"></i>
        </span>
        <div>
          <h2 class="h6 mb-0" style="font-weight:600;">Transaction Log Feed</h2>
          <div class="text-muted" style="font-size:12px;">Real-time event stream</div>
        </div>
      </div>
      <div class="d-flex gap-1 flex-wrap" id="auditFilters">
        <button type="button" class="btn btn-sm btn-brand active py-1 px-3" style="font-size:12px; font-weight:600;" data-module="all">All Modules</button>
        @foreach ($uniqueModules as $mod)
          <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" style="font-size:12px; font-weight:600;" data-module="{{ strtolower($mod) }}">{{ strtoupper($mod) }}</button>
        @endforeach
      </div>
    </div>

    <div style="overflow-x:auto;">
      <table class="table align-middle table-hover" id="auditTable">
        <thead>
          <tr class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">
            <th style="width:170px;">Timestamp</th>
            <th style="width:190px;">Officer / Actor</th>
            <th>Action Taken</th>
            <th>Module &amp; Reference</th>
          </tr>
        </thead>
        <tbody style="font-size:13.5px;">
          @forelse ($logs as $log)
            @php
              $actionLower = strtolower($log->action);
              $actionIcon = 'bi-circle';
              $iconBg = '#EEF0F3';
              $iconColor = '#64748B';

              if (str_contains($actionLower, 'approve') || str_contains($actionLower, 'settle') || str_contains($actionLower, 'resolve')) {
                $actionIcon = 'bi-check-circle-fill';
                $iconBg = '#E6F3EC';
                $iconColor = '#2E7D4F';
              } elseif (str_contains($actionLower, 'create') || str_contains($actionLower, 'store') || str_contains($actionLower, 'dispatch')) {
                $actionIcon = 'bi-plus-circle-fill';
                $iconBg = '#E6F0FB';
                $iconColor = '#1E5FA8';
              } elseif (str_contains($actionLower, 'reject') || str_contains($actionLower, 'close') || str_contains($actionLower, 'reverse')) {
                $actionIcon = 'bi-x-circle-fill';
                $iconBg = '#FBE7E9';
                $iconColor = '#B0263A';
              } elseif (str_contains($actionLower, 'pushback') || str_contains($actionLower, 'escalat')) {
                $actionIcon = 'bi-arrow-counterclockwise';
                $iconBg = '#FBF0DC';
                $iconColor = '#8A5A12';
              }
            @endphp
            <tr class="audit-item" data-module="{{ strtolower($log->module ?? '') }}">
              <td>
                <div class="d-flex align-items-center gap-2 text-muted" style="font-size:12.5px;">
                  <i class="bi bi-clock"></i>
                  <span>{{ $log->created_at?->format('d M Y, H:i:s') }}</span>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-circle" style="width:28px; height:28px; font-size:12px; background:#f1f5f9; color:#475569;">
                    <i class="bi bi-person"></i>
                  </span>
                  <span class="fw-semibold text-dark" style="font-weight:600;">{{ $log->user?->name ?? 'System Automated' }}</span>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-circle" style="width:24px; height:24px; font-size:11px; background:{{ $iconBg }}; color:{{ $iconColor }};">
                    <i class="bi {{ $actionIcon }}"></i>
                  </span>
                  <div>
                    <span class="fw-semibold text-dark" style="font-weight:600;">{{ ucfirst($log->action) }}</span>
                    @if ($log->detail)
                      <span class="text-muted ms-1">— {{ $log->detail }}</span>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:600;">
                    {{ strtoupper($log->module) }}
                  </span>
                  @if ($log->reference)
                    <span class="text-muted" style="font-size:13px; font-weight:500;">
                      {{ $log->reference }}
                    </span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-3"></i>
                <div class="mt-2">No audit activity recorded yet.</div>
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
        b.classList.remove('btn-brand', 'active');
        b.classList.add('btn-outline-secondary');
      });
      btn.classList.remove('btn-outline-secondary');
      btn.classList.add('btn-brand', 'active');

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

