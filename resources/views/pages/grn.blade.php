@extends('layouts.app')

@section('title', 'Goods Receipt (GRN)')

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending — HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Closed', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Goods Receipt Notes are raised against an issued PO, capturing received quantity, invoice number and quality remarks. Once submitted, only quantity and date remain editable — everything else is locked after HOD approval closes the record.</div>
    @if ($canCreate)
      <a href="{{ route('grn.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        Log Goods Receipt
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Goods Receipt Notes</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>GRN No.</div>
        <div>Linked PO / Vendor</div>
        <div>Qty Received</div>
        <div>Receipt Date</div>
        <div>Status</div>
      </div>

      @forelse ($grns as $grn)
        @php $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod']; @endphp
        <a href="{{ route('grn.show', $grn) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $grn->grn_number }}</div>
          <div class="module-row-c1">{{ $grn->purchaseOrder->po_number }} · {{ $grn->purchaseOrder->vendor->company_name }}</div>
          <div class="module-row-c2">{{ $grn->received_qty }} / {{ $grn->po_qty }}</div>
          <div class="module-row-c3">{{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No Goods Receipt Notes logged yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Goods Receipt (GRN)' });
@endsection
