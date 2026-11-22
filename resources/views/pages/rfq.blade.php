@extends('layouts.app')

@section('title', 'RFQ & Quotations')

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
    'dispatched'      => ['label' => 'Dispatched', 'badge' => 'badge-soft-warning'],
    'quotes_received' => ['label' => 'Quotes Received', 'badge' => 'badge-soft-info'],
    'approved'        => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
  ];

  $rfqTotalCount = $rfqs->count();
  $rfqQuotesReceivedCount = $rfqs->filter(fn($r) => $r->quotes_received > 0)->count();
  $rfqAwaitingCount = $rfqs->filter(fn($r) => $r->quotes_received == 0)->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-0">Request for Quotation (RFQ)</h1>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('rfq.create') }}" class="btn btn-sm btn-primary fw-semibold shadow-sm">
          <i class="bi bi-plus-circle me-1"></i> New RFQ
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
    .hover-primary:hover {
      color: #c8102e !important;
      text-decoration: underline !important;
    }
    .vendor-link {
      color: #0f172a;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s ease;
    }
    .vendor-link:hover {
      color: #c8102e;
      text-decoration: underline;
    }
    .pr-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 12px !important;
      font-weight: 600;
      color: #c8102e !important;
      text-decoration: none;
      transition: color 0.15s ease;
      display: inline-block;
      white-space: nowrap;
      letter-spacing: -0.01em;
    }
    .pr-num-code:hover {
      color: #a50d24 !important;
      text-decoration: underline !important;
    }

    /* Responsive & clean tightly-fitted table matching PR page */
    .rfq-table-container {
      width: 100%;
      overflow-x: auto;
      overflow-y: visible;
    }
    #rfqTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #rfqTable thead th {
      font-size: 12.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 12px 14px !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
    }
    #rfqTable tbody td {
      padding: 13px 14px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      background-color: #ffffff;
      line-height: 1.35 !important;
      white-space: normal;
      transition: background-color 0.15s ease;
    }
    #rfqTable tbody tr:hover td {
      background-color: #f8faff;
    }
    #rfqTable tbody tr:last-child td {
      border-bottom: none !important;
    }
    #rfqTable th:first-child,
    #rfqTable td:first-child {
      padding-left: 16px !important;
    }
    #rfqTable th:last-child,
    #rfqTable td:last-child {
      padding-right: 16px !important;
    }

    .rfq-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 13.5px !important;
      font-weight: 700;
      color: #0f172a !important;
      text-decoration: none;
      transition: color 0.15s ease;
      display: inline-block;
      white-space: nowrap;
      letter-spacing: -0.01em;
    }
    .rfq-num-code:hover {
      color: #c8102e !important;
      text-decoration: underline !important;
    }

    .rfq-title-text {
      font-size: 14px !important;
      font-weight: 600;
      color: #0f172a;
      white-space: normal !important;
      word-break: break-word !important;
      line-height: 1.35;
      display: block;
      max-width: 450px;
    }

    .rfq-sector-badge {
      font-size: 11px !important;
      font-weight: 500;
      padding: 3px 8px;
      border-radius: 4px;
      white-space: normal !important;
      word-break: break-word !important;
      line-height: 1.25;
      display: inline-block;
      text-align: left;
      letter-spacing: 0.01em;
    }

    .rfq-vendors-text {
      font-size: 13px !important;
      line-height: 1.35;
      max-width: 380px;
      word-break: break-word;
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

    /* Action View Button matching PR table */
    .dash-review-btn {
      height: 31px;
      font-size: 13px;
      font-weight: 500;
      padding: 0 14px;
      line-height: 1;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    /* Polished, Unified Action Controls matching PR */
    .rfq-action-cell {
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
    .rfq-action-menu {
      min-width: 130px;
      max-width: 160px;
      padding: 2.5px;
      border-radius: 5px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.12);
      font-size: 11px;
      z-index: 1060;
    }
    .rfq-action-menu .dropdown-item {
      padding: 4px 7px;
      border-radius: 3.5px;
      font-size: 11px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background-color 0.15s ease;
    }
    .rfq-action-menu .dropdown-item:hover {
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
    .erp-filter-ctrl.form-select {
      background-position: right 0.75rem center !important;
      padding-right: 2.25rem !important;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  </style>

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="rfqSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectRfqTab('all')">
      All RFQs <span class="erp-switch-count">{{ $rfqTotalCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="quotes_received" onclick="selectRfqTab('quotes_received')">
      Quotes Received <span class="erp-switch-count">{{ $rfqQuotesReceivedCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="awaiting" onclick="selectRfqTab('awaiting')">
      Awaiting Quotes <span class="erp-switch-count">{{ $rfqAwaitingCount }}</span>
    </button>
  </div>

  <!-- Unified Premium ERP Table Container -->
  <div class="erp-table-container">
    <!-- Integrated Search & Filter Toolbar -->
    <div class="erp-table-toolbar">
      <div class="erp-toolbar-left">
        @php
          $showBuyerFilter = isset($activeBuyers) && $activeBuyers->count() > 1;
          $allSectors = $rfqs->map(fn($r) => $r->sector ?: ($r->purchaseRequisition?->department ?? null))->filter()->unique()->sort();
        @endphp
        <!-- Live Search -->
        <div class="erp-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="rfqSearchInput" class="erp-search-input" placeholder="Search RFQ, PR, project, vendor..." onkeyup="applyRfqFilters()">
        </div>

        <!-- Sector / Department Dropdown -->
        <select id="rfqSectorFilter" class="erp-select" onchange="applyRfqFilters()">
          <option value="all">All Sectors / Depts</option>
          @foreach ($allSectors as $sec)
            <option value="{{ strtolower($sec) }}">{{ $sec }}</option>
          @endforeach
        </select>

        @if ($showBuyerFilter)
          <select id="rfqBuyerFilter" class="erp-select" onchange="applyRfqFilters()">
            <option value="all">All Buyers</option>
            @foreach ($activeBuyers as $buyerOpt)
              <option value="{{ $buyerOpt->id }}">{{ $buyerOpt->name }}</option>
            @endforeach
          </select>
        @endif

        <!-- Reset Button -->
        <button type="button" class="erp-btn-reset d-none" id="clearRfqFilterBtn" onclick="resetRfqFilters()" title="Reset Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
          <span>Reset</span>
        </button>
      </div>
    </div>

    <div class="rfq-table-container">
      <table class="erp-table table-hover align-middle mb-0" id="rfqTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">RFQ NO.</th>
            <th style="width: 15%;">SECTOR</th>
            <th style="width: 35%;">PROJECT TITLE</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rfqs as $rfq)
            @php
              $pr = $rfq->purchaseRequisition;
              $items = $pr?->items ?? collect();
              
              // Use PR title if present, otherwise join line item names
              if (!empty($pr?->title)) {
                $rfqTitle = $pr->title;
              } elseif ($items->isNotEmpty()) {
                $rfqTitle = $items->pluck('item_name')->join(', ');
              } else {
                $rfqTitle = ($pr?->department ? $pr->department . ' Requisition' : 'Procurement Requisition');
              }
              
              $filterStatus = in_array($rfq->status, ['quotes_received', 'approved']) ? 'quotes_received' : 'awaiting';
              $isQuotesReceived = in_array($rfq->status, ['quotes_received', 'approved']);
              $effectiveSector = strtolower($rfq->sector ?: ($pr?->department ?? 'general'));

              $ccs = $rfq->costComparativeStatement;
              $rfqDisplayProject = $rfq->project_name ?: $rfqTitle;
            @endphp
            <tr class="rfq-row" data-status="{{ $filterStatus }}" data-dept="{{ $pr?->department ?? '' }}" data-sector="{{ $effectiveSector }}" data-buyer="{{ $rfq->poc_user_id ?? '' }}">
              <!-- 1. RFQ No. -->
              <td class="ps-3">
                <a href="{{ route('rfq.show', $rfq) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;" title="{{ $rfq->rfq_number }}">
                  {{ $rfq->rfq_number }}
                </a>
              </td>

              <!-- 2. Sector -->
              <td>
                <span class="text-secondary" style="font-size: 13.5px;">
                  {{ $rfq->sector ?: ($pr?->department ?? 'General') }}
                </span>
              </td>

              <!-- 3. Project Title -->
              <td>
                <span class="rfq-title-text" title="{{ $rfqDisplayProject }}">{{ $rfqDisplayProject }}</span>
              </td>

              <!-- 4. Status -->
              <td>
                @if ($isQuotesReceived)
                  <span class="badge badge-soft-success status-badge-pipeline">Quotes In</span>
                @else
                  <span class="badge badge-soft-warning status-badge-pipeline">Awaiting</span>
                @endif
              </td>

              <!-- 5. Action -->
              <td class="text-end pe-3 rfq-action-cell">
                <div class="erp-row-actions">
                  <a href="{{ route('rfq.show', $rfq) }}" class="erp-btn-action-view">
                    <i class="bi bi-eye"></i> View
                  </a>
                  <div class="dropdown">
                    <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                      <li>
                        <a class="dropdown-item" href="{{ route('rfq.show', $rfq) }}">
                          <i class="bi bi-eye text-secondary"></i> View Quotations
                        </a>
                      </li>
                      @if ($rfq->purchase_requisition_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
                        <li>
                          <a class="dropdown-item" href="{{ route('requisition.show', $rfq->purchase_requisition_id) }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i> View Linked PR
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
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No RFQs raised yet.</span>
                </td>
              </tr>
            @endforelse

            <!-- Filter Empty State (Shown when filters match 0 RFQs) -->
            <tr id="rfqNoFilterMatchRow" style="display: none;">
              <td colspan="5" class="text-center py-5 text-muted bg-white">
                <div class="d-flex flex-column align-items-center justify-content-center">
                  <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 52px; height: 52px; background: #f1f5f9; color: #64748b;">
                    <i class="bi bi-funnel fs-4"></i>
                  </div>
                  <div class="fw-bold text-dark mb-1" style="font-size: 14.5px;">No RFQs match your filter criteria</div>
                  <div class="text-muted small mb-3" style="max-width: 420px;">
                    We couldn't find any Requests for Quotation matching the selected sector, quotation status, or search query.
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold" onclick="resetRfqFilters()">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Reset All Filters</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Pagination Footer (10 items per page) -->
      <div class="table-pagination-footer d-flex flex-wrap align-items-center justify-content-between gap-3" id="rfqPaginationWrapper" style="display: none !important;">
        <div class="text-muted small" id="rfqPaginationInfo" style="font-size: 12.5px;">
          Showing <span id="rfqPagStart">1</span> to <span id="rfqPagEnd">{{ min(10, $rfqs->count()) }}</span> of <span id="rfqPagTotal">{{ $rfqs->count() }}</span> RFQs
        </div>
        <div class="d-flex align-items-center gap-1.5" id="rfqPaginationControls">
          <button type="button" class="pagination-btn" id="rfqPrevPageBtn" onclick="changeRfqPage(-1)" disabled>
            <i class="bi bi-chevron-left"></i>
            <span>Previous</span>
          </button>
          <div class="d-flex align-items-center gap-1" id="rfqPageNumbers">
            <!-- Dynamic page buttons -->
          </div>
          <button type="button" class="pagination-btn" id="rfqNextPageBtn" onclick="changeRfqPage(1)">
            <span>Next</span>
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ & Quotations' });

(function() {
const PAGE_SIZE = 10;
let currentPage = 1;
let matchedRows = [];

function renderRfqPagination() {
  const total = matchedRows.length;
  const totalPages = Math.ceil(total / PAGE_SIZE) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const startIndex = (currentPage - 1) * PAGE_SIZE;
  const endIndex = startIndex + PAGE_SIZE;

  // Show only current page items among matched rows
  matchedRows.forEach((row, idx) => {
    if (idx >= startIndex && idx < endIndex) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Update counts
  const pagStart = document.getElementById('rfqPagStart');
  const pagEnd = document.getElementById('rfqPagEnd');
  const pagTotal = document.getElementById('rfqPagTotal');
  const paginationWrapper = document.getElementById('rfqPaginationWrapper');
  const countEl = document.getElementById('rfqFilterCount');
  const counterEl = document.getElementById('rfqCounterCount');

  if (pagStart) pagStart.textContent = total === 0 ? 0 : startIndex + 1;
  if (pagEnd) pagEnd.textContent = Math.min(endIndex, total);
  if (pagTotal) pagTotal.textContent = total;

  if (countEl) {
    countEl.innerText = `${total} RFQs`;
  }
  if (counterEl) {
    counterEl.textContent = total;
  }

  // Toggle Empty Filter Match State (only show when RFQs exist but active filters match 0)
  const noMatchRow = document.getElementById('rfqNoFilterMatchRow');
  if (noMatchRow) {
    const totalRfqRows = document.querySelectorAll('.rfq-row').length;
    noMatchRow.style.display = (total === 0 && totalRfqRows > 0) ? '' : 'none';
  }

  // Update Buttons
  const prevBtn = document.getElementById('rfqPrevPageBtn');
  const nextBtn = document.getElementById('rfqNextPageBtn');
  if (prevBtn) prevBtn.disabled = (currentPage <= 1);
  if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);

  // Render Page Number Pills
  const pageNumbersContainer = document.getElementById('rfqPageNumbers');
  if (pageNumbersContainer) {
    pageNumbersContainer.innerHTML = '';
    if (totalPages > 1) {
      for (let p = 1; p <= totalPages; p++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pagination-page-pill' + (p === currentPage ? ' active' : '');
        btn.textContent = p;
        btn.onclick = () => {
          currentPage = p;
          renderRfqPagination();
        };
        pageNumbersContainer.appendChild(btn);
      }
    }
  }

  if (paginationWrapper) {
    if (total > PAGE_SIZE) {
      paginationWrapper.style.setProperty('display', 'flex', 'important');
    } else {
      paginationWrapper.style.setProperty('display', 'none', 'important');
    }
  }
}

window.changeRfqPage = function(delta) {
  currentPage += delta;
  renderRfqPagination();
};

let currentRfqStatus = 'all';

window.selectRfqTab = function(status) {
  currentRfqStatus = status;
  window.applyRfqFilters();
};

window.applyRfqFilters = function() {
  const query = (document.getElementById('rfqSearchInput')?.value || '').toLowerCase().trim();
  const sectorFilter = (document.getElementById('rfqSectorFilter')?.value || 'all').toLowerCase().trim();
  const statusFilter = currentRfqStatus;
  const buyerFilter = document.getElementById('rfqBuyerFilter')?.value || 'all';

  // Sync Enseur Switch Bar tabs
  document.querySelectorAll('#rfqSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === statusFilter);
  });

  const rows = Array.from(document.querySelectorAll('.rfq-row'));
  matchedRows = [];

  rows.forEach(row => {
    const sector = (row.getAttribute('data-sector') || '').toLowerCase().trim();
    const dept = (row.getAttribute('data-dept') || '').toLowerCase().trim();
    const status = row.getAttribute('data-status') || '';
    const buyer = row.getAttribute('data-buyer') || '';
    const text = row.innerText.toLowerCase();

    const matchesSector = (sectorFilter === 'all') || (sector === sectorFilter) || (dept === sectorFilter);
    const matchesStatus = (statusFilter === 'all') || (status === statusFilter);
    const matchesBuyer = (buyerFilter === 'all') || (buyer === buyerFilter);
    const matchesQuery = !query || text.includes(query);

    if (matchesSector && matchesStatus && matchesBuyer && matchesQuery) {
      matchedRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const clearBtn = document.getElementById('clearRfqFilterBtn');
  if (clearBtn) {
    if (query || sectorFilter !== 'all' || statusFilter !== 'all' || buyerFilter !== 'all') {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  currentPage = 1;
  renderRfqPagination();
};

window.resetRfqFilters = function() {
  const searchInput = document.getElementById('rfqSearchInput');
  const sectorFilter = document.getElementById('rfqSectorFilter');
  const buyerFilter = document.getElementById('rfqBuyerFilter');
  if (searchInput) searchInput.value = '';
  if (sectorFilter) sectorFilter.value = 'all';
  currentRfqStatus = 'all';
  if (buyerFilter) buyerFilter.value = 'all';
  window.applyRfqFilters();
};

// Initial run
window.applyRfqFilters();
})();
@endsection
