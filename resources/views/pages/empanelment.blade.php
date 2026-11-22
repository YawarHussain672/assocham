@extends('layouts.app')

@section('title', 'Vendor Empanelment')

@php
$statusMeta = [
'pending_admin_head' => ['label' => 'Pending Admin', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_l1' => ['label' => 'Pending ASG 1', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_l2' => ['label' => 'Pending ASG 2', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_cfo' => ['label' => 'Pending CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'approved' => ['label' => 'Empanelled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F', 'badge' => 'success'],
'on_hold' => ['label' => 'On Hold', 'bg' => '#FEF3C7', 'fg' => '#B45309', 'badge' => 'warning'],
'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A', 'badge' => 'danger'],
'blacklisted' => ['label' => 'Blacklisted', 'bg' => '#FEE2E2', 'fg' => '#DC2626', 'badge' => 'danger'],
'draft' => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B', 'badge' => 'secondary'],
];

$vendorStageTitles = [
1 => 'Admin Head',
2 => 'ASG 1',
3 => 'ASG 2',
4 => 'CFO',
];

$totalVendors = $vendors->count();
$empanelledCount = $vendors->where('status', 'approved')->count();
$pendingCount = $vendors->whereIn('status', ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo'])->count();
$onHoldCount = $vendors->where('status', 'on_hold')->count();
$rejectedCount = $vendors->where('status', 'rejected')->count();
$blacklistedCount = $vendors->filter(fn($v) => $v->isBlacklisted())->count();
$uniqueCategories = $vendors->pluck('category')->filter(fn($c) => !is_numeric($c) && strlen(trim($c)) > 1)->unique()->sort();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Vendor Empanelment</h1>
    </div>
    @if ($canSubmit)
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('onboarding') }}" class="btn btn-sm btn-primary fw-semibold shadow-sm">
        <i class="bi bi-person-plus me-1"></i> Add Vendor
      </a>
    </div>
    @endif
  </div>

  @if (session('status'))
  <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3">
    <i class="bi bi-check-circle-fill"></i>
    <div>{{ session('status') }}</div>
  </div>
  @endif

  @if (isset($errors) && $errors->any())
  <div class="alert alert-danger mb-3">
    <div class="fw-semibold mb-1">Please correct the following errors:</div>
    <ul class="mb-0 ps-3 small">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <style>
    /* Empanelment Table Layout Enforcement */
    .empanelment-table-container {
      width: 100%;
      overflow-x: auto;
      background: #ffffff;
      border-radius: 0 0 8px 8px;
    }

    #vendorsTable {
      width: 100% !important;
      border-collapse: collapse !important;
      margin-bottom: 0 !important;
      table-layout: fixed !important;
    }

    #vendorsTable thead,
    #vendorsTable tbody {
      width: 100% !important;
    }

    #vendorsTable tr,
    #vendorsTable thead tr,
    #vendorsTable tbody tr {
      grid: none !important;
      gap: 0 !important;
    }

    #vendorsTable tr.d-none,
    #vendorsTable tbody tr.d-none {
      display: none !important;
    }

    #vendorsTable thead tr {
      border-bottom: 1px solid #e2e8f0 !important;
    }

    #vendorsTable thead th {
      display: table-cell !important;
      font-size: 11.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #64748b !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      border-top: none !important;
      padding: 12px 16px !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
    }

    #vendorsTable tbody td {
      display: table-cell !important;
      padding: 12px 16px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      background-color: #ffffff;
      font-size: 13.5px !important;
      line-height: 1.45 !important;
      white-space: normal;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      transition: background-color 0.15s ease;
    }

    #vendorsTable tbody tr:last-child td {
      border-bottom: none !important;
    }

    #vendorsTable tbody tr:hover td {
      background-color: #f8faff !important;
    }

    #vendorsTable thead th.vendor-action-header,
    #vendorsTable tbody td.vendor-action-cell {
      overflow: visible !important;
      text-overflow: clip !important;
      white-space: nowrap !important;
      text-align: right !important;
      padding: 10px 16px 10px 4px !important;
      width: 16% !important;
      min-width: 120px !important;
    }

    .erp-row-actions {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: flex-end !important;
      gap: 6px !important;
      white-space: nowrap !important;
      flex-wrap: nowrap !important;
    }

    .erp-btn-action-view,
    .erp-action-dropdown-btn {
      flex-shrink: 0 !important;
    }

    .vendor-action-menu {
      min-width: 115px;
      max-width: 140px;
      padding: 2.5px;
      border-radius: 5px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.12);
      font-size: 10.5px;
      z-index: 1060;
    }

    .vendor-action-menu .dropdown-item {
      padding: 3.5px 6.5px;
      border-radius: 3.5px;
      font-size: 10.5px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background-color 0.15s ease;
    }

    .vendor-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }

    .vendor-action-menu .dropdown-item.action-approve-item:hover {
      background-color: #ecfdf5;
      color: #059669;
    }

    .vendor-action-menu .dropdown-item.action-reject-item:hover {
      background-color: #fef2f2;
      color: #dc2626;
    }

    /* Client-side Table Pagination */
    .table-pagination-footer {
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 12px 18px;
    }

    #vendorPaginationInfo,
    #vendorPaginationInfo span {
      font-weight: 500 !important;
      color: #64748b !important;
    }

    #vendorPaginationControls {
      display: inline-flex !important;
      align-items: center !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
      white-space: nowrap !important;
      gap: 6px !important;
    }

    #vendorPageNumbers {
      display: inline-flex !important;
      align-items: center !important;
      flex-direction: row !important;
      flex-wrap: nowrap !important;
      white-space: nowrap !important;
      gap: 4px !important;
    }

    .pagination-btn {
      height: 32px !important;
      min-height: 32px !important;
      font-size: 12px !important;
      font-weight: 600 !important;
      padding: 0 12px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 5px !important;
      border-radius: 6px !important;
      border: 1px solid #cbd5e1 !important;
      background: #ffffff !important;
      color: #334155 !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      line-height: 30px !important;
      white-space: nowrap !important;
      text-decoration: none !important;
      flex-shrink: 0 !important;
    }

    .pagination-btn:hover:not(:disabled) {
      background: #f8fafc !important;
      border-color: #94a3b8 !important;
      color: #0f172a !important;
    }

    .pagination-btn:disabled {
      opacity: 0.5 !important;
      cursor: not-allowed !important;
      border-color: #e2e8f0 !important;
      color: #94a3b8 !important;
      background: #f8fafc !important;
    }

    .pagination-page-pill {
      min-width: 32px !important;
      height: 32px !important;
      min-height: 32px !important;
      font-size: 12px !important;
      font-weight: 600 !important;
      padding: 0 8px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      border-radius: 6px !important;
      border: 1px solid #cbd5e1 !important;
      background: #ffffff !important;
      color: #334155 !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      line-height: 30px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
    }

    .pagination-page-pill:hover:not(.active) {
      background: #f8fafc !important;
      border-color: #94a3b8 !important;
      color: #0f172a !important;
    }

    .pagination-page-pill.active {
      background: #c8102e !important;
      color: #ffffff !important;
      border-color: #a50d24 !important;
      box-shadow: 0 1px 3px rgba(244, 145, 4, 0.35) !important;
    }
  </style>

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="vendorSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectVendorTab('all')">
      All Vendors <span class="erp-switch-count">{{ $totalVendors }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending" onclick="selectVendorTab('pending')">
      Pending Review <span class="erp-switch-count">{{ $pendingCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectVendorTab('approved')">
      Empanelled <span class="erp-switch-count">{{ $empanelledCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="on_hold" onclick="selectVendorTab('on_hold')">
      On Hold <span class="erp-switch-count">{{ $onHoldCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="rejected" onclick="selectVendorTab('rejected')">
      Rejected <span class="erp-switch-count">{{ $rejectedCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="blacklisted" onclick="selectVendorTab('blacklisted')">
      Blacklisted <span class="erp-switch-count">{{ $blacklistedCount }}</span>
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
          <input type="text" id="filterVendorsInput" class="erp-search-input" placeholder="Search vendor name, code, category..." oninput="applyVendorFilter()">
        </div>

        <!-- Category Dropdown Filter -->
        <select id="filterCategorySelect" class="erp-select" onchange="applyVendorFilter()">
          <option value="all">All Categories</option>
          @foreach($uniqueCategories as $cat)
            <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
          @endforeach
        </select>

        <!-- Reset Button -->
        <button type="button" class="erp-btn-reset d-none" id="clearVendorFilterBtn" onclick="resetVendorFilters()" title="Reset Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
          <span>Reset</span>
        </button>
      </div>
    </div>

    <div class="empanelment-table-container">
      <table class="table erp-table table-hover align-middle mb-0" id="vendorsTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 15%;">VENDOR CODE</th>
            <th style="width: 33%;">VENDOR NAME</th>
            <th style="width: 22%;">CATEGORY</th>
            <th style="width: 14%;">STATUS</th>
            <th class="text-end vendor-action-header" style="width: 16%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($vendors as $vendor)
          @php
          $meta = $statusMeta[$vendor->status] ?? $statusMeta['draft'];
          $rating = $vendor->average_rating;
          $orderCount = $vendor->total_orders_count;
          $canAct = $vendor->currentApprovalFor(auth()->user());
          $pendingStage = $canAct ? ($vendorStageTitles[$canAct->level] ?? \App\Support\Roles::label($canAct->role_required)) : null;
          $statusGroup = ($vendor->isBlacklisted()) ? 'blacklisted' : (($vendor->status === 'approved') ? 'approved' : (($vendor->status === 'rejected') ? 'rejected' : (($vendor->status === 'on_hold') ? 'on_hold' : 'pending')));
          @endphp
          <tr class="vendor-item-row" data-status-group="{{ $statusGroup }}" data-category="{{ strtolower($vendor->category ?: 'general') }}">
            <!-- 1. Vendor Code -->
            <td class="ps-3">
              <a href="{{ route('empanelment.show', $vendor) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;">
                {{ $vendor->vendor_code }}
              </a>
            </td>

            <!-- 2. Vendor Name -->
            <td>
              <span class="fw-semibold text-main" style="font-size: 13.5px;">
                {{ $vendor->company_name }}
              </span>
            </td>

            <!-- 3. Category -->
            <td>
              <span class="text-secondary" style="font-size: 13.5px;">
                {{ (is_numeric($vendor->category) ? 'General' : $vendor->category) ?: 'General' }}
              </span>
            </td>

            <!-- 4. Status -->
            <td>
              @if ($vendor->isBlacklisted())
              <span class="badge badge-soft-danger status-badge-pipeline">Blacklisted</span>
              @else
              <span class="badge badge-soft-{{ $meta['badge'] ?? 'warning' }} status-badge-pipeline">{{ $meta['label'] }}</span>
              @endif
            </td>

            <!-- 5. Action -->
            <td class="text-end vendor-action-cell">
              <div class="erp-row-actions">
                <a href="{{ route('empanelment.show', $vendor) }}" class="erp-btn-action-view">
                  <i class="bi bi-eye"></i> View
                </a>
                @php
                  $canManageVendor = in_array(auth()->user()?->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $vendor->status !== 'approved' && !$vendor->isBlacklisted();
                  $isApprovedVendor = ($vendor->status === 'approved');
                @endphp
                @if ($canManageVendor || $isApprovedVendor)
                  <div class="dropdown">
                    <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                      @if ($canManageVendor)
                        <li>
                          <a class="dropdown-item" href="{{ route('empanelment.edit', $vendor) }}">
                            <i class="bi bi-pencil text-secondary"></i> {{ $vendor->status === 'rejected' ? 'Correct & Resubmit' : 'Edit Application' }}
                          </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <form id="delVendorForm{{ $vendor->id }}" method="POST" action="{{ route('empanelment.destroy', $vendor) }}" onsubmit="return confirm('Delete vendor {{ $vendor->company_name }}?');" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="bi bi-trash text-danger"></i> Delete Vendor
                            </button>
                          </form>
                        </li>
                      @elseif ($isApprovedVendor)
                        <li>
                          <form method="POST" action="{{ route('empanelment.resend-email', $vendor) }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-secondary">
                              <i class="bi bi-envelope text-secondary"></i> Resend Email
                            </button>
                          </form>
                        </li>
                        <li>
                          <a class="dropdown-item" href="{{ route('empanelment.declaration', $vendor) }}" target="_blank">
                            <i class="bi bi-file-earmark-check text-secondary"></i> Self-Declaration
                          </a>
                        </li>
                      @endif
                    </ul>
                  </div>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
              <span>No vendors submitted yet.</span>
            </td>
          </tr>
          @endforelse

          <!-- No Filter Match Row -->
          <tr id="emptyVendorFilterRow" class="d-none">
            <td colspan="5" class="text-center py-5 text-muted">
              <i class="bi bi-funnel fs-2 d-block mb-2 text-secondary"></i>
              <div class="fw-semibold text-dark mb-1">No matching vendors found</div>
              <p class="small text-muted mb-2">Try adjusting your search terms or clearing status filters.</p>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="resetVendorFilters()">Reset All Filters</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Pagination Footer (10 items per page) -->
    <div class="table-pagination-footer d-flex flex-wrap align-items-center justify-content-between gap-3" id="vendorPaginationWrapper" style="display: none !important;">
      <div class="text-muted small" id="vendorPaginationInfo" style="font-size: 12.5px;">
        Showing <span id="pagStart">1</span> to <span id="pagEnd">{{ min(10, $totalVendors) }}</span> of <span id="pagTotal">{{ $totalVendors }}</span> vendors
      </div>
      <div id="vendorPaginationControls">
        <button type="button" class="pagination-btn" id="prevPageBtn" onclick="changeVendorPage(-1)" disabled>
          <i class="bi bi-chevron-left"></i>
          <span>Previous</span>
        </button>
        <div id="vendorPageNumbers">
          <!-- Page pills dynamically populated via JS -->
        </div>
        <button type="button" class="pagination-btn" id="nextPageBtn" onclick="changeVendorPage(1)">
          <span>Next</span>
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Role-Based Approval & Rejection Modals -->
  @foreach ($vendors as $vendor)
  @php
  $canAct = $vendor->currentApprovalFor(auth()->user());
  @endphp
  @if ($canAct)
  @php
  $stageTitle = $vendorStageTitles[$canAct->level] ?? \App\Support\Roles::label($canAct->role_required);
  @endphp
  <!-- Approve Modal for Vendor {{ $vendor->id }} -->
  <div class="modal fade" id="approveModal-{{ $vendor->id }}" tabindex="-1" aria-labelledby="approveModalLabel-{{ $vendor->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="modal-header bg-success-subtle py-2.5 px-3 border-bottom">
          <h6 class="modal-title fw-bold text-success d-flex align-items-center gap-2 mb-0" id="approveModalLabel-{{ $vendor->id }}" style="font-size: 14px;">
            <i class="bi bi-patch-check-fill text-success fs-5"></i>
            <span>Approve Vendor Empanelment</span>
          </h6>
          <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('empanelment.approve', $vendor) }}">
          @csrf
          <div class="modal-body p-3.5">
            <div class="bg-light p-3 rounded border mb-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h6 class="fw-bold text-dark mb-0.5" style="font-size: 14px;">{{ $vendor->company_name }}</h6>
                  <span class="font-monospace text-muted small">{{ $vendor->vendor_code }}</span>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                  Stage: {{ $stageTitle }}
                </span>
              </div>
              <div class="row g-2 text-secondary" style="font-size: 11.5px;">
                <div class="col-6">
                  <span class="text-muted">Category:</span> <strong class="text-dark">{{ $vendor->category ?: 'General' }}</strong>
                </div>
                <div class="col-6">
                  <span class="text-muted">Location:</span> <strong class="text-dark">{{ $vendor->location ?: ($vendor->address ?: '—') }}</strong>
                </div>
                <div class="col-6">
                  <span class="text-muted">PAN:</span> <span class="font-monospace text-dark">{{ $vendor->pan ?: '—' }}</span>
                </div>
                <div class="col-6">
                  <span class="text-muted">GSTIN:</span> <span class="font-monospace text-dark">{{ $vendor->gstin ?: '—' }}</span>
                </div>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Approval Remarks (Optional)</label>
              <input type="text" name="comments" class="form-control form-control-sm" placeholder="e.g. Cleared compliance checks, approved for {{ $stageTitle }}">
            </div>
            <div class="form-text text-muted" style="font-size: 11px;">
              This will sign off on <strong>{{ $stageTitle }}</strong> and advance the vendor to the next governance stage.
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-check-circle-fill"></i>
              <span>Approve &amp; Advance</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Reject Modal for Vendor {{ $vendor->id }} -->
  <div class="modal fade" id="rejectModal-{{ $vendor->id }}" tabindex="-1" aria-labelledby="rejectModalLabel-{{ $vendor->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="modal-header bg-danger-subtle py-2.5 px-3 border-bottom">
          <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 mb-0" id="rejectModalLabel-{{ $vendor->id }}" style="font-size: 14px;">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <span>Decline Vendor Empanelment</span>
          </h6>
          <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('empanelment.reject', $vendor) }}">
          @csrf
          <div class="modal-body p-3.5">
            <div class="alert alert-danger-subtle text-danger border border-danger-subtle d-flex align-items-center gap-2 py-2 px-2.5 mb-3 rounded" style="font-size: 12px;">
              <i class="bi bi-exclamation-circle-fill fs-6 flex-shrink-0"></i>
              <div>Declining an empanelment application is <strong>terminal</strong>. The vendor will be marked as Rejected.</div>
            </div>

            <div class="mb-3">
              <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $vendor->company_name }}</div>
              <div class="text-muted small font-monospace">{{ $vendor->vendor_code }} &bull; Stage: {{ $stageTitle }}</div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-danger mb-1">Reason for Rejection (Required) *</label>
              <textarea name="comments" required rows="2" class="form-control form-control-sm" placeholder="Provide reason for rejection (e.g. Statutory documentation incomplete)..."></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-x-circle-fill"></i>
              <span>Confirm Rejection</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif

  @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && !$vendor->isBlacklisted())
  <!-- Blacklist Modal for Vendor {{ $vendor->id }} -->
  <div class="modal fade" id="blacklistModal-{{ $vendor->id }}" tabindex="-1" aria-labelledby="blacklistModalLabel-{{ $vendor->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="modal-header bg-dark text-white py-2.5 px-3 border-bottom">
          <h6 class="modal-title fw-bold text-white d-flex align-items-center gap-2 mb-0" id="blacklistModalLabel-{{ $vendor->id }}" style="font-size: 14px;">
            <i class="bi bi-slash-circle-fill text-danger fs-5"></i>
            <span>Blacklist Vendor — Permanent Action</span>
          </h6>
          <button type="button" class="btn-close btn-close-white btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('empanelment.blacklist', $vendor) }}">
          @csrf
          <div class="modal-body p-3.5">
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-2.5 mb-3 rounded" style="font-size: 12px;">
              <i class="bi bi-shield-lock-fill fs-5 flex-shrink-0"></i>
              <div>
                <strong>Warning:</strong> Blacklisting permanently locks this vendor. Editing, deleting, bidding, and purchase order issuance are permanently revoked.
              </div>
            </div>

            <div class="mb-3 bg-light p-2.5 rounded border">
              <div class="fw-bold text-dark" style="font-size: 13.5px;">{{ $vendor->company_name }}</div>
              <div class="text-muted small font-monospace">{{ $vendor->vendor_code }} &bull; PAN: {{ $vendor->pan ?: 'N/A' }}</div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-danger mb-1">Mandatory Reason for Blacklisting <span class="text-danger">*</span></label>
              <textarea name="reason" required minlength="5" rows="3" class="form-control form-control-sm" placeholder="Provide clear audit justification for blacklisting (e.g. Fraudulent GST certificate, repeated non-delivery, statutory violation)..."></textarea>
              <div class="form-text text-muted" style="font-size: 11px;">This justification is permanently stored in the compliance audit trail.</div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-slash-circle-fill"></i>
              <span>Confirm Blacklist</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif

  @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $vendor->isBlacklisted())
  <!-- Unblacklist Modal for Vendor {{ $vendor->id }} -->
  <div class="modal fade" id="unblacklistModal-{{ $vendor->id }}" tabindex="-1" aria-labelledby="unblacklistModalLabel-{{ $vendor->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="modal-header bg-success-subtle py-2.5 px-3 border-bottom border-success-subtle">
          <h6 class="modal-title fw-bold text-success d-flex align-items-center gap-2 mb-0" id="unblacklistModalLabel-{{ $vendor->id }}" style="font-size: 14px;">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <span>Reinstate / Unblacklist Vendor</span>
          </h6>
          <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('empanelment.unblacklist', $vendor) }}">
          @csrf
          <div class="modal-body p-3.5">
            <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-2.5 mb-3 rounded" style="font-size: 12px;">
              <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
              <div>
                Reinstating will set this vendor back to active compliance, allowing them to be evaluated, edited, or awarded contracts.
              </div>
            </div>

            <div class="mb-3 bg-light p-2.5 rounded border">
              <div class="fw-bold text-dark" style="font-size: 13.5px;">{{ $vendor->company_name }}</div>
              <div class="text-muted small font-monospace">{{ $vendor->vendor_code }} &bull; PAN: {{ $vendor->pan ?: 'N/A' }}</div>
              @if ($vendor->blacklist_reason)
              <div class="small text-danger mt-1"><strong>Previous Blacklist Reason:</strong> {{ $vendor->blacklist_reason }}</div>
              @endif
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-success mb-1">Audit Justification for Reinstatement <span class="text-danger">*</span></label>
              <textarea name="reason" required minlength="5" rows="3" class="form-control form-control-sm" placeholder="Provide reason for lifting the blacklist (e.g. Compliance documents verified and cleared, legal clearance received)..."></textarea>
              <div class="form-text text-muted" style="font-size: 11px;">This reinstatement rationale will be recorded in the audit trail.</div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-shield-check"></i>
              <span>Reinstate Vendor</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
  @endforeach

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Vendors', title: 'Empanelment' });

(function() {
const input = document.getElementById('filterVendorsInput');
const categorySelect = document.getElementById('filterCategorySelect');
const countEl = document.getElementById('vendorFilterCount');
const clearBtn = document.getElementById('clearVendorFilterBtn');
const emptyRow = document.getElementById('emptyVendorFilterRow');

const PAGE_SIZE = 10;
let currentPage = 1;
let matchedRows = [];
let currentVendorStatus = 'all';

function renderPagination() {
const total = matchedRows.length;
const totalPages = Math.ceil(total / PAGE_SIZE) || 1;

if (currentPage > totalPages) currentPage = totalPages;
if (currentPage < 1) currentPage=1;

  const startIndex=(currentPage - 1) * PAGE_SIZE;
  const endIndex=startIndex + PAGE_SIZE;

  // Show only current page items among matched rows
  matchedRows.forEach((row, idx)=> {
  if (idx >= startIndex && idx < endIndex) {
    row.style.display='' ;
    } else {
    row.style.display='none' ;
    }
    });

    // Update counts
    const pagStart=document.getElementById('pagStart');
    const pagEnd=document.getElementById('pagEnd');
    const pagTotal=document.getElementById('pagTotal');
    const paginationWrapper=document.getElementById('vendorPaginationWrapper');

    if (pagStart) pagStart.textContent=total===0 ? 0 : startIndex + 1;
    if (pagEnd) pagEnd.textContent=Math.min(endIndex, total);
    if (pagTotal) pagTotal.textContent=total;

    if (countEl) {
    countEl.innerText=`Showing ${Math.min(endIndex, total)} of ${total} vendors`;
    }

    const counterEl = document.getElementById('vendorCounterCount');
    if (counterEl) {
      counterEl.textContent = total;
    }

    // Update Buttons
    const prevBtn=document.getElementById('prevPageBtn');
    const nextBtn=document.getElementById('nextPageBtn');
    if (prevBtn) prevBtn.disabled=(currentPage <=1);
    if (nextBtn) nextBtn.disabled=(currentPage>= totalPages);

    // Render Page Number Pills
    const pageNumbersContainer = document.getElementById('vendorPageNumbers');
    if (pageNumbersContainer) {
    pageNumbersContainer.innerHTML = '';

    // Only render pills if more than 1 page
    if (totalPages > 1) {
    for (let p = 1; p <= totalPages; p++) {
      const btn=document.createElement('button');
      btn.type='button' ;
      btn.className='pagination-page-pill' + (p===currentPage ? ' active' : '' );
      btn.textContent=p;
      btn.onclick=()=> {
      currentPage = p;
      renderPagination();
      };
      pageNumbersContainer.appendChild(btn);
      }
      }
      }

      // Only show pagination footer when total items exceed PAGE_SIZE (10)
      if (paginationWrapper) {
      if (total > PAGE_SIZE) {
      paginationWrapper.style.setProperty('display', 'flex', 'important');
      } else {
      paginationWrapper.style.setProperty('display', 'none', 'important');
      }
      }
      }

      window.selectVendorTab = function(status) {
        currentVendorStatus = status;
        window.applyVendorFilter();
      };

      window.changeVendorPage = function(delta) {
      currentPage += delta;
      renderPagination();
      };

      window.applyVendorFilter = function() {
      const q = input ? input.value.toLowerCase().trim() : '';
      const selectedCat = categorySelect ? categorySelect.value.toLowerCase() : 'all';
      const selectedStatus = currentVendorStatus;

      // Sync Enseur Switch Bar tabs
      document.querySelectorAll('#vendorSwitchBar .erp-switch-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.status === selectedStatus);
      });

      const rows = Array.from(document.querySelectorAll('#vendorsTable tbody tr.vendor-item-row'));
      matchedRows = [];

      rows.forEach(r => {
      const textMatch = !q || r.innerText.toLowerCase().includes(q);
      const rowCat = (r.getAttribute('data-category') || '').toLowerCase();
      const catMatch = (selectedCat === 'all') || (rowCat === selectedCat);
      const rowStatusGroup = (r.getAttribute('data-status-group') || '').toLowerCase();
      const statusMatch = (selectedStatus === 'all') || (rowStatusGroup === selectedStatus);

      if (textMatch && catMatch && statusMatch) {
      matchedRows.push(r);
      } else {
      r.style.display = 'none';
      }
      });

      if (emptyRow) {
      emptyRow.classList.toggle('d-none', matchedRows.length > 0 || rows.length === 0);
      }

      if (clearBtn) {
      const hasFilter = (q !== '') || (selectedCat !== 'all') || (selectedStatus !== 'all');
      clearBtn.classList.toggle('d-none', !hasFilter);
      }

      currentPage = 1;
      renderPagination();
      };

      window.resetVendorFilters = function() {
      if (input) input.value = '';
      if (categorySelect) categorySelect.value = 'all';
      currentVendorStatus = 'all';
      window.applyVendorFilter();
      };

      // Initial run
      window.applyVendorFilter();
      })();
      @endsection