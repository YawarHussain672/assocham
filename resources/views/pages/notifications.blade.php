@extends('layouts.app')

@section('title', 'Notifications & Action Center')

@php
  $pendingCount = count($items);
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Action Center &amp; Pending Approvals</h1>
      <div class="page-sub" style="margin-top:0;">Real-time queue of requisitions, purchase orders, travel requests, and exceptions requiring your role's sign-off or review. Items clear automatically once decided.</div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Awaiting Your Action</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingCount }} {{ Str::plural('Task', $pendingCount) }}</div>
            <div class="mt-1" style="font-size:11.5px; color:{{ $pendingCount > 0 ? '#b0263a' : '#2e7d4f' }}; font-weight:600;">
              {{ $pendingCount > 0 ? 'Requires your decision' : 'All queues clear' }}
            </div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-bell fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Auto-Clear Protocol</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">Automatic</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Clears immediately upon approval/rejection</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-check2-all fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Institutional SLA</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">48 Hours</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Target turnaround window per tier</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-stopwatch fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Queue Panel -->
  <div class="card-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <span class="stat-icon-circle" style="background:#f8fafc; color:#1e293b; width:34px; height:34px; font-size:14px;">
          <i class="bi bi-inbox-fill"></i>
        </span>
        <div>
          <h2 class="h6 mb-0" style="font-weight:600;">Pending Action Queue</h2>
          <div class="text-muted" style="font-size:12px;">Click any row to open the decision workspace</div>
        </div>
      </div>
      <span class="badge" style="background:{{ $pendingCount > 0 ? '#fbf0dc' : '#e6f3ec' }}; color:{{ $pendingCount > 0 ? '#8a5a12' : '#2e7d4f' }}; font-weight:600;">
        {{ $pendingCount }} Pending
      </span>
    </div>

    <div class="d-flex flex-column gap-2" id="notificationsList">
      @forelse ($items as $item)
        <a href="{{ $item['url'] }}" class="p-3 border rounded-3 text-decoration-none d-flex justify-content-between align-items-center transition-all bg-white hover-shadow" style="border-color:#e2e8f0;">
          <div class="d-flex align-items-center gap-3">
            <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706; width:38px; height:38px;">
              <i class="bi bi-clock-history fs-6"></i>
            </span>
            <div>
              <div class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $item['number'] }}</div>
              <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size:12.5px;">
                <span>{{ $item['label'] }}</span>
                <span>•</span>
                <span class="badge" style="background:#fffbeb; color:#b45309; font-weight:600; font-size:11px;">Awaiting Your Decision</span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="btn-brand py-1 px-3 d-inline-flex align-items-center gap-1" style="font-size:12px; font-weight:600;">
              <span>Review Now</span>
              <i class="bi bi-arrow-right"></i>
            </span>
          </div>
        </a>
      @empty
        <div class="p-5 text-center text-muted border rounded-3" style="background:#f8fafc; border-style:dashed !important;">
          <i class="bi bi-check-circle-fill text-success" style="font-size:42px;"></i>
          <div class="mt-3 fw-semibold text-dark" style="font-size:15px; font-weight:600;">All Clear! No Pending Approvals</div>
          <div class="text-muted mt-1" style="font-size:13px; max-width:420px; margin:0 auto;">
            There are currently no requisitions, purchase orders, travel vouchers, or grievances assigned to your queue.
          </div>
        </div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'notifications', crumb: 'System', title: 'Notifications' });
@endsection

