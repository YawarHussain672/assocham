@extends('layouts.app')

@section('title', 'Cost Comparative Statement')

@php
  $statusMeta = [
    'pending_committee' => ['label' => 'Pending — Committee', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'          => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'          => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'             => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Quotes received against an RFQ are compared side-by-side with automatic L1/L2 ranking by cost, then need Procurement Committee (Admin Head) sign-off before a PO can be raised. Start a CCS from an RFQ marked "Quotes Received".</div>
    <a href="{{ route('rfq') }}" class="btn-brand">Go to RFQs</a>
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Cost Comparative Statements</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>CCS No.</div>
        <div>Linked RFQ / PR</div>
        <div>Lowest Quote (L1)</div>
        <div>Vendors Compared</div>
        <div>Status</div>
      </div>

      @forelse ($statements as $ccs)
        @php $meta = $statusMeta[$ccs->status] ?? $statusMeta['draft']; $l1 = $ccs->lowestQuote(); @endphp
        <a href="{{ route('ccs.show', $ccs) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $ccs->ccs_number }}</div>
          <div class="module-row-c1">{{ $ccs->rfq->rfq_number }} · {{ $ccs->rfq->purchaseRequisition->pr_number }}</div>
          <div class="module-row-c2">{{ $l1 ? '₹'.number_format($l1->quoted_cost, 2).' — '.$l1->vendor->company_name : '—' }}</div>
          <div class="module-row-c3">{{ $ccs->quotes->count() }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No Cost Comparative Statements yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'Cost Comparative Statement' });
@endsection
