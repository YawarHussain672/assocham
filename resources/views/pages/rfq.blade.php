@extends('layouts.app')

@section('title', 'RFQ & Quotations')

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
    'dispatched'      => ['label' => 'Dispatched', 'badge' => 'badge-soft-warning'],
    'quotes_received' => ['label' => 'Quotes Received', 'badge' => 'badge-soft-info'],
    'approved'        => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
  ];
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
        <a href="{{ route('rfq.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i> New RFQ
        </a>
      </div>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Status KPI Tab Cards (Total, Quotes Received, Dispatched) - Informational Only -->
  <div class="row g-2 mb-3">
    <!-- Total Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-primary h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-send-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $rfqs->count() }}</div>
        </div>
      </div>
    </div>

    <!-- Quotes Received Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-info h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Quotes Received</span>
            <div class="stat-icon-box icon-info" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-inbox-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $rfqs->filter(fn($r) => in_array($r->status, ['quotes_received', 'approved']))->count() }}</div>
        </div>
      </div>
    </div>

    <!-- Dispatched Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Dispatched</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $rfqs->where('status', 'dispatched')->count() }}</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Informational Cards - no pointer, no outline */
    .stat-card {
      cursor: default !important;
      user-select: none;
      outline: none !important;
    }
    .stat-card:focus,
    .stat-card:active {
      outline: none !important;
      box-shadow: none !important;
    }
    .hover-primary:hover {
      color: #0d6efd !important;
      text-decoration: underline !important;
    }
    .vendor-link {
      color: #0d6efd;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.15s ease;
    }
    .vendor-link:hover {
      color: #0a58ca;
      text-decoration: underline;
    }
    .pr-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 11.5px;
      font-weight: 600;
      color: #2563eb;
      text-decoration: none;
    }
    .pr-num-code:hover {
      text-decoration: underline;
    }
    /* Fixed & responsive layout across all screen sizes */
    .rfq-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    #rfqTable {
      width: 100% !important;
      min-width: 1100px;
      table-layout: auto !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #rfqTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #475569;
      vertical-align: middle;
      padding: 10px 10px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #rfqTable td {
      vertical-align: middle;
      padding: 10px 10px;
      font-size: 12px;
      line-height: 1.4;
      white-space: normal;
      border-bottom: 1px solid #f1f5f9;
    }
    .rfq-table-card {
      overflow: hidden;
      border-radius: 8px;
    }
    .rfq-action-menu {
      min-width: 165px;
      padding: 5px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
      font-size: 12px;
      z-index: 1060;
    }
    .rfq-action-menu .dropdown-item {
      padding: 6px 9px;
      border-radius: 5px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: background-color 0.15s ease;
    }
    .rfq-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }
    .rfq-action-dot {
      position: absolute;
      top: -3px;
      right: -3px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #f59e0b;
      border: 1.5px solid #ffffff;
      pointer-events: none;
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
      background: #2563eb;
      color: #ffffff;
      border-color: #2563eb;
      box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
    }
    .erp-filter-ctrl.form-select {
      background-position: right 0.75rem center !important;
      padding-right: 2.25rem !important;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  </style>

  <!-- Filter & Search Bar -->
  <div class="card shadow-sm border-0 mb-3" style="border-radius: 10px;">
    <div class="card-body py-2.5 px-3 bg-white">
      <div class="row g-2 align-items-center">
        <!-- Live Search -->
        <div class="col-12 col-md-4 col-lg-4">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="rfqSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="Search RFQ, PR, Project, Vendor..." onkeyup="applyRfqFilters()">
          </div>
        </div>

        <!-- Sector / Department Dropdown -->
        @php
          $allSectors = $rfqs->map(fn($r) => $r->sector ?: ($r->purchaseRequisition?->department ?? null))->filter()->unique()->sort();
        @endphp
        <div class="col-6 col-md-3 col-lg-3">
          <select id="rfqSectorFilter" class="form-select erp-filter-ctrl bg-white w-100" style="padding-right: 2rem;" onchange="applyRfqFilters()">
            <option value="all">All Sectors / Depts</option>
            @foreach ($allSectors as $sec)
              <option value="{{ strtolower($sec) }}">{{ $sec }}</option>
            @endforeach
          </select>
        </div>

        <!-- Status Dropdown -->
        <div class="col-6 col-md-3 col-lg-3">
          <select id="rfqStatusFilter" class="form-select erp-filter-ctrl bg-white w-100" style="padding-right: 2rem;" onchange="applyRfqFilters()">
            <option value="all">All Quotation Statuses</option>
            <option value="quotes_received">Received</option>
            <option value="awaiting">Awaiting Quotations</option>
          </select>
        </div>

        <!-- Reset Button & Count -->
        <div class="col-12 col-md-2 col-lg-2 d-flex align-items-center justify-content-between justify-content-md-end gap-2">
          <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none px-2.5" id="clearRfqFilterBtn" onclick="resetRfqFilters()" title="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
          <span class="text-muted small text-nowrap fw-semibold px-1" id="rfqFilterCount">{{ $rfqs->count() }} RFQs</span>
        </div>
      </div>
    </div>
  </div>

  <!-- RFQ Table Card -->
  <div class="card shadow-sm border-0 mb-4 rfq-table-card">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-send text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-main" style="font-size: 14.5px;">Requests for Quotation</h6>
      </div>
      <span class="badge bg-light text-dark border">{{ $rfqs->count() }} RFQs</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive rfq-table-container">
        <table class="table table-hover align-middle mb-0" id="rfqTable">
          <thead class="table-light">
            <tr>
              <th class="ps-3 text-nowrap" style="min-width: 95px;">RFQ NO.</th>
              <th class="text-nowrap" style="min-width: 175px;">LINKED PR NO.</th>
              <th class="pe-2 text-nowrap" style="min-width: 110px;">SECTOR / DEPT</th>
              <th class="ps-2 text-nowrap" style="min-width: 220px;">PROJECT NAME</th>
              <th class="text-nowrap" style="min-width: 220px;">VENDORS</th>
              <th class="text-center text-nowrap" style="min-width: 110px;">QUOTATIONS</th>
              <th class="text-center text-nowrap" style="min-width: 100px;">ISSUE DATE</th>
              <th class="text-end pe-3 text-nowrap" style="min-width: 100px;">ACTION</th>
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
                $hasExistingPo = $pr?->purchaseOrders?->isNotEmpty();
                $existingPo = $pr?->purchaseOrders?->first();
                $winningQuote = $ccs?->winningQuote();
                $awardedVendor = $ccs?->selectedVendor ?: $winningQuote?->vendor;
                $canGeneratePo = $isQuotesReceived && !$hasExistingPo && $awardedVendor && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD]);
              @endphp
              <tr class="rfq-row" data-status="{{ $filterStatus }}" data-dept="{{ $pr?->department ?? '' }}" data-sector="{{ $effectiveSector }}">
                <!-- 1. RFQ No. -->
                <td class="ps-3 text-nowrap">
                  <a href="{{ route('rfq.show', $rfq) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $rfq->rfq_number }}</code>
                  </a>
                </td>

                <!-- 2. Linked PR No. -->
                <td class="text-nowrap">
                  @if ($pr)
                    <a href="{{ route('requisition.show', $pr) }}" class="pr-num-code" title="{{ $pr->pr_number }}">
                      {{ $pr->pr_number }}
                    </a>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>

                <!-- 3. Sector / Department -->
                <td class="pe-2 text-nowrap">
                  <span class="badge bg-light text-dark border fw-medium px-2 py-1" style="font-size: 11px;">
                    <i class="bi bi-diagram-3 text-secondary me-1"></i>{{ $rfq->sector ?: ($pr?->department ?? '—') }}
                  </span>
                </td>

                <!-- 4. Project Name -->
                <td class="ps-2">
                  <div class="fw-semibold text-dark" style="max-width: 280px; word-break: break-word;">
                    {{ $rfq->project_name ?: $rfqTitle }}
                  </div>
                  @if ($rfq->sector && $pr?->department && strtolower($rfq->sector) !== strtolower($pr->department))
                    <div class="small text-muted" style="font-size: 11px;">
                      Dept: {{ $pr->department }}
                    </div>
                  @endif
                </td>

                <!-- 5. Vendors -->
                <td>
                  <div style="max-width: 260px; word-break: break-word; line-height: 1.45;">
                    @forelse ($rfq->rfqVendors as $rv)
                      @if ($rv->vendor)
                        <a href="{{ route('empanelment.show', $rv->vendor) }}" class="vendor-link" title="View Vendor: {{ $rv->vendor->company_name }}">{{ $rv->vendor->company_name }}</a>{{ !$loop->last ? ', ' : '' }}
                      @endif
                    @empty
                      <span class="text-muted small">—</span>
                    @endforelse
                  </div>
                </td>

                <!-- 6. Quotations Received -->
                <td class="text-center px-1 text-nowrap">
                  @if ($isQuotesReceived)
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px; display: inline-flex; align-items: center; white-space: nowrap;">
                      <i class="bi bi-check2-circle me-1"></i>Received
                    </span>
                  @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size: 11px; display: inline-flex; align-items: center; white-space: nowrap;">
                      <i class="bi bi-clock-history me-1"></i>Awaiting
                    </span>
                  @endif
                </td>

                <!-- 7. Issue Date -->
                <td class="text-center px-1 text-nowrap">
                  <span class="text-dark fw-semibold font-monospace" style="font-size: 11px; white-space: nowrap;">
                    {{ optional($rfq->requisition_date ?? $rfq->created_at)->format('d M Y') ?? '—' }}
                  </span>
                </td>

                <!-- 8. Action -->
                <td class="text-end pe-3 text-nowrap">
                  <div class="dropdown d-inline-block position-relative">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-none dropdown-toggle position-relative"
                            data-bs-toggle="dropdown"
                            data-bs-strategy="fixed"
                            aria-expanded="false"
                            title="{{ $canGeneratePo ? 'Action required: Ready to Generate PO for ' . $awardedVendor->company_name : 'Actions' }}"
                            style="font-size: 11px; height: 26px; line-height: 1; min-width: 78px;">
                      <i class="bi bi-gear" style="font-size: 11px;"></i>
                      <span>Action</span>
                      @if ($canGeneratePo)
                        <span class="rfq-action-dot"></span>
                      @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rfq-action-menu shadow-sm">
                      <li>
                        <a class="dropdown-item" href="{{ route('rfq.show', $rfq) }}">
                          <i class="bi bi-eye text-primary"></i>
                          <span>View RFQ</span>
                        </a>
                      </li>
                      @if ($pr)
                        <li>
                          <a class="dropdown-item" href="{{ route('requisition.show', $pr) }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i>
                            <span>View PR</span>
                          </a>
                        </li>
                      @endif
                      @if ($canGeneratePo)
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                          <a class="dropdown-item text-primary fw-semibold" href="{{ route('po.create', array_filter(['pr_id' => $rfq->purchase_requisition_id, 'vendor_id' => $awardedVendor->id])) }}">
                            <i class="bi bi-receipt-cutoff text-primary"></i>
                            <span>Generate PO</span>
                          </a>
                        </li>
                      @elseif ($hasExistingPo)
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                          <a class="dropdown-item text-success fw-semibold" href="{{ route('po.show', $existingPo) }}" title="{{ $existingPo->po_number }}">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>View PO</span>
                          </a>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No RFQs raised yet.</span>
                </td>
              </tr>
            @endforelse

            <!-- Filter Empty State (Shown when filters match 0 RFQs) -->
            <tr id="rfqNoFilterMatchRow" style="display: none;">
              <td colspan="8" class="text-center py-5 text-muted bg-white">
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

  if (pagStart) pagStart.textContent = total === 0 ? 0 : startIndex + 1;
  if (pagEnd) pagEnd.textContent = Math.min(endIndex, total);
  if (pagTotal) pagTotal.textContent = total;

  if (countEl) {
    countEl.innerText = `${total} RFQs`;
  }

  // Toggle Empty Filter Match State
  const noMatchRow = document.getElementById('rfqNoFilterMatchRow');
  if (noMatchRow) {
    noMatchRow.style.display = (total === 0) ? '' : 'none';
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

window.applyRfqFilters = function() {
  const query = (document.getElementById('rfqSearchInput')?.value || '').toLowerCase().trim();
  const sectorFilter = (document.getElementById('rfqSectorFilter')?.value || 'all').toLowerCase().trim();
  const statusFilter = document.getElementById('rfqStatusFilter')?.value || 'all';
  const rows = Array.from(document.querySelectorAll('.rfq-row'));
  matchedRows = [];

  rows.forEach(row => {
    const sector = (row.getAttribute('data-sector') || '').toLowerCase().trim();
    const dept = (row.getAttribute('data-dept') || '').toLowerCase().trim();
    const status = row.getAttribute('data-status') || '';
    const text = row.innerText.toLowerCase();

    const matchesSector = (sectorFilter === 'all') || (sector === sectorFilter) || (dept === sectorFilter);
    const matchesStatus = (statusFilter === 'all') || (status === statusFilter);
    const matchesQuery = !query || text.includes(query);

    if (matchesSector && matchesStatus && matchesQuery) {
      matchedRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const clearBtn = document.getElementById('clearRfqFilterBtn');
  if (clearBtn) {
    if (query || sectorFilter !== 'all' || statusFilter !== 'all') {
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
  const statusFilter = document.getElementById('rfqStatusFilter');
  if (searchInput) searchInput.value = '';
  if (sectorFilter) sectorFilter.value = 'all';
  if (statusFilter) statusFilter.value = 'all';
  window.applyRfqFilters();
};

// Initial run
window.applyRfqFilters();
})();
@endsection
