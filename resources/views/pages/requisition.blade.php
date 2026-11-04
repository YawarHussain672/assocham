@extends('layouts.app')

@section('title', 'Purchase Requisition')

@php
  $statusMeta = [
    'pending_l1'  => ['label' => 'Pending — Operational Review (Basu)', 'badge' => 'badge-soft-warning'],
    'pending_l2'  => ['label' => 'Pending — Administrative Review (Ambika)', 'badge' => 'badge-soft-warning'],
    'pending_l3'  => ['label' => 'Pending — Finance Clearance (CFO)', 'badge' => 'badge-soft-warning'],
    'pending_l4'  => ['label' => 'Pending — Executive Sanction (SG)', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
    'draft'       => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Purchase Requisition</h1>
      <p class="page-subtitle">Track, raise and manage departmental purchase requests through multi-level governance.</p>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importExcelModal">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
        </button>
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i> New Requisition
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
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total PRs</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-file-earmark-text"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $prs->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Departmental demand records</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Approved Requisitions</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $prs->where('status', 'approved')->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-patch-check"></i>
            <span>Sanctioned for RFQ sourcing</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">In Approval Chain</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $prs->whereIn('status', ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4'])->count() }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-hourglass-split"></i>
            <span>Awaiting multi-stage review</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Requisitions Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Purchase Requisitions Pipeline</h6>
        <small class="text-muted">Live listing of departmental requisitions and approval states</small>
      </div>
      <span class="badge badge-soft-primary">{{ $prs->count() }} Records</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">PR NO.</th>
              <th>DEPARTMENT</th>
              <th>EST. COST (GST-INCL.)</th>
              <th>REQUIRED BY</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($prs as $pr)
              @php $meta = $statusMeta[$pr->status] ?? $statusMeta['draft']; @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('requisition.show', $pr) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $pr->pr_number }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $pr->department }}</span>
                  @if($pr->sole_source)
                    <span class="badge badge-soft-danger ms-1">Sole Source</span>
                  @endif
                </td>
                <td>
                  <span class="fw-bold text-main">₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</span>
                  <small class="text-muted d-block">{{ $pr->items->count() }} item{{ $pr->items->count() === 1 ? '' : 's' }}</small>
                </td>
                <td>
                  <span class="text-muted">{{ optional($pr->required_by)->format('d M Y') ?? '—' }}</span>
                </td>
                <td>
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('requisition.show', $pr) }}" class="btn btn-sm btn-outline-custom">
                    View Dossier <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No purchase requisitions filed yet.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if ($canCreate)
    <!-- Import Excel Modal (Slide 10 & 34) -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
          <form method="POST" action="{{ route('requisition.import-excel') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title d-flex align-items-center gap-2 fw-bold" id="importExcelModalLabel">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                <span>Bulk Import Purchase Requisition</span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-light border small text-muted mb-3 d-flex align-items-start gap-2">
                <i class="bi bi-info-circle text-primary fs-5 mt-0.5"></i>
                <div>
                  <strong>Excel / CSV Format Specification:</strong><br>
                  Column structure: <code>Item Name, Quantity, Unit, Unit Cost, GST%</code><br>
                  Example: <code>Dell Latitude 5540, 10, Units, 85000, 18</code>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Target Department *</label>
                  <select name="department" class="form-select" required>
                    <option value="IT">IT</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Operations">Operations</option>
                    <option value="Projects">Projects</option>
                    <option value="Finance">Finance</option>
                    <option value="HR">HR</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Required By Date</label>
                  <input type="date" name="required_by" class="form-control" value="{{ now()->addDays(14)->format('Y-m-d') }}">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Upload Excel / CSV File</label>
                <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx">
              </div>

              <div class="text-center my-2 text-muted small fw-semibold">— OR PASTE TABULAR DATA —</div>

              <div class="mb-2">
                <label class="form-label fw-semibold">Paste CSV / Comma-Separated Rows</label>
                <textarea name="csv_data" class="form-control font-monospace" rows="4" placeholder="Dell Latitude 5540, 10, Units, 85000, 18
HP LaserJet Pro, 5, Units, 24000, 18"></textarea>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-cloud-arrow-up me-1"></i> Parse &amp; Submit PR
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'Purchase Requisition' });
@endsection
