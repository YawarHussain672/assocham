@extends('layouts.app')

@section('title', 'Travel Requisition')

@php
  $statusMeta = [
    'pending_asg' => ['label' => 'Pending — ASG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'pushed_back' => ['label' => 'Pushed Back', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];

  $totalTrfs = $trfs->count();
  $pendingAsg = $trfs->where('status', 'pending_asg')->count();
  $pendingSg = $trfs->where('status', 'pending_sg')->count();
  $approvedTrfs = $trfs->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Travel Requisitions &amp; Logistics</h1>
      <div class="page-sub" style="margin-top:0;">Domestic travel clears via ASG; international travel routes directly to SG. Requisitions allow advance fund disbursement and hotel/flight booking.</div>
    </div>
    <a href="{{ route('trf.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
      <i class="bi bi-plus-lg"></i>
      <span>New Travel Requisition</span>
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
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total TRFs</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalTrfs }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Official trip itineraries</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-airplane fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Pending ASG Review</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingAsg }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Domestic itineraries</div>
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
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Pending SG Clearance</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingSg }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">International missions</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-globe fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Cleared Trips</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $approvedTrfs }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Authorized for travel</div>
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
        <i class="bi bi-card-checklist text-primary"></i>
        <span>Travel Requisition Roster</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalTrfs }} Entries</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>TRF No.</div>
        <div>Employee</div>
        <div>Route</div>
        <div>Travel Dates</div>
        <div>Status</div>
      </div>

      @forelse ($trfs as $trf)
        @php $meta = $statusMeta[$trf->status] ?? $statusMeta['draft']; @endphp
        <a href="{{ route('trf.show', $trf) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi bi-airplane text-muted"></i>
            <span>{{ $trf->trf_number }}</span>
          </div>
          <div class="module-row-c1">{{ $trf->employee->name }} @if($trf->is_international)<span class="badge-soft ms-1" style="background:#EFE6FB; color:#5B3A9E; font-size:11px;">Intl</span>@endif</div>
          <div class="module-row-c2">{{ $trf->route ?: '—' }}</div>
          <div class="module-row-c3">
            @if ($trf->travel_start)
              {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
            @else
              —
            @endif
          </div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600;">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No travel requisitions recorded yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'Travel Requisition' });
@endsection
