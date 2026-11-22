@extends('layouts.app')

@section('title', 'Annual Budget: ' . $budget->budget_number)

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending CFO Endorsement', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'badge' => 'warning'],
    'pending_sg'  => ['label' => 'Pending SG Sanction', 'bg' => '#E0F2FE', 'fg' => '#0369A1', 'badge' => 'info'],
    'approved'    => ['label' => 'Approved & Finalized', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F', 'badge' => 'success'],
    'on_hold'     => ['label' => 'On Hold', 'bg' => '#FEF3C7', 'fg' => '#B45309', 'badge' => 'warning'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A', 'badge' => 'danger'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B', 'badge' => 'secondary'],
  ];
  $meta = $statusMeta[$budget->status] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  {{-- Back Navigation Link --}}
  <a href="{{ route('annual-budget') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1.5 text-decoration-none">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Annual Budget Proposals</span>
  </a>

  {{-- Session Alerts --}}
  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 shadow-sm border-0 rounded-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 shadow-sm border-0 rounded-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 shadow-sm border-0 rounded-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  {{-- On Hold Alert Banner --}}
  @if ($budget->status === 'on_hold')
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-4" style="border-radius: 10px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.25rem;">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-dark fs-6 mb-0.5">This Annual Budget Proposal is Currently On Hold</div>
          <div class="text-secondary small">
            Review is paused at the current stage pending departmental clarifications or budget adjustments.
          </div>
        </div>
      </div>
      <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
    </div>
  @endif

  {{-- Unified Header & Action Dossier Card --}}
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100 position-relative" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
    <div class="p-3 p-md-4">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <h1 class="h5 mb-0 fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $budget->budget_number }}</h1>
            <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
              {{ $meta['label'] }}
            </span>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="detail-meta-chip"><i class="bi bi-building"></i> <span class="meta-label">Department:</span> <strong>{{ $budget->department }}</strong></span>
            <span class="detail-meta-chip"><i class="bi bi-calendar3"></i> <span class="meta-label">Fiscal Year:</span> <strong>{{ $budget->fiscal_year }}</strong></span>
            <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Submitted By:</span> <strong>{{ $budget->submitter?->name ?? '—' }}</strong></span>
            <span class="detail-meta-chip"><i class="bi bi-clock"></i> <span class="meta-label">Date:</span> <strong>{{ $budget->created_at->format('d M Y, h:i A') }}</strong></span>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
          @if ($canEdit)
            <a href="{{ route('annual-budget.edit', $budget) }}" class="btn btn-sm btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5" style="font-size: 13px; border-radius: 6px;">
              <i class="bi bi-pencil-square"></i>
              <span>Edit Proposal</span>
            </a>
          @endif
          @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'checks-balances'))
            <a href="{{ route('checks-balances', ['fy' => $budget->fiscal_year]) }}" class="btn btn-sm btn-outline-custom fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5" style="font-size: 13px; border-radius: 6px;">
              <i class="bi bi-shield-check text-warning"></i>
              <span>Budget Master</span>
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Standard Horizontal Approval Timeline Card --}}
  <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          {{-- Stage 0: HOD Submission --}}
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Submitted by HOD">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  HOD Submission
                </span>
              </div>
              <div class="h-step-person" title="{{ $budget->submitter?->name ?? 'HOD' }}">
                {{ $budget->submitter?->name ?? 'HOD' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $budget->created_at->format('d M, H:i') }}
              </div>
            </div>
          </div>

          {{-- Stage 1: CFO Endorsement --}}
          @php
            $isCfoDone = !empty($budget->cfo_approved_at);
            $isCfoActive = $budget->isPendingCfo();
            $isCfoRejected = $budget->isRejected() && $budget->current_approval_level === 1;
          @endphp
          <div class="h-timeline-step {{ $isCfoDone ? 'is-done' : ($isCfoActive ? 'is-active' : ($isCfoRejected ? 'is-rejected' : '')) }}">
            <div class="h-step-icon {{ $isCfoDone ? 'done' : ($isCfoActive ? 'active' : ($isCfoRejected ? 'rejected' : 'pending')) }}" title="CFO Endorsement">
              @if ($isCfoDone)
                <i class="bi bi-check-lg"></i>
              @elseif ($isCfoActive)
                <i class="bi bi-hourglass-split"></i>
              @elseif ($isCfoRejected)
                <i class="bi bi-x-lg"></i>
              @else
                <span>2</span>
              @endif
            </div>
            <div class="h-step-card {{ $isCfoDone ? 'done-card' : ($isCfoActive ? 'active-card' : ($isCfoRejected ? 'rejected-card' : 'pending-card')) }}">
              <div class="mb-1.5">
                @if ($isCfoDone)
                  <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Endorsed</span>
                @elseif ($isCfoActive)
                  <span class="badge bg-warning-subtle text-warning-emphasis border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">In Review</span>
                @elseif ($isCfoRejected)
                  <span class="badge bg-danger-subtle text-danger border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Rejected</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Pending</span>
                @endif
              </div>
              <div class="h-step-person" title="CFO Endorsement">
                CFO Endorsement
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($isCfoDone)
                  {{ $budget->cfo_approved_at->format('d M, H:i') }}
                @elseif ($isCfoActive)
                  Awaiting CFO
                @else
                  Sequential Queue
                @endif
              </div>
            </div>
          </div>

          {{-- Stage 2: SG Final Sanction --}}
          @php
            $isSgDone = !empty($budget->sg_approved_at);
            $isSgActive = $budget->isPendingSg();
            $isSgRejected = $budget->isRejected() && $budget->current_approval_level === 2;
          @endphp
          <div class="h-timeline-step {{ $isSgDone ? 'is-done' : ($isSgActive ? 'is-active' : ($isSgRejected ? 'is-rejected' : '')) }}">
            <div class="h-step-icon {{ $isSgDone ? 'done' : ($isSgActive ? 'active' : ($isSgRejected ? 'rejected' : 'pending')) }}" title="Secretary General Sanction">
              @if ($isSgDone)
                <i class="bi bi-check-lg"></i>
              @elseif ($isSgActive)
                <i class="bi bi-hourglass-split"></i>
              @elseif ($isSgRejected)
                <i class="bi bi-x-lg"></i>
              @else
                <span>3</span>
              @endif
            </div>
            <div class="h-step-card {{ $isSgDone ? 'done-card' : ($isSgActive ? 'active-card' : ($isSgRejected ? 'rejected-card' : 'pending-card')) }}">
              <div class="mb-1.5">
                @if ($isSgDone)
                  <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Approved</span>
                @elseif ($isSgActive)
                  <span class="badge bg-info-subtle text-info-emphasis border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Final Review</span>
                @elseif ($isSgRejected)
                  <span class="badge bg-danger-subtle text-danger border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Rejected</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Pending</span>
                @endif
              </div>
              <div class="h-step-person" title="Secretary General Sanction">
                Secretary General
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($isSgDone)
                  {{ $budget->sg_approved_at->format('d M, H:i') }}
                @elseif ($isSgActive)
                  Awaiting SG Sanction
                @else
                  Sequential Queue
                @endif
              </div>
            </div>
          </div>

          {{-- Stage 3: Budget Master Active --}}
          @php
            $isMasterActive = $budget->isApproved();
          @endphp
          <div class="h-timeline-step {{ $isMasterActive ? 'is-done' : '' }}">
            <div class="h-step-icon {{ $isMasterActive ? 'done' : 'pending' }}" title="Budget Master Live Ceiling">
              @if ($isMasterActive)
                <i class="bi bi-shield-check"></i>
              @else
                <span>4</span>
              @endif
            </div>
            <div class="h-step-card {{ $isMasterActive ? 'done-card' : 'pending-card' }}">
              <div class="mb-1.5">
                @if ($isMasterActive)
                  <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Active</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">Pending Activation</span>
                @endif
              </div>
              <div class="h-step-person" title="Budget Master Live">
                Budget Master Live
              </div>
              <div class="h-step-meta mt-0.5">
                @if ($isMasterActive)
                  Limit: ₹{{ number_format((float) ($budget->approved_amount ?: $budget->proposed_amount), 2) }}
                @else
                  Activates on Approval
                @endif
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- Interactive Action Box if Current User is the Pending Approver --}}
  @if ($pendingApproval)
    <div class="card shadow-sm border-0 mb-4" style="border: 1px solid #e2e8f0 !important; border-left: 4px solid #c8102e !important; border-radius: 10px; background: #ffffff;">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 border-bottom pb-3 mb-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 fw-semibold" style="font-size: 11px;">
                <i class="bi bi-clock-history me-1"></i>Action Required
              </span>
              <h6 class="fw-bold text-dark mb-0 fs-6">
                @if ($pendingApproval->level === 1)
                  Level 1: CFO Financial Endorsement
                @elseif ($pendingApproval->level === 2)
                  Level 2: Secretary General (SG) Final Sanction & Allocation
                @endif
              </h6>
            </div>
            <div class="text-muted small">
              You are authorized to review and decide on this annual budget proposal as {{ \App\Support\Roles::label(auth()->user()->role) }}.
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('annual-budget.approve', $budget) }}">
          @csrf

          @if ($pendingApproval->level === 2)
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Final Approved Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group input-group-sm" style="max-width: 340px;">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" step="0.01" min="1" max="999999999999" name="approved_amount" class="form-control fw-bold text-success" value="{{ old('approved_amount', $budget->approved_amount ?: $budget->proposed_amount) }}" required>
              </div>
              <div class="form-text text-muted" style="font-size: 11px;">You may maintain the proposed ₹{{ number_format((float) $budget->proposed_amount, 2) }} or modify the approved allocation ceiling.</div>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Remarks / Observations</label>
            <textarea name="comments" class="form-control form-control-sm" rows="2" placeholder="Optional notes, recommendations or institutional guidance..."></textarea>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="submit" class="btn btn-sm btn-success px-4 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" onclick="return confirm('Confirm approval for this annual budget proposal?');">
              <i class="bi bi-check-lg"></i>
              <span>
                @if ($pendingApproval->level === 1)
                  Endorse & Advance to SG (Level 2)
                @else
                  Finalize & Activate Budget Allocation
                @endif
              </span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalHold">
              <i class="bi bi-pause-circle"></i>
              <span>Put on Hold</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalReject">
              <i class="bi bi-x-circle"></i>
              <span>Reject Proposal</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- Details & Context Grid (Standard row g-4) --}}
  <div class="row g-4 mb-4">
    {{-- Left: Scope, Justification & Summary Cards --}}
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm border-0 mb-4 h-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
          <h6 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-file-text text-primary"></i>
            <span>Departmental Scope &amp; Justification</span>
          </h6>
        </div>
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div class="bg-light p-3 rounded-3 mb-4 text-dark border" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.6;">
            {{ $budget->justification }}
          </div>

          <div class="row g-3">
            <div class="col-6">
              <div class="p-3 rounded-3 border bg-light">
                <div class="text-muted small mb-1">Proposed Allocation Ceiling</div>
                <div class="fs-5 fw-bold text-dark">₹{{ number_format((float) $budget->proposed_amount, 2) }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 rounded-3 border bg-light">
                <div class="text-muted small mb-1">Final Approved Ceiling</div>
                <div class="fs-5 fw-bold {{ $budget->isApproved() ? 'text-success' : 'text-muted' }}">
                  @if ($budget->isApproved())
                    ₹{{ number_format((float) ($budget->approved_amount ?: $budget->proposed_amount), 2) }}
                  @else
                    Pending Final Sanction
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Right: Live Department Spend Context --}}
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm border-0 mb-4 h-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
          <h6 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-pie-chart text-warning"></i>
            <span>Live Department Spend Context</span>
          </h6>
        </div>
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          @if ($activeDeptBudget)
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                <span class="small fw-semibold text-secondary">Active Budget Limit ({{ $budget->fiscal_year }}):</span>
                <span class="fw-bold text-dark">₹{{ number_format((float) $activeDeptBudget->allocated_amount, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                <span class="small text-muted">Currently Utilised Spend:</span>
                <span class="fw-semibold text-danger">₹{{ number_format((float) $activeDeptBudget->utilised_amount, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted">Remaining Headroom:</span>
                <span class="fw-bold text-success">₹{{ number_format((float) $activeDeptBudget->remainingAmount(), 2) }}</span>
              </div>

              <div class="progress mb-1" style="height: 8px; border-radius: 4px;">
                <div class="progress-bar {{ $activeDeptBudget->isNearLimit() ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $activeDeptBudget->utilisationPercent()) }}%;"></div>
              </div>
              <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 11.5px;">
                <span>Utilization Rate</span>
                <span class="fw-semibold {{ $activeDeptBudget->isNearLimit() ? 'text-danger' : 'text-primary' }}">{{ $activeDeptBudget->utilisationPercent() }}% utilised</span>
              </div>
            </div>

            <div class="p-2.5 bg-light rounded-3 text-muted small border mt-3" style="font-size: 11.5px; line-height: 1.45;">
              <i class="bi bi-info-circle me-1 text-primary"></i>
              Finalizing this proposal will update the live ceiling from ₹{{ number_format((float) $activeDeptBudget->allocated_amount, 2) }} to ₹{{ number_format((float) ($budget->approved_amount ?: $budget->proposed_amount), 2) }}.
            </div>
          @else
            <div class="p-3 bg-light rounded-3 text-muted small text-center my-auto border">
              <i class="bi bi-calendar-plus d-block fs-4 mb-2 text-primary"></i>
              No active budget record exists for <strong>{{ $budget->department }}</strong> in <strong>{{ $budget->fiscal_year }}</strong>.
              Finalizing this proposal will seed the active budget master record automatically.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Itemized Expenditure Breakdown Table Card --}}
  @if ($budget->items->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-list-check text-primary"></i>
          <span>Itemized Expenditure Breakdown</span>
        </h6>
        <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
          {{ $budget->items->count() }} {{ Str::plural('Item', $budget->items->count()) }}
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light text-secondary text-uppercase" style="font-size: 11px; letter-spacing: 0.03em;">
              <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th style="width: 20%;">Category</th>
                <th style="width: 35%;">Item / Head Name</th>
                <th style="width: 20%;" class="text-end">Proposed Amount (₹)</th>
                <th style="width: 25%;">Remarks / Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($budget->items as $index => $item)
                <tr>
                  <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                  <td><span class="badge bg-light text-dark border px-2 py-0.5 rounded-pill">{{ $item->category }}</span></td>
                  <td class="fw-semibold text-dark">{{ $item->title }}</td>
                  <td class="text-end fw-semibold">₹{{ number_format((float) $item->amount, 2) }}</td>
                  <td class="text-muted small">{{ $item->notes ?: '—' }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr>
                <td colspan="3" class="text-end text-dark">Itemized Subtotal:</td>
                <td class="text-end text-primary" style="font-size: 14px;">₹{{ number_format((float) $budget->items->sum('amount'), 2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  @endif

  {{-- Governance & Audit Trail Card --}}
  <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
      <h6 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-journal-text text-primary"></i>
        <span>Governance &amp; Audit Trail</span>
      </h6>
      <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1 rounded-pill" style="font-size: 11px;">
        {{ $auditLogs->count() }} {{ Str::plural('event', $auditLogs->count()) }}
      </span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
          <thead class="table-light text-secondary text-uppercase" style="font-size: 10.5px; letter-spacing: 0.03em;">
            <tr>
              <th style="width: 20%;">Timestamp</th>
              <th style="width: 20%;">Actor</th>
              <th style="width: 15%;">Action</th>
              <th style="width: 45%;">Details</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($auditLogs as $log)
              <tr>
                <td class="text-muted">{{ $log->created_at->format('d M Y, h:i:s A') }}</td>
                <td class="fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</td>
                <td><span class="badge bg-light text-dark border text-uppercase px-2 py-0.5 rounded-pill">{{ $log->action }}</span></td>
                <td class="text-muted">{{ $log->detail }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  <i class="bi bi-clock-history d-block fs-4 mb-1 text-secondary opacity-50"></i>
                  No audit entries recorded yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- Modal: Put On Hold --}}
@if ($pendingApproval)
  <div class="modal fade" id="modalHold" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" action="{{ route('annual-budget.hold', $budget) }}" class="modal-content border-0 shadow-lg" style="border-radius: 10px;">
        @csrf
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-pause-circle text-warning fs-5"></i>
            <span>Put Budget Proposal on Hold</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-3">
          <p class="text-muted small mb-3">Specify the departmental queries or adjustments required before proceeding with governance review.</p>
          <div class="mb-2">
            <label class="form-label small fw-semibold text-secondary">Hold Reason / Clarifications <span class="text-danger">*</span></label>
            <textarea name="comments" class="form-control" rows="3" required placeholder="Detail the query or required departmental information..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-secondary fw-semibold px-3">Place on Hold</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal: Reject Proposal --}}
  <div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form method="POST" action="{{ route('annual-budget.reject', $budget) }}" class="modal-content border-0 shadow-lg" style="border-radius: 10px;">
        @csrf
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle text-danger fs-5"></i>
            <span>Reject Budget Proposal</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-3">
          <p class="text-muted small mb-3">Please provide the governance justification for rejecting this proposal so the department HOD can revise accordingly.</p>
          <div class="mb-2">
            <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
            <textarea name="comments" class="form-control" rows="3" required placeholder="State why this proposed budget allocation is rejected..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3">Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
@endif
@endsection

@section('init')
Shell.init({ activeNav: 'annual-budget', crumb: 'Governance & Controls', title: 'Annual Budget: {{ $budget->budget_number }}' });
@endsection
