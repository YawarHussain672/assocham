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

  <!-- Vendor Header Dossier Card -->
  <div class="detail-header-card">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#2563eb,#3b82f6);">
        <i class="bi bi-building"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="detail-name">{{ $vendor->company_name }}</div>
          <span class="badge bg-light text-dark font-monospace border">
            {{ $vendor->vendor_code }}
          </span>
          @if ($vendor->msme_status)
          <span class="badge bg-success-subtle text-success border border-success-subtle">
            <i class="bi bi-patch-check me-1"></i> MSME
          </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1 flex-wrap">
          <span><i class="bi bi-tags me-1"></i> {{ $vendor->category ?: 'General Goods & Services' }}</span>
          <span>•</span>
          <span><i class="bi bi-person me-1"></i> Submitted by {{ $vendor->submittedBy?->name ?? 'Buyer' }}</span>
        </div>
      </div>
    </div>
    <div>
      <span class="badge bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} border border-{{ $meta['badge'] }}-subtle px-3 py-2 fs-6">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Statutory & Banking Dossier (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel h-100 p-0 overflow-hidden d-flex flex-column justify-content-between">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-check text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold">Statutory &amp; Banking Dossier</h6>
          </div>
          <span class="badge bg-light text-dark border">
            Compliance Profile
          </span>
        </div>

        <div class="card-body p-4">
          <div class="row g-3">
            <!-- Statutory Tax Information -->
            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Permanent Account Number (PAN)
                </div>
                <div class="font-monospace fw-bold text-dark fs-6">
                  {{ $vendor->pan ?: 'Not provided' }}
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  GST Identification Number (GSTIN)
                </div>
                <div class="font-monospace fw-bold text-dark fs-6">
                  {{ $vendor->gstin ?: 'Unregistered' }}
                </div>
              </div>
            </div>

            <!-- Point of Contact -->
            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Designated Representative
                </div>
                <div class="fw-semibold text-dark">
                  {{ $vendor->contact_person ?: '—' }}
                </div>
                <div class="text-muted small mt-1">
                  {{ $vendor->contact_email ?: 'No email on record' }}
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Contact Phone
                </div>
                <div class="fw-semibold text-dark">
                  {{ $vendor->contact_phone ?: '—' }}
                </div>
                <div class="text-muted small mt-1">
                  Mobile / Landline
                </div>
              </div>
            </div>

            <!-- Banking Credentials -->
            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Bank Account Number
                </div>
                <div class="font-monospace fw-bold text-dark fs-6">
                  {{ $vendor->bank_account_no ?: '—' }}
                </div>
                <div class="text-muted small mt-1">
                  Settlement Account
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="p-3 rounded-3 bg-light h-100">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Bank IFSC Code
                </div>
                <div class="font-monospace fw-bold text-dark fs-6">
                  {{ $vendor->bank_ifsc ?: '—' }}
                </div>
                <div class="text-muted small mt-1">
                  Electronic Transfer (NEFT/RTGS)
                </div>
              </div>
            </div>

            <!-- Commercial Terms -->
            <div class="col-12">
              <div class="p-3 rounded-3 bg-light">
                <div class="row g-2 align-items-center">
                  <div class="col-sm-6">
                    <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                      Commercial Payment Terms
                    </div>
                    <div class="fw-semibold text-dark">
                      {{ $vendor->payment_terms ?: 'Net 30 Days' }}
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                      Enterprise Classification
                    </div>
                    <div>
                      @if ($vendor->msme_status)
                      <span class="badge bg-success-subtle text-success border border-success-subtle">MSME Priority Settlement (45 Days)</span>
                      @else
                      <span class="text-muted small">Standard Enterprise</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>

            @if ($vendor->address)
            <div class="col-12">
              <div class="p-3 rounded-3 bg-light">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size:11px; letter-spacing:0.04em;">
                  Registered Business Address
                </div>
                <div class="text-dark fw-medium small">
                  <i class="bi bi-geo-alt text-danger me-1"></i>
                  {{ $vendor->address }}
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Empanelment Approval Chain (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel h-100 p-0 overflow-hidden d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <!-- <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #0284c7, #2563eb); color:#fff; box-shadow:0 3px 8px rgba(37,99,235,0.25);">
                <i class="bi bi-diagram-3-fill fs-6"></i>
              </div> -->
              <div>
                <h6 class="mb-0 fw-bold">Empanelment Governance Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">Vendor Onboarding &amp; Compliance Clearance</div>
              </div>
            </div>
            <!-- @php
            $vendorApprovedCount = $vendor->approvals->where('status', 'approved')->count();
            $vendorTotalCount = $vendor->approvals->count();
            $isVendorEmpanelled = $vendorTotalCount > 0 && $vendorApprovedCount === $vendorTotalCount;
            @endphp
            <span class="badge {{ $isVendorEmpanelled ? 'bg-success-subtle text-success border border-success-subtle' : ($vendor->status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isVendorEmpanelled)
              <i class="bi bi-check-circle-fill me-1"></i>Empanelled ({{ $vendorApprovedCount }}/{{ $vendorTotalCount }})
              @elseif ($vendor->status === 'rejected')
              <i class="bi bi-x-circle-fill me-1"></i>Rejected
              @else
              <i class="bi bi-clock-history me-1"></i>{{ $vendorApprovedCount }}/{{ $vendorTotalCount }} Cleared
              @endif
            </span> -->
          </div>

          <div class="card-body p-4">
            <!-- Micro Progress Bar -->
            <div class="travel-lifecycle-bar" title="Empanelment Progress">
              <div class="bar-segment done" title="1. Submitted"></div>
              <div class="bar-segment {{ $vendorApprovedCount >= 1 ? 'done' : ($vendor->status === 'pending_admin_head' ? 'active' : '') }}" title="Admin Audit"></div>
              <div class="bar-segment {{ $vendorApprovedCount >= 2 ? 'done' : ($vendor->status === 'pending_l1' ? 'active' : '') }}" title="Secretarial 1"></div>
              <div class="bar-segment {{ $vendorApprovedCount >= 3 ? 'done' : ($vendor->status === 'pending_l2' ? 'active' : '') }}" title="Secretarial 2"></div>
              <div class="bar-segment {{ $isVendorEmpanelled ? 'done' : ($vendor->status === 'pending_cfo' ? 'active' : '') }}" title="Finance Clearance"></div>
            </div>

            <!-- Connected Vertical Workflow Timeline -->
            <div class="workflow-timeline position-relative">

              <!-- Step 1: Application Lodged -->
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
                      <span class="timeline-step-role text-success">Application Lodged</span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Registered
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>{{ $vendor->submittedBy?->name ?? 'Buyer' }} ({{ $vendor->category }})</span>
                      <span>{{ $vendor->created_at?->format('d M, H:i') }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Approval Stages -->
              @php
              $vendorStageTitles = [
              1 => 'Administrative & KYC Audit (Admin Head)',
              2 => 'Secretarial Review 1 (ASG 1)',
              3 => 'Secretarial Review 2 (ASG 2)',
              4 => 'Financial Clearance (CFO)',
              ];
              @endphp
              @foreach ($vendor->approvals->sortBy('level') as $approval)
              @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';

              $connectorClass = $isApproved ? 'approved' : ($isPending ? 'pending dashed' : 'dashed');
              $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending pulse-ring' : 'upcoming'));
              $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
              $stageTitle = $vendorStageTitles[$approval->level] ?? \App\Support\Roles::label($approval->role_required);
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
                  <div class="timeline-step-connector {{ $loop->last && !$isVendorEmpanelled ? '' : $connectorClass }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isPending ? 'text-primary' : '') }}">
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

              <!-- Step: Empanelled Directory -->
              @if ($isVendorEmpanelled)
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon approved">
                    <i class="bi bi-patch-check-fill"></i>
                  </div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card approved-stage">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role text-success">Empanelled Directory</span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Active
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5">
                      Code: <strong>{{ $vendor->vendor_code }}</strong> &bull; Eligible for RFQs
                    </div>
                  </div>
                </div>
              </div>
              @endif

            </div>

            <!-- Decision Box for authorized approver -->
            @if ($pendingApproval)
            @php
            $pendingStageTitle = $vendorStageTitles[$pendingApproval->level] ?? \App\Support\Roles::label($pendingApproval->role_required);
            @endphp
            <div class="mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">
              <div class="p-3 rounded-3" style="background:#fffcf5; border:1px solid #fde68a; box-shadow:0 2px 8px rgba(245,158,11,0.06);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:20px; height:20px; font-size:11px;">
                      <i class="bi bi-pen"></i>
                    </div>
                    <span>Your Decision Required</span>
                  </div>
                  <!-- <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
                    {{ $pendingStageTitle }}
                  </span> -->
                </div>
                <!-- 
                <div class="text-secondary small mb-2.5" style="font-size:12px;">
                  Verify KYC credentials, tax registrations, and past track record before clearing.
                </div> -->

                <!-- Quick Comment Chips -->
                <!-- <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
                  <span class="quick-comment-chip" onclick="setVendorComment('KYC documents and credentials verified.')">✓ KYC Verified</span>
                  <span class="quick-comment-chip" onclick="setVendorComment('Approved for empanelment.')">✓ Approved for Empanelment</span>
                  <span class="quick-comment-chip" onclick="setVendorComment('Pricing terms and track record confirmed.')">💼 Terms Confirmed</span>
                </div> -->

                <form method="POST" action="{{ route('empanelment.approve', $vendor) }}" class="mb-2">
                  @csrf
                  <!-- <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                    <input type="text" name="comments" id="vendorCommentsInput" class="form-control" placeholder="Approval justification or remarks (optional)">
                  </div> -->
                  <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-check2-circle"></i>
                    <span>Approve &amp; Clear Stage</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('empanelment.reject', $vendor) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
                  @csrf
                  <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                    <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
                  </div>
                  <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                    <i class="bi bi-x-circle"></i>
                    <span>Decline Application</span>
                  </button>
                </form>

                <script>
                  function setVendorComment(text) {
                    var el = document.getElementById('vendorCommentsInput');
                    if (el) {
                      el.value = text;
                      el.focus();
                    }
                  }
                </script>
              </div>
            </div>
            @elseif (in_array($vendor->status, ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo']))
            <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
              <!-- <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i> -->
              <div>
                <div class="fw-bold mb-0.5">Awaiting Reviewing Authority</div>
                <div class="text-secondary" style="font-size:12px;">
                  Currently assigned to <strong>{{ \App\Support\Roles::label($vendor->approvals->where('status', 'pending')->first()?->role_required ?? 'Reviewing Authority') }}</strong>.
                </div>
              </div>
            </div>
            @elseif ($vendor->status === 'approved')
            <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
              <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
              <div>
                <div class="fw-bold mb-0.5">Vendor Empanelled</div>
                <div class="text-secondary" style="font-size:12px;">
                  All regulatory, secretarial, and financial verifications have been fully cleared.
                </div>
              </div>
            </div>
            @endif

          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Detail' });
@endsection