@extends('layouts.app')

@section('title', $grn->grn_number)

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending — HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Closed', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grn') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Goods Receipt (GRN)
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">GRN</div>
    <div>
      <div class="detail-name">{{ $grn->grn_number }}</div>
      <div class="detail-meta">{{ $grn->purchaseOrder->po_number }} · {{ $grn->purchaseOrder->vendor->company_name }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Receipt Details</div>
      <div class="row row-cols-2 g-3">
        <div><div class="field-label">Item(s)</div><div class="field-value">{{ $grn->item_description ?: '—' }}</div></div>
        <div><div class="field-label">Qty Received</div><div class="field-value">{{ $grn->received_qty }} / {{ $grn->po_qty }} on PO</div></div>
        <div><div class="field-label">Invoice Number</div><div class="field-value">{{ $grn->invoice_number ?: '—' }}</div></div>
        <div><div class="field-label">Receipt Date</div><div class="field-value">{{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}</div></div>
        <div><div class="field-label">Logged By</div><div class="field-value">{{ $grn->creator?->name ?? '—' }}</div></div>
        <div><div class="field-label">Linked PR</div><div class="field-value">{{ $grn->purchaseOrder->purchaseRequisition->pr_number }}</div></div>
      </div>
      @if ($grn->quality_remarks)
        <div class="mt-3"><div class="field-label">Quality Remarks</div><div class="field-value">{{ $grn->quality_remarks }}</div></div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">HOD Approval</div>
      @foreach ($grn->approvals as $approval)
        @php $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
                          'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
                          'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
             $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">{{ \App\Support\Roles::label($approval->role_required) }} — verifies qty &amp; quality</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision (HOD)</div>
          <form method="POST" action="{{ route('grn.approve', $grn) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve &amp; Close</button>
          </form>
          <form method="POST" action="{{ route('grn.reject', $grn) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif ($grn->status === 'pending_hod')
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">Awaiting HOD action — nothing for you to do here right now.</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'GRN Detail' });
@endsection
