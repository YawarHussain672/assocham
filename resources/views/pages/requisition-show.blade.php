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

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: PR Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(200,16,46,0.15),rgba(200,16,46,0.05)); color:#c8102e; border:1px solid rgba(200,16,46,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-file-earmark-text fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $pr->pr_number }}</span>
              @if ($pr->title)
                <span class="fw-semibold text-secondary" style="font-size: 1.15rem; line-height: 1.2;">— {{ $pr->title }}</span>
              @endif
              @if ($pr->event_name)
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  <i class="bi bi-calendar-event me-1"></i>{{ $pr->event_name }}
                </span>
              @endif
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              @if ($pr->sole_source)
                <span class="badge" style="background:#fee2e2; color:#c8102e; font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  <i class="bi bi-shield-lock me-1"></i> Sole Source
                </span>
              @endif
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span><i class="bi bi-building text-secondary me-1"></i> {{ $pr->department }}</span>
              @if ($pr->region)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-pin-map text-secondary me-1"></i> {{ $pr->region }}</span>
              @endif
              <span class="text-secondary">•</span>
              <span><i class="bi bi-person text-secondary me-1"></i> Created by <strong class="text-dark">{{ $pr->creator?->name ?? 'System' }}</strong></span>
              @if ($pr->created_at)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-calendar3 text-secondary me-1"></i> {{ $pr->created_at->format('d M Y') }}</span>
              @endif
              <span class="text-secondary">•</span>
              <span><i class="bi bi-cash-stack text-success me-1"></i> ₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</span>
            </div>
            @php
              $instructionsText = $pr->special_instructions ?: $pr->description;
            @endphp
            @if (!empty($instructionsText))
              <div class="mt-2 text-secondary small bg-light-subtle rounded-2 px-3 py-2 border shadow-2xs" style="font-size: 12.5px; line-height: 1.45; max-width: 820px; border-left: 3px solid #2563eb !important;">
                <span class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-0.5">
                  <i class="bi bi-info-circle text-primary"></i> Special Instructions:
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

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @if ($pr->status === 'rejected' && $canEditPr)
            <a href="{{ route('requisition.edit', $pr) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5 fw-semibold">
              <i class="bi bi-pencil-square"></i>
              <span>Correct &amp; Resubmit</span>
            </a>
          @elseif ($canEditPr)
            <a href="{{ route('requisition.edit', $pr) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-semibold" title="Edit PR line items or details and re-send for approval">
              <i class="bi bi-pencil"></i>
              <span>{{ $pendingApproval ? 'Edit & Re-send' : 'Edit PR' }}</span>
            </a>
          @endif
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600;">
            <i class="bi bi-layers me-1"></i> {{ $pr->items->count() }} {{ Str::plural('Item', $pr->items->count()) }}
          </span>
          <span class="badge bg-light text-primary border px-2.5 py-1.5" style="font-size:12px; font-weight:600;">
            <i class="bi bi-clock-history me-1"></i> v{{ $pr->versions->first()?->version_number ?? 1 }}
          </span>
        </div>
      </div>
    </div>

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval)
      @php
        $currentStageTitle = $stageNames[$pendingApproval->level] ?? \App\Support\Roles::label($pendingApproval->role_required);
      @endphp
      <div class="p-3 p-md-3.5 border-top w-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-top: 1px solid #fde68a !important;">
        <div class="row align-items-center g-3">
          
          <!-- Left: Stage Notice & Instructions -->
          <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center" style="gap: 16px;">
              <div class="flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px; min-width: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">
                <i class="bi bi-shield-exclamation fs-5"></i>
              </div>
              <div class="flex-grow-1 text-start">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="fw-bold text-dark" style="font-size: 15px;">Your Action Required</span>
                  <span class="badge bg-white text-dark border border-warning-subtle shadow-2xs px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                    Stage: {{ $currentStageTitle }}
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Review line items, specifications, and departmental budget commitment before authorizing this Purchase Requisition.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (Supports Line-Item Decision Review) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              @if ($pr->items->count() > 1)
                <!-- Multi-item PR: Line-item Review Modal Trigger -->
                <button type="button" class="btn btn-primary px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#lineItemApprovalModal">
                  <i class="bi bi-ui-checks-grid fs-6"></i>
                  <span>Review &amp; Endorse Items</span>
                </button>
              @else
                <!-- Single-item PR: Direct Quick Approve -->
                <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="m-0">
                  @csrf
                  <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                    <i class="bi bi-check2-circle fs-6"></i>
                    <span>Approve &amp; Advance</span>
                  </button>
                </form>
              @endif

              <!-- Edit & Re-send Option for Upper Hierarchy Approver -->
              <a href="{{ route('requisition.edit', $pr) }}" class="btn btn-outline-secondary px-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" title="Modify line items, quantities, or details and re-send for review">
                <i class="bi bi-pencil-square fs-6"></i>
                <span>Edit &amp; Re-send</span>
              </a>

              <!-- Decline Button (Triggers Clean Modal) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#declinePrModal">
                <i class="bi bi-x-circle fs-6"></i>
                <span>Decline PR</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Approval Chain Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Approval Chain Timeline</h6>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 11.5px; font-weight: 600;">
          @if ($isAllApproved)
            <i class="bi bi-check-circle-fill text-success me-1"></i>{{ $totalCount }}/{{ $totalCount }} Endorsed
          @elseif ($isPrRejected)
            <i class="bi bi-x-circle-fill text-danger me-1"></i>Rejected
          @else
            <i class="bi bi-clock-history text-warning-emphasis me-1"></i>{{ $approvedCount }}/{{ $totalCount }} Endorsed
          @endif
        </span>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Requisition Lodged -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Initiated">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Lodged</div>
              <div class="mb-1.5">
                <span class="badge bg-light text-secondary border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
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
                <div class="h-step-title {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : ($isCurrentDue ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="{{ $stageTitle }}">
                  L{{ $approval->level }}: {{ trim(Str::before($stageTitle, '(')) }}
                </div>
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
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}" title="{{ $approval->comments }}">
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
              <div class="h-step-title {{ $rfqReady || $rfqDone ? 'text-dark' : 'text-muted' }} mb-1" title="RFQ Handover">RFQ Handover</div>
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
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
              <i class="bi bi-boxes"></i>
            </div>
            <h6 class="mb-0 fw-bold text-dark">Requisition Line Items ({{ $pr->items->count() }})</h6>
          </div>
          @if ($pr->required_by)
          <span class="badge bg-light text-primary border px-2.5 py-1.5">
            <i class="bi bi-calendar-check me-1"></i> Required By: {{ $pr->required_by->format('d M Y') }}
          </span>
          @endif
        </div>

        @php
          $subtotalExclGst = $pr->items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
          $totalGst = $pr->estimatedCostInclGst() - $subtotalExclGst;
          $uniqueGst = $pr->items->pluck('gst_percent')->map(fn ($r) => (int)$r == (float)$r ? (int)$r : (float)$r)->unique();
          $gstHeaderLabel = $uniqueGst->count() === 1 ? 'Estimated GST (' . $uniqueGst->first() . '%):' : 'Estimated GST:';
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
                <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#itemDetailModal-pr-{{ $item->id }}" title="Click to view full specifications & financials">
                  <td class="ps-3 text-center text-muted fw-semibold" style="font-size: 12px;">{{ $loop->iteration }}</td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                    @if (!empty($item->description))
                      <div class="text-muted small" style="font-size: 11.5px; line-height: 1.3;">{{ $item->description }}</div>
                    @endif
                    <div class="text-primary small mt-0.5" style="font-size: 11px;">
                      <i class="bi bi-arrows-angle-expand me-1"></i>View Details
                    </div>
                  </td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 12px;">{{ $item->quantity }} {{ $item->unit ?: 'Units' }}</span>
                  </td>
                  <td class="text-end py-2.5 text-nowrap fw-semibold text-secondary font-monospace">₹{{ number_format($item->unit_cost, 2) }}</td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 11px;">{{ $gstFormatted }}%</span>
                  </td>
                  <td class="text-end py-2.5 text-nowrap fw-bold text-dark font-monospace">₹{{ number_format($totalCost, 2) }}</td>
                  <td class="text-center pe-3 py-2.5 text-nowrap">
                    @if ($itemApproved)
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        <i class="bi bi-check2 me-0.5"></i> Approved
                      </span>
                    @elseif ($itemRejected)
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        <i class="bi bi-x me-0.5"></i> Declined
                      </span>
                    @else
                      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size: 11px; font-weight: 600;">
                        <i class="bi bi-clock me-0.5"></i> Pending
                      </span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr style="font-size: 12.5px; color: #64748b; background: #f8fafc; border-top: 1.5px solid #e2e8f0;">
                <td colspan="5" class="text-end py-2 fw-semibold">Subtotal (Excl. GST):</td>
                <td class="text-end py-2 font-monospace fw-semibold text-dark">₹{{ number_format($subtotalExclGst, 2) }}</td>
                <td></td>
              </tr>
              <tr style="font-size: 12.5px; color: #64748b; background: #f8fafc;">
                <td colspan="5" class="text-end py-2 fw-semibold">{{ $gstHeaderLabel }}</td>
                <td class="text-end py-2 font-monospace fw-semibold text-dark">₹{{ number_format(max(0, $totalGst), 2) }}</td>
                <td></td>
              </tr>
              <tr style="font-size: 13.5px; font-weight: 700; background: #f1f5f9; border-top: 2px solid #cbd5e1;">
                <td colspan="5" class="text-end py-2 text-dark">Grand Total (Incl. GST):</td>
                <td class="text-end py-2 font-monospace text-primary fw-bold fs-6">₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</td>
                <td class="text-center pe-3 py-2">
                  @if ($isAllApproved)
                    <span class="badge bg-success text-white py-1 px-2" style="font-size: 10.5px; font-weight: 600;">Fully Approved</span>
                  @elseif ($isPrRejected)
                    <span class="badge bg-danger text-white py-1 px-2" style="font-size: 10.5px; font-weight: 600;">Rejected</span>
                  @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size: 10.5px; font-weight: 600;">In Review</span>
                  @endif
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Line Item Detail Modals for PR -->
        @foreach ($pr->items as $item)
        @php
          $itemBase = (float)$item->unit_cost * (int)$item->quantity;
          $itemGst = $itemBase * ((float)$item->gst_percent / 100);
          $itemTotal = $item->lineTotalInclGst();
          $gstFormatted = rtrim(rtrim(number_format($item->gst_percent, 2), '0'), '%');
        @endphp
        <div class="modal fade" id="itemDetailModal-pr-{{ $item->id }}" tabindex="-1" aria-labelledby="itemModalLabel-pr-{{ $item->id }}" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
              <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                    #{{ $loop->iteration }}
                  </div>
                  <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="itemModalLabel-pr-{{ $item->id }}">
                      {{ $item->item_name }}
                    </h6>
                    <div class="text-muted small" style="font-size: 11.5px;">Purchase Requisition Line Item Specifications &amp; Estimation</div>
                  </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                <!-- 4 Grid Metric Boxes -->
                <div class="row g-3 mb-4">
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Quantity</div>
                      <div class="fw-bold text-dark fs-5 mt-1">{{ $item->quantity }} <span class="fs-6 text-muted fw-normal">{{ $item->unit ?: 'Units' }}</span></div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Est. Unit Rate</div>
                      <div class="fw-bold text-secondary fs-5 mt-1 font-monospace">₹{{ number_format($item->unit_cost, 2) }}</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">GST Rate</div>
                      <div class="fw-bold text-dark fs-5 mt-1 font-monospace">{{ $gstFormatted }}%</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-primary-subtle rounded-2 border border-primary-subtle">
                      <div class="text-primary text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total (Incl. GST)</div>
                      <div class="fw-bold text-primary fs-5 mt-1 font-monospace">₹{{ number_format($itemTotal, 2) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Technical Specifications & Description -->
                <div class="mb-4">
                  <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                    <i class="bi bi-card-text text-primary me-1"></i> Technical Specifications &amp; Requirements
                  </h6>
                  @if (!empty($item->description))
                    <div class="p-3 rounded-2 border bg-white text-dark shadow-2xs" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap; border-left: 3px solid #2563eb !important;">
                      {{ $item->description }}
                    </div>
                  @else
                    <div class="p-3 rounded-2 border bg-light-subtle text-muted fst-italic" style="font-size: 12.5px; line-height: 1.5;">
                      <i class="bi bi-info-circle me-1"></i> No custom specifications provided. Standard institutional procurement guidelines apply for {{ $item->item_name }}.
                    </div>
                  @endif
                </div>

                <!-- Commercial Calculation Table -->
                <div class="table-responsive border rounded-2 mb-3">
                  <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3 py-2">Cost Element</th>
                        <th class="text-center py-2">Calculation Basis</th>
                        <th class="text-end pe-3 py-2">Estimated Amount (INR)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-3 py-2">Base Cost (Excl. Taxes)</td>
                        <td class="text-center py-2 text-muted">{{ $item->quantity }} {{ $item->unit ?: 'Units' }} &times; ₹{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end pe-3 py-2 font-monospace fw-semibold">₹{{ number_format($itemBase, 2) }}</td>
                      </tr>
                      <tr>
                        <td class="ps-3 py-2">Estimated GST</td>
                        <td class="text-center py-2 text-muted">{{ $gstFormatted }}% on ₹{{ number_format($itemBase, 2) }}</td>
                        <td class="text-end pe-3 py-2 font-monospace text-secondary">₹{{ number_format($itemGst, 2) }}</td>
                      </tr>
                      <tr class="table-light border-top fw-bold">
                        <td class="ps-3 py-2 text-dark">Total Estimated Budget Commitment</td>
                        <td class="text-center py-2 text-muted">All-inclusive</td>
                        <td class="text-end pe-3 py-2 font-monospace text-primary fs-6">₹{{ number_format($itemTotal, 2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Metadata Footer -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 text-muted small" style="font-size: 12px;">
                  <span><i class="bi bi-file-earmark-text me-1"></i> Requisition: <strong>{{ $pr->pr_number }}</strong></span>
                  <span><i class="bi bi-building me-1"></i> Department: <strong>{{ $pr->department }}</strong></span>
                  <span><i class="bi bi-calendar3 me-1"></i> Required By: <strong>{{ optional($pr->required_by)->format('d M Y') ?? 'Standard' }}</strong></span>
                </div>
              </div>
              <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @endforeach

        <div class="pt-3 mt-3 border-top text-secondary d-flex flex-wrap justify-content-between align-items-center gap-2" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <span><i class="bi bi-person text-muted me-1"></i> Created By: <strong class="text-dark">{{ $pr->creator?->name ?? 'System' }}</strong></span>
            <span><i class="bi bi-building text-muted me-1"></i> Department: <strong class="text-dark">{{ $pr->department }}</strong></span>
            @if ($pr->region)
              <span><i class="bi bi-geo-alt text-muted me-1"></i> Region: <strong class="text-dark">{{ $pr->region }}</strong></span>
            @endif
            @if ($pr->event_name)
              <span><i class="bi bi-calendar-event text-muted me-1"></i> Event: <strong class="text-dark">{{ $pr->event_name }}</strong></span>
            @endif
          </div>
          <div class="d-flex align-items-center gap-2 text-muted" style="font-size:12px;">
            <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-shield-check text-success me-1"></i> Budget Check: GST-Inclusive</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Version History (Who, When, What Changed, Comments) -->
    <div class="col-12 mt-4">
      <div class="card-panel p-4 shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom gap-2">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
              <i class="bi bi-clock-history"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold text-dark">Complete Version &amp; Revision History</h6>
              <div class="text-muted small" style="font-size:12px;">Immutable ledger of every revision, stage approval, rejection, and re-submission.</div>
            </div>
          </div>
          <span class="badge bg-light text-primary border px-2.5 py-1" style="font-size:11.5px; font-weight:600;">
            {{ $pr->versions->count() }} {{ Str::plural('Version', $pr->versions->count()) }} Recorded
          </span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
              <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700;">
                <th class="ps-3" style="width: 85px;">VERSION</th>
                <th style="min-width: 140px;">ACTION &amp; EVENT</th>
                <th style="min-width: 240px;">WHAT CHANGED</th>
                <th style="min-width: 180px;">OFFICER (WHO)</th>
                <th style="min-width: 130px;">TIMESTAMP (WHEN)</th>
                <th style="min-width: 180px;">COMMENTS / REMARKS</th>
                <th class="text-center pe-3" style="width: 90px;">SNAPSHOT</th>
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
                  $eventIcon = match($ver->event) {
                    'created' => 'bi-file-earmark-plus',
                    'approved' => 'bi-check2-circle',
                    'rejected' => 'bi-x-circle',
                    'resubmitted' => 'bi-arrow-repeat',
                    'edited' => 'bi-pencil',
                    default => 'bi-dot'
                  };
                @endphp
                <tr>
                  <!-- Version Pill -->
                  <td class="ps-3">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1" style="font-size: 11.5px; font-weight: 700;">
                      v{{ $ver->version_number }}
                    </span>
                  </td>

                  <!-- Action Title & Event -->
                  <td>
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle py-1 px-1.5" style="font-size: 11px; font-weight: 600;">
                        <i class="bi {{ $eventIcon }} me-1"></i>{{ $ver->action_title }}
                      </span>
                    </div>
                  </td>

                  <!-- What Changed -->
                  <td>
                    <div class="text-dark fw-medium" style="font-size: 12.5px; line-height: 1.35;">
                      {{ $ver->change_summary ?: 'No field modifications logged.' }}
                    </div>
                  </td>

                  <!-- Who -->
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-secondary border flex-shrink-0" style="width: 28px; height: 28px; font-size: 13px;">
                        <i class="bi bi-person"></i>
                      </div>
                      <div style="line-height: 1.25;">
                        <div class="fw-semibold text-dark" style="font-size: 12.5px;">{{ $ver->user?->name ?? 'System' }}</div>
                        @if ($ver->user?->role)
                          <div class="text-muted" style="font-size: 11px;">{{ \App\Support\Roles::label($ver->user->role) }}</div>
                        @endif
                      </div>
                    </div>
                  </td>

                  <!-- When -->
                  <td class="text-nowrap text-secondary" style="font-size: 12px;">
                    <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $ver->created_at->format('d M Y, h:i A') }}
                    <div class="text-muted small" style="font-size: 10.5px;">{{ $ver->created_at->diffForHumans() }}</div>
                  </td>

                  <!-- Comments -->
                  <td>
                    @if ($ver->comments)
                      <div class="p-1.5 rounded bg-light border text-secondary small" style="font-size: 11.5px; line-height: 1.35; max-width: 260px;">
                        <i class="bi bi-chat-left-quote me-1 text-muted"></i>{{ $ver->comments }}
                      </div>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
                  </td>

                  <!-- Snapshot Modal Trigger -->
                  <td class="text-center pe-3">
                    @if ($ver->snapshot)
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#snapshotModal-{{ $ver->id }}" title="View snapshot at v{{ $ver->version_number }}">
                        <i class="bi bi-eye"></i> View
                      </button>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
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

        <!-- Version Snapshot Modals (Placed Outside Table for Clean DOM) -->
        @foreach ($pr->versions as $ver)
          @if ($ver->snapshot)
            <div class="modal fade" id="snapshotModal-{{ $ver->id }}" tabindex="-1" aria-labelledby="snapshotLabel-{{ $ver->id }}" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi bi-clock-history text-primary fs-5"></i>
                      <h6 class="modal-title fw-bold text-dark mb-0" id="snapshotLabel-{{ $ver->id }}">
                        {{ $pr->pr_number }} — Snapshot at v{{ $ver->version_number }}
                      </h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-3.5">
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
                  </div>
                  <div class="modal-footer bg-light py-2 px-3.5 border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          @endif
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

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'PR Detail' });
@endsection