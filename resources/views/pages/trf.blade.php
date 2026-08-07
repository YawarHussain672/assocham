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
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Domestic travel needs ASG approval only (final); international travel needs mandatory SG approval. A pushback returns the TRF to the requester with review comments instead of an outright rejection.</div>
    <a href="{{ route('trf.create') }}" class="btn-brand">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
      New Travel Requisition
    </a>
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Travel Requisition Forms</div>
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
          <div class="module-row-id">{{ $trf->trf_number }}</div>
          <div class="module-row-c1">{{ $trf->employee->name }} @if($trf->is_international)<span class="badge-soft" style="background:#EFE6FB; color:#5B3A9E;">Intl</span>@endif</div>
          <div class="module-row-c2">{{ $trf->route ?: '—' }}</div>
          <div class="module-row-c3">
            @if ($trf->travel_start)
              {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
            @else
              —
            @endif
          </div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No travel requisitions yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'Travel Requisition' });
@endsection
