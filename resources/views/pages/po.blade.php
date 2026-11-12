@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Purchase Orders (PO)</h1>
    </div>
    @if ($canCreate)
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('po.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm">
        <i class="bi bi-plus-circle"></i>
        <span>New Purchase Order</span>
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

  <!-- Status Metric Cards (Approved, Pending, Rejected) -->
  <div class="row g-3 mb-3">
    <!-- All Orders Card -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary h-100 p-3 rounded-3" onclick="setPoFilter('all')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total Orders</span>
          <div class="stat-icon-box icon-primary" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-receipt"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pos->count() }}</div>
      </div>
    </div>

    <!-- Approved Card -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success h-100 p-3 rounded-3" onclick="setPoFilter('approved')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Issued / Approved</span>
          <div class="stat-icon-box icon-success" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-check2-circle"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pos->where('status', 'approved')->count() }}</div>
      </div>
    </div>

    <!-- Pending Card -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning h-100 p-3 rounded-3" onclick="setPoFilter('pending')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending Signoff</span>
          <div class="stat-icon-box icon-warning" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pos->whereNotIn('status', ['approved', 'rejected'])->count() }}</div>
      </div>
    </div>

    <!-- Rejected Card -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-danger h-100 p-3 rounded-3" onclick="setPoFilter('rejected')" style="cursor: pointer;">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Rejected</span>
          <div class="stat-icon-box icon-danger" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 8px;">
            <i class="bi bi-x-circle"></i>
          </div>
        </div>
        <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pos->where('status', 'rejected')->count() }}</div>
      </div>
    </div>
  </div>

  <style>
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
    .po-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    #poTable {
      width: 100% !important;
      min-width: 1100px;
      table-layout: auto !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #poTable th {
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
    #poTable td {
      vertical-align: middle;
      padding: 10px 10px;
      font-size: 12px;
      line-height: 1.4;
      white-space: normal;
      border-bottom: 1px solid #f1f5f9;
    }
    .po-table-card {
      border-radius: 8px;
    }
    .po-action-menu {
      min-width: 175px;
      padding: 5px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
      font-size: 12px;
      z-index: 1060;
    }
    .po-action-menu .dropdown-item {
      padding: 6px 9px;
      border-radius: 5px;
      font-size: 12px !important;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: background-color 0.15s ease;
    }
    .po-action-menu .dropdown-item i {
      font-size: 12.5px !important;
    }
    .po-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }
    .po-action-dot {
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

  <!-- Filter & Search Bar (With Dropdown Filter matching other pages) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2.5 px-3">
      <div class="row g-2 align-items-center">
        <!-- Live Search -->
        <div class="col-12 col-md-5 col-lg-5">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="poSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="Search PO No, PR, Department, Vendor..." oninput="applyPoFilters()">
          </div>
        </div>

        <!-- Department Dropdown -->
        <div class="col-6 col-md-3 col-lg-3">
          <select id="poDeptFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyPoFilters()">
            <option value="all">All Departments</option>
            @foreach ($pos->pluck('purchaseRequisition.department')->filter()->unique()->sort() as $dept)
              <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <!-- Status Dropdown (User requested dropdown) -->
        <div class="col-6 col-md-3 col-lg-3">
          <select id="poStatusFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyPoFilters()">
            <option value="all">All Statuses</option>
            <option value="approved">Approved / Issued</option>
            <option value="pending">Pending Signoff</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Reset Button -->
        <div class="col-12 col-md-1 col-lg-1 text-end">
          <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none w-100" id="clearPoFilterBtn" onclick="resetPoFilters()" title="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- PO Table Card -->
  <div class="card shadow-sm border-0 mb-4 po-table-card">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-receipt text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-main" style="font-size: 14.5px;">Purchase Orders Registry</h6>
      </div>
      <span class="badge bg-light text-dark border" id="poRecordCountBadge">{{ $pos->count() }} Orders</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive po-table-container">
        <table class="table table-hover align-middle mb-0" id="poTable">
          <thead class="table-light">
            <tr>
              <th style="min-width: 130px;" class="ps-3 text-nowrap">PO NO.</th>
              <th style="min-width: 175px;" class="text-nowrap">LINKED PR &amp; DEPT</th>
              <th style="min-width: 220px;" class="text-nowrap">CONTRACTED VENDOR</th>
              <th style="min-width: 130px;" class="text-center text-nowrap">AMOUNT (INCL. GST)</th>
              <th style="min-width: 110px;" class="text-center text-nowrap">DELIVERY TARGET</th>
              <th style="min-width: 135px;" class="text-center text-nowrap">GRN / FULFILLMENT</th>
              <th style="min-width: 105px;" class="text-center text-nowrap">STATUS</th>
              <th style="min-width: 95px;" class="text-end pe-3 text-nowrap">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($pos as $po)
            @php
              $pr = $po->purchaseRequisition;
              $dept = $pr?->department ?? 'General';
              $poGroup = ($po->status === 'approved') ? 'approved' : (($po->status === 'rejected') ? 'rejected' : 'pending');
              
              if ($po->status === 'approved') {
                $statusBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
                $statusLabel = 'PO Issued';
              } elseif ($po->status === 'rejected') {
                $statusBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                $statusLabel = 'Rejected';
              } else {
                $statusBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                $statusLabel = 'Pending Review';
              }
            @endphp
            <tr class="po-row" data-status="{{ $poGroup }}" data-dept="{{ $dept }}" data-search="{{ strtolower($po->po_number . ' ' . ($pr?->pr_number ?? '') . ' ' . $dept . ' ' . $po->vendor->company_name) }}">
              <!-- 1. PO No -->
              <td class="ps-3 text-nowrap">
                <a href="{{ route('po.show', $po) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                  <code>{{ $po->po_number }}</code>
                </a>
                @if ($po->created_at || $po->revision_number > 0)
                  <div class="text-muted small mt-0.5" style="font-size: 11px;">
                    {{ optional($po->created_at)->format('d M Y') }}
                    @if ($po->revision_number > 0)
                      <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-1 px-1.5 py-0" style="font-size: 10px;">Rev {{ $po->revision_number }}</span>
                    @endif
                  </div>
                @endif
              </td>

              <!-- 2. Linked PR & Dept -->
              <td class="text-nowrap">
                @if ($pr)
                  <div>
                    <a href="{{ route('requisition.show', $pr) }}" class="pr-num-code" title="{{ $pr->pr_number }}">
                      {{ $pr->pr_number }}
                    </a>
                  </div>
                  <span class="badge bg-light text-dark border px-2 py-0.5 mt-1" style="font-size: 10.5px;">
                    <i class="bi bi-diagram-3 text-secondary me-1"></i>{{ $dept }}
                  </span>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>

              <!-- 3. Vendor -->
              <td>
                <div class="fw-semibold text-dark" style="max-width: 240px; word-break: break-word; line-height: 1.45;">
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'empanelment'))
                    <a href="{{ route('empanelment.show', $po->vendor) }}" class="vendor-link" title="{{ $po->vendor->company_name }}">
                      {{ $po->vendor->company_name }}
                    </a>
                  @else
                    {{ $po->vendor->company_name }}
                  @endif
                </div>
                <div class="text-muted small font-monospace mt-0.5" style="font-size: 11px;">
                  Code: {{ $po->vendor->vendor_code }}
                </div>
              </td>

              <!-- 4. Amount -->
              <td class="text-center text-nowrap">
                <div class="fw-bold text-dark font-monospace">
                  ₹{{ number_format($po->amount_incl_gst, 2) }}
                </div>
                @if ($po->isHighValue())
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-0.5" style="font-size: 10px;">
                    High Value (&ge;10L)
                  </span>
                @endif
              </td>

              <!-- 5. Delivery Target -->
              <td class="text-center text-nowrap">
                <span class="text-secondary small">
                  {{ optional($po->delivery_date)->format('d M Y') ?? 'Immediate' }}
                </span>
              </td>

              <!-- 6. GRN / Fulfillment -->
              <td class="text-center text-nowrap">
                @if ($po->status === 'approved')
                  @php
                    $received = $po->receivedQty();
                    $ordered = (int) $po->po_qty;
                    $grnCount = $po->grns->count();
                  @endphp
                  @if ($received >= $ordered && $ordered > 0)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;" title="{{ $grnCount }} GRN(s) logged">
                      <i class="bi bi-check2-circle me-1"></i>Received: {{ $received }} / {{ $ordered }}
                    </span>
                  @elseif ($received > 0)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;" title="{{ $grnCount }} GRN(s) logged">
                      <i class="bi bi-box-seam me-1"></i>Received: {{ $received }} / {{ $ordered }}
                    </span>
                  @else
                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                      Received: 0 / {{ $ordered }}
                    </span>
                  @endif
                @else
                  <span class="text-muted small" style="font-size: 11.5px;">Pending PO Issue</span>
                @endif
              </td>

              <!-- 7. Status -->
              <td class="text-center text-nowrap">
                <span class="badge {{ $statusBadgeClass }} px-2 py-1 text-nowrap" style="font-size: 11px;">
                  {{ $statusLabel }}
                </span>
              </td>

              <!-- 8. Action -->
              <td class="text-end pe-3 text-nowrap">
                @php
                  $canApprove = $po->currentApprovalFor(auth()->user());
                  $canEditPo = in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $po->status !== 'cancelled';
                  $canRegeneratePo = $po->canRegenerate() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
                  $hasActionDot = (bool) $canApprove || $canRegeneratePo;
                @endphp
                <div class="dropdown d-inline-block">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-none dropdown-toggle position-relative"
                          data-bs-toggle="dropdown"
                          data-bs-popper-config='{"strategy":"fixed"}'
                          aria-expanded="false"
                          title="{{ $canApprove ? 'Action required: Signoff pending on this PO' : ($canRegeneratePo ? 'Action required: Re-approved PO ready to regenerate' : 'Actions') }}"
                          style="font-size: 11px; height: 26px; line-height: 1; min-width: 78px;">
                    <i class="bi bi-gear" style="font-size: 11px;"></i>
                    <span>Action</span>
                    @if ($hasActionDot)
                      <span class="po-action-dot"></span>
                    @endif
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end po-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('po.show', $po) }}">
                        <i class="bi bi-eye text-primary"></i>
                        <span>View Details</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('po.pdf', $po) }}">
                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                        <span>Download PDF</span>
                      </a>
                    </li>

                    @if ($canRegeneratePo)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <form method="POST" action="{{ route('po.regenerate', $po) }}" class="m-0">
                          @csrf
                          <button type="submit" class="dropdown-item text-success">
                            <i class="bi bi-arrow-repeat text-success"></i>
                            <span>Regenerate PO</span>
                          </button>
                        </form>
                      </li>
                    @endif

                    @if ($canApprove)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#poApproveModal-{{ $po->id }}">
                          <i class="bi bi-check-circle-fill text-success"></i>
                          <span>Approve &amp; Issue</span>
                        </button>
                      </li>
                      <li>
                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#poRejectModal-{{ $po->id }}">
                          <i class="bi bi-x-circle-fill text-danger"></i>
                          <span>Reject / Decline</span>
                        </button>
                      </li>
                    @endif

                    @if ($canEditPo)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <a class="dropdown-item text-secondary" href="{{ route('po.edit', $po) }}">
                          <i class="bi bi-pencil-square text-secondary"></i>
                          <span>Edit PO</span>
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
                <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                <span>No Purchase Orders created yet.</span>
              </td>
            </tr>
            @endforelse
            <tr id="emptyPoFilterRow" class="d-none">
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary"></i>
                <span>No purchase orders match your filter criteria.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div id="poPaginationWrapper" class="table-pagination-footer d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2" style="display: none !important;">
        <div class="text-secondary small" style="font-size: 12.5px;">
          Showing <span id="poPagStart" class="fw-semibold text-dark">0</span> to <span id="poPagEnd" class="fw-semibold text-dark">0</span> of <span id="poPagTotal" class="fw-semibold text-dark">{{ $pos->count() }}</span> Orders
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

function setPoFilter(status) {
  const select = document.getElementById('poStatusFilter');
  if (select) {
    select.value = status;
  }
  applyPoFilters();
}

function applyPoFilters() {
  const searchTerm = (document.getElementById('poSearchInput')?.value || '').trim().toLowerCase();
  const selectedDept = document.getElementById('poDeptFilter')?.value || 'all';
  const selectedStatus = document.getElementById('poStatusFilter')?.value || 'all';
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
  if (document.getElementById('poStatusFilter')) document.getElementById('poStatusFilter').value = 'all';
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
