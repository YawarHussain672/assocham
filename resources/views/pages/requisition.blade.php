@extends('layouts.app')

@section('title', 'Purchase Requisition')

@php
  $statusMeta = [
    'pending_l1'  => ['label' => 'Pending — L1 (Basu)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l2'  => ['label' => 'Pending — L2 (Ambika)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l3'  => ['label' => 'Pending — L3 (CFO)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l4'  => ['label' => 'Pending — L4 (SG)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Requisitions route through a fixed four-level chain — Basu (L1) → Ambika (L2) → CFO (L3) → SG (L4) — with a real-time GST-inclusive budget check against each department's fiscal-year allocation.</div>
    @if ($canCreate)
      <a href="{{ route('requisition.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        New Requisition
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="stat-strip">
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Total PRs</div>
        <div class="stat-count">{{ $prs->count() }}</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Approved</div>
        <div class="stat-count">{{ $prs->where('status', 'approved')->count() }}</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">In Approval Chain</div>
        <div class="stat-count">{{ $prs->whereIn('status', ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4'])->count() }}</div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Purchase Requisitions</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>PR No.</div>
        <div>Department</div>
        <div>Est. Cost (GST-incl.)</div>
        <div>Required By</div>
        <div>Status</div>
      </div>

      @forelse ($prs as $pr)
        @php $meta = $statusMeta[$pr->status] ?? $statusMeta['draft']; @endphp
        <a href="{{ route('requisition.show', $pr) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $pr->pr_number }}</div>
          <div class="module-row-c1">{{ $pr->department }} @if($pr->sole_source)<span class="badge-soft" style="background:#EFE6FB; color:#5B3A9E;">Sole Source</span>@endif</div>
          <div class="module-row-c2">₹{{ number_format($pr->estimatedCostInclGst(), 2) }} · {{ $pr->items->count() }} item{{ $pr->items->count() === 1 ? '' : 's' }}</div>
          <div class="module-row-c3">{{ optional($pr->required_by)->format('d M Y') ?? '—' }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No purchase requisitions yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'Purchase Requisition' });
@endsection
