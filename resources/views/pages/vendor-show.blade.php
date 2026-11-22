@extends('layouts.app')

@section('title', $vendor->company_name)

@php
$statusMeta = [
'pending_admin_head' => ['label' => 'Pending — Administrative Audit', 'badge' => 'warning'],
'pending_l1' => ['label' => 'Pending — Secretarial Review (ASG 1)', 'badge' => 'warning'],
'pending_l2' => ['label' => 'Pending — Secretarial Review (ASG 2)', 'badge' => 'warning'],
'pending_cfo' => ['label' => 'Pending — Financial Clearance (CFO)', 'badge' => 'warning'],
'approved' => ['label' => 'Empanelled & Verified', 'badge' => 'success'],
'rejected' => ['label' => 'Rejected', 'badge' => 'danger'],
'draft' => ['label' => 'Draft', 'badge' => 'secondary'],
];
$meta = $statusMeta[$vendor->status] ?? $statusMeta['draft'];
$stepMeta = [
'pending' => ['label' => 'Pending', 'badge' => 'warning'],
'approved' => ['label' => 'Approved', 'badge' => 'success'],
'rejected' => ['label' => 'Rejected', 'badge' => 'danger'],
];

$approvals = $vendor->approvals->sortByDesc('id')->unique('level')->sortBy('level');
$vendorApprovedCount = $approvals->where('status', 'approved')->count();
$vendorTotalCount = $approvals->count();
$isVendorEmpanelled = $vendorTotalCount > 0 && $vendorApprovedCount === $vendorTotalCount;
$isVendorRejected = $approvals->where('status', 'rejected')->isNotEmpty() || $vendor->status === 'rejected';
$activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

$vendorStageTitles = [
1 => 'Admin Head',
2 => 'ASG 1',
3 => 'ASG 2',
4 => 'CFO',
];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Back Navigation Link -->
  <a href="{{ route('empanelment') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Vendor Empanelment</span>
  </a>

  @if (session('status'))
  <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <span>{{ session('status') }}</span>
  </div>
  @endif
  @if ($errors->any())
  <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span>{{ $errors->first() }}</span>
  </div>
  @endif

  @if ($vendor->isBlacklisted())
  <!-- Blacklisted Alert Banner -->
  <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-4" style="border-radius: 10px; background: #fef2f2; border-left: 5px solid #dc2626 !important;">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger text-white flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.25rem;">
        <i class="bi bi-slash-circle-fill"></i>
      </div>
      <div>
        <div class="fw-bold text-danger fs-6 mb-0.5">This Vendor is Blacklisted & Permanently Locked</div>
        <div class="text-secondary small">
          Reason: <strong>{{ $vendor->blacklist_reason }}</strong>
          @if ($vendor->blacklisted_at)
          &bull; Actioned on {{ $vendor->blacklisted_at->format('d M Y, h:i A') }}
          @endif
        </div>
      </div>
    </div>
    <span class="badge bg-danger text-white px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">Non-Compliant</span>
  </div>
  @elseif ($vendor->status === 'on_hold')
  <!-- On Hold Alert Banner -->
  <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-4" style="border-radius: 10px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.25rem;">
        <i class="bi bi-pause-circle-fill"></i>
      </div>
      <div>
        <div class="fw-bold text-dark fs-6 mb-0.5">This Vendor is Currently On Hold</div>
        <div class="text-secondary small">
          Review is temporarily paused at the current approval level pending required documents or clarifications.
        </div>
      </div>
    </div>
    <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
  </div>
  @endif

  <!-- Clean & Unified Header Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100" style="border: 1px solid var(--border-color) !important; border-radius: 10px; background: #ffffff;">
    <div class="p-4 p-md-4">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">

        <!-- Left: Vendor Identity & Clean Status -->
        <div>
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <h1 class="h5 mb-0 fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $vendor->company_name }}</h1>
            <span class="badge badge-soft-{{ $meta['badge'] }} status-badge-pipeline px-2.5 py-1" style="font-size: 12px;">
              {{ $meta['label'] }}
            </span>
            @if ($vendor->msme_status)
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 11px; font-weight: 600;">MSME</span>
            @endif
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="detail-meta-chip"><i class="bi bi-upc-scan"></i> <span class="meta-label">Code:</span> <strong>{{ $vendor->vendor_code }}</strong></span>
            @if ($vendor->category)
              <span class="detail-meta-chip"><i class="bi bi-tag"></i> <span class="meta-label">Category:</span> <strong>{{ $vendor->category }}</strong></span>
            @endif
            @if ($vendor->submittedBy)
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Submitted By:</span> <strong>{{ $vendor->submittedBy->name }}</strong></span>
            @endif
            @php
              $vLocation = $vendor->location ?: trim(($vendor->city ? $vendor->city . ', ' : '') . $vendor->state);
            @endphp
            @if ($vLocation)
              <span class="detail-meta-chip"><i class="bi bi-geo-alt"></i> <span class="meta-label">Location:</span> <strong>{{ $vLocation }}</strong></span>
            @endif
          </div>
        </div>

        <!-- Right: Focused Actions in One Clean Row -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          @if ($vendor->isBlacklisted())
          <span class="badge bg-dark text-white border border-dark px-2.5 py-1.5" style="border-radius: 6px; font-size: 12px; font-weight: 600;">
            <i class="bi bi-slash-circle-fill text-danger me-1"></i>Blacklisted
          </span>
          @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
          <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#unblacklistDossierModal" style="font-size: 12.5px;">
            <i class="bi bi-shield-check"></i>
            <span>Unblacklist</span>
          </button>
          @endif
          @else
          @php
          $canEditOrDelete = in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
          $canBlacklist = auth()->user()->role === \App\Support\Roles::ADMIN_HEAD;
          $canResendEmail = $vendor->isEmpanelled() && in_array(auth()->user()->role, [\App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::BUYER], true);
          @endphp

          @if ($pendingApproval && !$vendor->isBlacklisted())
          <!-- On Hold Button -->
          <button type="button" class="btn btn-warning btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap text-dark" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#holdVendorModal">
            <i class="bi bi-pause-circle"></i>
            <span>On Hold</span>
          </button>

          <!-- Approve Button -->
          <form method="POST" action="{{ route('empanelment.approve', $vendor) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-success btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;">
              <i class="bi bi-check-lg"></i>
              <span>Approve Application</span>
            </button>
          </form>

          <!-- Decline / Reject Button -->
          <button type="button" class="btn btn-outline-danger btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#declineVendorModal">
            <i class="bi bi-x-lg"></i>
            <span>Decline</span>
          </button>
          @endif

          <!-- Clean More Dropdown (Holding Edit, Resend, Blacklist, Delete) -->
          @if ($canBlacklist || $canEditOrDelete || $canResendEmail)
          <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center px-2.5 py-1.5 fw-semibold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 13px; border-radius: 6px;" title="More options">
              <i class="bi bi-three-dots"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="font-size: 12.5px; min-width: 190px;">
              @if ($canEditOrDelete && $vendor->status !== 'approved')
              <li>
                <a href="{{ route('empanelment.edit', $vendor) }}" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-dark">
                  <i class="bi bi-pencil text-secondary"></i>
                  <span>Edit Vendor Details</span>
                </a>
              </li>
              @endif
              @if ($canResendEmail)
              <li>
                <form method="POST" action="{{ route('empanelment.resend-email', $vendor) }}" class="m-0">
                  @csrf
                  <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-primary">
                    <i class="bi bi-envelope-arrow-up text-primary"></i>
                    <span>Resend Empanelment Email</span>
                  </button>
                </form>
              </li>
              @endif
              @if ($canBlacklist)
              @if ($canResendEmail || ($canEditOrDelete && $vendor->status !== 'approved'))
              <li>
                <hr class="dropdown-divider my-1">
              </li>
              @endif
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-danger" data-bs-toggle="modal" data-bs-target="#blacklistDossierModal">
                  <i class="bi bi-slash-circle text-danger"></i>
                  <span>Blacklist Vendor</span>
                </button>
              </li>
              @endif
              @if ($canEditOrDelete && $vendor->status !== 'approved')
              @if ($canBlacklist || $canResendEmail)
              <li>
                <hr class="dropdown-divider my-1">
              </li>
              @endif
              <li>
                <form method="POST" action="{{ route('empanelment.destroy', $vendor) }}" onsubmit="return confirm('Are you sure you want to delete vendor {{ $vendor->vendor_code }} ({{ $vendor->company_name }})? This action cannot be undone.');" class="mb-0">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-danger">
                    <i class="bi bi-trash3 text-danger"></i>
                    <span>Delete Vendor</span>
                  </button>
                </form>
              </li>
              @endif
            </ul>
          </div>
          @endif
          @endif
        </div>

      </div>
    </div>
  </div>

  <!-- Horizontal Empanelment Governance Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Application Lodged -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Application Lodged">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Application Lodged</div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Registered
                </span>
              </div>
              <div class="h-step-person" title="{{ $vendor->submittedBy?->name ?? 'Buyer' }} ({{ $vendor->category }})">
                {{ $vendor->submittedBy?->name ?? 'Buyer' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $vendor->created_at ? $vendor->created_at->timezone('Asia/Kolkata')->format('d-M-Y H:i') . ' IST' : '—' }}
              </div>
            </div>
          </div>

          <!-- Multi-Tier Empanelment Approval Stages -->
          @php
          $approvalL1 = $approvals->firstWhere('level', 1); // Admin Head
          $approvalL2 = $approvals->firstWhere('level', 2); // ASG 1
          $approvalL3 = $approvals->firstWhere('level', 3); // ASG 2
          $approvalL4 = $approvals->firstWhere('level', 4); // CFO
          @endphp

          <!-- Stage 1: Admin Head -->
          @if ($approvalL1)
          @php
          $isApproved = $approvalL1->status === 'approved';
          $isRejected = $approvalL1->status === 'rejected';
          $isPending = $approvalL1->status === 'pending';
          $isCurrentDue = (!$isVendorRejected && $isPending && $approvalL1->level === $activePendingLevel);
          $roleShort = 'Admin Head';

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
              <span class="text-secondary fw-bold" style="font-size: 11.5px;">1</span>
              @endif
            </div>
            <div class="h-step-card {{ $cardClass }}">
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Admin Head
                </span>
              </div>
              <div class="h-step-person">
                @if ($approvalL1->approver)
                <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $approvalL1->approver->name }}</span>
                @elseif ($isCurrentDue)
                <span class="text-warning fw-bold">Action Required</span>
                @else
                <span class="text-muted">Awaiting Stage</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($approvalL1->acted_at)
                {{ $approvalL1->acted_at->timezone('Asia/Kolkata')->format('d-M-Y H:i') }} IST
                @elseif ($isRejected)
                Declined
                @else
                Pending
                @endif
              </div>
              @if ($approvalL1->comments)
              <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}"
                data-bs-toggle="modal"
                data-bs-target="#approvalCommentModal-{{ $approvalL1->id }}"
                title="Click to view full comment">
                &ldquo;{{ $approvalL1->comments }}&rdquo;
              </div>
              @endif
            </div>
          </div>
          @endif

          <!-- Combined Stage 2: ASG 1 & ASG 2 (Dual Secretarial Review) -->
          @if ($approvalL2 || $approvalL3)
          @php
          $asg1Approved = $approvalL2 && $approvalL2->status === 'approved';
          $asg1Rejected = $approvalL2 && $approvalL2->status === 'rejected';
          $asg1Pending = $approvalL2 && $approvalL2->status === 'pending';

          $asg2Approved = $approvalL3 && $approvalL3->status === 'approved';
          $asg2Rejected = $approvalL3 && $approvalL3->status === 'rejected';
          $asg2Pending = $approvalL3 && $approvalL3->status === 'pending';

          $bothApproved = $asg1Approved && $asg2Approved;
          $anyRejected = $asg1Rejected || $asg2Rejected;
          $isAsgActive = (!$isVendorRejected && !$bothApproved && in_array($activePendingLevel, [2, 3], true));

          if ($bothApproved) {
          $asgStepStatusClass = 'is-done';
          $asgIconClass = 'done';
          $asgCardClass = 'done-card';
          } elseif ($anyRejected) {
          $asgStepStatusClass = 'is-declined';
          $asgIconClass = 'rejected';
          $asgCardClass = 'active-card border-danger-subtle';
          } elseif ($isAsgActive) {
          $asgStepStatusClass = 'is-active';
          $asgIconClass = 'active';
          $asgCardClass = 'active-card';
          } else {
          $asgStepStatusClass = 'is-stopped';
          $asgIconClass = 'upcoming';
          $asgCardClass = 'stopped-card';
          }
          @endphp
          <div class="h-timeline-step {{ $asgStepStatusClass }}">
            <div class="h-step-icon {{ $asgIconClass }}">
              @if ($bothApproved)
              <i class="bi bi-check-lg"></i>
              @elseif ($anyRejected)
              <i class="bi bi-x-lg"></i>
              @elseif ($isAsgActive)
              <i class="bi bi-hourglass-split"></i>
              @else
              <span class="text-secondary fw-bold" style="font-size: 11.5px;">2</span>
              @endif
            </div>
            <div class="h-step-card {{ $asgCardClass }}">
              <div class="h-step-title {{ $bothApproved ? 'text-success' : ($anyRejected ? 'text-danger' : ($isAsgActive ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="ASG 1 & ASG 2 Review">
                ASG 1 &amp; ASG 2
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $bothApproved ? 'bg-success-subtle text-success' : ($anyRejected ? 'bg-danger-subtle text-danger' : ($isAsgActive ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  ASG 1 &amp; ASG 2
                </span>
              </div>

              <!-- Individual Sub-status for ASG 1 and ASG 2 -->
              <div class="d-flex flex-column gap-1 text-start my-1" style="font-size: 11px;">
                <!-- ASG 1 -->
                <div class="d-flex align-items-center justify-content-between p-1 rounded bg-light border" style="font-size: 10.5px;">
                  <span class="fw-semibold text-dark">ASG 1:</span>
                  @if ($asg1Approved)
                  <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-0.5"></i> {{ $approvalL2->approver?->name ?? 'Cleared' }}</span>
                  @elseif ($asg1Rejected)
                  <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-0.5"></i> Declined</span>
                  @elseif ($activePendingLevel === 2)
                  <span class="text-warning fw-bold"><i class="bi bi-clock me-0.5"></i> Awaiting Approval</span>
                  @else
                  <span class="text-muted">Pending</span>
                  @endif
                </div>

                <!-- ASG 2 -->
                <div class="d-flex align-items-center justify-content-between p-1 rounded bg-light border" style="font-size: 10.5px;">
                  <span class="fw-semibold text-dark">ASG 2:</span>
                  @if ($asg2Approved)
                  <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-0.5"></i> {{ $approvalL3->approver?->name ?? 'Cleared' }}</span>
                  @elseif ($asg2Rejected)
                  <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-0.5"></i> Declined</span>
                  @elseif ($activePendingLevel === 3)
                  <span class="text-warning fw-bold"><i class="bi bi-clock me-0.5"></i> Awaiting Approval</span>
                  @else
                  <span class="text-muted">Pending</span>
                  @endif
                </div>
              </div>

              <div class="h-step-meta mt-0.5">
                @if ($bothApproved)
                {{ optional($approvalL3->acted_at ?? $approvalL2->acted_at)->timezone('Asia/Kolkata')->format('d-M-Y H:i') }} IST
                @elseif ($anyRejected)
                Declined
                @elseif ($isAsgActive)
                @if ($asg1Approved) ASG 1 cleared • Awaiting ASG 2
                @else Awaiting ASG 1 &amp; ASG 2
                @endif
                @else
                Pending
                @endif
              </div>

              @php
              $asgCommentApproval = $approvalL3?->comments ? $approvalL3 : ($approvalL2?->comments ? $approvalL2 : null);
              @endphp
              @if ($asgCommentApproval)
              <div class="h-step-quote {{ $bothApproved ? 'quote-approved' : '' }}"
                data-bs-toggle="modal"
                data-bs-target="#approvalCommentModal-{{ $asgCommentApproval->id }}"
                title="Click to view full comment">
                &ldquo;{{ $asgCommentApproval->comments }}&rdquo;
              </div>
              @endif
            </div>
          </div>
          @endif

          <!-- Stage 3: CFO -->
          @if ($approvalL4)
          @php
          $isApproved = $approvalL4->status === 'approved';
          $isRejected = $approvalL4->status === 'rejected';
          $isPending = $approvalL4->status === 'pending';
          $isCurrentDue = (!$isVendorRejected && $isPending && $approvalL4->level === $activePendingLevel);
          $roleShort = 'CFO';

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
              <span class="text-secondary fw-bold" style="font-size: 11.5px;">3</span>
              @endif
            </div>
            <div class="h-step-card {{ $cardClass }}">
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  CFO
                </span>
              </div>
              <div class="h-step-person">
                @if ($approvalL4->approver)
                <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $approvalL4->approver->name }}</span>
                @elseif ($isCurrentDue)
                <span class="text-warning fw-bold">Action Required</span>
                @else
                <span class="text-muted">Awaiting Stage</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($approvalL4->acted_at)
                {{ $approvalL4->acted_at->timezone('Asia/Kolkata')->format('d-M-Y H:i') }} IST
                @elseif ($isRejected)
                Declined
                @else
                Pending
                @endif
              </div>
              @if ($approvalL4->comments)
              <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}"
                data-bs-toggle="modal"
                data-bs-target="#approvalCommentModal-{{ $approvalL4->id }}"
                title="Click to view full comment">
                &ldquo;{{ $approvalL4->comments }}&rdquo;
              </div>
              @endif
            </div>
          </div>
          @endif

          <!-- Final Stage: Empanelled Directory -->
          @php
          if ($isVendorEmpanelled) {
          $dirStatus = 'is-done';
          $dirIcon = 'done';
          $dirCard = 'done-card';
          } elseif ($isVendorRejected) {
          $dirStatus = 'is-stopped';
          $dirIcon = 'upcoming';
          $dirCard = 'stopped-card';
          } else {
          $dirStatus = 'is-stopped';
          $dirIcon = 'upcoming';
          $dirCard = 'stopped-card';
          }
          @endphp
          <div class="h-timeline-step {{ $dirStatus }}">
            <div class="h-step-icon {{ $dirIcon }}">
              <i class="bi {{ $isVendorEmpanelled ? 'bi-check-all' : 'bi-person-check' }}"></i>
            </div>
            <div class="h-step-card {{ $dirCard }}">
              <div class="h-step-title {{ $isVendorEmpanelled ? 'text-success' : 'text-muted' }} mb-1" title="Empanelled Directory">
                Directory Roster
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isVendorEmpanelled ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $isVendorEmpanelled ? 'Active' : 'Pending Empanelment' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($isVendorEmpanelled)
                <span class="text-success fw-semibold">Code: {{ $vendor->vendor_code }}</span>
                @else
                <span class="text-muted">Eligible for RFQs</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($isVendorEmpanelled)
                @php
                $lastApproval = $approvals->where('status', 'approved')->sortByDesc('acted_at')->first();
                @endphp
                {{ $lastApproval && $lastApproval->acted_at ? $lastApproval->acted_at->timezone('Asia/Kolkata')->format('d-M-Y H:i') . ' IST' : 'Active' }}
                @else
                Post-Approval
                @endif
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Statutory & Banking Dossier (TRF Clean Typography Dossier Style) -->
    <div class="col-12">
      <div class="card shadow-sm border-0 p-4" style="border: 1px solid #e2e8f0 !important; border-radius: 10px; background: #ffffff;">
        <div class="pb-3 mb-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color: #e2e8f0 !important;">
          <h2 class="h5 mb-0 fw-bold text-dark" style="font-size: 19px; letter-spacing: -0.01em;">Vendor Empanelment Dossier</h2>
          <span class="badge badge-soft-{{ $meta['badge'] }} status-badge-pipeline px-3 py-1.5" style="font-size: 12px;">{{ $meta['label'] }}</span>
        </div>

        <!-- Personal / Firm Details -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Personal / Firm Details</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Legal Entity / Person Name</div>
              <div class="text-dark fw-bold" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->company_name }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Contact Person in Case of Entity</div>
              <div class="text-dark fw-semibold {{ $vendor->contact_person ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->contact_person ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Government ID / Reg / CIN</div>
              <div class="text-dark fw-semibold font-monospace {{ $vendor->id_number ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->id_number ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Mobile Phone Number</div>
              @php $phoneVal = $vendor->contact_phone ?? $vendor->phone; @endphp
              @if ($phoneVal)
                <div><a href="tel:{{ $phoneVal }}" class="text-dark text-decoration-none fw-semibold font-monospace" style="font-size: 13.5px;">{{ $phoneVal }}</a></div>
              @else
                <div class="text-muted" style="font-size: 13.5px;">Not Specified</div>
              @endif
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Official Email Address</div>
              @php $emailVal = $vendor->contact_email ?? $vendor->email; @endphp
              @if ($emailVal)
                <div><a href="mailto:{{ $emailVal }}" class="text-primary text-decoration-none fw-semibold" style="font-size: 13.5px;">{{ $emailVal }}</a></div>
              @else
                <div class="text-muted" style="font-size: 13.5px;">Not Specified</div>
              @endif
            </div>

            <div class="col-12">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Complete Registered Address</div>
              <div class="text-dark fw-semibold {{ $vendor->address ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->address ?: 'Not Specified' }}</div>
            </div>
          </div>
        </div>

        <!-- Banking Details -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Banking Details</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Bank Name</div>
              <div class="text-dark fw-bold {{ $vendor->bank_name ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->bank_name ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-8">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Branch Address</div>
              <div class="text-dark fw-semibold {{ $vendor->bank_branch ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->bank_branch ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Account Holder Name</div>
              <div class="text-dark fw-semibold {{ $vendor->bank_account_holder ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->bank_account_holder ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Bank Account Number</div>
              @php $bankAcct = $vendor->bank_account_no ?? $vendor->bank_account; @endphp
              <div class="text-dark fw-bold font-monospace {{ $bankAcct ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $bankAcct ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-2">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Type of Account</div>
              <div class="text-dark fw-semibold {{ $vendor->bank_account_type ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->bank_account_type ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-2">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">IFSC Code</div>
              @php $ifscVal = $vendor->bank_ifsc ?? $vendor->ifsc_code; @endphp
              <div class="text-dark fw-bold font-monospace {{ $ifscVal ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $ifscVal ?: 'Not Specified' }}</div>
            </div>
          </div>
        </div>

        <!-- Business & References -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Business &amp; References</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Business Category (Deals In)</div>
              <div class="text-dark fw-bold" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->category ?: 'General' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Performance Rating &amp; Completed Orders</div>
              <div>
                @if ($vendor->average_rating)
                  <span class="badge badge-soft-warning status-badge-pipeline">
                    ★ {{ number_format($vendor->average_rating, 1) }} / 5.0
                  </span>
                  <span class="text-secondary small ms-2 fw-normal">({{ $vendor->total_orders_count }} {{ Str::plural('order', $vendor->total_orders_count) }})</span>
                @else
                  <span class="badge badge-soft-secondary status-badge-pipeline">
                    New Vendor (No orders yet)
                  </span>
                @endif
              </div>
            </div>

            <div class="col-12">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Professional / Trade References</div>
              <div class="text-dark fw-semibold {{ $vendor->references ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a; white-space: pre-line;">{{ $vendor->references ?: 'None provided' }}</div>
            </div>
          </div>
        </div>

        <!-- Tax & Statutory Details -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Tax &amp; Statutory Details</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Permanent Account Number (PAN)</div>
              @php $panVal = $vendor->pan ?? $vendor->pan_number; @endphp
              <div class="text-dark fw-bold font-monospace {{ $panVal ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $panVal ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">GST Registration Number (GSTIN)</div>
              <div class="text-dark fw-bold font-monospace {{ $vendor->gstin ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->gstin ?: 'Not Specified' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">MSME / UDYAM Number</div>
              <div class="text-dark fw-semibold font-monospace {{ $vendor->msme_number || $vendor->msme_status ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">
                {{ $vendor->msme_number ?: ($vendor->msme_status ? 'Registered (Number Not Stated)' : 'Not Registered') }}
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Incorporation Certificate Number</div>
              <div class="text-dark fw-semibold font-monospace {{ $vendor->incorporation_certificate_no ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->incorporation_certificate_no ?: 'Not Applicable' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Partnership Deed / Registration Number</div>
              <div class="text-dark fw-semibold font-monospace {{ $vendor->partnership_deed_no ? '' : 'text-muted' }}" style="font-size: 13.5px; color: #0f172a;">{{ $vendor->partnership_deed_no ?: 'Not Applicable' }}</div>
            </div>
          </div>
        </div>

        <!-- Self - Declaration Form & Authorized Signature -->
        @php
        $sigAttachment = $vendor->signatureAttachment();
        @endphp
        <div class="mb-0">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Declaration with Signature / Date</h3>
            <div class="d-flex align-items-center gap-2">
              <a href="{{ route('empanelment.declaration', $vendor) }}" target="_blank" class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1.5" style="height: 32px; font-size: 12px; border-radius: 6px;">
                <i class="bi bi-eye"></i>
                <span>Preview Form</span>
              </a>
              <a href="{{ route('empanelment.declaration', $vendor) }}" target="_blank" class="btn btn-primary fw-semibold d-inline-flex align-items-center gap-1.5 px-3" style="height: 32px; font-size: 12px; border-radius: 6px;">
                <i class="bi bi-file-earmark-arrow-down-fill"></i>
                <span>Download Self-Declaration Form</span>
              </a>
            </div>
          </div>

          <div class="row g-3 align-items-start mb-4">
            <div class="col-12 col-md-8">
              <div class="row g-3">
                <div class="col-12 col-sm-6">
                  <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Status</div>
                  <div>
                    @if($vendor->declaration_accepted)
                      <span class="badge badge-soft-success status-badge-pipeline">Accepted &amp; Digitally Affirmed</span>
                    @else
                      <span class="badge badge-soft-secondary status-badge-pipeline">Recorded upon submission</span>
                    @endif
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Date of Submission / Declaration</div>
                  <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">
                    {{ optional($vendor->declaration_date)->format('d M Y') ?? optional($vendor->created_at)->format('d M Y') }}
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Authorized Signatory</div>
                  <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">
                    {{ $vendor->declaration_signatory_name ?: ($vendor->contact_person ?: 'Authorized Representative') }}
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Designation / Capacity</div>
                  <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">
                    {{ $vendor->declaration_designation ?: 'Authorized Signatory' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Specimen Signature or Download Preview Tile -->
            <div class="col-12 col-md-4 border-start-md ps-md-4">
              <div class="text-secondary small mb-2" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Official Stamp / Specimen Signature</div>
              @if($sigAttachment)
                <div class="p-3 rounded-2 bg-light border text-center">
                  @if($sigAttachment->isImage())
                    <img src="{{ $sigAttachment->url }}" alt="Specimen Signature" style="max-height: 60px; max-width: 100%; object-fit: contain;">
                  @else
                    <div class="text-primary small fw-semibold py-1">
                      <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>{{ Str::limit($sigAttachment->original_name, 22) }}
                    </div>
                  @endif
                  <div class="mt-2 d-flex justify-content-center gap-2">
                    <a href="{{ $sigAttachment->url }}" target="_blank" class="btn btn-outline-secondary py-1 px-2.5" style="font-size: 11.5px; border-radius: 5px;">
                      <i class="bi bi-download me-1"></i>Download
                    </a>
                    <a href="{{ route('empanelment.declaration', $vendor) }}" target="_blank" class="btn btn-primary py-1 px-2.5" style="font-size: 11.5px; border-radius: 5px;">
                      <i class="bi bi-eye me-1"></i>Preview
                    </a>
                  </div>
                </div>
              @else
                <div class="p-3 rounded-2 bg-light border text-center">
                  <i class="bi bi-file-earmark-check text-success d-block fs-4 mb-1"></i>
                  <div class="fw-semibold text-dark" style="font-size: 13px;">Affirmed via Portal</div>
                  <div class="mt-2">
                    <a href="{{ route('empanelment.declaration', $vendor) }}" target="_blank" class="btn btn-outline-primary py-1 px-2.5" style="font-size: 11.5px; border-radius: 5px;">
                      <i class="bi bi-eye me-1"></i>Preview Form
                    </a>
                  </div>
                </div>
              @endif
            </div>
          </div>

          <!-- Attached Supporting Documents List -->
          @if($vendor->attachments->isNotEmpty())
            <div class="p-3 rounded-3 bg-light border">
              <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
                <span class="text-dark fw-bold" style="font-size: 13.5px;">
                  <i class="bi bi-paperclip me-1 text-primary"></i>Uploaded Supporting Documents ({{ $vendor->attachments->count() }})
                </span>
              </div>
              <div class="row g-2.5">
                @foreach($vendor->attachments as $att)
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-2.5 rounded-2 bg-white border">
                      <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi bi-file-earmark-check text-primary fs-5 flex-shrink-0"></i>
                        <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;" title="{{ $att->original_name }}">{{ $att->original_name }}</span>
                      </div>
                      <a href="{{ $att->url }}" target="_blank" class="btn btn-outline-secondary py-1 px-2.5 fw-semibold text-nowrap flex-shrink-0" style="font-size: 11.5px; border-radius: 5px;">
                        View
                      </a>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if ($pendingApproval)
  <!-- Place On Hold Modal -->
  <div class="modal fade" id="holdVendorModal" tabindex="-1" aria-labelledby="holdVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <form method="POST" action="{{ route('empanelment.hold', $vendor) }}" class="modal-content border-0 shadow">
        @csrf
        <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
          <div class="d-flex align-items-center gap-2 text-warning-emphasis">
            <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
            <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdVendorModalLabel">Place Vendor On Hold</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3.5">
          <p class="text-secondary small mb-3">
            Placing <strong>{{ $vendor->company_name }}</strong> ({{ $vendor->vendor_code }}) on hold temporarily pauses the onboarding approval chain at this stage. You can resume and decide (Approve/Reject) at any time.
          </p>
          <div class="mb-1">
            <label for="holdVendorComments" class="form-label fw-semibold text-dark small">Reason for Holding Review <span class="text-danger">*</span></label>
            <textarea name="comments" id="holdVendorComments" class="form-control" rows="3" placeholder="Provide justification (e.g. Additional financial documents requested, vendor clarifications pending)..." required></textarea>
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

  <!-- Decline Vendor Modal (Small, Clean & Minimal Window) -->
  <div class="modal fade" id="declineVendorModal" tabindex="-1" aria-labelledby="declineVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <form method="POST" action="{{ route('empanelment.reject', $vendor) }}" class="modal-content border-0 shadow">
        @csrf
        <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
          <div class="d-flex align-items-center gap-2 text-danger">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <h5 class="modal-title fw-bold fs-6 mb-0" id="declineVendorModalLabel">Decline Vendor Application</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3.5">
          <p class="text-secondary small mb-3">
            Please state the specific reason for declining <strong>{{ $vendor->company_name }}</strong> ({{ $vendor->vendor_code }}). This feedback will be recorded in the governance audit trail.
          </p>
          <div class="mb-1">
            <label for="rejectVendorComments" class="form-label fw-semibold text-dark small">Reason for Rejection <span class="text-danger">*</span></label>
            <textarea name="comments" id="rejectVendorComments" class="form-control" rows="3" placeholder="e.g. Incomplete GST compliance documents, address mismatch, negative credit check, etc." required></textarea>
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

  @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && !$vendor->isBlacklisted())
  <!-- Blacklist Modal for Vendor Show -->
  <div class="modal fade" id="blacklistDossierModal" tabindex="-1" aria-labelledby="blacklistDossierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <form method="POST" action="{{ route('empanelment.blacklist', $vendor) }}" class="modal-content border-0 shadow">
        @csrf
        <div class="modal-header bg-dark text-white py-2.5 px-3.5 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-slash-circle-fill text-danger fs-5"></i>
            <h5 class="modal-title fw-bold fs-6 mb-0 text-white" id="blacklistDossierModalLabel">Blacklist Vendor Dossier</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3.5">
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-2.5 mb-3 rounded" style="font-size: 12px;">
            <i class="bi bi-shield-lock-fill fs-5 flex-shrink-0"></i>
            <div>
              <strong>Permanent Lock:</strong> Blacklisting permanently disqualifies this vendor from bidding, contracts, and PO issuance. Edit and delete will be locked.
            </div>
          </div>
          <div class="mb-2">
            <label for="blacklistReasonDossier" class="form-label fw-semibold text-danger small">Mandatory Audit Justification <span class="text-danger">*</span></label>
            <textarea name="reason" id="blacklistReasonDossier" class="form-control" rows="3" placeholder="Provide reason for blacklisting (e.g. Fraudulent GST certificate, repeated non-delivery, insolvency, etc.)..." required minlength="5"></textarea>
            <div class="form-text text-muted" style="font-size: 11px;">Minimum 5 characters. Stored in compliance audit log.</div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
            <i class="bi bi-slash-circle-fill"></i>
            <span>Confirm Blacklist</span>
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $vendor->isBlacklisted())
  <!-- Unblacklist Modal for Vendor Show -->
  <div class="modal fade" id="unblacklistDossierModal" tabindex="-1" aria-labelledby="unblacklistDossierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <form method="POST" action="{{ route('empanelment.unblacklist', $vendor) }}" class="modal-content border-0 shadow">
        @csrf
        <div class="modal-header bg-success-subtle text-success py-2.5 px-3.5 border-bottom border-success-subtle">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check fs-5"></i>
            <h5 class="modal-title fw-bold fs-6 mb-0 text-success" id="unblacklistDossierModalLabel">Reinstate / Unblacklist Vendor</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3.5">
          <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-2.5 mb-3 rounded" style="font-size: 12px;">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>
              Reinstating this vendor will reactivate compliance status and lift the permanent lock, allowing ongoing procurement participation.
            </div>
          </div>
          @if ($vendor->blacklist_reason)
          <div class="mb-3 bg-light p-2.5 rounded border small">
            <span class="text-danger fw-semibold">Blacklisted on:</span> {{ optional($vendor->blacklisted_at)->format('d M Y') ?? 'Recorded' }}<br>
            <span class="text-danger fw-semibold">Reason:</span> {{ $vendor->blacklist_reason }}
          </div>
          @endif
          <div class="mb-2">
            <label for="unblacklistReasonDossier" class="form-label fw-semibold text-success small">Mandatory Audit Justification <span class="text-danger">*</span></label>
            <textarea name="reason" id="unblacklistReasonDossier" class="form-control" rows="3" placeholder="State reason for lifting blacklist (e.g. Legal settlement reached, audit cleared, compliance verified)..." required minlength="5"></textarea>
            <div class="form-text text-muted" style="font-size: 11px;">Minimum 5 characters. Stored in compliance audit log.</div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
            <i class="bi bi-shield-check"></i>
            <span>Reinstate Vendor</span>
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Approval Comments Modals --}}
  @foreach ($approvals as $approval)
  @if ($approval->comments)
  @php
  $stageTitle = match ($approval->level) {
  1 => 'Admin Head Review',
  2 => 'ASG 1 Review',
  3 => 'ASG 2 Review',
  4 => 'CFO Financial Review',
  default => 'Reviewer (Tier ' . $approval->level . ')',
  };
  $isAppr = $approval->status === 'approved';
  $isRej = $approval->status === 'rejected';
  @endphp
  <div class="modal fade" id="approvalCommentModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-quote text-primary fs-5"></i>
            <h6 class="modal-title fw-bold text-dark mb-0">Approval Remarks — {{ $approval->approver?->name ?? 'Approver' }}</h6>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3.5">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom text-muted small">
            <div><span class="text-secondary">Stage:</span> <strong class="text-dark">{{ $stageTitle }}</strong></div>
            <div>
              <span class="badge {{ $isAppr ? 'bg-success-subtle text-success' : ($isRej ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis') }} border">
                {{ ucfirst($approval->status) }}
              </span>
            </div>
          </div>
          <div class="p-3.5 rounded-3 bg-white border text-dark shadow-2xs" style="font-size: 14px; font-weight: 500; line-height: 1.6; border-left: 4px solid {{ $isAppr ? 'var(--bs-success)' : ($isRej ? 'var(--bs-danger)' : 'var(--bs-warning)') }} !important;">
            &ldquo;{{ trim($approval->comments) }}&rdquo;
          </div>
          @if ($approval->acted_at)
          <div class="mt-2 text-end text-muted" style="font-size: 11px;">
            Recorded on {{ $approval->acted_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST
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
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Detail' });
@endsection