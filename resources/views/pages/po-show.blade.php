@extends('layouts.app')

@section('title', $po->po_number)

@php
  $statusMeta = [
    'pending'  => ['label' => 'Pending Approval', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved — Issued', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$po->status] ?? $statusMeta['pending'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Orders</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-receipt"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $po->po_number }}</div>
          @if ($po->isHighValue())
            <span class="badge" style="background:#fef3c7; color:#d97706; font-size:11.5px; font-weight:600; padding:3px 7px;">
              <i class="bi bi-star-fill"></i> High Value (&ge; ₹10L)
            </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
          <span><i class="bi bi-shop"></i> {{ $po->vendor->company_name }}</span>
          <span><i class="bi bi-file-earmark-text"></i> {{ $po->purchaseRequisition->pr_number }} ({{ $po->purchaseRequisition->department }})</span>
          @if ($po->created_at)
            <span><i class="bi bi-clock"></i> {{ $po->created_at->format('d M Y') }}</span>
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
    <!-- Left Column: Order Details (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-info-circle" style="color:var(--maroon);"></i>
              <span>Purchase Order Specifications</span>
            </div>
            <span class="badge" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:12px; padding:5px 10px;">
              Total Qty: {{ $po->po_qty }}
            </span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-shop text-secondary"></i> Vendor</div>
                <div class="field-value text-dark" style="font-size:14.5px; font-weight:600;">{{ $po->vendor->company_name }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Code: {{ $po->vendor->vendor_code }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-currency-rupee text-secondary"></i> Total Amount (GST-incl.)</div>
                <div class="field-value" style="font-size:16px; font-weight:600; color:#c8102e;">₹{{ number_format($po->amount_incl_gst, 2) }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Committed Expenditure</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-calendar-event text-secondary"></i> Delivery Target</div>
                <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ optional($po->delivery_date)->format('d M Y') ?? 'Not specified' }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">Agreed fulfillment timeline</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-link-45deg text-secondary"></i> Linked Records</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">PR: {{ $po->purchaseRequisition->pr_number }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">RFQ: {{ $po->rfq?->rfq_number ?? 'Direct' }}</div>
              </div>
            </div>
          </div>

          <!-- Goods Receipt Progress -->
          @if ($po->status === 'approved')
            <div class="mt-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #cbd5e1;">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600; color:#1e293b;">
                  <i class="bi bi-truck text-secondary"></i>
                  <span>Goods Receipt Status</span>
                </div>
                <span class="badge" style="background:#e2e8f0; color:#334155; font-weight:600; font-size:12px;">
                  {{ $po->receivedQty() }} / {{ $po->po_qty }} Received
                </span>
              </div>
              @php $recvPct = $po->po_qty > 0 ? min(100, round(($po->receivedQty() / $po->po_qty) * 100)) : 0; @endphp
              <div class="progress mb-2" style="height:8px; border-radius:10px;">
                <div class="progress-bar" style="width:{{ $recvPct }}%; background-color:#10b981;"></div>
              </div>
              @if ($po->remainingQtyToReceive() > 0 && auth()->user()->role === \App\Support\Roles::PR_SR_EXEC)
                <a href="{{ route('grn.create') }}" class="btn-brand w-100 d-inline-flex justify-content-center align-items-center gap-2 mt-2" style="font-weight:600; height:38px; text-decoration:none;">
                  <i class="bi bi-box-arrow-in-down"></i> Log Goods Receipt (GRN)
                </a>
              @elseif ($po->remainingQtyToReceive() === 0)
                <div class="text-success d-flex align-items-center gap-1" style="font-size:12.5px; font-weight:600;">
                  <i class="bi bi-check-circle-fill"></i> All items accounted for in inventory.
                </div>
              @endif
            </div>
          @endif

          @if ($po->grn)
            <div class="mt-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #cbd5e1;">
              <div class="field-label mb-2"><i class="bi bi-box-seam text-secondary me-1"></i> Fulfilled via Goods Receipt</div>
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="field-value text-dark" style="font-size:14px; font-weight:600;">{{ $po->grn->grn_number }}</div>
                  <div class="text-secondary" style="font-size:12px; font-weight:500;">Qty Received: {{ $po->grn->qty_received }} · Accepted: {{ $po->grn->qty_accepted }}</div>
                </div>
                <a href="{{ route('grn.show', $po->grn) }}" class="btn btn-outline-secondary btn-sm" style="font-weight:600;">
                  <i class="bi bi-eye"></i> View GRN
                </a>
              </div>
              @if ($po->grn->status === 'approved')
                <div class="badge bg-success-subtle text-success border border-success-subtle mt-2 p-2 w-100 text-start d-flex align-items-center gap-2" style="font-size:12px; font-weight:600;">
                  <i class="bi bi-check-all fs-5"></i> Fully received &amp; accounted for in inventory.
                </div>
              @endif
            </div>
          @endif
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Created by {{ $po->creator?->name ?? 'System' }}</span>
          <span>Department: {{ $po->purchaseRequisition->department }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Timeline & Approval Routing (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Approval Routing Timeline</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              {{ $po->approvals->where('status', 'approved')->count() }} / {{ $po->approvals->count() }} Signed
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($po->approvals->sortBy('level') as $i => $approval)
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
                  @if ($i < $po->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">L{{ $approval->level }} · {{ \App\Support\Roles::label($approval->role_required) }}</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Approved &amp; Signed</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting review &amp; signature</span>
                        @else
                          <span class="text-muted"><i class="bi bi-dash-circle"></i> Upcoming stage</span>
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
              <span>Your Action (L{{ $pendingApproval->level }} — {{ \App\Support\Roles::label($pendingApproval->role_required) }})</span>
            </div>
            <form method="POST" action="{{ route('po.approve', $po) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Comments (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve &amp; Issue PO</span>
              </button>
            </form>
            <form method="POST" action="{{ route('po.reject', $po) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject PO</span>
              </button>
            </form>
          </div>
        @elseif ($po->status === 'pending')
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting assigned authority's endorsement — no action required from you right now.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'PO Detail' });
@endsection
