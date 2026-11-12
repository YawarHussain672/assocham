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

  <!-- Status KPI Tab Cards (Transactions, Spend, Flights, Hotels) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Transactions</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-ticket-detailed"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalLogs }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Spend</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">₹{{ number_format($totalCost, 2) }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Flights</span>
            <div class="stat-icon-box icon-secondary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-airplane"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $flightCount }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Hotels</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $hotelCount }}</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    #flightTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #flightTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #64748b;
      vertical-align: middle;
      padding: 10px 14px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #flightTable td {
      vertical-align: middle;
      padding: 11px 14px;
      font-size: 12.5px;
      line-height: 1.4;
      border-bottom: 1px solid #f1f5f9;
    }
  </style>

  <!-- Logistics Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-ticket-detailed text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Logistics Transaction Ledger</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 11px;">
          {{ $totalLogs }} {{ Str::plural('Record', $totalLogs) }}
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="flightTable">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th style="width: 11%;" class="ps-4">REF NO.</th>
              <th style="width: 20%;">TRF &amp; TRAVELER</th>
              <th style="width: 15%;">BOOKING TYPE</th>
              <th style="width: 18%;">PROVIDER</th>
              <th style="width: 21%;">COST &amp; STATUS</th>
              <th style="width: 15%;" class="text-end pe-4">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($logs as $log)
              @php
                $icon = $typeIcons[$log->type] ?? 'bi-receipt';
                $isSettled = $log->trf?->reimbursements->contains('status', 'approved');
                $isUnderAudit = !$isSettled && $log->trf?->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg'])->isNotEmpty();
                $latestMail = $log->mailTransactions->first();
              @endphp
              <tr>
                <td class="ps-4 text-nowrap">
                  <a href="{{ route('flight.show', $log) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 12.5px;">
                    {{ $log->ref }}
                  </a>
                </td>
                <td>
                  <div class="fw-semibold">
                    <a href="{{ route('trf.show', $log->trf) }}" class="text-dark text-decoration-none">{{ $log->trf->trf_number }}</a>
                  </div>
                  <div class="text-muted" style="font-size: 11.5px;">{{ $log->trf->employee->name }}</div>
                </td>
                <td class="text-nowrap text-dark">
                  <i class="bi {{ $icon }} text-primary me-1"></i>{{ $log->type }}
                </td>
                <td>
                  <span class="text-dark fw-medium">{{ $log->vendor->company_name ?? 'Direct Booking' }}</span>
                </td>
                <td>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-bold text-dark">₹{{ number_format($log->cost, 2) }}</span>
                    @if ($log->file_path)
                      <a href="{{ route('flight.proof', $log) }}" target="_blank" class="text-primary text-decoration-none fw-medium" style="font-size: 11.5px;" title="Open Proof Document">
                        <i class="bi bi-paperclip"></i>Proof
                      </a>
                    @endif
                  </div>
                  <div class="text-muted" style="font-size: 11.5px;">
                    <span class="{{ $isSettled ? 'text-success fw-medium' : ($isUnderAudit ? 'text-warning-emphasis fw-medium' : 'text-muted') }}">
                      {{ $isSettled ? 'Settled' : ($isUnderAudit ? 'Under Audit' : 'Ready to Settle') }}
                    </span>
                    <span class="mx-1">&bull;</span>
                    @if ($latestMail)
                      <span class="{{ $latestMail->status === 'sent' ? 'text-success fw-medium' : 'text-danger fw-medium' }}" title="{{ $latestMail->status === 'sent' ? 'Sent to ' . $latestMail->recipient_email : ($latestMail->error_message ?? 'Mail delivery failed') }}">
                        {{ $latestMail->status === 'sent' ? 'Mailed' : 'Mail Failed' }}
                      </span>
                    @else
                      <span class="text-secondary">Not Mailed</span>
                    @endif
                  </div>
                </td>
                <td class="text-end pe-4 text-nowrap">
                  <div class="d-inline-flex align-items-center gap-1">
                    @if ($canCreate)
                      <form method="POST" action="{{ route('flight.resend-email', $log) }}" class="m-0 d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $latestMail && $latestMail->status === 'sent' ? 'btn-outline-secondary' : 'btn-outline-primary' }} px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 500;" title="{{ $latestMail ? 'Resend confirmation email to ' . ($log->trf?->employee?->email ?? 'traveller') : 'Send confirmation email to ' . ($log->trf?->employee?->email ?? 'traveller') }}">
                          <i class="bi bi-send"></i>
                          <span>{{ $latestMail ? 'Resend' : 'Mail' }}</span>
                        </button>
                      </form>
                    @endif
                    <a href="{{ route('flight.show', $log) }}" class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 500;" title="View Transaction Details">
                      <i class="bi bi-eye"></i>
                      <span>View</span>
                    </a>
                  </div>
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
