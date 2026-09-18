@extends('layouts.app')

@section('title', 'Grievance Redressal')

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'admin_head' => ['label' => 'With Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'cfo'        => ['label' => 'With CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'sg'         => ['label' => 'With SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  ];

  $totalGrievances = $grievances->count();
  $activeGrievances = $grievances->where('status', '!=', 'resolved')->count();
  $resolvedGrievances = $grievances->where('status', 'resolved')->count();
  $seniorQueue = $grievances->whereIn('current_owner_role', ['cfo', 'sg'])->where('status', '!=', 'resolved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Grievance Redressal &amp; Incident Tracker</h1>
      <div class="page-sub" style="margin-top:0;">Structured dispute and operational roadblock ticketing. Tickets escalate strictly across HOD → Admin Head → CFO → Secretary General.</div>
    </div>
    <a href="{{ route('grievance.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
      <i class="bi bi-ticket-perforated"></i>
      <span>Raise Ticket</span>
    </a>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Tickets</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalGrievances }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">All time incident reports</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-ticket-detailed fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Active Queue</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $activeGrievances }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">In escalation chain</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-hourglass-split fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Executive Escalations</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $seniorQueue }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">With CFO or SG</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-person-badge fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Resolved Cases</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $resolvedGrievances }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Successfully mediated</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-check2-all fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 overflow-hidden">
    <div class="table-toolbar px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="table-toolbar-title d-flex align-items-center gap-2">
        <i class="bi bi-chat-left-text text-primary"></i>
        <span>Grievance Incident Roster</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalGrievances }} Tickets</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Ticket No.</div>
        <div>Subject Narrative</div>
        <div>Category</div>
        <div>Raised By</div>
        <div>Status</div>
      </div>

      @forelse ($grievances as $g)
        @php $meta = $g->status === 'resolved' ? ['label' => 'Resolved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'] : ($ownerMeta[$g->current_owner_role] ?? $ownerMeta['hod']); @endphp
        <a href="{{ route('grievance.show', $g) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi bi-ticket text-muted"></i>
            <span>{{ $g->ticket_number }}</span>
          </div>
          <div class="module-row-c1">{{ $g->subject }}</div>
          <div class="module-row-c2">
            <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11.5px; font-weight:500;">{{ $g->subject_category }}</span>
          </div>
          <div class="module-row-c3">{{ $g->raisedBy->name }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600;">{{ $meta['label'] }}</span></div>
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

