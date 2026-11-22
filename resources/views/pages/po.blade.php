@extends('layouts.app')

@section('title', 'Purchase Orders')

@php
  $poTotalCount = $pos->count();
  $poPendingCount = $pos->filter(fn($p) => !in_array($p->status, ['approved', 'rejected', 'on_hold', 'cancelled']))->count();
  $poApprovedCount = $pos->where('status', 'approved')->count();
  $poOnHoldCount = $pos->where('status', 'on_hold')->count();
  $poCancelledCount = $pos->where('status', 'cancelled')->count();
  $poRejectedCount = $pos->where('status', 'rejected')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Purchase Orders (PO)</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      @if ($canCreate)
        <a href="{{ route('po.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
          <i class="bi bi-plus-circle"></i>
          <span>New Purchase Order</span>
        </a>
      @endif
    </div>
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

    /* Responsive & clean tightly-fitted table matching RFQ & PR pages */
    .po-table-container {
      background-color: #ffffff;
      overflow-x: auto;
      border-radius: 0 0 10px 10px;
    }
    #poTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
      table-layout: auto;
    }
    #poTable thead th {
      font-size: 12.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 12px 14px !important;
      line-height: 1.35 !important;
      vertical-align: middle !important;
    }
    #poTable tbody td {
      padding: 13px 14px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      background-color: #ffffff;
      line-height: 1.35 !important;
      white-space: normal;
      transition: background-color 0.15s ease;
    }
    #poTable tbody tr:hover td {
      background-color: #f8faff;
    }
    #poTable tbody tr:last-child td {
      border-bottom: none !important;
    }
    #poTable th:first-child,
    #poTable td:first-child {
      padding-left: 16px !important;
    }
    #poTable th:last-child,
    #poTable td:last-child {
      padding-right: 16px !important;
    }

    .po-num-code {
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
    .po-num-code:hover {
      color: #c8102e !important;
      text-decoration: underline !important;
    }

    .po-dept-badge {
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

    .status-badge-custom {
      font-size: 11.5px !important;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      white-space: nowrap;
    }

    /* Polished, Unified Action Controls matching RFQ */
    .po-action-cell {
      padding: 6px 10px !important;
      white-space: nowrap !important;
      text-align: center;
      width: 85px;
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
    .po-action-menu {
      min-width: 130px;
      max-width: 160px;
      padding: 2.5px;
      border-radius: 5px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.12);
      font-size: 11px;
      z-index: 1060;
    }
    .po-action-menu .dropdown-item {
      padding: 4px 7px;
      border-radius: 3.5px;
      font-size: 11px !important;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background-color 0.15s ease;
    }
    .po-action-menu .dropdown-item i {
      font-size: 11px !important;
    }
    .po-action-menu .dropdown-item:hover {
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
  <div class="erp-switch-bar" id="poSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectPoTab('all')">
      All Orders <span class="erp-switch-count">{{ $poTotalCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending" onclick="selectPoTab('pending')">
      Pending Signoff <span class="erp-switch-count">{{ $poPendingCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectPoTab('approved')">
      Approved / Issued <span class="erp-switch-count">{{ $poApprovedCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="on_hold" onclick="selectPoTab('on_hold')">
      On Hold <span class="erp-switch-count">{{ $poOnHoldCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="cancelled" onclick="selectPoTab('cancelled')">
      Cancelled <span class="erp-switch-count">{{ $poCancelledCount }}</span>
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
          <input type="text" id="poSearchInput" class="erp-search-input" placeholder="Search PO number, PR, department, vendor..." oninput="applyPoFilters()">
        </div>

        <!-- Department Dropdown -->
        <select id="poDeptFilter" class="erp-select" onchange="applyPoFilters()">
          <option value="all">All Departments</option>
          @foreach ($pos->pluck('purchaseRequisition.department')->filter()->unique()->sort() as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
          @endforeach
        </select>

        <!-- Status Filter (Hidden from UI, handled by switch bar tabs) -->
        <select id="poStatusFilter" class="d-none" aria-hidden="true" tabindex="-1">
          <option value="all">All Statuses</option>
          <option value="approved">Approved / Issued</option>
          <option value="pending">Pending Signoff</option>
          <option value="on_hold">On Hold</option>
          <option value="rejected">Rejected</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <!-- Reset Button -->
        <button type="button" class="erp-btn-reset d-none" id="clearPoFilterBtn" onclick="resetPoFilters()" title="Reset Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
          <span>Reset</span>
        </button>
      </div>
    </div>

    <div class="po-table-container">
      <table class="erp-table table-hover align-middle mb-0" id="poTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">PO NUMBER</th>
            <th style="width: 35%;">VENDOR</th>
            <th style="width: 15%;">AMOUNT</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($pos as $po)
          @php
            $pr = $po->purchaseRequisition;
            $dept = $pr?->department ?? 'General';
            $poGroup = ($po->status === 'approved') ? 'approved' : (($po->status === 'rejected') ? 'rejected' : (($po->status === 'on_hold') ? 'on_hold' : 'pending'));
            
            if ($po->status === 'approved') {
              $statusBadgeClass = 'badge-soft-success';
              $statusLabel = 'PO Issued';
            } elseif ($po->status === 'rejected') {
              $statusBadgeClass = 'badge-soft-danger';
              $statusLabel = 'Rejected';
            } elseif ($po->status === 'on_hold') {
              $statusBadgeClass = 'badge-soft-warning';
              $statusLabel = 'On Hold';
            } else {
              $statusBadgeClass = 'badge-soft-warning';
              $statusLabel = 'Pending';
            }
          @endphp
          @php
            $poProjectLabel = $pr?->projectBudget?->project_name ?: ($po->event_name ?: null);
          @endphp
          <tr class="po-row" data-status="{{ $poGroup }}" data-dept="{{ $dept }}" data-search="{{ strtolower($po->po_number . ' ' . ($pr?->pr_number ?? '') . ' ' . $dept . ' ' . ($poProjectLabel ?? '') . ' ' . $po->vendor->company_name) }}">
            <!-- 1. PO No -->
            <td class="ps-3" title="{{ $po->po_number }}">
              <a href="{{ route('po.show', $po) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;">
                {{ $po->po_number }}
              </a>
            </td>

            <!-- 2. Contracted Vendor -->
            <td title="{{ $po->vendor->company_name }}">
              <span class="fw-semibold text-main" style="font-size: 13.5px;">
                {{ $po->vendor->company_name }}
              </span>
              @if ($poProjectLabel)
                <div class="mt-0.5">
                  <span class="badge bg-light text-primary border" style="font-size: 11px; font-weight: 500;">
                    <i class="bi bi-folder2-open me-1"></i>{{ $poProjectLabel }}
                  </span>
                </div>
              @endif
            </td>

            <!-- 3. Amount -->
            <td class="text-nowrap">
              <span class="fw-bold font-monospace text-dark" style="font-size: 13.5px;">
                ₹{{ number_format($po->amount_incl_gst, 2) }}
              </span>
            </td>

            <!-- 4. Status -->
            <td>
              <span class="badge {{ $statusBadgeClass }} status-badge-pipeline">
                {{ $statusLabel }}
              </span>
            </td>

            <!-- 5. Action -->
            <td class="text-end pe-3 po-action-cell">
              @php
                $canRegeneratePo = $po->canRegenerate() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
                $canCancelPo = in_array(auth()->user()?->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $po->status !== 'cancelled' && !$po->grns()->exists();
              @endphp
              <div class="erp-row-actions">
                <a href="{{ route('po.show', $po) }}" class="erp-btn-action-view">
                  <i class="bi bi-eye"></i> View
                </a>
                <div class="dropdown">
                  <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('po.preview-pdf', $po) }}" target="_blank">
                        <i class="bi bi-file-earmark-pdf text-danger"></i> View PDF
                      </a>
                    </li>
                    @if ($canRegeneratePo)
                      <li>
                        <form method="POST" action="{{ route('po.regenerate', $po) }}" class="m-0">
                          @csrf
                          <button type="submit" class="dropdown-item text-success">
                            <i class="bi bi-arrow-repeat text-success"></i> Regenerate PO
                          </button>
                        </form>
                      </li>
                    @endif
                    @if ($canCancelPo)
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="POST" action="{{ route('po.cancel', $po) }}" onsubmit="return confirm('Cancel PO {{ $po->po_number }}?');" class="m-0">
                          @csrf
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-x-circle text-danger"></i> Cancel PO
                          </button>
                        </form>
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
              <span>No Purchase Orders created yet.</span>
            </td>
          </tr>
          @endforelse
          <tr id="emptyPoFilterRow" class="d-none">
            <td colspan="5" class="text-center py-4 text-muted">
              <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary"></i>
              <span>No purchase orders match your filter criteria.</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div id="poPaginationWrapper" class="table-pagination-footer d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2" style="display: none !important;">
      <div class="text-muted small" style="font-size: 12.5px;">
        Showing <span id="poPagStart">0</span> to <span id="poPagEnd">0</span> of <span id="poPagTotal">{{ $pos->count() }}</span> Orders
      </div>
      <div class="d-flex align-items-center gap-1.5">
        <button type="button" class="pagination-btn" id="poPrevPageBtn" onclick="changePoPage(-1)">
          <i class="bi bi-chevron-left"></i> Previous
        </button>
        <div id="poPageNumbers" class="d-flex align-items-center gap-1"></div>
        <button type="button" class="pagination-btn" id="poNextPageBtn" onclick="changePoPage(1)">
          Next <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>

  </div>

  <!-- Approve & Decline Modals for POs with Pending Actions -->
  @foreach ($pos as $po)
    @php $canAct = $po->currentApprovalFor(auth()->user()); @endphp
    @if ($canAct)
      <!-- Quick Approve Modal for PO {{ $po->id }} -->
      <div class="modal fade" id="poApproveModal-{{ $po->id }}" tabindex="-1" aria-labelledby="poApproveLabel-{{ $po->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <form method="POST" action="{{ route('po.approve', $po) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-success-subtle py-2.5 px-3.5 border-bottom border-success-subtle">
              <div class="d-flex align-items-center gap-2 text-success">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <h6 class="modal-title fw-bold fs-6 mb-0" id="poApproveLabel-{{ $po->id }}">Approve Purchase Order</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <p class="text-secondary small mb-3">
                You are approving and endorsing Purchase Order <strong>{{ $po->po_number }}</strong> for <strong>{{ $po->vendor->company_name }}</strong> totaling <strong>₹{{ number_format($po->amount_incl_gst, 2) }}</strong> as <strong>{{ \App\Support\Roles::label(auth()->user()->role) }}</strong>.
              </p>
              <div class="mb-2">
                <label class="form-label small fw-semibold text-dark">Approval Comments <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text" name="comments" class="form-control form-control-sm" placeholder="e.g. Terms verified and approved for issuance">
              </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-check2-circle"></i>
                <span>Confirm Approval</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Decline Modal for PO {{ $po->id }} -->
      <div class="modal fade" id="poRejectModal-{{ $po->id }}" tabindex="-1" aria-labelledby="poRejectLabel-{{ $po->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <form method="POST" action="{{ route('po.reject', $po) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
              <div class="d-flex align-items-center gap-2 text-danger">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <h6 class="modal-title fw-bold fs-6 mb-0" id="poRejectLabel-{{ $po->id }}">Decline Purchase Order</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <p class="text-secondary small mb-3">
                Decline <strong>{{ $po->po_number }}</strong> ({{ $po->vendor->company_name }}). This order will be marked as rejected and returned to procurement.
              </p>
              <div class="mb-2">
                <label class="form-label small fw-semibold text-dark">Reason for Rejection <span class="text-danger">*</span></label>
                <textarea name="comments" class="form-control form-control-sm" rows="3" placeholder="State specific reason for declining this PO..." required></textarea>
              </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-x-circle"></i>
                <span>Confirm Decline</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    @endif
  @endforeach

</div>

<script>
let currentPoPage = 1;
const PO_PAGE_SIZE = 10;
let matchedPoRows = [];
let currentPoStatus = 'all';

function selectPoTab(status) {
  currentPoStatus = status;
  applyPoFilters();
}

function setPoFilter(status) {
  selectPoTab(status);
}

function applyPoFilters() {
  const searchTerm = (document.getElementById('poSearchInput')?.value || '').trim().toLowerCase();
  const selectedDept = document.getElementById('poDeptFilter')?.value || 'all';
  const selectedStatus = currentPoStatus;

  // Sync Enseur Switch Bar tabs
  document.querySelectorAll('#poSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === selectedStatus);
  });

  const rows = Array.from(document.querySelectorAll('.po-row'));
  matchedPoRows = [];

  rows.forEach(row => {
    const rowStatus = row.dataset.status;
    const rowDept = row.dataset.dept;
    const rowSearch = row.dataset.search || '';

    const matchesStatus = (selectedStatus === 'all' || rowStatus === selectedStatus);
    const matchesDept = (selectedDept === 'all' || rowDept === selectedDept);
    const matchesSearch = (!searchTerm || rowSearch.includes(searchTerm));

    if (matchesStatus && matchesDept && matchesSearch) {
      matchedPoRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const counterEl = document.getElementById('poCounterCount');
  if (counterEl) {
    counterEl.textContent = matchedPoRows.length;
  }

  const isFiltered = (selectedStatus !== 'all' || selectedDept !== 'all' || searchTerm !== '');
  const badge = document.getElementById('poRecordCountBadge');
  if (badge) {
    badge.textContent = isFiltered ? `${matchedPoRows.length} of {{ $pos->count() }} Orders` : `{{ $pos->count() }} Orders`;
  }

  const clearBtn = document.getElementById('clearPoFilterBtn');
  if (clearBtn) {
    if (isFiltered) {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  currentPoPage = 1;
  renderPoPagination();
}

function renderPoPagination() {
  const total = matchedPoRows.length;
  const totalPages = Math.ceil(total / PO_PAGE_SIZE) || 1;

  if (currentPoPage < 1) currentPoPage = 1;
  if (currentPoPage > totalPages) currentPoPage = totalPages;

  const startIdx = (currentPoPage - 1) * PO_PAGE_SIZE;
  const endIdx = startIdx + PO_PAGE_SIZE;

  // Toggle display of rows
  matchedPoRows.forEach((row, idx) => {
    if (idx >= startIdx && idx < endIdx) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Empty state handling
  const emptyRow = document.getElementById('emptyPoFilterRow');
  if (emptyRow) {
    if (total === 0 && document.querySelectorAll('.po-row').length > 0) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  // Update pagination UI elements: only display when total items exceed PAGE_SIZE (10)
  const paginationWrapper = document.getElementById('poPaginationWrapper');
  if (paginationWrapper) {
    if (total > PO_PAGE_SIZE) {
      paginationWrapper.style.setProperty('display', 'flex', 'important');
    } else {
      paginationWrapper.style.setProperty('display', 'none', 'important');
    }
  }

  const pagStart = document.getElementById('poPagStart');
  const pagEnd = document.getElementById('poPagEnd');
  const pagTotal = document.getElementById('poPagTotal');
  if (pagStart && pagEnd && pagTotal) {
    pagStart.textContent = total === 0 ? 0 : startIdx + 1;
    pagEnd.textContent = Math.min(endIdx, total);
    pagTotal.textContent = total;
  }

  const prevBtn = document.getElementById('poPrevPageBtn');
  const nextBtn = document.getElementById('poNextPageBtn');
  if (prevBtn) prevBtn.disabled = (currentPoPage <= 1);
  if (nextBtn) nextBtn.disabled = (currentPoPage >= totalPages);

  // Render page pills (only if multiple pages)
  const pillsContainer = document.getElementById('poPageNumbers');
  if (pillsContainer) {
    pillsContainer.innerHTML = '';
    if (totalPages > 1) {
      for (let p = 1; p <= totalPages; p++) {
        const pill = document.createElement('button');
        pill.type = 'button';
        pill.className = `pagination-page-pill ${p === currentPoPage ? 'active' : ''}`;
        pill.textContent = p;
        pill.onclick = () => {
          currentPoPage = p;
          renderPoPagination();
        };
        pillsContainer.appendChild(pill);
      }
    }
  }
}

function changePoPage(delta) {
  currentPoPage += delta;
  renderPoPagination();
}

function resetPoFilters() {
  if (document.getElementById('poSearchInput')) document.getElementById('poSearchInput').value = '';
  if (document.getElementById('poDeptFilter')) document.getElementById('poDeptFilter').value = 'all';
  currentPoStatus = 'all';
  applyPoFilters();
}

document.addEventListener('DOMContentLoaded', function() {
  applyPoFilters();
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'Purchase Orders' });
@endsection
