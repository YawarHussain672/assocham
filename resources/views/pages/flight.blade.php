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

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Flight &amp; Boarding Logistics Ledger</h1>
      <div class="page-sub" style="margin-top:0;">Attach verified tickets, boarding passes, and lodging vouchers against approved TRFs for audit-proof expenditure reconciliation.</div>
    </div>
    @if ($canCreate)
      <a href="{{ route('flight.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
        <i class="bi bi-cloud-arrow-up"></i>
        <span>Upload Transaction</span>
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Logged Transactions</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalLogs }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Flight, hotel &amp; transit</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-ticket-detailed fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Booking Spend</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">₹{{ number_format($totalCost, 2) }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Mapped against TRFs</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-wallet2 fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Flight Tickets</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $flightCount }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Airlines booked</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-airplane fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Hotel Stays</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $hotelCount }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Empanelled lodgings</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-building fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 overflow-hidden">
    <div class="table-toolbar px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="table-toolbar-title d-flex align-items-center gap-2">
        <i class="bi bi-receipt text-primary"></i>
        <span>Logistics Transaction Entries</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalLogs }} Records</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Ref No.</div>
        <div>Linked TRF &amp; Traveler</div>
        <div>Booking Type</div>
        <div>Service Provider</div>
        <div>Cost &amp; Proof</div>
      </div>

      @forelse ($logs as $log)
        @php $icon = $typeIcons[$log->type] ?? 'bi-receipt'; @endphp
        <a href="{{ route('flight.show', $log) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi {{ $icon }} text-muted"></i>
            <span>{{ $log->ref }}</span>
          </div>
          <div class="module-row-c1">{{ $log->trf->trf_number }} · {{ $log->trf->employee->name }}</div>
          <div class="module-row-c2">
            <span class="badge" style="background:#f1f5f9; color:#334155; font-size:12px; font-weight:500;">{{ $log->type }}</span>
          </div>
          <div class="module-row-c2">{{ $log->vendor->company_name ?? 'Direct Provider' }}</div>
          <div class="module-row-c3 d-flex align-items-center gap-2" style="font-weight:600; color:#1e293b;">
            <span>₹{{ number_format($log->cost, 2) }}</span>
            @if ($log->file_path)
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-size:11px;" title="Proof Attached">
                <i class="bi bi-paperclip"></i> Proof
              </span>
            @endif
          </div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No flight &amp; boarding transactions logged yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Logistics' });
@endsection

