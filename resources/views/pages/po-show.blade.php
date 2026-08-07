@extends('layouts.app')

@section('title', $po->po_number)

@php
  $statusMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved — Issued', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$po->status] ?? $statusMeta['pending'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Purchase Orders
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">PO</div>
    <div>
      <div class="detail-name">{{ $po->po_number }}</div>
      <div class="detail-meta">{{ $po->vendor->company_name }} · {{ $po->purchaseRequisition->pr_number }} ({{ $po->purchaseRequisition->department }})</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Order Details</div>
      <div class="row row-cols-2 g-3">
        <div><div class="field-label">Vendor</div><div class="field-value">{{ $po->vendor->company_name }} ({{ $po->vendor->vendor_code }})</div></div>
        <div><div class="field-label">Quantity</div><div class="field-value">{{ $po->po_qty }}</div></div>
        <div><div class="field-label">Amount (GST-incl.)</div><div class="field-value">₹{{ number_format($po->amount_incl_gst, 2) }} {{ $po->isHighValue() ? '(High Value)' : '' }}</div></div>
        <div><div class="field-label">Delivery Date</div><div class="field-value">{{ optional($po->delivery_date)->format('d M Y') ?? '—' }}</div></div>
        <div><div class="field-label">Linked PR</div><div class="field-value">{{ $po->purchaseRequisition->pr_number }}</div></div>
        <div><div class="field-label">Linked CCS</div><div class="field-value">{{ $po->costComparativeStatement?->ccs_number ?? '—' }}</div></div>
        <div><div class="field-label">Created By</div><div class="field-value">{{ $po->creator?->name ?? '—' }}</div></div>
      </div>

      @if ($po->status === 'approved')
        <div class="mt-3 pt-2" style="border-top:1px solid var(--border-lighter);">
          <div class="field-label">Received So Far</div>
          <div class="field-value">{{ $po->receivedQty() }} / {{ $po->po_qty }}</div>
          @if ($po->remainingQtyToReceive() > 0 && auth()->user()->role === \App\Support\Roles::PR_SR_EXEC)
            <a href="{{ route('grn.create') }}" class="btn-brand w-100 d-block text-center mt-2">Log Goods Receipt (GRN)</a>
          @elseif ($po->remainingQtyToReceive() === 0)
            <div class="note-bar mt-2"><div class="note-dot"></div><div class="note-text">Fully received.</div></div>
          @endif
        </div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval Routing</div>
      @foreach ($po->approvals as $approval)
        @php $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
                          'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
                          'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
             $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">L{{ $approval->level }} — {{ \App\Support\Roles::label($approval->role_required) }}</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision (L{{ $pendingApproval->level }} — {{ \App\Support\Roles::label($pendingApproval->role_required) }})</div>
          <form method="POST" action="{{ route('po.approve', $po) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('po.reject', $po) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif ($po->status === 'pending')
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">Awaiting a different role's action — nothing for you to do here right now.</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'PO Detail' });
@endsection
