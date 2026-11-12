@extends('layouts.app')

@section('title', 'Audit Trail')

@php
  $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['module'] ?? '') !== '' || ($filters['action'] ?? '') !== '';

  $formatModule = function (?string $raw): string {
    return match ($raw) {
      'Trf' => 'TRF',
      'Rfq' => 'RFQ',
      'Grn' => 'GRN',
      'PurchaseOrder' => 'Purchase Order',
      'PurchaseRequisition' => 'Purchase Requisition',
      'CostComparativeStatement' => 'CCS Statement',
      'FlightLog' => 'Flight Log',
      'DepartmentBudget' => 'Budget Control',
      'ExceptionRequest' => 'Exception',
      'UserManagement' => 'User Access',
      default => \Illuminate\Support\Str::headline($raw ?: 'System'),
    };
  };
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Audit Trail &amp; Governance Ledger</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
        <i class="bi bi-shield-check text-success me-1"></i> Immutable Ledger
      </span>
    </div>
  </div>

  <!-- Compact KPI Strip -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Total Events</span>
            <div class="stat-icon-box icon-primary" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-journal-text"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums;">{{ number_format($stats['total']) }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-accent card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Today</span>
            <div class="stat-icon-box icon-accent" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums;">{{ number_format($stats['today']) }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Modules Logged</span>
            <div class="stat-icon-box icon-info" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-layers"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums;">{{ $stats['modules'] }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Active Officers</span>
            <div class="stat-icon-box icon-warning" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-person-check"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums;">{{ $stats['officers'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Standalone ERP Filter Bar (Aligned 12-Col Grid Matching PO/TRF/RFQ) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2 px-3">
      <form method="GET" action="{{ route('audit') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="search"
              name="q"
              class="form-control bg-white border-start-0 ps-0"
              value="{{ $filters['q'] }}"
              placeholder="Search reference, officer, or detail..."
              aria-label="Search audit trail">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select name="module" class="form-select erp-filter-ctrl bg-white" onchange="this.form.submit()" aria-label="Filter by module">
            <option value="">All Modules ({{ $modules->count() }})</option>
            @foreach ($modules as $mod)
              <option value="{{ $mod }}" @selected($filters['module'] === $mod)>
                {{ $formatModule($mod) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="action" class="form-select erp-filter-ctrl bg-white" onchange="this.form.submit()" aria-label="Filter by action">
            <option value="">All Actions</option>
            @foreach ($actions as $act)
              <option value="{{ $act }}" @selected($filters['action'] === $act)>
                {{ \Illuminate\Support\Str::headline($act) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex align-items-center gap-2 justify-content-md-end">
          <button type="submit" class="btn btn-primary erp-filter-ctrl fw-semibold px-3 flex-grow-1">Apply</button>
          @if ($hasFilters)
            <a href="{{ route('audit') }}" class="btn btn-outline-secondary erp-filter-ctrl px-2" title="Reset Filters" aria-label="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Minimal Ledger Table Card -->
  <div class="card card-panel shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <h2 class="h6 mb-0 fw-bold text-dark">Activity Ledger</h2>
        <span class="badge bg-light text-secondary border" style="font-size: 11px;">
          {{ number_format($logs->total()) }} {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}
        </span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light text-uppercase" style="font-size: 11px; letter-spacing: 0.4px;">
          <tr>
            <th class="ps-4 py-2" style="width: 150px;">Timestamp</th>
            <th class="py-2" style="width: 200px;">Officer</th>
            <th class="py-2">Event &amp; Summary</th>
            <th class="pe-4 py-2 text-end" style="width: 185px;">Module &amp; Ref</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            @php
              $actionLower = strtolower($log->action ?? '');
              $actionLabel = \Illuminate\Support\Str::headline($log->action ?? 'Event');
              $moduleLabel = $formatModule($log->module);

              $badgeStyle = match (true) {
                str_contains($actionLower, 'fail') || str_contains($actionLower, 'reject') || str_contains($actionLower, 'cancel') || str_contains($actionLower, 'reverse') || str_contains($actionLower, 'blacklist')
                  => ['bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA'],
                str_contains($actionLower, 'approve') || str_contains($actionLower, 'settle') || str_contains($actionLower, 'resolve')
                  => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0'],
                str_contains($actionLower, 'alert') || str_contains($actionLower, 'pushback') || str_contains($actionLower, 'escalat')
                  => ['bg' => '#FFFBEB', 'color' => '#B45309', 'border' => '#FDE68A'],
                str_contains($actionLower, 'create') || str_contains($actionLower, 'respond') || str_contains($actionLower, 'dispatch')
                  => ['bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE'],
                default
                  => ['bg' => '#F8FAFC', 'color' => '#475569', 'border' => '#E2E8F0'],
              };

              $rawDetail = trim((string) ($log->detail ?? ''));
              $summaryDetail = $rawDetail;
              $technicalTrace = null;
              if (strlen($rawDetail) > 180 && str_contains($rawDetail, ': Failed to ')) {
                [$summaryDetail, $rest] = explode(': ', $rawDetail, 2);
                $technicalTrace = $rest;
              }
            @endphp
            <tr>
              <td class="ps-4 py-2 text-nowrap">
                <div class="fw-medium text-dark" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                  {{ $log->created_at?->format('d M Y') }}
                </div>
                <div class="text-muted font-monospace" style="font-size: 11px;">
                  {{ $log->created_at?->format('H:i:s') }}
                </div>
              </td>
              <td class="py-2">
                <div class="fw-semibold text-dark" style="font-size: 12.5px;">{{ $log->user?->name ?? 'System Control' }}</div>
                <div class="text-muted" style="font-size: 11px;">
                  {{ $log->user?->designationLabel() ?? 'Automated Governance' }}
                </div>
              </td>
              <td class="py-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge fw-semibold px-2 py-1" style="font-size: 11px; background: {{ $badgeStyle['bg'] }}; color: {{ $badgeStyle['color'] }}; border: 1px solid {{ $badgeStyle['border'] }};">
                    {{ $actionLabel }}
                  </span>
                  @if ($summaryDetail !== '')
                    <span class="text-secondary" style="font-size: 12.5px; line-height: 1.4;">
                      {{ $summaryDetail }}
                    </span>
                  @endif
                </div>
                @if ($technicalTrace)
                  <button
                    type="button"
                    class="btn btn-link btn-sm p-0 mt-1 text-decoration-none fw-semibold text-danger"
                    style="font-size: 11px;"
                    data-bs-toggle="collapse"
                    data-bs-target="#trace-{{ $log->id }}"
                    aria-expanded="false">
                    View diagnostic detail &rarr;
                  </button>
                  <div class="collapse mt-2" id="trace-{{ $log->id }}">
                    <div class="p-2 rounded-2 bg-light border text-muted font-monospace" style="font-size: 11px; line-height: 1.4; word-break: break-word;">
                      {{ $technicalTrace }}
                    </div>
                  </div>
                @endif
              </td>
              <td class="pe-4 py-2 text-end text-nowrap">
                <div class="fw-semibold text-dark" style="font-size: 12px;">{{ $moduleLabel }}</div>
                @if ($log->reference)
                  <div class="text-muted font-monospace" style="font-size: 11px;">{{ $log->reference }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                <div>No audit records match the active filters.</div>
                @if ($hasFilters)
                  <a href="{{ route('audit') }}" class="btn btn-sm btn-outline-secondary mt-2">Clear Filters</a>
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($logs->total() > 0)
      <div class="bg-white border-top px-4 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 12px;">
        <div class="text-muted">
          Showing <strong>{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</strong> of <strong>{{ number_format($logs->total()) }}</strong> entries
        </div>

        @if ($logs->hasPages())
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
              @if ($logs->onFirstPage())
                <span class="btn btn-light border disabled text-muted px-2">
                  <i class="bi bi-chevron-left"></i> Prev
                </span>
              @else
                <a href="{{ $logs->previousPageUrl() }}" class="btn btn-light border text-dark px-2 fw-semibold">
                  <i class="bi bi-chevron-left"></i> Prev
                </a>
              @endif

              @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="btn btn-light border text-dark px-2 fw-semibold">
                  Next <i class="bi bi-chevron-right"></i>
                </a>
              @else
                <span class="btn btn-light border disabled text-muted px-2">
                  Next <i class="bi bi-chevron-right"></i>
                </span>
              @endif
            </div>
          </div>
        @endif
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'audit', crumb: 'Governance', title: 'Audit Trail' });
@endsection
