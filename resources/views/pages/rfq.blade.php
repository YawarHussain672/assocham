@extends('layouts.app')

@section('title', 'RFQ & Quotations')

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'dispatched'      => ['label' => 'Dispatched', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'quotes_received' => ['label' => 'Quotes Received', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'],
    'approved'        => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">RFQs can only be raised against an already-approved Purchase Requisition, keeping every quotation request traceable to a sanctioned need.</div>
    @if ($canCreate)
      <a href="{{ route('rfq.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        New RFQ
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Requests for Quotation</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>RFQ No.</div>
        <div>Linked PR</div>
        <div>Vendors</div>
        <div>Requisition Date</div>
        <div>Status</div>
      </div>

      @forelse ($rfqs as $rfq)
        @php $meta = $statusMeta[$rfq->status] ?? $statusMeta['draft']; @endphp
        <a href="{{ route('rfq.show', $rfq) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $rfq->rfq_number }}</div>
          <div class="module-row-c1">{{ $rfq->purchaseRequisition->pr_number }}</div>
          <div class="module-row-c2">{{ $rfq->rfqVendors->pluck('vendor.company_name')->filter()->join(', ') ?: '—' }}</div>
          <div class="module-row-c3">{{ optional($rfq->requisition_date)->format('d M Y') ?? '—' }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No RFQs raised yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ & Quotations' });
@endsection
