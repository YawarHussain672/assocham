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
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Reimbursement &amp; Advance Claims</span>
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
    
    <!-- Top Row: Claim Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(5,150,105,0.15),rgba(5,150,105,0.05)); color:#059669; border:1px solid rgba(5,150,105,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-wallet2 fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $reimbursement->claim_number }} — {{ $reimbursement->employee?->name ?? 'Claimant' }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              <span class="badge bg-light text-secondary border" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                Expense Settlement
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span><i class="bi bi-airplane text-secondary me-1"></i> {{ $reimbursement->trf ? 'Linked: ' . $reimbursement->trf->trf_number : 'Non-TRF Claim' }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-shield-check text-secondary me-1"></i> {{ ucfirst(str_replace('_', ' ', $reimbursement->routing_flag)) }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-cash-stack text-success me-1"></i> Claim: ₹{{ number_format($reimbursement->claim_amount, 2) }}</span>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          <span class="badge bg-light text-secondary border px-2 py-1" style="font-size:12px; font-weight:600;">
            <i class="bi bi-cash me-1"></i> Net: ₹{{ number_format($reimbursement->balance_amount, 2) }}
          </span>
          <a href="{{ route('grievance.create', ['reimbursement_id' => $reimbursement->id, 'category' => \App\Http\Controllers\GrievanceController::CATEGORY_SETTLEMENT]) }}" class="btn btn-sm btn-outline-danger fw-semibold">
            <i class="bi bi-chat-square-text me-1"></i> Raise Grievance
          </a>
        </div>
      </div>
    </div>

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval)
      @php
        $currentStageTitle = \App\Support\Roles::label($pendingApproval->role_required);
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
                    Stage: {{ $currentStageTitle }}
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Verify expense invoices, travel advance deduction, and policy allowance limits before authorizing this reimbursement claim.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (No Inline Input) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <!-- Approve Button Form -->
              <form method="POST" action="{{ route('reimb.approve', $reimbursement) }}" class="m-0">
                @csrf
                <input type="hidden" name="comments" value="Settlement audited and approved.">
                <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-check2-circle fs-6"></i>
                  <span>Approve &amp; Release Settlement</span>
                </button>
              </form>

              <!-- Decline Button (Triggers Clean Modal) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#declineReimbModal">
                <i class="bi bi-x-circle fs-6"></i>
                <span>Decline Claim</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Financial Audit Timeline Card (On Top) -->
  @php
    $approvals = $reimbursement->approvals->sortByDesc('id')->unique('level')->sortBy('level');
    $reimbApprovedCount = $approvals->where('status', 'approved')->count();
    $reimbTotalCount = $approvals->count();
    $isReimbFullyApproved = $reimbTotalCount > 0 && $reimbApprovedCount === $reimbTotalCount;
    $isReimbRejected = $approvals->where('status', 'rejected')->isNotEmpty() || $reimbursement->status === 'rejected';
    $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;
  @endphp
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Financial Audit Timeline</h6>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Claim Lodged -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Expense Claim Lodged">
              <i class="bi bi-check-lg"></i>
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
                @if ($isApproved)
                  <i class="bi bi-check-lg"></i>
                @elseif ($isRejected)
                  <i class="bi bi-x-lg"></i>
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
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    @if ($isApproved) Cleared
                    @elseif ($isRejected) Declined
                    @elseif ($isCurrentDue) In Review
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
                  @else
                    Pending
                  @endif
                </div>
                @if ($approval->comments)
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}" title="{{ $approval->comments }}">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- Final Step: Accounts Disbursement -->
          @php
            if ($isReimbFullyApproved) {
              $disbStatus = 'is-done';
              $disbIcon = 'done';
              $disbCard = 'done-card';
            } elseif ($isReimbRejected) {
              $disbStatus = 'is-stopped';
              $disbIcon = 'upcoming';
              $disbCard = 'stopped-card';
            } else {
              $disbStatus = 'is-stopped';
              $disbIcon = 'upcoming';
              $disbCard = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $disbStatus }}">
            <div class="h-step-icon {{ $disbIcon }}">
              <i class="bi {{ $isReimbFullyApproved ? 'bi-check-all' : 'bi-cash-stack' }}"></i>
            </div>
            <div class="h-step-card {{ $disbCard }}">
              <div class="h-step-title {{ $isReimbFullyApproved ? 'text-success' : 'text-muted' }} mb-1" title="Accounts Disbursement">
                Disbursement
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isReimbFullyApproved ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $isReimbFullyApproved ? 'Cleared' : 'Pending Settlement' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($isReimbFullyApproved)
                  <span class="text-success fw-semibold">Queued for Bank Payout</span>
                @else
                  <span class="text-muted">Accounts Handover</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $isReimbFullyApproved ? 'Ready' : 'Post-Approval' }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Left Column: Claim Financial Details -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
              <i class="bi bi-cash-stack"></i>
            </div>
            <h6 class="mb-0 fw-bold text-dark">Settlement Financial Breakdown</h6>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">
            Auto-Calculated
          </span>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-receipt text-secondary"></i> Total Claim Amount</div>
                <div class="field-value text-dark" style="font-size:15.5px; font-weight:600;">₹{{ number_format($reimbursement->claim_amount, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Submitted expenditure bills</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-arrow-down-right-circle text-secondary"></i> Advance Disbursed</div>
                <div class="field-value text-secondary" style="font-size:15.5px; font-weight:600;">₹{{ number_format($reimbursement->advance_amount, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Previously sanctioned advance</div>
              </div>
            </div>

            @php
              $netBal = (float) ($reimbursement->balance_amount ?? 0);
              $isNilBal = abs($netBal) < 0.01;
              $balBg = $isNilBal ? '#f0fdfa' : ($netBal > 0 ? '#ecfdf5' : '#fef2f2');
              $balBorder = $isNilBal ? '#99f6e4' : ($netBal > 0 ? '#a7f3d0' : '#fecaca');
              $balColor = $isNilBal ? '#0f766e' : ($netBal > 0 ? '#059669' : '#c8102e');
              $balBadgeBg = $isNilBal ? '#0d9488' : ($netBal > 0 ? '#10b981' : '#ef4444');
              $balLabel = $isNilBal ? 'Nil Balance (Adjusted)' : ($netBal > 0 ? 'Payable to Traveler' : 'Refundable by Traveler');
            @endphp
            <div class="col-12">
              <div class="p-3 rounded-3" style="background:{{ $balBg }}; border:1px solid {{ $balBorder }};">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="field-label d-flex align-items-center gap-1 mb-1" style="color:#334155;">
                      <i class="bi bi-calculator text-secondary"></i> Net Balance Settlement
                    </div>
                    <div style="font-size:17px; font-weight:600; color:{{ $balColor }};">
                      ₹{{ number_format(abs($netBal), 2) }}
                    </div>
                  </div>
                  <span class="badge" style="background:{{ $balBadgeBg }}; color:#fff; font-weight:600; font-size:12px; padding:4px 9px;">
                    {{ $balLabel }}
                  </span>
                </div>
                @if ($reimbursement->status === 'approved' && $reimbursement->trf)
                  <div class="mt-2 pt-2 border-top small fw-medium text-success" style="border-color: {{ $balBorder }} !important; font-size:12px;">
                    <i class="bi bi-shield-check me-1"></i>Settled &amp; Reconciled — {{ $reimbursement->trf->trf_number }} advance adjusted and travel block cleared.
                  </div>
                @endif
              </div>
            </div>
            @if ($reimbursement->trf && $reimbursement->trf->flightLogs->isNotEmpty())
              <div class="col-12 mt-2">
                <div class="dossier-field-box">
                  <div class="field-label d-flex align-items-center justify-content-between mb-2">
                    <span><i class="bi bi-airplane text-primary me-1"></i> Linked Corporate Travel Logistics ({{ $reimbursement->trf->trf_number }})</span>
                    <span class="fw-bold text-dark">Total: ₹{{ number_format((float) $reimbursement->trf->flightLogs->sum('cost'), 2) }}</span>
                  </div>
                  <div class="d-flex flex-column gap-1.5">
                    @foreach ($reimbursement->trf->flightLogs as $fLog)
                      <div class="d-flex flex-wrap align-items-center justify-content-between small py-1 border-bottom border-light-subtle">
                        <div>
                          <span class="fw-semibold font-monospace text-primary">{{ $fLog->ref }}</span>
                          <span class="text-muted mx-1">&bull;</span>
                          <span class="text-dark">{{ $fLog->type }}</span>
                          <span class="text-muted ms-1">({{ $fLog->vendor->company_name ?? 'Direct Booking' }})</span>
                        </div>
                        <span class="fw-semibold text-dark">₹{{ number_format((float) $fLog->cost, 2) }}</span>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif
            @if ($reimbursement->items->isNotEmpty())
              <div class="col-12 mt-2">
                <div class="dossier-field-box p-3">
                  <div class="field-label d-flex align-items-center justify-content-between mb-2">
                    <span><i class="bi bi-list-check text-primary me-1"></i> Itemised Expense Breakdown ({{ $reimbursement->items->count() }})</span>
                    <span class="fw-bold text-dark">Total: ₹{{ number_format((float) $reimbursement->items->sum('amount'), 2) }}</span>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="font-size:12px;">
                      <thead class="table-light">
                        <tr style="font-size:11px; text-transform:uppercase; color:#64748b;">
                          <th>Date</th>
                          <th>Category</th>
                          <th>Particulars</th>
                          <th class="text-end">Amount</th>
                          <th class="text-end">Bill</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($reimbursement->items as $item)
                          <tr>
                            <td class="text-nowrap text-muted">{{ optional($item->expense_date)->format('d M Y') }}</td>
                            <td class="fw-semibold text-dark">{{ $item->category }}</td>
                            <td class="text-muted">{{ $item->description ?: '—' }}</td>
                            <td class="text-end fw-semibold text-dark text-nowrap">₹{{ number_format((float) $item->amount, 2) }}</td>
                            <td class="text-end text-nowrap">
                              @if ($item->bill_path)
                                <a href="{{ route('reimb.items.bill', [$reimbursement, $item]) }}" target="_blank" class="btn btn-sm btn-outline-primary px-2 py-1" style="font-size:11px;">
                                  <i class="bi bi-paperclip me-1"></i>View Bill
                                </a>
                              @else
                                <span class="text-muted" style="font-size:11px;">No bill</span>
                              @endif
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            @endif
            @if ($reimbursement->attachments->isNotEmpty())
              <div class="col-12 mt-2">
                <div class="dossier-field-box">
                  <div class="field-label d-flex align-items-center justify-content-between mb-2">
                    <span><i class="bi bi-paperclip text-primary me-1"></i> Attached Tax Invoices &amp; Receipts ({{ $reimbursement->attachments->count() }})</span>
                  </div>
                  <div class="d-flex flex-column gap-2">
                    @foreach ($reimbursement->attachments as $att)
                      <div class="d-flex flex-wrap align-items-center justify-content-between small py-1 border-bottom border-light-subtle">
                        <div class="text-dark fw-medium">
                          <i class="bi bi-file-earmark-text text-secondary me-1"></i>
                          {{ $att->original_name ?: basename($att->file_path) }}
                        </div>
                        <a href="{{ route('reimb.attachments.show', [$reimbursement, $att]) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size: 11.5px;">
                          View File
                        </a>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif
            @if ($reimbursement->reason)
              <div class="col-12 mt-3">
                <div class="dossier-field-box">
                  <div class="field-label mb-1"><i class="bi bi-info-circle text-secondary me-1"></i> Claim Justification / Reason</div>
                  <div class="text-dark" style="font-size:13.5px; font-weight:600;">{{ $reimbursement->reason }}</div>
                </div>
              </div>
            @endif

            @php
              $visibleGrievances = $reimbursement->grievances->filter(function ($ticket) {
                $user = auth()->user();
                return $user && (
                  (int) $ticket->raised_by === (int) $user->id
                  || in_array($user->role, [\App\Support\Roles::HOD, \App\Support\Roles::ADMIN_HEAD, \App\Support\Roles::CFO, \App\Support\Roles::SG], true)
                );
              });
            @endphp
            @if ($visibleGrievances->isNotEmpty())
              <div class="col-12 mt-3">
                <div class="dossier-field-box">
                  <div class="field-label mb-2"><i class="bi bi-chat-square-text text-danger me-1"></i> Raised Settlement Grievances ({{ $visibleGrievances->count() }})</div>
                  <div class="d-flex flex-column gap-2">
                    @foreach ($visibleGrievances as $ticket)
                      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small py-1 border-bottom border-light-subtle">
                        <div>
                          <span class="font-monospace fw-bold text-dark">{{ $ticket->ticket_number }}</span>
                          <span class="text-dark fw-semibold ms-1">{{ $ticket->subject }}</span>
                          <span class="badge {{ $ticket->status === 'resolved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }} ms-1">
                            {{ $ticket->status === 'resolved' ? 'Resolved' : 'With ' . \App\Support\Roles::label($ticket->current_owner_role) }}
                          </span>
                        </div>
                        <a href="{{ route('grievance.show', $ticket) }}" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size:11.5px;">View Ticket</a>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif
          </div>

          <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
            <span>Claimant: {{ $reimbursement->employee?->name ?? '—' }}</span>
            <span>Department: {{ $reimbursement->employee?->department ?? ($reimbursement->trf?->employee?->department ?? 'General') }}</span>
          </div>

        </div>
      </div>
    </div>
  </div>

  @if ($pendingApproval)
    <!-- Decline Reimbursement Modal (Small, Clean & Minimal Window) -->
    <div class="modal fade" id="declineReimbModal" tabindex="-1" aria-labelledby="declineReimbModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('reimb.reject', $reimbursement) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <div class="d-flex align-items-center gap-2 text-danger">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="declineReimbModalLabel">Decline Reimbursement Claim</h5>
            </div>
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
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-x-circle"></i>
              <span>Confirm Decline</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Claim Detail' });
@endsection
