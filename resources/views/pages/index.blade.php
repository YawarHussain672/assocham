@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Dashboard</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('grievance.create') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chat-square-text me-1"></i> File Grievance
      </a>
      @if (auth()->user()?->role === \App\Support\Roles::HOD)
        <a href="{{ route('annual-budget.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-cash-stack me-1"></i> Submit Annual Budget
        </a>
      @endif
      @if (auth()->user()?->role === \App\Support\Roles::PR_SR_EXEC)
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Raise Purchase Requisition (PR)
        </a>
      @elseif (auth()->user()?->role === \App\Support\Roles::BUYER)
        <a href="{{ route('onboarding') }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-person-plus me-1"></i> Empanel Vendor
        </a>
        <a href="{{ route('rfq.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-send me-1"></i> Create RFQ
        </a>
      @elseif (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
        <a href="{{ route('trf.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-airplane me-1"></i> New Travel Request (TRF)
        </a>
      @endif
    </div>
  </div>

  <!-- ====================================================================
       FOUR CORE KPI STAT CARDS (Clean & Minimal Style)
       ==================================================================== -->
  <div class="row g-2 mb-3">
    <!-- Card 1: Awaiting My Approval -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Awaiting My Approval</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value">{{ $pendingItems->count() }}</div>
          <div class="stat-footer">
            @if ($pendingItems->count() > 0)
              <a href="#pendingApprovalsSection" class="text-decoration-none d-inline-flex align-items-center gap-1 text-danger fw-medium" style="font-size: 11.5px;">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill">
                  <i class="bi bi-clock-history me-1"></i>Approve or reject
                </span>
                <span class="text-muted ms-1">{{ $pendingItems->count() }} pending</span>
              </a>
            @else
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-medium">
                <i class="bi bi-check2 me-1"></i>All caught up
              </span>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Card 2: Open Requisitions -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Open Requisitions</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-file-earmark-text"></i>
            </div>
          </div>
          <div class="stat-value">{{ $pendingPrs }}</div>
          <div class="stat-footer text-muted">
            <a href="{{ route('requisition') }}" class="text-decoration-none text-muted">
              <span><strong class="text-dark">{{ $approvedPrs }}</strong> approved in total ({{ $totalPrs }} filed)</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 3: Budget Utilised (Governance Roles) / Travel & Settlements (Other Roles) -->
    <div class="col-12 col-sm-6 col-xl-3">
      @if ($hasBudgetAccess)
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Budget Utilised (GST-Incl)</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-pie-chart"></i>
            </div>
          </div>
          <div class="stat-value">{{ $overallUtilisation }}%</div>
          <div class="stat-footer d-flex align-items-center justify-content-between text-muted">
            <span title="Department Budgets">₹{{ number_format($totalUtilised / 10000000, 2) }} Cr / ₹{{ number_format($totalAllocated / 10000000, 2) }} Cr</span>
            @php
              $totalBudgetAlerts = $nearLimitCount + (!empty($nearLimitProjects) ? $nearLimitProjects->count() : 0);
            @endphp
            @if ($totalBudgetAlerts > 0)
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5 rounded-pill" style="font-size: 10.5px;">
                {{ $totalBudgetAlerts }} alert{{ $totalBudgetAlerts === 1 ? '' : 's' }}
              </span>
            @endif
          </div>
        </div>
      </div>
      @else
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Travel &amp; Claims</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-airplane"></i>
            </div>
          </div>
          <div class="stat-value">{{ $totalTrfs }}</div>
          <div class="stat-footer d-flex align-items-center justify-content-between text-muted">
            <span>{{ $approvedTrfs }} approved TRFs</span>
            <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5 rounded-pill" style="font-size: 10.5px;">{{ $totalReimbursements }} claims</span>
          </div>
        </div>
      </div>
      @endif
    </div>

    <!-- Card 4: Committed Spend -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-danger card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Committed Spend</span>
            <div class="stat-icon-box icon-danger">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value">₹{{ number_format($totalPoSpend + $totalReimbClaimed, 2) }}</div>
          <div class="stat-footer text-muted">
            <span>{{ $approvedVendors }} active vendors · {{ $totalWorkflows }} workflows</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- @if ($hasBudgetAccess && !empty($nearLimitBudgets) && $nearLimitBudgets->isNotEmpty())
  <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2" style="background:#FEF2F2; border-left:4px solid #DC2626 !important;">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-white border text-danger" style="width:34px; height:34px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
      </div>
      <div style="font-size:13px;">
        <div class="fw-bold text-danger">
          90% Budget Utilisation Alert · {{ $nearLimitBudgets->count() }} {{ Str::plural('Department', $nearLimitBudgets->count()) }} Near Limit
        </div>
        <div class="text-dark" style="font-size:12px;">
          @foreach ($nearLimitBudgets->take(2) as $alertDept)
          <span class="me-3">
            <strong>{{ $alertDept->department }}</strong> ({{ $alertDept->utilisationPercent() }}% used · ₹{{ number_format($alertDept->remainingAmount(), 2) }} left)
          </span>
          @endforeach
          @if ($nearLimitBudgets->count() > 2)
          <span class="text-danger fw-semibold">+{{ $nearLimitBudgets->count() - 2 }} more</span>
          @endif
        </div>
      </div>
    </div>
    <a href="{{ route('checks-balances') }}" class="btn btn-sm btn-danger fw-semibold text-nowrap px-3 py-1">
      Review Budget Limits →
    </a>
  </div>
  @endif -->

  @if ($hasBudgetAccess && !empty($nearLimitProjects) && $nearLimitProjects->isNotEmpty())
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
          @foreach ($nearLimitProjects->take(2) as $alertProj)
          <span class="me-3">
            <strong>{{ $alertProj->project_name }}</strong> ({{ $alertProj->utilisationPercent() }}% used · ₹{{ number_format($alertProj->remainingAmount(), 2) }} left)
          </span>
          @endforeach
          @if ($nearLimitProjects->count() > 2)
          <span class="text-warning-emphasis fw-semibold">+{{ $nearLimitProjects->count() - 2 }} more</span>
          @endif
        </div>
      </div>
    </div>
    <a href="{{ route('checks-balances', ['view' => 'project']) }}" class="btn btn-sm erp-btn-action-view fw-semibold text-nowrap px-3 py-1">
      Review Project Budgets →
    </a>
  </div>
  @endif

  <!-- ====================================================================
       ACTION PIPELINE & LIVE AUDIT TRAIL FEED
       ==================================================================== -->
  <style>
    .dash-pipeline-table {
      width: 100%;
      table-layout: fixed;
      border-collapse: collapse;
      margin-bottom: 0;
    }

    .dash-pipeline-table thead th {
      font-size: 12.5px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #475569;
      padding: 12px 14px;
      background-color: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      white-space: nowrap;
    }

    .dash-pipeline-table tbody td {
      padding: 12px 14px;
      font-size: 13.5px;
      vertical-align: middle;
      border-bottom: 1px solid #f1f5f9;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .dash-pipeline-table tbody tr:last-child td {
      border-bottom: none;
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

    .dash-audit-row {
      padding: 10px 14px;
      border-bottom: 1px solid #f1f5f9;
      flex: 1 1 0;
      display: flex;
      align-items: center;
    }

    .dash-audit-row:last-child {
      border-bottom: none;
    }

    .dash-card-footer {
      padding: 9px 14px;
      background-color: #f8fafc;
      border-top: 1px solid #e2e8f0;
      font-size: 11.5px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
  </style>

  @php
  $dashboardPendingLimit = 7;
  $visiblePendingItems = $pendingItems->take($dashboardPendingLimit);
  $extraPendingItems = max(0, $pendingItems->count() - $dashboardPendingLimit);
  $visibleRecentActivity = $recentActivity->take(6);
  @endphp

  <div class="row g-4 mb-4">
    <!-- Items Awaiting Action / Pipeline -->
    <div class="col-12 d-flex flex-column">
      <div class="erp-table-container">
        <div class="erp-table-toolbar justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark" style="font-size: 14px;">Awaiting Action &amp; Pipeline Review</span>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $pendingItems->count() }} Pending</span>
          </div>
          <a href="{{ route('notifications') }}" class="btn btn-sm btn-outline-secondary" style="font-size: 12px;">
            Open Action Center &rarr;
          </a>
        </div>
        <div class="table-responsive">
          <table class="erp-table align-middle mb-0">
            <thead>
              <tr>
                <th class="ps-3" style="width: 25%;">REFERENCE NO.</th>
                <th style="width: 15%;">MODULE</th>
                <th style="width: 35%;">TITLE</th>
                <th style="width: 13%;">STATUS</th>
                <th class="text-end pe-3" style="width: 12%;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($visiblePendingItems as $item)
              <tr>
                <td class="ps-3" title="{{ $item['number'] }}">
                  <a href="{{ $item['url'] }}" class="erp-code-link" style="font-size: 13.5px;">
                    {{ $item['number'] }}
                  </a>
                </td>
                <td>
                  <span class="erp-badge-neutral">{{ strtoupper($item['module'] ?? 'PR') }}</span>
                </td>
                <td title="{{ $item['label'] }}">
                  <span class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $item['label'] }}</span>
                </td>
                <td>
                  <span class="erp-badge-warning">Pending</span>
                </td>
                <td class="text-end pe-3">
                  <div class="erp-row-actions">
                    <a href="{{ $item['url'] }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                  <span class="fw-semibold text-dark">All caught up! There are no action items pending your sign-off.</span>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="erp-table-footer">
          <span>Showing {{ $visiblePendingItems->count() }} of {{ $pendingItems->count() }} pending {{ Str::plural('item', $pendingItems->count()) }}</span>
          <a href="{{ route('notifications') }}" class="fw-semibold text-primary text-decoration-none">
            {{ $extraPendingItems > 0 ? "+{$extraPendingItems} more in Action Center" : 'View Action Center' }} &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'dashboard', crumb: 'Overview', title: 'Dashboard' });
@endsection