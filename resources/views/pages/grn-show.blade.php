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

  <style>
    .btn-more-action {
      background-color: #ffffff !important;
      color: #475569 !important;
      border: 1px solid #e2e8f0 !important;
      font-size: 13px !important;
      border-radius: 8px !important;
      transition: all 0.15s ease-in-out !important;
    }
    .btn-more-action i {
      color: #64748b !important;
    }
    .btn-more-action:hover, .btn-more-action:focus, .show > .btn-more-action {
      background-color: #f1f5f9 !important;
      color: #0f172a !important;
      border-color: #cbd5e1 !important;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }
    .btn-more-action:hover i, .btn-more-action:focus i, .show > .btn-more-action i {
      color: #0f172a !important;
    }
    .meta-link {
      color: #c8102e;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s ease;
    }
    .meta-link:hover {
      color: #a50d24;
      text-decoration: underline;
    }
  </style>

  <a href="{{ route('grn') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1.5">
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

  @if ($grn->status === 'on_hold')
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-3" style="border-radius: 8px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-dark fs-6 mb-0.5">This Goods Receipt Note is Currently On Hold</div>
          <div class="text-secondary small">
            Review is paused at the HOD verification stage pending physical inspection or invoice validation.
          </div>
        </div>
      </div>
      <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100 position-relative" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); z-index: 20;">
    
    <!-- Top Row: GRN Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="text-start">
          @php
            $linkedPr = $grn->purchaseRequisition ?? $grn->purchaseOrder?->purchaseRequisition;
            $canViewPo = \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po');
            $canViewPr = \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition');
            $canEditGrn = $isPending && in_array(auth()->user()?->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true);
            $hasMoreActions = $pendingApproval || $canEditGrn || ($linkedPr && $canViewPr) || ($grn->purchaseOrder && $canViewPo);
          @endphp

          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $grn->grn_number }}</span>
            <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
              {{ $meta['label'] }}
            </span>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($grn->purchaseOrder)
              <span class="detail-meta-chip">
                <i class="bi bi-receipt"></i>
                <span class="meta-label">PO:</span>
                @if ($canViewPo)
                  <a href="{{ route('po.show', $grn->purchaseOrder) }}" title="View Purchase Order #{{ $grn->purchaseOrder->po_number }}">
                    {{ $grn->purchaseOrder->po_number }}
                  </a>
                @else
                  <strong>{{ $grn->purchaseOrder->po_number }}</strong>
                @endif
              </span>
            @endif

            @if ($linkedPr)
              <span class="detail-meta-chip">
                <i class="bi bi-file-earmark-text"></i>
                <span class="meta-label">PR:</span>
                @if ($canViewPr)
                  <a href="{{ route('requisition.show', $linkedPr) }}" title="View Purchase Requisition #{{ $linkedPr->pr_number }}">
                    {{ $linkedPr->pr_number }}
                  </a>
                @else
                  <strong>{{ $linkedPr->pr_number }}</strong>
                @endif
              </span>
            @endif

            @if ($grn->purchaseOrder?->vendor)
              <span class="detail-meta-chip">
                <i class="bi bi-building"></i>
                <span class="meta-label">Vendor:</span>
                <strong>{{ $grn->purchaseOrder->vendor->company_name }}</strong>
              </span>
            @endif

            @if (!empty($grnDepartment))
              <span class="detail-meta-chip">
                <i class="bi bi-diagram-3"></i>
                <span class="meta-label">Dept:</span>
                <strong>{{ $grnDepartment }}</strong>
              </span>
            @endif
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0 text-start">
          @if ($pendingApproval)
            <!-- Approve Button Form -->
            <form method="POST" action="{{ route('grn.approve', $grn) }}" class="m-0 d-inline" id="grnApproveForm">
              @csrf
              <input type="hidden" name="comments" value="Physical goods received in sound condition. Qty and specs verified.">
              <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap shadow-sm" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-check-lg"></i>
                <span>Approve &amp; Close</span>
              </button>
            </form>

            <!-- Reject Button Trigger -->
            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#rejectGrnModal">
              <i class="bi bi-x-lg"></i>
              <span>Reject</span>
            </button>

            <!-- More Actions Dropdown -->
            <div class="dropdown d-inline-block position-relative">
              <button class="btn btn-sm btn-more-action dropdown-toggle d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                <i class="bi bi-three-dots"></i>
                <span>More</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-lg border py-1" style="font-size: 13px; min-width: 195px; z-index: 1060; border-radius: 8px;">
                <li>
                  <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning-emphasis fw-semibold" data-bs-toggle="modal" data-bs-target="#holdGrnModal">
                    <i class="bi bi-pause-circle text-warning fs-6"></i>
                    <span>Put On Hold</span>
                  </button>
                </li>
                @if ($canEditGrn)
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('grn.edit', $grn) }}">
                      <i class="bi bi-pencil text-secondary fs-6"></i>
                      <span>Edit Qty / Date</span>
                    </a>
                  </li>
                @endif
                @if ($grn->purchaseOrder && $canViewPo)
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('po.show', $grn->purchaseOrder) }}">
                      <i class="bi bi-receipt text-secondary fs-6"></i>
                      <span>View Purchase Order</span>
                    </a>
                  </li>
                @endif
                @if ($linkedPr && $canViewPr)
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('requisition.show', $linkedPr) }}">
                      <i class="bi bi-file-earmark-text text-secondary fs-6"></i>
                      <span>View Requisition</span>
                    </a>
                  </li>
                @endif
              </ul>
            </div>
          @else
            <!-- Non-Pending State: Clean Navigation Buttons -->
            @if ($grn->purchaseOrder && $canViewPo)
              <a href="{{ route('po.show', $grn->purchaseOrder) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-receipt"></i>
                <span>View PO</span>
              </a>
            @endif
            @if ($linkedPr && $canViewPr)
              <a href="{{ route('requisition.show', $linkedPr) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-file-earmark-text"></i>
                <span>View PR</span>
              </a>
            @endif
            @if ($canEditGrn)
              <a href="{{ route('grn.edit', $grn) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-pencil"></i>
                <span>Edit Qty / Date</span>
              </a>
            @endif
          @endif
        </div>
      </div>
    </div>

  </div>

  <!-- Horizontal Inspection & Verification Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
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
                  <div class="h-step-quote {{ $stepApproved ? 'quote-approved' : '' }}" 
                       data-bs-toggle="modal" 
                       data-bs-target="#grnApprovalCommentModal-{{ $approval->id }}"
                       title="Click to view full comment">
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
          <div>
            <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
              Store Delivery &amp; Inspection Specifications
            </h6>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border font-monospace px-3 py-1.5" style="font-size: 12px;">
              PO Qty: <strong class="text-dark">{{ $grn->po_qty }} Units</strong>
            </span>
          </div>
        </div>

        <!-- Key Information Grid -->
        <div class="p-3 bg-white border-bottom">
          <div class="erp-kv-grid p-0">
            <!-- Item 1: Delivering Vendor -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Delivering Vendor</span>
              <div class="erp-kv-val">
                {{ $grn->purchaseOrder?->vendor?->company_name ?? '—' }}
              </div>
              <div class="text-muted font-monospace" style="font-size: 11px;">
                Code: {{ $grn->purchaseOrder?->vendor?->vendor_code ?? '—' }}
              </div>
            </div>

            <!-- Item 2: Invoice Number & Attached Copy -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Invoice / Challan</span>
              <div class="erp-kv-val d-flex align-items-center justify-content-between gap-1">
                <span>{{ $grn->invoice_number ?: 'Not Specified' }}</span>
                @if ($grn->attachments->isNotEmpty())
                  <a href="{{ $grn->attachments->first()->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1.5" style="font-size: 11px;" title="View / Download Invoice ({{ $grn->attachments->first()->original_name }})">
                    View
                  </a>
                @endif
              </div>
              <div class="text-muted" style="font-size: 11px;">
                Date: {{ optional($grn->receipt_date)->format('d M Y') ?? '—' }}
              </div>
            </div>

            <!-- Item 3: Received Quantity -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Received Quantity</span>
              <div class="erp-kv-val font-monospace text-success" style="font-size: 1rem;">
                {{ $grn->received_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->received_qty) }}
              </div>
              <div class="text-muted font-monospace" style="font-size: 11px;">
                Ordered: {{ $grn->po_qty }} {{ \Illuminate\Support\Str::plural('Unit', $grn->po_qty) }}
              </div>
            </div>

            <!-- Item 4: Verification Status -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Inspection Stage</span>
              <div class="erp-kv-val">
                <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; font-size:11.5px; padding: 4px 9px; border-radius: 4px;">
                  {{ $meta['label'] }}
                </span>
              </div>
              <div class="text-muted" style="font-size: 11px;">
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

        <!-- Materials Inspection Table -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
              <thead class="table-light">
                <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700;">
                  <th class="ps-3 py-2.5 text-center" style="width: 35px;">#</th>
                  <th class="py-2.5">ITEM &amp; DESCRIPTION</th>
                  <th class="text-center py-2.5" style="width: 115px;">ORDERED QTY</th>
                  <th class="text-center py-2.5" style="width: 135px;">INWARD RECEIVED</th>
                  <th class="text-center py-2.5" style="width: 120px;">ACCEPTED QTY</th>
                  <th class="text-center pe-3 py-2.5" style="width: 120px;">STATUS</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $itemCount = $items->count();
                  $unitLabel = \Illuminate\Support\Str::plural('Unit', $grn->received_qty);
                @endphp
                @forelse ($items as $item)
                <tr>
                  <td class="ps-3 py-2.5 text-center text-muted fw-semibold" style="font-size: 12px;">
                    {{ $loop->iteration }}
                  </td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                    @if ($item->description)
                      <div class="text-muted small" style="font-size: 12px; line-height: 1.45; white-space: pre-line;">{{ $item->description }}</div>
                    @endif
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
                          Accepted
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
                <tr>
                  <td class="ps-3 py-2.5 text-center text-muted fw-semibold">1</td>
                  <td class="py-2.5">
                    <div class="fw-semibold text-dark">{{ $grn->item_description ?: 'Contracted Goods Delivery' }}</div>
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

        <div class="p-3 bg-light-subtle border-top">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
            <div class="text-secondary small">
              <strong>Quality Remarks:</strong>
              <span>{{ $grn->quality_remarks ?: 'No quality remarks recorded.' }}</span>
            </div>
            @if ($grn->attachments->isNotEmpty())
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small"><strong>Attached Invoice / Document:</strong></span>
                @foreach ($grn->attachments as $att)
                  <a href="{{ $att->url }}" target="_blank" class="badge bg-white text-primary border px-2 py-1 text-decoration-none" title="Download {{ $att->original_name }}">
                    {{ $att->original_name ?: 'Invoice Document' }}
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

  </div>

  @if ($pendingApproval)
    <!-- Place GRN On Hold Modal -->
    <div class="modal fade" id="holdGrnModal" tabindex="-1" aria-labelledby="holdGrnModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('grn.hold', $grn) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdGrnModalLabel">Place GRN On Hold</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Placing <strong>{{ $grn->grn_number }}</strong> on hold temporarily pauses verification until physical goods are fully inspected or invoice discrepancies clarified.
            </p>
            <div class="mb-1">
              <label for="holdGrnCommentsInput" class="form-label fw-semibold text-dark small">Reason for Placing On Hold <span class="text-danger">*</span></label>
              <textarea name="comments" id="holdGrnCommentsInput" class="form-control" rows="3" placeholder="Provide reason for hold (e.g. Awaiting lab test report, vendor invoice copy pending)..." required></textarea>
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

  {{-- GRN Approval Comments Modals --}}
  @foreach ($grn->approvals as $approval)
    @if ($approval->comments)
      @php
        $stageTitle = $approval->role_name ?? ('Approver (Tier ' . $approval->level . ')');
        $isAppr = $approval->status === 'approved';
        $isRej = $approval->status === 'rejected';
      @endphp
      <div class="modal fade" id="grnApprovalCommentModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-chat-left-quote text-primary fs-5"></i>
                <h6 class="modal-title fw-bold text-dark mb-0">Approval Remarks — {{ $approval->approver?->name ?? 'Approver' }}</h6>
              </div>
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

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'GRN Detail' });
@endsection
