@extends('layouts.app')

@section('title', 'Purchase Requisition')

@php
  $statusMeta = [
    'pending_l1'  => ['label' => 'Pending L1', 'badge' => 'warning'],
    'pending_l2'  => ['label' => 'Pending L2', 'badge' => 'warning'],
    'pending_l3'  => ['label' => 'Pending L3', 'badge' => 'warning'],
    'pending_l4'  => ['label' => 'Pending L4', 'badge' => 'warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'danger'],
    'draft'       => ['label' => 'Draft', 'badge' => 'secondary'],
  ];

  $stageTitles = [
    1 => 'L1: Manager',
    2 => 'L2: Finance',
    3 => 'L3: CFO',
    4 => 'L4: SG',
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-0">Purchase Requisition</h1>
    </div>

    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#importExcelModal">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i>
          <span>Import Excel</span>
        </button>
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-plus-circle"></i>
          <span>New Requisition</span>
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

  @if ($errors->any())
    <div class="alert alert-danger mb-4 p-3 shadow-xs rounded-3 border-danger-subtle">
      <div class="d-flex align-items-center gap-2 fw-bold text-danger mb-1">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>Bulk Import / Requisition Notice</span>
      </div>
      <div class="small text-danger-emphasis mb-2">
        {{ $errors->first('file') ?: ($errors->first('budget') ?: 'Validation encountered issues. Please review details below.') }}
      </div>
      @if ($errors->has('import_errors'))
        <div class="p-2.5 bg-white rounded border border-danger-subtle small font-monospace mt-2" style="max-height: 200px; overflow-y: auto; font-size: 11.5px; line-height: 1.5;">
          <div class="fw-bold text-danger text-uppercase mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">Row-Level Errors Detected:</div>
          <ul class="mb-0 ps-3 text-danger">
            @foreach ($errors->get('import_errors')[0] ?? [] as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  @endif

  <!-- Status KPI Tab Cards (Approved, Pending, Rejected) - Informational Only -->
  <div class="row g-2 mb-3">
    <!-- Approved Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Approved</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $prs->where('status', 'approved')->count() }}</div>
        </div>
      </div>
    </div>

    <!-- Pending Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $prs->whereIn('status', ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4'])->count() }}</div>
        </div>
      </div>
    </div>

    <!-- Rejected Card -->
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-danger h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Rejected</span>
            <div class="stat-icon-box icon-danger" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-x-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $prs->where('status', 'rejected')->count() }}</div>
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

    .pr-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    #prTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #prTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: #475569;
      vertical-align: middle;
      padding: 10px 10px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #prTable td {
      vertical-align: middle;
      padding: 10px 10px;
      font-size: 12px;
      line-height: 1.35;
      border-bottom: 1px solid #f1f5f9;
      background-color: #ffffff;
      transition: background-color 0.15s ease;
    }
    #prTable tbody tr:hover td {
      background-color: #f8faff;
    }
    #prTable tbody tr:last-child td {
      border-bottom: none;
    }

    .pr-filter-toolbar {
      padding-top: 10px !important;
      padding-bottom: 10px !important;
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

    /* Clean, tag-based Item List */
    .item-tags-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      align-items: center;
      max-width: 320px;
    }
    .item-name-tag {
      font-size: 11.5px;
      font-weight: 600;
      color: #1e293b;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
      padding: 1.5px 6px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      line-height: 1.25;
    }
    .item-qty-tag {
      font-family: var(--bs-font-monospace);
      font-size: 10px;
      color: #64748b;
      font-weight: 500;
    }

    .status-badge-custom {
      font-size: 10.5px;
      font-weight: 600;
      padding: 2.5px 6px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      white-space: nowrap;
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

  <!-- Requisitions Table Card -->
  <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px;">
    <!-- Header -->
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 px-3.5 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-text text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Purchase Requisitions Pipeline</h6>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-2.5 py-1" style="font-size: 12px; font-weight: 600;" id="prRecordCountBadge">
          {{ $prs->count() }} Records
        </span>
      </div>
    </div>

    <!-- Filter & Search Toolbar (Clean, Minimal, Aligned with PO & Empanelment) -->
    <div class="card-body py-2.5 px-3 border-bottom bg-white">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Live Search -->
        <div class="flex-grow-1" style="min-width: 220px;">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2"><i class="bi bi-search"></i></span>
            <input type="text" id="prSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="Search PR No, item, department, title..." oninput="applyPrFilters()">
          </div>
        </div>

        <!-- Status Filter -->
        <div style="width: 140px;">
          <select id="prStatusFilter" class="form-select erp-filter-ctrl bg-white" onchange="onStatusSelectChange(this.value)">
            <option value="all">All Statuses</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Department Filter -->
        <div style="width: 155px;">
          <select id="prDepartmentFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyPrFilters()">
            <option value="all">All Departments</option>
            @foreach($prs->pluck('department')->unique()->filter()->sort() as $dept)
              <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <!-- Filter Dropdown Drawer (Sector, Requester, Date Range, Amount Range) -->
        <div class="dropdown">
          <button class="btn btn-outline-secondary erp-filter-ctrl d-inline-flex align-items-center gap-1.5 px-2.5 position-relative" type="button" id="prAdvFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="More Filters">
            <i class="bi bi-funnel"></i>
            <span>Filters</span>
            <span id="prAdvFilterIndicator" class="badge rounded-pill bg-primary ms-1 px-1.5 py-0.5 d-none" style="font-size: 10px;">•</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end shadow p-3.5 border-0" style="width: 320px; font-size: 12.5px; border-radius: 8px;">
            <div class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
              <span class="d-flex align-items-center gap-1.5"><i class="bi bi-sliders text-primary"></i> Detailed Filters</span>
              <button type="button" class="btn btn-link text-decoration-none p-0 text-muted small" style="font-size: 11.5px;" onclick="resetAdvFilters()">Reset</button>
            </div>

            <!-- Sector Filter -->
            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Sector / Category</label>
              <select id="prCategoryFilter" class="form-select form-select-sm" onchange="applyPrFilters()">
                <option value="all">All Sectors</option>
                <option value="IT & Hardware">IT &amp; Hardware</option>
                <option value="Marketing & Events">Marketing &amp; Events</option>
                <option value="Operations & Logistics">Operations &amp; Logistics</option>
                <option value="Project Procurement">Project Procurement</option>
                <option value="Finance & Services">Finance &amp; Services</option>
                <option value="HR & Admin Supplies">HR &amp; Admin Supplies</option>
                <option value="General Procurement">General Procurement</option>
              </select>
            </div>

            <!-- Requester Filter -->
            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Requester</label>
              <select id="prRequesterFilter" class="form-select form-select-sm" onchange="applyPrFilters()">
                <option value="all">All Requesters</option>
                @foreach($prs->pluck('creator.name')->unique()->filter()->sort() as $requester)
                  <option value="{{ $requester }}">{{ $requester }}</option>
                @endforeach
              </select>
            </div>

            <!-- Date Range -->
            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Required By Date</label>
              <div class="row g-1">
                <div class="col-6">
                  <input type="date" id="prDateFrom" class="form-control form-control-sm" placeholder="From" onchange="applyPrFilters()">
                </div>
                <div class="col-6">
                  <input type="date" id="prDateTo" class="form-control form-control-sm" placeholder="To" onchange="applyPrFilters()">
                </div>
              </div>
            </div>

            <!-- Amount Range -->
            <div class="mb-2">
              <label class="form-label fw-semibold text-secondary mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Est. Amount (₹)</label>
              <div class="row g-1">
                <div class="col-6">
                  <input type="number" id="prAmountMin" class="form-control form-control-sm" placeholder="Min ₹" min="0" step="500" oninput="applyPrFilters()">
                </div>
                <div class="col-6">
                  <input type="number" id="prAmountMax" class="form-control form-control-sm" placeholder="Max ₹" min="0" step="500" oninput="applyPrFilters()">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reset Button -->
        <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none px-2.5" id="clearPrFilterBtn" onclick="resetAllFilters()" title="Reset All Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive pr-table-container">
        <table class="table align-middle mb-0" id="prTable">
          <thead class="table-light">
            <tr>
              <th class="ps-3 text-nowrap" style="min-width: 140px;">PR NO.</th>
              <th class="text-nowrap" style="min-width: 160px;">PR TITLE</th>
              <th style="min-width: 220px;">ITEM NAME(S)</th>
              <th class="text-nowrap" style="min-width: 110px;">DEPARTMENT</th>
              <th class="text-nowrap" style="min-width: 125px;">REGION / LOCATION</th>
              <th class="text-nowrap" style="min-width: 130px;">CATEGORY</th>
              <th class="text-end text-nowrap" style="min-width: 110px;">EST. COST</th>
              <th class="text-center text-nowrap" style="min-width: 110px;">REQUIRED BY</th>
              <th class="text-center text-nowrap" style="min-width: 100px;">STATUS</th>
              <th class="text-center pe-3 text-nowrap" style="min-width: 90px;">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($prs as $pr)
              @php 
                $meta = $statusMeta[$pr->status] ?? $statusMeta['draft']; 
                $filterGroup = in_array($pr->status, ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4']) ? 'pending' : $pr->status;
                $displayTitle = $pr->title ?: ($pr->items->first()?->item_name ?? 'Procurement Requisition');
                $itemsSearch = $pr->items->pluck('item_name')->join(' ');
                $categoryMap = [
                  'IT' => 'IT & Hardware',
                  'Marketing' => 'Marketing & Events',
                  'Operations' => 'Operations & Logistics',
                  'Projects' => 'Project Procurement',
                  'Finance' => 'Finance & Services',
                  'HR' => 'HR & Admin Supplies',
                ];
                $category = $categoryMap[$pr->department] ?? 'General Procurement';
                $canApprove = $pr->currentApprovalFor(auth()->user());
              @endphp
              <tr class="pr-item-row" 
                  data-filter-status="{{ $filterGroup }}" 
                  data-filter-dept="{{ strtolower($pr->department) }}"
                  data-filter-category="{{ $category }}"
                  data-filter-requester="{{ $pr->creator?->name ?? 'System' }}"
                  data-filter-date="{{ optional($pr->required_by)->format('Y-m-d') ?? '' }}"
                  data-filter-amount="{{ $pr->estimatedCostInclGst() }}"
                  data-filter-search="{{ strtolower($pr->pr_number . ' ' . $displayTitle . ' ' . ($pr->event_name ?? '') . ' ' . ($pr->region ?? '') . ' ' . ($pr->special_instructions ?? '') . ' ' . $itemsSearch . ' ' . $pr->department . ' ' . $category . ' ' . ($pr->creator?->name ?? '')) }}">
                <!-- 1. PR No. -->
                <td class="ps-3 text-nowrap">
                  <a href="{{ route('requisition.show', $pr) }}" class="pr-num-code" title="{{ $pr->pr_number }}">
                    {{ $pr->pr_number }}
                  </a>
                </td>

                <!-- 2. PR Title -->
                <td class="text-nowrap">
                  <span class="fw-semibold text-dark" style="font-size: 12.5px;" title="{{ $displayTitle }}">
                    {{ $displayTitle }}
                  </span>
                </td>

                <!-- 3. Item Name(s) -->
                <td>
                  <div class="item-tags-wrap">
                    @foreach ($pr->items as $item)
                      <span class="item-name-tag" title="{{ $item->item_name }}">
                        <i class="bi bi-box-seam text-secondary" style="font-size: 10px;"></i>
                        <span>{{ $item->item_name }}</span>
                        @if ($item->quantity > 0)
                          <span class="item-qty-tag">({{ $item->quantity }} {{ $item->unit ?: 'Units' }})</span>
                        @endif
                      </span>
                    @endforeach
                  </div>
                </td>

                <!-- 4. Department -->
                <td class="text-nowrap">
                  <span class="fw-semibold text-dark" style="font-size: 12px;">{{ $pr->department }}</span>
                  @if($pr->sole_source)
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 px-1.5 py-0.5" style="font-size: 9.5px; font-weight: 600;">Sole</span>
                  @endif
                </td>

                <!-- 5. Region / Location -->
                <td class="text-nowrap">
                  @if ($pr->region)
                    <span class="text-dark" style="font-size: 12px;">{{ $pr->region }}</span>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>

                <!-- 6. Category -->
                <td class="text-nowrap">
                  <span class="badge bg-light text-dark border fw-medium" style="font-size: 11px;">{{ $category }}</span>
                </td>

                <!-- 5. Est. Cost -->
                <td class="text-end text-nowrap">
                  <span class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</span>
                </td>

                <!-- 6. Required By -->
                <td class="text-center text-nowrap">
                  <span class="text-secondary" style="font-size: 11.5px;">{{ optional($pr->required_by)->format('d M Y') ?? '—' }}</span>
                </td>

                <!-- 7. Status -->
                <td class="text-center text-nowrap">
                  <span class="status-badge-custom bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle">
                    <i class="bi bi-circle-fill" style="font-size: 5px;"></i>
                    <span>{{ $meta['label'] }}</span>
                  </span>
                </td>

                <!-- 8. Action (Role-wise Actions Dropdown & Direct Actions) -->
                <td class="text-center pe-3 text-nowrap" style="min-width: 110px;">
                  @php
                    $isApproverInChain = $pr->approvals->where('role_required', auth()->user()?->role)->isNotEmpty()
                      || in_array(auth()->user()?->role, [\App\Support\Roles::PR_APPROVER_L1, \App\Support\Roles::PR_APPROVER_L2, \App\Support\Roles::CFO, \App\Support\Roles::SG, \App\Support\Roles::ASG1, \App\Support\Roles::ASG2, \App\Support\Roles::ADMIN_HEAD], true);
                    $canEditPr = ($pr->status !== 'approved')
                      && (in_array(auth()->user()->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true) || auth()->id() === $pr->created_by || $isApproverInChain);
                    $hasDropdownActions = ($canApprove || $canEditPr);
                  @endphp

                  @if ($hasDropdownActions)
                    <div class="dropdown d-inline-block">
                      <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-none dropdown-toggle" data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false" style="font-size: 11px; height: 26px; line-height: 1;">
                        <i class="bi bi-gear" style="font-size: 11px;"></i>
                        <span>Action</span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 12px; min-width: 170px;">
                        <li>
                          <a class="dropdown-item py-1.5" href="{{ route('requisition.show', $pr) }}">
                            <i class="bi bi-eye text-primary me-1.5"></i>
                            <span>View Details</span>
                          </a>
                        </li>

                        @if ($canApprove)
                          <li><hr class="dropdown-divider my-1"></li>
                          <li>
                            <button type="button" class="dropdown-item py-1.5 text-success" data-bs-toggle="modal" data-bs-target="#tableApproveModal-{{ $pr->id }}">
                              <i class="bi bi-check-circle-fill text-success me-1.5"></i>
                              <span>Approve (L{{ $canApprove->level }})</span>
                            </button>
                          </li>
                          <li>
                            <button type="button" class="dropdown-item py-1.5 text-danger" data-bs-toggle="modal" data-bs-target="#tableRejectModal-{{ $pr->id }}">
                              <i class="bi bi-x-circle-fill text-danger me-1.5"></i>
                              <span>Reject / Decline</span>
                            </button>
                          </li>
                        @endif

                        @if ($canEditPr)
                          <li><hr class="dropdown-divider my-1"></li>
                          <li>
                            <a class="dropdown-item py-1.5 text-dark" href="{{ route('requisition.edit', $pr) }}">
                              <i class="bi bi-pencil-square text-secondary me-1.5"></i>
                              <span>{{ $pr->status === 'rejected' ? 'Correct & Resubmit' : ($canApprove ? 'Edit & Re-send' : 'Edit Requisition') }}</span>
                            </a>
                          </li>
                        @endif
                      </ul>
                    </div>
                  @else
                    <a href="{{ route('requisition.show', $pr) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11px; height: 26px; line-height: 1;">
                      <i class="bi bi-eye" style="font-size: 11px;"></i>
                      <span>View</span>
                    </a>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No purchase requisitions filed yet.</span>
                </td>
              </tr>
            @endforelse
            <tr id="emptyPrFilterRow" class="d-none">
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-funnel fs-2 d-block mb-2 text-secondary"></i>
                <div class="fw-semibold text-main mb-1">No matching requisitions found</div>
                <p class="small text-muted mb-2">Try adjusting your search terms or clearing filters.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="resetAllFilters()">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Reset All Filters
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Pagination Footer (10 items per page) -->
      <div class="table-pagination-footer d-flex flex-wrap align-items-center justify-content-between gap-3" id="prPaginationWrapper" style="display: none !important;">
        <div class="text-muted small" id="prPaginationInfo" style="font-size: 12.5px;">
          Showing <span id="prPagStart">1</span> to <span id="prPagEnd">{{ min(10, $prs->count()) }}</span> of <span id="prPagTotal">{{ $prs->count() }}</span> Records
        </div>
        <div class="d-flex align-items-center gap-1.5" id="prPaginationControls">
          <button type="button" class="pagination-btn" id="prPrevPageBtn" onclick="changePrPage(-1)" disabled>
            <i class="bi bi-chevron-left"></i>
            <span>Previous</span>
          </button>
          <div class="d-flex align-items-center gap-1" id="prPageNumbers">
            <!-- Dynamic Page Numbers -->
          </div>
          <button type="button" class="pagination-btn" id="prNextPageBtn" onclick="changePrPage(1)">
            <span>Next</span>
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  @foreach ($prs as $pr)
    @php $canAct = $pr->currentApprovalFor(auth()->user()); @endphp
    @if ($canAct)
      <!-- Quick Approve Modal for PR {{ $pr->id }} -->
      <div class="modal fade" id="tableApproveModal-{{ $pr->id }}" tabindex="-1" aria-labelledby="tableApproveLabel-{{ $pr->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-success-subtle py-2.5 px-3.5 border-bottom border-success-subtle">
              <div class="d-flex align-items-center gap-2 text-success">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <h6 class="modal-title fw-bold fs-6 mb-0" id="tableApproveLabel-{{ $pr->id }}">Approve Requisition</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <p class="text-secondary small mb-3">
                You are endorsing <strong>{{ $pr->pr_number }}</strong> ({{ $pr->title ?: $pr->department }}) as <strong>{{ \App\Support\Roles::label(auth()->user()->role) }}</strong> at <strong>Level {{ $canAct->level }}</strong>.
              </p>
              <div class="mb-2">
                <label class="form-label small fw-semibold text-dark">Approval Comments <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text" name="comments" class="form-control form-control-sm" placeholder="e.g. Cleared under departmental budget allocation">
              </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-check2-circle"></i>
                <span>Confirm Endorsement</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Reject Modal for PR {{ $pr->id }} -->
      <div class="modal fade" id="tableRejectModal-{{ $pr->id }}" tabindex="-1" aria-labelledby="tableRejectLabel-{{ $pr->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <form method="POST" action="{{ route('requisition.reject', $pr) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
              <div class="d-flex align-items-center gap-2 text-danger">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <h6 class="modal-title fw-bold fs-6 mb-0" id="tableRejectLabel-{{ $pr->id }}">Decline Requisition</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <p class="text-secondary small mb-3">
                Decline <strong>{{ $pr->pr_number }}</strong>. The reserved budget of <strong>₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</strong> will be released.
              </p>
              <div class="mb-2">
                <label class="form-label small fw-semibold text-dark">Reason for Rejection <span class="text-danger">*</span></label>
                <textarea name="comments" class="form-control form-control-sm" rows="3" placeholder="State specific reason for declining..." required></textarea>
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

  @if ($canCreate)
    <!-- Simple & Clean Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <form method="POST" action="{{ route('requisition.import-excel') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header py-3 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-5"></i>
                <h6 class="modal-title fw-bold text-dark mb-0" id="importExcelModalLabel">Import Excel Requisition</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
              <!-- Download template banner -->
              <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border mb-3">
                <div class="d-flex align-items-center gap-2.5">
                  <i class="bi bi-filetype-xlsx text-success fs-4"></i>
                  <div>
                    <div class="fw-semibold text-dark small">Download Excel Template</div>
                    <div class="text-muted" style="font-size: 11px;">PR NO., TITLE, DEPT, ITEM NAME, QTY, UNIT, UNIT COST, GST%, SPECS, REQUIRED BY, PURPOSE, SOLE SOURCE, STATUS</div>
                  </div>
                </div>
                <a href="{{ route('requisition.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 fw-semibold" style="font-size: 11.5px;">
                  <i class="bi bi-download"></i> Template
                </a>
              </div>

              <div class="mb-2">
                <label class="form-label fw-semibold small text-dark mb-1">Select Excel File (.xlsx, .csv) <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required style="font-size: 13px;">
                <div class="text-muted mt-1 small" style="font-size: 11.5px;">
                  Upload your completed Excel spreadsheet. For multi-item requisitions, list multiple rows with the same <strong>PR NO.</strong> All rows, departments, and line items will be grouped and processed automatically.
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-cloud-arrow-up"></i>
                <span>Import Excel</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  <!-- Excel Import Results Summary Window (Automatic popup after import) -->
  @if (session('import_summary'))
    @php
      $summary = session('import_summary');
    @endphp
    <div class="modal fade" id="importResultModal" tabindex="-1" aria-labelledby="importResultModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header py-3 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2.5">
              <div class="rounded-2 d-flex align-items-center justify-content-center text-white {{ $summary['entered_count'] > 0 ? 'bg-success' : 'bg-danger' }}" style="width: 34px; height: 34px; font-size: 16px;">
                <i class="bi {{ $summary['entered_count'] > 0 ? 'bi-file-earmark-check-fill' : 'bi-exclamation-triangle-fill' }}"></i>
              </div>
              <div>
                <h6 class="modal-title fw-bold text-dark mb-0" id="importResultModalLabel">Excel Bulk Import Results</h6>
                <div class="text-muted small" style="font-size: 12px;">Processed {{ $summary['total_rows'] }} total row(s) from uploaded file</div>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <!-- 2 Executive Summary KPI Cards -->
            <div class="row g-3 mb-4">
              <!-- Rows Entered Card -->
              <div class="col-12 col-sm-6">
                <div class="card h-100 border-0 rounded-3 p-3 {{ $summary['entered_count'] > 0 ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-light text-muted border' }}">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Rows Entered</span>
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                  </div>
                  <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-2 fw-bold font-monospace text-success">{{ $summary['entered_count'] }}</span>
                    <span class="small text-muted">of {{ $summary['total_rows'] }} total rows</span>
                  </div>
                  @if (!empty($summary['pr_number']))
                    <div class="small pt-2 border-top border-success-subtle d-flex align-items-center justify-content-between">
                      <span class="text-dark">PR: <strong class="font-monospace text-primary">{{ $summary['pr_number'] }}</strong></span>
                      <a href="{{ route('requisition.show', $summary['pr_id']) }}" class="btn btn-xs btn-success fw-semibold px-2 py-0.5" style="font-size: 11px;">
                        Open PR &rarr;
                      </a>
                    </div>
                  @else
                    <div class="small pt-2 border-top text-muted" style="font-size: 11px;">No requisition lines created.</div>
                  @endif
                </div>
              </div>

              <!-- Rows Rejected Card -->
              <div class="col-12 col-sm-6">
                <div class="card h-100 border-0 rounded-3 p-3 {{ $summary['rejected_count'] > 0 ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' : 'bg-light text-muted border' }}">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Rows Not Entered (Rejected)</span>
                    <i class="bi bi-x-circle-fill fs-5 {{ $summary['rejected_count'] > 0 ? 'text-danger' : 'text-muted' }}"></i>
                  </div>
                  <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-2 fw-bold font-monospace {{ $summary['rejected_count'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $summary['rejected_count'] }}</span>
                    <span class="small text-muted">of {{ $summary['total_rows'] }} total rows</span>
                  </div>
                  <div class="small pt-2 border-top {{ $summary['rejected_count'] > 0 ? 'border-danger-subtle text-danger' : 'text-muted' }}" style="font-size: 11px;">
                    {{ $summary['rejected_count'] > 0 ? 'Failed validation checks detailed below.' : 'All rows passed verification perfectly!' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Table of Rejections and Specific Reasons -->
            @if (!empty($summary['rejections']))
              <div class="border rounded-3 overflow-hidden">
                <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                  <span class="small fw-bold text-danger text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Row Rejection Details &amp; Reasons ({{ count($summary['rejections']) }})
                  </span>
                  <span class="text-muted small" style="font-size: 11px;">Correct these rows in your template to re-import</span>
                </div>
                <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                  <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                      <tr style="font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">
                        <th class="ps-3 text-center" style="width: 60px;">Row</th>
                        <th style="width: 170px;">PR Unique No.</th>
                        <th style="width: 170px;">Item Name</th>
                        <th>Reason for Rejection</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($summary['rejections'] as $rej)
                        <tr>
                          <td class="ps-3 text-center font-monospace fw-bold text-secondary">#{{ $rej['row'] }}</td>
                          <td class="font-monospace small text-dark fw-semibold">{{ $rej['pr_number'] ?? '—' }}</td>
                          <td class="fw-semibold text-dark text-truncate" style="max-width: 170px;" title="{{ $rej['item'] }}">{{ $rej['item'] ?: '—' }}</td>
                          <td>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-normal px-2 py-1" style="font-size: 11.5px; font-weight: 500;">
                              {{ $rej['reason'] }}
                            </span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif
          </div>

          <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small" style="font-size: 12px;">
              @if ($summary['entered_count'] > 0)
                <i class="bi bi-check2-all text-success me-1"></i> Requisition filed with {{ $summary['entered_count'] }} line item(s).
              @else
                <i class="bi bi-x-octagon text-danger me-1"></i> Please update the invalid rows and retry.
              @endif
            </span>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
              @if (!empty($summary['pr_id']))
                <a href="{{ route('requisition.show', $summary['pr_id']) }}" class="btn btn-primary btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                  <span>View Requisition</span>
                  <i class="bi bi-arrow-right"></i>
                </a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>

<script>
let currentPrPage = 1;
const PR_PAGE_SIZE = 10;
let matchedPrRows = [];

function onStatusSelectChange(status) {
  applyPrFilters();
}

function applyPrFilters() {
  const searchTerm = (document.getElementById('prSearchInput')?.value || '').trim().toLowerCase();
  const deptFilter = (document.getElementById('prDepartmentFilter')?.value || 'all').toLowerCase();
  const statusFilter = (document.getElementById('prStatusFilter')?.value || 'all');
  const categoryFilter = (document.getElementById('prCategoryFilter')?.value || 'all');
  const requesterFilter = (document.getElementById('prRequesterFilter')?.value || 'all');
  const dateFrom = document.getElementById('prDateFrom')?.value || '';
  const dateTo = document.getElementById('prDateTo')?.value || '';
  const amountMin = parseFloat(document.getElementById('prAmountMin')?.value || '') || null;
  const amountMax = parseFloat(document.getElementById('prAmountMax')?.value || '') || null;

  const rows = Array.from(document.querySelectorAll('.pr-item-row'));
  matchedPrRows = [];

  rows.forEach(row => {
    const rowStatus = row.dataset.filterStatus || '';
    const rowDept = row.dataset.filterDept || '';
    const rowCategory = row.dataset.filterCategory || '';
    const rowRequester = row.dataset.filterRequester || '';
    const rowDate = row.dataset.filterDate || '';
    const rowAmount = parseFloat(row.dataset.filterAmount || '0');
    const rowSearch = row.dataset.filterSearch || '';

    const matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
    const matchesDept = (deptFilter === 'all' || rowDept === deptFilter);
    const matchesCategory = (categoryFilter === 'all' || rowCategory === categoryFilter);
    const matchesRequester = (requesterFilter === 'all' || rowRequester === requesterFilter);
    const matchesSearch = (!searchTerm || rowSearch.includes(searchTerm));

    // Date range check
    let matchesDate = true;
    if (dateFrom && rowDate && rowDate < dateFrom) matchesDate = false;
    if (dateTo && rowDate && rowDate > dateTo) matchesDate = false;
    if ((dateFrom || dateTo) && !rowDate) matchesDate = false;

    // Amount range check
    let matchesAmount = true;
    if (amountMin !== null && rowAmount < amountMin) matchesAmount = false;
    if (amountMax !== null && rowAmount > amountMax) matchesAmount = false;

    if (matchesStatus && matchesDept && matchesCategory && matchesRequester && matchesSearch && matchesDate && matchesAmount) {
      matchedPrRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  const hasAdvFilters = (dateFrom !== '' || dateTo !== '' || amountMin !== null || amountMax !== null);
  const advIndicator = document.getElementById('prAdvFilterIndicator');
  if (advIndicator) {
    if (hasAdvFilters) {
      advIndicator.classList.remove('d-none');
    } else {
      advIndicator.classList.add('d-none');
    }
  }

  const isFiltered = (statusFilter !== 'all' || deptFilter !== 'all' || categoryFilter !== 'all' || requesterFilter !== 'all' || searchTerm !== '' || hasAdvFilters);
  const clearBtn = document.getElementById('clearPrFilterBtn');
  if (clearBtn) {
    if (isFiltered) {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  // Live badge update
  const badge = document.getElementById('prRecordCountBadge');
  if (badge) {
    badge.textContent = isFiltered ? `${matchedPrRows.length} of {{ $prs->count() }} Records` : `{{ $prs->count() }} Records`;
  }

  currentPrPage = 1;
  renderPrPagination();
}

function renderPrPagination() {
  const total = matchedPrRows.length;
  const totalPages = Math.ceil(total / PR_PAGE_SIZE) || 1;

  if (currentPrPage < 1) currentPrPage = 1;
  if (currentPrPage > totalPages) currentPrPage = totalPages;

  const startIdx = (currentPrPage - 1) * PR_PAGE_SIZE;
  const endIdx = startIdx + PR_PAGE_SIZE;

  // Toggle display of rows
  matchedPrRows.forEach((row, idx) => {
    if (idx >= startIdx && idx < endIdx) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Empty state handling
  const emptyRow = document.getElementById('emptyPrFilterRow');
  if (emptyRow) {
    if (total === 0 && document.querySelectorAll('.pr-item-row').length > 0) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  // Update pagination UI elements: only display when total items exceed PR_PAGE_SIZE (10)
  const paginationWrapper = document.getElementById('prPaginationWrapper');
  if (paginationWrapper) {
    if (total > PR_PAGE_SIZE) {
      paginationWrapper.style.setProperty('display', 'flex', 'important');
    } else {
      paginationWrapper.style.setProperty('display', 'none', 'important');
    }
  }

  const pagStart = document.getElementById('prPagStart');
  const pagEnd = document.getElementById('prPagEnd');
  const pagTotal = document.getElementById('prPagTotal');
  if (pagStart && pagEnd && pagTotal) {
    pagStart.textContent = total === 0 ? 0 : startIdx + 1;
    pagEnd.textContent = Math.min(endIdx, total);
    pagTotal.textContent = total;
  }

  const prevBtn = document.getElementById('prPrevPageBtn');
  const nextBtn = document.getElementById('prNextPageBtn');
  if (prevBtn) prevBtn.disabled = (currentPrPage <= 1);
  if (nextBtn) nextBtn.disabled = (currentPrPage >= totalPages);

  // Render page pills (only if multiple pages)
  const pillsContainer = document.getElementById('prPageNumbers');
  if (pillsContainer) {
    pillsContainer.innerHTML = '';
    if (totalPages > 1) {
      for (let p = 1; p <= totalPages; p++) {
        const pill = document.createElement('button');
        pill.type = 'button';
        pill.className = `pagination-page-pill ${p === currentPrPage ? 'active' : ''}`;
        pill.textContent = p;
        pill.onclick = () => {
          currentPrPage = p;
          renderPrPagination();
        };
        pillsContainer.appendChild(pill);
      }
    }
  }
}

function changePrPage(delta) {
  currentPrPage += delta;
  renderPrPagination();
}

function resetAdvFilters() {
  const dateFrom = document.getElementById('prDateFrom');
  const dateTo = document.getElementById('prDateTo');
  const amountMin = document.getElementById('prAmountMin');
  const amountMax = document.getElementById('prAmountMax');

  if (dateFrom) dateFrom.value = '';
  if (dateTo) dateTo.value = '';
  if (amountMin) amountMin.value = '';
  if (amountMax) amountMax.value = '';

  applyPrFilters();
}

function resetAllFilters() {
  const searchInput = document.getElementById('prSearchInput');
  const deptSelect = document.getElementById('prDepartmentFilter');
  const statusSelect = document.getElementById('prStatusFilter');
  const categorySelect = document.getElementById('prCategoryFilter');
  const requesterSelect = document.getElementById('prRequesterFilter');

  if (searchInput) searchInput.value = '';
  if (deptSelect) deptSelect.value = 'all';
  if (statusSelect) statusSelect.value = 'all';
  if (categorySelect) categorySelect.value = 'all';
  if (requesterSelect) requesterSelect.value = 'all';

  resetAdvFilters();
}

document.addEventListener('DOMContentLoaded', function() {
  applyPrFilters();

  @if (session('import_summary'))
    const resultModalEl = document.getElementById('importResultModal');
    if (resultModalEl) {
      const modal = new bootstrap.Modal(resultModalEl);
      modal.show();
    }
  @endif
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'Purchase Requisition' });
@endsection
