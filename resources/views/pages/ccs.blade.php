@extends('layouts.app')

@section('title', 'Cost Comparative Statement')

@php
  $statusMeta = [
    'pending_committee' => ['label' => 'Pending — Committee', 'badge' => 'badge-soft-warning'],
    'approved'          => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'rejected'          => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
    'draft'             => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Cost Comparative Statements (CCS)</h1>
      <p class="page-subtitle">Analyze vendor quotes side-by-side and determine L1 bidder allocations.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('rfq') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-send me-1"></i> Go to RFQs
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top 3 Pastel KPI Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Statements</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-bar-chart-steps"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $statements->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Evaluated comparative matrices</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Approved &amp; Awarded</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-award-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $statements->where('status', 'approved')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-check2-circle"></i>
            <span>Ready for PO generation</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Pending Committee</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $statements->where('status', 'pending_committee')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-clock-history"></i>
            <span>Awaiting committee endorsement</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CCS Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Cost Comparative Matrix Registry</h6>
        <small class="text-muted">Vendor bid rankings and committee approval status</small>
      </div>
      <span class="badge badge-soft-primary">{{ $statements->count() }} Statements</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">CCS NO.</th>
              <th>LINKED RFQ / PR</th>
              <th>LOWEST EVALUATED BID</th>
              <th>VENDORS COMPARED</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($statements as $ccs)
              @php
                $meta = $statusMeta[$ccs->status] ?? $statusMeta['draft'];
                $l1 = $ccs->lowestQuote();
              @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('ccs.show', $ccs) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $ccs->ccs_number }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $ccs->rfq->rfq_number }}</span>
                  <small class="text-muted d-block">{{ $ccs->rfq->purchaseRequisition->pr_number }}</small>
                </td>
                <td>
                  @if ($l1)
                    <span class="fw-bold text-success">₹{{ number_format($l1->quoted_cost, 2) }}</span>
                    <small class="text-muted d-block">{{ $l1->vendor->company_name }} (Awarded)</small>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <span class="badge badge-soft-info">{{ $ccs->quotes->count() }} Bids Submitted</span>
                </td>
                <td>
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('ccs.show', $ccs) }}" class="btn btn-sm btn-outline-custom">
                    View Matrix <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No Cost Comparative Statements created yet.</span>
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
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'Cost Comparative Statement' });
@endsection
