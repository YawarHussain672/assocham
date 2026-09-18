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

  <!-- Header Card -->
  <div class="detail-header-card d-flex align-items-center justify-content-between p-3 rounded-3" style="background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 16px -2px rgba(15,23,42,0.04);">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#059669,#34d399); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-wallet2"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $reimbursement->claim_number }} — {{ $reimbursement->employee->name }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Expense Settlement
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
          <span><i class="bi bi-airplane"></i> {{ $reimbursement->trf ? 'Linked: ' . $reimbursement->trf->trf_number : 'Non-TRF Claim' }}</span>
          <span><i class="bi bi-shield-check"></i> {{ ucfirst(str_replace('_', ' ', $reimbursement->routing_flag)) }}</span>
        </div>
      </div>
    </div>
    <div>
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:13px; font-weight:600; padding:6px 12px; border-radius:20px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4 mt-2">
    <!-- Left Column: Claim Financial Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-cash-stack" style="color:var(--maroon);"></i>
              <span>Settlement Financial Breakdown</span>
            </div>
            <span class="badge" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:12px; padding:5px 10px;">
              Auto-Calculated
            </span>
          </div>

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

            <div class="col-12">
              <div class="p-3 rounded-3" style="background:{{ $reimbursement->balance_amount >= 0 ? '#ecfdf5' : '#fef2f2' }}; border:1px solid {{ $reimbursement->balance_amount >= 0 ? '#a7f3d0' : '#fecaca' }};">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="field-label d-flex align-items-center gap-1 mb-1" style="color:#334155;">
                      <i class="bi bi-calculator text-secondary"></i> Net Balance Settlement
                    </div>
                    <div style="font-size:17px; font-weight:600; color:{{ $reimbursement->balance_amount >= 0 ? '#059669' : '#c8102e' }};">
                      ₹{{ number_format(abs($reimbursement->balance_amount), 2) }}
                    </div>
                  </div>
                  <span class="badge" style="background:{{ $reimbursement->balance_amount >= 0 ? '#10b981' : '#ef4444' }}; color:#fff; font-weight:600; font-size:12px; padding:4px 9px;">
                    {{ $reimbursement->balance_amount >= 0 ? 'Payable to Traveler' : 'Refundable by Traveler' }}
                  </span>
                </div>
              </div>
            </div>
            @if ($reimbursement->reason)
              <div class="col-12 mt-3">
                <div class="dossier-field-box">
                  <div class="field-label mb-1"><i class="bi bi-info-circle text-secondary me-1"></i> Claim Justification / Reason</div>
                  <div class="text-dark" style="font-size:13.5px; font-weight:600;">{{ $reimbursement->reason }}</div>
                </div>
              </div>
            @endif
          </div>
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Claimant: {{ $reimbursement->employee?->name ?? '—' }}</span>
          <span>Department: {{ $reimbursement->travelRequisition->department }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Colored Icon Timeline & Financial Endorsement (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Financial Audit Timeline</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              {{ $reimbursement->approvals->where('status', 'approved')->count() }} / {{ $reimbursement->approvals->count() }} Signed
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($reimbursement->approvals->sortBy('level') as $i => $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending' : 'upcoming'));
                $iconBi = $isApproved ? 'bi-check-lg' : ($isRejected ? 'bi-x-lg' : ($isPending ? 'bi-clock-history' : 'bi-circle'));
                $sm = $stepMeta[$approval->status] ?? $stepMeta['pending'];
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    <i class="bi {{ $iconBi }}"></i>
                  </div>
                  @if ($i < $reimbursement->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">{{ \App\Support\Roles::label($approval->role_required) }}</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Clearance Granted</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting audit verification</span>
                        @else
                          <span class="text-muted"><i class="bi bi-dash-circle"></i> Upcoming tier</span>
                        @endif
                      </div>
                    </div>
                    <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }}; font-weight:600; font-size:11.5px; padding:3px 8px;">
                      {{ $sm['label'] }}
                    </span>
                  </div>
                  @if ($approval->comments)
                    <div class="timeline-step-comment">
                      <i class="bi bi-chat-left-quote me-1 text-muted"></i> {{ $approval->comments }}
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <!-- Decision Box -->
        @if ($pendingApproval)
          <div class="mt-3 pt-3 border-top" style="border-color:#f1f5f9 !important;">
            <div class="field-label mb-2 d-flex align-items-center gap-1" style="font-size:13px; font-weight:600;">
              <i class="bi bi-pen" style="color:var(--maroon);"></i>
              <span>Your Action ({{ \App\Support\Roles::label($pendingApproval->role_required) }})</span>
            </div>
            <form method="POST" action="{{ route('reimb.approve', $reimbursement) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Comments (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve &amp; Release Settlement</span>
              </button>
            </form>
            <form method="POST" action="{{ route('reimb.reject', $reimbursement) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Claim</span>
              </button>
            </form>
          </div>
        @elseif (in_array($reimbursement->status, ['pending_cfo', 'pending_sg']))
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting finance executive signoff — no action pending on your account.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'Claim Detail' });
@endsection
