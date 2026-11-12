@extends('layouts.app')

@section('title', $grn->grn_number)

@php
  $statusMeta = [
    'pending_hod' => ['label' => 'Pending HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Verified Closed', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$grn->status] ?? $statusMeta['pending_hod'];

  $approvals = $grn->approvals->sortByDesc('id')->unique('level')->sortBy('level');
  $isApproved = $grn->status === 'approved';
  $isRejected = $grn->status === 'rejected';
  $isPending = $grn->status === 'pending_hod';
  $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

  $items = $grn->purchaseOrder?->purchaseRequisition?->items ?? collect();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grn') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Goods Receipts (GRN)</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: GRN Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(16,185,129,0.05)); color:#10b981; border:1px solid rgba(16,185,129,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-box-seam fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $grn->grn_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              <span class="badge bg-light text-secondary border" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                Goods Receipt Note
              </span>
            </div>
            @php
              $linkedPr = $grn->purchaseRequisition ?? $grn->purchaseOrder?->purchaseRequisition;
            @endphp
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span><i class="bi bi-receipt text-secondary me-1"></i> PO: <a href="{{ route('po.show', $grn->purchaseOrder) }}" class="text-dark fw-bold text-decoration-none">{{ $grn->purchaseOrder->po_number }}</a></span>
              @if ($linkedPr)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-file-earmark-text text-secondary me-1"></i> PR: <a href="{{ route('requisition.show', $linkedPr) }}" class="text-dark fw-bold text-decoration-none">{{ $linkedPr->pr_number }}</a></span>
              @endif
              <span class="text-secondary">•</span>
              <span><i class="bi bi-shop text-secondary me-1"></i> {{ $grn->purchaseOrder->vendor->company_name }}</span>
              @if ($grn->created_at)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-calendar3 text-secondary me-1"></i> {{ $grn->created_at->format('d M Y') }}</span>
              @endif
              <span class="text-secondary">•</span>
              <span><i class="bi bi-box text-success me-1"></i> Recv: {{ $grn->received_qty }} / {{ $grn->po_qty }} units</span>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0 text-start">
          @if ($isPending && in_array(auth()->user()?->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true))
            <a href="{{ route('grn.edit', $grn) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 13px;">
              <i class="bi bi-pencil-square"></i>
              <span>Edit Qty / Date</span>
            </a>
          @endif
          @if ($linkedPr)
            <a href="{{ route('requisition.show', $linkedPr) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 13px;">
              <i class="bi bi-file-earmark-text"></i>
              <span>View PR</span>
            </a>
          @endif
          @if ($grn->purchaseOrder)
            <a href="{{ route('po.show', $grn->purchaseOrder) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 13px;">
              <i class="bi bi-receipt"></i>
              <span>View PO</span>
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval)
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
                    Stage: HOD Verification
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Confirm physical delivery count, damage condition, and invoice specification before endorsing this receipt.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (No Inline Input) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <!-- Approve Button Form -->
              <form method="POST" action="{{ route('grn.approve', $grn) }}" class="m-0" id="grnApproveForm">
                @csrf
                <input type="hidden" name="comments" value="Physical goods received in sound condition. Qty and specs verified.">
                <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-check2-circle fs-6"></i>
                  <span>Approve &amp; Close GRN</span>
                </button>
              </form>

              <!-- Reject Delivery Button (Triggers Clean Modal) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#rejectGrnModal">
                <i class="bi bi-x-circle fs-6"></i>
                <span>Reject Delivery</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Inspection & Verification Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Inspection &amp; Verification Rail</h6>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 1: Store Receipt Inward -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Store Receipt Inward">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Store Inward</div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Inwarded
                </span>
              </div>
              <div class="h-step-person" title="{{ $grn->creator?->name ?? 'Store Executive' }}">
                {{ $grn->creator?->name ?? 'Store Executive' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $grn->received_qty }}/{{ $grn->po_qty }} units &bull; {{ optional($grn->receipt_date)->format('d M') }}
              </div>
            </div>
          </div>

          <!-- Stage 2: HOD Physical Signoff -->
          @foreach ($approvals as $approval)
            @php
              $stepApproved = $approval->status === 'approved';
              $stepRejected = $approval->status === 'rejected';
              $stepPending  = $approval->status === 'pending';

              if ($stepApproved) {
                $stepStatusClass = 'is-done';
                $iconClass = 'done';
                $cardClass = 'done-card';
              } elseif ($stepRejected) {
                $stepStatusClass = 'is-declined';
                $iconClass = 'rejected';
                $cardClass = 'active-card border-danger-subtle';
              } elseif ($stepPending) {
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
                @if ($stepApproved)
                  <i class="bi bi-check-lg"></i>
                @elseif ($stepRejected)
                  <i class="bi bi-x-lg"></i>
                @elseif ($stepPending)
                  <i class="bi bi-hourglass-split"></i>
                @else
                  <span class="text-secondary fw-bold" style="font-size: 11.5px;">{{ $approval->level }}</span>
                @endif
              </div>
              <div class="h-step-card {{ $cardClass }}">
                <div class="h-step-title {{ $stepApproved ? 'text-success' : ($stepRejected ? 'text-danger' : ($stepPending ? 'text-warning-emphasis' : 'text-muted')) }} mb-1" title="HOD Physical Signoff">
                  HOD Verification
                </div>
                <div class="mb-1.5">
                  <span class="badge {{ $stepApproved ? 'bg-success-subtle text-success' : ($stepRejected ? 'bg-danger-subtle text-danger' : ($stepPending ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    @if ($stepApproved) Verified
                    @elseif ($stepRejected) Rejected
                    @elseif ($stepPending) Action Due
                    @else Standby
                    @endif
                  </span>
                </div>
                <div class="h-step-person">
                  @if ($approval->approver)
                    <span class="{{ $stepRejected ? 'text-danger fw-semibold' : '' }}">{{ $approval->approver->name }}</span>
                  @elseif ($stepPending)
                    <span class="text-warning fw-bold">Action Due</span>
                  @else
                    <span class="text-muted">Awaiting Stage</span>
                  @endif
                </div>
                <div class="h-step-meta mt-0.5">
                  @if ($approval->acted_at)
                    {{ $approval->acted_at->format('d M, H:i') }}
                  @elseif ($stepRejected)
                    Declined
                  @else
                    Pending
                  @endif
                </div>
                @if ($approval->comments)
                  <div class="h-step-quote {{ $stepApproved ? 'quote-approved' : '' }}" title="{{ $approval->comments }}">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <!-- Stage 3: Inventory Stock Acceptance -->
          @php
            if ($isApproved) {
              $accStatus = 'is-done';
              $accIcon = 'done';
              $accCard = 'done-card';
            } elseif ($isRejected) {
              $accStatus = 'is-stopped';
              $accIcon = 'upcoming';
              $accCard = 'stopped-card';
            } else {
              $accStatus = 'is-stopped';
              $accIcon = 'upcoming';
              $accCard = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $accStatus }}">
            <div class="h-step-icon {{ $accIcon }}">
              <i class="bi {{ $isApproved ? 'bi-check-all' : 'bi-shield-check' }}"></i>
            </div>
            <div class="h-step-card {{ $accCard }}">
              <div class="h-step-title {{ $isApproved ? 'text-success' : 'text-muted' }} mb-1" title="Inventory Acceptance">
                Inventory Stock
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $isApproved ? 'Closed' : 'Locked' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($isApproved)
                  <span class="text-success fw-semibold">Stock Ledger Updated</span>
                @else
                  <span class="text-muted">Post-Verification</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $isApproved ? 'Settled' : 'Post-HOD' }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Left Column: Goods Delivery Dossier & Material Inspection -->
    <div class="col-12">
      <div class="card shadow-sm border-0 mb-4">
        <!-- Header -->
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 38px; height: 38px;">
              <i class="bi bi-box-seam fs-5"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
                Store Delivery &amp; Inspection Specifications
              </h6>
              <div class="text-muted small mt-0.5">
                Receipt logged against Purchase Order <strong class="text-dark">{{ $grn->purchaseOrder->po_number }}</strong>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border font-monospace px-3 py-1.5" style="font-size: 12px;">
              PO Qty: <strong class="text-dark">{{ $grn->po_qty }} Units</strong>
            </span>
          </div>
        </div>

        <!-- 4 Premium Executive Metric Cards -->
        <div class="p-3 bg-light-subtle border-bottom">
          <div class="row g-3">
            <!-- Card 1: Delivering Vendor -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Delivering Vendor
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-primary flex-shrink-0" style="width: 32px; height: 32px; background: rgba(37, 99, 235, 0.08); font-size: 15px;">
                    <i class="bi bi-shop"></i>
                  </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
                  {{ $grn->purchaseOrder?->vendor?->company_name ?? '—' }}
                </div>
                <div class="text-muted small font-monospace" style="font-size: 11.5px;">
                  Code: {{ $grn->purchaseOrder?->vendor?->vendor_code ?? '—' }}
                </div>
              </div>
            </div>

            <!-- Card 2: Invoice Number -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Invoice Number
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-info flex-shrink-0" style="width: 32px; height: 32px; background: rgba(14, 165, 233, 0.08); font-size: 15px;">
                    <i class="bi bi-receipt-cutoff"></i>
                  </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
                  {{ $grn->invoice_number ?: 'Not Specified' }}
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  <i class="bi bi-calendar3 me-1"></i>Date: {{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}
                </div>
              </div>
            </div>

            <!-- Card 3: Received Quantity -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Received Quantity
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.08); font-size: 15px;">
                    <i class="bi bi-check2-circle"></i>
                  </div>
                </div>
                <div class="font-monospace fw-bold text-success mb-1" style="font-size: 14.5px; line-height: 1.4; word-break: break-word;">
                  {{ $grn->received_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->received_qty) }}
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  Ordered: {{ $grn->po_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->po_qty) }}
                </div>
              </div>
            </div>

            <!-- Card 4: Verification Status -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Inspection Stage
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-warning flex-shrink-0" style="width: 32px; height: 32px; background: rgba(245, 158, 11, 0.08); font-size: 15px;">
                    <i class="bi bi-shield-check"></i>
                  </div>
                </div>
                <div class="mb-1">
                  <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; font-size:11.5px; padding: 4px 9px; border-radius: 4px;">
                    {{ $meta['label'] }}
                  </span>
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  @if ($isApproved)
                    Accepted: {{ $grn->received_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->received_qty) }}
                  @elseif ($isRejected)
                    Accepted: 0 Units (Rejected)
                  @else
                    Awaiting HOD Signoff
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Materials Inspection Table -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
              <thead class="table-light border-bottom">
                <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                  <th class="ps-3 py-2.5 text-center" style="width: 35px;">#</th>
                  <th class="py-2.5">Item Name &amp; Description</th>
                  <th class="text-center py-2.5" style="width: 115px;">Ordered Qty</th>
                  <th class="text-center py-2.5" style="width: 135px;">Inward Received</th>
                  <th class="text-center py-2.5" style="width: 120px;">Accepted Qty</th>
                  <th class="text-center pe-3 py-2.5" style="width: 120px;">Status</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $itemCount = $items->count();
                  $unitLabel = \Illuminate\Support\Str::plural('Unit', $grn->received_qty);
                @endphp
                @forelse ($items as $item)
                <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#itemDetailModal-grn-{{ $item->id }}" title="Click to view full inspection details">
                  <td class="ps-3 py-2.5 text-center text-muted fw-semibold" style="font-size: 12px;">
                    {{ $loop->iteration }}
                  </td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                    @if ($item->description)
                      <div class="text-muted small" style="font-size: 11.5px; line-height: 1.3;">{{ $item->description }}</div>
                    @endif
                    <div class="text-primary small mt-0.5" style="font-size: 11px;">
                      <i class="bi bi-arrows-angle-expand me-1"></i>View Details
                    </div>
                  </td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 12px;">
                      {{ $item->quantity }} {{ ($item->unit && !is_numeric($item->unit)) ? $item->unit : \Illuminate\Support\Str::plural('Unit', $item->quantity) }}
                    </span>
                  </td>
                  @if ($loop->first)
                    <td class="text-center py-2.5 text-nowrap font-monospace text-primary fw-bold bg-white" @if($itemCount > 1) rowspan="{{ $itemCount }}" style="vertical-align: middle; border-left: 1px solid #f1f5f9;" @endif>
                      {{ $grn->received_qty }} / {{ $grn->po_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->po_qty) }}
                      @if ($itemCount > 1)
                        <div class="text-muted fw-normal" style="font-size: 10.5px;">PO-level receipt</div>
                      @endif
                    </td>
                    <td class="text-center py-2.5 text-nowrap font-monospace fw-bold bg-white {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : 'text-warning-emphasis') }}" @if($itemCount > 1) rowspan="{{ $itemCount }}" style="vertical-align: middle;" @endif>
                      @if ($isApproved)
                        {{ $grn->received_qty }} {{ $unitLabel }}
                      @elseif ($isRejected)
                        0 Units
                      @else
                        Pending
                      @endif
                    </td>
                    <td class="text-center pe-3 py-2.5 bg-white" @if($itemCount > 1) rowspan="{{ $itemCount }}" style="vertical-align: middle;" @endif>
                      @if ($isApproved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                          <i class="bi bi-check2 me-1"></i>Accepted
                        </span>
                      @elseif ($isRejected)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">
                          Rejected
                        </span>
                      @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                          Pending
                        </span>
                      @endif
                    </td>
                  @endif
                </tr>
                @empty
                <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#itemDetailModal-grn-default" title="Click to view full inspection details">
                  <td class="ps-3 py-2.5 text-center text-muted fw-semibold">1</td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $grn->item_description ?: 'Contracted Goods Delivery' }}</div>
                    <div class="text-primary small mt-0.5" style="font-size: 11px;">
                      <i class="bi bi-arrows-angle-expand me-1"></i>View Details
                    </div>
                  </td>
                  <td class="text-center py-2.5 font-monospace">{{ $grn->po_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->po_qty) }}</td>
                  <td class="text-center py-2.5 font-monospace text-primary fw-bold">{{ $grn->received_qty }} {{ $unitLabel }}</td>
                  <td class="text-center py-2.5 font-monospace fw-bold {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : 'text-warning-emphasis') }}">
                    {{ $isApproved ? $grn->received_qty . ' ' . $unitLabel : ($isRejected ? '0 Units' : 'Pending') }}
                  </td>
                  <td class="text-center pe-3 py-2.5">
                    <span class="badge {{ $meta['bg'] ? '' : 'bg-light text-dark border' }}" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:11px;">
                      {{ $meta['label'] }}
                    </span>
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- GRN Material Detail Modals -->
        @foreach ($items as $item)
        <div class="modal fade" id="itemDetailModal-grn-{{ $item->id }}" tabindex="-1" aria-labelledby="itemModalLabel-grn-{{ $item->id }}" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
              <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                    #{{ $loop->iteration }}
                  </div>
                  <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="itemModalLabel-grn-{{ $item->id }}">
                      {{ $item->item_name }}
                    </h6>
                    <div class="text-muted small" style="font-size: 11.5px;">Physical Inspection &amp; Inward Material Receipt Verification</div>
                  </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                <!-- 4 Grid Metric Boxes -->
                <div class="row g-3 mb-4">
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">PO Ordered Qty</div>
                      <div class="fw-bold text-dark fs-5 mt-1">{{ $item->quantity }} <span class="fs-6 text-muted fw-normal">{{ ($item->unit && !is_numeric($item->unit)) ? $item->unit : \Illuminate\Support\Str::plural('Unit', $item->quantity) }}</span></div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">GRN Received</div>
                      <div class="fw-bold text-primary fs-5 mt-1 font-monospace">{{ $grn->received_qty }} / {{ $grn->po_qty }}</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-success-subtle rounded-2 border border-success-subtle">
                      <div class="text-success text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Accepted Qty</div>
                      <div class="fw-bold text-success fs-5 mt-1 font-monospace">{{ $isApproved ? $grn->received_qty . ' ' . $unitLabel : ($isRejected ? '0 Units' : 'Pending') }}</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Inspection Result</div>
                      <div class="fw-bold text-dark fs-6 mt-1">{{ $isApproved ? 'Verified & Accepted' : ($isRejected ? 'Declined' : 'Pending Signoff') }}</div>
                    </div>
                  </div>
                </div>

                <!-- Technical Description -->
                <div class="mb-4">
                  <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                    <i class="bi bi-card-text text-primary me-1"></i> Technical Item Description &amp; Verification Standard
                  </h6>
                  <div class="p-3 rounded-2 border bg-light-subtle text-dark" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap;">{{ $item->description ?: 'Standard material receipt inspection for ' . $item->item_name . '. Verified against PO terms and delivery challan.' }}</div>
                </div>

                @if ($grn->quality_remarks)
                <div class="mb-3">
                  <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                    <i class="bi bi-chat-left-quote text-primary me-1"></i> Quality &amp; Verification Remarks
                  </h6>
                  <div class="p-3 rounded-2 border bg-warning-subtle text-dark" style="font-size: 13px;">
                    {{ $grn->quality_remarks }}
                  </div>
                </div>
                @endif

                <!-- Metadata Footer -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 text-muted small" style="font-size: 12px;">
                  <span><i class="bi bi-upc-scan me-1"></i> GRN: <strong>{{ $grn->grn_number }}</strong></span>
                  <span><i class="bi bi-receipt me-1"></i> Linked PO: <strong>{{ $grn->purchaseOrder?->po_number ?? '—' }}</strong></span>
                  <span><i class="bi bi-calendar3 me-1"></i> Receipt Date: <strong>{{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}</strong></span>
                </div>
              </div>
              <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @endforeach

        <div class="p-3 bg-light-subtle border-top">
          <div class="d-flex align-items-start gap-2 text-secondary small">
            <i class="bi bi-chat-left-text text-primary mt-0.5"></i>
            <div>
              <strong>Quality Remarks:</strong>
              <span>{{ $grn->quality_remarks ?: 'No quality remarks recorded.' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  @if ($pendingApproval)
    <!-- Reject Delivery GRN Modal (Small, Clean & Minimal Window) -->
    <div class="modal fade" id="rejectGrnModal" tabindex="-1" aria-labelledby="rejectGrnModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('grn.reject', $grn) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <div class="d-flex align-items-center gap-2 text-danger">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="rejectGrnModalLabel">Reject Goods Receipt</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific reason for rejecting <strong>{{ $grn->grn_number }}</strong>. This feedback will be recorded in the audit trail and its received quantity will be revoked from PO fulfillment.
            </p>
            <div class="mb-1">
              <label for="rejectCommentsInput" class="form-label fw-semibold text-dark small">Discrepancy / Rejection Reason <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectCommentsInput" class="form-control" rows="3" placeholder="e.g. Broken packaging, quantity mismatch with delivery challan, wrong specifications, etc." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3.5 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
              <i class="bi bi-x-circle"></i>
              <span>Confirm Rejection</span>
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
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'GRN Detail' });
@endsection
