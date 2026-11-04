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
      <p class="page-subtitle">Real-time queue of items requiring your review, action, or digital endorsement.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge {{ $pendingCount > 0 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-3 py-2">
        <i class="bi {{ $pendingCount > 0 ? 'bi-bell' : 'bi-check2-all' }} me-1"></i>
        {{ $pendingCount }} Pending {{ Str::plural('Task', $pendingCount) }}
      </span>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Awaiting Your Action</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-bell"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingCount }}</div>
          <div class="d-flex align-items-center gap-1 {{ $pendingCount > 0 ? 'text-danger' : 'text-success' }} small fw-semibold">
            <i class="bi {{ $pendingCount > 0 ? 'bi-exclamation-circle' : 'bi-check2-all' }}"></i>
            <span>{{ $pendingCount > 0 ? 'Requires your decision' : 'All queues clear' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Auto-Clear Protocol</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size:1.4rem;">Automatic</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-arrow-repeat"></i>
            <span>Clears immediately upon decision</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Institutional SLA</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-stopwatch"></i>
            </div>
          </div>
          <div class="stat-value mb-1">48h</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-speedometer2"></i>
            <span>Target turnaround window</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Queue Card -->
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
