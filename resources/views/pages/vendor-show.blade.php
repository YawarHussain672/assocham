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
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: Vendor Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(37,99,235,0.12),rgba(37,99,235,0.04)); color:#2563eb; border:1px solid rgba(37,99,235,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-building fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1.5">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $vendor->company_name }}</span>
              <span class="badge bg-light text-secondary font-monospace border" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                {{ $vendor->vendor_code }}
              </span>
              @if ($vendor->msme_status)
                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px; font-weight:600; padding:3px 7px; border-radius:6px;">
                  <i class="bi bi-patch-check me-1"></i> MSME
                </span>
              @endif
              @if ($vendor->category)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:11px; font-weight:600; padding:3px 7px; border-radius:6px;">
                  <i class="bi bi-tag me-1"></i>{{ $vendor->category }}
                </span>
              @endif
            </div>
            
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 12.5px;">
              @if ($vendor->location || $vendor->address)
                <span class="text-dark"><i class="bi bi-geo-alt text-danger me-1"></i>{{ $vendor->location ?: $vendor->address }}</span>
                <span class="text-secondary">•</span>
              @endif

              @if ($vendor->average_rating)
                <span class="d-inline-flex align-items-center gap-1 text-dark fw-semibold">
                  <i class="bi bi-star-fill text-warning"></i> {{ number_format($vendor->average_rating, 1) }} / 5.0
                </span>
                <span class="text-secondary">•</span>
              @endif

              <span class="text-dark"><i class="bi bi-bag-check text-success me-1"></i>{{ $vendor->total_orders_count }} {{ Str::plural('Order', $vendor->total_orders_count) }}</span>
              
              @if ($vendor->submittedBy)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-person text-secondary me-1"></i>Submitted by <strong class="text-dark">{{ $vendor->submittedBy->name }}</strong></span>
              @endif
            </div>
          </div>
        </div>

        <!-- Right: Status Badge & Clean Consolidated Action Group -->
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
          @if ($vendor->isBlacklisted())
            <span class="badge bg-dark text-white border border-dark px-3 py-2 fs-6 d-inline-flex align-items-center gap-1.5 shadow-sm" style="border-radius: 6px; font-weight: 600;">
              <i class="bi bi-slash-circle-fill text-danger"></i>
              <span>Blacklisted</span>
            </span>
            @if (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
              <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#unblacklistDossierModal" style="font-size:12.5px;">
                <i class="bi bi-shield-check"></i>
                <span>Unblacklist / Reinstate</span>
              </button>
            @endif
          @else
            <!-- Standard Status Badge -->
            <span class="badge bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle px-3 py-2 fs-6" style="border-radius: 6px; font-weight: 600;">
              {{ $meta['label'] }}
            </span>

            @php
              $canEditOrDelete = in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
              $canBlacklist = auth()->user()->role === \App\Support\Roles::ADMIN_HEAD;
              $canResendEmail = $vendor->isEmpanelled() && in_array(auth()->user()->role, [\App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::BUYER], true);
            @endphp

            @if ($canResendEmail)
              <form method="POST" action="{{ route('empanelment.resend-email', $vendor) }}" class="d-inline-block m-0">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm fw-semibold" style="font-size:12.5px;" title="{{ $vendor->empanelment_email_sent_at ? 'Email sent on ' . $vendor->empanelment_email_sent_at->format('d M Y, h:i A') : 'Send notification email to vendor' }}">
                  <i class="bi bi-envelope-arrow-up"></i>
                  <span>{{ $vendor->empanelment_email_sent_at ? 'Resend Empanelment Email' : 'Send Empanelment Email' }}</span>
                </button>
              </form>
            @endif

            @if ($canEditOrDelete && $vendor->status !== 'approved')
              <a href="{{ route('empanelment.edit', $vendor) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm fw-semibold" style="font-size:12.5px;">
                <i class="bi bi-pencil"></i>
                <span>{{ $vendor->status === 'rejected' ? 'Correct & Resubmit' : 'Edit Vendor' }}</span>
              </a>
            @endif

            @if ($canBlacklist || ($canEditOrDelete && $vendor->status !== 'approved') || $canResendEmail)
              <!-- Actions Dropdown to Keep Header Clean & Minimal -->
              <div class="dropdown d-inline-block">
                <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 px-2.5 py-1.5 shadow-sm fw-semibold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:12.5px;">
                  <i class="bi bi-gear"></i>
                  <span>More</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="font-size:12.5px; min-width: 190px;">
                  @if ($canResendEmail)
                    <li>
                      <form method="POST" action="{{ route('empanelment.resend-email', $vendor) }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-primary">
                          <i class="bi bi-envelope-arrow-up text-primary"></i>
                          <span>{{ $vendor->empanelment_email_sent_at ? 'Resend Empanelment Email' : 'Send Empanelment Email' }}</span>
                        </button>
                      </form>
                    </li>
                  @endif
                  @if ($canBlacklist)
                    @if ($canResendEmail)
                      <li><hr class="dropdown-divider my-1"></li>
                    @endif
                    <li>
                      <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-danger" data-bs-toggle="modal" data-bs-target="#blacklistDossierModal">
                        <i class="bi bi-slash-circle text-danger"></i>
                        <span>Blacklist Vendor</span>
                      </button>
                    </li>
                  @endif
                  @if ($canEditOrDelete && $vendor->status !== 'approved')
                    @if ($canBlacklist)
                      <li><hr class="dropdown-divider my-1"></li>
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

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval && !$vendor->isBlacklisted())
      @php
        $pendingStageTitle = $vendorStageTitles[$pendingApproval->level] ?? \App\Support\Roles::label($pendingApproval->role_required);
      @endphp
      <div class="p-3 p-md-3.5 border-top w-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-top: 1px solid #fde68a !important;">
        <div class="row align-items-center g-3">
          
          <!-- Left: Stage Notice & Instructions with clear non-overlapping spacing -->
          <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center" style="gap: 16px;">
              <div class="flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px; min-width: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">
                <i class="bi bi-shield-exclamation fs-5"></i>
              </div>
              <div class="flex-grow-1 text-start">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="fw-bold text-dark" style="font-size: 15px;">Your Action Required</span>
                  <span class="badge bg-white text-dark border border-warning-subtle shadow-2xs px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                    Stage: {{ $pendingStageTitle }}
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Review statutory documentation and PAN/GSTIN compliance before approving or declining this vendor.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (No Inline Input) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <!-- Approve Button Form -->
              <form method="POST" action="{{ route('empanelment.approve', $vendor) }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-check2-circle fs-6"></i>
                  <span>Approve Application</span>
                </button>
              </form>

              <!-- Decline Button (Triggers Clean Small Modal Window) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#declineVendorModal">
                <i class="bi bi-x-circle fs-6"></i>
                <span>Decline</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Empanelment Governance Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-warning fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Empanelment Governance Timeline</h6>
      </div>
    </div>
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
          @foreach ($approvals as $approval)
            @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';
              $isCurrentDue = (!$isVendorRejected && $isPending && $approval->level === $activePendingLevel);
              $roleShort = $vendorStageTitles[$approval->level] ?? \App\Support\Roles::label($approval->role_required);

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
                <div class="h-step-title {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : ($isCurrentDue ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="{{ $roleShort }}">
                  Stage {{ $approval->level }}: {{ $roleShort }}
                </div>
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    @if ($isApproved) Cleared
                    @elseif ($isRejected) Declined
                    @elseif ($isCurrentDue) Action Due
                    @else Standby
                    @endif
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
                    {{ $approval->acted_at->timezone('Asia/Kolkata')->format('d-M-Y H:i') }} IST
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
    <!-- Statutory & Banking Dossier (Full Width, Perfectly Grid-Aligned) -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-warning"></i>
            <h6 class="mb-0 fw-bold text-dark">Statutory &amp; Banking Dossier</h6>
          </div>
          <span class="badge bg-light text-secondary border">
            {{ $vendor->category }}
          </span>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-building text-secondary"></i> Legal Entity Name</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $vendor->company_name }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-person text-secondary"></i> Primary Contact Person</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $vendor->contact_person ?: 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center justify-content-between mb-1">
                  <span class="d-flex align-items-center gap-1"><i class="bi bi-envelope text-secondary"></i> Email Address</span>
                  @if ($vendor->isEmpanelled())
                    @if ($vendor->empanelment_email_sent_at)
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size: 10px;" title="Notification sent on {{ $vendor->empanelment_email_sent_at->format('d M Y, h:i A') }}">
                        <i class="bi bi-check2-all me-1"></i>Email Sent
                      </span>
                    @else
                      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0.5 px-1.5" style="font-size: 10px;">
                        <i class="bi bi-envelope-exclamation me-1"></i>Not Sent
                      </span>
                    @endif
                  @endif
                </div>
                <div class="field-value text-dark" style="font-size:13px; font-weight:600;">{{ $vendor->contact_email ?? $vendor->email ?? 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-telephone text-secondary"></i> Phone Number</div>
                <div class="field-value text-dark" style="font-size:13px; font-weight:600;">{{ $vendor->contact_phone ?? $vendor->phone ?? 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-credit-card-2-front text-secondary"></i> PAN Number</div>
                <div class="field-value text-dark font-monospace" style="font-size:14px; font-weight:700;">{{ $vendor->pan ?? $vendor->pan_number ?: 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-receipt text-secondary"></i> GSTIN</div>
                <div class="field-value text-dark font-monospace" style="font-size:14px; font-weight:700;">{{ $vendor->gstin ?: 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-bank text-secondary"></i> Bank Account</div>
                <div class="field-value text-dark font-monospace" style="font-size:13px; font-weight:600;">{{ $vendor->bank_account_no ?? $vendor->bank_account ?: 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-geo text-secondary"></i> IFSC Code</div>
                <div class="field-value text-dark font-monospace" style="font-size:13px; font-weight:600;">{{ $vendor->bank_ifsc ?? $vendor->ifsc_code ?: 'Not Specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-geo-alt text-secondary"></i> Operating Location</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $vendor->location ?: ($vendor->address ?: 'Not Specified') }}</div>
              </div>
            </div>

            <div class="col-sm-3">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-star-fill text-warning"></i> Vendor Rating</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:700;">
                  @if ($vendor->average_rating)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2 d-inline-flex align-items-center gap-1">
                      <i class="bi bi-star-fill text-warning"></i>
                      <span>{{ number_format($vendor->average_rating, 1) }} / 5.0</span>
                    </span>
                  @else
                    <span class="text-muted fw-normal" style="font-size:13px;">Not Rated</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-sm-3">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-bag-check text-success"></i> Previous Orders</div>
                <div class="field-value text-dark font-monospace" style="font-size:13.5px; font-weight:700;">
                  <span class="badge bg-light text-dark border py-1 px-2">
                    <i class="bi bi-check2-circle text-success me-1"></i>{{ $vendor->total_orders_count }} {{ Str::plural('Order', $vendor->total_orders_count) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-pin-map text-secondary"></i> Registered Address</div>
                <div class="field-value text-dark" style="font-size:13px; font-weight:500;">{{ $vendor->address ?: 'Not Specified' }}</div>
              </div>
            </div>
          </div>

          <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
            <span>Submitted by {{ $vendor->submittedBy?->name ?? 'Buyer' }}</span>
            <span>Category: {{ $vendor->category }}</span>
          </div>

        </div>
      </div>
    </div>
  </div>

  @if ($pendingApproval)
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
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('PERMANENT ACTION: Are you sure you want to blacklist {{ $vendor->company_name }}?');">
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
            <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('Confirm lifting the blacklist on {{ $vendor->company_name }}?');">
              <i class="bi bi-shield-check"></i>
              <span>Reinstate Vendor</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Detail' });
@endsection
