@extends('layouts.app')

@section('title', 'Purchase Requisitions')

@php
  $statusMeta = [
    'pending_l1'  => ['label' => 'Pending L1', 'badge' => 'warning'],
    'pending_l2'  => ['label' => 'Pending L2', 'badge' => 'warning'],
    'pending_l3'  => ['label' => 'Pending L3', 'badge' => 'warning'],
    'pending_l4'  => ['label' => 'Pending L4', 'badge' => 'warning'],
    'on_hold'     => ['label' => 'On Hold', 'badge' => 'warning'],
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

  $prTotalCount = $prs->count();
  $prPendingCount = $prs->filter(fn($p) => in_array($p->status, ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4']))->count();
  $prApprovedCount = $prs->where('status', 'approved')->count();
  $prOnHoldCount = $prs->where('status', 'on_hold')->count();
  $prRejectedCount = $prs->where('status', 'rejected')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
      <h1 class="page-title">Purchase Requisitions</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      @if ($canCreate)
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm">
          <i class="bi bi-plus-lg"></i>
          <span>New Requisition</span>
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#importExcelModal">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          <span>Import Excel</span>
        </button>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger mb-3 p-3 rounded-3 border-danger-subtle">
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

  <style>
    /* Responsive & clean tightly-fitted table */
    .pr-table-container {
      width: 100%;
      overflow-x: auto;
      overflow-y: visible;
    }
    #prTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #prTable thead th {
      font-size: 11.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 10px 14px !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
    }
    #prTable tbody td {
      padding: 11px 14px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      background-color: #ffffff;
      line-height: 1.35 !important;
      white-space: normal;
      transition: background-color 0.15s ease;
    }
    #prTable tbody tr:hover td {
      background-color: #f8fafc;
    }
    #prTable tbody tr:last-child td {
      border-bottom: none !important;
    }
    #prTable th:first-child,
    #prTable td:first-child {
      padding-left: 16px !important;
    }
    #prTable th:last-child,
    #prTable td:last-child {
      padding-right: 16px !important;
    }

    .pr-num-code {
      font-family: var(--bs-font-monospace);
      font-size: 13px !important;
      font-weight: 700;
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

    .dash-review-btn {
      height: 28px;
      font-size: 12px;
      font-weight: 600;
      padding: 0 14px;
      line-height: 1;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .pr-action-cell {
      padding: 6px 8px !important;
      white-space: nowrap !important;
      text-align: right;
    }

    /* Client-side Table Pagination */
    .table-pagination-footer {
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 10px 16px;
    }
    .pagination-btn {
      height: 30px;
      font-size: 12px;
      font-weight: 600;
      padding: 0 10px;
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
      min-width: 30px;
      height: 30px;
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
      background: var(--brand-maroon, #c8102e);
      color: #ffffff;
      border-color: var(--brand-maroon, #c8102e);
    }
  </style>

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="prSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectPrTab('all')">
      All Requisitions <span class="erp-switch-count">{{ $prTotalCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending" onclick="selectPrTab('pending')">
      Pending Review <span class="erp-switch-count">{{ $prPendingCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectPrTab('approved')">
      Approved <span class="erp-switch-count">{{ $prApprovedCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="on_hold" onclick="selectPrTab('on_hold')">
      On Hold <span class="erp-switch-count">{{ $prOnHoldCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="rejected" onclick="selectPrTab('rejected')">
      Rejected <span class="erp-switch-count">{{ $prRejectedCount }}</span>
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
          <input type="text" id="prSearchInput" class="erp-search-input" placeholder="Search PR number, title, item, department..." oninput="applyPrFilters()">
        </div>

        <!-- Department Filter -->
        <select id="prDepartmentFilter" class="erp-select" onchange="applyPrFilters()">
          <option value="all">All Departments</option>
          @foreach(($departments ?? $prs->pluck('department')->unique()->filter()->sort()) as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
          @endforeach
        </select>

        <!-- Detailed Filters Dropdown -->
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary erp-btn-reset d-inline-flex align-items-center gap-1.5 px-2.5 position-relative" type="button" id="prAdvFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="More Filters">
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
            @php
              $defaultCategoryMap = [
                'IT' => 'IT & Hardware',
                'Marketing' => 'Marketing & Events',
                'Operations' => 'Operations & Logistics',
                'Projects' => 'Project Procurement',
                'Finance' => 'Finance & Services',
                'HR' => 'HR & Admin Supplies',
              ];
              $dynamicCategories = ($departments ?? $prs->pluck('department')->unique()->filter())
                ->map(fn ($d) => $defaultCategoryMap[$d] ?? ($d . ' Procurement'))
                ->unique()
                ->sort()
                ->values();
            @endphp
            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Sector / Category</label>
              <select id="prCategoryFilter" class="form-select form-select-sm" onchange="applyPrFilters()">
                <option value="all">All Sectors</option>
                @foreach ($dynamicCategories as $catOption)
                  <option value="{{ $catOption }}">{{ $catOption }}</option>
                @endforeach
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
        <button type="button" class="erp-btn-reset d-none" id="clearPrFilterBtn" onclick="resetAllFilters()" title="Reset All Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
          <span>Reset</span>
        </button>
      </div>
    </div>

    <!-- The Table Container -->
    <div class="pr-table-container">
      <table class="erp-table table-hover align-middle mb-0" id="prTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">PR NUMBER</th>
            <th style="width: 15%;">DEPARTMENT</th>
            <th style="width: 35%;">TITLE</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
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
                $category = $categoryMap[$pr->department] ?? ($pr->department . ' Procurement');
                $prProjLabel = $pr->projectBudget?->project_name ?: $pr->event_name;
              @endphp
              <tr class="pr-item-row" 
                  data-filter-status="{{ $filterGroup }}" 
                  data-filter-dept="{{ strtolower($pr->department) }}"
                  data-filter-category="{{ $category }}"
                  data-filter-requester="{{ $pr->creator?->name ?? 'System' }}"
                  data-filter-date="{{ optional($pr->required_by)->format('Y-m-d') ?? '' }}"
                  data-filter-amount="{{ $pr->estimatedCostInclGst() }}"
                  data-filter-search="{{ strtolower($pr->pr_number . ' ' . $displayTitle . ' ' . ($prProjLabel ?? '') . ' ' . ($pr->region ?? '') . ' ' . ($pr->special_instructions ?? '') . ' ' . $itemsSearch . ' ' . $pr->department . ' ' . $category . ' ' . ($pr->creator?->name ?? '')) }}">
                <!-- 1. PR No. -->
                <td class="ps-3" title="{{ $pr->pr_number }}">
                  <a href="{{ route('requisition.show', $pr) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;">
                    {{ $pr->pr_number }}
                  </a>
                </td>

                <!-- 2. Department -->
                <td>
                  <span class="badge badge-soft-info" style="font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px;">{{ strtoupper($pr->department ?? 'GENERAL') }}</span>
                </td>

                <!-- 3. PR Title -->
                <td title="{{ $displayTitle }}">
                  <span class="fw-semibold text-main" style="font-size: 13.5px;">{{ $displayTitle }}</span>
                  @if (!empty($prProjLabel))
                    <span class="d-none" aria-hidden="true">{{ $prProjLabel }}</span>
                  @endif
                </td>

                <!-- 4. Status -->
                <td>
                  <span class="badge badge-soft-{{ $meta['badge'] ?? 'warning' }} status-badge-pipeline">{{ $meta['label'] }}</span>
                </td>

                <!-- 5. Action -->
                <td class="text-end pe-3 pr-action-cell">
                  <div class="erp-row-actions">
                    <a href="{{ route('requisition.show', $pr) }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                    <div class="dropdown">
                      <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                        <li>
                          <a class="dropdown-item" href="{{ route('requisition.pdf', $pr) }}" target="_blank">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Download PDF
                          </a>
                        </li>
                        @if ($canCreate && in_array($pr->status, ['pending_l1', 'on_hold']))
                          <li>
                            <a class="dropdown-item" href="{{ route('requisition.edit', $pr) }}">
                              <i class="bi bi-pencil text-secondary"></i> Edit Requisition
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
                  <span>No purchase requisitions filed yet.</span>
                </td>
              </tr>
            @endforelse
            <tr id="emptyPrFilterRow" class="d-none">
              <td colspan="5" class="text-center py-5 text-muted">
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
let currentPrStatus = 'all';

function selectPrTab(status) {
  currentPrStatus = status;
  applyPrFilters();
}

function onStatusSelectChange(status) {
  currentPrStatus = status;
  applyPrFilters();
}

function applyPrFilters() {
  const searchTerm = (document.getElementById('prSearchInput')?.value || '').trim().toLowerCase();
  const deptFilter = (document.getElementById('prDepartmentFilter')?.value || 'all').toLowerCase();
  const statusFilter = currentPrStatus;
  const categoryFilter = (document.getElementById('prCategoryFilter')?.value || 'all');
  const requesterFilter = (document.getElementById('prRequesterFilter')?.value || 'all');
  const dateFrom = document.getElementById('prDateFrom')?.value || '';
  const dateTo = document.getElementById('prDateTo')?.value || '';
  const amountMin = parseFloat(document.getElementById('prAmountMin')?.value || '') || null;
  const amountMax = parseFloat(document.getElementById('prAmountMax')?.value || '') || null;

  // Sync Enseur Switch Bar tabs
  document.querySelectorAll('#prSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === statusFilter);
  });

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

  const counterEl = document.getElementById('prCounterCount');
  if (counterEl) {
    counterEl.textContent = matchedPrRows.length;
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
  const categorySelect = document.getElementById('prCategoryFilter');
  const requesterSelect = document.getElementById('prRequesterFilter');

  if (searchInput) searchInput.value = '';
  if (deptSelect) deptSelect.value = 'all';
  currentPrStatus = 'all';
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
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'Purchase Requisitions' });
@endsection
