@extends('layouts.app')

@section('title', $po->po_number)

@php
  $statusMeta = [
    'pending'  => ['label' => 'Pending Approval', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved — Issued', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$po->status] ?? $statusMeta['pending'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Orders</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#2563eb,#3b82f6);">
        <i class="bi bi-receipt"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $po->po_number }}</div>
          @if ($po->isHighValue())
            <span class="badge" style="background:#fef3c7; color:#d97706; font-size:11.5px; font-weight:600; padding:3px 7px;">
              <i class="bi bi-star-fill"></i> High Value (&ge; ₹10L)
            </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
          <span><i class="bi bi-shop"></i> {{ $po->vendor->company_name }}</span>
          <span><i class="bi bi-file-earmark-text"></i> {{ $po->purchaseRequisition->pr_number }} ({{ $po->purchaseRequisition->department }})</span>
          @if ($po->created_at)
            <span><i class="bi bi-clock"></i> {{ $po->created_at->format('d M Y') }}</span>
          @endif
        </div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:13px; font-weight:600; padding:6px 12px; border-radius:20px;">
        {{ $meta['label'] }}
      </span>
      @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $po->status !== 'cancelled')
        <a href="{{ route('po.edit', $po) }}" class="btn btn-sm btn-outline-secondary" title="Edit PO details">
          <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @if ($po->status === 'pending' || $po->status === 'approved')
          <form method="POST" action="{{ route('po.cancel', $po) }}" onsubmit="return confirm('Are you sure you want to cancel this Purchase Order?');" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel PO">
              <i class="bi bi-x-circle me-1"></i> Cancel PO
            </button>
          </form>
        @endif
      @endif
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Order Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-info-circle" style="color:var(--maroon);"></i>
              <span>Purchase Order Specifications</span>
            </div>
            <span class="badge" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:12px; padding:5px 10px;">
              Total Qty: {{ $po->po_qty }}
            </span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-shop text-secondary"></i> Vendor</div>
                <div class="field-value text-dark" style="font-size:14.5px; font-weight:600;">{{ $po->vendor->company_name }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Code: {{ $po->vendor->vendor_code }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-currency-rupee text-secondary"></i> Total Amount (GST-incl.)</div>
                <div class="field-value" style="font-size:16px; font-weight:600; color:#c8102e;">₹{{ number_format($po->amount_incl_gst, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Committed Expenditure</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-calendar-event text-secondary"></i> Delivery Target</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ optional($po->delivery_date)->format('d M Y') ?? 'Not specified' }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Agreed fulfillment timeline</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-link-45deg text-secondary"></i> Linked Records</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">PR: {{ $po->purchaseRequisition->pr_number }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">RFQ: {{ $po->rfq?->rfq_number ?? 'Direct' }}</div>
              </div>
            </div>
          </div>

          <!-- Goods Receipt Progress -->
          @if ($po->status === 'approved')
            <div class="mt-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #cbd5e1;">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600; color:#1e293b;">
                  <i class="bi bi-truck text-secondary"></i>
                  <span>Goods Receipt Status</span>
                </div>
                <span class="badge" style="background:#e2e8f0; color:#334155; font-weight:600; font-size:12px;">
                  {{ $po->receivedQty() }} / {{ $po->po_qty }} Received
                </span>
              </div>
              @php $recvPct = $po->po_qty > 0 ? min(100, round(($po->receivedQty() / $po->po_qty) * 100)) : 0; @endphp
              <div class="progress mb-2" style="height:8px; border-radius:10px;">
                <div class="progress-bar" style="width:{{ $recvPct }}%; background-color:#10b981;"></div>
              </div>
              @if ($po->remainingQtyToReceive() > 0 && auth()->user()->role === \App\Support\Roles::PR_SR_EXEC)
                <a href="{{ route('grn.create') }}" class="btn-brand w-100 d-inline-flex justify-content-center align-items-center gap-2 mt-2" style="font-weight:600; height:38px; text-decoration:none;">
                  <i class="bi bi-box-arrow-in-down"></i> Log Goods Receipt (GRN)
                </a>
              @elseif ($po->remainingQtyToReceive() === 0)
                <div class="text-success d-flex align-items-center gap-1" style="font-size:12.5px; font-weight:600;">
                  <i class="bi bi-check-circle-fill"></i> All items accounted for in inventory.
                </div>
              @endif
            </div>
          @endif

          @if ($po->grn)
            <div class="mt-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #cbd5e1;">
              <div class="field-label mb-2"><i class="bi bi-box-seam text-secondary me-1"></i> Fulfilled via Goods Receipt</div>
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $po->grn->grn_number }}</div>
                  <div class="text-secondary" style="font-size:12px; font-weight:500;">Qty Received: {{ $po->grn->qty_received }} · Accepted: {{ $po->grn->qty_accepted }}</div>
                </div>
                <a href="{{ route('grn.show', $po->grn) }}" class="btn btn-outline-secondary btn-sm" style="font-weight:600;">
                  <i class="bi bi-eye"></i> View GRN
                </a>
              </div>
              @if ($po->grn->status === 'approved')
                <div class="badge bg-success-subtle text-success border border-success-subtle mt-2 p-2 w-100 text-start d-flex align-items-center gap-2" style="font-size:12px; font-weight:600;">
                  <i class="bi bi-check-all fs-5"></i> Fully received &amp; accounted for in inventory.
                </div>
              @endif
            </div>
          @endif
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Created by {{ $po->creator?->name ?? 'System' }}</span>
          <span>Department: {{ $po->purchaseRequisition->department }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Vertical Connected Workflow Timeline & Approval Routing (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-2 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #059669, #10b981); color:#fff; box-shadow:0 3px 8px rgba(16,185,129,0.25);">
                <i class="bi bi-file-earmark-check-fill fs-6"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark">PO Governance Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">Order Issuance &amp; Multi-Tier Sanction</div>
              </div>
            </div>
            @php
              $poApprovedCount = $po->approvals->where('status', 'approved')->count();
              $poTotalCount = $po->approvals->count();
              $isPoFullyApproved = $poTotalCount > 0 && $poApprovedCount === $poTotalCount;
            @endphp
            <span class="badge {{ $isPoFullyApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($po->status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isPoFullyApproved)
                <i class="bi bi-check-circle-fill me-1"></i>Issued ({{ $poApprovedCount }}/{{ $poTotalCount }})
              @elseif ($po->status === 'rejected')
                <i class="bi bi-x-circle-fill me-1"></i>Rejected
              @else
                <i class="bi bi-clock-history me-1"></i>{{ $poApprovedCount }}/{{ $poTotalCount }} Signed
              @endif
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          <div class="travel-lifecycle-bar" title="Purchase Order Progress">
            <div class="bar-segment done" title="1. PO Generated"></div>
            <div class="bar-segment {{ $poApprovedCount >= 1 ? 'done' : ($po->status === 'pending' ? 'active' : '') }}" title="Admin Review"></div>
            @if ($poTotalCount >= 2)
              <div class="bar-segment {{ $poApprovedCount >= 2 ? 'done' : ($poApprovedCount === 1 ? 'active' : '') }}" title="Finance Clearance"></div>
            @endif
            @if ($poTotalCount >= 3)
              <div class="bar-segment {{ $isPoFullyApproved ? 'done' : ($poApprovedCount === 2 ? 'active' : '') }}" title="Executive Sanction"></div>
            @endif
            <div class="bar-segment {{ ($po->grns && $po->grns->isNotEmpty()) ? 'done' : ($isPoFullyApproved ? 'active' : '') }}" title="Goods Receipt"></div>
          </div>

          <!-- Connected Workflow Timeline -->
          <div class="workflow-timeline position-relative">

            <!-- Step 1: PO Created -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon approved">
                  <i class="bi bi-check-lg"></i>
                </div>
                <div class="timeline-step-connector approved"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card approved-stage">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role text-success">Order Drafted</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Issued
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $po->vendor?->company_name }} (₹{{ number_format($po->amount_incl_gst, 2) }})</span>
                    <span>{{ $po->created_at?->format('d M, H:i') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Approval Steps -->
            @php
              $poStageTitles = [
                \App\Support\Roles::ADMIN_HEAD => 'Administrative Clearance (Admin Head)',
                \App\Support\Roles::CFO => 'Financial Sanction (CFO)',
                \App\Support\Roles::SG => 'Executive Authorization (Secretary General)',
              ];
            @endphp
            @foreach ($po->approvals->sortBy('level') as $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';

                $connectorClass = $isApproved ? 'approved' : ($isPending ? 'pending dashed' : 'dashed');
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending pulse-ring' : 'upcoming'));
                $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
                $stageTitle = $poStageTitles[$approval->role_required] ?? \App\Support\Roles::label($approval->role_required);
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    @if ($isApproved)
                      <i class="bi bi-check-lg"></i>
                    @elseif ($isRejected)
                      <i class="bi bi-x-lg"></i>
                    @elseif ($isPending)
                      <i class="bi bi-hourglass-split"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                  <div class="timeline-step-connector {{ $loop->last && !$isPoFullyApproved ? '' : $connectorClass }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isPending ? 'text-primary' : '') }}">
                        {{ $stageTitle }}
                      </span>
                      @if ($isApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Approved
                        </span>
                      @elseif ($isRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Declined
                        </span>
                      @elseif ($isPending)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Action Due
                        </span>
                      @else
                        <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                          Standby
                        </span>
                      @endif
                    </div>

                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>
                        @if ($approval->approver)
                          {{ $approval->approver->name }}
                        @elseif ($isPending)
                          <span class="text-warning fw-semibold">Action Required</span>
                        @else
                          <span>Awaiting Stage</span>
                        @endif
                      </span>
                      @if ($approval->acted_at)
                        <span>{{ $approval->acted_at->format('d M, H:i') }}</span>
                      @endif
                    </div>

                    @if ($approval->comments)
                      <div class="timeline-quote-bubble">
                        &ldquo;{{ $approval->comments }}&rdquo;
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach

            <!-- Step: GRN Fulfillment Handover -->
            @if ($isPoFullyApproved)
              @php
                $hasGrn = $po->grns && $po->grns->isNotEmpty();
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $hasGrn ? 'approved' : 'info pulse-ring' }}">
                    <i class="bi {{ $hasGrn ? 'bi-check-all' : 'bi-truck' }}"></i>
                  </div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $hasGrn ? 'approved-stage' : 'active-stage' }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $hasGrn ? 'text-success' : 'text-primary' }}">Delivery &amp; Goods Receipt</span>
                      <span class="badge {{ $hasGrn ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-info-subtle text-info border border-info-subtle' }} py-0.5 px-1.5" style="font-size:10px;">
                        {{ $hasGrn ? 'Received' : 'Pending Delivery' }}
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5">
                      @if ($hasGrn)
                        <span>{{ $po->grns->count() }} GRN inspection record(s) logged</span>
                      @else
                        <span>PO released to vendor &bull; Awaiting delivery at store</span>
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
          @php
            $pendingPoStage = $poStageTitles[$pendingApproval->role_required] ?? ('Stage ' . $pendingApproval->level . ' · ' . \App\Support\Roles::label($pendingApproval->role_required));
          @endphp
          <div class="mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">
            <div class="p-3 rounded-3" style="background:#fffcf5; border:1px solid #fde68a; box-shadow:0 2px 8px rgba(245,158,11,0.06);">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                  <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:20px; height:20px; font-size:11px;">
                    <i class="bi bi-pen"></i>
                  </div>
                  <span>Your Action Required</span>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
                  {{ $pendingPoStage }}
                </span>
              </div>

              <div class="text-secondary small mb-2.5" style="font-size:12px;">
                Confirm contractual terms, vendor empanelment status, and delivery schedule before authorizing.
              </div>

              <!-- Quick Comment Chips -->
              <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
                <span class="quick-comment-chip" onclick="setPoComment('Approved for issuance.')">✓ Approved for Issuance</span>
                <span class="quick-comment-chip" onclick="setPoComment('Commercial terms and deliverables verified.')">📋 Terms Verified</span>
                <span class="quick-comment-chip" onclick="setPoComment('Budget commitment cleared.')">💰 Budget Cleared</span>
              </div>

              <form method="POST" action="{{ route('po.approve', $po) }}" class="mb-2">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                  <input type="text" name="comments" id="poCommentsInput" class="form-control" placeholder="Approval justification or remarks (optional)">
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                  <i class="bi bi-check2-circle"></i>
                  <span>Approve &amp; Issue PO</span>
                </button>
              </form>

              <form method="POST" action="{{ route('po.reject', $po) }}"
                    onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                  <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                  <i class="bi bi-x-circle"></i>
                  <span>Decline Purchase Order</span>
                </button>
              </form>

              <script>
                function setPoComment(text) {
                  var el = document.getElementById('poCommentsInput');
                  if (el) {
                    el.value = text;
                    el.focus();
                  }
                }
              </script>
            </div>
          </div>
        @elseif ($po->status === 'pending')
          <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Awaiting Order Sign-Off</div>
              <div class="text-secondary" style="font-size:12px;">
                Currently with <strong>{{ \App\Support\Roles::label($po->approvals->where('status', 'pending')->first()?->role_required ?? 'Reviewing Authority') }}</strong>.
              </div>
            </div>
          </div>
        @elseif ($po->status === 'approved')
          <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
            <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">PO Formally Issued</div>
              <div class="text-secondary" style="font-size:12px;">
                Order authorized and contractually committed to {{ $po->vendor?->company_name }}.
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
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'PO Detail' });
@endsection
