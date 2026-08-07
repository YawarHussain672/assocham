@extends('layouts.app')

@section('title', $rfq->rfq_number)

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'dispatched'      => ['label' => 'Dispatched', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'quotes_received' => ['label' => 'Quotes Received', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'],
    'approved'        => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
  ];
  $meta = $statusMeta[$rfq->status] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to RFQ &amp; Quotations
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">RFQ</div>
    <div>
      <div class="detail-name">{{ $rfq->rfq_number }}</div>
      <div class="detail-meta">Linked to {{ $rfq->purchaseRequisition->pr_number }} · {{ $rfq->purchaseRequisition->department }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Requisition Items</div>
      <div style="overflow-x:auto;">
        <table class="table table-sm">
          <thead>
            <tr class="text-muted" style="font-size:11px; text-transform:uppercase;">
              <th>Item</th><th>Qty</th><th class="text-end">Unit Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rfq->purchaseRequisition->items as $item)
              <tr style="font-size:13px;">
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }} {{ $item->unit }}</td>
                <td class="text-end">₹{{ number_format($item->unit_cost, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if ($canManage && $rfq->status === 'dispatched')
        <form method="POST" action="{{ route('rfq.quotes-received', $rfq) }}" class="mt-3">
          @csrf
          <button type="submit" class="btn-brand w-100">Mark Quotes Received</button>
          <div class="field-label mt-2" style="text-transform:none; letter-spacing:0;">Once vendor quotes are in hand, mark this RFQ received to move it into a Cost Comparative Statement.</div>
        </form>
      @elseif ($rfq->status === 'quotes_received' && ! $rfq->costComparativeStatement)
        <a href="{{ route('ccs.create', $rfq) }}" class="btn-brand w-100 d-block text-center mt-3">Prepare Cost Comparative Statement</a>
      @elseif ($rfq->costComparativeStatement)
        <a href="{{ route('ccs.show', $rfq->costComparativeStatement) }}" class="btn btn-outline-secondary w-100 d-block text-center mt-3">View Cost Comparative Statement</a>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Dispatched To ({{ $rfq->rfqVendors->count() }})</div>
      @forelse ($rfq->rfqVendors as $rv)
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">{{ $rv->vendor->company_name }}</div>
            <div class="field-label" style="text-transform:none; letter-spacing:0;">{{ $rv->vendor->vendor_code }}</div>
          </div>
          <span class="badge-soft" style="background:#EEF0F3; color:#64748B;">{{ ucfirst($rv->response_status) }}</span>
        </div>
      @empty
        <div class="text-muted">No vendors dispatched.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ Detail' });
@endsection
