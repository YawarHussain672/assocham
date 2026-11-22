@extends('layouts.app')

@section('title', $reimbursement->claim_number)

@php
  $statusMeta = [
    'pending_cfo' => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved — Settled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$reimbursement->status] ?? $statusMeta['pending_cfo'];

  $approvals = $reimbursement->approvals->sortByDesc('id')->unique('level')->sortBy('level');
  $reimbApprovedCount = $approvals->where('status', 'approved')->count();
  $reimbTotalCount = $approvals->count();
  $isReimbFullyApproved = $reimbTotalCount > 0 && $reimbApprovedCount === $reimbTotalCount;
  $isReimbRejected = $approvals->where('status', 'rejected')->isNotEmpty() || $reimbursement->status === 'rejected';
  $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

  $netBal = (float) ($reimbursement->balance_amount ?? 0);
  $isNilBal = abs($netBal) < 0.01;
  $balColor = $isNilBal ? '#0f766e' : ($netBal > 0 ? '#059669' : '#b0263a');
  $balBadgeClass = $isNilBal
    ? 'bg-secondary-subtle text-secondary border border-secondary-subtle'
    : ($netBal > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
  $balLabel = $isNilBal ? 'Nil Balance (Adjusted)' : ($netBal > 0 ? 'Payable to Traveler' : 'Refundable by Traveler');

  $visibleGrievances = $reimbursement->grievances->filter(function ($ticket) {
    $user = auth()->user();
    return $user && (
      (int) $ticket->raised_by === (int) $user->id
      || in_array($user->role, [\App\Support\Roles::HOD, \App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::SG], true)
    );
  });
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <span>&larr; Back to Reimbursement &amp; Advance Claims</span>
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

  @if ($reimbursement->status === 'on_hold')
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-3" style="border-radius: 8px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-dark fs-6 mb-0.5">This Reimbursement Claim is Currently On Hold</div>
          <div class="text-secondary small">
            Review is temporarily paused pending expense receipts or claim clarifications.
          </div>
        </div>
      </div>
      <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card (Vendor Dossier Style) -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">

    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="text-start">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $reimbursement->claim_number }}</span>
            <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
              {{ $meta['label'] }}
            </span>
          </div>
          <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
            <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Claimant:</span> <strong>{{ $reimbursement->employee?->name ?? 'Claimant' }}</strong></span>
            @if ($reimbursement->trf)
              <span class="detail-meta-chip">
                <i class="bi bi-airplane"></i>
                <span class="meta-label">TRF:</span>
                @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
                  <a href="{{ route('trf.show', $reimbursement->trf) }}">{{ $reimbursement->trf->trf_number }}</a>
                @else
                  <strong>{{ $reimbursement->trf->trf_number }}</strong>
                @endif
              </span>
            @endif
            <span class="detail-meta-chip"><i class="bi bi-cash-stack"></i> <span class="meta-label">Claim Amount:</span> <strong>₹{{ number_format($reimbursement->claim_amount, 2) }}</strong></span>
            <span class="detail-meta-chip"><i class="bi bi-wallet2"></i> <span class="meta-label">Net Settlement:</span> <strong>₹{{ number_format($reimbursement->balance_amount, 2) }}</strong></span>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @if ($pendingApproval)
            <!-- On Hold Button -->
            <button type="button" class="btn btn-sm btn-warning px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap text-dark" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#holdReimbModal">
              <i class="bi bi-pause-circle"></i>
              <span>On Hold</span>
            </button>

            <!-- Approve Button -->
            <form method="POST" action="{{ route('reimb.approve', $reimbursement) }}" class="m-0">
              @csrf
              <input type="hidden" name="comments" value="Settlement audited and approved.">
              <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-check-lg"></i>
                <span>Approve &amp; Release Settlement</span>
              </button>
            </form>

            <!-- Decline / Reject Button -->
            <button type="button" class="btn btn-sm btn-outline-danger px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#declineReimbModal">
              <i class="bi bi-x-lg"></i>
              <span>Reject</span>
            </button>
          @endif

          <a href="{{ route('grievance.create', ['reimbursement_id' => $reimbursement->id, 'category' => \App\Http\Controllers\GrievanceController::CATEGORY_SETTLEMENT]) }}" class="btn btn-sm btn-outline-danger fw-semibold px-3 py-1.5" style="font-size: 13px; border-radius: 6px;">
            Raise Grievance
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Horizontal Financial Audit Timeline Card (Clean, Text/Step Numbers Only) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Claim Lodged -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Expense Claim Lodged">
              <span class="fw-bold" style="font-size: 11px;">1</span>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Claim Lodged</div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Submitted
                </span>
              </div>
              <div class="h-step-person" title="{{ $reimbursement->employee?->name ?? 'Claimant' }}">
                {{ $reimbursement->employee?->name ?? 'Claimant' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $reimbursement->submitted_at?->format('d M, H:i') ?? $reimbursement->created_at?->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Multi-Tier Approval Stages -->
          @foreach ($approvals as $approval)
            @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';
              $isCurrentDue = (!$isReimbRejected && $isPending && $approval->level === $activePendingLevel);
              $roleShort = \App\Support\Roles::label($approval->role_required);

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
                <span class="fw-bold" style="font-size: 11px;">{{ $loop->iteration + 1 }}</span>
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
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
                  @else
                    Pending
                  @endif
                </div>
                @if ($approval->comments)
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}"
                       data-bs-toggle="modal"
                       data-bs-target="#reimbApprovalCommentModal-{{ $approval->id }}"
                       title="Click to view full comment">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- Final Step: Accounts Disbursement -->
          @php
            $disbDone = $isReimbFullyApproved || $reimbursement->status === 'approved';
            $disbStatus = $disbDone ? 'is-done' : 'is-stopped';
            $disbIcon = $disbDone ? 'done' : 'upcoming';
            $disbCard = $disbDone ? 'done-card' : 'stopped-card';
          @endphp
          <div class="h-timeline-step {{ $disbStatus }}">
            <div class="h-step-icon {{ $disbIcon }}">
              <span class="fw-bold" style="font-size: 11px;">{{ $approvals->count() + 2 }}</span>
            </div>
            <div class="h-step-card {{ $disbCard }}">
              <div class="h-step-title {{ $disbDone ? 'text-success' : 'text-muted' }} mb-1">
                Disbursement
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $disbDone ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $disbDone ? 'Cleared' : 'Pending Settlement' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($disbDone)
                  <span class="text-success fw-semibold">Settled</span>
                @else
                  <span class="text-muted">Accounts Handover</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $disbDone ? 'Reconciled' : 'Post-Approval' }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Main Settlement Dossier (Matching Vendor Dossier UI) -->
  <div class="row g-4 align-items-start">
    <div class="col-12">
      <div class="card shadow-sm border-0 p-4" style="border: 1px solid #e2e8f0 !important; border-radius: 10px; background: #ffffff;">
        <div class="pb-3 mb-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color: #e2e8f0 !important;">
          <h2 class="h5 mb-0 fw-bold text-dark" style="font-size: 19px; letter-spacing: -0.01em;">Settlement Financial Breakdown</h2>
          <span class="badge bg-light text-secondary border px-2.5 py-1 fw-semibold" style="font-size: 11.5px; border-color: #e2e8f0 !important;">
            Auto-Calculated
          </span>
        </div>

        <!-- Claimant & Financial Summary -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Claimant &amp; Financial Summary</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Claimant</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $reimbursement->employee?->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Department</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px; color: #0f172a;">{{ $reimbursement->employee?->department ?? ($reimbursement->trf?->employee?->department ?? 'General') }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Linked Travel Reference</div>
              <div class="text-dark font-monospace fw-semibold" style="font-size: 13px;">
                @if ($reimbursement->trf)
                  <a href="{{ route('trf.show', $reimbursement->trf) }}" class="text-primary text-decoration-none">{{ $reimbursement->trf->trf_number }}</a>
                @else
                  Non-TRF Claim
                @endif
              </div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Total Claim Amount</div>
              <div class="text-dark font-monospace fw-bold" style="font-size: 15px; color: #0f172a;">₹{{ number_format($reimbursement->claim_amount, 2) }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Advance Disbursed</div>
              <div class="text-secondary font-monospace fw-semibold" style="font-size: 15px;">₹{{ number_format($reimbursement->advance_amount, 2) }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Net Balance Settlement</div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="font-monospace fw-bold" style="font-size: 15px; color: {{ $balColor }};">₹{{ number_format(abs($netBal), 2) }}</span>
                <span class="badge {{ $balBadgeClass }} fw-semibold" style="font-size: 11px;">{{ $balLabel }}</span>
              </div>
            </div>

            @if ($reimbursement->status === 'approved' && $reimbursement->trf)
              <div class="col-12">
                <div class="text-success small fw-semibold pt-2 border-top" style="font-size: 12.5px; border-color: #e2e8f0 !important;">
                  Settled &amp; Reconciled — {{ $reimbursement->trf->trf_number }} advance adjusted and travel block cleared.
                </div>
              </div>
            @endif

            @if ($reimbursement->reason)
              <div class="col-12">
                <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500; color: #64748b;">Claim Justification / Reason</div>
                <div class="text-dark" style="font-size: 13px; line-height: 1.5; color: #0f172a;">{{ $reimbursement->reason }}</div>
              </div>
            @endif
          </div>
        </div>

        <!-- Optional Section: Itemised Expense Breakdown -->
        @if ($reimbursement->items->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Itemised Expense Breakdown ({{ $reimbursement->items->count() }})</h3>
              <span class="font-monospace fw-bold text-dark" style="font-size: 13px;">Total: ₹{{ number_format((float) $reimbursement->items->sum('amount'), 2) }}</span>
            </div>

            <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
              <table class="table table-hover align-middle mb-0 w-100" style="font-size: 12.5px;">
                <thead class="table-light">
                  <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc;">
                    <th class="ps-3">Date</th>
                    <th>Category</th>
                    <th>Particulars</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end pe-3">Bill</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($reimbursement->items as $item)
                    <tr>
                      <td class="text-nowrap text-muted ps-3 py-2.5">{{ optional($item->expense_date)->format('d M Y') }}</td>
                      <td class="fw-semibold text-dark py-2.5">{{ $item->category }}</td>
                      <td class="text-muted py-2.5">{{ $item->description ?: '—' }}</td>
                      <td class="text-end font-monospace fw-semibold text-dark text-nowrap py-2.5">₹{{ number_format((float) $item->amount, 2) }}</td>
                      <td class="text-end text-nowrap pe-3 py-2.5">
                        @if ($item->bill_path)
                          <a href="{{ route('reimb.items.bill', [$reimbursement, $item]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;">
                            View Bill
                          </a>
                        @else
                          <span class="text-muted" style="font-size: 11.5px;">No bill</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

        <!-- Optional Section: Linked Corporate Travel Logistics -->
        @if ($reimbursement->trf && $reimbursement->trf->flightLogs->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Linked Corporate Travel Logistics ({{ $reimbursement->trf->trf_number }})</h3>
              <span class="font-monospace fw-bold text-dark" style="font-size: 13px;">Total: ₹{{ number_format((float) $reimbursement->trf->flightLogs->sum('cost'), 2) }}</span>
            </div>

            <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
              <table class="table table-hover align-middle mb-0 w-100" style="font-size: 12.5px;">
                <thead class="table-light">
                  <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; background: #f8fafc;">
                    <th class="ps-3">Booking Ref</th>
                    <th>Type</th>
                    <th>Travel Partner</th>
                    <th class="text-end pe-3">Cost</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($reimbursement->trf->flightLogs as $fLog)
                    <tr>
                      <td class="font-monospace fw-semibold text-primary text-nowrap ps-3 py-2.5">{{ $fLog->ref }}</td>
                      <td class="text-dark py-2.5">{{ $fLog->type }}</td>
                      <td class="text-muted py-2.5">{{ $fLog->vendor->company_name ?? 'Direct Booking' }}</td>
                      <td class="text-end font-monospace fw-semibold text-dark text-nowrap pe-3 py-2.5">₹{{ number_format((float) $fLog->cost, 2) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

        <!-- Optional Section: Attached Tax Invoices & Receipts -->
        @if ($reimbursement->attachments->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-4">
            <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Attached Tax Invoices &amp; Receipts ({{ $reimbursement->attachments->count() }})</h3>
            </div>

            <div class="d-flex flex-column gap-2">
              @foreach ($reimbursement->attachments as $att)
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2.5 border rounded-2 bg-white" style="font-size: 13px; border-color: #e2e8f0 !important;">
                  <span class="text-dark fw-medium"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>{{ $att->original_name ?: basename($att->file_path) }}</span>
                  <a href="{{ route('reimb.attachments.show', [$reimbursement, $att]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;">
                    View File
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        <!-- Optional Section: Raised Settlement Grievances -->
        @if ($visibleGrievances->isNotEmpty())
          <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>
          <div class="mb-0">
            <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
              <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Raised Settlement Grievances ({{ $visibleGrievances->count() }})</h3>
            </div>

            <div class="d-flex flex-column gap-2">
              @foreach ($visibleGrievances as $ticket)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2.5 border rounded-2 bg-white" style="font-size: 13px; border-color: #e2e8f0 !important;">
                  <div>
                    <span class="font-monospace fw-bold text-dark">{{ $ticket->ticket_number }}</span>
                    <span class="text-dark fw-semibold ms-1">{{ $ticket->subject }}</span>
                    <span class="badge {{ $ticket->status === 'resolved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }} ms-1">
                      {{ $ticket->status === 'resolved' ? 'Resolved' : 'With ' . \App\Support\Roles::label($ticket->current_owner_role) }}
                    </span>
                  </div>
                  <a href="{{ route('grievance.show', $ticket) }}" class="btn btn-sm btn-outline-secondary px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;">
                    View Ticket
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>

      </div>
    </div>
  </div>

  @if ($pendingApproval)
    <!-- Place Reimbursement On Hold Modal -->
    <div class="modal fade" id="holdReimbModal" tabindex="-1" aria-labelledby="holdReimbModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('reimb.hold', $reimbursement) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdReimbModalLabel">Place Reimbursement Claim On Hold</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Placing claim <strong>{{ $reimbursement->claim_number }}</strong> on hold temporarily pauses audit review until missing receipts or trip expense clarifications are submitted.
            </p>
            <div class="mb-1">
              <label for="holdReimbCommentsInput" class="form-label fw-semibold text-dark small">Reason for Placing On Hold <span class="text-danger">*</span></label>
              <textarea name="comments" id="holdReimbCommentsInput" class="form-control" rows="3" placeholder="Provide reason for hold (e.g. Awaiting original boarding passes, taxi receipt verification)..." required></textarea>
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

    <!-- Decline Reimbursement Modal -->
    <div class="modal fade" id="declineReimbModal" tabindex="-1" aria-labelledby="declineReimbModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('reimb.reject', $reimbursement) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <h5 class="modal-title fw-bold text-danger fs-6 mb-0" id="declineReimbModalLabel">Decline Reimbursement Claim</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific reason for declining <strong>{{ $reimbursement->claim_number }}</strong>. This feedback will be recorded in the audit trail and returned to the claimant for revision.
            </p>
            <div class="mb-1">
              <label for="rejectCommentsInput" class="form-label fw-semibold text-dark small">Reason for Rejection <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectCommentsInput" class="form-control" rows="3" placeholder="e.g. Missing original taxi bills, expense exceeds daily limit, etc." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold shadow-sm">
              Confirm Decline
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- Reimbursement Approval Comments Modals --}}
  @foreach ($approvals as $approval)
    @if ($approval->comments)
      @php
        $stageTitle = $approval->role_name ?? ('Auditor (Tier ' . $approval->level . ')');
        $isAppr = $approval->status === 'approved';
        $isRej = $approval->status === 'rejected';
      @endphp
      <div class="modal fade" id="reimbApprovalCommentModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
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
                  <span class="badge {{ $isAppr ? 'bg-success-subtle text-success' : ($isRej ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis') }} border">
                    {{ ucfirst($approval->status) }}
                  </span>
                </div>
              </div>
              <div class="p-3.5 rounded-3 bg-white border text-dark shadow-2xs" style="font-size: 14px; font-weight: 500; line-height: 1.6; border-left: 4px solid {{ $isAppr ? 'var(--bs-success)' : ($isRej ? 'var(--bs-danger)' : 'var(--bs-warning)') }} !important;">
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
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Claim Detail' });
@endsection
