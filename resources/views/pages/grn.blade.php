@extends('layouts.app')

@section('title', 'Goods Receipt (GRN)')

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending HOD', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
    'on_hold'     => ['label' => 'On Hold', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
    'approved'    => ['label' => 'Verified Closed', 'badge' => 'bg-success-subtle text-success border border-success-subtle'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle'],
  ];

  $grnTotalCount = $grns->count();
  $grnPendingCount = $grns->filter(fn($g) => in_array($g->status, ['pending_hod', 'on_hold']))->count();
  $grnApprovedCount = $grns->where('status', 'approved')->count();
  $grnRejectedCount = $grns->where('status', 'rejected')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Goods Receipt Notes (GRN)</h1>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('grn.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
          <i class="bi bi-box-seam"></i>
          <span>Log Goods Receipt</span>
        </a>
      </div>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <style>
    .grn-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 12px !important;
      font-weight: 600;
      color: #0f172a !important;
      text-decoration: none;
      transition: color 0.15s ease;
      display: inline-block;
      white-space: nowrap;
      letter-spacing: -0.01em;
    }
    .grn-num-code:hover {
      color: #c8102e !important;
      text-decoration: underline !important;
    }

    .pr-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 11px !important;
      font-weight: 600;
      color: #c8102e !important;
      text-decoration: none;
      transition: color 0.15s ease;
      display: inline-block;
      white-space: nowrap;
    }
    .pr-num-code:hover {
      color: #a50d24 !important;
      text-decoration: underline !important;
    }

    .grn-dept-badge {
      font-size: 8.5px !important;
      font-weight: 500;
      padding: 2px 6px;
      border-radius: 4px;
      display: inline-block;
      letter-spacing: 0.01em;
    }

    .status-badge-custom {
      font-size: 9px !important;
      font-weight: 600;
      padding: 2px 6px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      white-space: nowrap;
    }

    /* Responsive & clean tightly-fitted table matching PR page */
    .grn-table-container {
      width: 100%;
      overflow-x: auto;
      overflow-y: visible;
    }
    #grnTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #grnTable thead th {
      font-size: 12.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 12px 16px !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
    }
    #grnTable tbody td {
      padding: 13px 16px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      background-color: #ffffff;
      font-size: 13.5px !important;
      line-height: 1.45 !important;
      white-space: normal;
      transition: background-color 0.15s ease;
    }
    .dash-review-btn {
      height: 31px;
      padding: 0 14px;
      font-size: 13px;
      font-weight: 500;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    #grnTable tbody tr:hover td {
      background-color: #f8faff;
    }
    #grnTable tbody tr:last-child td {
      border-bottom: none !important;
    }
    #grnTable th:first-child,
    #grnTable td:first-child {
      padding-left: 16px !important;
    }
    #grnTable th:last-child,
    #grnTable td:last-child {
      padding-right: 16px !important;
    }

    /* Polished, Unified Action Controls matching PR */
    .grn-action-cell {
      padding: 6px 8px !important;
      white-space: nowrap !important;
      text-align: right;
    }
    .btn-action-dropdown {
      height: 24px !important;
      font-size: 10.5px !important;
      font-weight: 600 !important;
      padding: 0 8px !important;
      line-height: 1 !important;
      border-radius: 3.5px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
      gap: 3px !important;
    }
    .btn-action-dropdown i {
      font-size: 9.5px !important;
    }
    .btn-action-dropdown.dropdown-toggle::after {
      margin-left: 2px !important;
      vertical-align: 0.12em !important;
    }
    .action-alert-dot {
      position: absolute;
      top: -2px;
      right: -2px;
      width: 5.5px;
      height: 5.5px;
      border-radius: 50%;
      background-color: #f59e0b; /* Yellow/Amber */
      border: 1px solid #ffffff;
      box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.45);
      pointer-events: none;
      z-index: 3;
    }
    .grn-action-menu {
      min-width: 130px;
      max-width: 160px;
      padding: 2.5px;
      border-radius: 5px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.12);
      font-size: 11px;
      z-index: 1060;
    }
    .grn-action-menu .dropdown-item {
      padding: 4px 7px;
      border-radius: 3.5px;
      font-size: 11px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background-color 0.15s ease;
    }
    .grn-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }

    /* Client-side Table Pagination */
    .table-pagination-footer {
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 12px 18px;
    }
    .pagination-btn {
      height: 32px;
      font-size: 12px;
      font-weight: 600;
      padding: 0 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      color: #334155;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .pagination-btn:hover:not(:disabled) {
      background: #f8fafc;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .pagination-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      border-color: #e2e8f0;
      color: #94a3b8;
    }
    .pagination-page-pill {
      min-width: 32px;
      height: 32px;
      font-size: 12px;
      font-weight: 600;
      padding: 0 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      color: #334155;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .pagination-page-pill:hover:not(.active) {
      background: #f8fafc;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .pagination-page-pill.active {
      background: #c8102e;
      color: #ffffff;
      border-color: #c8102e;
      box-shadow: 0 1px 3px rgba(200, 16, 46, 0.25);
    }
  </style>

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="grnSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectGrnTab('all')">
      All Receipts <span class="erp-switch-count">{{ $grnTotalCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending_hod" onclick="selectGrnTab('pending_hod')">
      Pending Verification <span class="erp-switch-count">{{ $grnPendingCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectGrnTab('approved')">
      Verified Closed <span class="erp-switch-count">{{ $grnApprovedCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="rejected" onclick="selectGrnTab('rejected')">
      Rejected <span class="erp-switch-count">{{ $grnRejectedCount }}</span>
    </button>
  </div>

  <!-- Unified Premium ERP Table Container -->
  <div class="erp-table-container">
    <!-- Integrated Search & Filter Toolbar -->
    <div class="erp-table-toolbar">
      <div class="erp-toolbar-left">
        <!-- Live Search -->
        <div class="erp-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="grnSearchInput" class="erp-search-input" placeholder="Search GRN, PO No, vendor, challan / invoice..." oninput="applyGrnFilters()">
        </div>

        @php
          $allDepts = $grns->map(fn($g) => $g->purchaseRequisition?->department ?? $g->purchaseOrder?->purchaseRequisition?->department)->filter()->unique()->sort();
        @endphp
        <!-- Department Dropdown -->
        <select id="grnDeptFilter" class="erp-select" onchange="applyGrnFilters()">
          <option value="all">All Departments</option>
          @foreach ($allDepts as $d)
            <option value="{{ strtolower($d) }}">{{ $d }}</option>
          @endforeach
        </select>

        <!-- Reset Button -->
        <button type="button" class="erp-btn-reset d-none" id="clearGrnFilterBtn" onclick="resetGrnFilters()" title="Reset Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
          <span>Reset</span>
        </button>
      </div>
    </div>

    <div class="grn-table-container">
      <table class="erp-table table-hover align-middle mb-0" id="grnTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">GRN NO.</th>
            <th style="width: 25%;">PO NO.</th>
            <th style="width: 25%;">VENDOR</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
          </tr>
        </thead>
          <tbody>
            @forelse ($grns as $grn)
              @php
                $po = $grn->purchaseOrder;
                $pr = $grn->purchaseRequisition ?? $po?->purchaseRequisition;
                $dept = $pr?->department ?? 'General';
              @endphp
              <tr class="grn-row" data-status="{{ $grn->status }}" data-dept="{{ strtolower($dept) }}" data-search="{{ strtolower($grn->grn_number . ' ' . ($po?->po_number ?? '') . ' ' . ($pr?->pr_number ?? '') . ' ' . ($po?->vendor?->company_name ?? '') . ' ' . ($grn->invoice_number ?? '') . ' ' . $dept) }}">
                <!-- 1. GRN No -->
                <td class="ps-3">
                  <a href="{{ route('grn.show', $grn) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;" title="{{ $grn->grn_number }}">
                    {{ $grn->grn_number }}
                  </a>
                </td>

                <!-- 2. PO No. -->
                <td>
                  <span class="fw-semibold text-main font-monospace" style="font-size: 13.5px;">
                    {{ $po?->po_number ?? '—' }}
                  </span>
                  @if ($pr?->pr_number)
                    <div class="text-muted small font-monospace" style="font-size: 11.5px;">
                      {{ $pr->pr_number }}
                    </div>
                  @endif
                </td>

                <!-- 3. Vendor -->
                <td>
                  <div class="text-secondary" style="font-size: 13.5px;" title="{{ $po?->vendor?->company_name }}">
                    {{ $po?->vendor?->company_name ?? '—' }}
                  </div>
                  @if ($grn->invoice_number)
                    <div class="text-muted small" style="font-size: 11.5px;">
                      Inv: <span class="font-monospace text-dark">{{ $grn->invoice_number }}</span>
                    </div>
                  @endif
                </td>

                <!-- 4. Status -->
                <td>
                  @if ($grn->status === 'approved')
                    <span class="badge badge-soft-success status-badge-pipeline">Verified Closed</span>
                  @elseif ($grn->status === 'rejected')
                    <span class="badge badge-soft-danger status-badge-pipeline">Rejected</span>
                  @else
                    <span class="badge badge-soft-warning status-badge-pipeline">Pending HOD</span>
                  @endif
                </td>

                <!-- 5. Action -->
                <td class="text-end pe-3 grn-action-cell">
                  @php
                    $canEditThisGrn = in_array(auth()->user()?->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true) && $grn->status === 'pending_hod';
                  @endphp
                  <div class="erp-row-actions">
                    <a href="{{ route('grn.show', $grn) }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                    <div class="dropdown">
                      <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                        <li>
                          <a class="dropdown-item" href="{{ route('grn.show', $grn) }}">
                            <i class="bi bi-eye text-secondary"></i> View Details
                          </a>
                        </li>
                        @if ($grn->purchase_order_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po'))
                          <li>
                            <a class="dropdown-item" href="{{ route('po.show', $grn->purchase_order_id) }}">
                              <i class="bi bi-receipt text-secondary"></i> View Linked PO
                            </a>
                          </li>
                        @endif
                        @if ($canEditThisGrn)
                          <li>
                            <a class="dropdown-item" href="{{ route('grn.edit', $grn) }}">
                              <i class="bi bi-pencil text-secondary"></i> Edit GRN
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
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                  <span>No Goods Receipt Notes logged yet.</span>
                </td>
              </tr>
            @endforelse
            <tr id="emptyGrnFilterRow" class="d-none">
              <td colspan="5" class="text-center py-4 text-muted">
                <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary"></i>
                <span>No Goods Receipt Notes match your filter criteria.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div id="grnPaginationWrapper" class="table-pagination-footer d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2" style="display: none !important;">
        <div class="text-muted small" style="font-size: 12.5px;">
          Showing <span id="grnPagStart">0</span> to <span id="grnPagEnd">0</span> of <span id="grnPagTotal">{{ $grns->count() }}</span> Entries
        </div>
        <div class="d-flex align-items-center gap-1.5">
          <button type="button" class="pagination-btn" id="grnPrevPageBtn" onclick="changeGrnPage(-1)">
            <i class="bi bi-chevron-left"></i> Previous
          </button>
          <div id="grnPageNumbers" class="d-flex align-items-center gap-1"></div>
          <button type="button" class="pagination-btn" id="grnNextPageBtn" onclick="changeGrnPage(1)">
            Next <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
let currentGrnPage = 1;
const GRN_PAGE_SIZE = 10;
let matchedGrnRows = [];
let currentGrnStatus = 'all';

function selectGrnTab(status) {
  currentGrnStatus = status;
  applyGrnFilters();
}

function setGrnFilter(status) {
  selectGrnTab(status);
}

function applyGrnFilters() {
  const searchTerm = (document.getElementById('grnSearchInput')?.value || '').trim().toLowerCase();
  const selectedStatus = currentGrnStatus;
  const selectedDept = document.getElementById('grnDeptFilter')?.value || 'all';

  // Sync Enseur Switch Bar tabs
  document.querySelectorAll('#grnSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === selectedStatus);
  });

  const rows = Array.from(document.querySelectorAll('.grn-row'));
  matchedGrnRows = [];

  rows.forEach(row => {
    const rowStatus = row.dataset.status;
    const rowDept = row.dataset.dept || '';
    const rowSearch = row.dataset.search || '';

    const matchesStatus = (selectedStatus === 'all' || rowStatus === selectedStatus);
    const matchesDept = (selectedDept === 'all' || rowDept === selectedDept);
    const matchesSearch = (!searchTerm || rowSearch.includes(searchTerm));

    if (matchesStatus && matchesDept && matchesSearch) {
      matchedGrnRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const isFiltered = (selectedStatus !== 'all' || selectedDept !== 'all' || searchTerm !== '');
  const badge = document.getElementById('grnRecordCountBadge');
  if (badge) {
    badge.textContent = isFiltered ? `${matchedGrnRows.length} of {{ $grns->count() }} Entries` : `{{ $grns->count() }} Entries`;
  }

  const counterEl = document.getElementById('grnCounterCount');
  if (counterEl) {
    counterEl.textContent = matchedGrnRows.length;
  }

  const clearBtn = document.getElementById('clearGrnFilterBtn');
  if (clearBtn) {
    if (isFiltered) {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  currentGrnPage = 1;
  renderGrnPagination();
}

function renderGrnPagination() {
  const total = matchedGrnRows.length;
  const totalPages = Math.ceil(total / GRN_PAGE_SIZE) || 1;

  if (currentGrnPage < 1) currentGrnPage = 1;
  if (currentGrnPage > totalPages) currentGrnPage = totalPages;

  const startIdx = (currentGrnPage - 1) * GRN_PAGE_SIZE;
  const endIdx = startIdx + GRN_PAGE_SIZE;

  // Toggle display of rows
  matchedGrnRows.forEach((row, idx) => {
    if (idx >= startIdx && idx < endIdx) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Empty state handling
  const emptyRow = document.getElementById('emptyGrnFilterRow');
  if (emptyRow) {
    if (total === 0 && document.querySelectorAll('.grn-row').length > 0) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  // Update pagination UI elements: only display when total items exceed PAGE_SIZE (10)
  const paginationWrapper = document.getElementById('grnPaginationWrapper');
  if (paginationWrapper) {
    if (total > GRN_PAGE_SIZE) {
      paginationWrapper.style.setProperty('display', 'flex', 'important');
    } else {
      paginationWrapper.style.setProperty('display', 'none', 'important');
    }
  }

  const pagStart = document.getElementById('grnPagStart');
  const pagEnd = document.getElementById('grnPagEnd');
  const pagTotal = document.getElementById('grnPagTotal');
  if (pagStart && pagEnd && pagTotal) {
    pagStart.textContent = total === 0 ? 0 : startIdx + 1;
    pagEnd.textContent = Math.min(endIdx, total);
    pagTotal.textContent = total;
  }

  const prevBtn = document.getElementById('grnPrevPageBtn');
  const nextBtn = document.getElementById('grnNextPageBtn');
  if (prevBtn) prevBtn.disabled = (currentGrnPage <= 1);
  if (nextBtn) nextBtn.disabled = (currentGrnPage >= totalPages);

  // Render page pills (only if multiple pages)
  const pillsContainer = document.getElementById('grnPageNumbers');
  if (pillsContainer) {
    pillsContainer.innerHTML = '';
    if (totalPages > 1) {
      for (let p = 1; p <= totalPages; p++) {
        const pill = document.createElement('button');
        pill.type = 'button';
        pill.className = `pagination-page-pill ${p === currentGrnPage ? 'active' : ''}`;
        pill.textContent = p;
        pill.onclick = () => {
          currentGrnPage = p;
          renderGrnPagination();
        };
        pillsContainer.appendChild(pill);
      }
    }
  }
}

function changeGrnPage(delta) {
  currentGrnPage += delta;
  renderGrnPagination();
}

function resetGrnFilters() {
  if (document.getElementById('grnSearchInput')) document.getElementById('grnSearchInput').value = '';
  currentGrnStatus = 'all';
  if (document.getElementById('grnDeptFilter')) document.getElementById('grnDeptFilter').value = 'all';
  applyGrnFilters();
}

document.addEventListener('DOMContentLoaded', function() {
  applyGrnFilters();
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Goods Receipt (GRN)' });
@endsection
