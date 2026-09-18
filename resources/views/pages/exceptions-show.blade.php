@extends('layouts.app')

@section('title', 'Exception #'.$exception->id)

@php
  $typeMeta = [
    'single_quotation' => 'Single Quotation',
    'budget_exceed' => 'Budget Exceed Override',
    'po_force_close' => 'Purchase Order Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Compliance Check',
  ];
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$exception->status] ?? $statusMeta['pending'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('exceptions') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Exception Handling</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar" style="background:linear-gradient(135deg,rgba(176,38,58,0.15),rgba(176,38,58,0.05)); color:var(--maroon); border:1px solid rgba(176,38,58,0.2);">
      <i class="bi bi-shield-exclamation fs-4"></i>
    </div>
    <div>
      <div class="detail-name">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }} #{{ $exception->id }}</div>
      <div class="detail-meta d-flex align-items-center gap-2 mt-1">
        <span>Filed by {{ $exception->requester->name }}</span>
        <span>•</span>
        <span>Authority: {{ \App\Support\Roles::label($exception->approver_queue) }}</span>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; padding:6px 14px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <!-- Left: Exception Details & Actions (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-file-earmark-text text-primary"></i>
          <span>Exception Dossier</span>
        </h3>

        <div class="field-label">Business Justification &amp; Urgency</div>
        <div class="p-3 rounded-3 mt-1 mb-3" style="background:#f8fafc; border-left:3px solid var(--maroon); font-size:13.5px; color:#1e293b; line-height:1.5;">
          {{ $exception->justification }}
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="field-label">Reference Type</div>
            <div class="field-value mt-1">
              @if ($exception->reference_type)
                <span class="badge" style="background:#f1f5f9; color:#334155; font-size:12px; font-weight:500;">
                  {{ strtoupper($exception->reference_type) }} #{{ $exception->reference_id }}
                </span>
              @else
                <span class="text-muted">General Policy Deviation</span>
              @endif
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Deciding Authority Queue</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1" style="font-weight:600; color:#1e293b;">
              <i class="bi bi-person-badge text-muted"></i>
              <span>{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
            </div>
          </div>
        </div>

        @if ($exception->status !== 'pending')
          <div class="p-3 rounded-3 mt-4 border d-flex align-items-center gap-2" style="background:{{ $exception->status === 'approved' ? '#f0fdf4' : '#fef2f2' }}; border-color:{{ $exception->status === 'approved' ? '#bbf7d0' : '#fecaca' }} !important;">
            <i class="bi {{ $exception->status === 'approved' ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i>
            <div style="font-size:13px; color:#1e293b;">
              <strong>{{ ucfirst($exception->status) }}</strong> by {{ $exception->decidedBy?->name ?? 'System' }} on {{ $exception->decided_at?->format('d M Y, H:i') }}.
            </div>
          </div>
        @elseif ($canDecide)
          <div class="pt-4 border-top mt-4 d-flex gap-3">
            <form method="POST" action="{{ route('exceptions.approve', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 22px;">
                <i class="bi bi-check-circle"></i>
                <span>Approve Exception</span>
              </button>
            </form>
            <form method="POST" action="{{ route('exceptions.reject', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-outline-danger d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 22px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Exception</span>
              </button>
            </form>
          </div>
        @else
          <div class="p-3 rounded-3 mt-4 border d-flex align-items-center gap-2" style="background:#f8fafc;">
            <i class="bi bi-hourglass-split text-warning fs-5"></i>
            <div style="font-size:13px; color:#64748b;">
              Awaiting review from {{ \App\Support\Roles::label($exception->approver_queue) }}. No action required from your role at this time.
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Right: Colored Icon Workflow Timeline (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="card-panel p-4">
        <h3 class="h6 mb-4 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-diagram-3 text-primary"></i>
          <span>Resolution Lifecycle</span>
        </h3>

        <div class="workflow-timeline">
          <!-- Step 1: Logged -->
          <div class="timeline-step-item">
            <div class="timeline-step-rail">
              <div class="timeline-step-icon approved">
                <i class="bi bi-check-lg"></i>
              </div>
              <div class="timeline-step-connector {{ $exception->status !== 'pending' ? 'approved' : '' }}"></div>
            </div>
            <div class="timeline-step-body">
              <div class="timeline-step-role">Exception Filed</div>
              <div class="timeline-step-name">{{ $exception->requester->name }}</div>
              <div class="timeline-step-time">{{ $exception->created_at?->format('d M, H:i') }}</div>
            </div>
          </div>

          <!-- Step 2: Governance Authority Review -->
          @php
            $step2State = $exception->status === 'approved' ? 'approved' : ($exception->status === 'rejected' ? 'rejected' : 'pending');
          @endphp
          <div class="timeline-step-item">
            <div class="timeline-step-rail">
              <div class="timeline-step-icon {{ $step2State }}">
                @if ($exception->status === 'approved')
                  <i class="bi bi-check-lg"></i>
                @elseif ($exception->status === 'rejected')
                  <i class="bi bi-x-lg"></i>
                @else
                  <i class="bi bi-clock-history"></i>
                @endif
              </div>
              <div class="timeline-step-connector {{ $exception->status === 'approved' ? 'approved' : '' }}"></div>
            </div>
            <div class="timeline-step-body">
              <div class="timeline-step-role">{{ \App\Support\Roles::label($exception->approver_queue) }} Review</div>
              <div class="timeline-step-name">
                @if ($exception->status === 'pending')
                  Awaiting Decision
                @else
                  {{ $exception->decidedBy?->name ?? 'Decided' }} ({{ ucfirst($exception->status) }})
                @endif
              </div>
              @if ($exception->decided_at)
                <div class="timeline-step-time">{{ $exception->decided_at->format('d M, H:i') }}</div>
              @endif
            </div>
          </div>

          <!-- Step 3: Ledger Effect -->
          @php
            $step3State = $exception->status === 'approved' ? 'approved' : ($exception->status === 'rejected' ? 'rejected' : 'upcoming');
          @endphp
          <div class="timeline-step-item">
            <div class="timeline-step-rail">
              <div class="timeline-step-icon {{ $step3State }}">
                @if ($exception->status === 'approved')
                  <i class="bi bi-check-all"></i>
                @elseif ($exception->status === 'rejected')
                  <i class="bi bi-slash-circle"></i>
                @else
                  <i class="bi bi-circle"></i>
                @endif
              </div>
            </div>
            <div class="timeline-step-body">
              <div class="timeline-step-role">Ledger Effect</div>
              <div class="timeline-step-name">
                @if ($exception->status === 'approved')
                  Override Executed &amp; Logged
                @elseif ($exception->status === 'rejected')
                  Exception Disallowed
                @else
                  Pending Approval
                @endif
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Detail' });
@endsection

