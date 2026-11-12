@extends('layouts.app')

@section('title', 'Checks & Balances')

@php
  $totalAllocated = $budgets->sum('allocated_amount');
  $totalUtilised = $budgets->sum('utilised_amount');
  $totalAvailable = max(0, $totalAllocated - $totalUtilised);
  $criticalCount = $budgets->filter(fn ($b) => $b->isNearLimit())->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Budget Master Actions -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Checks &amp; Balances &amp; Department Budget Master</h1>
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

  @if (!empty($nearLimitBudgets) && $nearLimitBudgets->isNotEmpty())
    <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3" style="background:#FEF2F2; border-left:4px solid #DC2626 !important;" role="alert">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
        <div class="d-flex align-items-start gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-white border text-danger" style="width:36px; height:36px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div style="font-size:13px;">
            <div class="fw-bold text-danger">
              90% Budget Utilisation Alert · {{ $nearLimitBudgets->count() }} {{ Str::plural('Department', $nearLimitBudgets->count()) }} Approaching Ceiling
            </div>
            <div class="text-dark mt-1" style="font-size:12px;">
              @foreach ($nearLimitBudgets->take(2) as $alertDept)
                <span class="me-3 d-inline-block">
                  <strong>{{ $alertDept->department }}</strong> at <strong>{{ $alertDept->utilisationPercent() }}%</strong>
                  (₹{{ number_format($alertDept->remainingAmount(), 2) }} remaining before overrun)
                </span>
              @endforeach
              @if ($nearLimitBudgets->count() > 2)
                <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-danger text-decoration-underline align-baseline" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#budgetAlertsModal">
                  +{{ $nearLimitBudgets->count() - 2 }} more departments
                </button>
              @endif
            </div>
          </div>
        </div>
        @if ($canManageBudgets && $nearLimitBudgets->count() === 1)
          @php $firstAlert = $nearLimitBudgets->first(); @endphp
          <button
            type="button"
            class="btn btn-sm btn-danger fw-semibold text-nowrap px-3 py-1"
            data-bs-toggle="modal"
            data-bs-target="#editBudgetModal"
            data-budget-id="{{ $firstAlert->id }}"
            data-budget-department="{{ $firstAlert->department }}"
            data-budget-fy="{{ $firstAlert->fiscal_year }}"
            data-budget-allocated="{{ number_format((float) $firstAlert->allocated_amount, 2, '.', '') }}"
            data-budget-utilised="{{ number_format((float) $firstAlert->utilised_amount, 2, '.', '') }}"
            data-budget-gst="{{ number_format((float) $firstAlert->gst_rate, 2, '.', '') }}"
            data-update-url="{{ route('checks-balances.budgets.update', $firstAlert) }}">
            Adjust Limit
          </button>
        @elseif ($nearLimitBudgets->count() > 2)
          <button type="button" class="btn btn-sm btn-outline-danger fw-semibold text-nowrap px-3 py-1" data-bs-toggle="modal" data-bs-target="#budgetAlertsModal">
            View All Alerts ({{ $nearLimitBudgets->count() }})
          </button>
        @endif
      </div>
    </div>

    @if ($nearLimitBudgets->count() > 2)
      <div class="modal fade" id="budgetAlertsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                <h6 class="modal-title fw-bold mb-0">90%+ Budget Utilisation Alerts ({{ $nearLimitBudgets->count() }})</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
              <div class="list-group list-group-flush">
                @foreach ($nearLimitBudgets as $alertDept)
                  <div class="list-group-item px-3 py-2 d-flex align-items-center justify-content-between gap-2">
                    <div>
                      <div class="fw-bold text-dark">{{ $alertDept->department }} · {{ $alertDept->utilisationPercent() }}% used</div>
                      <div class="text-muted small">
                        Utilised ₹{{ number_format((float) $alertDept->utilised_amount, 2) }} / ₹{{ number_format((float) $alertDept->allocated_amount, 2) }} ·
                        ₹{{ number_format($alertDept->remainingAmount(), 2) }} remaining before overrun
                      </div>
                    </div>
                    @if ($canManageBudgets)
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger fw-semibold"
                        data-bs-dismiss="modal"
                        data-bs-toggle="modal"
                        data-bs-target="#editBudgetModal"
                        data-budget-id="{{ $alertDept->id }}"
                        data-budget-department="{{ $alertDept->department }}"
                        data-budget-fy="{{ $alertDept->fiscal_year }}"
                        data-budget-allocated="{{ number_format((float) $alertDept->allocated_amount, 2, '.', '') }}"
                        data-budget-utilised="{{ number_format((float) $alertDept->utilised_amount, 2, '.', '') }}"
                        data-budget-gst="{{ number_format((float) $alertDept->gst_rate, 2, '.', '') }}"
                        data-update-url="{{ route('checks-balances.budgets.update', $alertDept) }}">
                        Adjust Limit
                      </button>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endif

  <!-- Status KPI Cards -->
  @php
    $ceilingFormatted = number_format($totalAllocated, 2);
    $committedFormatted = number_format($totalUtilised, 2);
    $headroomFormatted = number_format($totalAvailable, 2);
    $maxKpiLen = max(strlen($ceilingFormatted), strlen($committedFormatted), strlen($headroomFormatted));
    $kpiFontSize = match (true) {
      $maxKpiLen >= 17 => 'clamp(0.95rem, 1.15vw, 1.15rem)',
      $maxKpiLen >= 14 => 'clamp(1.05rem, 1.3vw, 1.3rem)',
      default => 'clamp(1.15rem, 1.45vw, 1.45rem)',
    };
    $kpiValueStyle = "font-size: {$kpiFontSize}; font-weight: 700; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; line-height: 1.2;";
  @endphp
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3" style="min-width: 0;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Ceiling</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="{{ $kpiValueStyle }}" title="₹{{ $ceilingFormatted }}">₹{{ $ceilingFormatted }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3" style="min-width: 0;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Committed</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="{{ $kpiValueStyle }}" title="₹{{ $committedFormatted }}">₹{{ $committedFormatted }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3" style="min-width: 0;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Headroom</span>
            <div class="stat-icon-box icon-secondary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-cash-coin"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="{{ $kpiValueStyle }}" title="₹{{ $headroomFormatted }}">₹{{ $headroomFormatted }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3" style="min-width: 0;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Alerts (90%+)</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="{{ $kpiValueStyle }}">{{ $criticalCount }} {{ Str::plural('Dept', $criticalCount) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Department Budget Master Table -->
  <div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 border-bottom">
      <div>
        <h6 class="fw-bold mb-0 text-main">Departmental Budget Master — {{ $fiscalYear }}</h6>
        <div class="text-muted" style="font-size: 12px;">
          Department-wise ceiling limits enforced across purchase requisitions.
          @if ($canManageBudgets)
            Managed by Head Admin.
          @endif
        </div>
      </div>
      <span class="badge badge-soft-primary">{{ $budgets->count() }} {{ Str::plural('Department', $budgets->count()) }}</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light text-uppercase" style="font-size: 11px; letter-spacing: 0.4px;">
          <tr>
            <th class="ps-4 py-2">Department</th>
            <th class="py-2 text-end">Budget Limit</th>
            <th class="py-2 text-end">Utilised</th>
            <th class="py-2" style="min-width: 190px;">Utilisation</th>
            <th class="py-2 text-end">Headroom</th>
            @if ($canManageBudgets)
              <th class="pe-4 py-2 text-end">Action</th>
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
              <td class="ps-4 py-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="fw-bold text-main">{{ $budget->department }}</span>
                  @if ($budget->isNearLimit())
                    <span class="badge badge-soft-danger" style="font-size: 11px;">Critical (90%+)</span>
                  @endif
                </div>
                <div class="text-muted" style="font-size: 11px;">
                  {{ $budget->fiscal_year }} · GST {{ number_format((float) $budget->gst_rate, 0) }}%
                </div>
              </td>
              <td class="py-3 text-end fw-bold text-main text-nowrap" style="font-variant-numeric: tabular-nums;">
                ₹{{ number_format((float) $budget->allocated_amount, 2) }}
              </td>
              <td class="py-3 text-end text-muted text-nowrap" style="font-variant-numeric: tabular-nums;">
                ₹{{ number_format((float) $budget->utilised_amount, 2) }}
              </td>
              <td class="py-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height: 7px; border-radius: 99px; background-color: var(--border-color);">
                    <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($pct, 100) }}%; border-radius: 99px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <span class="badge {{ $pctBadge }} flex-shrink-0" style="min-width: 52px; font-size: 11px; font-variant-numeric: tabular-nums;">{{ $pct }}%</span>
                </div>
              </td>
              <td class="py-3 text-end fw-semibold text-nowrap {{ $remaining > 0 ? 'text-success' : 'text-danger' }}" style="font-variant-numeric: tabular-nums;">
                ₹{{ number_format($remaining, 2) }}
              </td>
              @if ($canManageBudgets)
                <td class="pe-4 py-3 text-end text-nowrap">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1"
                    style="font-size: 12px;"
                    data-bs-toggle="modal"
                    data-bs-target="#editBudgetModal"
                    data-budget-id="{{ $budget->id }}"
                    data-budget-department="{{ $budget->department }}"
                    data-budget-fy="{{ $budget->fiscal_year }}"
                    data-budget-allocated="{{ number_format((float) $budget->allocated_amount, 2, '.', '') }}"
                    data-budget-utilised="{{ number_format((float) $budget->utilised_amount, 2, '.', '') }}"
                    data-budget-gst="{{ number_format((float) $budget->gst_rate, 2, '.', '') }}"
                    data-update-url="{{ route('checks-balances.budgets.update', $budget) }}">
                    <i class="bi bi-pencil-square me-1"></i>Edit Limit
                  </button>
                  @if ($budget->can_delete)
                    <form method="POST" action="{{ route('checks-balances.budgets.destroy', $budget) }}" class="d-inline ms-1" onsubmit="return confirm('Remove unused budget limit for {{ $budget->department }} ({{ $budget->fiscal_year }})?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-light border text-danger px-2 py-1" title="Remove unused entry" style="font-size: 12px;">
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

  <!-- Institutional Governance Authority Matrix -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4">
      <div>
        <h6 class="fw-bold mb-0 text-main">Institutional Governance &amp; Signing Authority Matrix</h6>
      </div>
      <span class="badge badge-soft-info"><i class="bi bi-shield-lock me-1"></i> Governance Mandate</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">PROCUREMENT TIER</th>
              <th>REVIEW &amp; APPROVAL AUTHORITY</th>
              <th>KEY COMPLIANCE CONSTRAINTS</th>
              <th class="text-end pe-4">STATUS RULE</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-box icon-primary" style="width: 28px; height: 28px; font-size: 12px;">
                    <i class="bi bi-receipt"></i>
                  </span>
                  <span class="fw-bold text-main">General PRs (&lt; ₹10 Lakhs)</span>
                </div>
              </td>
              <td>
                <span class="badge badge-soft-primary">Admin Head &rarr; Operational Review &rarr; CFO</span>
              </td>
              <td class="text-muted">Budget headroom checked at both PR and PO issuance.</td>
              <td class="text-end pe-4">
                <span class="badge badge-soft-success">Standard Chain</span>
              </td>
            </tr>

            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-box icon-warning" style="width: 28px; height: 28px; font-size: 12px;">
                    <i class="bi bi-stars"></i>
                  </span>
                  <span class="fw-bold text-main">High-Value PRs (&ge; ₹10 Lakhs)</span>
                </div>
              </td>
              <td>
                <span class="badge badge-soft-warning">Auto-routed to Secretary General Sanction</span>
              </td>
              <td class="text-muted">Requires CFO budget sign-off and SG executive approval.</td>
              <td class="text-end pe-4">
                <span class="badge badge-soft-warning">Executive Clear</span>
              </td>
            </tr>

            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-box icon-info" style="width: 28px; height: 28px; font-size: 12px;">
                    <i class="bi bi-airplane"></i>
                  </span>
                  <span class="fw-bold text-main">Domestic Travel (TRF)</span>
                </div>
              </td>
              <td>
                <span class="badge badge-soft-info">ASG 1 or ASG 2 Final Approval</span>
              </td>
              <td class="text-muted">Pushbacks permitted at either level with mandatory reviewer comments.</td>
              <td class="text-end pe-4">
                <span class="badge badge-soft-success">ASG Level</span>
              </td>
            </tr>

            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-box icon-secondary" style="width: 28px; height: 28px; font-size: 12px;">
                    <i class="bi bi-globe"></i>
                  </span>
                  <span class="fw-bold text-main">International Travel (TRF)</span>
                </div>
              </td>
              <td>
                <span class="badge badge-soft-secondary">Secretary General (SG) Approval</span>
              </td>
              <td class="text-muted">Requires foreign travel justification and insurance validation.</td>
              <td class="text-end pe-4">
                <span class="badge badge-soft-secondary">SG Level</span>
              </td>
            </tr>

            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <span class="stat-icon-box icon-danger" style="width: 28px; height: 28px; font-size: 12px;">
                    <i class="bi bi-shield-exclamation"></i>
                  </span>
                  <span class="fw-bold text-main">Off-TRF Direct Claims</span>
                </div>
              </td>
              <td>
                <span class="badge badge-soft-danger">Secretary General Override</span>
              </td>
              <td class="text-muted">Bypasses prior TRF requisition; requires mandatory business justification.</td>
              <td class="text-end pe-4">
                <span class="badge badge-soft-danger">Strict Override</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

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
@endif
@endsection

@section('init')
Shell.init({ activeNav: 'checks-balances', crumb: 'Governance', title: 'Checks & Balances' });

@if ($canManageBudgets)
(function () {
  const editModalEl = document.getElementById('editBudgetModal');
  const createModalEl = document.getElementById('createBudgetModal');
  const editForm = document.getElementById('editBudgetForm');
  const editSubtitle = document.getElementById('editBudgetSubtitle');
  const editUtilisedDisplay = document.getElementById('editBudgetUtilisedDisplay');
  const editAllocatedInput = document.getElementById('editAllocatedInput');
  const editGstInput = document.getElementById('editGstInput');
  const editPreview = document.getElementById('editBudgetPreview');

  let activeUtilised = {{ $editingBudget ? (float) $editingBudget->utilised_amount : 0 }};

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

  const reopenModal = @json(session('budget_modal'));
  if (reopenModal === 'create' && createModalEl && window.bootstrap) {
    new bootstrap.Modal(createModalEl).show();
  } else if (reopenModal === 'edit' && editModalEl && window.bootstrap) {
    new bootstrap.Modal(editModalEl).show();
  }
})();
@endif
@endsection
