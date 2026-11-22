@extends('layouts.app')

@section('title', $pr->pr_number)

@php
$statusMeta = [
  'pending_l1' => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  'pending_l2' => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  'pending_l3' => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  'pending_l4' => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  'approved'   => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
  'rejected'   => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  'draft'      => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
];
$stageNames = [
  1 => 'Operational Review (Basu)',
  2 => 'Administrative Compliance (Ambika)',
  3 => 'Finance Clearance (CFO)',
  4 => 'Executive Sanction (Secretary General)',
];
$designationShort = [
  0 => 'PR & GRN Creator',
  1 => 'DSG',
  2 => 'Head of States',
  3 => 'CFO',
  4 => 'SG',
];
$approvals = $pr->approvals->sortByDesc('id')->unique('level')->sortBy('level');
$approvedCount = $approvals->where('status', 'approved')->count();
$totalCount = $approvals->count();
$isAllApproved = $totalCount > 0 && $approvedCount === $totalCount;
$isPrRejected = ($pr->status === 'rejected');
$activePendingLevel = (!$isPrRejected && !$isAllApproved) ? ($approvals->where('status', 'pending')->first()?->level ?? 1) : 0;

// Real-time synchronization check: if all approval levels are approved, state is Approved
$effectiveStatus = $isAllApproved ? 'approved' : $pr->status;
$meta = $statusMeta[$effectiveStatus] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <style>
    .btn-pdf-action {
      background-color: #fdf2f4 !important;
      color: #c8102e !important;
      border: 1px solid #c8102e !important;
      font-size: 13px !important;
      border-radius: 6px !important;
      transition: all 0.15s ease-in-out !important;
      text-decoration: none !important;
    }
    .btn-pdf-action:hover, .btn-pdf-action:focus {
      background-color: #c8102e !important;
      color: #ffffff !important;
      border-color: #c8102e !important;
      box-shadow: 0 2px 4px rgba(200, 16, 46, 0.22) !important;
    }
    .btn-pdf-action:hover i, .btn-pdf-action:focus i {
      color: #ffffff !important;
    }
    .btn-more-action {
      background-color: #ffffff !important;
      color: #475569 !important;
      border: 1px solid #e2e8f0 !important;
      font-size: 13px !important;
      border-radius: 8px !important;
      transition: all 0.15s ease-in-out !important;
    }
    .btn-more-action i {
      color: #64748b !important;
    }
    .btn-more-action:hover, .btn-more-action:focus, .show > .btn-more-action {
      background-color: #f1f5f9 !important;
      color: #0f172a !important;
      border-color: #cbd5e1 !important;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }
    .btn-more-action:hover i, .btn-more-action:focus i, .show > .btn-more-action i {
      color: #0f172a !important;
    }
  </style>

  <a href="{{ route('requisition') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Requisitions</span>
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

  @if ($pr->status === 'on_hold')
  <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-3" style="border-radius: 8px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
        <i class="bi bi-pause-circle-fill"></i>
      </div>
      <div>
        <div class="fw-bold text-dark fs-6 mb-0.5">This Purchase Requisition is Currently On Hold</div>
        <div class="text-secondary small">
          Review is paused at the current approval level pending required documents or clarifications.
        </div>
      </div>
    </div>
    <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
  </div>
  @endif



  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100 position-relative" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); z-index: 20;">
    
    <!-- Top Row: PR Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $pr->pr_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              @if ($pr->isCopex())
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fw-semibold" style="font-size: 11px;">
                  <i class="bi bi-shield-lock-fill me-1"></i> CapEx
                </span>
              @elseif ($pr->expense_type === 'opex')
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 fw-semibold" style="font-size: 11px;">
                  <i class="bi bi-arrow-repeat me-1"></i> OpEx
                </span>
              @endif
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
              @if ($pr->department)
                <span class="detail-meta-chip">
                  <i class="bi bi-diagram-3"></i>
                  <span class="meta-label">Dept:</span>
                  <strong>{{ $pr->department }}</strong>
                </span>
              @endif

              <span class="detail-meta-chip">
                <i class="bi bi-person"></i>
                <span class="meta-label">Requester:</span>
                <strong>{{ $pr->creator?->name ?? 'System' }}</strong>
              </span>

              <span class="detail-meta-chip">
                <i class="bi bi-cash-stack"></i>
                <span class="meta-label">Estimated:</span>
                <strong>₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</strong>
              </span>

              @if ($pr->projectBudget)
                <span class="detail-meta-chip">
                  <i class="bi bi-calendar-event"></i>
                  <span class="meta-label">Event:</span>
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'events'))
                    <a href="{{ route('events.show', $pr->projectBudget) }}" title="View Event Procurement Dossier">
                      {{ $pr->projectBudget->project_name ?: 'Event Dossier' }}
                    </a>
                  @else
                    <strong>{{ $pr->projectBudget->project_name ?: 'Event Dossier' }}</strong>
                  @endif
                </span>
              @endif
            </div>

            @php
              $instructionsText = $pr->special_instructions ?: $pr->description;
            @endphp
            @if (!empty($instructionsText))
              <div class="mt-2.5 text-secondary small bg-light-subtle rounded-2 px-3 py-2 border shadow-2xs" style="font-size: 12.5px; line-height: 1.45; max-width: 820px; border-left: 3px solid #f49104 !important;">
                <span class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-0.5">
                  Special Instructions:
                </span>
                <span class="text-secondary" style="white-space: pre-line;">{{ $instructionsText }}</span>
              </div>
            @endif
          </div>
        </div>

        @php
          $isApproverInChain = $pr->approvals->where('role_required', auth()->user()?->role)->isNotEmpty()
            || in_array(auth()->user()?->role, [\App\Support\Roles::PR_APPROVER_L1, \App\Support\Roles::PR_APPROVER_L2, \App\Support\Roles::CFO, \App\Support\Roles::SG, \App\Support\Roles::ASG1, \App\Support\Roles::ASG2, \App\Support\Roles::ADMIN_HEAD], true);
          $canEditPr = ($pr->status !== 'approved') && (
            in_array(auth()->user()?->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true)
            || auth()->id() === $pr->created_by
            || $isApproverInChain
          );
        @endphp

        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0 text-start">
          @if ($pendingApproval)
            @if ($pr->items->count() > 1)
              <!-- Multi-item PR: Line-item Review Modal Trigger -->
              <button type="button" class="btn btn-sm btn-primary px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#lineItemApprovalModal">
                <i class="bi bi-ui-checks-grid"></i>
                <span>Review &amp; Endorse</span>
              </button>
            @else
              <!-- Single-item PR: Direct Quick Approve -->
              <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="m-0 d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap shadow-sm" style="font-size: 13px; border-radius: 6px;">
                  <i class="bi bi-check-lg"></i>
                  <span>Approve</span>
                </button>
              </form>
            @endif

            <!-- Decline / Reject Button -->
            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#declinePrModal">
              <i class="bi bi-x-lg"></i>
              <span>Reject</span>
            </button>
          @endif

          @if ($isAllApproved || in_array($pr->status, ['approved', 'partially_approved'], true))
            @php
              $topLinkedPos = $pr->purchaseOrders;
              $topLinkedPo = $topLinkedPos->first();
              $topCanIssuePo = in_array(auth()->user()?->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
            @endphp
            @if ($topLinkedPos->count() > 1)
              <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-receipt"></i>
                  <span>View POs ({{ $topLinkedPos->count() }})</span>
                </button>
                <ul class="dropdown-menu shadow-sm">
                  @foreach ($topLinkedPos as $tlp)
                    <li>
                      <a class="dropdown-item py-1.5" href="{{ route('po.show', $tlp) }}">
                        <strong>{{ $tlp->po_number }}</strong> — {{ $tlp->vendor->company_name }} (₹{{ number_format($tlp->amount_incl_gst, 2) }})
                      </a>
                    </li>
                  @endforeach
                </ul>
              </div>
            @elseif ($topLinkedPo)
              <a href="{{ route('po.show', $topLinkedPo) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-receipt"></i>
                <span>View PO (#{{ $topLinkedPo->po_number }})</span>
              </a>
            @elseif ($topCanIssuePo)
              <a href="{{ route('po.create', ['purchase_requisition_id' => $pr->id, 'pr' => $pr->id]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3.5 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-file-earmark-plus"></i>
                <span>Generate Purchase Order (PO)</span>
              </a>
            @endif
          @endif

          @if ($pr->status === 'rejected' && $canEditPr)
            <a href="{{ route('requisition.edit', $pr) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
              <i class="bi bi-pencil-square"></i>
              <span>Correct &amp; Resubmit</span>
            </a>
          @endif

          <a href="{{ route('requisition.pdf', $pr) }}" class="btn btn-sm btn-pdf-action d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold" title="Download Official PR PDF" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
            <span>PDF</span>
          </a>

          @php
            $hasMoreActions = $pendingApproval || ($canEditPr && $pr->status !== 'rejected') || $pr->projectBudget;
          @endphp

          @if ($hasMoreActions)
            <div class="dropdown d-inline-block position-relative">
              <button class="btn btn-sm btn-more-action dropdown-toggle d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                <i class="bi bi-three-dots"></i>
                <span>More</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-lg border py-1" style="font-size: 13px; min-width: 195px; z-index: 1060; border-radius: 8px;">
                @if ($pendingApproval)
                  <li>
                    <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning-emphasis fw-semibold" data-bs-toggle="modal" data-bs-target="#holdPrModal">
                      <i class="bi bi-pause-circle text-warning fs-6"></i>
                      <span>Put On Hold</span>
                    </button>
                  </li>
                @endif
                @if ($canEditPr && $pr->status !== 'rejected')
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('requisition.edit', $pr) }}">
                      <i class="bi bi-pencil text-secondary fs-6"></i>
                      <span>{{ $pendingApproval ? 'Edit & Re-send' : 'Edit PR' }}</span>
                    </a>
                  </li>
                @endif
                @if ($pr->projectBudget && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'events'))
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-primary fw-semibold" href="{{ route('events.show', $pr->projectBudget) }}">
                      <i class="bi bi-calendar-event fs-6"></i>
                      <span>Event Dossier</span>
                    </a>
                  </li>
                @endif
              </ul>
            </div>
          @endif
        </div>
      </div>
    </div>

  </div>

  <!-- Horizontal Approval Chain Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Requisition Lodged -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Initiated">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $designationShort[0] }}
                </span>
              </div>
              <div class="h-step-person" title="{{ $pr->creator?->name ?? 'Requester' }}">
                {{ $pr->creator?->name ?? 'Requester' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $pr->created_at?->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Dynamic Multi-Tier Approvals (L1 to L4) -->
          @foreach ($approvals as $approval)
            @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';
              $stageTitle = $stageNames[$approval->level] ?? \App\Support\Roles::label($approval->role_required);

              // Map role from approver / role_required to exact PPT Page 10 designation
              $roleKey = $approval->approver?->role ?? $approval->role_required;
              $roleMap = [
                'pr_sr_exec' => 'PR & GRN Creator',
                'pr_approver_l1' => 'DSG',
                'pr_approver_l2' => 'Head of States',
                'dsg' => 'DSG',
                'head_of_states' => 'Head of States',
                'asg1' => 'ASG 1',
                'asg2' => 'ASG 2',
                'cfo' => 'CFO',
                'sg' => 'SG',
                'buyer' => 'Buyer',
                'admin_head' => 'Admin Head',
              ];
              $roleShort = $roleMap[$roleKey] ?? ($designationShort[$approval->level] ?? ('L'.$approval->level));

              // Correct state progression logic (Prevents broken states when declined early):
              $isCurrentDue = (!$isPrRejected && $isPending && $approval->level === $activePendingLevel);
              $isFutureStage = ($isPrRejected || (!$isApproved && !$isCurrentDue));

              if ($isApproved) {
                $stepStatusClass = 'is-done';
                $iconClass = 'done';
                $cardClass = 'done-card';
              } elseif ($isRejected) {
                $stepStatusClass = 'is-declined';
                $iconClass = 'rejected';
                $cardClass = 'active-card border-danger-subtle';
              } elseif ($isCurrentDue) {
                $stepStatusClass = 'is-active';
                $iconClass = 'active';
                $cardClass = 'active-card';
              } else {
                $stepStatusClass = 'is-stopped';
                $iconClass = 'upcoming';
                $cardClass = 'stopped-card';
              }
            @endphp
            <div class="h-timeline-step {{ $stepStatusClass }}">
              <div class="h-step-icon {{ $iconClass }}">
                @if ($isApproved)
                  <i class="bi bi-check-lg"></i>
                @elseif ($isRejected)
                  <i class="bi bi-x-lg"></i>
                @elseif ($isCurrentDue)
                  <i class="bi bi-hourglass-split"></i>
                @else
                  <span class="text-secondary fw-bold" style="font-size: 11.5px;">{{ $approval->level }}</span>
                @endif
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    {{ $roleShort }}
                  </span>
                </div>
                <div class="h-step-person">
                  @if ($approval->approver)
                    <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $approval->approver->name }}</span>
                  @elseif ($isCurrentDue)
                    <span class="text-warning fw-bold">Action Required</span>
                  @else
                    <span class="text-muted">Awaiting Stage</span>
                  @endif
                </div>
                <div class="h-step-meta mt-0.5">
                  @if ($approval->acted_at)
                    {{ $approval->acted_at->format('d M, H:i') }}
                  @elseif ($isRejected)
                    Declined
                  @else
                    Pending
                  @endif
                </div>
                @if ($approval->comments)
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}" 
                       data-bs-toggle="modal" 
                       data-bs-target="#approvalCommentModal-{{ $approval->id }}"
                       title="Click to view full comment">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- RFQ Handover Step -->
          @php
            $rfqDone = (bool)$pr->rfq;
            $rfqReady = $isAllApproved;
          @endphp
          <div class="h-timeline-step {{ $rfqDone ? 'is-done' : ($rfqReady ? 'is-active' : 'is-stopped') }}">
            <div class="h-step-icon {{ $rfqDone ? 'done' : ($rfqReady ? 'active' : 'upcoming') }}">
              <i class="bi {{ $rfqDone ? 'bi-check-all' : ($rfqReady ? 'bi-broadcast' : 'bi-send') }}"></i>
            </div>
            <div class="h-step-card {{ $rfqDone ? 'done-card' : ($rfqReady ? 'active-card' : 'stopped-card') }}">
              <div class="mb-1.5">
                <span class="badge {{ $rfqDone ? 'bg-success-subtle text-success' : ($rfqReady ? 'bg-info-subtle text-info' : 'bg-light text-muted') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Buyer
                </span>
              </div>
              <div class="h-step-person">
                @if ($pr->rfq)
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'rfq'))
                    <a href="{{ route('rfq.show', $pr->rfq) }}" class="fw-bold text-primary text-decoration-none">{{ $pr->rfq->rfq_number }} &rarr;</a>
                  @else
                    <span class="fw-bold">{{ $pr->rfq->rfq_number }}</span>
                  @endif
                @elseif ($isAllApproved)
                  <span class="text-info fw-semibold">Ready</span>
                @else
                  <span class="text-muted">Post-Approval</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $pr->rfq ? 'Dispatched' : '—' }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Line Items (Full Width col-12) -->
    <div class="col-12">
      <div class="card-panel p-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold text-dark">Requisition Line Items ({{ $pr->items->count() }})</h6>
          </div>
          @if ($pr->required_by)
          <span class="badge bg-light text-primary border px-2.5 py-1.5">
            Required By: {{ $pr->required_by->format('d M Y') }}
          </span>
          @endif
        </div>

        @php
          $subtotalExclGst = $pr->items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
          $totalGst = $pr->estimatedCostInclGst() - $subtotalExclGst;
          $totalGstPercent = $subtotalExclGst > 0 ? ($totalGst / $subtotalExclGst) * 100 : 0;
          $totalGstFormatted = (int)$totalGstPercent == (float)$totalGstPercent ? (int)$totalGstPercent : number_format($totalGstPercent, 2);
          $gstHeaderLabel = 'Estimated GST (' . $totalGstFormatted . '%):';
        @endphp
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
            <thead class="table-light">
              <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700;">
                <th class="ps-3 text-center" style="width: 35px;">#</th>
                <th>ITEM &amp; DESCRIPTION</th>
                <th class="text-center" style="width: 95px;">QTY</th>
                <th class="text-end" style="width: 110px;">UNIT COST</th>
                <th class="text-center" style="width: 75px;">GST</th>
                <th class="text-end" style="width: 125px;">LINE TOTAL</th>
                <th class="text-center pe-3" style="width: 100px;">STATUS</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($pr->items as $item)
                @php
                  $itemApproved = $isAllApproved || $item->status === 'approved';
                  $itemRejected = $isPrRejected || $item->status === 'rejected';
                  $baseCost = (float)$item->unit_cost * (int)$item->quantity;
                  $gstAmt = $baseCost * ((float)$item->gst_percent / 100);
                  $totalCost = $item->lineTotalInclGst();
                  $gstFormatted = (int)$item->gst_percent == (float)$item->gst_percent ? (int)$item->gst_percent : number_format((float)$item->gst_percent, 2);
                @endphp
                <tr>
                  <td class="ps-3 text-center text-muted fw-semibold" style="font-size: 12px;">{{ $loop->iteration }}</td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                    @if (!empty($item->description))
                      <div class="text-muted small" style="font-size: 12px; line-height: 1.45; white-space: pre-line;">{{ $item->description }}</div>
                    @endif
                    @if ($item->purchaseOrder)
                      <div class="mt-1">
                        <a href="{{ route('po.show', $item->purchaseOrder) }}" class="badge bg-light text-primary border text-decoration-none" title="Awarded to {{ $item->vendor?->company_name }}">
                          <i class="bi bi-receipt me-1"></i>{{ $item->purchaseOrder->po_number }} &bull; {{ $item->vendor?->company_name }}
                        </a>
                      </div>
                    @endif
                  </td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 12px;">{{ $item->quantity }} {{ $item->unit ?: 'Units' }}</span>
                  </td>
                  <td class="text-end py-2.5 text-nowrap fw-semibold text-secondary font-monospace">{{ (float)$item->unit_cost > 0 ? '₹' . number_format($item->unit_cost, 2) : '—' }}</td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 11px;">{{ (float)$item->unit_cost > 0 ? $gstFormatted . '%' : '—' }}</span>
                  </td>
                  <td class="text-end py-2.5 text-nowrap fw-bold text-dark font-monospace">{{ (float)$totalCost > 0 ? '₹' . number_format($totalCost, 2) : '—' }}</td>
                  <td class="text-center pe-3 py-2.5 text-nowrap">
                    @if ($itemApproved)
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        Approved
                      </span>
                    @elseif ($itemRejected)
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        Declined
                      </span>
                    @else
                      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        Pending
                      </span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="border-top">
              <tr style="font-size: 13px; background: #ffffff;">
                <td colspan="5" class="text-end py-2 text-muted fw-normal">Subtotal (Excl. GST):</td>
                <td class="text-end py-2 font-monospace text-secondary fw-semibold">₹{{ number_format($subtotalExclGst, 2) }}</td>
                <td></td>
              </tr>
              <tr style="font-size: 13px; background: #ffffff;">
                <td colspan="5" class="text-end py-1 text-muted fw-normal">{{ $gstHeaderLabel }}</td>
                <td class="text-end py-1 font-monospace text-secondary fw-semibold">₹{{ number_format(max(0, $totalGst), 2) }}</td>
                <td></td>
              </tr>
              <tr style="font-size: 14px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                <td colspan="5" class="text-end py-3 text-dark fw-bold">Grand Total (Incl. GST):</td>
                <td class="text-end py-3 font-monospace fw-bold fs-6" style="color: #c8102e;">
                  ₹{{ number_format($pr->estimatedCostInclGst(), 2) }}
                </td>
                <td class="text-center pe-3 py-3">
                  @if ($isAllApproved)
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">Approved</span>
                  @elseif ($isPrRejected)
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">Rejected</span>
                  @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">Pending</span>
                  @endif
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Complete Version History (Who, When, What Changed, Comments) -->
    <div class="col-12 mt-4">
      <div class="card-panel p-4 shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom gap-2">
          <div class="d-flex align-items-center gap-2">
            <div>
              <h6 class="mb-0 fw-bold text-dark">Complete Version &amp; Revision History</h6>
            </div>
          </div>
        </div>

        <div style="width: 100%; overflow-x: hidden;">
          <table class="table table-hover align-middle mb-0 w-100" style="font-size: 11.5px; border-collapse: collapse;">
            <thead class="table-light">
              <tr style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700;">
                <th class="ps-3 py-2 text-nowrap" style="width: 55px;">VERSION</th>
                <th class="py-2" style="width: 130px;">ACTION &amp; EVENT</th>
                <th class="py-2">WHAT CHANGED</th>
                <th class="py-2" style="width: 125px;">OFFICER (WHO)</th>
                <th class="py-2 text-nowrap" style="width: 95px;">TIMESTAMP</th>
                <th class="py-2">COMMENTS</th>
                <th class="text-center pe-3 py-2 text-nowrap" style="width: 65px;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pr->versions as $ver)
                @php
                  $badgeColor = match($ver->event) {
                    'created' => 'primary',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'resubmitted' => 'warning',
                    'edited' => 'info',
                    default => 'secondary'
                  };
                @endphp
                <tr>
                  <!-- Version Pill -->
                  <td class="ps-3 py-2 text-nowrap">
                    <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 10.5px; font-weight: 700;">
                      v{{ $ver->version_number }}
                    </span>
                  </td>

                  <!-- Action Title & Event -->
                  <td class="py-2">
                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle py-1 px-1.5 text-wrap text-start" style="font-size: 10px; font-weight: 600; line-height: 1.25; display: inline-block;">
                      {{ $ver->action_title }}
                    </span>
                  </td>

                  <!-- What Changed -->
                  <td class="py-2">
                    <div class="text-dark fw-medium" style="font-size: 11.5px; line-height: 1.35;">
                      {{ $ver->change_summary ?: '—' }}
                    </div>
                  </td>

                  <!-- Who -->
                  <td class="py-2">
                    <div class="fw-semibold text-dark" style="font-size: 11.5px; line-height: 1.25;">{{ $ver->user?->name ?? 'System' }}</div>
                    @if ($ver->user?->role)
                      <div class="text-muted" style="font-size: 10px; line-height: 1.2;">{{ \App\Support\Roles::label($ver->user->role) }}</div>
                    @endif
                  </td>

                  <!-- When -->
                  <td class="py-2 text-nowrap text-secondary">
                    <div style="font-size: 11px; line-height: 1.25;">{{ $ver->created_at->format('d M Y') }}</div>
                    <div class="text-muted" style="font-size: 10px; line-height: 1.2;">{{ $ver->created_at->format('h:i A') }}</div>
                  </td>

                  <!-- Comments -->
                  <td class="py-2" style="max-width: 220px;">
                    @if ($ver->comments)
                      @if (mb_strlen($ver->comments) > 55)
                        <div class="text-secondary" style="font-size: 11px; line-height: 1.35;">
                          <span>{{ Str::limit($ver->comments, 46) }}</span>
                          <a href="javascript:void(0)" class="text-primary text-decoration-none ms-1 text-nowrap" style="font-size: 8.5px; font-weight: 600; opacity: 0.85;" data-bs-toggle="modal" data-bs-target="#snapshotModal-{{ $ver->id }}" title="Click to view complete remarks">view more</a>
                        </div>
                      @else
                        <div class="text-secondary" style="font-size: 11px; line-height: 1.35;">
                          {{ $ver->comments }}
                        </div>
                      @endif
                    @else
                      <span class="text-muted small">—</span>
                    @endif
                  </td>

                  <!-- Snapshot Modal Trigger -->
                  <td class="text-center pe-3 py-2 text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-semibold" style="font-size: 10.5px; height: 22px; line-height: 1;" data-bs-toggle="modal" data-bs-target="#snapshotModal-{{ $ver->id }}" title="View details at v{{ $ver->version_number }}">
                      View
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    No version history recorded yet.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Version Detail & Snapshot Modals -->
        @foreach ($pr->versions as $ver)
          @php
            $badgeColor = match($ver->event) {
              'created' => 'primary',
              'approved' => 'success',
              'rejected' => 'danger',
              'resubmitted' => 'warning',
              'edited' => 'info',
              default => 'secondary'
            };
          @endphp
          <div class="modal fade" id="snapshotModal-{{ $ver->id }}" tabindex="-1" aria-labelledby="snapshotLabel-{{ $ver->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="snapshotLabel-{{ $ver->id }}">
                      {{ $pr->pr_number }} — Version {{ $ver->version_number }}
                    </h6>
                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle py-0.5 px-2" style="font-size: 11px;">
                      {{ $ver->action_title }}
                    </span>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3.5">
                  <div class="row g-2 mb-3 small bg-light p-2.5 rounded border">
                    <div class="col-sm-6">
                      <span class="text-muted">Recorded By:</span>
                      <strong class="text-dark ms-1">{{ $ver->user?->name ?? 'System' }}</strong>
                      @if ($ver->user?->role)
                        <span class="text-muted">({{ \App\Support\Roles::label($ver->user->role) }})</span>
                      @endif
                    </div>
                    <div class="col-sm-6">
                      <span class="text-muted">Timestamp:</span>
                      <strong class="text-dark ms-1">{{ $ver->created_at->format('d M Y, h:i A') }}</strong>
                      <span class="text-muted">({{ $ver->created_at->diffForHumans() }})</span>
                    </div>
                    <div class="col-12 mt-2">
                      <span class="text-muted">What Changed:</span>
                      <span class="text-dark fw-semibold ms-1">{{ $ver->change_summary ?: 'No field modifications logged.' }}</span>
                    </div>
                    @if ($ver->comments)
                      <div class="col-12 mt-2">
                        <div class="text-muted small mb-1">Comments / Remarks:</div>
                        <div class="p-2.5 rounded-2 bg-white border text-dark fw-medium" style="font-size: 13px; line-height: 1.5; border-left: 3px solid #c8102e !important;">
                          &ldquo;{{ trim($ver->comments) }}&rdquo;
                        </div>
                      </div>
                    @endif
                  </div>

                  @if ($ver->snapshot)
                    <div class="row g-2 mb-3 small">
                      <div class="col-sm-6">
                        <span class="text-muted">Requisition Title:</span>
                        <strong class="text-dark ms-1">{{ $ver->snapshot['title'] ?? '—' }}</strong>
                      </div>
                      <div class="col-sm-6">
                        <span class="text-muted">Department:</span>
                        <strong class="text-dark ms-1">{{ $ver->snapshot['department'] ?? '—' }}</strong>
                      </div>
                      <div class="col-sm-6">
                        <span class="text-muted">Required By:</span>
                        <strong class="text-dark ms-1">{{ $ver->snapshot['required_by'] ?? '—' }}</strong>
                      </div>
                      <div class="col-sm-6">
                        <span class="text-muted">Recorded Total (GST-incl.):</span>
                        <strong class="text-primary ms-1 font-monospace">₹{{ number_format($ver->snapshot['estimated_cost'] ?? 0, 2) }}</strong>
                      </div>
                    </div>

                    @if (!empty($ver->snapshot['description']))
                      <div class="p-2 mb-3 rounded bg-light-subtle border small">
                        <span class="fw-semibold text-dark">Description:</span>
                        <span class="text-secondary">{{ $ver->snapshot['description'] }}</span>
                      </div>
                    @endif

                    @if (!empty($ver->snapshot['items']))
                      <div class="table-responsive border rounded">
                        <table class="table table-sm align-middle mb-0" style="font-size: 12px;">
                          <thead class="table-light">
                            <tr>
                              <th class="ps-2">#</th>
                              <th>Item &amp; Specs</th>
                              <th class="text-center">Qty</th>
                              <th class="text-end">Unit Cost</th>
                              <th class="text-center">GST</th>
                              <th class="text-end pe-2">Line Total</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($ver->snapshot['items'] as $sItem)
                              <tr>
                                <td class="ps-2 text-muted">{{ $loop->iteration }}</td>
                                <td>
                                  <div class="fw-semibold text-dark">{{ $sItem['item_name'] ?? '—' }}</div>
                                  @if (!empty($sItem['description']))
                                    <div class="text-muted" style="font-size: 11px;">{{ $sItem['description'] }}</div>
                                  @endif
                                </td>
                                <td class="text-center">{{ $sItem['quantity'] ?? 1 }} {{ $sItem['unit'] ?? 'Units' }}</td>
                                <td class="text-end font-monospace">₹{{ number_format($sItem['unit_cost'] ?? 0, 2) }}</td>
                                <td class="text-center">{{ $sItem['gst_percent'] ?? 18 }}%</td>
                                <td class="text-end pe-2 font-monospace fw-bold text-dark">₹{{ number_format($sItem['line_total'] ?? 0, 2) }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    @endif
                  @endif
                </div>
                <div class="modal-footer bg-light py-2 px-3.5 border-top">
                  <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        @endforeach
        </div>
      </div>
    </div>
  </div>

  @if ($pendingApproval)
    <!-- Line-Item Approval & Partial Clearance Modal -->
    <div class="modal fade" id="lineItemApprovalModal" tabindex="-1" aria-labelledby="lineItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-primary-subtle py-2.5 px-3.5 border-bottom border-primary-subtle">
            <div class="d-flex align-items-center gap-2 text-primary">
              <i class="bi bi-ui-checks-grid fs-5"></i>
              <h6 class="modal-title fw-bold fs-6 mb-0" id="lineItemModalLabel">Line-Item Review &amp; Endorsement</h6>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <div class="p-2.5 rounded bg-light border mb-3 small text-secondary">
              <i class="bi bi-info-circle-fill text-primary me-1"></i>
              You may selectively approve or decline individual line items. Only approved items will move forward to the RFQ, and budget for declined lines will be automatically released back to <strong>{{ $pr->department }}</strong>.
            </div>

            <div class="table-responsive border rounded mb-3">
              <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                <thead class="table-light">
                  <tr style="font-size: 11px; text-transform: uppercase;">
                    <th class="ps-3 py-2">Item Details</th>
                    <th class="text-center py-2">Quantity</th>
                    <th class="text-end py-2">Line Total</th>
                    <th class="text-center pe-3 py-2" style="width: 170px;">Item Decision</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($pr->items as $item)
                    <tr>
                      <td class="ps-3 py-2">
                        <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                        @if ($item->description)
                          <div class="text-muted small" style="font-size: 11px;">{{ $item->description }}</div>
                        @endif
                      </td>
                      <td class="text-center py-2">
                        <span class="badge bg-light text-dark border font-monospace">{{ $item->quantity }} {{ $item->unit ?: 'Units' }}</span>
                      </td>
                      <td class="text-end py-2 font-monospace fw-semibold text-dark">
                        ₹{{ number_format($item->lineTotalInclGst(), 2) }}
                      </td>
                      <td class="text-center pe-3 py-2">
                        <div class="btn-group btn-group-sm w-100" role="group">
                          <input type="radio" class="btn-check" name="line_decisions[{{ $item->id }}]" id="line-app-{{ $item->id }}" value="approved" {{ $item->status !== 'rejected' ? 'checked' : '' }}>
                          <label class="btn btn-outline-success py-1 px-2 fw-semibold" for="line-app-{{ $item->id }}" style="font-size: 11px;">
                            <i class="bi bi-check2"></i> Approve
                          </label>

                          <input type="radio" class="btn-check" name="line_decisions[{{ $item->id }}]" id="line-rej-{{ $item->id }}" value="rejected" {{ $item->status === 'rejected' ? 'checked' : '' }}>
                          <label class="btn btn-outline-danger py-1 px-2 fw-semibold" for="line-rej-{{ $item->id }}" style="font-size: 11px;">
                            <i class="bi bi-x"></i> Reject
                          </label>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-dark">Stage Endorsement Comments <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="text" name="comments" class="form-control form-control-sm" placeholder="e.g. Approved items 1 and 2; item 3 deferred to next quarter.">
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success btn-sm px-3.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-check2-circle"></i>
              <span>Confirm Decisions &amp; Advance</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Place PR On Hold Modal -->
    <div class="modal fade" id="holdPrModal" tabindex="-1" aria-labelledby="holdPrModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('requisition.hold', $pr) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdPrModalLabel">Place PR On Hold</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Placing <strong>{{ $pr->pr_number }}</strong> on hold temporarily pauses the approval review at this stage until necessary clarifications or revised specs are provided.
            </p>
            <div class="mb-1">
              <label for="holdPrComments" class="form-label fw-semibold text-dark small">Reason for Placing On Hold <span class="text-danger">*</span></label>
              <textarea name="comments" id="holdPrComments" class="form-control" rows="3" placeholder="Provide reason for hold (e.g. Awaiting clarification on item specs, vendor pricing verification)..." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning btn-sm px-3 fw-semibold text-dark d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-pause-circle"></i>
              <span>Confirm On Hold</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Decline Requisition Modal (Small, Clean & Minimal Window) -->
    <div class="modal fade" id="declinePrModal" tabindex="-1" aria-labelledby="declinePrModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('requisition.reject', $pr) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <div class="d-flex align-items-center gap-2 text-danger">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="declinePrModalLabel">Decline Purchase Requisition</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific reason for declining <strong>{{ $pr->pr_number }}</strong>. This feedback will be recorded in the audit trail and the reserved budget will be released.
            </p>
            <div class="mb-1">
              <label for="rejectPrComments" class="form-label fw-semibold text-dark small">Reason for Rejection <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectPrComments" class="form-control" rows="3" placeholder="e.g. Budget ceiling exceeded, technical specs non-compliant, requires further quote evaluation, etc." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-x-circle"></i>
              <span>Confirm Decline</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <!-- Timeline Approval Comments Modals -->
  @foreach ($approvals as $approval)
    @if ($approval->comments)
      <div class="modal fade" id="approvalCommentModal-{{ $approval->id }}" tabindex="-1" aria-labelledby="commentModalLabel-{{ $approval->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-chat-left-quote text-primary fs-5"></i>
                <h6 class="modal-title fw-bold text-dark mb-0" id="commentModalLabel-{{ $approval->id }}">
                  Approval Remarks — {{ $approval->approver?->name ?? 'Approver' }}
                </h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom text-muted small">
                <div>
                  <span class="text-secondary">Stage:</span>
                  <strong class="text-dark">{{ $stageNames[$approval->level] ?? ('Level ' . $approval->level) }}</strong>
                </div>
                <div>
                  <span class="text-secondary">Status:</span>
                  <span class="badge {{ $approval->status === 'approved' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border py-0.5 px-2">
                    {{ ucfirst($approval->status) }}
                  </span>
                </div>
              </div>
              <div class="p-3.5 rounded-3 bg-white border text-dark shadow-2xs" style="font-size: 14px; font-weight: 500; line-height: 1.6; border-left: 4px solid {{ $approval->status === 'approved' ? '#10b981' : '#ef4444' }} !important;">
                &ldquo;{{ trim($approval->comments) }}&rdquo;
              </div>
              @if ($approval->acted_at)
                <div class="mt-2 text-end text-muted" style="font-size: 11px;">
                  Recorded on {{ $approval->acted_at->format('d M Y, h:i A') }}
                </div>
              @endif
            </div>
            <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endforeach

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'PR Detail' });
@endsection