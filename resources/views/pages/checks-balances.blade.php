@extends('layouts.app')

@section('title', 'Budget Master Sheet')

@php
  $activeView = $activeView ?? 'department';
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <style>
    .btn-budget-action {
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      font-size: 13px !important;
      font-weight: 600 !important;
      line-height: 1.4 !important;
      padding: 0 12px !important;
      height: 32px !important;
      border-radius: 8px !important;
      border: 1px solid #e2e8f0 !important;
      background-color: #ffffff !important;
      color: #334155 !important;
      transition: all 0.15s ease !important;
      text-decoration: none !important;
      cursor: pointer !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
      box-shadow: none !important;
    }
    .btn-budget-action i {
      font-size: 14px !important;
      color: #f49104 !important;
      transition: color 0.15s ease !important;
    }
    .btn-budget-action:hover,
    .btn-budget-action:focus {
      background-color: #fff7ed !important;
      border-color: #fdba74 !important;
      color: #b45f00 !important;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }
    .btn-budget-action:hover i,
    .btn-budget-action:focus i {
      color: #b45f00 !important;
    }
  </style>

  <!-- Page Header & Budget Master Actions -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Budget Master Sheet</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="GET" action="{{ route('checks-balances') }}" class="d-flex align-items-center gap-2">
        <select name="fy" class="form-select form-select-sm fw-semibold" style="width:auto;" onchange="this.form.submit()" aria-label="Fiscal Year">
          @foreach ($fiscalYears as $fy)
            <option value="{{ $fy }}" {{ $fy === $fiscalYear ? 'selected' : '' }}>
              {{ $fy }}{{ $fy === $currentFy ? ' (Active)' : '' }}
            </option>
          @endforeach
        </select>
      </form>

      @if ($canManageBudgets)
        <button type="button" class="btn btn-sm btn-primary fw-semibold d-inline-flex align-items-center gap-1 px-3" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add Department Budget</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1 px-3" data-bs-toggle="modal" data-bs-target="#createProjectBudgetModal">
          <i class="bi bi-plus-lg"></i>
          <span>Add Project Budget</span>
        </button>
      @else
        <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
          <i class="bi bi-eye me-1"></i>Read-Only View
        </span>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-octagon-fill"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill mt-1"></i>
      <ul class="mb-0 ps-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Status KPI Cards (Standard ERP Design) -->
  @php
    $deptCeiling = (float) $budgets->sum('allocated_amount');
    $deptUtilised = (float) $budgets->sum('utilised_amount');
    $deptHeadroom = max(0, $deptCeiling - $deptUtilised);
    $deptAlertsCount = $budgets->filter(fn ($b) => $b->isNearLimit())->count();

    $projCeiling = (float) $projectBudgets->sum('allocated_amount');
    $projUtilised = (float) $projectBudgets->sum('utilised_amount');
    $projHeadroom = max(0, $projCeiling - $projUtilised);
    $projAlertsCount = $projectBudgets->filter(fn ($p) => $p->isNearLimit())->count();

    $isProjectView = ($activeView === 'project');
  @endphp
  <div class="row g-2 mb-3" id="budgetKpiRow" style="{{ $activeView === 'items' ? 'display: none;' : '' }}">
    <!-- Ceiling / Total Limit -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label" id="kpiLabelCeiling">
              {{ $isProjectView ? 'Project Budget' : 'Total Budget Ceiling' }}
            </span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value" id="kpiValCeiling">
            ₹{{ number_format($isProjectView ? $projCeiling : $deptCeiling, 2) }}
          </div>
          <div class="stat-footer" id="kpiFootCeiling">
            <span class="text-muted">{{ $isProjectView ? $projectBudgets->count() . ' active projects' : $budgets->count() . ' department allocations' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Committed / Incurred Expense -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label" id="kpiLabelCommitted">
              {{ $isProjectView ? 'Project Expense' : 'Committed Spend' }}
            </span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value" id="kpiValCommitted">
            ₹{{ number_format($isProjectView ? $projUtilised : $deptUtilised, 2) }}
          </div>
          <div class="stat-footer" id="kpiFootCommitted">
            @php
              $currentCeil = $isProjectView ? $projCeiling : $deptCeiling;
              $currentUtil = $isProjectView ? $projUtilised : $deptUtilised;
              $currentPct = $currentCeil > 0 ? round(($currentUtil / $currentCeil) * 100, 1) : 0;
            @endphp
            <span class="text-muted"><strong class="text-dark">{{ $currentPct }}%</strong> utilised of ceiling</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Headroom / Surplus -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label" id="kpiLabelHeadroom">
              {{ $isProjectView ? 'Project Surplus' : 'Available Headroom' }}
            </span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-shield-check"></i>
            </div>
          </div>
          <div class="stat-value" id="kpiValHeadroom">
            ₹{{ number_format($isProjectView ? $projHeadroom : $deptHeadroom, 2) }}
          </div>
          <div class="stat-footer" id="kpiFootHeadroom">
            <span class="text-muted">Available uncommitted headroom</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Alerts / Critical (90%+) -->
    @php
      $currentAlerts = $isProjectView ? $projAlertsCount : $deptAlertsCount;
    @endphp
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card {{ $currentAlerts > 0 ? 'stat-danger' : 'stat-info' }} card-lift h-100" id="kpiCardAlerts">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label" id="kpiLabelAlerts">
              Alerts (90%+)
            </span>
            <div class="stat-icon-box {{ $currentAlerts > 0 ? 'icon-danger' : 'icon-info' }}" id="kpiIconAlerts">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
          </div>
          <div class="stat-value" id="kpiValAlerts">
            @if ($isProjectView)
              {{ $projAlertsCount }} {{ Str::plural('Project', $projAlertsCount) }}
            @else
              {{ $deptAlertsCount }} {{ Str::plural('Department', $deptAlertsCount) }}
            @endif
          </div>
          <div class="stat-footer" id="kpiFootAlerts">
            @if ($currentAlerts > 0)
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill fw-medium">
                <i class="bi bi-exclamation-triangle me-1"></i>Review required
              </span>
            @else
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-medium">
                <i class="bi bi-check2 me-1"></i>All within limit
              </span>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (!empty($nearLimitProjects) && $nearLimitProjects->isNotEmpty())
    <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2" style="background:#FFFBEB; border-left:4px solid #D97706 !important;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-white border text-warning" style="width:34px; height:34px;">
          <i class="bi bi-folder2-open"></i>
        </div>
        <div style="font-size:13px;">
          <div class="fw-bold text-warning-emphasis">
            90% Project Budget Alert · {{ $nearLimitProjects->count() }} {{ Str::plural('Project', $nearLimitProjects->count()) }} Near Limit
          </div>
          <div class="text-dark" style="font-size:12px;">
            @foreach ($nearLimitProjects->take(3) as $alertProj)
              <span class="me-3">
                <strong>{{ $alertProj->project_name }}</strong> ({{ $alertProj->utilisationPercent() }}% used · ₹{{ number_format($alertProj->remainingAmount(), 2) }} left)
              </span>
            @endforeach
            @if ($nearLimitProjects->count() > 3)
              <span class="text-warning-emphasis fw-semibold">+{{ $nearLimitProjects->count() - 3 }} more</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- View Switcher Pills: Department-wise vs Project-wise -->
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
    <ul class="nav nav-pills p-1 bg-white border rounded-3 shadow-sm" id="budgetTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeView === 'department' ? 'active' : '' }} fw-semibold px-3 py-1-5 d-flex align-items-center gap-2" id="dept-tab" data-bs-toggle="pill" data-bs-target="#deptView" type="button" role="tab" aria-controls="deptView" aria-selected="{{ $activeView === 'department' ? 'true' : 'false' }}" style="font-size: 13px;">
          <span>Department-wise Budget</span>
          <span class="badge {{ $activeView === 'department' ? 'bg-primary text-white' : 'bg-light text-secondary' }} rounded-pill ms-1">{{ $budgets->count() }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeView === 'project' ? 'active' : '' }} fw-semibold px-3 py-1-5 d-flex align-items-center gap-2" id="project-tab" data-bs-toggle="pill" data-bs-target="#projectView" type="button" role="tab" aria-controls="projectView" aria-selected="{{ $activeView === 'project' ? 'true' : 'false' }}" style="font-size: 13px;">
          <span>Project-wise Budget</span>
          <span class="badge {{ $activeView === 'project' ? 'bg-primary text-white' : 'bg-light text-secondary' }} rounded-pill ms-1" id="projectCountTabBadge">{{ $projectBudgets->count() }}</span>
        </button>
      </li>
    </ul>

    <div class="text-muted small">
      Viewing active allocations for <strong>{{ $fiscalYear }}</strong>
    </div>
  </div>

  <!-- Tab Content: Department View, Project View & Item Master View -->
  <div class="tab-content" id="budgetTabContent">
    <!-- Department-wise Tab Pane -->
    <div class="tab-pane fade {{ $activeView === 'department' ? 'show active' : '' }}" id="deptView" role="tabpanel" aria-labelledby="dept-tab">
      <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 border-bottom">
          <div>
            <h6 class="fw-bold mb-0 text-main">Departmental Budget Master — {{ $fiscalYear }}</h6>
          </div>
          <span class="badge badge-soft-primary">{{ $budgets->count() }} {{ Str::plural('Department', $budgets->count()) }}</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
            <thead class="table-light text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;">
              <tr>
                <th class="ps-4 py-2.5">Department</th>
                <th class="py-2.5 text-end text-nowrap">Budget Limit</th>
                <th class="py-2.5 text-end text-nowrap">Utilised</th>
                <th class="py-2.5" style="min-width: 180px;">Utilisation</th>
                <th class="py-2.5 text-end text-nowrap">Headroom</th>
                @if ($canManageBudgets)
                  <th class="pe-4 py-2.5 text-center text-nowrap" style="width: 110px;">Action</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse ($budgets as $budget)
                @php
                  $pct = $budget->utilisationPercent();
                  $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                  $pctBadge = $pct >= 90 ? 'badge-soft-danger' : ($pct >= 70 ? 'badge-soft-warning' : 'badge-soft-success');
                  $remaining = $budget->remainingAmount();
                @endphp
                <tr>
                  <td class="ps-4 py-2.5">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <span class="fw-bold text-dark" style="font-size: 13px;">{{ $budget->department }}</span>
                      @if ($budget->isNearLimit())
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px; padding: 2px 6px;">Critical (90%+)</span>
                      @endif
                    </div>
                    <div class="text-muted" style="font-size: 11px;">
                      {{ $budget->fiscal_year }} · GST {{ number_format((float) $budget->gst_rate, 0) }}%
                    </div>
                  </td>
                  <td class="py-2.5 text-end fw-bold text-dark text-nowrap font-monospace" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                    ₹{{ number_format((float) $budget->allocated_amount, 2) }}
                  </td>
                  <td class="py-2.5 text-end text-secondary text-nowrap font-monospace" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                    ₹{{ number_format((float) $budget->utilised_amount, 2) }}
                  </td>
                  <td class="py-2.5">
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px; border-radius: 99px; background-color: var(--border-color);">
                        <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($pct, 100) }}%; border-radius: 99px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="badge {{ $pctBadge }} flex-shrink-0" style="min-width: 50px; font-size: 11px; font-variant-numeric: tabular-nums;">{{ $pct }}%</span>
                    </div>
                  </td>
                  <td class="py-2.5 text-end fw-bold text-nowrap font-monospace {{ $remaining > 0 ? 'text-success' : 'text-danger' }}" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                    ₹{{ number_format($remaining, 2) }}
                  </td>
                  @if ($canManageBudgets)
                    <td class="pe-4 py-2.5 text-center text-nowrap">
                      <button
                        type="button"
                        class="btn-budget-action erp-btn-action-edit shadow-2xs"
                        data-bs-toggle="modal"
                        data-bs-target="#editBudgetModal"
                        data-budget-id="{{ $budget->id }}"
                        data-budget-department="{{ $budget->department }}"
                        data-budget-fy="{{ $budget->fiscal_year }}"
                        data-budget-allocated="{{ number_format((float) $budget->allocated_amount, 2, '.', '') }}"
                        data-budget-utilised="{{ number_format((float) $budget->utilised_amount, 2, '.', '') }}"
                        data-budget-gst="{{ number_format((float) $budget->gst_rate, 2, '.', '') }}"
                        data-update-url="{{ route('checks-balances.budgets.update', $budget) }}"
                        title="Edit Department Budget">
                        <i class="bi bi-pencil"></i>
                        <span>Edit Limit</span>
                      </button>
                      @if ($budget->can_delete)
                        <form method="POST" action="{{ route('checks-balances.budgets.destroy', $budget) }}" class="d-inline ms-1" onsubmit="return confirm('Remove unused budget limit for {{ $budget->department }} ({{ $budget->fiscal_year }})?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-xs btn-light border text-danger py-0.5 px-1.5 rounded-1" title="Remove unused entry" style="font-size: 11px; line-height: 1.3;">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      @endif
                    </td>
                  @endif
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $canManageBudgets ? 6 : 5 }}" class="p-4 text-center text-muted">
                    <i class="bi bi-wallet2 text-muted fs-3 d-block mb-1"></i>
                    <div>No department budgets configured for {{ $fiscalYear }}.</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Project-wise Tab Pane -->
    <div class="tab-pane fade {{ $activeView === 'project' ? 'show active' : '' }}" id="projectView" role="tabpanel" aria-labelledby="project-tab">
      <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        
        <!-- Header & Filter Toolbar -->
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2.5">
              <h6 class="fw-bold mb-0 text-dark" style="font-size: 14.5px;">Event &amp; Project Budget Master</h6>
              <span class="badge bg-light text-secondary border font-monospace px-2 py-0.5" id="projectCountBadge" style="font-size: 11px; font-weight: 600;">
                {{ $projectBudgets->count() }} Projects
              </span>
              <span class="text-muted" style="font-size: 12px;">· {{ $fiscalYear }}</span>
            </div>

            <!-- Filters & Actions Toolbar -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-white text-muted border-end-0 py-1 pe-1">
                  <i class="bi bi-search text-muted" style="font-size: 11px;"></i>
                </span>
                <input type="text" id="projectSearchInput" class="form-control border-start-0 py-1 ps-1 bg-white" placeholder="Search event, council, lead..." style="font-size: 12px;">
              </div>

              <select id="projectDeptFilter" class="form-select form-select-sm bg-white fw-medium text-dark" style="width: auto; min-width: 160px; font-size: 12px; cursor: pointer;">
                <option value="">All Councils / Depts</option>
                @foreach ($projectDepartments as $dept)
                  <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <div class="table-responsive" style="max-width: 100%; overflow-x: auto;">
          <table class="table table-hover align-middle mb-0" id="projectBudgetTable" style="font-size: 12.5px; border-collapse: separate; border-spacing: 0;">
            <thead style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
              <tr class="text-secondary" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
                <th class="ps-3 py-3 text-center" style="width: 44px; color: #64748b; font-weight: 600;">#</th>
                <th class="py-3" style="min-width: 230px; color: #64748b; font-weight: 600;">Name of Events / Projects</th>
                <th class="py-3" style="min-width: 140px; color: #64748b; font-weight: 600;">Council</th>
                <th class="py-3" style="width: 95px; color: #64748b; font-weight: 600;">Department</th>
                <th class="py-3" style="min-width: 130px; color: #64748b; font-weight: 600;">HOD / Lead</th>
                <th class="py-3 text-center" style="width: 95px; color: #64748b; font-weight: 600;">Category</th>
                <th class="py-3" style="min-width: 130px; color: #64748b; font-weight: 600;">Location &amp; Month</th>
                <th class="py-3 text-end text-nowrap" style="min-width: 120px; color: #64748b; font-weight: 600;">Income (Budget)</th>
                <th class="py-3 text-end text-nowrap" style="min-width: 120px; color: #64748b; font-weight: 600;">Expense (Utilised)</th>
                <th class="py-3 text-end text-nowrap" style="min-width: 135px; color: #64748b; font-weight: 600;">Surplus (Headroom)</th>
                @if ($canManageBudgets)
                  <th class="pe-3 py-3 text-center text-nowrap" style="width: 90px; color: #64748b; font-weight: 600;">Action</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse ($projectBudgets as $pBudget)
                @php
                  $pPct = $pBudget->utilisationPercent();
                  $pRemaining = $pBudget->remainingAmount();
                  $catKey = strtolower(trim($pBudget->category ?? ''));
                  [$catBg, $catColor, $catBorder] = match($catKey) {
                    'flagship' => ['#f5f3ff', '#6d28d9', '#ddd6fe'],
                    'major' => ['#eff6ff', '#1d4ed8', '#bfdbfe'],
                    'minor' => ['#f8fafc', '#475569', '#e2e8f0'],
                    'membership' => ['#ecfdf5', '#047857', '#a7f3d0'],
                    default => ['#f8fafc', '#475569', '#e2e8f0'],
                  };
                  $barColor = $pPct >= 90 ? 'bg-danger' : ($pPct >= 70 ? 'bg-warning' : 'bg-success');
                @endphp
                <tr style="border-bottom: 1px solid #f1f5f9;" data-department="{{ $pBudget->department }}" data-search="{{ strtolower($pBudget->project_name . ' ' . $pBudget->department . ' ' . $pBudget->council . ' ' . $pBudget->category . ' ' . $pBudget->location . ' ' . $pBudget->hod) }}">
                  <!-- S.No. -->
                  <td class="ps-3 py-2.5 text-center text-muted font-monospace" style="font-size: 11.5px;">
                    {{ $loop->iteration }}
                  </td>

                  <!-- Name of Events / Projects -->
                  <td class="py-2.5">
                    <div class="fw-bold text-dark" style="font-size: 13px; line-height: 1.35;">{{ $pBudget->project_name }}</div>
                    <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                      @if ($pBudget->isNearLimit())
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 9.5px; padding: 1px 5px; font-weight: 600;">Critical (90%+)</span>
                      @endif
                      @php
                        $linkedPrCount = $pBudget->purchaseRequisitions->count();
                      @endphp
                      @if ($linkedPrCount > 0)
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1.5 rounded-1 border d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#projectSpendModal-{{ $pBudget->id }}" title="Inspect Real PRs & Spend Breakdown" style="font-size: 10px; line-height: 1.4;">
                          <i class="bi bi-receipt"></i>
                          <span>{{ $linkedPrCount }} PR{{ $linkedPrCount > 1 ? 's' : '' }} &bull; View Breakdown</span>
                        </button>
                      @else
                        <span class="badge bg-light text-muted border" style="font-size: 9.5px; padding: 1px 5px;">0 PRs &bull; ₹0 Spend</span>
                      @endif
                    </div>
                  </td>

                  <!-- Council -->
                  <td class="py-2.5">
                    <span class="text-secondary" style="font-size: 12.5px;">
                      {{ $pBudget->council ?: '—' }}
                    </span>
                  </td>

                  <!-- Department -->
                  <td class="py-2.5">
                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 10.5px; font-weight: 500;">
                      {{ $pBudget->department }}
                    </span>
                  </td>

                  <!-- HOD / Lead -->
                  <td class="py-2.5">
                    <div class="text-dark fw-medium" style="font-size: 12.5px;">
                      {{ $pBudget->hod ?: '—' }}
                    </div>
                    @if ($pBudget->cluster_head && $pBudget->cluster_head !== $pBudget->hod)
                      <div class="text-muted" style="font-size: 10.5px;">
                        Cluster: {{ $pBudget->cluster_head }}
                      </div>
                    @endif
                  </td>

                  <!-- Category -->
                  <td class="py-2.5 text-center">
                    <span class="badge rounded-pill fw-semibold px-2 py-0.5" style="font-size: 10px; line-height: 1.2; background-color: {{ $catBg }} !important; color: {{ $catColor }} !important; border: 1px solid {{ $catBorder }} !important;">
                      {{ $pBudget->category ?: 'General' }}
                    </span>
                  </td>

                  <!-- Location & Month -->
                  <td class="py-2.5">
                    <div class="text-nowrap" style="font-size: 12px;">
                      <span class="text-dark fw-medium">{{ $pBudget->location ?: 'Delhi' }}</span>
                      <span class="text-muted"> · {{ $pBudget->event_month ?: '—' }}</span>
                    </div>
                  </td>

                  <!-- Income (Budget Limit) -->
                  <td class="py-2.5 text-end fw-bold text-dark text-nowrap font-monospace" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                    ₹{{ number_format((float) $pBudget->allocated_amount, 2) }}
                  </td>

                  <!-- Expense (Utilised) -->
                  <td class="py-2.5 text-end text-secondary text-nowrap font-monospace" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                    ₹{{ number_format((float) $pBudget->utilised_amount, 2) }}
                  </td>

                  <!-- Surplus (Headroom) -->
                  <td class="py-2.5 text-end text-nowrap">
                    <div class="fw-bold font-monospace {{ $pRemaining > 0 ? 'text-success' : 'text-danger' }}" style="font-size: 12.5px; font-variant-numeric: tabular-nums;">
                      ₹{{ number_format($pRemaining, 2) }}
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-1.5 mt-0.5">
                      <div class="progress" style="height: 4px; width: 44px; border-radius: 99px; background: #e2e8f0;">
                        <div class="progress-bar {{ $barColor }}" style="width: {{ min($pPct, 100) }}%; border-radius: 99px;"></div>
                      </div>
                      <span class="text-muted font-monospace" style="font-size: 10.5px;">{{ $pPct }}%</span>
                    </div>
                  </td>

                  <!-- Action -->
                  @if ($canManageBudgets)
                    <td class="pe-3 py-2.5 text-center text-nowrap">
                      <div class="d-inline-flex align-items-center gap-1">
                        <button
                          type="button"
                          class="btn-budget-action erp-btn-action-edit shadow-2xs"
                          data-bs-toggle="modal"
                          data-bs-target="#editProjectBudgetModal"
                          data-project-id="{{ $pBudget->id }}"
                          data-project-name="{{ $pBudget->project_name }}"
                          data-project-department="{{ $pBudget->department }}"
                          data-project-council="{{ $pBudget->council }}"
                          data-project-cluster-head="{{ $pBudget->cluster_head }}"
                          data-project-hod="{{ $pBudget->hod }}"
                          data-project-category="{{ $pBudget->category }}"
                          data-project-location="{{ $pBudget->location }}"
                          data-project-event-month="{{ $pBudget->event_month }}"
                          data-project-fy="{{ $pBudget->fiscal_year }}"
                          data-project-allocated="{{ number_format((float) $pBudget->allocated_amount, 2, '.', '') }}"
                          data-project-utilised="{{ number_format((float) $pBudget->utilised_amount, 2, '.', '') }}"
                          data-update-url="{{ route('checks-balances.project-budgets.update', $pBudget) }}"
                          title="Edit Project Budget">
                          <i class="bi bi-pencil"></i>
                          <span>Edit</span>
                        </button>
                        @if ((float) $pBudget->utilised_amount <= 0)
                          <form method="POST" action="{{ route('checks-balances.project-budgets.destroy', $pBudget) }}" class="d-inline" onsubmit="return confirm('Remove project budget limit for {{ $pBudget->project_name }} ({{ $pBudget->fiscal_year }})?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-light border text-danger py-0.5 px-1.5 rounded-1" title="Remove project budget" style="font-size: 11px; line-height: 1.3;">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        @endif
                      </div>
                    </td>
                  @endif
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $canManageBudgets ? 11 : 10 }}" class="p-4 text-center text-muted">
                    <i class="bi bi-folder2 text-muted fs-3 d-block mb-1"></i>
                    <div>No project budgets configured for {{ $fiscalYear }}.</div>
                  </td>
                </tr>
              @endforelse
            </tbody>

            @if ($projectBudgets->isNotEmpty())
              @php
                $totAlloc = (float) $projectBudgets->sum('allocated_amount');
                $totUtil = (float) $projectBudgets->sum('utilised_amount');
                $totSurplus = $totAlloc - $totUtil;
                $totPct = $totAlloc > 0 ? round(($totUtil / $totAlloc) * 100, 1) : 0;
              @endphp
              <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0; font-size: 12.5px;">
                <tr>
                  <td colspan="7" class="ps-3 py-3 text-uppercase text-secondary font-monospace" style="font-size: 11px; letter-spacing: 0.5px; font-weight: 600;">
                    Total Budget Summary ({{ $projectBudgets->count() }} Projects)
                  </td>
                  <td class="py-3 text-end text-nowrap font-monospace text-dark fw-bold" style="font-size: 12.5px;">
                    ₹{{ number_format($totAlloc, 2) }}
                  </td>
                  <td class="py-3 text-end text-nowrap font-monospace text-secondary fw-bold" style="font-size: 12.5px;">
                    ₹{{ number_format($totUtil, 2) }}
                  </td>
                  <td class="py-3 text-end text-nowrap font-monospace {{ $totSurplus >= 0 ? 'text-success' : 'text-danger' }} fw-bold" style="font-size: 12.5px;">
                    <div>₹{{ number_format($totSurplus, 2) }}</div>
                    <div class="small fw-normal text-muted" style="font-size: 10px;">{{ $totPct }}% total utilised</div>
                  </td>
                  @if ($canManageBudgets)
                    <td class="pe-3"></td>
                  @endif
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Project Spend Breakdown Modals -->
  @foreach ($projectBudgets as $pBudget)
    @php
      $pRemaining = $pBudget->remainingAmount();
      $pPct = $pBudget->utilisationPercent();
    @endphp
    <div class="modal fade" id="projectSpendModal-{{ $pBudget->id }}" tabindex="-1" aria-labelledby="projectSpendModalLabel-{{ $pBudget->id }}" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-bottom py-3 px-4 bg-white">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary text-white" style="font-size: 10px;">{{ $pBudget->category ?: 'General' }}</span>
                <span class="badge bg-light text-secondary border" style="font-size: 10px;">{{ $pBudget->fiscal_year }}</span>
                <span class="badge bg-light text-secondary border" style="font-size: 10px;">{{ $pBudget->department }}</span>
              </div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="projectSpendModalLabel-{{ $pBudget->id }}">
                {{ $pBudget->project_name }}
              </h5>
              <div class="text-muted small mt-1" style="font-size: 12px;">
                Council: <strong>{{ $pBudget->council ?: '—' }}</strong> &bull; Lead: <strong>{{ $pBudget->hod ?: '—' }}</strong> &bull; Location: <strong>{{ $pBudget->location }} ({{ $pBudget->event_month }})</strong>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 bg-white">
            <!-- Financial Overview Cards -->
            <div class="row g-2 mb-4">
              <div class="col-4">
                <div class="p-3 rounded-3 bg-light border text-center">
                  <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Allocated Budget</span>
                  <span class="fw-bold fs-6 text-dark font-monospace">₹{{ number_format((float) $pBudget->allocated_amount, 2) }}</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3 rounded-3 bg-light border text-center">
                  <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Committed Spend</span>
                  <span class="fw-bold fs-6 text-primary font-monospace">₹{{ number_format((float) $pBudget->utilised_amount, 2) }}</span>
                  <div class="small text-muted mt-0.5" style="font-size: 11px;">{{ $pPct }}% utilised</div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3 rounded-3 bg-light border text-center">
                  <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Available Headroom</span>
                  <span class="fw-bold fs-6 {{ $pRemaining > 0 ? 'text-success' : 'text-danger' }} font-monospace">₹{{ number_format($pRemaining, 2) }}</span>
                </div>
              </div>
            </div>

            <h6 class="fw-bold text-dark mb-2.5 d-flex align-items-center justify-content-between" style="font-size: 13px;">
              <span>Linked Purchase Requisitions &amp; Commitments</span>
              <span class="badge bg-secondary-subtle text-secondary">{{ $pBudget->purchaseRequisitions->count() }} Linked Record(s)</span>
            </h6>

            @if ($pBudget->purchaseRequisitions->isEmpty())
              <div class="p-4 text-center rounded-3 border bg-light text-muted small">
                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                <div>No Purchase Requisitions have been committed against this project yet.</div>
                <div class="mt-1 text-secondary font-monospace">Available Headroom: ₹{{ number_format($pRemaining, 2) }} (100% Buffer)</div>
              </div>
            @else
              <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                  <thead class="table-light text-secondary text-uppercase" style="font-size: 10.5px;">
                    <tr>
                      <th class="ps-3 py-2">PR Number</th>
                      <th class="py-2">Requisition Title / Line Items</th>
                      <th class="py-2">Stage / Status</th>
                      <th class="py-2 text-end">Committed (₹)</th>
                      <th class="pe-3 py-2 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($pBudget->purchaseRequisitions as $prItem)
                      @php
                        $prCost = $prItem->status === 'rejected' ? 0 : $prItem->estimatedCostInclGst();
                      @endphp
                      <tr>
                        <td class="ps-3 py-2 font-monospace fw-semibold">
                          <a href="{{ route('requisition.show', $prItem) }}" class="text-primary text-decoration-none">
                            {{ $prItem->pr_number }}
                          </a>
                        </td>
                        <td class="py-2">
                          <div class="text-dark fw-medium">{{ $prItem->title ?: $prItem->event_name }}</div>
                          <div class="text-muted" style="font-size: 10.5px;">
                            {{ $prItem->items->count() }} item(s) &bull; {{ $prItem->created_at->format('d-M-Y') }}
                          </div>
                        </td>
                        <td class="py-2">
                          <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                            {{ ucfirst(str_replace('_', ' ', $prItem->status)) }}
                          </span>
                        </td>
                        <td class="py-2 text-end font-monospace fw-bold {{ $prItem->status === 'rejected' ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                          ₹{{ number_format($prCost, 2) }}
                        </td>
                        <td class="pe-3 py-2 text-center">
                          <a href="{{ route('requisition.show', $prItem) }}" class="btn btn-xs btn-outline-primary py-0.5 px-2" style="font-size: 11px;">
                            View PR
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
          <div class="modal-footer py-2 px-4 border-top bg-light">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

@if ($canManageBudgets)
  <!-- Create Department Budget Modal -->
  <div class="modal fade" id="createBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <form method="POST" action="{{ route('checks-balances.budgets.store') }}">
          @csrf
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0">Add Department Budget Limit</h6>
              <div class="text-muted" style="font-size: 12px;">Configure a new department ceiling in Budget Master.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-12 col-sm-8">
                <label class="form-label fw-semibold text-dark mb-1" for="createDepartmentInput">
                  Department Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="department" id="createDepartmentInput" class="form-control @error('department') is-invalid @enderror" value="{{ session('budget_modal') === 'create' ? old('department') : '' }}" placeholder="e.g. Legal & Compliance" required maxlength="100">
              </div>

              <div class="col-12 col-sm-4">
                <label class="form-label fw-semibold text-dark mb-1" for="createFyInput">
                  Fiscal Year <span class="text-danger">*</span>
                </label>
                <input type="text" name="fiscal_year" id="createFyInput" class="form-control @error('fiscal_year') is-invalid @enderror" value="{{ session('budget_modal') === 'create' ? old('fiscal_year', $fiscalYear) : $fiscalYear }}" placeholder="FY26-27" pattern="^FY\d{2}-\d{2}$" required>
              </div>

              <div class="col-12 col-sm-8">
                <label class="form-label fw-semibold text-dark mb-1" for="createAllocatedInput">
                  Annual Budget Limit (₹) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="allocated_amount" id="createAllocatedInput" class="form-control @error('allocated_amount') is-invalid @enderror" value="{{ session('budget_modal') === 'create' ? old('allocated_amount') : '' }}" step="0.01" min="0" placeholder="0.00" required>
                </div>
              </div>

              <div class="col-12 col-sm-4">
                <label class="form-label fw-semibold text-dark mb-1" for="createGstInput">
                  GST Rate (%)
                </label>
                <input type="number" name="gst_rate" id="createGstInput" class="form-control @error('gst_rate') is-invalid @enderror" value="{{ session('budget_modal') === 'create' ? old('gst_rate', '18.00') : '18.00' }}" step="0.01" min="0" max="100">
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top px-4 py-3">
            <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-semibold px-4">Save Budget Limit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Department Budget Limit Modal -->
  <div class="modal fade" id="editBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        @php
          $editingBudget = session('edit_budget_id') ? $budgets->firstWhere('id', (int) session('edit_budget_id')) : null;
        @endphp
        <form method="POST" id="editBudgetForm" action="{{ $editingBudget ? route('checks-balances.budgets.update', $editingBudget) : '#' }}">
          @csrf
          @method('PUT')
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0">Edit Department Budget Limit</h6>
              <div class="text-muted" id="editBudgetSubtitle" style="font-size: 12px;">
                {{ $editingBudget ? $editingBudget->department.' · '.$editingBudget->fiscal_year : 'Adjust annual ceiling and GST configuration.' }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <div class="rounded-2 bg-light border px-3 py-2 mb-3 d-flex justify-content-between align-items-center" style="font-size: 13px;">
              <span class="text-muted">Already Utilised Spend</span>
              <strong class="text-dark" id="editBudgetUtilisedDisplay">
                ₹{{ $editingBudget ? number_format((float) $editingBudget->utilised_amount, 2) : '0.00' }}
              </strong>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-sm-8">
                <label class="form-label fw-semibold text-dark mb-1" for="editAllocatedInput">
                  New Budget Limit (₹) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="allocated_amount" id="editAllocatedInput" class="form-control @error('allocated_amount') is-invalid @enderror" value="{{ session('budget_modal') === 'edit' ? old('allocated_amount') : ($editingBudget ? number_format((float) $editingBudget->allocated_amount, 2, '.', '') : '') }}" step="0.01" min="0" required>
                </div>
              </div>

              <div class="col-12 col-sm-4">
                <label class="form-label fw-semibold text-dark mb-1" for="editGstInput">
                  GST Rate (%)
                </label>
                <input type="number" name="gst_rate" id="editGstInput" class="form-control @error('gst_rate') is-invalid @enderror" value="{{ session('budget_modal') === 'edit' ? old('gst_rate') : ($editingBudget ? number_format((float) $editingBudget->gst_rate, 2, '.', '') : '18.00') }}" step="0.01" min="0" max="100">
              </div>
            </div>

            <div id="editBudgetPreview" class="rounded-2 px-3 py-2 small" style="background:#F8FAFC; border-left:3px solid #64748B;">
              Enter a budget limit to preview remaining headroom.
            </div>
          </div>

          <div class="modal-footer bg-light border-top px-4 py-3">
            <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-semibold px-4">Update Limit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Create Project Budget Modal -->
  <div class="modal fade" id="createProjectBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <form method="POST" action="{{ route('checks-balances.project-budgets.store') }}">
          @csrf
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0">Add Project / Event Budget Limit</h6>
              <div class="text-muted" style="font-size: 12px;">Configure a new event, council, or project initiative in Budget Master.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectNameInput">
                  Name of Event / Project <span class="text-danger">*</span>
                </label>
                <input type="text" name="project_name" id="createProjectNameInput" class="form-control" placeholder="e.g. National Conference on Green Mobility" required maxlength="150">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectCouncilInput">
                  Council
                </label>
                <input type="text" name="council" id="createProjectCouncilInput" class="form-control" placeholder="e.g. Green Mobility / Auto & Auto Ancillaries">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectDeptInput">
                  Lead Department <span class="text-danger">*</span>
                </label>
                <select name="department" id="createProjectDeptInput" class="form-select" required>
                  <option value="" disabled selected>Select Department...</option>
                  @foreach ($projectDepartments as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectClusterHeadInput">
                  Cluster Head
                </label>
                <input type="text" name="cluster_head" id="createProjectClusterHeadInput" class="form-control" placeholder="e.g. Rajesh Sharma">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectHodInput">
                  Department HOD
                </label>
                <input type="text" name="hod" id="createProjectHodInput" class="form-control" placeholder="e.g. Rajesh Verma">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectCategoryInput">
                  Category
                </label>
                <select name="category" id="createProjectCategoryInput" class="form-select">
                  <option value="Flagship">Flagship</option>
                  <option value="Major" selected>Major</option>
                  <option value="Minor">Minor</option>
                  <option value="Membership">Membership</option>
                  <option value="General">General</option>
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectLocationInput">
                  Location
                </label>
                <input type="text" name="location" id="createProjectLocationInput" class="form-control" value="Delhi" placeholder="e.g. Delhi, Mumbai">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectEventMonthInput">
                  Month / Schedule
                </label>
                <input type="text" name="event_month" id="createProjectEventMonthInput" class="form-control" placeholder="e.g. Aug 2026">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectFyInput">
                  Fiscal Year <span class="text-danger">*</span>
                </label>
                <input type="text" name="fiscal_year" id="createProjectFyInput" class="form-control" value="{{ $fiscalYear }}" placeholder="FY26-27" pattern="^FY\d{2}-\d{2}$" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="createProjectAllocatedInput">
                  Income / Budget Limit (₹) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="allocated_amount" id="createProjectAllocatedInput" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top px-4 py-3">
            <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-semibold px-4">Save Project Budget</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Project Budget Limit Modal -->
  <div class="modal fade" id="editProjectBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <form method="POST" id="editProjectBudgetForm" action="#">
          @csrf
          @method('PUT')
          <div class="modal-header bg-white border-bottom px-4 py-3">
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0">Edit Project Budget Limit &amp; Details</h6>
              <div class="text-muted" id="editProjectBudgetSubtitle" style="font-size: 12px;">
                Adjust project financial ceiling and schedule metadata.
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body p-4">
            <div class="rounded-2 bg-light border px-3 py-2 mb-3 d-flex justify-content-between align-items-center" style="font-size: 13px;">
              <span class="text-muted">Already Utilised Spend (Expense)</span>
              <strong class="text-dark font-monospace" id="editProjectUtilisedDisplay">₹0.00</strong>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectAllocatedInput">
                  New Income / Budget Limit (₹) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted">₹</span>
                  <input type="number" name="allocated_amount" id="editProjectAllocatedInput" class="form-control font-monospace fw-bold" step="0.01" min="0" required>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectCouncilInput">
                  Council
                </label>
                <input type="text" name="council" id="editProjectCouncilInput" class="form-control" placeholder="e.g. Green Mobility">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectClusterHeadInput">
                  Cluster Head
                </label>
                <input type="text" name="cluster_head" id="editProjectClusterHeadInput" class="form-control" placeholder="e.g. Rajesh Sharma">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectHodInput">
                  Department HOD
                </label>
                <input type="text" name="hod" id="editProjectHodInput" class="form-control" placeholder="e.g. Rajesh Verma">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectCategoryInput">
                  Category
                </label>
                <select name="category" id="editProjectCategoryInput" class="form-select">
                  <option value="Flagship">Flagship</option>
                  <option value="Major">Major</option>
                  <option value="Minor">Minor</option>
                  <option value="Membership">Membership</option>
                  <option value="General">General</option>
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectLocationInput">
                  Location
                </label>
                <input type="text" name="location" id="editProjectLocationInput" class="form-control" placeholder="e.g. Delhi">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark mb-1" for="editProjectEventMonthInput">
                  Month / Schedule
                </label>
                <input type="text" name="event_month" id="editProjectEventMonthInput" class="form-control" placeholder="e.g. Aug 2026">
              </div>
            </div>

            <div id="editProjectPreview" class="rounded-2 px-3 py-2 small" style="background:#F8FAFC; border-left:3px solid #64748B;">
              Enter a project budget limit to preview headroom.
            </div>
          </div>

          <div class="modal-footer bg-light border-top px-4 py-3">
            <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-semibold px-4">Update Project Details</button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endif
@endsection

@section('init')
Shell.init({ activeNav: 'checks-balances', crumb: 'Governance', title: 'Budget Master Sheet' });

// Project Department & Live Search Filtering
(function () {
  const filterSelect = document.getElementById('projectDeptFilter');
  const searchInput = document.getElementById('projectSearchInput');
  const countBadge = document.getElementById('projectCountBadge');
  const tabBadge = document.getElementById('projectCountTabBadge');

  function applyProjectFilters() {
    const selectedDept = (filterSelect?.value || '').toLowerCase().trim();
    const query = (searchInput?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#projectBudgetTable tbody tr[data-department]');
    let visibleCount = 0;

    rows.forEach(row => {
      const rowDept = (row.getAttribute('data-department') || '').toLowerCase().trim();
      const rowSearch = (row.getAttribute('data-search') || '').toLowerCase().trim();
      const matchesDept = !selectedDept || rowDept === selectedDept;
      const matchesQuery = !query || rowSearch.includes(query);

      if (matchesDept && matchesQuery) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (countBadge) {
      countBadge.textContent = visibleCount + (visibleCount === 1 ? ' Project' : ' Projects');
    }
  }

  if (filterSelect) filterSelect.addEventListener('change', applyProjectFilters);
  if (searchInput) searchInput.addEventListener('input', applyProjectFilters);

  // Live KPI Card Updates when toggling Department / Project tabs
  const deptTabBtn = document.getElementById('dept-tab');
  const projectTabBtn = document.getElementById('project-tab');

  const deptStats = {
    ceilingLabel: 'Total Budget Ceiling',
    ceilingVal: '₹{{ number_format($deptCeiling, 2) }}',
    ceilingFoot: '{{ $budgets->count() }} department allocations',
    committedLabel: 'Committed Spend',
    committedVal: '₹{{ number_format($deptUtilised, 2) }}',
    committedFoot: '{{ $deptCeiling > 0 ? round(($deptUtilised / $deptCeiling) * 100, 1) : 0 }}% utilised of ceiling',
    headroomLabel: 'Available Headroom',
    headroomVal: '₹{{ number_format($deptHeadroom, 2) }}',
    headroomFoot: 'Available uncommitted headroom',
    alertsVal: '{{ $deptAlertsCount }} {{ Str::plural("Department", $deptAlertsCount) }}',
    isAlert: {{ $deptAlertsCount > 0 ? 'true' : 'false' }}
  };

  const projStats = {
    ceilingLabel: 'Project Budget',
    ceilingVal: '₹{{ number_format($projCeiling, 2) }}',
    ceilingFoot: '{{ $projectBudgets->count() }} active projects',
    committedLabel: 'Project Expense',
    committedVal: '₹{{ number_format($projUtilised, 2) }}',
    committedFoot: '{{ $projCeiling > 0 ? round(($projUtilised / $projCeiling) * 100, 1) : 0 }}% utilised of ceiling',
    headroomLabel: 'Project Surplus',
    headroomVal: '₹{{ number_format($projHeadroom, 2) }}',
    headroomFoot: 'Available uncommitted headroom',
    alertsVal: '{{ $projAlertsCount }} {{ Str::plural("Project", $projAlertsCount) }}',
    isAlert: {{ $projAlertsCount > 0 ? 'true' : 'false' }}
  };

  function updateKpis(stats) {
    const elCeilingLbl = document.getElementById('kpiLabelCeiling');
    const elCeilingVal = document.getElementById('kpiValCeiling');
    const elCeilingFoot = document.getElementById('kpiFootCeiling');

    const elCommittedLbl = document.getElementById('kpiLabelCommitted');
    const elCommittedVal = document.getElementById('kpiValCommitted');
    const elCommittedFoot = document.getElementById('kpiFootCommitted');

    const elHeadroomLbl = document.getElementById('kpiLabelHeadroom');
    const elHeadroomVal = document.getElementById('kpiValHeadroom');
    const elHeadroomFoot = document.getElementById('kpiFootHeadroom');

    const elAlertsVal = document.getElementById('kpiValAlerts');
    const elAlertsFoot = document.getElementById('kpiFootAlerts');
    const elCardAlerts = document.getElementById('kpiCardAlerts');
    const elIconAlerts = document.getElementById('kpiIconAlerts');

    if (elCeilingLbl) elCeilingLbl.textContent = stats.ceilingLabel;
    if (elCeilingVal) elCeilingVal.textContent = stats.ceilingVal;
    if (elCeilingFoot) elCeilingFoot.innerHTML = '<span class="text-muted">' + stats.ceilingFoot + '</span>';

    if (elCommittedLbl) elCommittedLbl.textContent = stats.committedLabel;
    if (elCommittedVal) elCommittedVal.textContent = stats.committedVal;
    if (elCommittedFoot) elCommittedFoot.innerHTML = '<span class="text-muted"><strong class="text-dark">' + stats.committedFoot.split(' ')[0] + '</strong> ' + stats.committedFoot.split(' ').slice(1).join(' ') + '</span>';

    if (elHeadroomLbl) elHeadroomLbl.textContent = stats.headroomLabel;
    if (elHeadroomVal) elHeadroomVal.textContent = stats.headroomVal;
    if (elHeadroomFoot) elHeadroomFoot.innerHTML = '<span class="text-muted">' + stats.headroomFoot + '</span>';

    if (elAlertsVal) elAlertsVal.textContent = stats.alertsVal;

    if (elAlertsFoot) {
      if (stats.isAlert) {
        elAlertsFoot.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill fw-medium"><i class="bi bi-exclamation-triangle me-1"></i>Review required</span>';
      } else {
        elAlertsFoot.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-medium"><i class="bi bi-check2 me-1"></i>All within limit</span>';
      }
    }

    if (elCardAlerts) {
      if (stats.isAlert) {
        elCardAlerts.classList.remove('stat-info');
        elCardAlerts.classList.add('stat-danger');
      } else {
        elCardAlerts.classList.remove('stat-danger');
        elCardAlerts.classList.add('stat-info');
      }
    }

    if (elIconAlerts) {
      if (stats.isAlert) {
        elIconAlerts.classList.remove('icon-info');
        elIconAlerts.classList.add('icon-danger');
      } else {
        elIconAlerts.classList.remove('icon-danger');
        elIconAlerts.classList.add('icon-info');
      }
    }
  }

  const budgetKpiRow = document.getElementById('budgetKpiRow');

  if (deptTabBtn) {
    deptTabBtn.addEventListener('shown.bs.tab', () => {
      if (budgetKpiRow) budgetKpiRow.style.display = '';
      updateKpis(deptStats);
    });
  }
  if (projectTabBtn) {
    projectTabBtn.addEventListener('shown.bs.tab', () => {
      if (budgetKpiRow) budgetKpiRow.style.display = '';
      updateKpis(projStats);
    });
  }

  // URL Hash or query view support
  const urlParams = new URLSearchParams(window.location.search);
  const requestedView = urlParams.get('view') || (window.location.hash ? window.location.hash.replace('#', '') : null);
  if (requestedView === 'project') {
    if (projectTabBtn && window.bootstrap) {
      new bootstrap.Tab(projectTabBtn).show();
      if (budgetKpiRow) budgetKpiRow.style.display = '';
      updateKpis(projStats);
    }
  } else if (requestedView === 'items') {
    window.location.href = "{{ route('item-catalogs.index') }}";
  }

})();

@if ($canManageBudgets)
(function () {
  // Department Budget Modal handling
  const editModalEl = document.getElementById('editBudgetModal');
  const createModalEl = document.getElementById('createBudgetModal');
  const editForm = document.getElementById('editBudgetForm');
  const editSubtitle = document.getElementById('editBudgetSubtitle');
  const editUtilisedDisplay = document.getElementById('editBudgetUtilisedDisplay');
  const editAllocatedInput = document.getElementById('editAllocatedInput');
  const editGstInput = document.getElementById('editGstInput');
  const editPreview = document.getElementById('editBudgetPreview');

  let activeUtilised = {{ isset($editingBudget) && $editingBudget ? (float) $editingBudget->utilised_amount : 0 }};

  function formatInr(amount) {
    return '₹' + Number(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateEditPreview() {
    if (!editPreview || !editAllocatedInput) return;
    const limit = parseFloat(editAllocatedInput.value || '0');
    if (Number.isNaN(limit)) return;

    if (limit < activeUtilised) {
      editPreview.style.background = '#FEF2F2';
      editPreview.style.borderLeft = '3px solid #DC2626';
      editPreview.style.color = '#991B1B';
      editPreview.textContent = 'Limit cannot be below currently utilised spend (' + formatInr(activeUtilised) + ').';
      return;
    }

    const headroom = Math.max(0, limit - activeUtilised);
    const pct = limit > 0 ? ((activeUtilised / limit) * 100).toFixed(1) : '0.0';
    const isCritical = parseFloat(pct) >= 90;

    editPreview.style.background = isCritical ? '#FFFBEB' : '#ECFDF5';
    editPreview.style.borderLeft = '3px solid ' + (isCritical ? '#D97706' : '#059669');
    editPreview.style.color = isCritical ? '#92400E' : '#065F46';
    editPreview.textContent = 'Resulting headroom: ' + formatInr(headroom) + ' (' + pct + '% utilised)';
  }

  document.querySelectorAll('[data-budget-id]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!editForm) return;
      editForm.action = btn.dataset.updateUrl;
      editSubtitle.textContent = btn.dataset.budgetDepartment + ' · ' + btn.dataset.budgetFy;
      activeUtilised = parseFloat(btn.dataset.budgetUtilised || '0');
      editUtilisedDisplay.textContent = formatInr(activeUtilised);
      editAllocatedInput.value = btn.dataset.budgetAllocated || '0.00';
      editGstInput.value = btn.dataset.budgetGst || '18.00';
      updateEditPreview();
    });
  });

  if (editAllocatedInput) {
    editAllocatedInput.addEventListener('input', updateEditPreview);
    updateEditPreview();
  }

  // Project Budget Modal handling
  const editProjectForm = document.getElementById('editProjectBudgetForm');
  const editProjectSubtitle = document.getElementById('editProjectBudgetSubtitle');
  const editProjectUtilisedDisplay = document.getElementById('editProjectUtilisedDisplay');
  const editProjectAllocatedInput = document.getElementById('editProjectAllocatedInput');
  const editProjectCouncilInput = document.getElementById('editProjectCouncilInput');
  const editProjectClusterHeadInput = document.getElementById('editProjectClusterHeadInput');
  const editProjectHodInput = document.getElementById('editProjectHodInput');
  const editProjectCategoryInput = document.getElementById('editProjectCategoryInput');
  const editProjectLocationInput = document.getElementById('editProjectLocationInput');
  const editProjectEventMonthInput = document.getElementById('editProjectEventMonthInput');
  const editProjectPreview = document.getElementById('editProjectPreview');

  let activeProjectUtilised = 0;

  function updateProjectPreview() {
    if (!editProjectPreview || !editProjectAllocatedInput) return;
    const limit = parseFloat(editProjectAllocatedInput.value || '0');
    if (Number.isNaN(limit)) return;

    if (limit < activeProjectUtilised) {
      editProjectPreview.style.background = '#FEF2F2';
      editProjectPreview.style.borderLeft = '3px solid #DC2626';
      editProjectPreview.style.color = '#991B1B';
      editProjectPreview.textContent = 'Limit cannot be below currently utilised spend (' + formatInr(activeProjectUtilised) + ').';
      return;
    }

    const headroom = Math.max(0, limit - activeProjectUtilised);
    const pct = limit > 0 ? ((activeProjectUtilised / limit) * 100).toFixed(1) : '0.0';
    const isCritical = parseFloat(pct) >= 90;

    editProjectPreview.style.background = isCritical ? '#FFFBEB' : '#ECFDF5';
    editProjectPreview.style.borderLeft = '3px solid ' + (isCritical ? '#D97706' : '#059669');
    editProjectPreview.style.color = isCritical ? '#92400E' : '#065F46';
    editProjectPreview.textContent = 'Resulting headroom: ' + formatInr(headroom) + ' (' + pct + '% utilised)';
  }

  document.querySelectorAll('[data-project-id]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!editProjectForm) return;
      editProjectForm.action = btn.dataset.updateUrl;
      editProjectSubtitle.textContent = btn.dataset.projectName + ' (' + btn.dataset.projectDepartment + ' · ' + btn.dataset.projectFy + ')';
      activeProjectUtilised = parseFloat(btn.dataset.projectUtilised || '0');
      editProjectUtilisedDisplay.textContent = formatInr(activeProjectUtilised);
      editProjectAllocatedInput.value = btn.dataset.projectAllocated || '0.00';
      if (editProjectCouncilInput) editProjectCouncilInput.value = btn.dataset.projectCouncil || '';
      if (editProjectClusterHeadInput) editProjectClusterHeadInput.value = btn.dataset.projectClusterHead || '';
      if (editProjectHodInput) editProjectHodInput.value = btn.dataset.projectHod || '';
      if (editProjectCategoryInput) editProjectCategoryInput.value = btn.dataset.projectCategory || 'Major';
      if (editProjectLocationInput) editProjectLocationInput.value = btn.dataset.projectLocation || 'Delhi';
      if (editProjectEventMonthInput) editProjectEventMonthInput.value = btn.dataset.projectEventMonth || '';
      updateProjectPreview();
    });
  });

  if (editProjectAllocatedInput) {
    editProjectAllocatedInput.addEventListener('input', updateProjectPreview);
  }

  const reopenModal = @json(session('budget_modal'));
  if (reopenModal === 'create' && createModalEl && window.bootstrap) {
    new bootstrap.Modal(createModalEl).show();
  } else if (reopenModal === 'edit' && editModalEl && window.bootstrap) {
    new bootstrap.Modal(editModalEl).show();
  }
})();
@endif
@endsection