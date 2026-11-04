@extends('layouts.app')

@section('title', 'Goods Receipt (GRN)')

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending — HOD', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Closed', 'badge' => 'badge-soft-success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Goods Receipt Notes (GRN)</h1>
      <p class="page-subtitle">Inspect delivered items, verify delivery challans, and record departmental acceptance.</p>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('grn.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-box-seam me-1"></i> Log Goods Receipt
        </a>
      </div>
    @endif
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
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Receipts</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-box-seam-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $grns->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Logged physical inward notes</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Closed &amp; Verified</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $grns->where('status', 'approved')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-patch-check"></i>
            <span>HOD signed &amp; stock added</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Awaiting Verification</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $grns->where('status', 'pending_hod')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-hourglass-split"></i>
            <span>Pending HOD review</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- GRN Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Goods Receipt Registry</h6>
        <small class="text-muted">Inward delivery records and inspection status</small>
      </div>
      <span class="badge badge-soft-primary">{{ $grns->count() }} Entries</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">GRN NO.</th>
              <th>LINKED PO / VENDOR</th>
              <th>QTY RECEIVED</th>
              <th>RECEIPT DATE</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($grns as $grn)
              @php $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod']; @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('grn.show', $grn) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $grn->grn_number }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $grn->purchaseOrder->po_number }}</span>
                  <small class="text-muted d-block">{{ $grn->purchaseOrder->vendor->company_name }}</small>
                </td>
                <td>
                  <span class="fw-bold text-main">{{ $grn->received_qty }} / {{ $grn->po_qty }} units</span>
                </td>
                <td>
                  <span class="text-muted">{{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}</span>
                </td>
                <td>
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('grn.show', $grn) }}" class="btn btn-sm btn-outline-custom">
                    View Dossier <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No Goods Receipt Notes logged yet.</span>
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
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Goods Receipt (GRN)' });
@endsection
