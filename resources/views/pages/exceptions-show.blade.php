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
    'on_hold'  => ['label' => 'On Hold', 'bg' => '#FFFBEB', 'fg' => '#B45309'],
  ];
  $meta = $statusMeta[$exception->status] ?? $statusMeta['pending'];
  $isApproved = $exception->status === 'approved';
  $isRejected = $exception->status === 'rejected';
  $isOnHold   = $exception->status === 'on_hold';
  $isPending  = in_array($exception->status, ['pending', 'on_hold']);
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

  @if ($isOnHold)
    <div class="alert alert-warning border-warning-subtle d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-pause-circle-fill text-warning fs-5"></i>
      <div>
        <strong>Exception is Currently On Hold.</strong> Additional clarification or internal inquiry is ongoing before a final decision.
      </div>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: Exception Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }} #{{ $exception->id }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Filed By:</span> <strong>{{ $exception->requester?->name ?? 'System' }}</strong></span>
              <span class="detail-meta-chip"><i class="bi bi-shield-check"></i> <span class="meta-label">Approver Queue:</span> <strong>{{ strtoupper($exception->approver_queue) }}</strong></span>
              @if ($exception->purchaseRequisition)
                <span class="detail-meta-chip">
                  <i class="bi bi-file-earmark-text"></i>
                  <span class="meta-label">PR:</span>
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
                    <a href="{{ route('requisition.show', $exception->purchaseRequisition) }}">{{ $exception->purchaseRequisition->pr_number }}</a>
                  @else
                    <strong>{{ $exception->purchaseRequisition->pr_number }}</strong>
                  @endif
                </span>
              @endif
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @if ($canDecide && in_array($exception->status, ['pending', 'on_hold']))
            <form method="POST" action="{{ route('exceptions.approve', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-check-lg"></i>
                <span>Approve Exception</span>
              </button>
            </form>
            @if ($exception->status !== 'on_hold')
              <button type="button" class="btn btn-sm btn-outline-warning px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" data-bs-toggle="modal" data-bs-target="#holdExceptionModal" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-pause-circle"></i>
                <span>Put On Hold</span>
              </button>
            @endif
            <form method="POST" action="{{ route('exceptions.reject', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-danger px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-x-lg"></i>
                <span>Reject Exception</span>
              </button>
            </form>
          @endif
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600;">
            Ref: {{ strtoupper($exception->reference_type ?: 'General') }} #{{ $exception->reference_id ?: '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Horizontal Governance & Audit Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Exception Filed -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Exception Filed">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="Exception Lodged">
                Exception Filed
              </div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Lodged
                </span>
              </div>
              <div class="h-step-person" title="{{ $exception->requester?->name ?? 'System' }}">
                {{ $exception->requester?->name ?? 'System' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $exception->created_at->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Stage 1: Authority Review -->
          @php
            if ($isApproved) {
              $revStatus = 'is-done';
              $revIcon = 'done';
              $revCard = 'done-card';
            } elseif ($isRejected) {
              $revStatus = 'is-declined';
              $revIcon = 'rejected';
              $revCard = 'active-card border-danger-subtle';
            } elseif ($isOnHold) {
              $revStatus = 'is-active';
              $revIcon = 'active';
              $revCard = 'active-card border-warning-subtle';
            } else {
              $revStatus = 'is-active';
              $revIcon = 'active';
              $revCard = 'active-card';
            }
          @endphp
          <div class="h-timeline-step {{ $revStatus }}">
            <div class="h-step-icon {{ $revIcon }}">
              @if ($isApproved)
                <i class="bi bi-check-lg"></i>
              @elseif ($isRejected)
                <i class="bi bi-x-lg"></i>
              @elseif ($isOnHold)
                <i class="bi bi-pause-fill text-warning"></i>
              @else
                <i class="bi bi-hourglass-split"></i>
              @endif
            </div>
            <div class="h-step-card {{ $revCard }}">
              <div class="h-step-title {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : 'text-warning-emphasis') }} mb-1" title="Authority Review">
                Authority Review
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $meta['label'] }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($exception->decidedBy)
                  <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $exception->decidedBy->name }}</span>
                @else
                  <span class="text-warning fw-bold">{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $exception->decided_at?->format('d M, H:i') ?? 'Action Required' }}
              </div>
            </div>
          </div>

          <!-- Stage 2: Audit Trail Entry -->
          @php
            if ($isApproved) {
              $audStatus = 'is-done';
              $audIcon = 'done';
              $audCard = 'done-card';
            } elseif ($isRejected) {
              $audStatus = 'is-stopped';
              $audIcon = 'upcoming';
              $audCard = 'stopped-card';
            } else {
              $audStatus = 'is-stopped';
              $audIcon = 'upcoming';
              $audCard = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $audStatus }}">
            <div class="h-step-icon {{ $audIcon }}">
              <i class="bi {{ $isApproved ? 'bi-shield-check' : 'bi-lock' }}"></i>
            </div>
            <div class="h-step-card {{ $audCard }}">
              <div class="h-step-title {{ $isApproved ? 'text-success' : 'text-muted' }} mb-1" title="Audit Trail Logged">
                Audit Trail
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $isApproved ? 'Committed' : 'Pending' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($isApproved)
                  <span class="text-success fw-semibold">Recorded in Ledger</span>
                @elseif ($isRejected)
                  <span class="text-danger">Deviation Blocked</span>
                @else
                  <span class="text-muted">Post-Review</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $isApproved ? 'Active' : ($isRejected ? 'Blocked' : 'Standby') }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Exception Justification & Metadata -->
    <div class="col-12">
      <div class="erp-kv-card">
        <div class="erp-kv-header">
          <div class="d-flex align-items-center gap-2">
            <span>Exception Justification &amp; Metadata</span>
          </div>
          <span class="badge bg-light text-secondary border">
            Ref: {{ strtoupper($exception->reference_type ?: 'General') }} #{{ $exception->reference_id ?: '—' }}
          </span>
        </div>
        <div>
          <div class="erp-kv-lbl mb-1">Exception Narrative &amp; Justification</div>
          <div class="p-3 rounded-2 mb-4 border bg-light" style="font-size:13.5px; line-height:1.6; color:#1e293b;">
            {{ $exception->justification }}
          </div>

          <div class="erp-kv-grid">
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Requester</span>
              <span class="erp-kv-val">{{ $exception->requester?->name ?? 'System' }}</span>
            </div>

            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Filed On</span>
              <span class="erp-kv-val">{{ $exception->created_at->format('d M Y, H:i') }}</span>
            </div>

            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Linked Transaction</span>
              <span class="erp-kv-val">
                @if ($exception->reference_type && $exception->reference_id)
                  <span class="erp-badge-info">
                    {{ strtoupper($exception->reference_type) }} #{{ $exception->reference_id }}
                  </span>
                @else
                  <span class="text-muted">General Policy Deviation</span>
                @endif
              </span>
            </div>

            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Deciding Authority Queue</span>
              <span class="erp-kv-val">{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
            </div>
          </div>

          @if ($exception->status !== 'pending' && $exception->status !== 'on_hold')
            <div class="p-3 rounded-2 mt-4 border d-flex align-items-center gap-2" style="background:{{ $exception->status === 'approved' ? '#f0fdf4' : '#fef2f2' }}; border-color:{{ $exception->status === 'approved' ? '#bbf7d0' : '#fecaca' }} !important;">
              <i class="bi {{ $exception->status === 'approved' ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i>
              <div style="font-size:13px; color:#1e293b;">
                <strong>{{ ucfirst($exception->status) }}</strong> by {{ $exception->decidedBy?->name ?? 'System' }} on {{ $exception->decided_at?->format('d M Y, H:i') }}.
              </div>
            </div>
          @elseif ($exception->status === 'on_hold')
            <div class="p-3 rounded-2 mt-4 border d-flex align-items-center gap-2" style="background:#fffbeb; border-color:#fde68a !important;">
              <i class="bi bi-pause-circle-fill text-warning fs-5"></i>
              <div style="font-size:13px; color:#92400e;">
                <strong>On Hold:</strong> This exception review is currently paused for clarification.
              </div>
            </div>
          @elseif (!$canDecide)
            <div class="p-3 rounded-2 mt-4 border d-flex align-items-center gap-2 bg-light">
              <i class="bi bi-hourglass-split text-warning fs-5"></i>
              <div style="font-size:13px; color:#64748b;">
                Awaiting review from {{ \App\Support\Roles::label($exception->approver_queue) }}. No action required from your role at this time.
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Hold Exception Modal -->
  <div class="modal fade" id="holdExceptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pause-circle text-warning me-2"></i>Put Exception On Hold</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('exceptions.hold', $exception) }}" method="POST">
          @csrf
          <div class="modal-body">
            <p class="text-muted small">Place this exception on hold while pending further information or governance review.</p>
            <div class="mb-3">
              <label class="form-label fw-semibold">Hold Reason / Comments <span class="text-danger">*</span></label>
              <textarea name="comments" class="form-control" rows="3" required placeholder="Specify why this exception is put on hold..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning"><i class="bi bi-pause-fill me-1"></i>Confirm Hold</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Detail' });
@endsection
