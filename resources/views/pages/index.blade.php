@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="view-wrap" id="pageContent">
  <div class="fade-in mb-3">
    <div>
      <div class="page-greeting">Welcome back, {{ auth()->user()->name }}</div>
      <div class="page-greeting-sub">{{ auth()->user()->roleLabel() }} · Real-time operational intelligence, procurement workflows &amp; departmental budgets for {{ $fiscalYear }}.</div>
    </div>
  </div>

  <!-- 4 Pastel KPI Cards with distinct colors like previous design -->
  <div class="stat-strip mb-3">
    <!-- Card 1 (Blue Pastel) -->
    <div class="stat-card stat-card-blue">
      <div class="stat-body">
        <div class="stat-label">Awaiting My Approval</div>
        <div class="stat-count">{{ $pendingItems->count() }}</div>
        <div class="kpi-delta-row">
          @if ($pendingItems->count() > 0)
            <span class="kpi-delta" style="background:rgba(200, 16, 46, 0.12); color:#c8102e; font-weight:600;">
              <i class="bi bi-exclamation-circle-fill"></i> Action Required
            </span>
          @else
            <span class="kpi-delta" style="background:rgba(16, 185, 129, 0.14); color:#059669; font-weight:600;">
              <i class="bi bi-check2-all"></i> All Caught Up
            </span>
          @endif
        </div>
      </div>
      <div class="stat-icon-circle">
        <i class="bi bi-check2-circle" style="font-size: 22px;"></i>
      </div>
    </div>

    <!-- Card 2 (Amber Pastel) -->
    <div class="stat-card stat-card-amber">
      <div class="stat-body">
        <div class="stat-label">Departments Near Limit</div>
        <div class="stat-count">{{ $nearLimitCount }} / {{ $allBudgets->count() }}</div>
        <div class="kpi-delta-row">
          @if ($nearLimitCount > 0)
            <span class="kpi-delta" style="background:rgba(217, 119, 6, 0.14); color:#d97706; font-weight:600;">
              <i class="bi bi-shield-exclamation"></i> Fiscal Watch ({{ $overallUtilisation }}% utilised)
            </span>
          @else
            <span class="kpi-delta" style="background:rgba(16, 185, 129, 0.14); color:#059669; font-weight:600;">
              <i class="bi bi-shield-check"></i> Healthy Spread
            </span>
          @endif
        </div>
      </div>
      <div class="stat-icon-circle">
        <i class="bi bi-pie-chart" style="font-size: 22px;"></i>
      </div>
    </div>

    <!-- Card 3 (Purple Pastel) -->
    <div class="stat-card stat-card-purple">
      <div class="stat-body">
        <div class="stat-label">Active Workflows</div>
        <div class="stat-count">{{ $totalWorkflows }}</div>
        <div class="kpi-delta-row">
          <span class="kpi-delta" style="background:rgba(124, 58, 237, 0.12); color:#7c3aed; font-weight:600;">
            <i class="bi bi-diagram-3"></i> {{ $totalPrs }} PRs · {{ $totalPos }} PO · {{ $totalTrfs }} TRF
          </span>
        </div>
      </div>
      <div class="stat-icon-circle">
        <i class="bi bi-people-fill" style="font-size: 22px;"></i>
      </div>
    </div>

    <!-- Card 4 (Green Pastel) -->
    <div class="stat-card stat-card-green">
      <div class="stat-body">
        <div class="stat-label">Committed Spend</div>
        <div class="stat-count" style="font-size:25px;">₹{{ number_format($totalPoSpend + $totalReimbClaimed, 2) }}</div>
        <div class="kpi-delta-row">
          <span class="kpi-delta" style="background:rgba(16, 185, 129, 0.14); color:#059669; font-weight:600;">
            <i class="bi bi-patch-check"></i> {{ $approvedVendors }}/{{ $totalVendors }} Active Vendor
          </span>
        </div>
      </div>
      <div class="stat-icon-circle">
        <i class="bi bi-cash-stack" style="font-size: 22px;"></i>
      </div>
    </div>
  </div>

  <!-- Row 1: The Two Interactive Graphs (col-6 / col-6) -->
  <div class="row g-3 mt-1">
    
    <!-- Left (col-6): Spline Chart for Procurement & Expenditure -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
              <div class="panel-title">Procurement &amp; Expenditure Analytics</div>
              <div class="d-flex align-items-center gap-3 mt-1" style="font-size:13.5px; font-weight:600; color:var(--text-muted2);">
                <span><i class="bi bi-circle-fill" style="color:#c8102e; font-size:10px;"></i> Approved Spend (₹{{ number_format($totalUtilised / 10000000, 2) }} Cr)</span>
                <span><i class="bi bi-circle-fill" style="color:#f59e0b; font-size:10px;"></i> Budget Allocation (₹{{ number_format($totalAllocated / 10000000, 2) }} Cr)</span>
              </div>
            </div>
            <select class="form-select form-select-sm" style="width: auto; border-radius: 20px; font-size: 13px; font-weight: 600; padding: 5px 28px 5px 12px;">
              <option>{{ $fiscalYear }} Active</option>
              <option>Previous Quarter</option>
              <option>All Time</option>
            </select>
          </div>

          <!-- SVG Spline Trend Graph -->
          <div style="position: relative; width: 100%; height: 215px;">
            <svg viewBox="0 0 700 190" style="width:100%; height:100%; overflow:visible;" preserveAspectRatio="none">
              <defs>
                <linearGradient id="mainRedGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#c8102e" stop-opacity="0.22"/>
                  <stop offset="100%" stop-color="#c8102e" stop-opacity="0.0"/>
                </linearGradient>
                <linearGradient id="mainAmberGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.18"/>
                  <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0"/>
                </linearGradient>
              </defs>

              <!-- Horizontal Gridlines -->
              <line x1="0" y1="150" x2="700" y2="150" stroke="#f1f5f9" stroke-width="1" />
              <line x1="0" y1="100" x2="700" y2="100" stroke="#f1f5f9" stroke-width="1" />
              <line x1="0" y1="50" x2="700" y2="50" stroke="#f1f5f9" stroke-width="1" />

              <!-- Amber Path (Budget Allocation) -->
              <path d="M 10 110 C 70 110, 100 145, 160 135 C 220 125, 240 65, 290 80 C 340 95, 360 150, 420 145 C 480 140, 510 80, 560 95 C 610 115, 650 125, 690 120"
                    fill="none" stroke="#f59e0b" stroke-width="2.4" />
              <path d="M 10 110 C 70 110, 100 145, 160 135 C 220 125, 240 65, 290 80 C 340 95, 360 150, 420 145 C 480 140, 510 80, 560 95 C 610 115, 650 125, 690 120 L 690 150 L 10 150 Z"
                    fill="url(#mainAmberGrad)" />

              <!-- Red Path (Actual Spend / Utilised) -->
              <path d="M 10 135 C 60 135, 110 125, 170 115 C 230 105, 260 35, 320 35 C 370 35, 380 115, 420 120 C 470 125, 500 70, 560 60 C 620 50, 660 90, 690 105"
                    fill="none" stroke="#c8102e" stroke-width="2.8" />
              <path d="M 10 135 C 60 135, 110 125, 170 115 C 230 105, 260 35, 320 35 C 370 35, 380 115, 420 120 C 470 125, 500 70, 560 60 C 620 50, 660 90, 690 105 L 690 150 L 10 150 Z"
                    fill="url(#mainRedGrad)" />

              <!-- Guideline & Indicator Point -->
              <line x1="390" y1="15" x2="390" y2="150" stroke="#94a3b8" stroke-dasharray="3 3" stroke-width="1" />
              <circle cx="390" cy="115" r="5.5" fill="#c8102e" stroke="#ffffff" stroke-width="2.5" />

              <!-- X-Axis Months -->
              <text x="25" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Apr</text>
              <text x="95" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">May</text>
              <text x="165" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Jun</text>
              <text x="235" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Jul</text>
              <text x="305" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Aug</text>
              <text x="375" y="174" font-size="13.5" fill="#0f172a" font-weight="600" font-family="inherit">Sep</text>
              <text x="445" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Oct</text>
              <text x="515" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Nov</text>
              <text x="585" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Dec</text>
              <text x="655" y="174" font-size="13" fill="#64748b" font-weight="500" font-family="inherit">Jan</text>
            </svg>

            <!-- Floating Highlight Badge -->
            <div style="position:absolute; top:36px; left:56%; transform:translateX(-50%); background:#1e1e24; color:#fff; padding:7px 14px; border-radius:10px; font-size:13px; font-weight:600; box-shadow:0 8px 20px rgba(0,0,0,0.25); text-align:center;">
              <div style="color:#fbbf24;">₹{{ number_format($totalUtilised / 10000000, 2) }} Cr</div>
              <div style="font-size:11px; color:#cbd5e1; font-weight:600;">{{ $overallUtilisation }}% Utilised</div>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between pt-3 mt-2 border-top flex-wrap gap-2" style="border-color:#f8ecee !important; font-size:13.5px; color:var(--text-muted2);">
          <span><i class="bi bi-shield-check text-success"></i> 100% Audit Trail Verified</span>
          <span class="text-dark fw-semibold" style="font-weight:600;">FY {{ $fiscalYear }} Active</span>
        </div>
      </div>
    </div>

    <!-- Right (col-6): Category Spend & Monthly Volume Bar Graph Card -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
              <div class="panel-title">Top Spend Departments &amp; Volume</div>
              <div class="d-flex align-items-center gap-3 mt-1" style="font-size:13.5px; font-weight:600; color:var(--text-muted2);">
                <span><i class="bi bi-circle-fill" style="color:#c8102e; font-size:10px;"></i> Projects</span>
                <span><i class="bi bi-circle-fill" style="color:#f59e0b; font-size:10px;"></i> Operations</span>
                <span><i class="bi bi-circle-fill" style="color:#3b82f6; font-size:10px;"></i> IT Dept</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge" style="background:#d1fae5; color:#047857; font-weight:600; font-size:12.5px; border-radius:12px; padding:5px 9px;">+12.4% YoY</span>
              <select class="form-select form-select-sm" style="width: auto; border-radius: 20px; font-size: 13px; font-weight: 600; padding: 5px 28px 5px 12px;">
                <option>Monthly View</option>
                <option>Quarterly</option>
              </select>
            </div>
          </div>

          <div class="d-flex align-items-baseline gap-2 mb-2">
            <span class="fw-semibold text-dark" style="font-size:28px; letter-spacing:-.02em; font-weight:600;">₹{{ number_format($totalUtilised, 2) }}</span>
            <span class="text-muted" style="font-size:13.5px; font-weight:600;">utilised volume tracked</span>
          </div>

          <!-- SVG Grouped Bar Chart -->
          <div style="position: relative; width: 100%; height: 215px;">
            <svg viewBox="0 0 650 190" style="width:100%; height:100%; overflow:visible;" preserveAspectRatio="none">
              <defs>
                <linearGradient id="barRedGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#ef4444"/>
                  <stop offset="100%" stop-color="#c8102e"/>
                </linearGradient>
                <linearGradient id="barAmberGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#fbbf24"/>
                  <stop offset="100%" stop-color="#d97706"/>
                </linearGradient>
                <linearGradient id="barBlueGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#60a5fa"/>
                  <stop offset="100%" stop-color="#2563eb"/>
                </linearGradient>
              </defs>

              <!-- Gridlines -->
              <line x1="0" y1="150" x2="650" y2="150" stroke="#f1f5f9" stroke-width="1"/>
              <line x1="0" y1="100" x2="650" y2="100" stroke="#f1f5f9" stroke-width="1"/>
              <line x1="0" y1="50" x2="650" y2="50" stroke="#f1f5f9" stroke-width="1"/>

              <!-- Apr -->
              <rect x="25" y="85" width="14" height="65" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="42" y="105" width="14" height="45" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="59" y="120" width="14" height="30" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="49" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">Apr</text>

              <!-- May -->
              <rect x="115" y="70" width="14" height="80" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="132" y="90" width="14" height="60" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="149" y="110" width="14" height="40" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="139" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">May</text>

              <!-- Jun -->
              <rect x="205" y="60" width="14" height="90" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="222" y="75" width="14" height="75" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="239" y="95" width="14" height="55" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="229" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">Jun</text>

              <!-- Jul -->
              <rect x="295" y="45" width="14" height="105" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="312" y="70" width="14" height="80" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="329" y="85" width="14" height="65" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="319" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">Jul</text>

              <!-- Aug -->
              <rect x="385" y="40" width="14" height="110" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="402" y="65" width="14" height="85" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="419" y="90" width="14" height="60" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="409" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">Aug</text>

              <!-- Sep (Peak Month) -->
              <rect x="475" y="25" width="14" height="125" rx="3.5" fill="url(#barRedGrad)"/>
              <rect x="492" y="50" width="14" height="100" rx="3.5" fill="url(#barAmberGrad)"/>
              <rect x="509" y="80" width="14" height="70" rx="3.5" fill="url(#barBlueGrad)"/>
              <text x="499" y="174" font-size="13.5" fill="#0f172a" font-weight="600" text-anchor="middle" font-family="inherit">Sep</text>

              <!-- Oct -->
              <rect x="565" y="50" width="14" height="100" rx="3.5" fill="url(#barRedGrad)" opacity="0.9"/>
              <rect x="582" y="80" width="14" height="70" rx="3.5" fill="url(#barAmberGrad)" opacity="0.9"/>
              <rect x="599" y="105" width="14" height="45" rx="3.5" fill="url(#barBlueGrad)" opacity="0.9"/>
              <text x="589" y="174" font-size="13" fill="#64748b" font-weight="600" text-anchor="middle" font-family="inherit">Oct</text>

              <!-- Peak volume tooltip highlight on Sep -->
              <g transform="translate(499, 8)">
                <rect x="-42" y="-20" width="84" height="26" rx="7" fill="#1e1e24"/>
                <text x="0" y="-3" text-anchor="middle" font-size="12" font-weight="600" fill="#fbbf24" font-family="inherit">₹61.2L Peak</text>
                <polygon points="-5,6 5,6 0,11" fill="#1e1e24"/>
              </g>
            </svg>
          </div>
        </div>

        <!-- Category / Department Breakdown Strip -->
        <div class="d-flex align-items-center justify-content-between pt-3 mt-2 border-top flex-wrap gap-2" style="border-color:#f8ecee !important;">
          <div class="d-flex align-items-center gap-2">
            <div style="width:11px; height:11px; border-radius:3px; background:#c8102e;"></div>
            <div>
              <div class="fw-semibold text-dark" style="font-size:13.5px; font-weight:600;">₹61.20 L</div>
              <div class="text-muted" style="font-size:12.5px; font-weight:600;">Projects (49%)</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div style="width:11px; height:11px; border-radius:3px; background:#f59e0b;"></div>
            <div>
              <div class="fw-semibold text-dark" style="font-size:13.5px; font-weight:600;">₹27.60 L</div>
              <div class="text-muted" style="font-size:12.5px; font-weight:600;">Operations (22%)</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div style="width:11px; height:11px; border-radius:3px; background:#2563eb;"></div>
            <div>
              <div class="fw-semibold text-dark" style="font-size:13.5px; font-weight:600;">₹14.30 L</div>
              <div class="text-muted" style="font-size:12.5px; font-weight:600;">IT Dept (11%)</div>
            </div>
          </div>
          <a href="{{ route('po') }}" class="btn-outline-brand py-1 px-3" style="height:32px; font-size:12.5px; font-weight:600; text-decoration:none; border-radius:8px;">
            <span>Details</span> <i class="bi bi-arrow-right" style="font-size:11px;"></i>
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Row 2: Department Budgets (Progress Bars) & Items Awaiting My Approval (col-6 / col-6) -->
  <div class="row g-3 mt-2">
    
    <!-- Department Budgets (col-6) -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="panel-title">Department Budgets &amp; Utilisation</div>
            <span class="badge" style="background:#fef2f2; color:#c8102e; font-weight:600; border-radius:20px; font-size:12px; padding:5px 10px;">
              {{ $fiscalYear }} Active
            </span>
          </div>
          <div class="panel-sub">Total ₹{{ number_format($totalAllocated / 100000, 1) }}L allocated · ₹{{ number_format($totalUtilised / 100000, 1) }}L utilised (GST-inclusive)</div>
          
          <div class="mt-3">
            @foreach ($allBudgets as $budget)
              @php
                $pct = $budget->utilisationPercent();
                $barColor = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981');
              @endphp
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $budget->department }}</span>
                  <span class="fw-semibold" style="font-size:14px; font-weight:600; color:{{ $barColor }};">
                    ₹{{ number_format($budget->utilised_amount / 100000, 1) }}L / ₹{{ number_format($budget->allocated_amount / 100000, 1) }}L ({{ $pct }}%)
                  </span>
                </div>
                <div class="progress" style="height:9px; border-radius:10px; background:#f1f5f9;">
                  <div class="progress-bar" style="width:{{ min($pct, 100) }}%; background-color:{{ $barColor }}; border-radius:10px;"></div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
        <a href="{{ route('checks-balances') }}" class="btn-outline-brand w-100 justify-content-center mt-3" style="height:42px; font-size:13.5px; font-weight:600; text-decoration:none;">
          <span>View Checks &amp; Balances</span>
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- Items Awaiting My Approval (col-6) -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
              <div class="panel-title">Items Awaiting My Approval</div>
              <div class="panel-sub">Immediate action required across your assigned duties</div>
            </div>
            <span class="status-pill status-in-stock" style="font-size:12px; padding:5px 10px; font-weight:600;">
              <i class="bi bi-check-circle-fill"></i> Ready
            </span>
          </div>

          <div class="d-flex flex-column gap-2">
            @forelse ($pendingItems as $item)
              <a href="{{ $item['url'] }}" class="d-flex justify-content-between align-items-center p-3 rounded-3" style="text-decoration:none; background:#f8fafc; border:1px solid #eef2f6; transition:all .15s ease;" onmouseover="this.style.background='#fff5f5'; this.style.borderColor='#fecaca';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#eef2f6';">
                <div>
                  <div class="fw-semibold" style="color:#0f172a; font-size:15px; font-weight:600;">{{ $item['number'] }}</div>
                  <div class="text-muted" style="font-size:13.5px; font-weight:600;">{{ $item['label'] }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="status-pill status-low-stock" style="font-size:12px; padding:4px 9px; font-weight:600;">Pending Approval</span>
                  <i class="bi bi-chevron-right" style="color:#c8102e; font-size:16px;"></i>
                </div>
              </a>
            @empty
              <div class="text-center py-4" style="color: var(--text-muted2);">
                <i class="bi bi-check2-all" style="font-size:32px; color:#10b981;"></i>
                <div class="mt-2 fw-semibold" style="font-size:14.5px; font-weight:600;">All caught up! Nothing waiting on you right now.</div>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Row 3: Recent Activity (Live Audit Trail Log) -->
  <div class="mt-3">
    <div class="card-panel">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <div class="panel-title">Recent Activity</div>
          <div class="panel-sub">Live audit trail log across procurement modules ({{ $totalAuditLogs }} entries logged)</div>
        </div>
        <a href="{{ route('audit') }}" class="small-link fw-semibold" style="color:var(--maroon); font-size:13.5px; font-weight:600;">View All</a>
      </div>
      
      <div id="activityList" class="d-flex flex-column gap-2">
        @forelse ($recentActivity as $log)
          <div class="d-flex justify-content-between align-items-center p-2 rounded-2" style="border-bottom:1px solid var(--border-lighter); font-size:13.5px;">
            <div class="d-flex align-items-center gap-2">
              <div style="width:9px; height:9px; border-radius:50%; background:#c8102e; flex:0 0 auto;"></div>
              <div>
                <span class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $log->user?->name ?? 'System' }}</span>
                <span class="text-muted"> — {{ ucfirst($log->action) }} {{ $log->module }}</span>
              </div>
            </div>
            <div class="text-muted" style="font-size:12.5px; white-space:nowrap;">{{ $log->created_at?->diffForHumans() }}</div>
          </div>
        @empty
          <div class="text-muted py-3 text-center" style="font-size:13.5px;">No activity recorded yet.</div>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'dashboard', crumb: 'Overview', title: 'Dashboard' });
@endsection
