@extends('layouts.app')

@section('title', 'Reimbursement & Advance')

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'badge' => 'badge-soft-warning'],
    'pending_sg'  => ['label' => 'Pending — SG', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
  ];
  $flagMeta = [
    'standard'     => ['label' => 'Standard Route', 'badge' => 'badge-soft-secondary'],
    'asg_ch_claim' => ['label' => 'ASG/CH Claim', 'badge' => 'badge-soft-info'],
    'exception'    => ['label' => 'Off-TRF Exception', 'badge' => 'badge-soft-warning'],
  ];

  $totalReimb = $reimbursements->count();
  $pendingCfo = $reimbursements->where('status', 'pending_cfo')->count();
  $pendingSg = $reimbursements->where('status', 'pending_sg')->count();
  $approvedReimb = $reimbursements->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Expense Reimbursements &amp; Advances</h1>
      <p class="page-subtitle">Submit travel expense claims, settle cash advances, and track financial reconciliations.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('reimb.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Claim
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top 4 Pastel KPI Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Claims</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalReimb }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Settlement filings</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Pending CFO</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingCfo }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-hourglass-split"></i>
            <span>Awaiting finance audit</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">SG Overrides</span>
            <div class="stat-icon-box icon-secondary">
              <i class="bi bi-patch-exclamation"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingSg }}</div>
          <div class="d-flex align-items-center gap-1 text-secondary small fw-semibold">
            <i class="bi bi-shield-exclamation"></i>
            <span>Off-TRF exception claims</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Settled &amp; Paid</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $approvedReimb }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-wallet2"></i>
            <span>Approved disbursements</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Claims Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Reimbursement &amp; Advance Registry</h6>
        <small class="text-muted">Settlement claims, advance offsets, and net payable balances</small>
      </div>
      <span class="badge badge-soft-primary">{{ $totalReimb }} Claims</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">CLAIM NO.</th>
              <th>EMPLOYEE &amp; TRF</th>
              <th>CLAIM AMOUNT</th>
              <th>ADVANCE OFFSET</th>
              <th>NET PAYABLE</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reimbursements as $reimb)
              @php
                $meta = $statusMeta[$reimb->status] ?? ['label' => ucfirst($reimb->status), 'badge' => 'badge-soft-secondary'];
                $fMeta = $flagMeta[$reimb->routing_flag ?? 'standard'] ?? ['label' => 'Standard', 'badge' => 'badge-soft-secondary'];
                $isPayable = ($reimb->balance_amount ?? 0) >= 0;
              @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('reimb.show', $reimb) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $reimb->claim_number }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $reimb->employee->name }}</span>
                  <small class="text-muted d-block">{{ $reimb->trf ? $reimb->trf->trf_number : 'Direct Claim' }}</small>
                </td>
                <td>
                  <span class="fw-bold text-main">₹{{ number_format($reimb->claim_amount, 2) }}</span>
                  <span class="badge {{ $fMeta['badge'] }} ms-1" style="font-size: 0.7rem;">{{ $fMeta['label'] }}</span>
                </td>
                <td>
                  <span class="text-muted">₹{{ number_format($reimb->advance_amount, 2) }}</span>
                </td>
                <td>
                  <span class="fw-bold {{ $isPayable ? 'text-success' : 'text-danger' }}">₹{{ number_format(abs($reimb->balance_amount ?? 0), 2) }}</span>
                  <small class="text-muted d-block" style="font-size: 11px;">{{ $isPayable ? 'due to traveler' : 'owed to org' }}</small>
                </td>
                <td>
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('reimb.show', $reimb) }}" class="btn btn-sm btn-outline-custom">
                    View Claim <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No reimbursement claims filed yet.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Reimbursement & Advance' });
@endsection
