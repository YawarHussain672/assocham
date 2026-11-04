@extends('layouts.app')

@section('title', $reimbursement->claim_number)

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved — Settled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$reimbursement->status] ?? $statusMeta['pending_cfo'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Reimbursement &amp; Advance Claims</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <!-- Header Card -->
  <div class="detail-header-card">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#059669,#34d399);">
        <i class="bi bi-wallet2"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $reimbursement->claim_number }} — {{ $reimbursement->employee?->name ?? 'Claimant' }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Expense Settlement
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
          <span><i class="bi bi-airplane"></i> {{ $reimbursement->trf ? 'Linked: ' . $reimbursement->trf->trf_number : 'Non-TRF Claim' }}</span>
          <span><i class="bi bi-shield-check"></i> {{ ucfirst(str_replace('_', ' ', $reimbursement->routing_flag)) }}</span>
        </div>
      </div>
    </div>
    <div>
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:13px; font-weight:600; padding:6px 12px; border-radius:20px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Claim Financial Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Settlement Financial Breakdown</h6>
            </div>
            <span class="badge bg-light text-dark border px-2.5 py-1.5">
              Auto-Calculated
            </span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-receipt text-secondary"></i> Total Claim Amount</div>
                <div class="field-value text-dark" style="font-size:15.5px; font-weight:600;">₹{{ number_format($reimbursement->claim_amount, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Submitted expenditure bills</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-arrow-down-right-circle text-secondary"></i> Advance Disbursed</div>
                <div class="field-value text-secondary" style="font-size:15.5px; font-weight:600;">₹{{ number_format($reimbursement->advance_amount, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Previously sanctioned advance</div>
              </div>
            </div>

            <div class="col-12">
              <div class="p-3 rounded-3" style="background:{{ $reimbursement->balance_amount >= 0 ? '#ecfdf5' : '#fef2f2' }}; border:1px solid {{ $reimbursement->balance_amount >= 0 ? '#a7f3d0' : '#fecaca' }};">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="field-label d-flex align-items-center gap-1 mb-1" style="color:#334155;">
                      <i class="bi bi-calculator text-secondary"></i> Net Balance Settlement
                    </div>
                    <div style="font-size:17px; font-weight:600; color:{{ $reimbursement->balance_amount >= 0 ? '#059669' : '#c8102e' }};">
                      ₹{{ number_format(abs($reimbursement->balance_amount), 2) }}
                    </div>
                  </div>
                  <span class="badge" style="background:{{ $reimbursement->balance_amount >= 0 ? '#10b981' : '#ef4444' }}; color:#fff; font-weight:600; font-size:12px; padding:4px 9px;">
                    {{ $reimbursement->balance_amount >= 0 ? 'Payable to Traveler' : 'Refundable by Traveler' }}
                  </span>
                </div>
              </div>
            </div>
            @if ($reimbursement->reason)
              <div class="col-12 mt-3">
                <div class="dossier-field-box">
                  <div class="field-label mb-1"><i class="bi bi-info-circle text-secondary me-1"></i> Claim Justification / Reason</div>
                  <div class="text-dark" style="font-size:13.5px; font-weight:600;">{{ $reimbursement->reason }}</div>
                </div>
              </div>
            @endif
          </div>
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Claimant: {{ $reimbursement->employee?->name ?? '—' }}</span>
          <span>Department: {{ $reimbursement->employee?->department ?? ($reimbursement->trf?->employee?->department ?? 'General') }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Vertical Connected Workflow Timeline & Financial Audit (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-2 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #10b981, #059669); color:#fff; box-shadow:0 3px 8px rgba(16,185,129,0.25);">
                <i class="bi bi-receipt-cutoff fs-6"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark">Financial Audit Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">Expense Verification &amp; Accounts Settlement</div>
              </div>
            </div>
            @php
              $reimbApprovedCount = $reimbursement->approvals->where('status', 'approved')->count();
              $reimbTotalCount = $reimbursement->approvals->count();
              $isReimbFullyApproved = $reimbTotalCount > 0 && $reimbApprovedCount === $reimbTotalCount;
            @endphp
            <span class="badge {{ $isReimbFullyApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($reimbursement->status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isReimbFullyApproved)
                <i class="bi bi-check-circle-fill me-1"></i>Settled ({{ $reimbApprovedCount }}/{{ $reimbTotalCount }})
              @elseif ($reimbursement->status === 'rejected')
                <i class="bi bi-x-circle-fill me-1"></i>Declined
              @else
                <i class="bi bi-clock-history me-1"></i>{{ $reimbApprovedCount }}/{{ $reimbTotalCount }} Cleared
              @endif
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          <div class="travel-lifecycle-bar" title="Reimbursement Progress">
            <div class="bar-segment done" title="1. Claim Lodged"></div>
            <div class="bar-segment {{ $isReimbFullyApproved ? 'done' : ($reimbursement->status === 'rejected' ? 'bg-danger' : 'active') }}" title="Finance Audit"></div>
            <div class="bar-segment {{ $isReimbFullyApproved ? 'done' : '' }}" title="Accounts Settlement"></div>
          </div>

          <!-- Connected Workflow Timeline -->
          <div class="workflow-timeline position-relative">

            <!-- Step 1: Claim Lodged -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon approved">
                  <i class="bi bi-receipt"></i>
                </div>
                <div class="timeline-step-connector approved"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card approved-stage">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role text-success">Expense Claim Lodged</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Submitted
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $reimbursement->employee?->name ?? 'Claimant' }} (₹{{ number_format($reimbursement->claim_amount, 2) }})</span>
                    <span>{{ $reimbursement->submitted_at?->format('d M, H:i') ?? $reimbursement->created_at?->format('d M, H:i') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Approval Steps -->
            @foreach ($reimbursement->approvals->sortBy('level') as $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';

                $connectorClass = $isApproved ? 'approved' : ($isPending ? 'pending dashed' : 'dashed');
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending pulse-ring' : 'upcoming'));
                $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
                $stageTitle = ($approval->role_required === \App\Support\Roles::CFO)
                  ? 'Finance Audit (CFO)'
                  : ( ($approval->role_required === \App\Support\Roles::SG)
                      ? 'Executive Sanction (Secretary General)'
                      : ('Audit · ' . \App\Support\Roles::label($approval->role_required)) );
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    @if ($isApproved)
                      <i class="bi bi-shield-check"></i>
                    @elseif ($isRejected)
                      <i class="bi bi-x-lg"></i>
                    @elseif ($isPending)
                      <i class="bi bi-hourglass-split"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                  <div class="timeline-step-connector {{ $loop->last && !$isReimbFullyApproved ? '' : $connectorClass }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : ($isPending ? 'text-primary' : 'text-muted')) }}">
                        {{ $stageTitle }}
                      </span>
                      @if ($isApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Cleared
                        </span>
                      @elseif ($isRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Declined
                        </span>
                      @elseif ($isPending)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          In Review
                        </span>
                      @else
                        <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                          Upcoming
                        </span>
                      @endif
                    </div>

                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      @if ($isApproved)
                        <span>Cleared by {{ $approval->approver?->name ?? 'Authority' }}</span>
                        <span>{{ $approval->acted_at?->format('d M, H:i') }}</span>
                      @elseif ($isRejected)
                        <span>Declined by {{ $approval->approver?->name ?? 'Authority' }}</span>
                        <span>{{ $approval->acted_at?->format('d M, H:i') }}</span>
                      @elseif ($isPending)
                        <span>Assigned to {{ \App\Support\Roles::label($approval->role_required) }}</span>
                        <span class="text-warning">In audit</span>
                      @else
                        <span>Awaiting prior clearance</span>
                        <span>&mdash;</span>
                      @endif
                    </div>

                    @if ($approval->comments)
                      <div class="timeline-quote-bubble mt-1">
                        &ldquo;{{ $approval->comments }}&rdquo;
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach

            <!-- Step: Settlement Handover -->
            @if ($isReimbFullyApproved)
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon approved">
                    <i class="bi bi-cash-stack"></i>
                  </div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card approved-stage">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role text-success">Accounts Disbursement</span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Cleared
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>Claim finalized &amp; queued for bank release</span>
                      <span class="text-success">Ready</span>
                    </div>
                  </div>
                </div>
              </div>
            @endif

          </div>
        </div>

        <!-- Decision Box -->
        @if ($pendingApproval)
          <div class="mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">
            <div class="p-3 rounded-3" style="background:#fffcf5; border:1px solid #fde68a; box-shadow:0 2px 8px rgba(245,158,11,0.06);">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                  <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:20px; height:20px; font-size:11px;">
                    <i class="bi bi-pen"></i>
                  </div>
                  <span>Your Audit Action Required</span>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
                  {{ \App\Support\Roles::label($pendingApproval->role_required) }}
                </span>
              </div>

              <div class="text-secondary small mb-2.5" style="font-size:12px;">
                Verify expense invoices, travel advance deduction, and policy allowance limits before approving.
              </div>

              <!-- Quick Comment Chips -->
              <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
                <span class="quick-comment-chip" onclick="setReimbComment('Vouchers audited and verified.')">✓ Vouchers Audited</span>
                <span class="quick-comment-chip" onclick="setReimbComment('Settlement approved within policy bounds.')">💰 Approved for Payout</span>
              </div>

              <form method="POST" action="{{ route('reimb.approve', $reimbursement) }}" class="mb-2">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                  <input type="text" name="comments" id="reimbCommentsInput" class="form-control" placeholder="Audit remarks or notes (optional)">
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                  <i class="bi bi-check2-circle"></i>
                  <span>Approve &amp; Release Settlement</span>
                </button>
              </form>

              <form method="POST" action="{{ route('reimb.reject', $reimbursement) }}"
                    onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                  <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                  <i class="bi bi-x-circle"></i>
                  <span>Decline Claim</span>
                </button>
              </form>

              <script>
                function setReimbComment(text) {
                  var el = document.getElementById('reimbCommentsInput');
                  if (el) {
                    el.value = text;
                    el.focus();
                  }
                }
              </script>
            </div>
          </div>
        @elseif (in_array($reimbursement->status, ['pending_cfo', 'pending_sg']))
          <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Awaiting Financial Audit Signoff</div>
              <div class="text-secondary" style="font-size:12px;">
                Currently queued with <strong>{{ \App\Support\Roles::label($reimbursement->approvals->where('status', 'pending')->first()?->role_required ?? 'Reviewing Authority') }}</strong>.
              </div>
            </div>
          </div>
        @elseif ($reimbursement->status === 'approved')
          <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
            <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Settlement Fully Approved</div>
              <div class="text-secondary" style="font-size:12px;">
                Expense audit complete. Fund disbursement and ledger clearing are authorized.
              </div>
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Claim Detail' });
@endsection
