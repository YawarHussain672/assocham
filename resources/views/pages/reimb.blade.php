@extends('layouts.app')

@section('title', 'Reimbursement & Advance')

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'badge' => 'badge-soft-warning'],
    'pending_sg'  => ['label' => 'Pending — SG', 'badge' => 'badge-soft-warning'],
    'on_hold'     => ['label' => 'On Hold', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
  ];
  $flagMeta = [
    'standard'     => ['label' => 'Standard Route', 'badge' => 'badge-soft-secondary'],
    'asg_ch_claim' => ['label' => 'ASG/CH Claim', 'badge' => 'badge-soft-info'],
    'exception'    => ['label' => 'Off-TRF Exception', 'badge' => 'badge-soft-warning'],
  ];

  $totalReimb = $reimbursements->count();
  $pendingReimb = $reimbursements->filter(fn($r) => in_array($r->status, ['pending_cfo', 'pending_sg', 'on_hold']))->count();
  $approvedReimb = $reimbursements->where('status', 'approved')->count();
  $rejectedReimb = $reimbursements->where('status', 'rejected')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Expense Reimbursements &amp; Advances</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('reimb.create') }}" class="btn btn-sm btn-primary fw-semibold shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> New Claim
      </a>
      <a href="{{ route('grievance.create', ['category' => \App\Http\Controllers\GrievanceController::CATEGORY_SETTLEMENT]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chat-square-text me-1"></i> Settlement Grievance
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (!empty($pendingSettlementTrfs) && $pendingSettlementTrfs->isNotEmpty())
    @php
      $pendingTrfCount = $pendingSettlementTrfs->count();
      $extraPendingTrfs = max(0, $pendingTrfCount - 2);
    @endphp
    <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3" style="background:#fdf2f4; border-left:4px solid #c8102e !important;">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-white border text-primary" style="width:36px; height:36px;">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div style="font-size:13px;">
            <div class="fw-bold text-dark">
              {{ $pendingTrfCount }} Completed {{ Str::plural('Trip', $pendingTrfCount) }} Pending Settlement
            </div>
            <div class="text-muted" style="font-size:12px;">
              Ready to settle:
              @foreach ($pendingSettlementTrfs->take(2) as $pTrf)
                <a href="{{ route('reimb.create', ['trf_id' => $pTrf->id]) }}" class="fw-semibold text-primary text-decoration-underline me-2">{{ $pTrf->trf_number }} ({{ $pTrf->route ?: 'Trip' }})</a>
              @endforeach
              @if ($extraPendingTrfs > 0)
                <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-primary text-decoration-none align-baseline" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#pendingSettlementModal">
                  +{{ $extraPendingTrfs }} more &rarr;
                </button>
              @endif
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          @if ($extraPendingTrfs > 0)
            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold text-nowrap px-3 py-1" data-bs-toggle="modal" data-bs-target="#pendingSettlementModal">
              View All ({{ $pendingTrfCount }})
            </button>
          @endif
          <a href="{{ route('reimb.create', ['trf_id' => $pendingSettlementTrfs->first()->id]) }}" class="btn btn-sm btn-primary fw-semibold text-nowrap px-3 py-1">
            File Settlement Claim
          </a>
        </div>
      </div>
    </div>

    @if ($extraPendingTrfs > 0)
      <div class="modal fade" id="pendingSettlementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i>
                <h6 class="modal-title fw-bold mb-0">Completed Trips Pending Settlement ({{ $pendingTrfCount }})</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
                  <thead class="table-light">
                    <tr style="font-size:11px; text-transform:uppercase; color:#64748b;">
                      <th class="ps-3">TRF No.</th>
                      <th>Route</th>
                      <th>Trip Ended</th>
                      <th>Advance</th>
                      <th class="pe-3 text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($pendingSettlementTrfs as $pTrf)
                      <tr>
                        <td class="ps-3 fw-semibold text-primary text-nowrap">{{ $pTrf->trf_number }}</td>
                        <td class="text-dark">{{ $pTrf->route ?: '—' }}</td>
                        <td class="text-muted text-nowrap" style="font-size:11.5px;">{{ optional($pTrf->travel_end ?? $pTrf->travel_start)->format('d M Y') ?? '—' }}</td>
                        <td class="fw-medium text-dark text-nowrap">₹{{ number_format((float) $pTrf->requested_advance, 2) }}</td>
                        <td class="pe-3 text-end text-nowrap">
                          <a href="{{ route('reimb.create', ['trf_id' => $pTrf->id]) }}" class="btn btn-sm btn-primary fw-semibold px-2 py-1" style="font-size:11.5px;">
                            Settle
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endif

  <style>
    #reimbTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #reimbTable th {
      font-size: 12.5px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #475569;
      vertical-align: middle;
      padding: 12px 16px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #reimbTable td {
      vertical-align: middle;
      padding: 13px 16px;
      font-size: 13.5px;
      line-height: 1.45;
      border-bottom: 1px solid #f1f5f9;
    }
    .dash-review-btn {
      height: 28px;
      padding: 0 12px;
      font-size: 12px;
      font-weight: 600;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .reimb-action-cell {
      padding: 6px 8px !important;
      white-space: nowrap !important;
      text-align: center;
    }
    .action-alert-dot {
      position: absolute;
      top: -2px;
      right: -2px;
      width: 5.5px;
      height: 5.5px;
      border-radius: 50%;
      background-color: #f59e0b;
      border: 1px solid #ffffff;
      box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.45);
      pointer-events: none;
      z-index: 3;
    }
    .reimb-action-menu {
      min-width: 145px;
      max-width: 175px;
      padding: 2.5px;
      border-radius: 5px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.12);
      font-size: 11px;
      z-index: 1060;
    }
    .reimb-action-menu .dropdown-item {
      padding: 4px 7px;
      border-radius: 3.5px;
      font-size: 11px !important;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background-color 0.15s ease;
    }
    .reimb-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }
    .reimb-action-menu .dropdown-item.action-approve-item:hover {
      background-color: #ecfdf5;
      color: #059669;
    }
    .reimb-action-menu .dropdown-item.action-reject-item:hover {
      background-color: #fef2f2;
      color: #dc2626;
    }
  </style>

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="reimbSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectReimbTab('all')">
      All Claims <span class="erp-switch-count">{{ $totalReimb }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="pending" onclick="selectReimbTab('pending')">
      Pending Review <span class="erp-switch-count">{{ $pendingReimb }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="approved" onclick="selectReimbTab('approved')">
      Settled / Approved <span class="erp-switch-count">{{ $approvedReimb }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="rejected" onclick="selectReimbTab('rejected')">
      Rejected <span class="erp-switch-count">{{ $rejectedReimb }}</span>
    </button>
  </div>

  <!-- Unified Premium ERP Table Container -->
  <div class="erp-table-container">
    <div class="erp-table-toolbar">
      <div class="erp-toolbar-left">
        <div class="erp-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="reimbSearchInput" class="erp-search-input" placeholder="Search claim #, employee..." oninput="applyReimbFilters()">
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="erp-table table-hover align-middle mb-0" id="reimbTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 25%;">CLAIM NO.</th>
            <th style="width: 35%;">EMPLOYEE</th>
            <th style="width: 15%;">CLAIM AMOUNT</th>
            <th style="width: 13%;">STATUS</th>
            <th class="text-end pe-3" style="width: 12%;">ACTION</th>
          </tr>
        </thead>
          <tbody>
            @forelse ($reimbursements as $reimb)
              @php
                $meta = $statusMeta[$reimb->status] ?? ['label' => ucfirst($reimb->status), 'badge' => 'badge-soft-secondary'];
                $rStatusGroup = in_array($reimb->status, ['pending_cfo', 'pending_sg', 'on_hold']) ? 'pending' : $reimb->status;
              @endphp
              <tr class="reimb-row" data-status="{{ $rStatusGroup }}" data-search="{{ strtolower($reimb->claim_number . ' ' . ($reimb->employee?->name ?? '')) }}">
                <!-- 1. Claim No. -->
                <td class="ps-3">
                  <a href="{{ route('reimb.show', $reimb) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 13.5px;">
                    {{ $reimb->claim_number }}
                  </a>
                </td>

                <!-- 2. Employee -->
                <td>
                  <span class="fw-semibold text-main" style="font-size: 13.5px;">
                    {{ $reimb->employee?->name ?? '—' }}
                  </span>
                  @php
                    $itemCount = $reimb->items->count();
                    $billCount = $reimb->items->whereNotNull('bill_path')->count() + $reimb->attachments->count();
                  @endphp
                  @if ($itemCount > 0 || $billCount > 0)
                    <div class="d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 11px;">
                      @if ($itemCount > 0)
                        <span class="badge bg-light text-secondary border">{{ $itemCount }} items</span>
                      @endif
                      @if ($billCount > 0)
                        <span class="badge bg-light text-secondary border">{{ $billCount }} bills</span>
                      @endif
                    </div>
                  @endif
                </td>

                <!-- 3. Claim Amount -->
                <td>
                  <span class="fw-bold text-dark" style="font-size: 13.5px;">₹{{ number_format($reimb->claim_amount, 2) }}</span>
                  @if ((float) ($reimb->balance_amount ?? 0) < 0)
                    <div class="mt-0.5">
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10.5px;">Refundable</span>
                    </div>
                  @endif
                </td>

                <!-- 4. Status -->
                <td>
                  <span class="badge {{ $meta['badge'] }} status-badge-pipeline">{{ $meta['label'] }}</span>
                </td>

                <!-- 5. Action -->
                <td class="text-end pe-3">
                  <div class="erp-row-actions">
                    <a href="{{ route('reimb.show', $reimb) }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                    <div class="dropdown">
                      <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                        <li>
                          <a class="dropdown-item" href="{{ route('reimb.show', $reimb) }}">
                            <i class="bi bi-eye text-secondary"></i> View Details
                          </a>
                        </li>
                        @if ($reimb->trf_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
                          <li>
                            <a class="dropdown-item" href="{{ route('trf.show', $reimb->trf_id) }}">
                              <i class="bi bi-geo-alt text-secondary"></i> View Linked TRF
                            </a>
                          </li>
                        @endif
                        @if (in_array($reimb->status, ['pending_cfo', 'pending_sg'], true) || in_array($reimb->id, $actionableIds ?? [], true))
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <form method="POST" action="{{ route('reimb.approve', $reimb) }}" class="m-0">
                              @csrf
                              <button type="submit" class="dropdown-item text-success">
                                <i class="bi bi-check-circle text-success"></i> Approve &amp; Settle
                              </button>
                            </form>
                          </li>
                          <li>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reimbRejectModal-{{ $reimb->id }}">
                              <i class="bi bi-x-circle text-danger"></i> Reject / Decline
                            </button>
                          </li>
                        @endif
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                  <div class="fw-bold text-dark fs-6 mb-1">No reimbursement claims filed yet</div>
                  <div class="small text-muted">When claims are submitted, they will appear here.</div>
                </td>
              </tr>
            @endforelse

            <tr id="emptyReimbFilterRow" class="d-none">
              <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <div class="fw-bold text-dark fs-6 mb-1" id="reimbEmptyTitle">No claims found</div>
                <div class="small text-muted" id="reimbEmptySubtitle">There are currently no records matching this view.</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  @foreach ($reimbursements as $reimb)
    @if (in_array($reimb->status, ['pending_cfo', 'pending_sg'], true) || in_array($reimb->id, $actionableIds ?? [], true))
      <div class="modal fade" id="reimbRejectModal-{{ $reimb->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
          <form method="POST" action="{{ route('reimb.reject', $reimb) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-danger-subtle py-2 px-3 border-bottom border-danger-subtle">
              <div class="d-flex align-items-center gap-2 text-danger">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <h6 class="modal-title fw-bold mb-0">Decline {{ $reimb->claim_number }}</h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
              <label for="rejectReason-{{ $reimb->id }}" class="form-label fw-semibold text-dark small">Rejection Reason <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectReason-{{ $reimb->id }}" class="form-control" rows="3" placeholder="Explain discrepancy or missing tax invoice..." required></textarea>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-danger px-3 fw-semibold">Confirm Rejection</button>
            </div>
          </form>
        </div>
      </div>
    @endif
  @endforeach

</div>

<script>
let currentReimbStatus = 'all';

function selectReimbTab(status) {
  currentReimbStatus = status;
  applyReimbFilters();
}

function applyReimbFilters() {
  const searchTerm = (document.getElementById('reimbSearchInput')?.value || '').trim().toLowerCase();
  const statusFilter = currentReimbStatus;

  document.querySelectorAll('#reimbSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.status === statusFilter);
  });

  const rows = document.querySelectorAll('.reimb-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const rowStatus = row.dataset.status || '';
    const rowSearch = row.dataset.search || '';

    const matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
    const matchesSearch = (!searchTerm || rowSearch.includes(searchTerm));

    if (matchesStatus && matchesSearch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const counterEl = document.getElementById('reimbCounterCount');
  if (counterEl) {
    counterEl.textContent = visibleCount;
  }

  const emptyFilterRow = document.getElementById('emptyReimbFilterRow');
  const emptyTitle = document.getElementById('reimbEmptyTitle');
  const emptySubtitle = document.getElementById('reimbEmptySubtitle');

  if (emptyFilterRow) {
    if (visibleCount === 0) {
      emptyFilterRow.classList.remove('d-none');
      if (statusFilter === 'rejected') {
        emptyTitle.textContent = 'No rejected claims found';
        emptySubtitle.textContent = 'There are no claims with rejected status in this period.';
      } else if (statusFilter === 'pending') {
        emptyTitle.textContent = 'No pending claims found';
        emptySubtitle.textContent = 'There are no claims awaiting CFO or SG sign-off.';
      } else if (statusFilter === 'approved') {
        emptyTitle.textContent = 'No settled claims found';
        emptySubtitle.textContent = 'There are no settled or approved claims yet.';
      } else if (searchTerm) {
        emptyTitle.textContent = `No claims matched "${searchTerm}"`;
        emptySubtitle.textContent = 'Try checking for typos or searching by another employee name or claim #.';
      } else {
        emptyTitle.textContent = 'No reimbursement claims found';
        emptySubtitle.textContent = 'There are no records matching your selected filter.';
      }
    } else {
      emptyFilterRow.classList.add('d-none');
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  applyReimbFilters();
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Reimbursement & Advance' });
@endsection
