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

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Departmental Budgets &amp; Governance Matrix</h1>
      <div class="page-sub" style="margin-top:0;">GST-inclusive departmental budget ceilings enforce hard stops across PR, PO, and Travel commitments. Overrides require CFO/SG exception approvals.</div>
    </div>
  </div>

  <!-- Top 4 Pastel Stat Cards -->
  <div class="row g-3 mb-4">
    <!-- Card 1: Blue -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Fiscal Budget Ceiling</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">₹{{ number_format($totalAllocated, 2) }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">FY {{ $fiscalYear }} Approved Total</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-wallet2 fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Card 2: Green -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Committed Spend</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">₹{{ number_format($totalUtilised, 2) }}</div>
            <div class="mt-1" style="font-size:11.5px; color:#2e7d4f; font-weight:600;">{{ $overallPct }}% Utilised Overall</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-check2-circle fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Card 3: Purple -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Remaining Headroom</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">₹{{ number_format($totalAvailable, 2) }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Available for Commitments</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-pie-chart fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Card 4: Amber -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Threshold Alerts</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $criticalCount }} Depts</div>
            <div class="mt-1" style="font-size:11.5px; color:{{ $criticalCount > 0 ? '#b0263a' : '#2e7d4f' }}; font-weight:600;">
              {{ $criticalCount > 0 ? 'Near or above 80% ceiling' : 'All budgets healthy' }}
            </div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-exclamation-triangle fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Departmental Budget Meters -->
  <div class="card-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="h6 mb-1 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-bank text-primary"></i>
          <span>Departmental Budget Consumption — FY {{ $fiscalYear }}</span>
        </h2>
        <div class="text-muted" style="font-size:12.5px;">Includes all approved purchase requisitions, active purchase orders, and travel claims.</div>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $budgets->count() }} Departments Configured</span>
    </div>

    <div class="d-flex flex-column gap-3">
      @forelse ($budgets as $budget)
        @php
          $pct = $budget->utilisationPercent();
          $barColor = $pct >= 90 ? '#B0263A' : ($pct >= 70 ? '#D97706' : '#2E7D4F');
          $remaining = max(0, $budget->allocated_amount - $budget->utilised_amount);
        @endphp
        <div class="p-3 border rounded-3 bg-white">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="stat-icon-circle" style="background:#f8fafc; color:#475569; width:32px; height:32px; font-size:13px;">
                <i class="bi bi-building"></i>
              </span>
              <div>
                <span class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $budget->department }}</span>
                @if($budget->isNearLimit())
                  <span class="badge ms-2" style="background:#fbe7e9; color:#b0263a; font-size:11px; font-weight:600;">Critical Threshold</span>
                @endif
              </div>
            </div>

            <div class="text-end">
              <span class="text-muted" style="font-size:12px;">Utilised: </span>
              <strong style="color:#1e293b; font-size:13.5px;">₹{{ number_format($budget->utilised_amount, 2) }}</strong>
              <span class="text-muted" style="font-size:12px;"> / ₹{{ number_format($budget->allocated_amount, 2) }}</span>
              <span class="badge ms-2" style="background:{{ $pct >= 90 ? '#fbe7e9' : ($pct >= 70 ? '#fbf0dc' : '#e6f3ec') }}; color:{{ $barColor }}; font-weight:600;">
                {{ $pct }}%
              </span>
            </div>
          </div>

          <div class="progress" style="height:8px; background:#f1f5f9; border-radius:99px;">
            <div class="progress-bar" role="progressbar" style="width:{{ min($pct, 100) }}%; background-color:{{ $barColor }}; border-radius:99px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:11.5px; color:#64748b;">
            <span>Headroom Balance: <strong style="color:{{ $remaining > 0 ? '#2e7d4f' : '#b0263a' }};">₹{{ number_format($remaining, 2) }}</strong> remaining</span>
            <span>Fiscal Year: {{ $fiscalYear }}</span>
          </div>
        </div>
      @empty
        <div class="p-4 text-center text-muted border rounded-3" style="background:#f8fafc;">
          <i class="bi bi-wallet2 text-muted fs-3"></i>
          <div class="mt-2">No department budgets configured for {{ $fiscalYear }}.</div>
        </div>
      @endforelse
    </div>
  </div>

  <!-- Governance Authority Matrix -->
  <div class="card-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="h6 mb-1 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-shield-lock text-primary"></i>
          <span>Institutional Governance &amp; Signing Authority Matrix</span>
        </h2>
        <div class="text-muted" style="font-size:12.5px;">Delegation of financial and administrative powers across requisition, procurement, and travel tiers.</div>
      </div>
    </div>

    <div style="overflow-x:auto;">
      <table class="table align-middle">
        <thead>
          <tr class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">
            <th>Procurement Tier</th>
            <th>Review &amp; Approval Authority</th>
            <th>Key Compliance Constraints</th>
            <th class="text-end">Status Rule</th>
          </tr>
        </thead>
        <tbody style="font-size:13.5px;">
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-circle" style="background:#e6f0fb; color:#1e5fa8; width:28px; height:28px; font-size:12px;">
                  <i class="bi bi-receipt"></i>
                </span>
                <span class="fw-semibold text-dark" style="font-weight:600;">General PRs (&lt; ₹10 Lakhs)</span>
              </div>
            </td>
            <td>
              <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:500;">Admin Head (L1) → Executive (L2) → CFO (L3)</span>
            </td>
            <td class="text-muted">Budget headroom checked at both PR and PO issuance.</td>
            <td class="text-end">
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-weight:600;">Standard Chain</span>
            </td>
          </tr>

          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-circle" style="background:#fbf0dc; color:#8a5a12; width:28px; height:28px; font-size:12px;">
                  <i class="bi bi-stars"></i>
                </span>
                <span class="fw-semibold text-dark" style="font-weight:600;">High-Value PRs (≥ ₹10 Lakhs)</span>
              </div>
            </td>
            <td>
              <span class="badge" style="background:#fbf0dc; color:#8a5a12; font-weight:600;">Auto-routed to Secretary General (L4)</span>
            </td>
            <td class="text-muted">Requires CFO budget sign-off and SG executive approval.</td>
            <td class="text-end">
              <span class="badge" style="background:#fbf0dc; color:#8a5a12; font-weight:600;">Executive Clear</span>
            </td>
          </tr>

          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-circle" style="background:#e6f0fb; color:#1e5fa8; width:28px; height:28px; font-size:12px;">
                  <i class="bi bi-airplane"></i>
                </span>
                <span class="fw-semibold text-dark" style="font-weight:600;">Domestic Travel (TRF)</span>
              </div>
            </td>
            <td>
              <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:500;">ASG 1 or ASG 2 Final Approval</span>
            </td>
            <td class="text-muted">Pushbacks permitted at either level with mandatory reviewer comments.</td>
            <td class="text-end">
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-weight:600;">ASG Level</span>
            </td>
          </tr>

          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-circle" style="background:#f3e8ff; color:#6b21a8; width:28px; height:28px; font-size:12px;">
                  <i class="bi bi-globe"></i>
                </span>
                <span class="fw-semibold text-dark" style="font-weight:600;">International Travel (TRF)</span>
              </div>
            </td>
            <td>
              <span class="badge" style="background:#f3e8ff; color:#6b21a8; font-weight:600;">Secretary General (SG) Approval</span>
            </td>
            <td class="text-muted">Requires foreign travel justification and insurance validation.</td>
            <td class="text-end">
              <span class="badge" style="background:#f3e8ff; color:#6b21a8; font-weight:600;">SG Level</span>
            </td>
          </tr>

          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="stat-icon-circle" style="background:#fbe7e9; color:#b0263a; width:28px; height:28px; font-size:12px;">
                  <i class="bi bi-shield-exclamation"></i>
                </span>
                <span class="fw-semibold text-dark" style="font-weight:600;">Off-TRF Direct Claims</span>
              </div>
            </td>
            <td>
              <span class="badge" style="background:#fbe7e9; color:#b0263a; font-weight:600;">Secretary General Override</span>
            </td>
            <td class="text-muted">Bypasses prior TRF requisition; requires mandatory business justification.</td>
            <td class="text-end">
              <span class="badge" style="background:#fbe7e9; color:#b0263a; font-weight:600;">Strict Override</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'checks-balances', crumb: 'Governance', title: 'Checks & Balances' });
@endsection

