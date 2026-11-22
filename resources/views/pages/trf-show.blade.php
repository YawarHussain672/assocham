@extends('layouts.app')

@section('title', $trf->trf_number)

@php
  $statusMeta = [
    'pending_asg' => ['label' => 'Pending — ASG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'pushed_back' => ['label' => 'Pushed Back', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$trf->status] ?? $statusMeta['draft'];

  $approvals = $trf->approvals->sortByDesc('id')->unique('level')->sortBy('level');
  $approvedApprovalsCount = $approvals->where('status', 'approved')->count();
  $totalApprovalsCount = $approvals->count();
  $isAllApproved = $totalApprovalsCount > 0 && $approvedApprovalsCount === $totalApprovalsCount;
  $isTrfRejected = $approvals->where('status', 'rejected')->isNotEmpty();
  $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

  $flightCount = $trf->flightLogs ? $trf->flightLogs->count() : 0;
  $flightCost = $trf->flightLogs ? $trf->flightLogs->sum('cost') : 0;

  $reimb = $trf->reimbursements ? $trf->reimbursements->first() : null;
  $hasReimbApproved = $reimb && $reimb->status === 'approved';
  $travelEndPassed = $trf->travel_end && $trf->travel_end->isPast();

  $trfEndDate = $trf->travel_end ?? $trf->travel_start;
  $trfTripCompleted = !$trfEndDate || !$trfEndDate->copy()->startOfDay()->isFuture();
  $activeOrApprovedReimb = $trf->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg', 'approved'])->sortByDesc('id')->first();
  $ratedVendorIds = $trf->evaluations->pluck('vendor_id');
  $unratedTravelVendor = $trf->flightLogs->pluck('vendor')->filter()->unique('id')->first(fn ($v) => !$ratedVendorIds->contains($v->id));

  $activityEntries = $trf->auditLogs
    ->reject(fn ($log) => $log->action === 'updated' && empty($log->detail))
    ->values();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('trf') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <span>&larr; Back to Travel Requisitions</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success mb-3">
      {{ session('status') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger mb-3">
      {{ $errors->first() }}
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card (Vendor Empanelment Style) -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">

    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="text-start">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $trf->trf_number }}</span>
            <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
              {{ $meta['label'] }}
            </span>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="detail-meta-chip">
              <i class="bi bi-person"></i>
              <span class="meta-label">Traveler:</span>
              <strong>{{ $trf->employee->name }}</strong>
            </span>

            @if ($trf->route)
              <span class="detail-meta-chip">
                <i class="bi bi-geo-alt"></i>
                <span class="meta-label">Route:</span>
                <strong>{{ $trf->route }}</strong>
              </span>
            @endif

            @if ($trf->department ?? $trf->employee->department)
              <span class="detail-meta-chip">
                <i class="bi bi-diagram-3"></i>
                <span class="meta-label">Dept:</span>
                <strong>{{ $trf->department ?? $trf->employee->department }}</strong>
              </span>
            @endif

            <span class="detail-meta-chip">
              <i class="bi bi-calendar-range"></i>
              <span class="meta-label">Dates:</span>
              <strong>
                @if ($trf->travel_start)
                  {{ $trf->travel_start->format('d M') }} &ndash; {{ optional($trf->travel_end)->format('d M Y') ?? $trf->travel_start->format('d M Y') }}
                @else
                  Date TBD
                @endif
              </strong>
            </span>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @if ($pendingApproval)
            <!-- On Hold Button -->
            <button type="button" class="btn btn-sm btn-warning px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap text-dark" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#holdTrfModal">
              <i class="bi bi-pause-circle"></i>
              <span>On Hold</span>
            </button>

            <!-- Approve Button Form -->
            <form method="POST" action="{{ route('trf.approve', $trf) }}" id="trfApproveForm" class="m-0">
              @csrf
              <input type="hidden" name="comments" value="Approved as per travel policy.">
              <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-check-lg"></i>
                <span>Endorse Travel Request</span>
              </button>
            </form>

            <!-- Reject Button -->
            <button type="button" class="btn btn-sm btn-outline-danger px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#rejectTrfModal">
              <i class="bi bi-x-lg"></i>
              <span>Reject</span>
            </button>
          @endif

          @if (!empty($canEdit) && $trf->status !== 'pushed_back')
            <a href="{{ route('trf.edit', $trf) }}" class="erp-btn-action-edit" title="Edit Travel Request">
              <i class="bi bi-pencil"></i>
              <span>Edit</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- On Hold Banner -->
    @if ($trf->status === 'on_hold')
      <div class="p-3 p-md-3.5 border-top w-100" style="background: #fffbeb; border-top: 1px solid #fde68a !important;">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
            <i class="bi bi-pause-circle-fill"></i>
          </div>
          <div>
            <div class="fw-bold text-dark fs-6 mb-0.5">This Travel Request is Currently On Hold</div>
            <div class="text-secondary small">
              Review is temporarily paused pending travel schedule confirmation or supporting justification.
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- Pushed Back Revision Banner -->
    @if ($trf->status === 'pushed_back')
      <div class="p-3 p-md-3.5 border-top w-100" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-top: 1px solid #fed7aa !important;">
        <div class="row align-items-center g-3">
          <div class="col-12 col-lg-8 text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="fw-bold" style="font-size: 14.5px; color: #9a3412;">Returned for Revision (Pushback)</span>
              @if (!empty($latestPushback))
                <span class="badge bg-white text-dark border border-warning-subtle px-2 py-0.5" style="font-size: 11px; font-weight: 600;">
                  By {{ $latestPushback->approver?->name ?? 'Approver' }} &middot; {{ optional($latestPushback->acted_at)->format('d M Y, H:i') }}
                </span>
              @endif
            </div>
            <div class="text-dark small" style="font-size: 13px; line-height: 1.45;">
              @if (!empty($latestPushback) && $latestPushback->comments)
                <strong>Review Comments:</strong> &ldquo;{{ $latestPushback->comments }}&rdquo;
              @else
                This travel requisition was pushed back for modifications rather than rejected.
              @endif
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="d-flex justify-content-lg-end">
              @if (!empty($canEdit))
                <a href="{{ route('trf.edit', $trf) }}" class="btn btn-primary px-3.5 py-2 fw-semibold shadow-sm text-nowrap" style="font-size: 13px;">
                  Edit &amp; Resubmit TRF
                </a>
              @else
                <span class="badge bg-white text-secondary border px-2.5 py-1.5" style="font-size: 11.5px;">Awaiting Requester Revision</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Travel Approval Timeline Card (Clean Step Numbers) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 1: Requisition Initiated -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Initiated">
              <span class="fw-bold" style="font-size: 11px;">1</span>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Requisition Initiated</div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Submitted
                </span>
              </div>
              <div class="h-step-person" title="{{ $trf->employee?->name ?? 'Requester' }}">
                {{ $trf->employee?->name ?? 'Requester' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $trf->created_at?->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Multi-Tier Executive Approvals (ASG / SG) -->
          @foreach ($approvals as $approval)
            @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';
              $isPushed = $approval->status === 'pushed_back';
              $isCurrentDue = (!$isTrfRejected && $isPending && $approval->level === $activePendingLevel);
              $roleShort = \App\Support\Roles::labelAny($approval->role_required);

              if ($isApproved) {
                $stepStatusClass = 'is-done';
                $iconClass = 'done';
                $cardClass = 'done-card';
              } elseif ($isRejected) {
                $stepStatusClass = 'is-declined';
                $iconClass = 'rejected';
                $cardClass = 'active-card border-danger-subtle';
              } elseif ($isPushed) {
                $stepStatusClass = 'is-active';
                $iconClass = 'pushed';
                $cardClass = 'active-card border-warning-subtle';
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
                <span class="fw-bold" style="font-size: 11px;">{{ $loop->iteration + 1 }}</span>
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isPushed ? 'bg-warning-subtle text-warning' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted'))) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
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
                  @elseif ($isPushed)
                    Pushed Back
                  @else
                    Pending
                  @endif
                </div>
                @if ($approval->comments)
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : ($isPushed ? 'quote-pushed' : '') }}"
                       data-bs-toggle="modal"
                       data-bs-target="#trfApprovalCommentModal-{{ $approval->id }}"
                       title="Click to view full comment">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- Flight & Logistics Booking -->
          @php
            $step3Num = $approvals->count() + 2;
            if ($flightCount > 0) {
              $step3Status = 'is-done';
              $step3Icon = 'done';
              $step3Card = 'done-card';
            } elseif ($isAllApproved || $trf->status === 'approved') {
              $step3Status = 'is-active';
              $step3Icon = 'active';
              $step3Card = 'active-card';
            } else {
              $step3Status = 'is-stopped';
              $step3Icon = 'upcoming';
              $step3Card = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $step3Status }}">
            <div class="h-step-icon {{ $step3Icon }}">
              <span class="fw-bold" style="font-size: 11px;">{{ $step3Num }}</span>
            </div>
            <div class="h-step-card {{ $step3Card }}">
              <div class="h-step-title {{ $flightCount > 0 ? 'text-success' : (($isAllApproved || $trf->status === 'approved') ? 'text-primary' : 'text-muted') }} mb-1" title="Flight &amp; Logistics Booking">
                Logistics Booking
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $flightCount > 0 ? 'bg-success-subtle text-success' : (($isAllApproved || $trf->status === 'approved') ? 'bg-info-subtle text-info' : 'bg-light text-muted') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $flightCount > 0 ? 'Ticketed' : (($isAllApproved || $trf->status === 'approved') ? 'Ready to Book' : 'Upcoming') }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($flightCount > 0)
                  <span>{{ $flightCount }} Booking(s) &middot; ₹{{ number_format($flightCost, 2) }}</span>
                @elseif ($isAllApproved || $trf->status === 'approved')
                  <span class="text-info fw-semibold">Admin Head</span>
                @else
                  <span class="text-muted">Admin Head</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $flightCount > 0 ? 'Confirmed' : (($isAllApproved || $trf->status === 'approved') ? 'Actionable' : '—') }}
              </div>
            </div>
          </div>

          <!-- Reimbursement & Settlement -->
          @php
            $step4Num = $approvals->count() + 3;
            if ($hasReimbApproved) {
              $step4Status = 'is-done';
              $step4Icon = 'done';
              $step4Card = 'done-card';
            } elseif ($reimb) {
              $step4Status = 'is-active';
              $step4Icon = 'active';
              $step4Card = 'active-card';
            } elseif ($travelEndPassed) {
              $step4Status = 'is-active';
              $step4Icon = 'pushed';
              $step4Card = 'active-card border-warning-subtle';
            } else {
              $step4Status = 'is-stopped';
              $step4Icon = 'upcoming';
              $step4Card = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $step4Status }}">
            <div class="h-step-icon {{ $step4Icon }}">
              <span class="fw-bold" style="font-size: 11px;">{{ $step4Num }}</span>
            </div>
            <div class="h-step-card {{ $step4Card }}">
              <div class="h-step-title {{ $hasReimbApproved ? 'text-success' : ($reimb ? 'text-primary' : ($travelEndPassed ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="Reimbursement &amp; Settlement">
                Settlement
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $hasReimbApproved ? 'bg-success-subtle text-success' : ($reimb ? 'bg-warning-subtle text-warning' : ($travelEndPassed ? 'bg-warning-subtle text-warning' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  @if ($hasReimbApproved) Reconciled
                  @elseif ($reimb) Under Audit
                  @elseif ($travelEndPassed) Due Now
                  @else Final Step
                  @endif
                </span>
              </div>
              <div class="h-step-person">
                @if ($hasReimbApproved)
                  <span>Finance Cleared</span>
                @elseif ($reimb)
                  <span class="text-warning fw-semibold">{{ $reimb->claim_number }}</span>
                @elseif ($travelEndPassed)
                  <span class="text-danger fw-semibold">Trip ended &middot; Claim due</span>
                @else
                  <span class="text-muted">Post-Travel</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($hasReimbApproved) Settled
                @elseif ($reimb) In Audit
                @elseif ($travelEndPassed) Claim Due
                @else &mdash;
                @endif
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Unified Travel Requisition Dossier (Matching Vendor Empanelment Dossier UI) -->
  <div class="row g-4 align-items-start">
    <div class="col-12">
      <div class="card shadow-sm border-0 p-4" style="border: 1px solid #e2e8f0 !important; border-radius: 10px; background: #ffffff;">
        <div class="pb-3 mb-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color: #e2e8f0 !important;">
          <h2 class="h5 mb-0 fw-bold text-dark" style="font-size: 19px; letter-spacing: -0.01em;">Travel Requisition Dossier</h2>
          <span class="badge bg-light text-secondary border px-2.5 py-1 fw-semibold" style="font-size: 11.5px; border-color: #e2e8f0 !important;">
            {{ $trf->is_international ? 'SG Approval Required' : 'ASG Tier' }}
          </span>
        </div>

        <!-- Itinerary & Logistics -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Itinerary &amp; Logistics</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Employee</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->employee?->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Department</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->department }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Travel Mode</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->travel_mode ?: 'Flight' }}</div>
            </div>

            <div class="col-md-8">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Travel Purpose</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->purpose ?: 'Official ASSOCHAM Travel' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Advance Requested</div>
              <div class="text-danger font-monospace fw-bold" style="font-size: 14.5px;">₹{{ number_format($trf->requested_advance, 2) }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Itinerary Route</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->route ?: '—' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Destinations</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->destinations ?: ($trf->route ?: '—') }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Travel Dates</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">
                {{ optional($trf->travel_start)->format('d M Y') ?? '—' }} to {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
              </div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Accommodation Needs</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->accommodation ? 'Required / Arranged' : 'Self-arranged' }}</div>
              @if ($trf->accommodation_details)
                <div class="text-muted small mt-0.5" style="font-size: 12px;">{{ $trf->accommodation_details }}</div>
              @endif
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Visa Requirement</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->visa_required ? 'Visa Required' : 'Not Required' }}</div>
              @if ($trf->visa_details)
                <div class="text-muted small mt-0.5" style="font-size: 12px;">{{ $trf->visa_details }}</div>
              @endif
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Travel Insurance</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $trf->insurance ? 'Yes (Requested)' : 'No' }}</div>
            </div>
          </div>

          @if (($trf->status === 'approved' && auth()->user()->role === \App\Support\Roles::ADMIN_HEAD) || ($trf->status === 'approved' && $activeOrApprovedReimb) || ($trf->status === 'approved' && $trfTripCompleted && auth()->id() === $trf->employee_id))
            <div class="mt-4 pt-3 border-top d-flex flex-wrap gap-2" style="border-color: #e2e8f0 !important;">
              @if ($trf->status === 'approved' && auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
                <a href="{{ route('flight.create', ['trf_id' => $trf->id]) }}" class="btn btn-sm btn-primary fw-semibold px-3.5 py-1.5 shadow-sm d-inline-flex align-items-center gap-1.5" style="border-radius: 6px; font-size: 13px;">
                  <i class="bi bi-airplane"></i>
                  <span>Log Flight &amp; Boarding Cost</span>
                </a>
                @if ($trfTripCompleted && $unratedTravelVendor)
                  <a href="{{ route('traveleval.create', ['trf_id' => $trf->id, 'vendor_id' => $unratedTravelVendor->id]) }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold px-3 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 13px;">
                    <i class="bi bi-star"></i>
                    <span>Rate Travel Vendor ({{ $unratedTravelVendor->company_name }})</span>
                  </a>
                @endif
              @endif
              @if ($trf->status === 'approved' && $activeOrApprovedReimb)
                <a href="{{ route('reimb.show', $activeOrApprovedReimb) }}" class="btn btn-sm btn-outline-secondary fw-semibold px-3.5 py-1.5 shadow-sm d-inline-flex align-items-center gap-1.5" style="border-radius: 6px; font-size: 13px;">
                  <i class="bi bi-receipt"></i>
                  <span>View Settlement ({{ $activeOrApprovedReimb->claim_number }})</span>
                </a>
              @elseif ($trf->status === 'approved' && $trfTripCompleted && auth()->id() === $trf->employee_id)
                <a href="{{ route('reimb.create', ['trf_id' => $trf->id]) }}" class="btn btn-sm btn-outline-primary fw-semibold px-3.5 py-1.5 shadow-sm d-inline-flex align-items-center gap-1.5" style="border-radius: 6px; font-size: 13px;">
                  <i class="bi bi-file-earmark-plus"></i>
                  <span>File Reimbursement Claim</span>
                </a>
              @endif
            </div>
          @endif
        </div>

        <!-- Boarding Passes & Travel Documents -->
        @if ($trf->status === 'approved' || $trf->boardingPasses->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Boarding Passes &amp; Travel Documents</h3>

              @if (!empty($canManageBoardingPasses) && $trf->status === 'approved' && $trf->boardingPasses->isNotEmpty())
                <form method="POST" action="{{ route('trf.boarding-passes.share', $trf) }}" class="m-0">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold" style="font-size: 12px; border-radius: 6px;">
                    Email All to Traveller
                  </button>
                </form>
              @endif
            </div>

            @if (!empty($canManageBoardingPasses) && $trf->status === 'approved')
              <form method="POST" action="{{ route('trf.boarding-passes.store', $trf) }}" enctype="multipart/form-data" class="p-3.5 rounded-2 mb-3 bg-white" style="border: 1px solid #e2e8f0;">
                @csrf
                <div class="row g-3 align-items-center">
                  <div class="col-12 col-md-6">
                    <label for="boardingPassesInput" class="form-label fw-semibold text-dark small mb-1.5">Upload Boarding Pass(es) (PDF / JPG / PNG, max 5MB)</label>
                    <input type="file" name="boarding_passes[]" id="boardingPassesInput" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple required style="border: 1.5px solid #e2e8f0;">
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="form-check form-switch mt-md-3 mb-0">
                      <input class="form-check-input" type="checkbox" id="shareWithTravellerCheck" name="share_with_traveller" value="1" {{ auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD ? 'checked' : '' }} style="cursor:pointer;">
                      <label class="form-check-label small fw-medium text-dark" for="shareWithTravellerCheck" style="cursor:pointer;">
                        Email pass to {{ $trf->employee?->name ?? 'traveller' }} on upload
                      </label>
                    </div>
                  </div>
                  <div class="col-12 col-md-2 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-md-3 fw-semibold py-1.5 shadow-sm" style="border-radius: 6px; font-size: 13px;">
                      Upload
                    </button>
                  </div>
                </div>
              </form>
            @endif

            @if ($trf->boardingPasses->isNotEmpty())
              <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
                <table class="table table-hover align-middle mb-0 w-100" style="font-size: 12.5px;">
                  <thead class="table-light">
                    <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc;">
                      <th class="ps-3">Boarding Pass File</th>
                      <th style="width: 170px;">Uploaded By</th>
                      <th style="width: 160px;">Stored On</th>
                      <th class="text-end pe-3" style="width: 240px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($trf->boardingPasses as $pass)
                      @php
                        $passExt = strtolower(pathinfo($pass->original_name ?: $pass->file_path, PATHINFO_EXTENSION));
                      @endphp
                      <tr>
                        <td class="ps-3 py-2.5">
                          <div class="fw-semibold text-dark">{{ $pass->original_name ?: basename($pass->file_path) }}</div>
                          <div class="text-muted" style="font-size: 11px;">Boarding Pass &middot; {{ strtoupper($passExt ?: 'FILE') }}</div>
                        </td>
                        <td class="text-secondary fw-medium py-2.5">{{ $pass->uploader?->name ?? 'System' }}</td>
                        <td class="text-muted text-nowrap py-2.5" style="font-size: 11.5px;">{{ optional($pass->created_at)->format('d M Y, H:i') }}</td>
                        <td class="text-end text-nowrap pe-3 py-2.5">
                          <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('trf.boarding-passes.show', [$trf, $pass]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;">
                              View
                            </a>
                            @if (!empty($canManageBoardingPasses) && $trf->status === 'approved')
                              <form method="POST" action="{{ route('trf.boarding-passes.share', $trf) }}" class="m-0 d-inline">
                                @csrf
                                <input type="hidden" name="attachment_id" value="{{ $pass->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;" title="Email to {{ $trf->employee?->email }}">
                                  Email
                                </button>
                              </form>
                              <form method="POST" action="{{ route('trf.boarding-passes.destroy', [$trf, $pass]) }}" class="m-0 d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;" title="Remove boarding pass">
                                  Remove
                                </button>
                              </form>
                            @endif
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-muted small py-1" style="font-size: 13px;">
                No boarding passes uploaded yet for {{ $trf->trf_number }}.
              </div>
            @endif
          </div>
        @endif

        <!-- Revision & Approval Activity -->
        @if ($activityEntries->isNotEmpty() || $trf->approvals->where('status', 'pushed_back')->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-4">
            <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Revision &amp; Approval Activity</h3>
            </div>

            <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
              <table class="table table-hover align-middle mb-0 w-100" style="font-size: 12.5px;">
                <thead class="table-light">
                  <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc;">
                    <th style="width: 130px;" class="ps-3">EVENT</th>
                    <th>ACTIVITY &amp; REMARKS</th>
                    <th style="width: 170px;">ACTIONED BY</th>
                    <th class="text-end pe-3" style="width: 160px;">TIMESTAMP</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($activityEntries as $log)
                    @php
                      $evMeta = match($log->action) {
                        'pushed_back'                => ['label' => 'Pushed Back', 'color' => 'warning'],
                        'resubmitted'                => ['label' => 'Resubmitted', 'color' => 'primary'],
                        'approved'                   => ['label' => 'Endorsed', 'color' => 'success'],
                        'created'                    => ['label' => 'Initiated', 'color' => 'info'],
                        'booking_email_sent'         => ['label' => 'Mail Sent', 'color' => 'success'],
                        'booking_email_failed'       => ['label' => 'Mail Failed', 'color' => 'danger'],
                        'boarding_pass_uploaded'     => ['label' => 'Pass Stored', 'color' => 'info'],
                        'boarding_pass_emailed'      => ['label' => 'Pass Mailed', 'color' => 'success'],
                        'boarding_pass_email_failed' => ['label' => 'Pass Mail Failed', 'color' => 'danger'],
                        'boarding_pass_removed'      => ['label' => 'Pass Removed', 'color' => 'secondary'],
                        'settlement_submitted'       => ['label' => 'Claim Filed', 'color' => 'info'],
                        'settlement_approved'        => ['label' => 'Settled', 'color' => 'success'],
                        'settlement_rejected'        => ['label' => 'Claim Declined', 'color' => 'danger'],
                        'vendor_evaluated'           => ['label' => 'Vendor Rated', 'color' => 'warning'],
                        default                      => ['label' => ucfirst(str_replace('_', ' ', $log->action)), 'color' => 'secondary'],
                      };
                      $fallbackDetail = match($log->action) {
                        'created'     => "Travel requisition {$trf->trf_number} submitted for " . ($trf->is_international ? 'SG' : 'ASG') . " approval.",
                        'approved'    => "Travel requisition {$trf->trf_number} endorsed.",
                        'pushed_back' => "Returned to requester for revision.",
                        'resubmitted' => "Revised and resubmitted for approval.",
                        default       => "Requisition record modified.",
                      };
                    @endphp
                    <tr>
                      <td class="ps-3 py-2.5">
                        <span class="badge bg-{{ $evMeta['color'] }}-subtle text-{{ $evMeta['color'] === 'warning' ? 'warning-emphasis' : $evMeta['color'] }} border border-{{ $evMeta['color'] }}-subtle px-2.5 py-1" style="font-size: 11px; font-weight: 600; border-radius: 4px;">
                          {{ $evMeta['label'] }}
                        </span>
                      </td>
                      <td class="text-dark py-2.5">{{ $log->detail ?: $fallbackDetail }}</td>
                      <td class="text-secondary fw-medium py-2.5">{{ $log->user?->name ?? 'System' }}</td>
                      <td class="text-end text-muted text-nowrap pe-3 py-2.5" style="font-size: 11.5px;">{{ optional($log->created_at)->format('d M Y, H:i') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

        <!-- Booking Confirmation Mail Transactions -->
        @if ($trf->mailTransactions->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-0">
            <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Booking Confirmation Mail Transactions</h3>
            </div>

            <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
              <table class="table table-hover align-middle mb-0 w-100" style="font-size: 12.5px;">
                <thead class="table-light">
                  <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc;">
                    <th style="width: 130px;" class="ps-3">Booking Ref</th>
                    <th>Subject &amp; Recipient</th>
                    <th style="width: 110px;">Delivery</th>
                    <th style="width: 160px;">Dispatched On</th>
                    <th class="text-end pe-3" style="width: 110px;">Archived Mail</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($trf->mailTransactions as $mailTx)
                    <tr>
                      <td class="text-nowrap ps-3 py-2.5">
                        @if ($mailTx->flightLog)
                          @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'flight'))
                            <a href="{{ route('flight.show', $mailTx->flightLog) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                              {{ $mailTx->flightLog->ref }}
                            </a>
                          @else
                            <span class="fw-bold font-monospace text-dark">{{ $mailTx->flightLog->ref }}</span>
                          @endif
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="py-2.5">
                        <div class="fw-semibold text-dark">{{ $mailTx->subject ?: 'Booking Confirmation' }}</div>
                        <div class="text-muted" style="font-size: 11px;">To: {{ $mailTx->recipient_email }}</div>
                      </td>
                      <td class="py-2.5">
                        <span class="badge {{ $mailTx->status === 'sent' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' }} border px-2 py-0.5" style="font-size: 10.5px; font-weight: 600; border-radius: 4px;">
                          {{ $mailTx->status === 'sent' ? 'Sent' : 'Failed' }}
                        </span>
                      </td>
                      <td class="text-muted text-nowrap py-2.5" style="font-size: 11.5px;">
                        {{ optional($mailTx->sent_at)->format('d M Y, H:i') }}
                      </td>
                      <td class="text-end text-nowrap pe-3 py-2.5">
                        @if ($mailTx->body_html)
                          <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 fw-semibold" style="font-size: 11.5px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#mailTxModal{{ $mailTx->id }}">
                            View
                          </button>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>

  @if ($trf->mailTransactions->isNotEmpty())
    @foreach ($trf->mailTransactions as $mailTx)
      <div class="modal fade" id="mailTxModal{{ $mailTx->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
              <div>
                <h6 class="modal-title fw-bold text-dark mb-0">{{ $mailTx->subject }}</h6>
                <div class="text-muted small" style="font-size: 11.5px;">
                  To: {{ $mailTx->recipient_email }} &bull; {{ optional($mailTx->sent_at)->format('d M Y, H:i') }}
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
              @if ($mailTx->body_html)
                <iframe srcdoc="{{ $mailTx->body_html }}" style="width:100%; height:420px; border:1px solid #e2e8f0; border-radius:8px; background:#fff;" title="Archived Booking Email"></iframe>
              @else
                <div class="p-3 bg-white rounded border text-muted small">Email body archive unavailable.</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  @endif

  @if ($pendingApproval)
    <!-- Place TRF On Hold Modal -->
    <div class="modal fade" id="holdTrfModal" tabindex="-1" aria-labelledby="holdTrfModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('trf.hold', $trf) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdTrfModalLabel">Place Travel Request On Hold</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Placing <strong>{{ $trf->trf_number }}</strong> on hold temporarily pauses review at this stage until dates, budget, or travel requirements are clarified.
            </p>
            <div class="mb-1">
              <label for="holdTrfCommentsInput" class="form-label fw-semibold text-dark small">Reason for Placing On Hold <span class="text-danger">*</span></label>
              <textarea name="comments" id="holdTrfCommentsInput" class="form-control" rows="3" placeholder="Provide reason for hold (e.g. Awaiting event schedule confirmation, budget check pending)..." required></textarea>
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

    <!-- Reject TRF Modal -->
    <div class="modal fade" id="rejectTrfModal" tabindex="-1" aria-labelledby="rejectTrfModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('trf.reject', $trf) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <div class="d-flex align-items-center gap-2 text-danger">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="rejectTrfModalLabel">Reject Travel Request</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific reason for rejecting <strong>{{ $trf->trf_number }}</strong>. This feedback will be recorded in the audit trail.
            </p>
            <div class="mb-1">
              <label for="rejectTrfCommentsInput" class="form-label fw-semibold text-dark small">Reason for Rejection <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectTrfCommentsInput" class="form-control" rows="3" placeholder="e.g. Travel not authorized, event cancelled, advance policy non-compliant..." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-x-circle"></i>
              <span>Confirm Rejection</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Push Back TRF Modal -->
    <div class="modal fade" id="pushbackTrfModal" tabindex="-1" aria-labelledby="pushbackTrfModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('trf.pushback', $trf) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <h5 class="modal-title fw-bold text-warning-emphasis fs-6 mb-0" id="pushbackTrfModalLabel">Push Back Travel Request</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific review comments for pushing back <strong>{{ $trf->trf_number }}</strong>. This is not a final rejection — the request will return to the traveler to edit and resubmit.
            </p>
            <div class="mb-1">
              <label for="pushbackCommentsInput" class="form-label fw-semibold text-dark small">Review Comments <span class="text-danger">*</span></label>
              <textarea name="comments" id="pushbackCommentsInput" class="form-control" rows="3" placeholder="e.g. Requested advance exceeds entitlement, travel dates conflict with session, etc." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning text-dark btn-sm px-3 fw-semibold shadow-sm">
              Confirm Push Back
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- TRF Approval Comments Modals --}}
  @foreach ($trf->approvals as $approval)
    @if ($approval->comments)
      @php
        $stageTitle = $approval->role_name ?? ('Approver (Tier ' . $approval->level . ')');
        $isAppr = $approval->status === 'approved';
        $isRej = $approval->status === 'rejected';
        $isPush = $approval->status === 'pushed_back';
      @endphp
      <div class="modal fade" id="trfApprovalCommentModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
              <h6 class="modal-title fw-bold text-dark mb-0">Approval Remarks — {{ $approval->approver?->name ?? 'Approver' }}</h6>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
              <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom text-muted small">
                <div><span class="text-secondary">Stage:</span> <strong class="text-dark">{{ $stageTitle }}</strong></div>
                <div>
                  <span class="badge {{ $isAppr ? 'bg-success-subtle text-success' : ($isRej ? 'bg-danger-subtle text-danger' : ($isPush ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border">
                    {{ ucfirst(str_replace('_', ' ', $approval->status)) }}
                  </span>
                </div>
              </div>
              <div class="p-3.5 rounded-3 bg-white border text-dark shadow-2xs" style="font-size: 14px; font-weight: 500; line-height: 1.6; border-left: 4px solid {{ $isAppr ? 'var(--bs-success)' : ($isRej ? 'var(--bs-danger)' : ($isPush ? 'var(--bs-warning)' : '#6c757d')) }} !important;">
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
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'TRF Detail' });
@endsection
