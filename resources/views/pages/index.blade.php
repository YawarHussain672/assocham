@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="view-wrap" id="pageContent">
  <div class="fade-in">
    <div class="page-greeting">Welcome back, {{ auth()->user()->name }}</div>
    <div class="page-greeting-sub">{{ auth()->user()->roleLabel() }} · here's what's outstanding right now.</div>
  </div>

  <div class="stat-strip">
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Awaiting My Approval</div>
        <div class="stat-count">{{ $pendingItems->count() }}</div>
      </div>
    </div>
    @if ($budgets !== null)
      <div class="stat-card">
        <div class="stat-body">
          <div class="stat-label">Departments Near Limit</div>
          <div class="stat-count">{{ $budgets->filter->isNearLimit()->count() }} / {{ $budgets->count() }}</div>
        </div>
      </div>
    @endif
  </div>

  <div class="dash-charts-row mt-3">
    <div class="card-panel">
      <div class="panel-head-row mb-1">
        <div>
          <div class="panel-title">Items Awaiting My Approval</div>
          <div class="panel-sub">Across every module you can act on</div>
        </div>
      </div>
      @forelse ($pendingItems as $item)
        <a href="{{ $item['url'] }}" class="d-flex justify-content-between align-items-center py-2" style="text-decoration:none; border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">{{ $item['number'] }}</div>
            <div class="field-label" style="text-transform:none; letter-spacing:0;">{{ $item['label'] }}</div>
          </div>
          <i class="bi bi-chevron-right" style="color:#B0A69D;"></i>
        </a>
      @empty
        <div class="text-muted py-3">Nothing waiting on you right now.</div>
      @endforelse
    </div>

    @if ($budgets !== null)
      <div class="card-panel">
        <div class="panel-title">Department Budgets</div>
        <div class="panel-sub">{{ \App\Models\DepartmentBudget::currentFiscalYear() }}, GST-inclusive</div>
        @foreach ($budgets as $budget)
          @php
            $pct = $budget->utilisationPercent();
            $barColor = $pct >= 90 ? '#B0263A' : ($pct >= 70 ? '#B5601A' : '#2E7D4F');
          @endphp
          <div class="mt-2">
            <div class="d-flex justify-content-between" style="font-size:12.5px;">
              <span>{{ $budget->department }}</span>
              <span class="text-muted">{{ $pct }}%</span>
            </div>
            <div class="progress" style="height:6px;">
              <div class="progress-bar" style="width:{{ min($pct, 100) }}%; background-color:{{ $barColor }};"></div>
            </div>
          </div>
        @endforeach
        <a href="{{ route('checks-balances') }}" class="small-link d-block mt-3">View Checks & Balances</a>
      </div>
    @endif
  </div>

  <div class="card-panel mt-3">
    <div class="panel-head-row mb-1">
      <div class="panel-title">Recent Activity</div>
      <a href="{{ route('audit') }}" class="small-link">View all</a>
    </div>
    <div id="activityList">
      @forelse ($recentActivity as $log)
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter); font-size:13px;">
          <div>
            <span class="field-value">{{ $log->user?->name ?? 'System' }}</span>
            <span class="text-muted"> — {{ ucfirst($log->action) }} {{ $log->module }} {{ $log->reference ? '('.$log->reference.')' : '' }}</span>
          </div>
          <div class="text-muted" style="font-size:12px;">{{ $log->created_at?->diffForHumans() }}</div>
        </div>
      @empty
        <div class="text-muted py-3">No activity recorded yet.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'dashboard', crumb: 'Overview', title: 'Dashboard' });
@endsection
