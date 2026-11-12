@extends('layouts.app')

@section('title', 'Vendor Empanelment')

@php
$statusMeta = [
  'pending_admin_head' => ['label' => 'Pending Admin', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
  'pending_l1' => ['label' => 'Pending ASG 1', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
  'pending_l2' => ['label' => 'Pending ASG 2', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
  'pending_cfo' => ['label' => 'Pending CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
  'approved' => ['label' => 'Empanelled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F', 'badge' => 'success'],
  'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A', 'badge' => 'danger'],
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
$uniqueCategories = $vendors->pluck('category')->filter()->unique()->sort();
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
      <a href="{{ route('onboarding') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-person-plus me-1"></i> Add Vendor
      </a>
    </div>
    @endif
  </div>

  @if (session('status'))
  <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
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

  <!-- Status KPI Cards (Total, Empanelled, Pending) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalVendors }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Empanelled</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-patch-check"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $empanelledCount }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingCount }}</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Clean informational cards */
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

    /* Responsive & clean layout across all screen sizes */
    .empanelment-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    #vendorsTable {
      width: 100% !important;
      min-width: 960px;
      table-layout: auto !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #vendorsTable thead th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      color: #475569;
      background-color: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      padding: 9px 8px;
      white-space: nowrap;
      vertical-align: middle;
    }
    #vendorsTable tbody td {
      padding: 8px 8px;
      vertical-align: middle;
      border-bottom: 1px solid #f1f5f9;
      background-color: #ffffff;
      font-size: 12px;
      line-height: 1.35;
      white-space: normal;
      transition: background-color 0.15s ease;
    }
    #vendorsTable tbody tr:last-child td {
      border-bottom: none;
    }
    #vendorsTable tbody tr:hover td {
      background-color: #f8faff;
    }

    .vendor-name-link {
      font-size: 12.5px;
      font-weight: 600;
      color: #0f172a;
      text-decoration: none;
      transition: color 0.15s ease;
      display: block;
      white-space: normal !important;
      word-break: break-word !important;
      line-height: 1.35;
    }
    .vendor-name-link:hover {
      color: #2563eb;
    }
    .vendor-code-tag {
      font-family: var(--bs-font-monospace);
      font-size: 10px;
      color: #64748b;
      background: #f1f5f9;
      padding: 1px 5px;
      border-radius: 4px;
      font-weight: 500;
      display: inline-block;
    }

    /* Statutory Compliance Tags */
    .compliance-box {
      display: flex;
      flex-direction: column;
      gap: 3px;
      min-width: 0;
    }
    .compliance-pill {
      font-family: var(--bs-font-monospace);
      font-size: 10.5px;
      line-height: 1.25;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }
    .compliance-lbl {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 0.05em;
      padding: 1px 4px;
      border-radius: 3px;
      background: #e2e8f0;
      color: #475569;
    }
    .compliance-val {
      color: #0f172a;
      font-weight: 600;
    }

    .category-badge {
      font-size: 11px;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 5px;
      white-space: nowrap !important;
      display: inline-block;
      max-width: 100%;
    }

    .contact-box {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }
    .contact-name {
      font-size: 12px;
      font-weight: 600;
      color: #1e293b;
      line-height: 1.3;
      white-space: normal !important;
      word-break: break-word !important;
    }
    .contact-detail {
      font-size: 11px;
      color: #64748b;
      display: flex;
      align-items: flex-start;
      gap: 4px;
      line-height: 1.25;
    }
    .contact-email-val {
      font-size: 11px;
      color: #64748b;
      white-space: normal !important;
      word-break: break-all !important;
      overflow-wrap: anywhere !important;
      line-height: 1.25;
    }

    .status-badge-custom {
      font-size: 10px;
      font-weight: 600;
      padding: 2.5px 5px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      white-space: nowrap;
    }

    /* Approval Hierarchy Chain Pills & Compact Info */
    .hierarchy-pill {
      font-size: 9.5px;
      font-weight: 700;
      font-family: var(--bs-font-monospace);
      padding: 1px 4.5px;
      border-radius: 3px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      line-height: 1.15;
    }
    .hierarchy-pill.cleared {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
    }
    .hierarchy-pill.current {
      background: #fffbeb;
      color: #b45309;
      border: 1px solid #fde68a;
      box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.2);
    }
    .hierarchy-pill.pending {
      background: #f8fafc;
      color: #94a3b8;
      border: 1px solid #e2e8f0;
    }
    .hierarchy-pill.declined {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .hierarchy-box {
      display: flex;
      flex-direction: column;
      gap: 3px;
      min-width: 0;
      white-space: nowrap;
    }
    .hierarchy-info-line {
      font-size: 11px;
      line-height: 1.25;
      display: flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }
    .hierarchy-role-tag {
      font-weight: 600;
      color: #334155;
    }
    .hierarchy-officer-name {
      color: #64748b;
      font-weight: 400;
    }

    /* Polished, Unified Action Controls */
    .vendor-action-cell {
      min-width: 145px;
      padding: 8px 12px !important;
    }
    .action-btn-single-view {
      height: 30px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      padding: 0 12px;
      border-radius: 6px;
      background: #fefce8;
      color: #a16207;
      border: 1px solid #fef08a;
      text-decoration: none;
      transition: all 0.15s ease;
    }
    .action-btn-single-view:hover {
      background: #fef9c3;
      color: #854d0e;
      border-color: #fde047;
    }
    .action-dropdown-toggle {
      height: 30px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      padding: 0 12px;
      border-radius: 6px;
      background: #ffffff;
      color: #0f172a;
      border: 1px solid #cbd5e1;
      transition: all 0.15s ease;
      cursor: pointer;
    }
    .action-dropdown-toggle:hover,
    .action-dropdown-toggle:focus {
      background: #f8fafc;
      border-color: #94a3b8;
      color: #0f172a;
    }
    .vendor-action-menu {
      min-width: 170px;
      padding: 5px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
      font-size: 12px;
      z-index: 1060;
    }
    .vendor-action-menu .dropdown-item {
      padding: 6px 9px;
      border-radius: 5px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 7px;
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
  </style>

  <!-- Filter & Search Bar Aligned Uniformly With PR & PO Modules -->
  <div class="card shadow-sm border-0 mb-3" style="border-radius: 8px;">
    <div class="card-body py-2.5 px-3">
      <div class="row g-2 align-items-center">
        <!-- Live Search -->
        <div class="col-12 col-md-5 col-lg-5">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="filterVendorsInput" class="form-control bg-white border-start-0 ps-0" placeholder="Search vendor name, code, location, contact..." oninput="applyVendorFilter()">
          </div>
        </div>

        <!-- Sector / Category Dropdown Filter -->
        <div class="col-6 col-md-3 col-lg-3">
          <select id="filterCategorySelect" class="form-select erp-filter-ctrl bg-white" onchange="applyVendorFilter()">
            <option value="all">All Sectors</option>
            @foreach($uniqueCategories as $cat)
              <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
            @endforeach
          </select>
        </div>

        <!-- Status Dropdown Filter -->
        <div class="col-6 col-md-3 col-lg-3">
          <select id="filterStatusSelect" class="form-select erp-filter-ctrl bg-white" onchange="applyVendorFilter()">
            <option value="all">All Statuses</option>
            <option value="approved">Empanelled</option>
            <option value="pending">Pending Approval</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Reset Button -->
        <div class="col-12 col-md-1 col-lg-1 text-end">
          <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none w-100 justify-content-center" id="clearVendorFilterBtn" onclick="resetVendorFilters()" title="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Empanelment Queue Table Card -->
  <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px;">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 px-3.5 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">Empanelment Directory</h6>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small" id="vendorFilterCount">Showing {{ $totalVendors }} of {{ $totalVendors }} vendors</span>
        <span class="badge bg-light text-dark border px-2.5 py-1" style="font-size: 12px; font-weight: 600;">{{ $totalVendors }} Vendors</span>
      </div>
    </div>

    <div class="table-responsive empanelment-table-container">
      <table class="table align-middle mb-0" id="vendorsTable">
        <thead>
          <tr>
            <th class="ps-3 pe-2 text-nowrap" style="min-width: 140px;">VENDOR &amp; CODE</th>
            <th class="px-2 text-nowrap" style="min-width: 130px;">COMPLIANCE / TAX ID</th>
            <th class="px-2 text-nowrap" style="min-width: 95px;">SECTOR / CATEGORY</th>
            <th class="px-2 text-nowrap" style="min-width: 95px;">LOCATION</th>
            <th class="text-center px-1 text-nowrap" style="min-width: 70px;">RATINGS</th>
            <th class="text-center px-1 text-nowrap" style="min-width: 75px;">ORDERS</th>
            <th class="px-2 text-nowrap" style="min-width: 170px;">CONTACT DETAILS</th>
            <th class="text-center px-1 text-nowrap" style="min-width: 60px;">MSME</th>
            <th class="text-center px-1 text-nowrap" style="min-width: 95px;">STATUS</th>
            <th class="px-2 text-nowrap" style="min-width: 170px;">APPROVAL HIERARCHY</th>
            <th class="text-center pe-3 ps-1 text-nowrap" style="min-width: 90px;">ACTION</th>
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
            $statusGroup = ($vendor->status === 'approved') ? 'approved' : (($vendor->status === 'rejected') ? 'rejected' : 'pending');
          @endphp
          <tr data-status-group="{{ $statusGroup }}" data-category="{{ strtolower($vendor->category ?: 'general') }}">
            <!-- 1. Vendor & Code -->
            <td class="ps-3 pe-2">
              <div class="d-flex flex-column align-items-start min-w-0">
                <a href="{{ route('empanelment.show', $vendor) }}" class="vendor-name-link" title="{{ $vendor->company_name }}">
                  {{ $vendor->company_name }}
                </a>
                <span class="vendor-code-tag mt-0.5">
                  {{ $vendor->vendor_code }}
                </span>
              </div>
            </td>

            <!-- 2. Compliance / Tax ID (PAN & GSTIN) -->
            <td class="px-2">
              <div class="compliance-box">
                <div class="compliance-pill" title="PAN: {{ $vendor->pan ?? $vendor->pan_number ?: 'Not Provided' }}">
                  <span class="compliance-lbl">PAN</span>
                  <span class="compliance-val">{{ $vendor->pan ?? $vendor->pan_number ?: '—' }}</span>
                </div>
                <div class="compliance-pill" title="GSTIN: {{ $vendor->gstin ?: 'Not Provided' }}">
                  <span class="compliance-lbl">GST</span>
                  <span class="compliance-val">{{ $vendor->gstin ?: '—' }}</span>
                </div>
              </div>
            </td>

            <!-- 3. Category -->
            <td class="px-2">
              <span class="badge bg-light text-dark border category-badge text-truncate d-inline-block" style="max-width: 100%; white-space: nowrap !important;" title="{{ $vendor->category ?: 'General' }}">
                {{ $vendor->category ?: 'General' }}
              </span>
            </td>

            <!-- 3. Location -->
            <td class="px-2">
              <div class="d-flex align-items-center gap-1 text-dark min-w-0" style="font-size: 11.5px;">
                <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 11px;"></i>
                <span class="fw-medium text-truncate" title="{{ $vendor->location ?: ($vendor->address ?: 'Not Specified') }}">
                  {{ $vendor->location ?: ($vendor->address ?: '—') }}
                </span>
              </div>
            </td>

            <!-- 4. Ratings -->
            <td class="text-center px-1">
              @if ($rating)
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center gap-1 py-1 px-1.5" style="font-size: 11px; font-weight: 700; white-space: nowrap;">
                  <i class="bi bi-star-fill text-warning" style="font-size: 10px;"></i>
                  <span>{{ number_format($rating, 1) }}/5</span>
                </span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>

            <!-- 5. Orders -->
            <td class="text-center px-1">
              @if ($orderCount > 0)
                <span class="badge bg-light text-dark border px-1.5 py-1 font-monospace text-nowrap" style="font-size: 11px; font-weight: 600;">
                  <i class="bi bi-bag-check text-success me-0.5"></i>{{ $orderCount }} {{ Str::plural('Order', $orderCount) }}
                </span>
              @else
                <span class="text-muted small">0 Orders</span>
              @endif
            </td>

            <!-- 6. Contact Details (No Text Truncate, Full Email Visible) -->
            <td class="px-2">
              <div class="contact-box min-w-0">
                <div class="contact-name" title="{{ $vendor->contact_person ?: '—' }}">
                  {{ $vendor->contact_person ?: '—' }}
                </div>
                @if($vendor->contact_email)
                  <div class="contact-detail" title="{{ $vendor->contact_email }}">
                    <i class="bi bi-envelope text-muted flex-shrink-0 mt-0.5"></i>
                    <span class="contact-email-val">{{ $vendor->contact_email }}</span>
                  </div>
                @elseif($vendor->contact_phone)
                  <div class="contact-detail" title="{{ $vendor->contact_phone }}">
                    <i class="bi bi-telephone text-muted flex-shrink-0 mt-0.5"></i>
                    <span class="contact-email-val">{{ $vendor->contact_phone }}</span>
                  </div>
                @endif
              </div>
            </td>

            <!-- 7. MSME -->
            <td class="text-center px-1">
              @if ($vendor->msme_status)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 10px; font-weight: 600;">
                  MSME
                </span>
              @else
                <span class="text-muted" style="font-size: 11px;">No</span>
              @endif
            </td>

            <!-- 8. Status -->
            <td class="text-center px-1">
              @if ($vendor->isBlacklisted())
                <span class="status-badge-custom bg-dark text-white border border-dark text-nowrap" title="Blacklisted: {{ $vendor->blacklist_reason }}">
                  <i class="bi bi-slash-circle-fill text-danger me-1"></i>
                  <span>Blacklisted</span>
                </span>
              @else
                <span class="status-badge-custom bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle text-nowrap" title="{{ $meta['label'] }}">
                  <i class="bi bi-circle-fill" style="font-size: 5px;"></i>
                  <span>{{ $meta['label'] }}</span>
                </span>
              @endif
            </td>

            <!-- 9. Approval Hierarchy (Clean & Minimal: L1 - L2 - L3 - L4 with Full Role & Officer Name) -->
            <td class="px-2" style="min-width: 170px;">
              @php
                $approvals = $vendor->approvals;
                $activePending = $approvals->where('status', 'pending')->sortBy('level')->first();
                $isRejected = $vendor->status === 'rejected';
                $isEmpanelled = $vendor->status === 'approved';
                $isBlacklisted = $vendor->isBlacklisted();

                // Approver/Rejecter detail extraction
                $lastApproved = $approvals->where('status', 'approved')->sortByDesc('acted_at')->first();
                $rejectedStep = $approvals->where('status', 'rejected')->first();
              @endphp
              <div class="hierarchy-box">
                <!-- Compact Stage Sequence Pills: L1 › L2 › L3 › L4 -->
                <div class="d-flex align-items-center gap-1">
                  @for ($lvl = 1; $lvl <= 4; $lvl++)
                    @php
                      $app = $approvals->firstWhere('level', $lvl);
                      $lvlStatus = $app?->status ?? 'pending';
                      $lvlTitle = $vendorStageTitles[$lvl] ?? 'L'.$lvl;
                      $isCurrent = (!$isRejected && !$isBlacklisted && $activePending && $activePending->level === $lvl);

                      // If a previous level is rejected, any subsequent level cannot be cleared
                      if ($rejectedStep && $lvl > $rejectedStep->level) {
                        $lvlStatus = 'pending';
                      }

                      if ($lvlStatus === 'approved') {
                        $pillClass = 'cleared';
                        $pillIcon = 'bi-check';
                        $pillTitle = "{$lvlTitle}: Approved" . ($app?->approver ? " by {$app->approver->name}" : '');
                      } elseif ($lvlStatus === 'rejected') {
                        $pillClass = 'declined';
                        $pillIcon = 'bi-x';
                        $pillTitle = "{$lvlTitle}: Declined" . ($app?->approver ? " by {$app->approver->name}" : '');
                      } elseif ($isCurrent) {
                        $pillClass = 'current';
                        $pillIcon = 'bi-arrow-right';
                        $pillTitle = "{$lvlTitle}: Pending Action";
                      } else {
                        $pillClass = 'pending';
                        $pillIcon = null;
                        $pillTitle = "{$lvlTitle}: " . ($rejectedStep && $lvl > $rejectedStep->level ? 'Halted' : 'Awaiting Stage');
                      }
                    @endphp
                    <span class="hierarchy-pill {{ $pillClass }}" title="{{ $pillTitle }}">
                      L{{ $lvl }}@if($pillIcon)<i class="bi {{ $pillIcon }} ms-0.5" style="font-size:8.5px;"></i>@endif
                    </span>
                    @if ($lvl < 4)
                      <span class="text-muted" style="font-size: 8px; opacity: 0.5;">›</span>
                    @endif
                  @endfor
                </div>

                <!-- Clean, Full Status Line with Role + Officer Name (No Truncation) -->
                <div class="hierarchy-info-line">
                  @if ($isBlacklisted)
                    <span class="text-muted d-inline-flex align-items-center gap-1">
                      <i class="bi bi-slash-circle text-danger" style="font-size:9.5px;"></i>
                      <span>Blacklisted</span>
                    </span>
                  @elseif ($isRejected && $rejectedStep)
                    @php
                      $rejectRole = $vendorStageTitles[$rejectedStep->level] ?? \App\Support\Roles::label($rejectedStep->role_required);
                      $rejectName = $rejectedStep->approver?->name;
                    @endphp
                    <span class="text-danger d-inline-flex align-items-center gap-1" title="Declined at Stage {{ $rejectedStep->level }} by {{ $rejectRole }}{{ $rejectName ? ' (' . $rejectName . ')' : '' }}">
                      <i class="bi bi-x-circle-fill text-danger flex-shrink-0" style="font-size: 9.5px;"></i>
                      <span>
                        <strong>{{ $rejectRole }}:</strong> {{ $rejectName ?: 'Declined' }}
                      </span>
                    </span>
                  @elseif ($isEmpanelled)
                    @php
                      $cfoName = $lastApproved?->approver?->name;
                    @endphp
                    <span class="text-success d-inline-flex align-items-center gap-1" title="Fully empanelled &bull; Final sign-off by CFO{{ $cfoName ? ' (' . $cfoName . ')' : '' }}">
                      <i class="bi bi-check-circle-fill text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                      <span>
                        <strong>CFO:</strong> {{ $cfoName ?: 'Approved' }}
                      </span>
                    </span>
                  @elseif ($activePending)
                    @php
                      $pendingRole = $vendorStageTitles[$activePending->level] ?? \App\Support\Roles::label($activePending->role_required);
                    @endphp
                    <span class="d-inline-flex align-items-center gap-1" title="Currently awaiting review by {{ $pendingRole }}">
                      <i class="bi bi-clock-history text-warning flex-shrink-0" style="font-size: 9.5px;"></i>
                      <span class="text-muted">Due:</span>
                      <strong class="text-dark">{{ $pendingRole }}</strong>
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </div>
              </div>
            </td>

            <!-- 10. Action (Clean Consistent Dropdown for Actions; Single View when view-only) -->
            <td class="text-center pe-3 ps-1 vendor-action-cell">
              @php
                $isBlacklisted = $vendor->isBlacklisted();
                $isEmpanelled = $vendor->isEmpanelled();
                $canBlacklist = (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                  && !$isBlacklisted;
                $canUnblacklist = (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                  && $isBlacklisted;
                $canEditOrDelete = in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true)
                  && $vendor->status !== 'approved'
                  && !$isBlacklisted;
                $canResendEmail = $isEmpanelled && in_array(auth()->user()->role, [\App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::BUYER], true);
                $hasMultipleActions = ($canAct || $canEditOrDelete || $canBlacklist || $canUnblacklist || $canResendEmail);
              @endphp

              @if ($hasMultipleActions)
                <div class="dropdown d-inline-block">
                  <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-none dropdown-toggle" data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false" style="font-size: 11px; height: 26px; line-height: 1;">
                    <i class="bi bi-gear" style="font-size: 11px;"></i>
                    <span>Action</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end vendor-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('empanelment.show', $vendor) }}">
                        <i class="bi bi-eye text-primary"></i>
                        <span>View Details</span>
                      </a>
                    </li>

                    @if ($canResendEmail)
                      <li>
                        <form method="POST" action="{{ route('empanelment.resend-email', $vendor) }}" class="m-0">
                          @csrf
                          <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-primary">
                            <i class="bi bi-envelope-arrow-up text-primary"></i>
                            <span>{{ $vendor->empanelment_email_sent_at ? 'Resend Empanelment Email' : 'Send Empanelment Email' }}</span>
                          </button>
                        </form>
                      </li>
                    @endif

                    @if ($canUnblacklist)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" class="dropdown-item text-success action-approve-item" data-bs-toggle="modal" data-bs-target="#unblacklistModal-{{ $vendor->id }}">
                          <i class="bi bi-shield-check text-success"></i>
                          <span>Unblacklist Vendor</span>
                        </button>
                      </li>
                    @endif

                    @if ($canEditOrDelete)
                      <li>
                        <a class="dropdown-item text-dark" href="{{ route('empanelment.edit', $vendor) }}">
                          <i class="bi bi-pencil text-secondary"></i>
                          <span>{{ $vendor->status === 'rejected' ? 'Correct & Resubmit' : 'Edit Application' }}</span>
                        </a>
                      </li>
                    @endif

                    @if ($canAct)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" class="dropdown-item action-approve-item" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $vendor->id }}">
                          <i class="bi bi-check-circle-fill text-success"></i>
                          <span>Approve Application</span>
                        </button>
                      </li>
                      <li>
                        <button type="button" class="dropdown-item action-reject-item" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $vendor->id }}">
                          <i class="bi bi-x-circle-fill text-danger"></i>
                          <span>Reject Application</span>
                        </button>
                      </li>
                    @endif

                    @if ($canBlacklist)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <button type="button" class="dropdown-item text-danger action-reject-item" data-bs-toggle="modal" data-bs-target="#blacklistModal-{{ $vendor->id }}">
                          <i class="bi bi-slash-circle text-danger"></i>
                          <span>Blacklist Vendor</span>
                        </button>
                      </li>
                    @endif

                    @if ($canEditOrDelete)
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        <form method="POST" action="{{ route('empanelment.destroy', $vendor) }}" onsubmit="return confirm('Are you sure you want to delete vendor {{ $vendor->vendor_code }} ({{ $vendor->company_name }})? This action cannot be undone.');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger action-reject-item">
                            <i class="bi bi-trash3 text-danger"></i>
                            <span>Delete Vendor</span>
                          </button>
                        </form>
                      </li>
                    @endif
                  </ul>
                </div>
              @else
                <a href="{{ route('empanelment.show', $vendor) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11px; height: 26px; line-height: 1;">
                  <i class="bi bi-eye" style="font-size: 11px;"></i>
                  <span>View</span>
                </a>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
              <span>No vendors submitted yet.</span>
            </td>
          </tr>
          @endforelse

          <!-- No Filter Match Row -->
          <tr id="emptyVendorFilterRow" class="d-none">
            <td colspan="11" class="text-center py-5 text-muted">
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
      <div class="d-flex align-items-center gap-1.5" id="vendorPaginationControls">
        <button type="button" class="pagination-btn" id="prevPageBtn" onclick="changeVendorPage(-1)" disabled>
          <i class="bi bi-chevron-left"></i>
          <span>Previous</span>
        </button>
        <div class="d-flex align-items-center gap-1" id="vendorPageNumbers">
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
                <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('CONFIRM PERMANENT BLACKLISTING: Are you completely certain you want to permanently blacklist this vendor?');">
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
                <button type="submit" class="btn btn-sm btn-success fw-semibold px-3 d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('Confirm lifting the blacklist on {{ $vendor->company_name }}?');">
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
const statusSelect = document.getElementById('filterStatusSelect');
const countEl = document.getElementById('vendorFilterCount');
const clearBtn = document.getElementById('clearVendorFilterBtn');
const emptyRow = document.getElementById('emptyVendorFilterRow');

const PAGE_SIZE = 10;
let currentPage = 1;
let matchedRows = [];

function renderPagination() {
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
  const pagStart = document.getElementById('pagStart');
  const pagEnd = document.getElementById('pagEnd');
  const pagTotal = document.getElementById('pagTotal');
  const paginationWrapper = document.getElementById('vendorPaginationWrapper');

  if (pagStart) pagStart.textContent = total === 0 ? 0 : startIndex + 1;
  if (pagEnd) pagEnd.textContent = Math.min(endIndex, total);
  if (pagTotal) pagTotal.textContent = total;

  if (countEl) {
    countEl.innerText = `Showing ${Math.min(endIndex, total)} of ${total} vendors`;
  }

  // Update Buttons
  const prevBtn = document.getElementById('prevPageBtn');
  const nextBtn = document.getElementById('nextPageBtn');
  if (prevBtn) prevBtn.disabled = (currentPage <= 1);
  if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);

  // Render Page Number Pills
  const pageNumbersContainer = document.getElementById('vendorPageNumbers');
  if (pageNumbersContainer) {
    pageNumbersContainer.innerHTML = '';
    
    // Only render pills if more than 1 page
    if (totalPages > 1) {
      for (let p = 1; p <= totalPages; p++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pagination-page-pill' + (p === currentPage ? ' active' : '');
        btn.textContent = p;
        btn.onclick = () => {
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

window.changeVendorPage = function(delta) {
  currentPage += delta;
  renderPagination();
};

window.applyVendorFilter = function() {
  const q = input ? input.value.toLowerCase().trim() : '';
  const selectedCat = categorySelect ? categorySelect.value.toLowerCase() : 'all';
  const selectedStatus = statusSelect ? statusSelect.value.toLowerCase() : 'all';

  const rows = Array.from(document.querySelectorAll('#vendorsTable tbody tr:not(#emptyVendorFilterRow)'));
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
  if (statusSelect) statusSelect.value = 'all';
  window.applyVendorFilter();
};

// Initial run
window.applyVendorFilter();
})();
@endsection
