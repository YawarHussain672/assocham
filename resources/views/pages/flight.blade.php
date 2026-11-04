@extends('layouts.app')

@section('title', 'Flight & Boarding Logistics')

@php
  $totalLogs = $logs->count();
  $totalCost = $logs->sum('cost');
  $flightCount = $logs->where('type', 'Flight ticket')->count();
  $hotelCount = $logs->where('type', 'Hotel')->count();

  $typeIcons = [
    'Flight ticket' => 'bi-airplane',
    'Hotel' => 'bi-building',
    'Transport' => 'bi-car-front',
    'Other' => 'bi-receipt',
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Flight &amp; Boarding Logistics</h1>
      <p class="page-subtitle">Track agency flight bookings, hotel reservations, and boarding expenditure logs.</p>
    </div>
    @if ($canCreate)
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('flight.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-cloud-arrow-up me-1"></i> Upload Transaction
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

  <!-- Top 4 Pastel KPI Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Transactions</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-ticket-detailed"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $totalLogs }}</div>
          <div class="d-flex align-items-center gap-1 text-primary small fw-semibold">
            <i class="bi bi-diagram-3"></i>
            <span>Flight, hotel &amp; transit items</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Booking Spend</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value mb-1" style="font-size: 1.55rem;">₹{{ number_format($totalCost, 2) }}</div>
          <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
            <i class="bi bi-check2"></i>
            <span>Mapped against approved TRFs</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Flight Tickets</span>
            <div class="stat-icon-box icon-secondary">
              <i class="bi bi-airplane"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $flightCount }}</div>
          <div class="d-flex align-items-center gap-1 text-secondary small fw-semibold">
            <i class="bi bi-ticket-perforated"></i>
            <span>Domestic &amp; intl airlines</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Hotel Stays</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-1">{{ $hotelCount }}</div>
          <div class="d-flex align-items-center gap-1 text-warning small fw-semibold">
            <i class="bi bi-houses"></i>
            <span>Empanelled lodgings</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Logistics Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
      <div>
        <h6 class="fw-bold mb-0 text-main">Logistics Transaction Ledger</h6>
        <small class="text-muted">Tickets, boarding passes, and stay vouchers linked to TRFs</small>
      </div>
      <span class="badge badge-soft-primary">{{ $totalLogs }} Records</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">REF NO.</th>
              <th>LINKED TRF &amp; TRAVELER</th>
              <th>BOOKING TYPE</th>
              <th>SERVICE PROVIDER</th>
              <th>COST &amp; PROOF</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($logs as $log)
              @php $icon = $typeIcons[$log->type] ?? 'bi-receipt'; @endphp
              <tr>
                <td class="ps-3">
                  <a href="{{ route('flight.show', $log) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                    <code>{{ $log->ref }}</code>
                  </a>
                </td>
                <td>
                  <span class="fw-semibold text-main">{{ $log->trf->trf_number }}</span>
                  <small class="text-muted d-block">{{ $log->trf->employee->name }}</small>
                </td>
                <td>
                  <span class="badge badge-soft-info d-inline-flex align-items-center gap-1">
                    <i class="bi {{ $icon }}"></i> {{ $log->type }}
                  </span>
                </td>
                <td>
                  <span class="text-main fw-medium">{{ $log->vendor->company_name ?? 'Direct Provider' }}</span>
                </td>
                <td>
                  <span class="fw-bold text-main">₹{{ number_format($log->cost, 2) }}</span>
                  @if ($log->file_path)
                    <span class="badge badge-soft-success ms-1" title="Proof Attached">
                      <i class="bi bi-paperclip"></i> Proof
                    </span>
                  @endif
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('flight.show', $log) }}" class="btn btn-sm btn-outline-custom">
                    View Details <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No flight &amp; boarding transactions logged yet.</span>
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
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Logistics' });
@endsection
