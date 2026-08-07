@extends('layouts.app')

@section('title', 'Grievance Redressal')

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'admin_head' => ['label' => 'With Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'cfo'        => ['label' => 'With CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'sg'         => ['label' => 'With SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Any user can raise a ticket. Every ticket follows a fixed escalation path — HOD → Admin Head → CFO → SG — and stays visible on this tracker until resolved.</div>
    <a href="{{ route('grievance.create') }}" class="btn-brand">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
      Raise Ticket
    </a>
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Grievance Tracker</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Ticket No.</div>
        <div>Subject</div>
        <div>Category</div>
        <div>Raised By</div>
        <div>Status</div>
      </div>

      @forelse ($grievances as $g)
        @php $meta = $g->status === 'resolved' ? ['label' => 'Resolved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'] : ($ownerMeta[$g->current_owner_role] ?? $ownerMeta['hod']); @endphp
        <a href="{{ route('grievance.show', $g) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $g->ticket_number }}</div>
          <div class="module-row-c1">{{ $g->subject }}</div>
          <div class="module-row-c2">{{ $g->subject_category }}</div>
          <div class="module-row-c3">{{ $g->raisedBy->name }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No grievances raised yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Redressal' });
@endsection
