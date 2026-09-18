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
  <div class="detail-header-card d-flex align-items-center justify-content-between p-3 rounded-3" style="background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 16px -2px rgba(15,23,42,0.04);">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#059669,#10b981); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-box-seam"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $grn->grn_number }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Goods Receipt Note
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
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

  <div class="row g-4 mt-2">
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

    <!-- Right Column: Colored Icon Timeline & HOD Verification (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Verification Timeline</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              HOD Verification
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($grn->approvals->sortBy('level') as $i => $approval)
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
                  @if ($i < $grn->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">{{ \App\Support\Roles::label($approval->role_required) }} (HOD)</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Verified &amp; Inventory Accepted</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting physical verification signoff</span>
                        @else
                          <span class="text-muted"><i class="bi bi-dash-circle"></i> Pending receipt</span>
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
              <span>Your Action (HOD Physical Signoff)</span>
            </div>
            <form method="POST" action="{{ route('grn.approve', $grn) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Inspection remarks (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve &amp; Close GRN</span>
              </button>
            </form>
            <form method="POST" action="{{ route('grn.reject', $grn) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Discrepancy / rejection reason (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Delivery</span>
              </button>
            </form>
          </div>
        @elseif ($grn->status === 'pending_hod')
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting HOD verification — no action pending on your account.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'GRN Detail' });
@endsection
