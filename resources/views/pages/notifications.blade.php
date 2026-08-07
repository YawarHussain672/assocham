@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="view-wrap-notif" id="pageContent">
  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Everything currently waiting on your action, across every module. An item clears itself once you act on it — there's nothing to mark as read.</div>
  </div>

  <div class="card-panel p-0 mt-3 overflow-hidden" id="notificationsList">
    @forelse ($items as $item)
      <a href="{{ $item['url'] }}" class="d-flex justify-content-between align-items-center px-3 py-3" style="text-decoration:none; border-bottom:1px solid var(--border-lighter);">
        <div class="d-flex align-items-center gap-3">
          <span class="stat-icon-circle" style="background:#FBF0DC; color:#8A5A12; width:36px; height:36px;">
            <i class="bi bi-bell" style="font-size:15px;"></i>
          </span>
          <div>
            <div class="field-value">{{ $item['number'] }}</div>
            <div class="field-label" style="text-transform:none; letter-spacing:0;">{{ $item['label'] }} — awaiting your approval</div>
          </div>
        </div>
        <i class="bi bi-chevron-right" style="color:#B0A69D;"></i>
      </a>
    @empty
      <div class="p-4 text-center text-muted">Nothing waiting on you right now.</div>
    @endforelse
  </div>
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'notifications', crumb: 'System', title: 'Notifications' });
@endsection
