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
  $stepMeta = [
    'pending'     => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'pushed_back' => ['label' => 'Pushed Back', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('trf') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Requisitions</span>
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
    
    <!-- Top Row: TRF Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(2,132,199,0.15),rgba(2,132,199,0.05)); color:#0284c7; border:1px solid rgba(2,132,199,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-airplane fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $trf->trf_number }} — {{ $trf->employee->name }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              @if ($trf->is_international)
                <span class="badge" style="background:#fef3c7; color:#d97706; font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  <i class="bi bi-globe2 me-1"></i> International (SG Approval)
                </span>
              @endif
              @if ($trf->isSettlementOverdue())
                <span class="badge" style="background:#FEE2E2; color:#B91C1C; font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  <i class="bi bi-exclamation-octagon me-1"></i> Overdue Advance ({{ $trf->overdueDays() }}d)
                </span>
              @endif
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span><i class="bi bi-geo-alt text-secondary me-1"></i> {{ $trf->route ?: 'Route not specified' }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-person text-secondary me-1"></i> {{ $trf->booking_type === 'on_behalf' ? 'Booked on behalf' : 'Self travel' }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-wallet2 text-success me-1"></i> Requested Advance: ₹{{ number_format($trf->requested_advance, 2) }}</span>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600;">
            <i class="bi bi-calendar-range me-1"></i>
            @if ($trf->travel_start)
              {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? $trf->travel_start->format('d M Y') }}
            @else
              Date TBD
            @endif
          </span>
          @if (!empty($canEdit) && $trf->status !== 'pushed_back')
            <a href="{{ route('trf.edit', $trf) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 fw-semibold" style="font-size: 12px;">
              <i class="bi bi-pencil-square"></i>
              <span>Edit</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- Pushed Back Revision Banner (When returned to requester for corrections) -->
    @if ($trf->status === 'pushed_back')
      <div class="p-3 p-md-3.5 border-top w-100" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-top: 1px solid #fed7aa !important;">
        <div class="row align-items-center g-3">
          <div class="col-12 col-lg-8">
            <div class="d-flex align-items-start" style="gap: 14px;">
              <div class="flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px; min-width: 42px; border-radius: 12px; background: #ffedd5; color: #c2410c; border: 1px solid #fdba74;">
                <i class="bi bi-arrow-return-left fs-5"></i>
              </div>
              <div class="flex-grow-1 text-start">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="fw-bold" style="font-size: 14.5px; color: #9a3412;">Returned for Revision (Pushback)</span>
                  @if (!empty($latestPushback))
                    <span class="badge bg-white text-dark border border-warning-subtle px-2 py-0.5" style="font-size: 11px; font-weight: 600;">
                      By {{ $latestPushback->approver?->name ?? 'Approver' }} · {{ optional($latestPushback->acted_at)->format('d M Y, H:i') }}
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
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="d-flex justify-content-lg-end">
              @if (!empty($canEdit))
                <a href="{{ route('trf.edit', $trf) }}" class="btn btn-primary px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-pencil-square"></i>
                  <span>Edit &amp; Resubmit TRF</span>
                </a>
              @else
                <span class="badge bg-white text-secondary border px-2.5 py-1.5" style="font-size: 11.5px;">Awaiting Requester Revision</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval)
      @php
        $pendingTrfStage = \App\Support\Roles::labelAny($pendingApproval->role_required);
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
                    Stage: {{ $pendingTrfStage }}
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Review travel route, logistics requirements, and advance amount (₹{{ number_format($trf->requested_advance, 2) }}) before endorsing.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (No Inline Input) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <!-- Endorse Form -->
              <form method="POST" action="{{ route('trf.approve', $trf) }}" id="trfApproveForm" class="m-0">
                @csrf
                <input type="hidden" name="comments" value="Approved as per travel policy.">
                <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-shield-check fs-6"></i>
                  <span>Endorse Travel Request</span>
                </button>
              </form>

              <!-- Push Back / Decline Button (Triggers Clean Modal) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#pushbackTrfModal">
                <i class="bi bi-arrow-return-left fs-6"></i>
                <span>Push Back</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Travel Approval Timeline Card (On Top) -->
  @php
    $approvals = $trf->approvals->sortByDesc('id')->unique('level')->sortBy('level');
    $approvedApprovalsCount = $approvals->where('status', 'approved')->count();
    $totalApprovalsCount = $approvals->count();
    $isAllApproved = $totalApprovalsCount > 0 && $approvedApprovalsCount === $totalApprovalsCount;
    $isTrfRejected = $approvals->where('status', 'rejected')->isNotEmpty();
    $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

    $hasFlights = $trf->flightLogs && $trf->flightLogs->isNotEmpty();
    $flightCount = $trf->flightLogs ? $trf->flightLogs->count() : 0;
    $flightCost = $trf->flightLogs ? $trf->flightLogs->sum('cost') : 0;

    $reimb = $trf->reimbursements ? $trf->reimbursements->first() : null;
    $hasReimbApproved = $reimb && $reimb->status === 'approved';
    $travelEndPassed = $trf->travel_end && $trf->travel_end->isPast();
  @endphp
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Travel Approval Timeline</h6>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Requisition Initiated -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Initiated">
              <i class="bi bi-check-lg"></i>
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
                @if ($isApproved)
                  <i class="bi bi-check-lg"></i>
                @elseif ($isRejected)
                  <i class="bi bi-x-lg"></i>
                @elseif ($isPushed)
                  <i class="bi bi-arrow-counterclockwise"></i>
                @elseif ($isCurrentDue)
                  <i class="bi bi-hourglass-split"></i>
                @else
                  <span class="text-secondary fw-bold" style="font-size: 11.5px;">{{ $approval->level }}</span>
                @endif
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="h-step-title {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : ($isCurrentDue ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="{{ $roleShort }}">
                  L{{ $approval->level }}: {{ $roleShort }}
                </div>
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isPushed ? 'bg-warning-subtle text-warning' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted'))) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    @if ($isApproved) Endorsed
                    @elseif ($isRejected) Declined
                    @elseif ($isPushed) Pushed Back
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
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : ($isPushed ? 'quote-pushed' : '') }}" title="{{ $approval->comments }}">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- Step 3: Flight & Logistics Booking -->
          @php
            if ($flightCount > 0) {
              $step3Status = 'is-done';
              $step3Icon = 'done';
              $step3Card = 'done-card';
            } elseif ($isAllApproved) {
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
              <i class="bi {{ $flightCount > 0 ? 'bi-check-lg' : 'bi-airplane' }}"></i>
            </div>
            <div class="h-step-card {{ $step3Card }}">
              <div class="h-step-title {{ $flightCount > 0 ? 'text-success' : ($isAllApproved ? 'text-primary' : 'text-muted') }} mb-1" title="Flight &amp; Logistics Booking">
                Logistics Booking
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $flightCount > 0 ? 'bg-success-subtle text-success' : ($isAllApproved ? 'bg-info-subtle text-info' : 'bg-light text-muted') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $flightCount > 0 ? 'Ticketed' : ($isAllApproved ? 'Ready to Book' : 'Upcoming') }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($flightCount > 0)
                  <span>{{ $flightCount }} Booking(s) · ₹{{ number_format($flightCost, 2) }}</span>
                @elseif ($isAllApproved)
                  <span class="text-info fw-semibold">Admin Head</span>
                @else
                  <span class="text-muted">Admin Head</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $flightCount > 0 ? 'Confirmed' : ($isAllApproved ? 'Actionable' : '—') }}
              </div>
            </div>
          </div>

          <!-- Step 4: Reimbursement & Settlement -->
          @php
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
              <i class="bi {{ $hasReimbApproved ? 'bi-check-all' : ($reimb ? 'bi-clock-history' : ($travelEndPassed ? 'bi-exclamation-circle' : 'bi-wallet2')) }}"></i>
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
                  <span class="text-danger fw-semibold">Trip ended · Claim due</span>
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

  <div class="row g-4 align-items-start">
    <!-- Trip Details (Full Width col-12) -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-compass text-primary"></i>
            <span>Itinerary &amp; Logistics</span>
          </div>
          <span class="badge bg-light text-secondary border" style="font-weight:600; font-size:12px; padding:5px 10px;">
            {{ $trf->is_international ? 'SG Approval Required' : 'ASG Tier' }}
          </span>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-card-text text-secondary"></i> Travel Purpose</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $trf->purpose ?: 'Official ASSOCHAM Travel' }}</div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-signpost-2 text-secondary"></i> Travel Mode</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $trf->travel_mode ?: 'Flight' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-geo-alt text-secondary"></i> Itinerary Route</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">{{ $trf->route ?: '—' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-pin-map text-secondary"></i> Destinations</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">{{ $trf->destinations ?: ($trf->route ?: '—') }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-calendar-event text-secondary"></i> Travel Dates</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ optional($trf->travel_start)->format('d M Y') ?? '—' }} to {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-cash-stack text-secondary"></i> Advance Requested</div>
                <div class="field-value text-danger" style="font-size:15px; font-weight:600;">
                  ₹{{ number_format($trf->requested_advance, 2) }}
                </div>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-building text-secondary"></i> Accommodation Needs</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $trf->accommodation ? 'Required / Arranged' : 'Self-arranged' }}
                </div>
                @if ($trf->accommodation_details)
                  <div class="text-muted small mt-1">{{ $trf->accommodation_details }}</div>
                @endif
              </div>
            </div>

            <div class="col-sm-4">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-passport text-secondary"></i> Visa Requirement</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $trf->visa_required ? 'Visa Required' : 'Not Required' }}
                </div>
                @if ($trf->visa_details)
                  <div class="text-muted small mt-1">{{ $trf->visa_details }}</div>
                @endif
              </div>
            </div>

            <div class="col-sm-4">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-shield text-secondary"></i> Travel Insurance</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">{{ $trf->insurance ? 'Yes (Requested)' : 'No' }}</div>
              </div>
            </div>
          </div>

          <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
            <span>Employee: {{ $trf->employee?->name ?? '—' }}</span>
            <span>Department: {{ $trf->department }}</span>
          </div>

          @php
            $trfEndDate = $trf->travel_end ?? $trf->travel_start;
            $trfTripCompleted = !$trfEndDate || !$trfEndDate->copy()->startOfDay()->isFuture();
            $activeOrApprovedReimb = $trf->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg', 'approved'])->sortByDesc('id')->first();
            $ratedVendorIds = $trf->evaluations->pluck('vendor_id');
            $unratedTravelVendor = $trf->flightLogs->pluck('vendor')->filter()->unique('id')->first(fn ($v) => !$ratedVendorIds->contains($v->id));
          @endphp
          <div class="mt-3 d-flex flex-wrap gap-2">
            @if ($trf->status === 'approved' && auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
              <a href="{{ route('flight.create', ['trf_id' => $trf->id]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-airplane-engines"></i>
                <span>Log Flight &amp; Boarding Cost</span>
              </a>
              @if ($trfTripCompleted && $unratedTravelVendor)
                <a href="{{ route('traveleval.create', ['trf_id' => $trf->id, 'vendor_id' => $unratedTravelVendor->id]) }}" class="btn btn-sm btn-outline-warning text-dark d-inline-flex align-items-center gap-2">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>Rate Travel Vendor ({{ $unratedTravelVendor->company_name }})</span>
                </a>
              @endif
            @endif
            @if ($trf->status === 'approved' && $activeOrApprovedReimb)
              <a href="{{ route('reimb.show', $activeOrApprovedReimb) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-receipt"></i>
                <span>View Settlement ({{ $activeOrApprovedReimb->claim_number }})</span>
              </a>
            @elseif ($trf->status === 'approved' && $trfTripCompleted && auth()->id() === $trf->employee_id)
              <a href="{{ route('reimb.create', ['trf_id' => $trf->id]) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-receipt"></i>
                <span>File Reimbursement Claim</span>
              </a>
            @endif
          </div>


        </div>
      </div>
    </div>

    @if ($trf->status === 'approved' || $trf->boardingPasses->isNotEmpty())
      <!-- Boarding Passes & Travel Documents Stored on TRF -->
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-ticket-perforated text-primary fs-5"></i>
              <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">Boarding Passes &amp; Travel Documents</h6>
              <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px; font-weight: 600;">
                {{ $trf->boardingPasses->count() }} {{ Str::plural('Pass', $trf->boardingPasses->count()) }}
              </span>
            </div>

            @if (!empty($canManageBoardingPasses) && $trf->status === 'approved' && $trf->boardingPasses->isNotEmpty())
              <form method="POST" action="{{ route('trf.boarding-passes.share', $trf) }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-3 py-1 fw-semibold" style="font-size: 12px;">
                  <i class="bi bi-envelope-arrow-up"></i>
                  <span>Email All to Traveller</span>
                </button>
              </form>
            @endif
          </div>

          <div class="card-body p-4">
            @if (!empty($canManageBoardingPasses) && $trf->status === 'approved')
              <form method="POST" action="{{ route('trf.boarding-passes.store', $trf) }}" enctype="multipart/form-data" class="p-3 rounded-3 border bg-light mb-3">
                @csrf
                <div class="row g-3 align-items-center">
                  <div class="col-12 col-md-6">
                    <label for="boardingPassesInput" class="form-label fw-semibold text-dark small mb-1">Upload Boarding Pass(es) (PDF / JPG / PNG, max 5MB)</label>
                    <input type="file" name="boarding_passes[]" id="boardingPassesInput" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple required>
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
                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-md-3 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 py-1">
                      <i class="bi bi-cloud-arrow-up"></i>
                      <span>Upload</span>
                    </button>
                  </div>
                </div>
              </form>
            @endif

            @if ($trf->boardingPasses->isNotEmpty())
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                  <thead class="table-light">
                    <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b;">
                      <th class="ps-3">Boarding Pass File</th>
                      <th style="width: 170px;">Uploaded By</th>
                      <th style="width: 160px;">Stored On</th>
                      <th class="pe-3 text-end" style="width: 240px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($trf->boardingPasses as $pass)
                      @php
                        $passExt = strtolower(pathinfo($pass->original_name ?: $pass->file_path, PATHINFO_EXTENSION));
                        $isPassImg = in_array($passExt, ['jpg', 'jpeg', 'png'], true);
                      @endphp
                      <tr>
                        <td class="ps-3">
                          <div class="d-flex align-items-center gap-2">
                            <i class="bi {{ $isPassImg ? 'bi-file-earmark-image text-primary' : 'bi-file-earmark-pdf text-danger' }} fs-5"></i>
                            <div>
                              <div class="fw-semibold text-dark">{{ $pass->original_name ?: basename($pass->file_path) }}</div>
                              <div class="text-muted" style="font-size: 11px;">{{ strtoupper($passExt ?: 'FILE') }}</div>
                            </div>
                          </div>
                        </td>
                        <td class="text-secondary fw-medium">{{ $pass->uploader?->name ?? 'System' }}</td>
                        <td class="text-muted text-nowrap" style="font-size: 11.5px;">{{ optional($pass->created_at)->format('d M Y, H:i') }}</td>
                        <td class="pe-3 text-end text-nowrap">
                          <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('trf.boarding-passes.show', [$trf, $pass]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size: 11.5px;">
                              View
                            </a>
                            @if (!empty($canManageBoardingPasses) && $trf->status === 'approved')
                              <form method="POST" action="{{ route('trf.boarding-passes.share', $trf) }}" class="m-0 d-inline">
                                @csrf
                                <input type="hidden" name="attachment_id" value="{{ $pass->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary px-2 py-1" style="font-size: 11.5px;" title="Email to {{ $trf->employee?->email }}">
                                  <i class="bi bi-send me-1"></i>Email
                                </button>
                              </form>
                              <form method="POST" action="{{ route('trf.boarding-passes.destroy', [$trf, $pass]) }}" class="m-0 d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" style="font-size: 11.5px;" title="Remove boarding pass">
                                  <i class="bi bi-trash"></i>
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
              <div class="p-3 rounded-3 text-center border bg-light text-muted small" style="border-style: dashed !important;">
                No boarding passes uploaded yet for {{ $trf->trf_number }}.
              </div>
            @endif
          </div>
        </div>
      </div>
    @endif

    @php
      $activityEntries = $trf->auditLogs
        ->reject(fn ($log) => $log->action === 'updated' && empty($log->detail))
        ->values();
    @endphp
    @if ($activityEntries->isNotEmpty() || $trf->approvals->where('status', 'pushed_back')->isNotEmpty())
      <!-- Revision & Approval Activity Table (Clean & Minimal) -->
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-2.5 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-clock-history text-primary fs-5"></i>
              <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">Revision &amp; Approval Activity</h6>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
              {{ $activityEntries->count() }} {{ Str::plural('Entry', $activityEntries->count()) }}
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
              <thead class="table-light">
                <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b;">
                  <th class="ps-4" style="width: 155px;">Event</th>
                  <th>Activity &amp; Remarks</th>
                  <th style="width: 190px;">Actioned By</th>
                  <th class="pe-4 text-end" style="width: 165px;">Timestamp</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($activityEntries as $log)
                  @php
                    $evMeta = match($log->action) {
                      'pushed_back'                => ['label' => 'Pushed Back', 'color' => 'warning', 'icon' => 'bi-arrow-return-left'],
                      'resubmitted'                => ['label' => 'Resubmitted', 'color' => 'primary', 'icon' => 'bi-arrow-repeat'],
                      'approved'                   => ['label' => 'Endorsed', 'color' => 'success', 'icon' => 'bi-check2-circle'],
                      'created'                    => ['label' => 'Initiated', 'color' => 'info', 'icon' => 'bi-file-earmark-plus'],
                      'booking_email_sent'         => ['label' => 'Mail Sent', 'color' => 'success', 'icon' => 'bi-envelope-check'],
                      'booking_email_failed'       => ['label' => 'Mail Failed', 'color' => 'danger', 'icon' => 'bi-envelope-exclamation'],
                      'boarding_pass_uploaded'     => ['label' => 'Pass Stored', 'color' => 'info', 'icon' => 'bi-ticket-perforated'],
                      'boarding_pass_emailed'      => ['label' => 'Pass Mailed', 'color' => 'success', 'icon' => 'bi-envelope-check'],
                      'boarding_pass_email_failed' => ['label' => 'Pass Mail Failed', 'color' => 'danger', 'icon' => 'bi-envelope-exclamation'],
                      'boarding_pass_removed'      => ['label' => 'Pass Removed', 'color' => 'secondary', 'icon' => 'bi-trash'],
                      'settlement_submitted'       => ['label' => 'Claim Filed', 'color' => 'info', 'icon' => 'bi-receipt'],
                      'settlement_approved'        => ['label' => 'Settled', 'color' => 'success', 'icon' => 'bi-check-all'],
                      'settlement_rejected'        => ['label' => 'Claim Declined', 'color' => 'danger', 'icon' => 'bi-x-circle'],
                      'vendor_evaluated'           => ['label' => 'Vendor Rated', 'color' => 'warning', 'icon' => 'bi-star-fill'],
                      default                      => ['label' => ucfirst(str_replace('_', ' ', $log->action)), 'color' => 'secondary', 'icon' => 'bi-dot'],
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
                    <td class="ps-4">
                      <span class="badge bg-{{ $evMeta['color'] }}-subtle text-{{ $evMeta['color'] === 'warning' ? 'warning-emphasis' : $evMeta['color'] }} border border-{{ $evMeta['color'] }}-subtle px-2 py-1" style="font-size: 11px; font-weight: 600;">
                        <i class="bi {{ $evMeta['icon'] }} me-1"></i>{{ $evMeta['label'] }}
                      </span>
                    </td>
                    <td class="text-dark">
                      {{ $log->detail ?: $fallbackDetail }}
                    </td>
                    <td class="text-secondary fw-medium">
                      {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td class="pe-4 text-end text-muted text-nowrap" style="font-size: 11.5px;">
                      {{ optional($log->created_at)->format('d M Y, H:i') }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    @if ($trf->mailTransactions->isNotEmpty())
      <!-- Booking Confirmation Mail Transactions Stored Against TRF -->
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-2.5 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-envelope-check text-primary fs-5"></i>
              <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">Booking Confirmation Mail Transactions</h6>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
              {{ $trf->mailTransactions->count() }} {{ Str::plural('Email', $trf->mailTransactions->count()) }}
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
              <thead class="table-light">
                <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b;">
                  <th class="ps-4" style="width: 120px;">Booking Ref</th>
                  <th>Subject &amp; Recipient</th>
                  <th style="width: 120px;">Delivery</th>
                  <th style="width: 165px;">Dispatched On</th>
                  <th class="pe-4 text-end" style="width: 110px;">Archived Mail</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($trf->mailTransactions as $mailTx)
                  <tr>
                    <td class="ps-4 text-nowrap">
                      @if ($mailTx->flightLog)
                        <a href="{{ route('flight.show', $mailTx->flightLog) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                          {{ $mailTx->flightLog->ref }}
                        </a>
                      @elseif ($mailTx->mail_type === 'boarding_pass')
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 10.5px;">Boarding Pass</span>
                      @else
                        —
                      @endif
                    </td>
                    <td>
                      <div class="fw-semibold text-dark">{{ $mailTx->subject }}</div>
                      <div class="text-muted small">To: {{ $mailTx->recipient_name ? "{$mailTx->recipient_name} <{$mailTx->recipient_email}>" : $mailTx->recipient_email }}</div>
                    </td>
                    <td>
                      @if ($mailTx->status === 'sent')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                          <i class="bi bi-check2 me-1"></i>Sent
                        </span>
                      @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;" title="{{ $mailTx->error_message }}">
                          <i class="bi bi-exclamation-triangle me-1"></i>Failed
                        </span>
                      @endif
                    </td>
                    <td class="text-muted text-nowrap" style="font-size: 11.5px;">
                      {{ optional($mailTx->sent_at)->format('d M Y, H:i') }}
                    </td>
                    <td class="pe-4 text-end">
                      <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1" style="font-size: 11.5px;" data-bs-toggle="modal" data-bs-target="#mailTxModal{{ $mailTx->id }}">
                        View Mail
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

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
  </div>

  @if ($pendingApproval)
    <!-- Push Back TRF Modal (Clean & Minimal Window) -->
    <div class="modal fade" id="pushbackTrfModal" tabindex="-1" aria-labelledby="pushbackTrfModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('trf.pushback', $trf) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-arrow-return-left fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="pushbackTrfModalLabel">Push Back Travel Request</h5>
            </div>
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
            <button type="submit" class="btn btn-warning text-dark btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-arrow-return-left"></i>
              <span>Confirm Push Back</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Procurement', title: 'TRF Detail' });
@endsection
