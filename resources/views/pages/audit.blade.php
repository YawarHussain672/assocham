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

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container">
    <!-- Integrated Toolbar -->
    <div class="erp-table-toolbar">
      <form method="GET" action="{{ route('audit') }}" class="d-flex flex-wrap align-items-center gap-2 mb-0 w-100">
        <div class="erp-search-wrap flex-grow-1" style="min-width: 240px;">
          <i class="bi bi-search erp-search-icon"></i>
          <input
            type="search"
            name="q"
            class="erp-search-input erp-filter-ctrl"
            value="{{ $filters['q'] }}"
            placeholder="Search reference, officer, or detail..."
            aria-label="Search audit trail">
        </div>

        <div style="width: 200px;">
          <select name="module" class="erp-select erp-filter-ctrl" onchange="this.form.submit()" aria-label="Filter by module">
            <option value="">All Modules ({{ $modules->count() }})</option>
            @foreach ($modules as $mod)
              <option value="{{ $mod }}" @selected($filters['module'] === $mod)>
                {{ $formatModule($mod) }}
              </option>
            @endforeach
          </select>
        </div>

        <div style="width: 170px;">
          <select name="action" class="erp-select erp-filter-ctrl" onchange="this.form.submit()" aria-label="Filter by action">
            <option value="">All Actions</option>
            @foreach ($actions as $act)
              <option value="{{ $act }}" @selected($filters['action'] === $act)>
                {{ \Illuminate\Support\Str::headline($act) }}
              </option>
            @endforeach
          </select>
        </div>

        <button type="submit" class="btn btn-sm btn-primary erp-filter-ctrl px-3 fw-semibold">Apply</button>
        @if ($hasFilters)
          <a href="{{ route('audit') }}" class="erp-btn-reset erp-filter-ctrl" title="Reset Filters" aria-label="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        @endif
      </form>
    </div>

    <table class="erp-table" style="font-size: 13.5px;">
      <thead>
        <tr>
          <th class="ps-4 py-3" style="width: 170px;">Timestamp</th>
          <th class="py-3" style="width: 220px;">Officer</th>
          <th class="py-3">Event &amp; Summary</th>
          <th class="pe-4 py-3 text-end" style="width: 200px;">Module &amp; Ref</th>
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

    @if ($logs->total() > 0)
      <div class="erp-table-footer">
        <div class="text-muted small">
          Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
        </div>

        @if ($logs->hasPages())
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
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
