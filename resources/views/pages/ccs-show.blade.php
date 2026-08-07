@extends('layouts.app')

@section('title', $ccs->ccs_number)

@php
  $statusMeta = [
    'pending_committee' => ['label' => 'Pending — Committee', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'          => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'          => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'             => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$ccs->status] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('ccs') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Cost Comparative Statement
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">CCS</div>
    <div>
      <div class="detail-name">{{ $ccs->ccs_number }}</div>
      <div class="detail-meta">From {{ $ccs->rfq->rfq_number }} · {{ $ccs->rfq->purchaseRequisition->pr_number }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Vendor Quotes</div>
      <div style="overflow-x:auto;">
        <table class="table table-sm">
          <thead>
            <tr class="text-muted" style="font-size:11px; text-transform:uppercase;">
              <th>Rank</th><th>Vendor</th><th class="text-end">Quoted Cost</th><th>Delivery</th><th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($ccs->quotes->sortBy('quoted_cost') as $quote)
              <tr style="font-size:13px;">
                <td><span class="badge-soft" style="background:{{ $quote->rank === 'L1' ? '#E6F3EC' : '#EEF0F3' }}; color:{{ $quote->rank === 'L1' ? '#2E7D4F' : '#64748B' }};">{{ $quote->rank }}</span></td>
                <td>{{ $quote->vendor->company_name }}</td>
                <td class="text-end">₹{{ number_format($quote->quoted_cost, 2) }}</td>
                <td>{{ $quote->delivery_days !== null ? $quote->delivery_days.' days' : '—' }}</td>
                <td>{{ $quote->remarks ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if ($ccs->status === 'approved' && $ccs->purchaseOrder)
        <a href="{{ route('po.show', $ccs->purchaseOrder) }}" class="btn btn-outline-secondary w-100 d-block text-center mt-3">View Purchase Order {{ $ccs->purchaseOrder->po_number }}</a>
      @elseif ($ccs->status === 'approved')
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">
            Approved — {{ $ccs->lowestQuote()?->vendor?->company_name }} (L1) is the documented justification.
            @if (auth()->user()->role === \App\Support\Roles::BUYER)
              <a href="{{ route('po.create') }}">Raise a Purchase Order</a>.
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval</div>
      @foreach ($ccs->approvals as $approval)
        @php $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
                          'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
                          'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
             $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">Procurement Committee — {{ \App\Support\Roles::label($approval->role_required) }}</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision (Procurement Committee)</div>
          <form method="POST" action="{{ route('ccs.approve', $ccs) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('ccs.reject', $ccs) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif ($ccs->status === 'pending_committee')
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
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'CCS Detail' });
@endsection
