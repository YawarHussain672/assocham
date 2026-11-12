@extends('layouts.app')

@section('title', 'Travel Requisition')

@php
  $statusMeta = [
    'pending_asg' => ['label' => 'Pending — ASG', 'badge' => 'badge-soft-warning'],
    'pending_sg'  => ['label' => 'Pending — SG', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'pushed_back' => ['label' => 'Pushed Back', 'badge' => 'badge-soft-warning'],
    'draft'       => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
  ];

  $totalTrfs = $trfs->count();
  $pendingAsg = $trfs->where('status', 'pending_asg')->count();
  $pendingSg = $trfs->where('status', 'pending_sg')->count();
  $approvedTrfs = $trfs->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Travel Requisitions (TRF)</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importTrfExcelModal">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
      </button>
      <a href="{{ route('trf.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Travel Requisition
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 shadow-sm border-0" style="border-left: 4px solid #198754 !important;">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>{{ session('status') }}</div>
    </div>
  @endif

  @if (!empty($selfOverdueTrf))
    <div class="alert border-0 shadow-sm mb-3 p-3.5 rounded-3" style="background:#FEF2F2; border-left:4px solid #DC2626 !important;">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px; height:38px; background:#FEE2E2; color:#B91C1C;">
            <i class="bi bi-shield-lock-fill fs-5"></i>
          </div>
          <div>
            <div class="fw-bold text-danger" style="font-size:14px;">New Travel Requests Blocked — Overdue Unsettled Advance</div>
            <div class="text-dark small mt-0.5" style="font-size:12.5px;">
              You have an unsettled advance
              @if ((float) $selfOverdueTrf->requested_advance > 0)
                (<strong>₹{{ number_format((float) $selfOverdueTrf->requested_advance, 2) }}</strong>)
              @endif
              from <a href="{{ route('trf.show', $selfOverdueTrf) }}" class="fw-semibold text-danger text-decoration-underline">{{ $selfOverdueTrf->trf_number }}</a>
              ({{ $selfOverdueTrf->route ?: 'Route N/A' }}, ended {{ optional($selfOverdueTrf->settlementReferenceDate())->format('d M Y') }}, {{ $selfOverdueTrf->overdueDays() }} days ago).
              Reconcile it via Reimbursement to lift this travel block.
            </div>
          </div>
        </div>
        <a href="{{ route('reimb.create', ['trf_id' => $selfOverdueTrf->id]) }}" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1.5 fw-semibold text-nowrap flex-shrink-0 px-3 py-1.5">
          <i class="bi bi-receipt-cutoff"></i>
          <span>Settle Advance Now</span>
        </a>
      </div>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger mb-3 border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
      <div class="d-flex align-items-center gap-2 fw-bold text-danger mb-1">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>Travel Requisition / Import Notice</span>
      </div>
      <div class="small text-danger-emphasis mb-2">
        {{ $errors->first('file') ?: ($errors->first('employee_id') ?: 'Validation encountered issues. Please review details below.') }}
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

  <!-- Status KPI Tab Cards (Total, Pending ASG, Pending SG, Approved) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-airplane-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalTrfs }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending ASG</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingAsg }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending SG</span>
            <div class="stat-icon-box icon-secondary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-globe"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingSg }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Approved</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $approvedTrfs }}</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Informational Cards */
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
    #trfTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #trfTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #64748b;
      vertical-align: middle;
      padding: 10px 14px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #trfTable td {
      vertical-align: middle;
      padding: 11px 14px;
      font-size: 12.5px;
      line-height: 1.4;
      border-bottom: 1px solid #f1f5f9;
    }
    .erp-filter-ctrl.form-select {
      background-position: right 0.75rem center !important;
      padding-right: 2.25rem !important;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  </style>

  <!-- Filter & Search Bar (Employee, Department, Status, Date Range, Destination) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2.5 px-3">
      <div class="row g-2 align-items-end">
        <!-- Keyword Search -->
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Search</label>
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="trfSearchInput" class="form-control bg-white border-start-0 ps-0" value="{{ request('search') }}" placeholder="TRF No, Purpose..." oninput="applyTrfFilters()">
          </div>
        </div>

        <!-- Employee Filter -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Employee</label>
          <select id="trfEmployeeFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyTrfFilters()">
            <option value="all">All Employees</option>
            @foreach ($employees ?? collect() as $emp)
              <option value="{{ $emp->id }}" {{ (string) request('employee_id') === (string) $emp->id ? 'selected' : '' }}>
                {{ $emp->name }} ({{ $emp->department ?: 'General' }})
              </option>
            @endforeach
          </select>
        </div>

        <!-- Department Filter -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Department</label>
          <select id="trfDepartmentFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyTrfFilters()">
            <option value="all">All Departments</option>
            @foreach ($departments ?? collect() as $dept)
              <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                {{ $dept }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Status Filter -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Status</label>
          <select id="trfStatusFilter" class="form-select erp-filter-ctrl bg-white" onchange="applyTrfFilters()">
            <option value="all">All Statuses</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending_asg" {{ request('status') === 'pending_asg' ? 'selected' : '' }}>Pending ASG</option>
            <option value="pending_sg" {{ request('status') === 'pending_sg' ? 'selected' : '' }}>Pending SG</option>
            <option value="pushed_back" {{ request('status') === 'pushed_back' ? 'selected' : '' }}>Pushed Back</option>
          </select>
        </div>

        <!-- Destination Filter -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Destination</label>
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-geo-alt"></i>
            </span>
            <input type="text" id="trfDestinationFilter" list="trfDestinationSuggestions" class="form-control bg-white border-start-0 ps-0" value="{{ request('destination') }}" placeholder="City or route..." oninput="applyTrfFilters()">
            <datalist id="trfDestinationSuggestions">
              @foreach ($destinations ?? collect() as $dest)
                <option value="{{ $dest }}">
              @endforeach
            </datalist>
          </div>
        </div>
      </div>

      <div class="row g-2 align-items-end mt-1">
        <!-- Date Range From -->
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Travel Date From</label>
          <input type="date" id="trfDateFrom" class="form-control erp-filter-ctrl bg-white" value="{{ request('date_from') }}" onchange="applyTrfFilters()">
        </div>

        <!-- Date Range To -->
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Travel Date To</label>
          <input type="date" id="trfDateTo" class="form-control erp-filter-ctrl bg-white" value="{{ request('date_to') }}" onchange="applyTrfFilters()">
        </div>

        <!-- Reset Button -->
        <div class="col-12 col-md-4 col-lg-6 text-end">
          <button type="button" class="btn btn-outline-secondary erp-filter-ctrl d-none px-3 ms-auto" id="clearTrfFilterBtn" onclick="resetTrfFilters()">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TRF Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-airplane text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Travel Requisitions Roster</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" id="trfCountBadge" style="font-size: 11px;">
          {{ $totalTrfs }} {{ Str::plural('Entry', $totalTrfs) }}
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="trfTable">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th style="width: 11%;" class="ps-4">TRF NO.</th>
              <th style="width: 21%;">EMPLOYEE</th>
              <th style="width: 27%;">ROUTE &amp; LOGISTICS</th>
              <th style="width: 16%;">TRAVEL DATES</th>
              <th style="width: 14%;">STATUS</th>
              <th style="width: 11%;" class="text-end pe-4">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($trfs as $trf)
              @php 
                $meta = $statusMeta[$trf->status] ?? $statusMeta['draft'];
                $empDept = $trf->department ?: 'General';
                $destText = trim(($trf->route ?? '') . ' ' . ($trf->destinations ?? ''));
                $searchStr = strtolower($trf->trf_number . ' ' . ($trf->employee?->name ?? '') . ' ' . $empDept . ' ' . $destText . ' ' . ($trf->travel_mode ?? '') . ' ' . ($trf->purpose ?? ''));
                $startIso = optional($trf->travel_start)->format('Y-m-d') ?? '';
                $endIso = optional($trf->travel_end)->format('Y-m-d') ?? $startIso;
                $logisticsBits = array_filter([
                  $trf->travel_mode ?: 'Flight',
                  $trf->accommodation ? 'Stay' : null,
                  $trf->visa_required ? 'Visa' : null,
                ]);
              @endphp
              <tr class="trf-row"
                  data-employee-id="{{ $trf->employee_id }}"
                  data-department="{{ strtolower($empDept) }}"
                  data-status="{{ $trf->status }}"
                  data-destination="{{ strtolower($destText) }}"
                  data-start="{{ $startIso }}"
                  data-end="{{ $endIso }}"
                  data-search="{{ $searchStr }}">
                <td class="ps-4 text-nowrap">
                  <a href="{{ route('trf.show', $trf) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 12.5px;">
                    {{ $trf->trf_number }}
                  </a>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $trf->employee->name }}</div>
                  <div class="text-muted" style="font-size: 11.5px;">
                    <span>{{ $empDept }}</span>
                    @if ($trf->is_international)
                      <span class="mx-1">&bull;</span><span class="text-warning-emphasis fw-medium">International</span>
                    @endif
                  </div>
                </td>
                <td>
                  <div class="text-dark fw-medium">{{ $trf->route ?: '—' }}</div>
                  <div class="text-muted" style="font-size: 11.5px;">{{ implode(' · ', $logisticsBits) }}</div>
                </td>
                <td class="text-nowrap text-muted" style="font-size: 12px;">
                  @if ($trf->travel_start)
                    {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
                  @else
                    —
                  @endif
                </td>
                <td class="text-nowrap">
                  <span class="badge {{ $meta['badge'] }} px-2 py-1" style="font-size: 11px;">{{ $meta['label'] }}</span>
                  @if ($trf->isSettlementOverdue())
                    <div class="text-danger fw-medium mt-1" style="font-size: 11px;" title="Unsettled past 30 days — blocks new TRFs">
                      <i class="bi bi-exclamation-circle me-1"></i>Overdue Advance
                    </div>
                  @endif
                </td>
                <td class="text-end pe-4 text-nowrap">
                  <div class="d-inline-flex align-items-center gap-1">
                    @if ($trf->status === 'pushed_back' && (auth()->id() === $trf->employee_id || auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD))
                      <a href="{{ route('trf.edit', $trf) }}" class="btn btn-sm btn-warning text-dark px-2 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 600;" title="Edit & Resubmit Pushed Back TRF">
                        <i class="bi bi-pencil-square"></i>
                        <span>Edit</span>
                      </a>
                    @endif
                    <a href="{{ route('trf.show', $trf) }}" class="btn btn-sm btn-outline-primary px-2 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 500;" title="View TRF Itinerary">
                      <i class="bi bi-eye"></i>
                      <span>View</span>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No travel requisitions recorded yet.</span>
                </td>
              </tr>
            @endforelse
            <tr id="emptyTrfFilterRow" class="d-none">
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary"></i>
                <span>No travel requisitions match your filter criteria.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Simple & Clean Import Excel Modal (Matching PR Page) -->
  <div class="modal fade" id="importTrfExcelModal" tabindex="-1" aria-labelledby="importTrfExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-3">
        <form method="POST" action="{{ route('trf.import-excel') }}" enctype="multipart/form-data">
          @csrf
          <div class="modal-header py-3 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-5"></i>
              <h6 class="modal-title fw-bold text-dark mb-0" id="importTrfExcelModalLabel">Import Travel Requisitions</h6>
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
                  <div class="text-muted" style="font-size: 11px;">EMPLOYEE (OPTIONAL), ROUTE, PURPOSE, TRAVEL MODE, START &amp; END DATES, ADVANCE, INTERNATIONAL, ACCOMMODATION</div>
                </div>
              </div>
              <a href="{{ route('trf.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 fw-semibold" style="font-size: 11.5px;">
                <i class="bi bi-download"></i> Template
              </a>
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold small text-dark mb-1">Select Excel File (.xlsx, .csv) <span class="text-danger">*</span></label>
              <input type="file" name="file" class="form-control" accept=".xlsx,.csv,.txt" style="font-size: 13px;">
              <div class="text-muted mt-1 small" style="font-size: 11.5px;">
                Upload your completed travel spreadsheet. Leave <strong>EMPLOYEE</strong> blank for self-travel, or enter a colleague's name/email for on-behalf booking. Domestic requests route to ASG and international trips route to Secretary General.
              </div>
            </div>

            <!-- Optional collapsible for direct text pasting -->
            <div class="text-center mt-3 pt-1 border-top">
              <a class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1" data-bs-toggle="collapse" href="#pasteTrfTextCollapse" role="button" aria-expanded="false" style="font-size: 11px;">
                <i class="bi bi-pencil-square"></i>
                <span>Or paste tabular CSV text</span>
              </a>
            </div>
            <div class="collapse mt-2" id="pasteTrfTextCollapse">
              <label class="form-label text-muted fw-semibold" style="font-size: 11px;">Paste CSV Rows:</label>
              <textarea name="csv_data" class="form-control font-monospace" rows="3" style="font-size: 11.5px;" placeholder="Delhi - Mumbai, Client Site Kickoff, 2026-07-10, 2026-07-12, 15000, no
Delhi - Dubai, Gulf Expo 2026, 2026-08-05, 2026-08-09, 45000, yes"></textarea>
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
                <h6 class="modal-title fw-bold text-dark mb-0" id="importResultModalLabel">Bulk Travel Import Results</h6>
                <div class="text-muted small" style="font-size: 12px;">Processed {{ $summary['total_rows'] }} total row(s) from uploaded spreadsheet</div>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <!-- 2 Summary KPI Cards -->
            <div class="row g-3 mb-3">
              <div class="col-12 col-sm-6">
                <div class="card h-100 border-0 rounded-3 p-3 {{ $summary['entered_count'] > 0 ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-light text-muted border' }}">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Requisitions Created</span>
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                  </div>
                  <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-2 fw-bold font-monospace text-success">{{ $summary['entered_count'] }}</span>
                    <span class="small text-muted">of {{ $summary['total_rows'] }} total rows</span>
                  </div>
                  <div class="small pt-2 border-top border-success-subtle text-muted" style="font-size: 11px;">
                    Auto-generated TRFs filed and routed for clearance.
                  </div>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="card h-100 border-0 rounded-3 p-3 {{ $summary['rejected_count'] > 0 ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' : 'bg-light text-muted border' }}">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Rows Not Entered</span>
                    <i class="bi bi-x-circle-fill fs-5 {{ $summary['rejected_count'] > 0 ? 'text-danger' : 'text-muted' }}"></i>
                  </div>
                  <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fs-2 fw-bold font-monospace {{ $summary['rejected_count'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $summary['rejected_count'] }}</span>
                    <span class="small text-muted">of {{ $summary['total_rows'] }} total rows</span>
                  </div>
                  <div class="small pt-2 border-top {{ $summary['rejected_count'] > 0 ? 'border-danger-subtle text-danger' : 'text-muted' }}" style="font-size: 11px;">
                    {{ $summary['rejected_count'] > 0 ? 'Failed validation checks detailed below.' : 'All rows parsed and created successfully!' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Table of Created TRFs -->
            @if (!empty($summary['created_trfs']))
              <div class="border rounded-3 overflow-hidden mb-3">
                <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                  <span class="small fw-bold text-dark text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="bi bi-airplane-fill text-primary me-1"></i> Generated Travel Requisitions ({{ count($summary['created_trfs']) }})
                  </span>
                  <span class="text-muted small" style="font-size: 11px;">Click TRF No. to view requisition details</span>
                </div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                  <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                      <tr style="font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">
                        <th class="ps-3" style="width: 120px;">TRF No.</th>
                        <th>Employee &amp; Dept</th>
                        <th>Route &amp; Mode</th>
                        <th>Travel Dates</th>
                        <th>Advance</th>
                        <th>Clearance Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($summary['created_trfs'] as $item)
                        <tr>
                          <td class="ps-3 font-monospace">
                            <a href="{{ route('trf.show', $item['id']) }}" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none fw-bold" style="font-size: 11.5px;">
                              {{ $item['trf_number'] }}
                            </a>
                          </td>
                          <td>
                            <div class="fw-semibold text-dark">{{ $item['employee_name'] }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $item['department'] }}</div>
                          </td>
                          <td>
                            <div class="text-dark">{{ $item['route'] }}</div>
                            <span class="badge bg-light text-secondary border" style="font-size: 10px;">{{ $item['travel_mode'] }}</span>
                            @if ($item['is_international'])
                              <span class="badge bg-primary text-white" style="font-size: 10px;">Intl</span>
                            @endif
                          </td>
                          <td class="text-nowrap small text-muted">{{ $item['travel_dates'] }}</td>
                          <td class="font-monospace small fw-semibold text-dark">{{ $item['advance'] }}</td>
                          <td>
                            <span class="badge {{ $item['is_international'] ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}" style="font-size: 11px;">
                              {{ $item['clearance'] }}
                            </span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif

            <!-- Table of Rejections and Specific Reasons (if any) -->
            @if (!empty($summary['rejections']))
              <div class="border rounded-3 overflow-hidden">
                <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                  <span class="small fw-bold text-danger text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Row Rejections ({{ count($summary['rejections']) }})
                  </span>
                  <span class="text-muted small" style="font-size: 11px;">Correct these rows in your template to re-import</span>
                </div>
                <div class="table-responsive" style="max-height: 180px; overflow-y: auto;">
                  <ul class="list-group list-group-flush small mb-0">
                    @foreach ($summary['rejections'] as $rej)
                      <li class="list-group-item text-danger py-2 px-3 d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle"></i>
                        <span>{{ is_array($rej) ? ($rej['reason'] ?? json_encode($rej)) : $rej }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              </div>
            @endif
          </div>

          <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-end">
            <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'Travel Requisition' });

window.applyTrfFilters = function() {
  const query = (document.getElementById('trfSearchInput')?.value || '').toLowerCase().trim();
  const employeeFilter = document.getElementById('trfEmployeeFilter')?.value || 'all';
  const departmentFilter = (document.getElementById('trfDepartmentFilter')?.value || 'all').toLowerCase();
  const statusFilter = document.getElementById('trfStatusFilter')?.value || 'all';
  const destinationFilter = (document.getElementById('trfDestinationFilter')?.value || '').toLowerCase().trim();
  const dateFrom = document.getElementById('trfDateFrom')?.value || '';
  const dateTo = document.getElementById('trfDateTo')?.value || '';

  const rows = document.querySelectorAll('.trf-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const rowEmpId = row.getAttribute('data-employee-id') || '';
    const rowDept = row.getAttribute('data-department') || '';
    const rowStatus = row.getAttribute('data-status') || '';
    const rowDest = row.getAttribute('data-destination') || '';
    const rowStart = row.getAttribute('data-start') || '';
    const rowEnd = row.getAttribute('data-end') || rowStart;
    const rowSearch = row.getAttribute('data-search') || '';

    const matchesEmployee = (employeeFilter === 'all' || rowEmpId === employeeFilter);
    const matchesDepartment = (departmentFilter === 'all' || rowDept === departmentFilter);
    const matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
    const matchesDestination = (!destinationFilter || rowDest.includes(destinationFilter));
    const matchesQuery = (!query || rowSearch.includes(query));

    let matchesDate = true;
    if (dateFrom) {
      matchesDate = Boolean(rowEnd && rowEnd >= dateFrom);
    }
    if (matchesDate && dateTo) {
      matchesDate = Boolean(rowStart && rowStart <= dateTo);
    }

    if (matchesEmployee && matchesDepartment && matchesStatus && matchesDestination && matchesDate && matchesQuery) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const emptyRow = document.getElementById('emptyTrfFilterRow');
  if (emptyRow) {
    if (visibleCount === 0 && rows.length > 0) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  const isFiltered = (
    employeeFilter !== 'all' ||
    departmentFilter !== 'all' ||
    statusFilter !== 'all' ||
    destinationFilter !== '' ||
    dateFrom !== '' ||
    dateTo !== '' ||
    query !== ''
  );

  const clearBtn = document.getElementById('clearTrfFilterBtn');
  if (clearBtn) {
    if (isFiltered) {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  const badge = document.getElementById('trfCountBadge');
  if (badge) {
    badge.textContent = isFiltered ? `${visibleCount} of {{ $totalTrfs }} Entries` : `{{ $totalTrfs }} Entries`;
  }
};

window.resetTrfFilters = function() {
  if (window.location.search) {
    window.location.href = "{{ route('trf') }}";
    return;
  }
  const searchInput = document.getElementById('trfSearchInput');
  const employeeFilter = document.getElementById('trfEmployeeFilter');
  const departmentFilter = document.getElementById('trfDepartmentFilter');
  const statusFilter = document.getElementById('trfStatusFilter');
  const destinationFilter = document.getElementById('trfDestinationFilter');
  const dateFrom = document.getElementById('trfDateFrom');
  const dateTo = document.getElementById('trfDateTo');

  if (searchInput) searchInput.value = '';
  if (employeeFilter) employeeFilter.value = 'all';
  if (departmentFilter) departmentFilter.value = 'all';
  if (statusFilter) statusFilter.value = 'all';
  if (destinationFilter) destinationFilter.value = '';
  if (dateFrom) dateFrom.value = '';
  if (dateTo) dateTo.value = '';
  applyTrfFilters();
};

if (window.location.search) {
  applyTrfFilters();
}

@if (session('import_summary'))
  const resultModalEl = document.getElementById('importResultModal');
  if (resultModalEl) {
    const modal = new bootstrap.Modal(resultModalEl);
    modal.show();
  }
@endif
@endsection
