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
        <a href="{{ route('flight.create') }}" class="btn btn-sm btn-primary fw-semibold shadow-sm">
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

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="flightSwitchBar">
    <button type="button" class="erp-switch-tab active" data-type="all" onclick="selectFlightTab('all')">
      All Transactions <span class="erp-switch-count">{{ $totalLogs }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-type="Flight ticket" onclick="selectFlightTab('Flight ticket')">
      Flight Tickets <span class="erp-switch-count">{{ $flightCount }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-type="Hotel" onclick="selectFlightTab('Hotel')">
      Hotels <span class="erp-switch-count">{{ $hotelCount }}</span>
    </button>
  </div>

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container">
    <table class="table erp-table" id="flightTable">
      <thead>
        <tr>
          <th class="ps-3" style="width: 25%;">REF NO.</th>
          <th style="width: 35%;">TRAVELER</th>
          <th style="width: 15%;">COST</th>
          <th style="width: 13%;">STATUS</th>
          <th class="text-end pe-3" style="width: 12%;">ACTION</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($logs as $log)
          @php
            $isSettled = $log->trf?->reimbursements->contains('status', 'approved');
            $isUnderAudit = !$isSettled && $log->trf?->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg'])->isNotEmpty();
          @endphp
          <tr class="flight-row" data-type="{{ $log->type }}">
            <!-- 1. Ref No. -->
            <td class="ps-3">
              <a href="{{ route('flight.show', $log) }}" class="fw-bold font-monospace text-primary text-decoration-none erp-code-link" style="font-size: 13.5px; color: #0d6efd !important;">
                {{ $log->ref }}
              </a>
            </td>

            <!-- 2. Traveler -->
            <td>
              <span class="fw-semibold text-dark">
                {{ $log->trf?->employee?->name ?? 'Traveller' }}
              </span>
            </td>

            <!-- 3. Cost -->
            <td>
              <span class="fw-bold text-dark font-monospace">₹{{ number_format($log->cost, 2) }}</span>
            </td>

            <!-- 4. Status -->
            <td>
              @if ($log->mailTransactions->isNotEmpty())
                <span class="erp-badge erp-badge-success">Mailed</span>
              @elseif ($isSettled)
                <span class="erp-badge erp-badge-success">Settled</span>
              @elseif ($isUnderAudit)
                <span class="erp-badge erp-badge-warning">Under Audit</span>
              @else
                <span class="erp-badge erp-badge-info">Ready to Settle</span>
              @endif
            </td>

            <!-- 5. Action -->
            <td class="text-end pe-3">
              <div class="erp-row-actions">
                <a href="{{ route('flight.show', $log) }}" class="erp-btn-action-view">
                  <i class="bi bi-eye"></i> View
                </a>
                <div class="dropdown">
                  <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('flight.show', $log) }}">
                        <i class="bi bi-eye text-secondary"></i> View Details
                      </a>
                    </li>
                    @if ($log->trf_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
                      <li>
                        <a class="dropdown-item" href="{{ route('trf.show', $log->trf_id) }}">
                          <i class="bi bi-geo-alt text-secondary"></i> View Linked TRF
                        </a>
                      </li>
                    @endif
                    @if ($log->mailTransactions->isNotEmpty())
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="POST" action="{{ route('flight.resend-email', $log) }}" class="m-0">
                          @csrf
                          <button type="submit" class="dropdown-item text-secondary">
                            <i class="bi bi-envelope text-secondary"></i> Resend Email
                          </button>
                        </form>
                      </li>
                    @endif
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-1 text-muted opacity-50"></i>
              <span>No flight &amp; boarding transactions logged yet.</span>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>

<script>
function selectFlightTab(type) {
  document.querySelectorAll('#flightSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.type === type);
  });

  const rows = document.querySelectorAll('.flight-row');
  let visible = 0;

  rows.forEach(row => {
    const rowType = row.dataset.type || '';
    if (type === 'all' || rowType === type) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  const counter = document.getElementById('flightCounterCount');
  if (counter) counter.textContent = visible;
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Logistics' });
@endsection
