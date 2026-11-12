@extends('layouts.app')

@section('title', 'Reimbursement & Advance')

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'badge' => 'badge-soft-warning'],
    'pending_sg'  => ['label' => 'Pending — SG', 'badge' => 'badge-soft-warning'],
    'approved'    => ['label' => 'Approved', 'badge' => 'badge-soft-success'],
    'rejected'    => ['label' => 'Rejected', 'badge' => 'badge-soft-danger'],
  ];
  $flagMeta = [
    'standard'     => ['label' => 'Standard Route', 'badge' => 'badge-soft-secondary'],
    'asg_ch_claim' => ['label' => 'ASG/CH Claim', 'badge' => 'badge-soft-info'],
    'exception'    => ['label' => 'Off-TRF Exception', 'badge' => 'badge-soft-warning'],
  ];

  $totalReimb = $reimbursements->count();
  $pendingCfo = $reimbursements->where('status', 'pending_cfo')->count();
  $pendingSg = $reimbursements->where('status', 'pending_sg')->count();
  $approvedReimb = $reimbursements->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Expense Reimbursements &amp; Advances</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('grievance.create', ['category' => \App\Http\Controllers\GrievanceController::CATEGORY_SETTLEMENT]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chat-square-text me-1"></i> Settlement Grievance
      </a>
      <a href="{{ route('reimb.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Claim
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (!empty($pendingSettlementTrfs) && $pendingSettlementTrfs->isNotEmpty())
    @php
      $pendingTrfCount = $pendingSettlementTrfs->count();
      $extraPendingTrfs = max(0, $pendingTrfCount - 2);
    @endphp
    <div class="alert border-0 shadow-sm mb-3 p-3 rounded-3" style="background:#EFF6FF; border-left:4px solid #2563EB !important;">
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

  <!-- Status KPI Tab Cards (Total, Pending CFO, SG Overrides, Settled) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Total</span>
            <div class="stat-icon-box icon-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $totalReimb }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Pending CFO</span>
            <div class="stat-icon-box icon-warning" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingCfo }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-secondary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">SG Overrides</span>
            <div class="stat-icon-box icon-secondary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-patch-exclamation"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $pendingSg }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">Settled</span>
            <div class="stat-icon-box icon-success" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.45rem; font-weight: 700;">{{ $approvedReimb }}</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    #reimbTable {
      width: 100% !important;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    #reimbTable th {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: #64748b;
      vertical-align: middle;
      padding: 10px 10px;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
    }
    #reimbTable td {
      vertical-align: middle;
      padding: 10px 10px;
      font-size: 12px;
      line-height: 1.4;
      border-bottom: 1px solid #f1f5f9;
    }
    .reimb-action-menu {
      min-width: 175px;
      padding: 5px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
      font-size: 12px;
      z-index: 1060;
    }
    .reimb-action-menu .dropdown-item {
      padding: 6px 9px;
      border-radius: 5px;
      font-size: 12px !important;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .reimb-action-menu .dropdown-item:hover {
      background-color: #f1f5f9;
    }
    .reimb-action-dot {
      position: absolute;
      top: -3px;
      right: -3px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #f59e0b;
      border: 1.5px solid #ffffff;
      pointer-events: none;
    }
  </style>

  <!-- Claims Table Card -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-receipt-cutoff text-primary fs-5"></i>
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Reimbursement &amp; Advance Registry</h6>
        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 11px;">
          {{ $totalReimb }} {{ Str::plural('Claim', $totalReimb) }}
        </span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="reimbTable">
          <thead class="table-light border-bottom">
            <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
              <th class="ps-3">CLAIM NO.</th>
              <th>EMPLOYEE &amp; TRF</th>
              <th>CLAIM</th>
              <th>ADVANCE</th>
              <th>NET BALANCE</th>
              <th>STATUS</th>
              <th class="text-end pe-3">ACTION</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reimbursements as $reimb)
              @php
                $meta = $statusMeta[$reimb->status] ?? ['label' => ucfirst($reimb->status), 'badge' => 'badge-soft-secondary'];
                $fMeta = $flagMeta[$reimb->routing_flag ?? 'standard'] ?? ['label' => 'Standard', 'badge' => 'badge-soft-secondary'];
                $netBal = (float) ($reimb->balance_amount ?? 0);
                $isNil = abs($netBal) < 0.01;
                $isPayable = $netBal > 0;
                $canApprove = in_array($reimb->id, $actionableIds ?? [], true);
              @endphp
              <tr>
                <td class="ps-3 text-nowrap">
                  <a href="{{ route('reimb.show', $reimb) }}" class="fw-bold font-monospace text-primary text-decoration-none" style="font-size: 12.5px;">
                    {{ $reimb->claim_number }}
                  </a>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $reimb->employee?->name ?? '—' }}</div>
                  <div class="text-muted" style="font-size: 11px;">
                    @if ($reimb->trf)
                      <a href="{{ route('trf.show', $reimb->trf) }}" class="text-muted text-decoration-none">{{ $reimb->trf->trf_number }}</a>
                    @else
                      Off-TRF Direct
                    @endif
                  </div>
                </td>
                <td>
                  <div class="fw-bold text-dark text-nowrap">₹{{ number_format($reimb->claim_amount, 2) }}</div>
                  @php
                    $billCount = $reimb->attachments->count() + $reimb->items->whereNotNull('bill_path')->count();
                    $itemCount = $reimb->items->count();
                  @endphp
                  <div class="text-muted" style="font-size: 11px;">
                    {{ $fMeta['label'] }}@if ($itemCount > 0) &bull; {{ $itemCount }} {{ Str::plural('item', $itemCount) }}@endif @if ($billCount > 0) &bull; {{ $billCount }} {{ Str::plural('bill', $billCount) }}@endif
                  </div>
                </td>
                <td class="text-nowrap text-muted">
                  ₹{{ number_format($reimb->advance_amount, 2) }}
                </td>
                <td>
                  <div class="fw-bold text-nowrap {{ $isNil ? 'text-secondary' : ($isPayable ? 'text-success' : 'text-danger') }}">₹{{ number_format(abs($netBal), 2) }}</div>
                  <div class="text-muted" style="font-size: 11px;">{{ $isNil ? 'Nil balance' : ($isPayable ? 'Payable' : 'Refundable') }}</div>
                </td>
                <td class="text-nowrap">
                  <span class="badge {{ $meta['badge'] }} px-2 py-1" style="font-size: 11px;">{{ $meta['label'] }}</span>
                </td>
                <td class="text-end pe-3 text-nowrap">
                  <div class="dropdown d-inline-block">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary px-2 py-1 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-none dropdown-toggle position-relative"
                            data-bs-toggle="dropdown"
                            data-bs-popper-config='{"strategy":"fixed"}'
                            aria-expanded="false"
                            style="font-size: 11.5px; height: 27px; line-height: 1;">
                      <i class="bi bi-gear" style="font-size: 11px;"></i>
                      <span>Action</span>
                      @if ($canApprove)
                        <span class="reimb-action-dot"></span>
                      @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end reimb-action-menu shadow-sm">
                      <li>
                        <a class="dropdown-item" href="{{ route('reimb.show', $reimb) }}">
                          <i class="bi bi-eye text-primary"></i>
                          <span>View Details</span>
                        </a>
                      </li>
                      @if ($canApprove)
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                          <form method="POST" action="{{ route('reimb.approve', $reimb) }}" class="m-0">
                            @csrf
                            <input type="hidden" name="comments" value="Settlement audited and approved.">
                            <button type="submit" class="dropdown-item text-success">
                              <i class="bi bi-check-circle-fill text-success"></i>
                              <span>Approve &amp; Settle</span>
                            </button>
                          </form>
                        </li>
                        <li>
                          <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reimbRejectModal-{{ $reimb->id }}">
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <span>Reject / Decline</span>
                          </button>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-1 text-soft"></i>
                  <span>No reimbursement claims filed yet.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @foreach ($reimbursements as $reimb)
    @if (in_array($reimb->id, $actionableIds ?? [], true))
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
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Reimbursement & Advance' });
@endsection
