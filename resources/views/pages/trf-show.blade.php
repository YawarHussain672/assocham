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

  <!-- Header Card -->
  <div class="detail-header-card">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#0284c7,#38bdf8);">
        <i class="bi bi-airplane"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $trf->trf_number }} — {{ $trf->employee->name }}</div>
          @if ($trf->is_international)
            <span class="badge" style="background:#fef3c7; color:#d97706; font-size:11.5px; font-weight:600; padding:3px 7px;">
              <i class="bi bi-globe2"></i> International
            </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
          <span><i class="bi bi-geo-alt"></i> {{ $trf->route ?: 'Route not specified' }}</span>
          <span><i class="bi bi-person"></i> {{ $trf->booking_type === 'on_behalf' ? 'Booked on behalf' : 'Self travel' }}</span>
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
    <!-- Left Column: Trip Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-compass" style="color:var(--maroon);"></i>
              <span>Itinerary &amp; Logistics</span>
            </div>
            <span class="badge" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:12px; padding:5px 10px;">
              {{ $trf->is_international ? 'SG Approval Required' : 'ASG Tier' }}
            </span>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-card-text text-secondary"></i> Travel Purpose</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $trf->purpose ?: 'Official ASSOCHAM Travel' }}</div>
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
                <div class="field-value" style="font-size:15px; font-weight:600; color:#c8102e;">
                  ₹{{ number_format($trf->requested_advance, 2) }}
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-shield text-secondary"></i> Travel Insurance</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">{{ $trf->insurance ? 'Yes (Requested)' : 'No' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-building text-secondary"></i> Hotel Accommodation</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">{{ $trf->hotel_req ? 'Required / Arranged' : 'Self-arranged' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Employee: {{ $trf->employee?->name ?? '—' }}</span>
          <span>Department: {{ $trf->department }}</span>
        </div>

        <div class="mt-3 d-flex flex-column gap-2">
          @if ($trf->status === 'approved' && auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
            <a href="{{ route('flight.create') }}" class="btn-brand w-100 justify-content-center d-inline-flex align-items-center gap-2" style="font-weight:600; height:38px; text-decoration:none;">
              <i class="bi bi-airplane-engines"></i>
              <span>Log Flight &amp; Boarding Cost</span>
            </a>
          @endif
          @if ($trf->status === 'approved' && auth()->id() === $trf->employee_id)
            <a href="{{ route('reimb.create') }}" class="btn btn-outline-secondary w-100 justify-content-center d-inline-flex align-items-center gap-2" style="font-weight:600; height:38px; text-decoration:none;">
              <i class="bi bi-receipt"></i>
              <span>File Reimbursement Claim</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Enhanced Travel Approval Timeline & Lifecycle (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-2 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #0284c7, #2563eb); color:#fff; box-shadow:0 3px 8px rgba(37,99,235,0.25);">
                <i class="bi bi-diagram-3-fill fs-6"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark">Travel Approval Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">End-to-End Travel Governance &amp; Clearance</div>
              </div>
            </div>
            @php
              $approvedApprovalsCount = $trf->approvals->where('status', 'approved')->count();
              $totalApprovalsCount = $trf->approvals->count();
              $isAllApproved = $totalApprovalsCount > 0 && $approvedApprovalsCount === $totalApprovalsCount;
            @endphp
            <span class="badge {{ $isAllApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($trf->status === 'pushed_back' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isAllApproved)
                <i class="bi bi-check-circle-fill me-1"></i>All Clear ({{ $approvedApprovalsCount }}/{{ $totalApprovalsCount }})
              @elseif ($trf->status === 'pushed_back')
                <i class="bi bi-arrow-return-left me-1"></i>Pushed Back
              @else
                <i class="bi bi-clock-history me-1"></i>{{ $approvedApprovalsCount }}/{{ $totalApprovalsCount }} Signed
              @endif
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          @php
            $hasFlights = $trf->flightLogs && $trf->flightLogs->isNotEmpty();
            $reimb = $trf->reimbursements ? $trf->reimbursements->first() : null;
            $hasReimbApproved = $reimb && $reimb->status === 'approved';
          @endphp
          <div class="travel-lifecycle-bar" title="Travel Lifecycle Progress">
            <div class="bar-segment done" title="1. Requisition Initiated"></div>
            <div class="bar-segment {{ $isAllApproved ? 'done' : ($trf->status === 'pushed_back' ? 'active' : 'active') }}" title="2. Executive Endorsement"></div>
            <div class="bar-segment {{ $hasFlights ? 'done' : ($isAllApproved ? 'active' : '') }}" title="3. Logistics &amp; Ticketing"></div>
            <div class="bar-segment {{ $hasReimbApproved ? 'done' : ($hasFlights ? 'active' : '') }}" title="4. Expense Settlement"></div>
          </div>

          <!-- Connected Workflow Timeline -->
          <div class="workflow-timeline position-relative">

            <!-- Step 1: TRF Raised -->
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
                    <span class="timeline-step-role text-success">Requisition Initiated</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Submitted
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $trf->employee?->name ?? 'Requester' }} ({{ $trf->department }})</span>
                    <span>{{ $trf->created_at?->format('d M, H:i') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Executive Approval Tier (ASG / SG) -->
            @foreach ($trf->approvals->sortBy('level') as $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';
                $isPushed = $approval->status === 'pushed_back';

                $connectorClass = $isApproved ? 'approved' : ($isPushed ? 'pushed_back' : ($isPending ? 'pending dashed' : 'dashed'));
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPushed ? 'pushed_back' : ($isPending ? 'pending pulse-ring' : 'upcoming')));
                $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    @if ($isApproved)
                      <i class="bi bi-check-lg"></i>
                    @elseif ($isRejected)
                      <i class="bi bi-x-lg"></i>
                    @elseif ($isPushed)
                      <i class="bi bi-arrow-counterclockwise"></i>
                    @elseif ($isPending)
                      <i class="bi bi-hourglass-split"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                  <div class="timeline-step-connector {{ $connectorClass }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isPending ? 'text-primary' : '') }}">
                        {{ \App\Support\Roles::labelAny($approval->role_required) }}
                      </span>
                      @if ($isApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Endorsed
                        </span>
                      @elseif ($isRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Declined
                        </span>
                      @elseif ($isPushed)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Pushed Back
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
                      <div class="timeline-quote-bubble {{ $isPushed ? 'pushed' : '' }}">
                        &ldquo;{{ $approval->comments }}&rdquo;
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach

            <!-- Step 3: Ticketing & Logistics -->
            @php
              $flightCount = $trf->flightLogs ? $trf->flightLogs->count() : 0;
              $flightCost = $trf->flightLogs ? $trf->flightLogs->sum('cost') : 0;
              $step3State = $flightCount > 0 ? 'approved' : ($isAllApproved ? 'info' : 'upcoming');
              $step3Connector = $flightCount > 0 ? 'approved' : 'dashed';
            @endphp
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon {{ $step3State }}">
                  @if ($flightCount > 0)
                    <i class="bi bi-check-lg"></i>
                  @elseif ($isAllApproved)
                    <i class="bi bi-ticket-perforated"></i>
                  @else
                    <i class="bi bi-airplane"></i>
                  @endif
                </div>
                <div class="timeline-step-connector {{ $step3Connector }}"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card {{ $flightCount > 0 ? 'approved-stage' : '' }}">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role {{ $flightCount > 0 ? 'text-success' : '' }}">Flight &amp; Logistics Booking</span>
                    @if ($flightCount > 0)
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Ticketed
                      </span>
                    @elseif ($isAllApproved)
                      <span class="badge bg-info-subtle text-info border border-info-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Ready to Book
                      </span>
                    @else
                      <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">Upcoming</span>
                    @endif
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    @if ($flightCount > 0)
                      <span>{{ $flightCount }} Booking(s) · ₹{{ number_format($flightCost, 2) }}</span>
                      <span class="text-success">Confirmed</span>
                    @elseif ($isAllApproved)
                      <span>Admin Head booking window open</span>
                      <span class="text-info">Actionable</span>
                    @else
                      <span>Unlocks after executive endorsement</span>
                      <span>&mdash;</span>
                    @endif
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Expense Clearance & Settlement -->
            @php
              $travelEndPassed = $trf->travel_end && $trf->travel_end->isPast();
              $step4State = $hasReimbApproved ? 'approved' : ($reimb ? 'pending' : ($travelEndPassed ? 'pushed_back' : 'upcoming'));
            @endphp
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon {{ $step4State }}">
                  @if ($hasReimbApproved)
                    <i class="bi bi-check-all"></i>
                  @elseif ($reimb)
                    <i class="bi bi-clock-history"></i>
                  @elseif ($travelEndPassed)
                    <i class="bi bi-exclamation-circle"></i>
                  @else
                    <i class="bi bi-wallet2"></i>
                  @endif
                </div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card {{ $hasReimbApproved ? 'approved-stage' : '' }}">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role {{ $hasReimbApproved ? 'text-success' : '' }}">Reimbursement &amp; Settlement</span>
                    @if ($hasReimbApproved)
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Reconciled
                      </span>
                    @elseif ($reimb)
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Under Audit
                      </span>
                    @elseif ($travelEndPassed)
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Due Now
                      </span>
                    @else
                      <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">Final Step</span>
                    @endif
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    @if ($hasReimbApproved)
                      <span>Travel claims fully reconciled by Finance</span>
                      <span class="text-success">Settled</span>
                    @elseif ($reimb)
                      <span>Claim {{ $reimb->reimbursement_number }} in audit queue</span>
                      <span class="text-warning">In Audit</span>
                    @elseif ($travelEndPassed)
                      <span class="text-danger fw-semibold">Trip ended · Settlement claim due</span>
                      @if (auth()->id() === $trf->employee_id)
                        <a href="{{ route('reimb.create') }}" class="badge bg-danger text-white text-decoration-none py-0.5 px-1.5">File Claim</a>
                      @endif
                    @else
                      <span>Lodged post-travel completion</span>
                      <span>&mdash;</span>
                    @endif
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Decision Box / Action Panel -->
        @if ($pendingApproval)
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
                  {{ \App\Support\Roles::labelAny($pendingApproval->role_required) }}
                </span>
              </div>

              <div class="text-secondary small mb-2.5" style="font-size:12px;">
                Review travel route, logistics requirements, and advance amount (₹{{ number_format($trf->requested_advance, 2) }}) before endorsing.
              </div>

              <!-- Quick Comment Chips -->
              <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
                <span class="quick-comment-chip" onclick="setTrfComment('Approved as per travel policy.')">✓ Policy Compliant</span>
                <span class="quick-comment-chip" onclick="setTrfComment('Advance cleared for booking.')">💰 Advance Cleared</span>
                <span class="quick-comment-chip" onclick="setTrfComment('Economy class flights endorsed.')">✈️ Economy Endorsed</span>
                <span class="quick-comment-chip" onclick="setTrfComment('Please adjust travel dates and resubmit.')">📅 Need Dates Adjustment</span>
              </div>

              <form method="POST" action="{{ route('trf.approve', $trf) }}" id="trfApproveForm" class="mb-2">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                  <input type="text" name="comments" id="trfCommentsInput" class="form-control" placeholder="Endorsement comments / notes (optional)">
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                  <i class="bi bi-shield-check"></i>
                  <span>Endorse Travel Request</span>
                </button>
              </form>

              <form method="POST" action="{{ route('trf.pushback', $trf) }}" id="trfPushbackForm"
                    onsubmit="return checkPushbackComments();">
                @csrf
                <input type="hidden" name="comments" id="pushbackCommentsHidden">
                <button type="button" class="btn btn-sm btn-outline-warning w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold" onclick="handlePushbackClick()">
                  <i class="bi bi-arrow-return-left"></i>
                  <span>Push Back for Revision</span>
                </button>
              </form>

              <script>
                function setTrfComment(text) {
                  var input = document.getElementById('trfCommentsInput');
                  if (input) {
                    input.value = text;
                    input.focus();
                  }
                }
                function handlePushbackClick() {
                  var input = document.getElementById('trfCommentsInput');
                  var val = input ? input.value.trim() : '';
                  if (!val) {
                    val = prompt('Please provide the revision reason or clarification required:');
                    if (!val || val.trim() === '') {
                      alert('A comment is required to push back the travel request.');
                      return;
                    }
                  }
                  document.getElementById('pushbackCommentsHidden').value = val;
                  document.getElementById('trfPushbackForm').submit();
                }
                function checkPushbackComments() {
                  var val = document.getElementById('pushbackCommentsHidden').value;
                  return val && val.trim() !== '';
                }
              </script>
            </div>
          </div>
        @elseif (in_array($trf->status, ['pending_asg', 'pending_sg']))
          <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Awaiting Executive Review</div>
              <div class="text-secondary" style="font-size:12px;">
                Currently with <strong>{{ \App\Support\Roles::labelAny($trf->approvals->where('status', 'pending')->first()?->role_required ?? 'Reviewing Executive') }}</strong>. You will be notified once a decision is made.
              </div>
            </div>
          </div>
        @elseif ($trf->status === 'approved')
          <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
            <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">TRF Fully Endorsed</div>
              <div class="text-secondary" style="font-size:12px;">
                All administrative and executive clearances are confirmed. Logistics and itinerary are approved.
              </div>
            </div>
          </div>
        @elseif ($trf->status === 'pushed_back')
          <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-exclamation-circle-fill fs-4 text-warning flex-shrink-0"></i>
            <div>
              <div class="fw-bold mb-0.5">Returned for Revision</div>
              <div class="text-secondary" style="font-size:12px;">
                The reviewing authority has requested revisions. Please check the review remarks above.
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
Shell.init({ activeNav: 'trf', crumb: 'Procurement', title: 'TRF Detail' });
@endsection
