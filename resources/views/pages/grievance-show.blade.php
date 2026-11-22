@extends('layouts.app')

@section('title', $grievance->ticket_number)

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'admin_head' => ['label' => 'With Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'cfo'        => ['label' => 'With CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'sg'         => ['label' => 'With SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  ];
  $meta = $grievance->status === 'resolved' ? ['label' => 'Resolved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'] : ($ownerMeta[$grievance->current_owner_role] ?? $ownerMeta['hod']);
  $currentIdx = array_search($grievance->current_owner_role, \App\Models\Grievance::ESCALATION_ORDER);
  if ($currentIdx === false) { $currentIdx = 0; }
  $isResolved = $grievance->status === 'resolved';
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grievance') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
    <span>&larr; Back to Grievance Redressal</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: Grievance Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $grievance->ticket_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
              <span class="detail-meta-chip"><i class="bi bi-tag"></i> <span class="meta-label">Category:</span> <strong>{{ $grievance->subject_category }}</strong></span>
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Raised By:</span> <strong>{{ $grievance->raisedBy?->name ?? 'System' }}</strong></span>
              <span class="detail-meta-chip"><i class="bi bi-shield-check"></i> <span class="meta-label">Handler:</span> <strong>{{ \App\Support\Roles::label($grievance->current_owner_role) }}</strong></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Horizontal Redressal & Escalation Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Raised -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Grievance Lodged">
              <span class="fw-bold" style="font-size: 11px;">1</span>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="Lodged">
                Incident Filed
              </div>
              <div class="mb-1">
                <span class="badge bg-success-subtle text-success border py-1 px-2" style="font-size: 10px; font-weight: 600;">
                  Lodged
                </span>
              </div>
              <div class="h-step-person" title="{{ $grievance->raisedBy?->name ?? 'System' }}">
                {{ $grievance->raisedBy?->name ?? 'System' }}
              </div>
              <div class="h-step-meta mt-1">
                {{ $grievance->created_at->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Escalation Roles -->
          @foreach (\App\Models\Grievance::ESCALATION_ORDER as $i => $role)
            @php
              $roleLabel = \App\Support\Roles::label($role);
              if ($isResolved) {
                if ($i <= $currentIdx) {
                  $stepStatusClass = 'is-done';
                  $iconClass = 'done';
                  $cardClass = 'done-card';
                } else {
                  $stepStatusClass = 'is-stopped';
                  $iconClass = 'upcoming';
                  $cardClass = 'stopped-card';
                }
              } else {
                if ($i < $currentIdx) {
                  $stepStatusClass = 'is-done';
                  $iconClass = 'done';
                  $cardClass = 'done-card';
                } elseif ($i === $currentIdx) {
                  $stepStatusClass = 'is-active';
                  $iconClass = 'active';
                  $cardClass = 'active-card';
                } else {
                  $stepStatusClass = 'is-stopped';
                  $iconClass = 'upcoming';
                  $cardClass = 'stopped-card';
                }
              }
            @endphp
            <div class="h-timeline-step {{ $stepStatusClass }}">
              <div class="h-step-icon {{ $iconClass }}">
                <span class="fw-bold" style="font-size: 11.5px;">{{ $i + 2 }}</span>
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="h-step-title {{ ($i < $currentIdx || ($isResolved && $i <= $currentIdx)) ? 'text-success' : ($i === $currentIdx ? 'text-warning-emphasis' : 'text-muted') }} mb-1" title="{{ $roleLabel }}">
                  Tier {{ $i + 1 }}: {{ $roleLabel }}
                </div>
                <div class="mb-1">
                  <span class="badge {{ ($i < $currentIdx || ($isResolved && $i <= $currentIdx)) ? 'bg-success-subtle text-success' : ($i === $currentIdx ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted') }} border py-1 px-2" style="font-size: 10px; font-weight: 600;">
                    @if ($isResolved && $i === $currentIdx) Resolved
                    @elseif ($i < $currentIdx) Escalated
                    @elseif ($i === $currentIdx) Active Tier
                    @else Standby
                    @endif
                  </span>
                </div>
                <div class="h-step-person">
                  @if ($isResolved && $i <= $currentIdx)
                    <span class="text-success fw-semibold">{{ $grievance->resolvedBy?->name ?? 'Closed' }}</span>
                  @elseif ($i === $currentIdx)
                    <span class="text-warning fw-bold">{{ $roleLabel }}</span>
                  @else
                    <span class="text-muted">Awaiting Stage</span>
                  @endif
                </div>
                <div class="h-step-meta mt-1">
                  @if ($isResolved && $i === $currentIdx)
                    {{ $grievance->resolved_at?->format('d M, H:i') ?? 'Resolved' }}
                  @elseif ($i === $currentIdx)
                    Action Required
                  @elseif ($i < $currentIdx)
                    Passed
                  @else
                    Upcoming
                  @endif
                </div>
              </div>
            </div>
          @endforeach

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Left: Incident Narrative & Metadata (Vendor Empanelment Dossier Style) -->
    <div class="col-12 {{ ($canResolve || $canEscalate) && $grievance->status !== 'resolved' ? 'col-lg-8' : '' }}">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="pb-3 mb-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h2 class="h5 mb-0 fw-bold text-dark">Incident Particulars &amp; Narrative</h2>
          <span class="badge bg-light text-secondary border font-monospace">
            {{ $grievance->ticket_number }}
          </span>
        </div>

        <!-- 1. Subject & Narrative -->
        <div class="mb-4">
          <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Subject &amp; Issue Summary</div>
          <div class="text-dark fw-bold mb-3" style="font-size: 15px;">
            {{ $grievance->subject }}
          </div>

          <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Incident Narrative Description</div>
          <div class="p-3 rounded-2 border bg-light-subtle text-dark" style="font-size: 13.5px; line-height: 1.6;">
            {{ $grievance->description }}
          </div>
        </div>

        <hr class="my-4 text-muted">

        <!-- 2. Incident Particulars & Ownership Grid -->
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Raised By</div>
            <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $grievance->raisedBy?->name ?? 'System' }}</div>
          </div>

          <div class="col-md-6">
            <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Filed On</div>
            <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $grievance->created_at->format('d M Y, H:i') }}</div>
          </div>

          <div class="col-md-6">
            <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Issue Category</div>
            <div class="text-dark fw-semibold" style="font-size: 13.5px;">
              <span class="badge bg-light text-dark border">{{ $grievance->subject_category }}</span>
            </div>
          </div>

          <div class="col-md-6">
            <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Current Ownership Role</div>
            <div class="text-dark fw-semibold" style="font-size: 13.5px;">
              <span class="badge badge-soft-warning">{{ \App\Support\Roles::label($grievance->current_owner_role) }}</span>
            </div>
          </div>

          @if ($grievance->reimbursement)
            <div class="col-12">
              <div class="p-3 rounded-2 bg-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                  <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Linked Reimbursement / Advance Claim</div>
                  <div class="fw-bold text-dark" style="font-size: 13.5px;">
                    <span class="font-monospace">{{ $grievance->reimbursement->claim_number }}</span>
                    · {{ $grievance->reimbursement->trf?->trf_number ?? 'Off-TRF Claim' }}
                  </div>
                  <div class="text-muted small">
                    Claim ₹{{ number_format((float) $grievance->reimbursement->claim_amount, 2) }}
                    · Advance ₹{{ number_format((float) $grievance->reimbursement->advance_amount, 2) }}
                    · Net Balance ₹{{ number_format((float) $grievance->reimbursement->balance_amount, 2) }}
                    ({{ ucfirst(str_replace('_', ' ', $grievance->reimbursement->status)) }})
                  </div>
                </div>
                <a href="{{ route('reimb.show', $grievance->reimbursement) }}" class="btn btn-sm btn-outline-primary fw-semibold">
                  View Claim &rarr;
                </a>
              </div>
            </div>
          @endif

          @if ($grievance->attachments->isNotEmpty())
            <div class="col-12">
              <div class="text-secondary small mb-2" style="font-size: 11.5px; font-weight: 500;">Supporting Evidence ({{ $grievance->attachments->count() }})</div>
              <div class="d-flex flex-column gap-2">
                @foreach ($grievance->attachments as $att)
                  <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-2 bg-light border small">
                    <span class="fw-medium text-dark">{{ $att->original_name ?: basename($att->file_path) }}</span>
                    <a href="{{ route('grievance.attachments.show', [$grievance, $att]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size: 11.5px;">
                      View File
                    </a>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>

        @if ($grievance->status === 'resolved')
          <div class="p-3 rounded-2 mt-4 border" style="background:#f0fdf4; border-color:#bbf7d0 !important;">
            <div style="font-size: 13.5px; color: #1e293b;">
              <strong>Resolved</strong> by {{ $grievance->resolvedBy?->name ?? 'System' }} on {{ $grievance->resolved_at?->format('d M Y, H:i') }}.
            </div>
            @if ($grievance->resolution_notes)
              <div class="text-secondary small mt-1">
                <em>Resolution Notes:</em> {{ $grievance->resolution_notes }}
              </div>
            @endif
          </div>
        @elseif (!$canRespond && !$canResolve && !$canEscalate)
          <div class="p-3 rounded-2 mt-4 border bg-light">
            <div style="font-size: 13px; color: #64748b;">
              Currently assigned to {{ \App\Support\Roles::label($grievance->current_owner_role) }}. You will be notified as soon as the assigned owner responds or resolves this ticket.
            </div>
          </div>
        @endif
      </div>

      <!-- Step-by-Step Response & Tracking Log -->
      @php
        $timelineSteps = $grievance->updates;
        if ($timelineSteps->isEmpty()) {
          $synthetic = collect([
            (object) [
              'action' => 'filed',
              'actionLabel' => 'Ticket Filed',
              'actorName' => $grievance->raisedBy?->name ?? 'System',
              'ownerLabel' => \App\Support\Roles::label(\App\Support\Roles::HOD),
              'timestamp' => $grievance->created_at?->format('d M Y, H:i'),
              'message' => 'Ticket logged and assigned for review.',
            ],
          ]);
          if ($grievance->status === 'resolved') {
            $synthetic->push((object) [
              'action' => 'resolved',
              'actionLabel' => 'Resolved & Closed',
              'actorName' => $grievance->resolvedBy?->name ?? 'Handler',
              'ownerLabel' => \App\Support\Roles::label($grievance->current_owner_role),
              'timestamp' => $grievance->resolved_at?->format('d M Y, H:i'),
              'message' => $grievance->resolution_notes ?: 'Ticket marked resolved and closed.',
            ]);
          }
          $timelineSteps = $synthetic;
        }
      @endphp
      <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <h6 class="mb-0 fw-bold text-dark">Response &amp; Tracking History</h6>
          <span class="badge bg-light text-secondary border">{{ $timelineSteps->count() }} {{ Str::plural('Update', $timelineSteps->count()) }}</span>
        </div>
        <div class="list-group list-group-flush">
          @foreach ($timelineSteps as $step)
            @php
              $stepAction = $step->action;
              $stepBadge = match ($stepAction) {
                'resolved' => 'bg-success-subtle text-success border-success-subtle',
                'escalated' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                'responded' => 'bg-primary-subtle text-primary border-primary-subtle',
                default => 'bg-light text-secondary border',
              };
              $isModelStep = $step instanceof \App\Models\GrievanceUpdate;
              $label = $isModelStep ? $step->actionLabel() : $step->actionLabel;
              $actor = $isModelStep ? ($step->actor?->name ?? 'System') : $step->actorName;
              $owner = $isModelStep ? \App\Support\Roles::label($step->owner_role) : $step->ownerLabel;
              $time = $isModelStep ? $step->created_at?->format('d M Y, H:i') : $step->timestamp;
            @endphp
            <div class="list-group-item px-4 py-3 border-light-subtle">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge border {{ $stepBadge }}" style="font-size:11px;">{{ $label }}</span>
                  <span class="fw-semibold text-dark small">{{ $actor }}</span>
                  <span class="text-muted" style="font-size:11.5px;">· {{ $owner }}</span>
                </div>
                <span class="text-muted" style="font-size:11.5px;">{{ $time }}</span>
              </div>
              <div class="text-dark" style="font-size:13.5px; line-height:1.5;">{{ $step->message }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <!-- Right: Clean Tabbed Operational Actions (Respond | Resolve | Escalate) -->
    @if (($canRespond || $canResolve || $canEscalate) && $grievance->status !== 'resolved')
      @php
        $defaultTab = $canRespond ? 'respond' : ($canResolve ? 'resolve' : 'escalate');
        $nextRoleSlug = $grievance->nextOwnerRole();
      @endphp
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark small">
              <span style="font-size: 13.5px;">Assigned Owner Actions</span>
            </div>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
              Actionable
            </span>
          </div>

          <div class="card-body p-3">
            <ul class="nav nav-pills nav-fill bg-light p-1 rounded-2 mb-3" role="tablist" style="font-size:12px;">
              @if ($canRespond)
                <li class="nav-item" role="presentation">
                  <button class="nav-link fw-semibold py-1 {{ $defaultTab === 'respond' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabRespond" type="button" role="tab">
                    Respond
                  </button>
                </li>
              @endif
              @if ($canResolve)
                <li class="nav-item" role="presentation">
                  <button class="nav-link fw-semibold py-1 {{ $defaultTab === 'resolve' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabResolve" type="button" role="tab">
                    Resolve
                  </button>
                </li>
              @endif
              @if ($canEscalate)
                <li class="nav-item" role="presentation">
                  <button class="nav-link fw-semibold py-1 {{ $defaultTab === 'escalate' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabEscalate" type="button" role="tab">
                    Escalate
                  </button>
                </li>
              @endif
            </ul>

            <div class="tab-content">
              @if ($canRespond)
                <div class="tab-pane fade {{ $defaultTab === 'respond' ? 'show active' : '' }}" id="tabRespond" role="tabpanel">
                  <form method="POST" action="{{ route('grievance.respond', $grievance) }}" class="m-0">
                    @csrf
                    <div class="mb-3">
                      <label class="form-label small fw-bold text-secondary mb-1" for="responseMessageInput">Owner Response / Status Update</label>
                      <textarea name="response_message" id="responseMessageInput" class="form-control form-control-sm" rows="3" placeholder="Share investigation progress or clarification with the ticket raiser..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100 justify-content-center d-flex align-items-center py-2 fw-semibold">
                      <span>Send Response &amp; Notify User</span>
                    </button>
                  </form>
                </div>
              @endif

              @if ($canResolve)
                <div class="tab-pane fade {{ $defaultTab === 'resolve' ? 'show active' : '' }}" id="tabResolve" role="tabpanel">
                  <form method="POST" action="{{ route('grievance.resolve', $grievance) }}" class="m-0">
                    @csrf
                    <div class="mb-3">
                      <label class="form-label small fw-bold text-secondary mb-1" for="resolutionNotesInput">Final Resolution Note (Optional)</label>
                      <textarea name="resolution_notes" id="resolutionNotesInput" class="form-control form-control-sm" rows="3" placeholder="Describe corrective actions taken or settlement closure details..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center py-2 fw-semibold shadow-sm">
                      <span>Resolve &amp; Close Ticket</span>
                    </button>
                  </form>
                </div>
              @endif

              @if ($canEscalate)
                <div class="tab-pane fade {{ $defaultTab === 'escalate' ? 'show active' : '' }}" id="tabEscalate" role="tabpanel">
                  <form method="POST" action="{{ route('grievance.escalate', $grievance) }}" class="m-0">
                    @csrf
                    <div class="rounded-2 bg-light border px-3 py-2 mb-2 text-muted" style="font-size:12px;">
                      Hands off ticket to <strong>{{ \App\Support\Roles::label($nextRoleSlug) }}</strong> and notifies both the user and next tier owner.
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-bold text-secondary mb-1" for="escalationNotesInput">Escalation Handoff Note (Optional)</label>
                      <textarea name="escalation_notes" id="escalationNotesInput" class="form-control form-control-sm" rows="3" placeholder="Explain why the next tier needs to review..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning text-dark btn-sm w-100 justify-content-center d-flex align-items-center py-2 fw-semibold">
                      <span>Escalate to {{ \App\Support\Roles::label($nextRoleSlug) }}</span>
                    </button>
                  </form>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Detail' });
@endsection
