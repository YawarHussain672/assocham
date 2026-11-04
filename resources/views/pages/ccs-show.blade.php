@extends('layouts.app')

@section('title', $ccs->ccs_number)

@php
  $statusMeta = [
    'pending_committee' => ['label' => 'Pending — Committee', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'          => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'          => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'             => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$ccs->status] ?? $statusMeta['draft'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('ccs') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Cost Comparative Statements</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#7c3aed,#9333ea);">
        <i class="bi bi-file-earmark-bar-graph"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $ccs->ccs_number }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Comparative Statement
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
          <span><i class="bi bi-broadcast"></i> RFQ: {{ $ccs->rfq->rfq_number }}</span>
          <span><i class="bi bi-file-earmark-text"></i> PR: {{ $ccs->rfq->purchaseRequisition->pr_number }} ({{ $ccs->rfq->purchaseRequisition->department }})</span>
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
    <!-- Left Column: Vendor Quotes & Comparison (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-table" style="color:var(--maroon);"></i>
              <span>Vendor Quotations Comparison ({{ $ccs->quotes->count() }})</span>
            </div>
            <span class="badge" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; font-weight:600; font-size:12px; padding:5px 10px;">
              <i class="bi bi-award"></i> Lowest Bid Ranked
            </span>
          </div>

          <div style="overflow-x:auto;">
            <table class="table table-hover align-middle">
              <thead>
                <tr class="text-muted" style="font-size:12.5px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">
                  <th>Rank</th>
                  <th>Vendor Partner</th>
                  <th class="text-end">Quoted Price</th>
                  <th>Delivery</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($ccs->quotes->sortBy('quoted_cost') as $quote)
                  @php $isL1 = $quote->rank === 'L1'; @endphp
                  <tr style="font-size:14px; {{ $isL1 ? 'background:#f0fdf4;' : '' }}">
                    <td>
                      <span class="badge" style="background:{{ $isL1 ? '#10b981' : '#64748b' }}; color:#fff; font-weight:600; font-size:11.5px; padding:3px 7px;">
                        {{ $isL1 ? 'Awarded (Lowest)' : $quote->rank }}
                      </span>
                    </td>
                    <td class="fw-semibold text-dark" style="font-weight:600;">{{ $quote->vendor->company_name }}</td>
                    <td class="text-end fw-semibold" style="font-weight:600; color:{{ $isL1 ? '#059669' : '#0f172a' }};">
                      ₹{{ number_format($quote->quoted_cost, 2) }}
                    </td>
                    <td class="text-muted" style="font-size:13px;">{{ $quote->delivery_days !== null ? $quote->delivery_days.' days' : '—' }}</td>
                    <td class="text-muted" style="font-size:12.5px;">{{ $quote->remarks ?: '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        @if ($ccs->status === 'approved' && $ccs->purchaseOrder)
          <div class="mt-3 pt-3 border-top">
            <a href="{{ route('po.show', $ccs->purchaseOrder) }}" class="btn-outline-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:40px; text-decoration:none;">
              <i class="bi bi-receipt"></i>
              <span>View Issued Purchase Order {{ $ccs->purchaseOrder->po_number }}</span>
            </a>
          </div>
        @elseif ($ccs->status === 'approved')
          <div class="p-3 rounded-3 mt-3 d-flex justify-content-between align-items-center" style="background:#ecfdf5; border:1px solid #a7f3d0; font-size:13px;">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success fs-5"></i>
              <span>Approved — {{ $ccs->lowestQuote()?->vendor?->company_name }} awarded.</span>
            </div>
            @if (auth()->user()->role === \App\Support\Roles::BUYER)
              <a href="{{ route('po.create') }}" class="btn-brand btn-sm d-inline-flex align-items-center gap-1" style="font-weight:600; text-decoration:none;">
                <i class="bi bi-plus-circle"></i>
                <span>Raise Purchase Order</span>
              </a>
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Right Column: Colored Icon Timeline & Committee Decision (col-lg-5) -->
    <!-- Right Column: Vertical Connected Workflow Timeline & Committee Decision (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-2 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #7c3aed, #6366f1); color:#fff; box-shadow:0 3px 8px rgba(99,102,241,0.25);">
                <i class="bi bi-diagram-3-fill fs-6"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark">Committee Evaluation Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">Comparative Sourcing &amp; Award Sanction</div>
              </div>
            </div>
            @php
              $ccsApprovedCount = $ccs->approvals->where('status', 'approved')->count();
              $ccsTotalCount = $ccs->approvals->count();
              $isCcsFullyApproved = $ccsTotalCount > 0 && $ccsApprovedCount === $ccsTotalCount;
            @endphp
            <span class="badge {{ $isCcsFullyApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($ccs->status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isCcsFullyApproved)
                <i class="bi bi-check-circle-fill me-1"></i>Authorized ({{ $ccsApprovedCount }}/{{ $ccsTotalCount }})
              @elseif ($ccs->status === 'rejected')
                <i class="bi bi-x-circle-fill me-1"></i>Rejected
              @else
                <i class="bi bi-clock-history me-1"></i>{{ $ccsApprovedCount }}/{{ $ccsTotalCount }} Endorsed
              @endif
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          <div class="travel-lifecycle-bar" title="Comparative Statement Progress">
            <div class="bar-segment done" title="1. Quotations Compiled"></div>
            <div class="bar-segment {{ $isCcsFullyApproved ? 'done' : ($ccs->status === 'pending_committee' ? 'active' : '') }}" title="Committee Review"></div>
            <div class="bar-segment {{ $ccs->purchaseOrder ? 'done' : ($isCcsFullyApproved ? 'active' : '') }}" title="PO Generation"></div>
          </div>

          <!-- Connected Workflow Timeline -->
          <div class="workflow-timeline position-relative">

            <!-- Step 1: Comparative Compiled -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon approved">
                  <i class="bi bi-bar-chart-line"></i>
                </div>
                <div class="timeline-step-connector approved"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card approved-stage">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role text-success">Comparative Statement Prepared</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Compiled
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>RFQ {{ $ccs->rfq?->rfq_number }} · {{ $ccs->quotes->count() }} Quotes</span>
                    <span>{{ $ccs->created_at?->format('d M, H:i') }}</span>
                  </div>
                  @php $l1Quote = $ccs->lowestQuote(); @endphp
                  @if ($l1Quote)
                    <div class="timeline-step-time text-success mt-0.5" style="font-size:11px;">
                      <i class="bi bi-award me-1"></i>Rec: <strong>{{ $l1Quote->vendor?->company_name }}</strong> (₹{{ number_format($l1Quote->quoted_cost, 2) }})
                    </div>
                  @endif
                </div>
              </div>
            </div>

            <!-- Approval Steps -->
            @foreach ($ccs->approvals->sortBy('level') as $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';

                $connectorClass = $isApproved ? 'approved' : ($isPending ? 'pending dashed' : 'dashed');
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending pulse-ring' : 'upcoming'));
                $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
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
                  <div class="timeline-step-connector {{ $loop->last && !$isCcsFullyApproved ? '' : $connectorClass }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : ($isPending ? 'text-primary' : 'text-muted')) }}">
                        Committee Review ({{ \App\Support\Roles::label($approval->role_required) }})
                      </span>
                      @if ($isApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Endorsed
                        </span>
                      @elseif ($isRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Declined
                        </span>
                      @elseif ($isPending)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Pending
                        </span>
                      @else
                        <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                          Upcoming
                        </span>
                      @endif
                    </div>

                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      @if ($isApproved)
                        <span>Endorsed by {{ $approval->approver?->name ?? 'Committee' }}</span>
                        <span>{{ $approval->acted_at?->format('d M, H:i') }}</span>
                      @elseif ($isRejected)
                        <span>Declined by {{ $approval->approver?->name ?? 'Committee' }}</span>
                        <span>{{ $approval->acted_at?->format('d M, H:i') }}</span>
                      @elseif ($isPending)
                        <span>Assigned to {{ \App\Support\Roles::label($approval->role_required) }}</span>
                        <span class="text-warning">In review</span>
                      @else
                        <span>Awaiting prior committee stage</span>
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

            <!-- Step: PO Generation Handover -->
            @if ($isCcsFullyApproved || $ccs->purchaseOrder)
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $ccs->purchaseOrder ? 'approved' : 'info pulse-ring' }}">
                    <i class="bi {{ $ccs->purchaseOrder ? 'bi-check2' : 'bi-bag-check' }}"></i>
                  </div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $ccs->purchaseOrder ? 'approved-stage' : 'active-stage' }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $ccs->purchaseOrder ? 'text-success' : 'text-info' }}">PO Handover</span>
                      @if ($ccs->purchaseOrder)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Created
                        </span>
                      @else
                        <span class="badge bg-info-subtle text-info border border-info-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Ready
                        </span>
                      @endif
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      @if ($ccs->purchaseOrder)
                        <span>Linked PO: <strong>{{ $ccs->purchaseOrder->po_number }}</strong></span>
                        <span>{{ $ccs->purchaseOrder->created_at?->format('d M, H:i') }}</span>
                      @else
                        <span>Ready to issue Purchase Order</span>
                        <span class="text-info">Actionable</span>
                      @endif
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
                  <span>Your Decision Required</span>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
                  Procurement Committee
                </span>
              </div>

              <div class="text-secondary small mb-2.5" style="font-size:12px;">
                Confirm lowest qualified compliant bid ranking and delivery commitments before endorsing.
              </div>

              <!-- Quick Comment Chips -->
              <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
                <span class="quick-comment-chip" onclick="setCcsComment('Lowest compliant bid verified and approved.')">✓ Lowest Bid Verified</span>
                <span class="quick-comment-chip" onclick="setCcsComment('Authorized for Purchase Order creation.')">✓ Authorized for PO</span>
              </div>

              <form method="POST" action="{{ route('ccs.approve', $ccs) }}" class="mb-2">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                  <input type="text" name="comments" id="ccsCommentsInput" class="form-control" placeholder="Committee review comments or notes (optional)">
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                  <i class="bi bi-check2-circle"></i>
                  <span>Approve Statement (Authorize Award)</span>
                </button>
              </form>

              <form method="POST" action="{{ route('ccs.reject', $ccs) }}"
                    onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                  <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                  <i class="bi bi-x-circle"></i>
                  <span>Decline Statement</span>
                </button>
              </form>

              <script>
                function setCcsComment(text) {
                  var el = document.getElementById('ccsCommentsInput');
                  if (el) {
                    el.value = text;
                    el.focus();
                  }
                }
              </script>
            </div>
          </div>
        @elseif ($ccs->status === 'pending_committee')
          <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Awaiting Committee Clearance</div>
              <div class="text-secondary" style="font-size:12px;">
                Currently under Procurement Committee review. You will be notified once a decision is made.
              </div>
            </div>
          </div>
        @elseif ($ccs->status === 'approved')
          <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
            <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">CCS Endorsed &amp; Authorized</div>
              <div class="text-secondary" style="font-size:12px;">
                Comparative matrix approved. Award is authorized to the lowest compliant vendor.
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
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'CCS Detail' });
@endsection
