@extends('layouts.app')

@section('title', 'Exception Handling')

@php
  $typeMeta = [
    'single_quotation' => 'Single Quotation',
    'budget_exceed' => 'Budget Exceed',
    'po_force_close' => 'PO Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Check',
  ];
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'badge' => 'warning'],
    'approved' => ['label' => 'Approved', 'badge' => 'success'],
    'rejected' => ['label' => 'Rejected', 'badge' => 'danger'],
    'on_hold'  => ['label' => 'On Hold', 'badge' => 'warning'],
  ];

  $totalExc = $exceptions->count();
  $pendingExc = $exceptions->whereIn('status', ['pending', 'on_hold'])->count();
  $budgetOverrides = $exceptions->where('exception_type', 'budget_exceed')->count();
  $approvedExc = $exceptions->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Governance Exceptions &amp; Overrides</h1>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('exceptions.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-shield-plus me-1"></i> File Exception
        </a>
      </div>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>{{ session('status') }}</div>
    </div>
  @endif

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="excSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectExcTab('all')">
      All Exceptions <span class="erp-switch-count">{{ $totalExc }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending" onclick="selectExcTab('pending')">
      Pending Review <span class="erp-switch-count">{{ $pendingExc }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectExcTab('approved')">
      Approved <span class="erp-switch-count">{{ $approvedExc }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="rejected" onclick="selectExcTab('rejected')">
      Rejected <span class="erp-switch-count">{{ $exceptions->where('status', 'rejected')->count() }}</span>
    </button>
  </div>

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container">
    <div class="erp-table-toolbar">
      <div class="erp-toolbar-left">
        <div class="erp-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="excSearchInput" class="erp-search-input" placeholder="Search exception type, justification, requester..." oninput="applyExcFilters()">
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="erp-table align-middle mb-0" id="excTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">EXCEPTION TYPE</th>
            <th style="width: 35%;">JUSTIFICATION</th>
            <th style="width: 15%;">REQUESTER</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($exceptions as $exception)
            @php 
              $meta = $statusMeta[$exception->status] ?? $statusMeta['pending'];
              $excGroup = in_array($exception->status, ['pending', 'on_hold']) ? 'pending' : $exception->status;
            @endphp
            <tr class="exc-row" data-status="{{ $excGroup }}" data-search="{{ strtolower(($typeMeta[$exception->exception_type] ?? $exception->exception_type) . ' ' . $exception->justification . ' ' . ($exception->requester?->name ?? '')) }}">
              <!-- 1. Exception Type -->
              <td class="ps-3">
                <a href="{{ route('exceptions.show', $exception) }}" class="erp-code-link" style="font-size: 13.5px;">
                  {{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }}
                </a>
              </td>

              <!-- 2. Justification -->
              <td>
                <span class="fw-semibold text-main" style="font-size: 13.5px;">
                  {{ \Illuminate\Support\Str::limit($exception->justification, 60) }}
                </span>
              </td>

              <!-- 3. Requester -->
              <td>
                <span class="text-secondary" style="font-size: 13.5px;">
                  {{ $exception->requester?->name ?? 'System' }}
                </span>
              </td>

              <!-- 4. Status -->
              <td>
                <span class="badge badge-soft-{{ $meta['badge'] ?? 'warning' }} status-badge-pipeline">
                  {{ $meta['label'] }}
                </span>
              </td>

              <!-- 5. Action -->
              <td class="text-end pe-3">
                <div class="erp-row-actions">
                  @if ($exception->status === 'pending' && in_array(auth()->user()?->role, array_filter([$exception->approver_queue, \App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::SG]), true))
                    <a href="{{ route('exceptions.show', $exception) }}" class="erp-btn-action-review text-nowrap">
                      Review &amp; Decide
                    </a>
                  @else
                    <a href="{{ route('exceptions.show', $exception) }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                  @endif
                  <div class="dropdown">
                    <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                      <li>
                        <a class="dropdown-item" href="{{ route('exceptions.show', $exception) }}">
                          <i class="bi bi-eye text-secondary"></i> View Case Dossier
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                No exceptions filed yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
let currentExcStatus = 'all';

function selectExcTab(status) {
  currentExcStatus = status;
  applyExcFilters();
}

function applyExcFilters() {
  const q = (document.getElementById('excSearchInput')?.value || '').toLowerCase().trim();
  const status = currentExcStatus;

  document.querySelectorAll('#excSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === status);
  });

  const rows = document.querySelectorAll('.exc-row');
  let visible = 0;

  rows.forEach(row => {
    const rStatus = row.dataset.status || '';
    const rSearch = row.dataset.search || '';

    const matchesStatus = (status === 'all' || rStatus === status);
    const matchesQ = (!q || rSearch.includes(q));

    if (matchesStatus && matchesQ) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  const countEl = document.getElementById('excCounterCount');
  if (countEl) countEl.textContent = visible;
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exceptions' });
@endsection