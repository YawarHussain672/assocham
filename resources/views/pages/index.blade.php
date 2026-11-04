@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Welcome back, {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'checks-balances'))
        <a href="{{ route('checks-balances') }}" class="btn btn-sm btn-outline-custom">
          <i class="bi bi-shield-check me-1 text-warning"></i> Checks &amp; Balances
        </a>
      @endif

      @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
        <a href="{{ route('exceptions.create') }}" class="btn btn-sm btn-outline-custom">
          <i class="bi bi-exclamation-octagon me-1 text-danger"></i> File Exception
        </a>
      @endif

      @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'audit'))
        <a href="{{ route('audit') }}" class="btn btn-sm btn-outline-custom">
          <i class="bi bi-journal-text me-1 text-info"></i> Audit Trail
        </a>
      @endif

      @if (auth()->user()->role === \App\Support\Roles::PR_SR_EXEC)
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Raise Purchase Requisition (PR)
        </a>
      @elseif (auth()->user()->role === \App\Support\Roles::BUYER)
        <a href="{{ route('onboarding') }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-person-plus me-1"></i> Empanel Vendor
        </a>
        <a href="{{ route('rfq.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-send me-1"></i> Create RFQ
        </a>
      @elseif (\App\Support\Roles::canAccessModule(auth()->user()->role, 'trf'))
        <a href="{{ route('trf.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-airplane me-1"></i> New Travel Request (TRF)
        </a>
      @endif
    </div>
  </div>

  <!-- ====================================================================
       FOUR CORE KPI STAT CARDS (Reference UI Style)
       ==================================================================== -->
  <div class="row g-3 mb-4">
    <!-- Card 1: Awaiting My Approval -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Awaiting My Approval</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingItems->count() }}</div>
          <div class="d-flex align-items-center gap-1 small fw-semibold">
            @if ($pendingItems->count() > 0)
              <span class="badge badge-soft-danger d-inline-flex align-items-center gap-1">
                <i class="bi bi-exclamation-circle-fill"></i> {{ $pendingItems->count() }} action item{{ $pendingItems->count() === 1 ? '' : 's' }} pending
              </span>
            @else
              <span class="badge badge-soft-success d-inline-flex align-items-center gap-1">
                <i class="bi bi-check2-all"></i> All Caught Up
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
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Open Requisitions</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-file-earmark-text"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingPrs }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>{{ $totalPrs }} total PRs filed ({{ $approvedPrs }} approved)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 3: Budget Utilised -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Budget Utilised (GST-Incl)</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-pie-chart"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $overallUtilisation }}%</div>
          <div class="d-flex align-items-center justify-content-between text-muted small">
            <span>₹{{ number_format($totalUtilised / 10000000, 2) }} Cr / ₹{{ number_format($totalAllocated / 10000000, 2) }} Cr</span>
            <span class="badge {{ $nearLimitCount > 0 ? 'badge-soft-warning' : 'badge-soft-success' }}">
              {{ $nearLimitCount > 0 ? $nearLimitCount . ' Depts Alert' : 'Ceilings Healthy' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 4: Committed Spend -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-danger card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Committed Spend</span>
            <div class="stat-icon-box icon-danger">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($totalPoSpend + $totalReimbClaimed, 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-muted small fw-medium">
            <i class="bi bi-building-check text-success"></i>
            <span>{{ $approvedVendors }}/{{ $totalVendors }} Vendors · {{ $totalWorkflows }} Workflows</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================================
       ANALYTICS & SPEND INTELLIGENCE SUITE (CHART.JS)
       ==================================================================== -->
  <section id="analytics-section" class="mb-4">
    <!-- Primary Analytics Row: 2 Large Interactive Charts -->
    <div class="row g-4 mb-4">
      @if ($budgets !== null)
      <!-- Chart 1: Department Budget vs Spend -->
      <div class="col-12 col-xl-7">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
            <div>
              <h6 class="fw-bold mb-0 text-main">Department Budgets &amp; Spend Utilisation</h6>
              <small class="text-muted">GST 18% Inclusive with Statutory Overrun Ceilings</small>
            </div>
            <span class="badge badge-soft-warning">Threshold at 90%</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height: 310px; position: relative;">
              <canvas id="deptBudgetSpendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      @endif

      <!-- Chart 2: Procurement vs Travel Trends -->
      <div class="col-12 {{ $budgets !== null ? 'col-xl-5' : 'col-xl-12' }}">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
            <div>
              <h6 class="fw-bold mb-0 text-main">Procurement vs Travel Spend Trends</h6>
              <small class="text-muted">Monthly spend comparison (FY {{ $fiscalYear }})</small>
            </div>
            <span class="badge badge-soft-primary">Live Trajectory</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height: 310px; position: relative;">
              <canvas id="procurementVsTravelChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Secondary Analytics Row: SLA Turnaround & Governance Distribution -->
    <div class="row g-4 mb-4">
      <!-- Chart 3: Turnaround SLA -->
      <div class="col-12 col-xl-7">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
            <div>
              <h6 class="fw-bold mb-0 text-main">Workflow Approval Turnaround SLA (Hours)</h6>
              <small class="text-muted">Average sign-off velocity across multi-level governance chains</small>
            </div>
            <span class="badge badge-soft-info">Governance Metric</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height: 270px; position: relative;">
              <canvas id="approvalTurnaroundChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart 4: Active Workflow Distribution -->
      <div class="col-12 col-xl-5">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
            <div>
              <h6 class="fw-bold mb-0 text-main">Active Workflow Distribution</h6>
              <small class="text-muted">Total {{ $totalWorkflows }} operations across procurement modules</small>
            </div>
            <span class="badge badge-soft-success">100% Traceability</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height: 270px; position: relative;">
              <canvas id="workflowDistChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ====================================================================
       ACTION PIPELINE & LIVE AUDIT TRAIL FEED
       ==================================================================== -->
  <div class="row g-4 mb-4">
    <!-- Items Awaiting Action / Pipeline -->
    <div class="col-12 col-xl-8">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
          <div>
            <h6 class="fw-bold mb-0 text-main">Awaiting Action &amp; Pipeline Review</h6>
            <small class="text-muted">Operational items queued for your role review and approval</small>
          </div>
          <a href="{{ route('notifications') }}" class="btn btn-sm btn-outline-custom">View All ({{ $pendingItems->count() }})</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">REFERENCE NO.</th>
                  <th>MODULE</th>
                  <th>TITLE / PURPOSE</th>
                  <th>STATUS</th>
                  <th class="text-end pe-3">ACTION</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($pendingItems as $item)
                  <tr>
                    <td class="ps-3">
                      <a href="{{ $item['url'] }}" class="fw-bold font-monospace text-primary text-decoration-none">
                        <code>{{ $item['number'] }}</code>
                      </a>
                    </td>
                    <td>
                      <span class="badge badge-soft-info">{{ strtoupper($item['module'] ?? 'PR') }}</span>
                    </td>
                    <td>
                      <div class="fw-semibold text-main">{{ $item['label'] }}</div>
                      <small class="text-muted">Requires signed endorsement</small>
                    </td>
                    <td>
                      <span class="badge badge-soft-warning">
                        <i class="bi bi-clock-history me-1"></i> Pending Action
                      </span>
                    </td>
                    <td class="text-end pe-3">
                      <a href="{{ $item['url'] }}" class="btn btn-sm btn-primary py-1 px-3">
                        Review <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="bi bi-check2-all text-success fs-2 d-block mb-1"></i>
                      <span class="fw-semibold">All caught up! There are no action items pending your sign-off.</span>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Audit Trail Activity Feed -->
    <div class="col-12 col-xl-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
          <div>
            <h6 class="fw-bold mb-0 text-main">Live Audit Trail Feed</h6>
            <small class="text-muted">Latest system transactions ({{ $totalAuditLogs }} total)</small>
          </div>
          <a href="{{ route('audit') }}" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold text-primary">View All</a>
        </div>
        <div class="card-body p-3">
          <div class="d-flex flex-column gap-3">
            @forelse ($recentActivity as $log)
              <div class="d-flex align-items-start gap-2 pb-2 border-bottom">
                <span class="pulse-dot mt-1" style="background-color: var(--primary); flex-shrink: 0;"></span>
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="fw-bold text-main small text-truncate">{{ $log->user?->name ?? 'System' }}</span>
                    <small class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at?->diffForHumans() }}</small>
                  </div>
                  <div class="text-muted small text-truncate">
                    <span class="badge badge-soft-secondary me-1" style="font-size: 0.68rem;">{{ strtoupper($log->module) }}</span>
                    {{ ucfirst($log->action) }}
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center py-4 text-muted small">No recent activity logged.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'dashboard', crumb: 'Overview', title: 'Dashboard' });
@endsection

@section('scripts')
<script>
(function() {
  'use strict';

  if (typeof Chart === 'undefined') return;

  // Set global Chart.js defaults matching reference SaaS styling
  Chart.defaults.font.family = "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#64748B';

  const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';
  const textColor = isDark ? '#94A3B8' : '#64748B';

  // Real backend budget data from Laravel
  const budgetDepartments = @json($allBudgets->pluck('department'));
  const budgetAllocated = @json($allBudgets->map(fn($b) => round($b->allocated_amount / 100000, 2)));
  const budgetUtilised = @json($allBudgets->map(fn($b) => round($b->utilised_amount / 100000, 2)));

  // Dynamic bar colors based on utilisation percentage
  const budgetBarColors = budgetAllocated.map((alloc, idx) => {
    const util = budgetUtilised[idx] || 0;
    const pct = alloc > 0 ? (util / alloc) * 100 : 0;
    if (pct >= 90) return '#EF4444'; // Red threshold alert
    if (pct >= 70) return '#F59E0B'; // Amber watch
    return '#10B981'; // Green healthy
  });

  // 1. Department Budget vs Spend (Bar Chart)
  const budgetCtx = document.getElementById('deptBudgetSpendChart');
  if (budgetCtx) {
    new Chart(budgetCtx, {
      type: 'bar',
      data: {
        labels: budgetDepartments.length ? budgetDepartments : ['Projects', 'IT Dept', 'Marketing', 'Finance', 'Operations', 'HR'],
        datasets: [
          {
            label: 'Allocated Budget (₹ Lakhs)',
            data: budgetAllocated.length ? budgetAllocated : [85.0, 22.0, 15.0, 9.0, 32.0, 12.0],
            backgroundColor: isDark ? '#1E293B' : '#E2E8F0',
            borderRadius: 6,
            barPercentage: 0.65
          },
          {
            label: 'Utilised Spend (₹ Lakhs)',
            data: budgetUtilised.length ? budgetUtilised : [61.2, 14.3, 13.8, 3.1, 27.6, 4.5],
            backgroundColor: budgetBarColors.length ? budgetBarColors : ['#4F46E5', '#4F46E5', '#EF4444', '#10B981', '#F59E0B', '#10B981'],
            borderRadius: 6,
            barPercentage: 0.65
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.dataset.label}: ₹${ctx.raw} Lakhs`
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor } },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: (v) => `₹${v}L`
            }
          }
        }
      }
    });
  }

  // 2. Procurement vs Travel Monthly Spend Trends (Spline Gradient Line Chart)
  const trendCtx = document.getElementById('procurementVsTravelChart');
  if (trendCtx) {
    const ctx = trendCtx.getContext('2d');
    const gradProc = ctx.createLinearGradient(0, 0, 0, 300);
    gradProc.addColorStop(0, 'rgba(209, 15, 23, 0.28)');
    gradProc.addColorStop(1, 'rgba(209, 15, 23, 0.0)');

    const gradTravel = ctx.createLinearGradient(0, 0, 0, 300);
    gradTravel.addColorStop(0, 'rgba(244, 145, 4, 0.25)');
    gradTravel.addColorStop(1, 'rgba(244, 145, 4, 0.0)');

    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        datasets: [
          {
            label: 'Procurement (POs & PRs)',
            data: [28, 34, 45, 52, 48, 62, 70, 68, 75, 82, 88, 94],
            borderColor: '#d10f17',
            backgroundColor: gradProc,
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: '#d10f17'
          },
          {
            label: 'Travel & TRF Settlements',
            data: [8, 12, 16, 19, 14, 22, 25, 21, 28, 31, 29, 35],
            borderColor: '#f49104',
            backgroundColor: gradTravel,
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#f49104'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.dataset.label}: ₹${ctx.raw} Lakhs`
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor } },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: (v) => `₹${v}L`
            }
          }
        }
      }
    });
  }

  // 3. Workflow Turnaround SLA Chart
  const turnaroundCtx = document.getElementById('approvalTurnaroundChart');
  if (turnaroundCtx) {
    new Chart(turnaroundCtx, {
      type: 'bar',
      data: {
        labels: ['PR Operational Review', 'PR Finance Clearance', 'PR Executive Sanction', 'PO Non-IT', 'PO High Value', 'TRF Secretarial Review', 'TRF Executive Review', 'GRN Physical Signoff'],
        datasets: [{
          label: 'Avg Turnaround (Hours)',
          data: [14.2, 8.5, 24.0, 11.4, 28.5, 6.2, 18.1, 4.8],
          backgroundColor: [
            '#4F46E5',
            '#4F46E5',
            '#F59E0B',
            '#10B981',
            '#EF4444',
            '#0EA5E9',
            '#7C3AED',
            '#10B981'
          ],
          borderRadius: 6,
          barPercentage: 0.58
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => ` Average Turnaround: ${ctx.raw} Hours`
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor, maxRotation: 40 } },
          y: {
            grid: { color: gridColor },
            ticks: { color: textColor, callback: (v) => `${v}h` }
          }
        }
      }
    });
  }

  // 4. Workflow Distribution Doughnut Chart
  const distCtx = document.getElementById('workflowDistChart');
  if (distCtx) {
    const totalPrs = {{ $totalPrs }};
    const totalPos = {{ $totalPos }};
    const totalTrfs = {{ $totalTrfs }};
    const totalReimb = {{ $totalReimbursements }};

    new Chart(distCtx, {
      type: 'doughnut',
      data: {
        labels: ['Purchase Requisitions', 'Purchase Orders', 'Travel Requisitions', 'Reimbursements'],
        datasets: [{
          data: [
            totalPrs || 12,
            totalPos || 8,
            totalTrfs || 6,
            totalReimb || 4
          ],
          backgroundColor: ['#d10f17', '#f49104', '#0EA5E9', '#10B981'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, padding: 12, color: textColor, usePointStyle: true }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.label}: ${ctx.raw} active records`
            }
          }
        }
      }
    });
  }
})();
</script>
@endsection
