@extends('layouts.app')

@section('title', 'Notifications & Action Center')

@php
  $pendingCount = count($items);
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Action Center &amp; Pending Approvals</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge {{ $pendingCount > 0 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-3 py-2">
        <i class="bi {{ $pendingCount > 0 ? 'bi-bell' : 'bi-check2-all' }} me-1"></i>
        {{ $pendingCount }} Pending {{ Str::plural('Task', $pendingCount) }}
      </span>
    </div>
  </div>

  <!-- Summary Cards -->
  <!-- Status KPI Tab Cards (Action Required, Auto-Clear, SLA) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Action Due</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-bell"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingCount }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Protocol</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">Auto-Clear</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">SLA</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-stopwatch"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">48h</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Queue Card -->
  @if (!empty($budgetAlerts) && $budgetAlerts->isNotEmpty())
    <div class="card shadow-sm border-0 mb-3 overflow-hidden">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill text-danger"></i>
          <h6 class="mb-0 fw-bold text-dark">90% Budget Utilisation Alerts</h6>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
          {{ $budgetAlerts->count() }} {{ Str::plural('Department', $budgetAlerts->count()) }} Flagged
        </span>
      </div>
      <div class="list-group list-group-flush">
        @foreach ($budgetAlerts as $alertDept)
          <div class="list-group-item px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="fw-bold text-dark" style="font-size:14px;">
                {{ $alertDept->department }} ({{ $alertDept->fiscal_year }}) — {{ $alertDept->utilisationPercent() }}% Utilised
              </div>
              <div class="text-muted small">
                ₹{{ number_format((float) $alertDept->utilised_amount, 2) }} used of ₹{{ number_format((float) $alertDept->allocated_amount, 2) }} limit ·
                <strong class="text-danger">₹{{ number_format($alertDept->remainingAmount(), 2) }} remaining before overrun</strong>
              </div>
            </div>
            <a href="{{ route('checks-balances') }}" class="btn btn-sm btn-outline-danger fw-semibold text-nowrap">
              Review in Budget Master →
            </a>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if (!empty($grievanceUpdates) && $grievanceUpdates->isNotEmpty())
    <div class="card shadow-sm border-0 mb-3 overflow-hidden">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-chat-square-text-fill text-primary"></i>
          <h6 class="mb-0 fw-bold text-dark">Grievance Status Updates</h6>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
          {{ $grievanceUpdates->count() }} Unread {{ Str::plural('Update', $grievanceUpdates->count()) }}
        </span>
      </div>
      <div class="list-group list-group-flush">
        @foreach ($grievanceUpdates as $gUpdate)
          <div class="list-group-item px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div style="min-width:0;">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge {{ $gUpdate->status === 'resolved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }}">
                  {{ $gUpdate->actionLabel() }}
                </span>
                <span class="fw-bold text-dark" style="font-size:13.5px;">
                  {{ $gUpdate->grievance?->ticket_number }} — {{ $gUpdate->grievance?->subject }}
                </span>
              </div>
              <div class="text-dark small">{{ $gUpdate->message }}</div>
              <div class="text-muted mt-1" style="font-size:11.5px;">
                {{ $gUpdate->actor?->name ?? 'Handler' }} ({{ \App\Support\Roles::label($gUpdate->owner_role) }}) · {{ $gUpdate->created_at?->format('d M Y, H:i') }}
              </div>
            </div>
            @if ($gUpdate->grievance)
              <a href="{{ route('grievance.show', $gUpdate->grievance) }}" class="btn btn-sm btn-outline-primary fw-semibold text-nowrap">
                Track Ticket →
              </a>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold">Pending Action Queue</h6>
        <span class="badge bg-light text-dark">Click any row to open decision workspace</span>
      </div>
      <span class="badge {{ $pendingCount > 0 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}">
        {{ $pendingCount }} Pending
      </span>
    </div>

    <div class="card-body p-3">
      <div class="d-flex flex-column gap-2" id="notificationsList">
        @forelse ($items as $item)
          <a href="{{ $item['url'] }}" class="p-3 border rounded-3 text-decoration-none d-flex justify-content-between align-items-center transition-all bg-white hover-shadow">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-sm rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:40px; height:40px; font-size:18px;">
                <i class="bi bi-clock-history"></i>
              </div>
              <div>
                <div class="fw-bold text-dark font-monospace" style="font-size:14px;">{{ $item['number'] }}</div>
                <div class="text-muted d-flex align-items-center gap-2 mt-0.5 small">
                  <span>{{ $item['label'] }}</span>
                  <span>•</span>
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Awaiting Decision</span>
                </div>
              </div>
            </div>
            <div>
              <span class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <span>Review Now</span>
                <i class="bi bi-arrow-right"></i>
              </span>
            </div>
          </a>
        @empty
          <div class="p-5 text-center text-muted border rounded-3 bg-light" style="border-style:dashed !important;">
            <i class="bi bi-check-circle-fill text-success" style="font-size:42px;"></i>
            <div class="mt-3 fw-bold text-dark fs-6">All Clear! Nothing waiting on you right now</div>
            <div class="text-muted small mt-1" style="max-width:420px; margin:0 auto;">
              There are currently no requisitions, purchase orders, travel vouchers, or grievances assigned to your queue.
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'notifications', crumb: 'System', title: 'Notifications' });
@endsection
