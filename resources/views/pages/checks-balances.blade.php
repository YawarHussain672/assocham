@extends('layouts.app')

@section('title', 'Checks & Balances')

@php
  $totalAllocated = $budgets->sum('allocated_amount');
  $totalUtilised = $budgets->sum('utilised_amount');
  $totalAvailable = max(0, $totalAllocated - $totalUtilised);
  $criticalCount = $budgets->filter(fn($b) => $b->isNearLimit())->count();
  $overallPct = $totalAllocated > 0 ? round(($totalUtilised / $totalAllocated) * 100, 1) : 0;
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Checks &amp; Balances Governance Matrix</h1>
      <p class="page-subtitle">Departmental budget allocations, fiscal commitments, and financial control thresholds.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge badge-soft-warning py-2 px-3 fs-6">
        <i class="bi bi-shield-check me-1"></i> FY {{ $fiscalYear }} Active
      </span>
    </div>
  </div>

  <!-- Top 4 Pastel KPI Stat Cards -->
  <div class="row g-3 mb-4">
    <!-- Card 1: Blue -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Fiscal Ceiling</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($totalAllocated, 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-calendar3"></i>
            <span>Approved FY {{ $fiscalYear }} Total</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 2: Green -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Committed Spend</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($totalUtilised, 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-pie-chart"></i>
            <span>{{ $overallPct }}% Utilised Overall</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 3: Purple -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Remaining Headroom</span>
            <div class="stat-icon-box icon-secondary">
              <i class="bi bi-cash-coin"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($totalAvailable, 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-secondary small fw-semibold">
            <i class="bi bi-shield-check"></i>
            <span>Available for Commitments</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 4: Amber -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Threshold Alerts</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $criticalCount }} Depts</div>
          <div class="d-flex align-items-center gap-1 small fw-semibold {{ $criticalCount > 0 ? 'text-danger' : 'text-success' }}">
            <i class="bi {{ $criticalCount > 0 ? 'bi-exclamation-octagon-fill' : 'bi-check-all' }}"></i>
            <span>{{ $criticalCount > 0 ? 'Near or above 80% ceiling' : 'All budgets healthy' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Departmental Budget Meters -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Departmental Budget Consumption — FY {{ $fiscalYear }}</h6>
        <small class="text-muted">Includes approved purchase requisitions, active purchase orders, and travel claims</small>
      </div>
      <span class="badge badge-soft-primary">{{ $budgets->count() }} Departments Configured</span>
    </div>
    <div class="card-body p-4">
      <div class="d-flex flex-column gap-3">
        @forelse ($budgets as $budget)
          @php
            $pct = $budget->utilisationPercent();
            $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
            $remaining = max(0, $budget->allocated_amount - $budget->utilised_amount);
          @endphp
          <div class="p-3 border rounded-3 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 13px;">
                  <i class="bi bi-building"></i>
                </span>
                <div>
                  <span class="fw-bold text-main">{{ $budget->department }}</span>
                  @if($budget->isNearLimit())
                    <span class="badge badge-soft-danger ms-2">Critical Threshold (90% Alert)</span>
                  @endif
                </div>
              </div>

              <div class="text-end">
                <span class="text-muted small">Utilised: </span>
                <strong class="text-main">₹{{ number_format($budget->utilised_amount, 2) }}</strong>
                <span class="text-muted small"> / ₹{{ number_format($budget->allocated_amount, 2) }}</span>
                <span class="badge {{ $pct >= 90 ? 'badge-soft-danger' : ($pct >= 70 ? 'badge-soft-warning' : 'badge-soft-success') }} ms-2">
                  {{ $pct }}%
                </span>
              </div>
            </div>

            <div class="progress" style="height: 8px; border-radius: 99px; background-color: var(--border-color);">
              <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($pct, 100) }}%; border-radius: 99px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
              <span>Headroom Balance: <strong class="{{ $remaining > 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format($remaining, 2) }}</strong> remaining</span>
              <span>Fiscal Year: {{ $fiscalYear }}</span>
            </div>
          </div>
        @empty
          <div class="p-4 text-center text-muted border rounded-3 bg-light">
            <i class="bi bi-wallet2 text-muted fs-3 d-block mb-1"></i>
            <div>No department budgets configured for {{ $fiscalYear }}.</div>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Governance Authority Matrix -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Institutional Governance &amp; Signing Authority Matrix</h6>
        <small class="text-muted">Delegation of financial and administrative powers across requisition, procurement, and travel tiers</small>
      </div>
      <span class="badge badge-soft-info"><i class="bi bi-shield-lock me-1"></i> Governance Mandate</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">PROCUREMENT TIER</th>
              <th>REVIEW &amp; APPROVAL AUTHORITY</th>
              <th>KEY COMPLIANCE CONSTRAINTS</th>
              <th class="text-end pe-3">STATUS RULE</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="ps-3">
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
              <td class="text-end pe-3">
                <span class="badge badge-soft-success">Standard Chain</span>
              </td>
            </tr>

            <tr>
              <td class="ps-3">
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
              <td class="text-end pe-3">
                <span class="badge badge-soft-warning">Executive Clear</span>
              </td>
            </tr>

            <tr>
              <td class="ps-3">
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
              <td class="text-end pe-3">
                <span class="badge badge-soft-success">ASG Level</span>
              </td>
            </tr>

            <tr>
              <td class="ps-3">
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
              <td class="text-end pe-3">
                <span class="badge badge-soft-secondary">SG Level</span>
              </td>
            </tr>

            <tr>
              <td class="ps-3">
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
              <td class="text-end pe-3">
                <span class="badge badge-soft-danger">Strict Override</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'checks-balances', crumb: 'Governance', title: 'Checks & Balances' });
@endsection
