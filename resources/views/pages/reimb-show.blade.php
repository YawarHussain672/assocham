@extends('layouts.app')

@section('title', $reimbursement->claim_number)

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$reimbursement->status] ?? $statusMeta['pending_cfo'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Reimbursement &amp; Advance
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">{{ strtoupper(substr($reimbursement->employee->name, 0, 2)) }}</div>
    <div>
      <div class="detail-name">{{ $reimbursement->claim_number }} — {{ $reimbursement->employee->name }}</div>
      <div class="detail-meta">{{ $reimbursement->trf ? $reimbursement->trf->trf_number : 'Off-TRF claim' }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Claim Details</div>
      <div class="row row-cols-2 g-3">
        <div><div class="field-label">Claim Amount</div><div class="field-value">₹{{ number_format($reimbursement->claim_amount, 2) }}</div></div>
        <div><div class="field-label">Advance Released</div><div class="field-value">₹{{ number_format($reimbursement->advance_amount, 2) }}</div></div>
        <div><div class="field-label">Balance</div><div class="field-value">₹{{ number_format(abs($reimbursement->balance_amount), 2) }} {{ $reimbursement->balance_amount >= 0 ? 'due to traveler' : 'owed by traveler' }}</div></div>
        <div><div class="field-label">Routing</div><div class="field-value">{{ ucfirst(str_replace('_', ' ', $reimbursement->routing_flag)) }}</div></div>
      </div>
      @if ($reimbursement->reason)
        <div class="mt-3"><div class="field-label">Reason</div><div class="field-value">{{ $reimbursement->reason }}</div></div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval</div>
      @foreach ($reimbursement->approvals as $approval)
        @php $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
                          'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
                          'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
             $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">{{ \App\Support\Roles::label($approval->role_required) }}</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision ({{ \App\Support\Roles::label($pendingApproval->role_required) }})</div>
          <form method="POST" action="{{ route('reimb.approve', $reimbursement) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('reimb.reject', $reimbursement) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif (in_array($reimbursement->status, ['pending_cfo', 'pending_sg']))
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
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Claim Detail' });
@endsection
