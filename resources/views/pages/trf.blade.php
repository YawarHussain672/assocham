@extends('layouts.app')

@section('title', 'Travel Requisition')

@php
  $statusMeta = [
    'pending_asg' => ['label' => 'Pending — ASG', 'badge' => 'badge-soft-warning'],
    'pending_sg'  => ['label' => 'Pending — SG', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'pushed_back' => ['label' => 'Pushed Back', 'badge' => 'badge-soft-danger'],
    'draft'       => ['label' => 'Draft', 'badge' => 'badge-soft-secondary'],
  ];

  $totalTrfs = $trfs->count();
  $pendingAsg = $trfs->where('status', 'pending_asg')->count();
  $pendingSg = $trfs->where('status', 'pending_sg')->count();
  $approvedTrfs = $trfs->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Travel Requisitions (TRF)</h1>
      <p class="page-subtitle">Submit travel applications, route domestic and international approvals, and book itineraries.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importTrfExcelModal">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
      </button>
      <a href="{{ route('trf.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Travel Requisition
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
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total TRFs</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-airplane-fill"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalTrfs }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Official trip itineraries</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Pending ASG</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingAsg }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-geo-alt"></i>
            <span>Domestic itineraries</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Pending SG</span>
            <div class="stat-icon-box icon-secondary">
              <i class="bi bi-globe"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $pendingSg }}</div>
          <div class="d-flex align-items-center gap-1 text-secondary small fw-semibold">
            <i class="bi bi-shield-lock"></i>
            <span>International missions</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Cleared Trips</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $approvedTrfs }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-patch-check"></i>
            <span>Authorized for travel</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TRF Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Travel Requisitions Roster</h6>
        <small class="text-muted">Active trip requests, itinerary details, and clearance levels</small>
      </div>
      <span class="badge badge-soft-primary">{{ $totalTrfs }} Entries</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">TRF NO.</th>
              <th>EMPLOYEE</th>
              <th>ROUTE / DESTINATION</th>
              <th>TRAVEL DATES</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($trfs as $trf)
              @php $meta = $statusMeta[$trf->status] ?? $statusMeta['draft']; @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('trf.show', $trf) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $trf->trf_number }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $trf->employee->name }}</span>
                  @if($trf->is_international)
                    <span class="badge badge-soft-secondary ms-1">International</span>
                  @endif
                </td>
                <td>
                  <span class="text-main fw-medium">{{ $trf->route ?: '—' }}</span>
                </td>
                <td>
                  <span class="text-muted">
                    @if ($trf->travel_start)
                      {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
                    @else
                      —
                    @endif
                  </span>
                </td>
                <td>
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('trf.show', $trf) }}" class="btn btn-sm btn-outline-custom">
                    View Itinerary <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No travel requisitions recorded yet.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Import Travel Excel Modal (Slide 16 & 39) -->
  <div class="modal fade" id="importTrfExcelModal" tabindex="-1" aria-labelledby="importTrfExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <form method="POST" action="{{ route('trf.import-excel') }}" enctype="multipart/form-data">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center gap-2 fw-bold" id="importTrfExcelModalLabel">
              <i class="bi bi-file-earmark-spreadsheet text-primary"></i>
              <span>Bulk Import Travel Requisitions</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="alert alert-light border small text-muted mb-3 d-flex align-items-start gap-2">
              <i class="bi bi-info-circle text-primary fs-5 mt-0.5"></i>
              <div>
                <strong>Excel / CSV Column Structure:</strong><br>
                <code>Route, Purpose, Start Date (YYYY-MM-DD), End Date (YYYY-MM-DD), Advance (₹), International (yes/no)</code><br>
                Example: <code>Delhi &rarr; Mumbai, Client Site Kickoff, 2026-07-10, 2026-07-12, 15000, no</code>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Upload Excel / CSV File</label>
              <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx">
            </div>

            <div class="text-center my-2 text-muted small fw-semibold">— OR PASTE TABULAR DATA —</div>

            <div class="mb-2">
              <label class="form-label fw-semibold">Paste Travel Itinerary Rows</label>
              <textarea name="csv_data" class="form-control font-monospace" rows="4" placeholder="Delhi - Mumbai, Client Site Kickoff, 2026-07-10, 2026-07-12, 15000, no
Delhi - Dubai, Gulf Expo 2026, 2026-08-05, 2026-08-09, 45000, yes"></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
              <i class="bi bi-cloud-arrow-up me-1"></i> Parse &amp; Submit TRF(s)
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'Travel Requisition' });
@endsection
