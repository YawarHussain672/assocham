@extends('layouts.app')

@section('title', 'Goods Receipt (GRN)')

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending HOD', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
    'approved'    => ['label' => 'Verified Closed', 'badge' => 'bg-success-subtle text-success border border-success-subtle'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle'],
  ];
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
        <a href="{{ route('grn.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm">
          <i class="bi bi-box-seam"></i>
          <span>Log Goods Receipt</span>
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

  <!-- Status Metric Cards (Total, Verified, Pending) -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-primary h-100 p-3 rounded-3" onclick="setGrnFilter('all')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total Receipts</span>
          <div class="stat-icon-box icon-primary" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-box-seam-fill"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $grns->count() }}</div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-success h-100 p-3 rounded-3" onclick="setGrnFilter('approved')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Verified Closed</span>
          <div class="stat-icon-box icon-success" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-check2-circle"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $grns->where('status', 'approved')->count() }}</div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-warning h-100 p-3 rounded-3" onclick="setGrnFilter('pending_hod')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending HOD Signoff</span>
          <div class="stat-icon-box icon-warning" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $grns->where('status', 'pending_hod')->count() }}</div>
      </div>
    </div>
  </div>

  <style>
    .grn-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    #grnTable {
      width: 100% !important;
      table-layout: auto !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #grnTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #475569;
      vertical-align: middle;
      padding: 10px 10px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #grnTable td {
      vertical-align: middle;
      padding: 10px 10px;
      font-size: 12px;
      line-height: 1.4;
      white-space: normal;
      border-bottom: 1px solid #f1f5f9;
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
      padding: 0 6px;
      font-size: 12px;
      font-weight: 600;
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
    .pagination-page-pill:hover {
      background: #f1f5f9;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .pagination-page-pill.active {
      background: #0d6efd;
      border-color: #0d6efd;
      color: #ffffff;
    }
  </style>

  <!-- Filter & Search Bar (With Dropdown Filter matching other pages) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2.5 px-3">
      <div class="row g-2 align-items-center">
        <!-- Live Search -->
        <div class="col-12 col-md-6 col-lg-7">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="grnSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="Search GRN, PO No, Vendor, Challan / Invoice..." oninput="applyGrnFilters()">
          </div>
        </div>

        <!-- Status Dropdown (User requested dropdown) -->
        <div class="col-8 col-md-4 col-lg-3">
          <select id="grnStatusFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyGrnFilters()">
            <option value="all">All Statuses</option>
            <option value="approved">Verified Closed</option>
            <option value="pending_hod">Pending HOD Signoff</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Reset Button -->
        <div class="col-4 col-md-2 col-lg-2 text-end">
          <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none px-3 ms-auto" id="clearGrnFilterBtn" onclick="resetGrnFilters()">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- GRN Table Card -->
  <div class="card shadow-sm border-0 mb-4 grn-table-card">
    <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-box-seam text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Goods Receipt Registry</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5" id="grnRecordCountBadge" style="font-size: 11px;">
          {{ $grns->count() }} Entries
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="grn-table-container">
        <table class="table table-hover align-middle mb-0" id="grnTable">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th style="min-width: 135px;" class="ps-3 text-nowrap">GRN NO. &amp; INVOICE</th>
              <th style="min-width: 195px;" class="text-nowrap">LINKED PO / PR &amp; DEPT</th>
              <th style="min-width: 170px;" class="text-nowrap">DELIVERING VENDOR</th>
              <th style="min-width: 125px;" class="text-center text-nowrap">RECEIVED QTY</th>
              <th style="min-width: 175px;" class="text-nowrap">QUALITY REMARKS</th>
              <th style="min-width: 105px;" class="text-center text-nowrap">RECEIPT DATE</th>
              <th style="min-width: 115px;" class="text-center text-nowrap">STATUS</th>
              <th style="min-width: 85px;" class="text-center pe-3 text-nowrap">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($grns as $grn)
              @php
                $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod'];
                $po = $grn->purchaseOrder;
                $pr = $grn->purchaseRequisition ?? $po?->purchaseRequisition;
                $dept = $pr?->department ?? 'General';
              @endphp
              <tr class="grn-row" data-status="{{ $grn->status }}" data-search="{{ strtolower($grn->grn_number . ' ' . ($po?->po_number ?? '') . ' ' . ($pr?->pr_number ?? '') . ' ' . ($po?->vendor?->company_name ?? '') . ' ' . ($grn->invoice_number ?? '') . ' ' . ($grn->quality_remarks ?? '') . ' ' . $dept) }}">
                <!-- 1. GRN No & Invoice -->
                <td class="ps-3">
                  <a href="{{ route('grn.show', $grn) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 12.5px;">
                    {{ $grn->grn_number }}
                  </a>
                  <div class="text-muted small text-truncate mt-0.5" style="font-size: 11px; max-width: 145px;" title="{{ $grn->invoice_number ?: 'No invoice number' }}">
                    @if ($grn->invoice_number)
                      Inv: <span class="fw-medium text-dark">{{ $grn->invoice_number }}</span>
                    @else
                      <span class="text-muted">No invoice</span>
                    @endif
                  </div>
                </td>

                <!-- 2. Linked PO / PR & Dept -->
                <td>
                  @if ($po)
                    <div>
                      <a href="{{ route('po.show', $po) }}" class="fw-semibold text-dark text-decoration-none font-monospace" style="font-size: 12px;">
                        {{ $po->po_number }}
                      </a>
                    </div>
                    @if ($pr)
                      <div class="mt-0.5">
                        <a href="{{ route('requisition.show', $pr) }}" class="text-primary text-decoration-none font-monospace" style="font-size: 11px;" title="Originating PR">
                          {{ $pr->pr_number }}
                        </a>
                      </div>
                    @endif
                    <span class="badge bg-light text-secondary border px-1.5 py-0.5 mt-1" style="font-size: 10px;">
                      {{ $dept }}
                    </span>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>

                <!-- 3. Vendor -->
                <td>
                  <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;" title="{{ $po?->vendor?->company_name }}">
                    {{ $po?->vendor?->company_name ?? '—' }}
                  </div>
                  <div class="text-muted small font-monospace mt-0.5" style="font-size: 10.5px;">
                    Code: {{ $po?->vendor?->vendor_code ?? '—' }}
                  </div>
                </td>

                <!-- 4. Received Qty -->
                <td class="text-center text-nowrap">
                  <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 11px;">
                    <strong>{{ $grn->received_qty }}</strong> / {{ $grn->po_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->po_qty) }}
                  </span>
                </td>

                <!-- 5. Quality Remarks -->
                <td>
                  @if ($grn->quality_remarks)
                    <div class="text-dark text-truncate" style="max-width: 190px; font-size: 11.5px;" title="{{ $grn->quality_remarks }}">
                      {{ $grn->quality_remarks }}
                    </div>
                  @else
                    <span class="text-muted" style="font-size: 11.5px;">—</span>
                  @endif
                </td>

                <!-- 6. Receipt Date -->
                <td class="text-center text-muted text-nowrap" style="font-size: 11.5px;">
                  {{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}
                </td>

                <!-- 7. Status -->
                <td class="text-center text-nowrap">
                  <span class="badge {{ $meta['badge'] }} px-2 py-1 text-nowrap" style="font-size: 10.5px;">
                    {{ $meta['label'] }}
                  </span>
                </td>

                <!-- 8. Action -->
                <td class="text-center pe-3 text-nowrap">
                  <div class="d-inline-flex align-items-center gap-1">
                    <a href="{{ route('grn.show', $grn) }}" class="btn btn-sm btn-outline-primary px-2.5 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 500;" title="View GRN Dossier">
                      <i class="bi bi-eye"></i>
                      <span>View</span>
                    </a>
                    @if ($grn->status === 'pending_hod' && $canCreate)
                      <a href="{{ route('grn.edit', $grn) }}" class="btn btn-sm btn-outline-secondary px-2 py-1 text-nowrap d-inline-flex align-items-center" style="font-size: 11.5px;" title="Edit Quantity &amp; Date (Other fields locked)">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                  <span>No Goods Receipt Notes logged yet.</span>
                </td>
              </tr>
            @endforelse
            <tr id="emptyGrnFilterRow" class="d-none">
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary"></i>
                <span>No Goods Receipt Notes match your filter criteria.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div id="grnPaginationWrapper" class="table-pagination-footer d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2" style="display: none !important;">
        <div class="text-secondary small" style="font-size: 12.5px;">
          Showing <span id="grnPagStart" class="fw-semibold text-dark">0</span> to <span id="grnPagEnd" class="fw-semibold text-dark">0</span> of <span id="grnPagTotal" class="fw-semibold text-dark">{{ $grns->count() }}</span> Entries
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

function setGrnFilter(status) {
  const select = document.getElementById('grnStatusFilter');
  if (select) {
    select.value = status;
  }
  applyGrnFilters();
}

function applyGrnFilters() {
  const searchTerm = (document.getElementById('grnSearchInput')?.value || '').trim().toLowerCase();
  const selectedStatus = document.getElementById('grnStatusFilter')?.value || 'all';
  const rows = Array.from(document.querySelectorAll('.grn-row'));
  matchedGrnRows = [];

  rows.forEach(row => {
    const rowStatus = row.dataset.status;
    const rowSearch = row.dataset.search || '';

    const matchesStatus = (selectedStatus === 'all' || rowStatus === selectedStatus);
    const matchesSearch = (!searchTerm || rowSearch.includes(searchTerm));

    if (matchesStatus && matchesSearch) {
      matchedGrnRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const isFiltered = (selectedStatus !== 'all' || searchTerm !== '');
  const badge = document.getElementById('grnRecordCountBadge');
  if (badge) {
    badge.textContent = isFiltered ? `${matchedGrnRows.length} of {{ $grns->count() }} Entries` : `{{ $grns->count() }} Entries`;
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
  if (document.getElementById('grnStatusFilter')) document.getElementById('grnStatusFilter').value = 'all';
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
