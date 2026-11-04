@extends('layouts.app')

@section('title', $grn->grn_number)

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending — HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Closed — Verified', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Verified', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grn') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Goods Receipts (GRN)</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#059669,#10b981);">
        <i class="bi bi-box-seam"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $grn->grn_number }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Goods Receipt Note
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
          <span><i class="bi bi-receipt"></i> PO: {{ $grn->purchaseOrder->po_number }}</span>
          <span><i class="bi bi-shop"></i> {{ $grn->purchaseOrder->vendor->company_name }}</span>
          @if ($grn->created_at)
            <span><i class="bi bi-clock"></i> {{ $grn->created_at->format('d M Y') }}</span>
          @endif
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
    <!-- Left Column: Receipt Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-clipboard-check" style="color:var(--maroon);"></i>
              <span>Delivery &amp; Physical Verification</span>
            </div>
            <span class="badge" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; font-weight:600; font-size:12px; padding:5px 10px;">
              Qty: {{ $grn->received_qty }} / {{ $grn->po_qty }}
            </span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-box text-secondary"></i> Received Item</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $grn->item_description ?: 'Specified on PO' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-receipt-cutoff text-secondary"></i> Vendor Invoice / Challan</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $grn->invoice_number ?: 'Not specified' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-calendar3 text-secondary"></i> Receipt Inspection Date</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-person text-secondary"></i> Logged In Store By</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $grn->creator?->name ?? 'Store Executive' }}</div>
              </div>
            </div>
          </div>

          @if ($grn->quality_remarks)
            <div class="dossier-field-box mt-3">
              <div class="field-label mb-1"><i class="bi bi-chat-left-text text-secondary me-1"></i> Quality &amp; Verification Remarks</div>
              <div class="text-dark" style="font-size:13.5px; font-weight:600;">{{ $grn->quality_remarks }}</div>
            </div>
          @endif
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Accepted Qty: {{ $grn->qty_accepted ?? $grn->received_qty }}</span>
          <span>Status: {{ $meta['label'] }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Vertical Connected Timeline & HOD Verification (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div class="travel-timeline-header">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <div class="icon-bubble" style="background:#fef2f2; color:var(--maroon); width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px;">
                <i class="bi bi-diagram-3-fill"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold" style="font-size:14.5px; color:#0f172a;">Inspection &amp; Verification Rail</h6>
                <div class="text-muted" style="font-size:11.5px;">Store Receipt &bull; Physical Audit</div>
              </div>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11px; padding:4px 9px;">
              {{ $grn->status === 'approved' ? 'Inventory Closed' : ($grn->status === 'rejected' ? 'Delivery Rejected' : 'Awaiting Inspection') }}
            </span>
          </div>

          <!-- Micro Lifecycle Progress Bar -->
          @php
            $isApproved = $grn->status === 'approved';
            $isRejected = $grn->status === 'rejected';
            $isPending = $grn->status === 'pending_hod';
          @endphp
          <div class="travel-lifecycle-bar" title="GRN Verification Progress">
            <div class="bar-segment done" title="1. Store Inward"></div>
            <div class="bar-segment {{ $isApproved ? 'done' : ($isPending ? 'active' : '') }}" title="2. HOD Physical Signoff"></div>
            <div class="bar-segment {{ $isApproved ? 'done' : '' }}" title="3. Inventory Acceptance"></div>
          </div>
        </div>

        <div class="travel-timeline-body">
          <div class="workflow-timeline position-relative">

            <!-- Step 1: Store Receipt Inward -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon approved">
                  <i class="bi bi-box-seam"></i>
                </div>
                <div class="timeline-step-connector approved"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card approved-stage">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role text-success">Store Receipt Inward</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Inwarded
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $grn->creator?->name ?? 'Store Executive' }} ({{ $grn->received_qty }}/{{ $grn->po_qty }} units)</span>
                    <span>{{ $grn->receipt_date?->format('d M, H:i') ?? $grn->created_at?->format('d M, H:i') }}</span>
                  </div>
                  @if ($grn->invoice_number)
                    <div class="timeline-step-time text-muted mt-0.5" style="font-size:11px;">
                      <i class="bi bi-receipt-cutoff me-1"></i>Doc / Invoice: <strong>{{ $grn->invoice_number }}</strong>
                    </div>
                  @endif
                </div>
              </div>
            </div>

            <!-- Step 2: HOD Physical Signoff -->
            @foreach ($grn->approvals->sortBy('level') as $approval)
              @php
                $stepApproved = $approval->status === 'approved';
                $stepRejected = $approval->status === 'rejected';
                $stepPending  = $approval->status === 'pending';
                $cardStageClass = $stepPending ? 'active-stage' : ($stepApproved ? 'approved-stage' : '');
                $iconClass = $stepApproved ? 'approved' : ($stepRejected ? 'rejected' : ($stepPending ? 'pending pulse-ring' : 'upcoming'));
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    @if ($stepApproved)
                      <i class="bi bi-check-lg"></i>
                    @elseif ($stepRejected)
                      <i class="bi bi-x-lg"></i>
                    @elseif ($stepPending)
                      <i class="bi bi-hourglass-split"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                  <div class="timeline-step-connector {{ $stepApproved ? 'approved' : '' }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $cardStageClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $stepApproved ? 'text-success' : ($stepRejected ? 'text-danger' : ($stepPending ? 'text-primary' : 'text-muted')) }}">
                        Department Head (HOD) Verification
                      </span>
                      @if ($stepApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Verified
                        </span>
                      @elseif ($stepRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Rejected
                        </span>
                      @elseif ($stepPending)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                          Pending
                        </span>
                      @else
                        <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                          Upcoming
                        </span>
                      @endif
                    </div>

                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>{{ $approval->approver?->name ?? 'Assigned Department HOD' }}</span>
                      @if ($stepApproved && $approval->acted_at)
                        <span>{{ $approval->acted_at->format('d M, H:i') }}</span>
                      @elseif ($stepRejected && $approval->acted_at)
                        <span>{{ $approval->acted_at->format('d M, H:i') }}</span>
                      @elseif ($stepPending)
                        <span class="text-warning">Awaiting audit</span>
                      @else
                        <span>&mdash;</span>
                      @endif
                    </div>

                    @if ($approval->comments)
                      <div class="timeline-quote-bubble mt-1">
                        &ldquo;{{ $approval->comments }}&rdquo;
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach

            <!-- Step 3: Acceptance & PO Settlement Handover -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon {{ $isApproved ? 'approved' : 'upcoming' }}">
                  <i class="bi {{ $isApproved ? 'bi-check-all' : 'bi-shield-check' }}"></i>
                </div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card {{ $isApproved ? 'approved-stage' : '' }}">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role {{ $isApproved ? 'text-success' : 'text-muted' }}">Inventory Acceptance</span>
                    <span class="badge {{ $isApproved ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }} py-0.5 px-1.5" style="font-size:10px;">
                      {{ $isApproved ? 'Closed' : 'Locked' }}
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $isApproved ? 'Receipt closed & stock updated' : 'Locked pending physical audit' }}</span>
                    <span class="{{ $isApproved ? 'text-success' : 'text-muted' }}">{{ $isApproved ? 'Settled' : 'Awaiting HOD' }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Decision Action Area -->
          @if ($pendingApproval)
            <div class="timeline-action-card mt-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="badge-soft" style="background:rgba(176,38,58,0.1); color:var(--maroon); font-size:11px;">
                  <i class="bi bi-shield-lock-fill me-1"></i> Authorized Action Required
                </div>
                <span class="text-dark fw-bold" style="font-size:13px;">HOD Physical Verification</span>
              </div>

              <!-- Quick Comment Chips -->
              <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                <span class="text-muted small me-1" style="font-size:11px;"><i class="bi bi-magic me-1"></i>Quick notes:</span>
                <button type="button" class="quick-comment-chip" onclick="setGrnComment(this, 'Physical goods received in sound condition. Qty and specs verified.')">Goods verified</button>
                <button type="button" class="quick-comment-chip" onclick="setGrnComment(this, 'Inspected and accepted into departmental inventory.')">Inventory accepted</button>
                <button type="button" class="quick-comment-chip" onclick="setGrnComment(this, 'Approved. Ready for invoice payment clearance.')">Ready for finance</button>
              </div>

              <form method="POST" action="{{ route('grn.approve', $grn) }}" class="mb-2" id="grnApproveForm">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-chat-text text-muted"></i></span>
                  <input type="text" name="comments" id="grnCommentInput" class="form-control" placeholder="Inspection verification remarks (optional)">
                </div>
                <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                  <i class="bi bi-check2-circle"></i>
                  <span>Approve &amp; Close GRN</span>
                </button>
              </form>

              <form method="POST" action="{{ route('grn.reject', $grn) }}"
                    onsubmit="return this.comments.value.trim() !== '' || (alert('A specific discrepancy reason is required to reject delivery.'), false);">
                @csrf
                <div class="input-group input-group-sm mb-2">
                  <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                  <input type="text" name="comments" class="form-control" placeholder="Discrepancy / rejection reason (required)">
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                  <i class="bi bi-x-circle"></i>
                  <span>Reject Delivery</span>
                </button>
              </form>
            </div>

            <script>
              function setGrnComment(btn, text) {
                document.querySelectorAll('.quick-comment-chip').forEach(c => c.classList.remove('selected'));
                btn.classList.add('selected');
                const inp = document.getElementById('grnCommentInput');
                if (inp) {
                  inp.value = text;
                  inp.focus();
                }
              }
            </script>
          @elseif ($grn->status === 'pending_hod')
            <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
              <div class="note-dot" style="background:#f59e0b;"></div>
              <div class="note-text text-muted">Awaiting HOD physical verification signoff &bull; no action pending on your account.</div>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'GRN Detail' });
@endsection
