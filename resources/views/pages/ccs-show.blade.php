@extends('layouts.app')

@section('title', $ccs->ccs_number)

@php
  $statusMeta = [
    'pending_committee' => ['label' => 'Pending — Committee', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'          => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'          => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'             => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$ccs->status] ?? $statusMeta['draft'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('ccs') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Cost Comparative Statements</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-file-earmark-bar-graph"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $ccs->ccs_number }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            Comparative Statement
          </span>
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
          <span><i class="bi bi-broadcast"></i> RFQ: {{ $ccs->rfq->rfq_number }}</span>
          <span><i class="bi bi-file-earmark-text"></i> PR: {{ $ccs->rfq->purchaseRequisition->pr_number }} ({{ $ccs->rfq->purchaseRequisition->department }})</span>
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
    <!-- Left Column: Vendor Quotes & Comparison (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-table" style="color:var(--maroon);"></i>
              <span>Vendor Quotations Comparison ({{ $ccs->quotes->count() }})</span>
            </div>
            <span class="badge" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; font-weight:600; font-size:12px; padding:5px 10px;">
              <i class="bi bi-award"></i> L1 Ranked
            </span>
          </div>

          <div style="overflow-x:auto;">
            <table class="table table-hover table-sm align-middle">
              <thead>
                <tr class="text-muted" style="font-size:12.5px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">
                  <th>Rank</th>
                  <th>Vendor Partner</th>
                  <th class="text-end">Quoted Price</th>
                  <th>Delivery</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($ccs->quotes->sortBy('quoted_cost') as $quote)
                  @php $isL1 = $quote->rank === 'L1'; @endphp
                  <tr style="font-size:14px; {{ $isL1 ? 'background:#f0fdf4;' : '' }}">
                    <td>
                      <span class="badge" style="background:{{ $isL1 ? '#10b981' : '#64748b' }}; color:#fff; font-weight:600; font-size:11.5px; padding:3px 7px;">
                        {{ $quote->rank }}
                      </span>
                    </td>
                    <td class="fw-semibold text-dark" style="font-weight:600;">{{ $quote->vendor->company_name }}</td>
                    <td class="text-end fw-semibold" style="font-weight:600; color:{{ $isL1 ? '#059669' : '#0f172a' }};">
                      ₹{{ number_format($quote->quoted_cost, 2) }}
                    </td>
                    <td class="text-muted" style="font-size:13px;">{{ $quote->delivery_days !== null ? $quote->delivery_days.' days' : '—' }}</td>
                    <td class="text-muted" style="font-size:12.5px;">{{ $quote->remarks ?: '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        @if ($ccs->status === 'approved' && $ccs->purchaseOrder)
          <div class="mt-3 pt-3 border-top">
            <a href="{{ route('po.show', $ccs->purchaseOrder) }}" class="btn-outline-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:40px; text-decoration:none;">
              <i class="bi bi-receipt"></i>
              <span>View Issued Purchase Order {{ $ccs->purchaseOrder->po_number }}</span>
            </a>
          </div>
        @elseif ($ccs->status === 'approved')
          <div class="p-3 rounded-3 mt-3 d-flex justify-content-between align-items-center" style="background:#ecfdf5; border:1px solid #a7f3d0; font-size:13px;">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success fs-5"></i>
              <span>Approved — {{ $ccs->lowestQuote()?->vendor?->company_name }} (L1) awarded.</span>
            </div>
            @if (auth()->user()->role === \App\Support\Roles::BUYER)
              <a href="{{ route('po.create') }}" class="btn-brand btn-sm d-inline-flex align-items-center gap-1" style="font-weight:600; text-decoration:none;">
                <i class="bi bi-plus-circle"></i>
                <span>Raise Purchase Order</span>
              </a>
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Right Column: Colored Icon Timeline & Committee Decision (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Committee Review Timeline</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              {{ $ccs->approvals->where('status', 'approved')->count() }} / {{ $ccs->approvals->count() }} Endorsed
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($ccs->approvals->sortBy('level') as $i => $approval)
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
                  @if ($i < $ccs->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">Procurement Committee · {{ \App\Support\Roles::label($approval->role_required) }}</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Endorsed</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting Committee action</span>
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
              <span>Your Decision (Procurement Committee)</span>
            </div>
            <form method="POST" action="{{ route('ccs.approve', $ccs) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Comments (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve Statement (Authorize L1)</span>
              </button>
            </form>
            <form method="POST" action="{{ route('ccs.reject', $ccs) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Statement</span>
              </button>
            </form>
          </div>
        @elseif ($ccs->status === 'pending_committee')
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting Procurement Committee review — no action pending on your account.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'CCS Detail' });
@endsection
