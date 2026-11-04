@extends('layouts.app')

@section('title', 'Vendor Empanelment')

@php
$statusMeta = [
'pending_admin_head' => ['label' => 'Pending — Administrative Audit', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_l1' => ['label' => 'Pending — Secretarial Review (ASG 1)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_l2' => ['label' => 'Pending — Secretarial Review (ASG 2)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'pending_cfo' => ['label' => 'Pending — Financial Clearance (CFO)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
'approved' => ['label' => 'Empanelled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F', 'badge' => 'success'],
'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A', 'badge' => 'danger'],
'draft' => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B', 'badge' => 'secondary'],
];

$totalVendors = $vendors->count();
$empanelledCount = $vendors->where('status', 'approved')->count();
$pendingCount = $vendors->whereIn('status', ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo'])->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Vendor Empanelment</h1>
      <p class="page-subtitle">Onboard, verify and maintain qualified suppliers across categories and compliance tiers.</p>
    </div>
    @if ($canSubmit)
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('onboarding') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-person-plus me-1"></i> Add Vendor
      </a>
    </div>
    @endif
  </div>

  @if (session('status'))
  <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>{{ session('status') }}</div>
  </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total Vendors</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalVendors }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Institutional vendor registry</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Empanelled (Active)</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-patch-check"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $empanelledCount }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-check2-circle"></i>
            <span>Cleared for RFQs &amp; POs</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Pending Approval</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingCount }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-clock-history"></i>
            <span>Awaiting multi-tier review</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Empanelment Queue Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold">Empanelment Queue</h6>
        <span class="badge bg-light text-dark">{{ $totalVendors }} total</span>
      </div>
      <div>
        <input type="text" class="form-control form-control-sm" placeholder="Filter vendors..." id="filterVendorsInput" style="max-width:200px;">
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="vendorsTable">
        <thead class="table-light">
          <tr>
            <th>Vendor</th>
            <th>PAN / GSTIN</th>
            <th>Category</th>
            <th>Contact</th>
            <th>MSME</th>
            <th>Status</th>
            <th class="text-end pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($vendors as $vendor)
          @php $meta = $statusMeta[$vendor->status] ?? $statusMeta['draft']; @endphp
          <tr onclick="window.location='{{ route('empanelment.show', $vendor) }}'" style="cursor:pointer;">
            <td>
              <div class="fw-semibold text-dark">{{ $vendor->company_name }}</div>
              <div class="text-muted small font-monospace">{{ $vendor->vendor_code }}</div>
            </td>
            <td>
              <div class="font-monospace small fw-medium">{{ $vendor->pan ?: '—' }}</div>
              <div class="text-muted font-monospace small">{{ $vendor->gstin ?: '—' }}</div>
            </td>
            <td>
              <span class="badge bg-light text-dark fw-medium">{{ $vendor->category ?: 'General' }}</span>
            </td>
            <td>
              <div class="small fw-medium">{{ $vendor->contact_person ?: '—' }}</div>
            </td>
            <td>
              @if ($vendor->msme_status)
              <span class="badge bg-success-subtle text-success border border-success-subtle">MSME</span>
              @else
              <span class="text-muted small">No</span>
              @endif
            </td>
            <td>
              <span class="badge bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle px-2 py-1">
                {{ $meta['label'] }}
              </span>
            </td>
            <td class="text-end pe-3">
              <a href="{{ route('empanelment.show', $vendor) }}" class="btn btn-sm btn-outline-secondary">
                View
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2"></i>
              No vendors submitted yet.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Vendors', title: 'Empanelment' });

(function() {
const input = document.getElementById('filterVendorsInput');
if (input) {
input.addEventListener('keyup', function() {
const q = this.value.toLowerCase();
const rows = document.querySelectorAll('#vendorsTable tbody tr');
rows.forEach(r => {
r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
});
});
}
})();
@endsection