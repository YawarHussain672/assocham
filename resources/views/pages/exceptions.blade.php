@extends('layouts.app')

@section('title', 'Exception Handling')

@php
  $typeMeta = [
    'single_quotation' => 'Single Quotation',
    'budget_exceed' => 'Budget Exceed',
    'po_force_close' => 'PO Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Check',
  ];
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'badge' => 'warning'],
    'approved' => ['label' => 'Approved', 'badge' => 'success'],
    'rejected' => ['label' => 'Rejected', 'badge' => 'danger'],
  ];

  $totalExc = $exceptions->count();
  $pendingExc = $exceptions->where('status', 'pending')->count();
  $budgetOverrides = $exceptions->where('exception_type', 'budget_exceed')->count();
  $approvedExc = $exceptions->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Governance Exceptions &amp; Overrides</h1>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('exceptions.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-shield-plus me-1"></i> File Exception
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

  <!-- Status KPI Tab Cards (Total, Pending, Overrides, Approved) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-shield-exclamation"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalExc }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingExc }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Overrides</span>
            <div class="stat-icon-box icon-info" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $budgetOverrides }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Approved</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $approvedExc }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Exceptions Registry Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold">Exception Governance Registry</h6>
        <span class="badge bg-light text-dark">{{ $totalExc }} records</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Exception Type</th>
            <th>Justification Narrative</th>
            <th>Requested By</th>
            <th>Approver Queue</th>
            <th>Status</th>
            <th class="text-end pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($exceptions as $exception)
            @php $meta = $statusMeta[$exception->status] ?? $statusMeta['pending']; @endphp
            <tr onclick="window.location='{{ route('exceptions.show', $exception) }}'" style="cursor:pointer;">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-shield text-primary"></i>
                  <span class="fw-semibold text-dark">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }}</span>
                </div>
              </td>
              <td>
                <span class="text-muted small">{{ \Illuminate\Support\Str::limit($exception->justification, 60) }}</span>
              </td>
              <td>
                <span class="small fw-medium">{{ $exception->requester->name }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
              </td>
              <td>
                <span class="badge bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle px-2 py-1">
                  {{ $meta['label'] }}
                </span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('exceptions.show', $exception) }}" class="btn btn-sm btn-outline-secondary">
                  View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-shield-check fs-2 d-block mb-2"></i>
                No exceptions filed yet.
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
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exceptions' });
@endsection
