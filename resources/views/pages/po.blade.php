@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Purchase Orders (PO)</h1>
      <p class="page-subtitle">Generate legally binding purchase contracts and track executive approval thresholds.</p>
    </div>
    @if ($canCreate)
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('po.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New PO
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
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total POs</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pos->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Committed vendor orders</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Approved Orders</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pos->where('status', 'approved')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-patch-check"></i>
            <span>Formally sanctioned &amp; issued</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-danger card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total PO Value (GST-Incl)</span>
            <div class="stat-icon-box icon-danger">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($pos->sum('amount_incl_gst'), 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-muted small fw-semibold">
            <i class="bi bi-wallet2"></i>
            <span>Committed fiscal commitments</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PO Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Purchase Orders Registry</h6>
        <small class="text-muted">Issued and pending orders across approved vendors</small>
      </div>
      <span class="badge badge-soft-primary">{{ $pos->count() }} Orders</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">PO NO.</th>
              <th>VENDOR</th>
              <th>AMOUNT (GST-INCL.)</th>
              <th>DELIVERY DATE</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($pos as $po)
            @php
            $pending = $po->pendingApproval();
            if ($po->status === 'approved') {
            $badge = 'badge-soft-success';
            $label = 'Approved — Issued';
            } elseif ($po->status === 'rejected') {
            $badge = 'badge-soft-danger';
            $label = 'Rejected';
            } else {
            $badge = 'badge-soft-warning';
            $label = 'Pending — ' . ($pending ? \App\Support\Roles::label($pending->role_required) : 'Review');
            }
            @endphp
            <tr>
              <td class="ps-3">
                <a href="{{ route('po.show', $po) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                  <code>{{ $po->po_number }}</code>
                </a>
              </td>
              <td>
                <span class="fw-semibold text-main">{{ $po->vendor->company_name }}</span>
                <small class="text-muted d-block">{{ $po->vendor->pan_number ? 'PAN: ' . $po->vendor->pan_number : 'Empanelled' }}</small>
              </td>
              <td>
                <span class="fw-bold text-main">₹{{ number_format($po->amount_incl_gst, 2) }}</span>
                @if($po->isHighValue())
                <span class="badge badge-soft-secondary ms-1">High Value (&ge;₹10L)</span>
                @endif
              </td>
              <td>
                <span class="text-muted">{{ optional($po->delivery_date)->format('d M Y') ?? '—' }}</span>
              </td>
              <td>
                <span class="badge {{ $badge }}">{{ $label }}</span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('po.show', $po) }}" class="btn btn-sm btn-outline-custom">
                  View <i class="bi bi-chevron-right ms-1"></i>
                </a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                <span>No Purchase Orders created yet.</span>
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
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'Purchase Orders' });
@endsection