@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="view-wrap-audit" id="pageContent">
  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Immutable, system-generated log of every create, edit and approval across every module — captured automatically, not entered by hand. Showing the latest 200 entries.</div>
  </div>
  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="audit-row audit-head">
      <div>Timestamp</div><div>User</div><div>Action</div><div>Module / Reference</div>
    </div>
    <div id="auditList">
      @forelse ($logs as $log)
        <div class="audit-row">
          <div>{{ $log->created_at?->format('d M, H:i') }}</div>
          <div>{{ $log->user?->name ?? 'System' }}</div>
          <div>{{ ucfirst($log->action) }}{{ $log->detail ? ' — '.$log->detail : '' }}</div>
          <div>{{ $log->module }}{{ $log->reference ? ' · '.$log->reference : '' }}</div>
        </div>
      @empty
        <div class="p-4 text-center text-muted">No audit activity recorded yet.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'audit', crumb: 'Governance', title: 'Audit Trail' });
@endsection
