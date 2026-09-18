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

  $totalReimb = $reimbursements->count();
  $pendingCfo = $reimbursements->where('status', 'pending_cfo')->count();
  $pendingSg = $reimbursements->where('status', 'pending_sg')->count();
  $approvedReimb = $reimbursements->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Expense Reimbursements &amp; Advance Settlements</h1>
      <div class="page-sub" style="margin-top:0;">Claims auto-reconcile against approved TRFs and offset previously disbursed travel advances. Unsettled advances are flagged for finance recovery.</div>
    </div>
    <a href="{{ route('reimb.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
      <i class="bi bi-plus-lg"></i>
      <span>New Claim</span>
    </a>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Claims</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalReimb }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Settlement filings</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-receipt-cutoff fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Pending CFO Audit</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingCfo }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Awaiting finance clearance</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-hourglass-split fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">SG Overrides</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingSg }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Off-TRF exception claims</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-patch-exclamation fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Settled &amp; Paid</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $approvedReimb }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Approved disbursements</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-check2-circle fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 overflow-hidden">
    <div class="table-toolbar px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="table-toolbar-title d-flex align-items-center gap-2">
        <i class="bi bi-wallet2 text-primary"></i>
        <span>Settlement Ledger</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalReimb }} Claims</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Claim No.</div>
        <div>Employee / TRF</div>
        <div>Net Settlement Balance</div>
        <div>Routing Flag</div>
        <div>Status</div>
      </div>

      @forelse ($reimbursements as $reimb)
        @php
          $meta = $statusMeta[$reimb->status] ?? $statusMeta['pending_cfo'];
          $flag = $flagMeta[$reimb->routing_flag] ?? $flagMeta['standard'];
        @endphp
        <a href="{{ route('reimb.show', $reimb) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi bi-receipt text-muted"></i>
            <span>{{ $reimb->claim_number }}</span>
          </div>
          <div class="module-row-c1">{{ $reimb->employee->name }} {{ $reimb->trf ? '· '.$reimb->trf->trf_number : '(off-TRF)' }}</div>
          <div class="module-row-c2" style="color:{{ $reimb->balance_amount >= 0 ? '#2e7d4f' : '#b0263a' }}; font-weight:600;">
            ₹{{ number_format(abs($reimb->balance_amount), 2) }} 
            <span class="text-muted" style="font-size:11.5px; font-weight:400;">({{ $reimb->balance_amount >= 0 ? 'due to traveler' : 'owed to org' }})</span>
          </div>
          <div><span class="badge-soft" style="background:{{ $flag['bg'] }}; color:{{ $flag['fg'] }}; font-weight:600;">{{ $flag['label'] }}</span></div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600;">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No reimbursement claims filed yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Reimbursement & Advance' });
@endsection
