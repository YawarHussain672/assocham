@extends('layouts.app')

@section('title', 'Reimbursement & Advance')

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $flagMeta = [
    'standard' => ['label' => 'Standard Route', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'asg_ch_claim' => ['label' => 'ASG/CH Claim', 'bg' => '#EFE6FB', 'fg' => '#5B3A9E'],
    'exception' => ['label' => 'Off-TRF Exception', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Claims auto-fill from an approved TRF and adjust against any travel advance already released. Overdue, unsettled advances (30+ days past trip end) block the traveler from raising a new TRF until settled here.</div>
    <a href="{{ route('reimb.create') }}" class="btn-brand">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
      New Claim
    </a>
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Reimbursement &amp; Advance Settlement</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Claim No.</div>
        <div>Employee / TRF</div>
        <div>Balance</div>
        <div>Routing</div>
        <div>Status</div>
      </div>

      @forelse ($reimbursements as $reimb)
        @php
          $meta = $statusMeta[$reimb->status] ?? $statusMeta['pending_cfo'];
          $flag = $flagMeta[$reimb->routing_flag] ?? $flagMeta['standard'];
        @endphp
        <a href="{{ route('reimb.show', $reimb) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $reimb->claim_number }}</div>
          <div class="module-row-c1">{{ $reimb->employee->name }} {{ $reimb->trf ? '· '.$reimb->trf->trf_number : '(off-TRF)' }}</div>
          <div class="module-row-c2">₹{{ number_format($reimb->balance_amount, 2) }} {{ $reimb->balance_amount >= 0 ? 'due to traveler' : 'owed by traveler' }}</div>
          <div><span class="badge-soft" style="background:{{ $flag['bg'] }}; color:{{ $flag['fg'] }};">{{ $flag['label'] }}</span></div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No reimbursement claims yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Reimbursement & Advance' });
@endsection
