@extends('layouts.app')

@section('title', 'RFQ & Quotations')

@php
$statusMeta = [
'draft' => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
'dispatched' => ['label' => 'Dispatched', 'badge' => 'badge-soft-warning'],
'quotes_received' => ['label' => 'Quotes Received', 'badge' => 'badge-soft-info'],
'approved' => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Request for Quotation (RFQ)</h1>
      <p class="page-subtitle">Publish tenders, solicit vendor proposals and collect competitive price quotations.</p>
    </div>
    @if ($canCreate)
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('rfq.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New RFQ
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
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total RFQs</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-send-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $rfqs->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Vendor quotation inquiries</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Quotes Received</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-inbox-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $rfqs->where('status', 'quotes_received')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-info small fw-semibold">
            <i class="bi bi-check2"></i>
            <span>Ready for CCS evaluation</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Dispatched</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $rfqs->where('status', 'dispatched')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-hourglass-split"></i>
            <span>Awaiting vendor responses</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RFQ Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Requests for Quotation</h6>
        <small class="text-muted">Competitive vendor inquiries across active procurement</small>
      </div>
      <span class="badge badge-soft-primary">{{ $rfqs->count() }} Inquiries</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">RFQ NO.</th>
              <th>LINKED PR</th>
              <th>INVITED VENDORS</th>
              <th>REQUISITION DATE</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rfqs as $rfq)
            @php $meta = $statusMeta[$rfq->status] ?? $statusMeta['draft']; @endphp
            <tr>
              <td class="ps-3">
                <a href="{{ route('rfq.show', $rfq) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                  <code>{{ $rfq->rfq_number }}</code>
                </a>
              </td>
              <td>
                <span class="fw-semibold text-main">{{ $rfq->purchaseRequisition->pr_number }}</span>
                <small class="text-muted d-block">{{ $rfq->purchaseRequisition->department }}</small>
              </td>
              <td>
                <span class="text-main fw-medium">{{ $rfq->rfqVendors->pluck('vendor.company_name')->filter()->join(', ') ?: '—' }}</span>
                <small class="text-muted d-block">{{ $rfq->rfqVendors->count() }} vendor{{ $rfq->rfqVendors->count() === 1 ? '' : 's' }} participating</small>
              </td>
              <td>
                <span class="text-muted">{{ optional($rfq->requisition_date)->format('d M Y') ?? '—' }}</span>
              </td>
              <td>
                <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-sm btn-outline-custom">
                  View <i class="bi bi-chevron-right ms-1"></i>
                </a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                <span>No RFQs raised yet.</span>
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
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ & Quotations' });
@endsection