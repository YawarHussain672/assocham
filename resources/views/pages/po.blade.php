@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Auto-populated from an approved Cost Comparative Statement's L1 vendor. Routes for approval by department and value — Admin Head for non-IT items, CFO and SG for orders ≥₹10L — before being issued.</div>
    @if ($canCreate)
      <a href="{{ route('po.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        New PO
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Purchase Orders</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>PO No.</div>
        <div>Vendor</div>
        <div>Amount (GST-incl.)</div>
        <div>Delivery Date</div>
        <div>Status</div>
      </div>

      @forelse ($pos as $po)
        @php
          $pending = $po->pendingApproval();
          if ($po->status === 'approved') { $bg = '#E6F3EC'; $fg = '#2E7D4F'; $label = 'Approved — Issued'; }
          elseif ($po->status === 'rejected') { $bg = '#FBE7E9'; $fg = '#B0263A'; $label = 'Rejected'; }
          else { $bg = '#FBF0DC'; $fg = '#8A5A12'; $label = 'Pending — '.($pending ? \App\Support\Roles::label($pending->role_required) : '—'); }
        @endphp
        <a href="{{ route('po.show', $po) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $po->po_number }}</div>
          <div class="module-row-c1">{{ $po->vendor->company_name }}</div>
          <div class="module-row-c2">₹{{ number_format($po->amount_incl_gst, 2) }} @if($po->isHighValue())<span class="badge-soft" style="background:#EFE6FB; color:#5B3A9E;">High Value</span>@endif</div>
          <div class="module-row-c3">{{ optional($po->delivery_date)->format('d M Y') ?? '—' }}</div>
          <div><span class="badge-soft" style="background:{{ $bg }}; color:{{ $fg }};">{{ $label }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No Purchase Orders yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'Purchase Orders' });
@endsection
