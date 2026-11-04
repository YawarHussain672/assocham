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
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grievance') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Grievance Redressal</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar" style="background:linear-gradient(135deg,rgba(176,38,58,0.15),rgba(176,38,58,0.05)); color:var(--maroon); border:1px solid rgba(176,38,58,0.2);">
      <i class="bi bi-ticket-perforated fs-4"></i>
    </div>
    <div>
      <div class="detail-name">{{ $grievance->ticket_number }} — {{ $grievance->subject }}</div>
      <div class="detail-meta d-flex align-items-center gap-2 mt-1">
        <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $grievance->subject_category }}</span>
        <span>•</span>
        <span>Raised by {{ $grievance->raisedBy->name }}</span>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; padding:6px 14px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left: Grievance Details & Action Controls (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-card-text text-primary"></i>
          <span>Incident Particulars</span>
        </h3>

        <div class="field-label">Detailed Incident Narrative</div>
        <div class="p-3 rounded-3 mt-1 mb-3" style="background:#f8fafc; border-left:3px solid var(--maroon); font-size:13.5px; color:#1e293b; line-height:1.6;">
          {{ $grievance->description ?: 'No detailed narrative provided.' }}
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="field-label">Current Owner Role</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1" style="font-weight:600; color:#1e293b;">
              <i class="bi bi-person-badge text-muted"></i>
              <span>{{ \App\Support\Roles::label($grievance->current_owner_role) }}</span>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Date Filed</div>
            <div class="field-value mt-1 text-muted" style="font-size:13px;">
              <i class="bi bi-calendar-event me-1"></i>{{ $grievance->created_at?->format('d M Y, H:i') }}
            </div>
          </div>
        </div>

        @if ($grievance->status === 'resolved')
          <div class="p-3 rounded-3 mt-4 border d-flex align-items-center gap-2" style="background:#f0fdf4; border-color:#bbf7d0 !important;">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div style="font-size:13px; color:#1e293b;">
              <strong>Ticket Resolved</strong> by {{ $grievance->resolvedBy?->name ?? 'System' }} on {{ $grievance->resolved_at?->format('d M Y, H:i') }}.
            </div>
          </div>
        @else
          <div class="pt-4 border-top mt-4 d-flex gap-3">
            @if ($canEscalate)
              <form method="POST" action="{{ route('grievance.escalate', $grievance) }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
                  <i class="bi bi-arrow-up-right"></i>
                  <span>Escalate to {{ \App\Support\Roles::label($grievance->nextOwnerRole() ?? '') }}</span>
                </button>
              </form>
            @endif

            @if ($canResolve)
              <form method="POST" action="{{ route('grievance.resolve', $grievance) }}" class="m-0">
                @csrf
                <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 22px;">
                  <i class="bi bi-check2-circle"></i>
                  <span>Mark Ticket Resolved</span>
                </button>
              </form>
            @endif

            @if (! $canEscalate && ! $canResolve)
              <div class="p-3 rounded-3 border w-100 d-flex align-items-center gap-2" style="background:#f8fafc;">
                <i class="bi bi-hourglass-split text-warning fs-5"></i>
                <div style="font-size:13px; color:#64748b;">
                  Currently assigned to {{ \App\Support\Roles::label($grievance->current_owner_role) }}. No action required from your role at this stage.
                </div>
              </div>
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Right: Vertical Connected Escalation Path Timeline (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div class="travel-timeline-header">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <div class="icon-bubble" style="background:#fef2f2; color:var(--maroon); width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px;">
                <i class="bi bi-diagram-3-fill"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold" style="font-size:14.5px; color:#0f172a;">Redressal &amp; Escalation Rail</h6>
                <div class="text-muted" style="font-size:11.5px;">Institutional Grievance Governance</div>
              </div>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11px; padding:4px 9px;">
              {{ $meta['label'] }}
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          @php
            $currentIdx = array_search($grievance->current_owner_role, \App\Models\Grievance::ESCALATION_ORDER);
            if ($currentIdx === false) { $currentIdx = 0; }
            $isResolved = $grievance->status === 'resolved';
          @endphp
          <div class="travel-lifecycle-bar" title="Escalation Progress">
            @foreach (\App\Models\Grievance::ESCALATION_ORDER as $idx => $roleKey)
              @php
                $segClass = '';
                if ($isResolved) {
                  $segClass = $idx <= $currentIdx ? 'done' : '';
                } else {
                  if ($idx < $currentIdx) { $segClass = 'done'; }
                  elseif ($idx === $currentIdx) { $segClass = 'active'; }
                }
              @endphp
              <div class="bar-segment {{ $segClass }}" title="{{ \App\Support\Roles::label($roleKey) }}"></div>
            @endforeach
          </div>
        </div>

        <div class="travel-timeline-body">
          <div class="workflow-timeline position-relative">
            @foreach (\App\Models\Grievance::ESCALATION_ORDER as $index => $role)
              @php
                $isPast = $index < $currentIdx;
                $isCurrent = $role === $grievance->current_owner_role && ! $isResolved;
                $isLast = $index === count(\App\Models\Grievance::ESCALATION_ORDER) - 1;

                $state = 'upcoming';
                if ($isResolved) {
                  $state = ($index <= $currentIdx) ? 'approved' : 'upcoming';
                } elseif ($isCurrent) {
                  $state = 'pending';
                } elseif ($isPast) {
                  $state = 'approved';
                }

                $cardStage = $state === 'approved' ? 'approved-stage' : ($state === 'pending' ? 'active-stage' : '');
                $iconClass = $state === 'approved' ? 'approved' : ($state === 'pending' ? 'pending pulse-ring' : 'upcoming');
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    @if ($state === 'approved')
                      <i class="bi bi-check-lg"></i>
                    @elseif ($state === 'pending')
                      <i class="bi bi-hourglass-split"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                  @if (! $isLast)
                    <div class="timeline-step-connector {{ $state === 'approved' ? 'approved' : '' }}"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStage }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $state === 'approved' ? 'text-success' : ($state === 'pending' ? 'text-primary' : 'text-muted') }}">
                        {{ \App\Support\Roles::label($role) }} Review
                      </span>
                      @if ($isResolved && $index === $currentIdx)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Resolved
                        </span>
                      @elseif ($isCurrent)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          In Deliberation
                        </span>
                      @elseif ($state === 'approved')
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Reviewed
                        </span>
                      @else
                        <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                          Standby
                        </span>
                      @endif
                    </div>

                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      @if ($isResolved && $index === $currentIdx)
                        <span>Resolved by {{ $grievance->resolvedBy?->name ?? 'Authority' }}</span>
                        <span>{{ $grievance->resolved_at?->format('d M, H:i') }}</span>
                      @elseif ($isCurrent)
                        <span>Active review by {{ \App\Support\Roles::label($role) }}</span>
                        <span class="text-warning">In deliberation</span>
                      @elseif ($state === 'approved')
                        <span>Escalated to next authority</span>
                        <span>Closed</span>
                      @else
                        <span>Pending escalation review</span>
                        <span>&mdash;</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Detail' });
@endsection

