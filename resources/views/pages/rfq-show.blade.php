@extends('layouts.app')

@section('title', $rfq->rfq_number)

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'dispatched'      => ['label' => 'Dispatched to Vendors', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'quotes_received' => ['label' => 'Quotes Received', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'],
    'approved'        => ['label' => 'Awarded / Completed', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
  ];
  $meta = $statusMeta[$rfq->status] ?? $statusMeta['draft'];
  $quotesReceived = in_array($rfq->status, ['quotes_received', 'approved']);
  $isAwarded = $rfq->status === 'approved';
  // Only approved lines move forward to RFQ (supporting line-item partial approval)
  $rfqDisplayItems = $rfq->purchaseRequisition->items->where('status', '!=', 'rejected');
  if ($rfqDisplayItems->isEmpty()) {
    $rfqDisplayItems = $rfq->purchaseRequisition->items;
  }
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to RFQs &amp; Quotations</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: RFQ Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        
        <!-- Left: Avatar + RFQ Number + Status + Meta Details -->
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(217,119,6,0.15),rgba(217,119,6,0.05)); color:#d97706; border:1px solid rgba(217,119,6,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-broadcast fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $rfq->rfq_number }}</span>
              @if ($rfq->project_name)
                <span class="fw-semibold text-secondary" style="font-size: 1.15rem; line-height: 1.2;">— {{ $rfq->project_name }}</span>
              @elseif ($rfq->purchaseRequisition?->title)
                <span class="fw-semibold text-secondary" style="font-size: 1.15rem; line-height: 1.2;">— {{ $rfq->purchaseRequisition->title }}</span>
              @endif
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
            </div>
            
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              @if ($rfq->sector)
                <span><i class="bi bi-diagram-3 text-secondary me-1"></i>Sector: <strong class="text-dark">{{ $rfq->sector }}</strong></span>
                <span class="text-secondary">•</span>
              @endif
              <span><i class="bi bi-file-earmark-text text-secondary me-1"></i>Linked PR: <strong class="text-dark">{{ $rfq->purchaseRequisition->pr_number }}</strong></span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-building text-secondary me-1"></i>{{ $rfq->purchaseRequisition->department }}</span>
              @if ($rfq->created_at)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-calendar3 text-secondary me-1"></i>Issued: {{ $rfq->created_at->format('d M Y') }}</span>
              @endif
              @if ($rfq->due_date)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-clock-history text-secondary me-1"></i>Due: <strong class="text-dark">{{ $rfq->due_date->format('d M Y') }}</strong></span>
              @endif
              <span class="text-secondary">•</span>
              <span><i class="bi bi-people text-secondary me-1"></i>{{ $rfq->rfqVendors->count() }} {{ Str::plural('Vendor', $rfq->rfqVendors->count()) }} Invited</span>
              @if ($rfq->email_dispatched_at)
                <span class="text-secondary">•</span>
                <span class="text-success"><i class="bi bi-envelope-check me-1"></i>Emailed</span>
              @endif
            </div>
          </div>
        </div>

        <!-- Right: Actions -->
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @php
            $ccs = $rfq->costComparativeStatement;
            $winningVendorId = $ccs?->selected_vendor_id ?: $ccs?->winningQuote()?->vendor_id;
            $existingPo = $rfq->purchaseRequisition?->purchaseOrders()->first();
          @endphp
          @if ($quotesReceived && !$existingPo)
            @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po') && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
              <a href="{{ route('po.create', array_filter(['pr_id' => $rfq->purchase_requisition_id, 'vendor_id' => $winningVendorId])) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold shadow-sm text-decoration-none">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Generate PO</span>
              </a>
            @endif
          @elseif ($existingPo)
            <a href="{{ route('po.show', $existingPo) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold text-decoration-none">
              <i class="bi bi-eye"></i>
              <span>View PO ({{ $existingPo->po_number }})</span>
            </a>
          @endif
        </div>

      </div>
    </div>

    <!-- Integrated Action Required Section (When Bidding in Progress & Can Manage) -->
    @if ($canManage && $rfq->status === 'dispatched')
      <div class="p-3 p-md-3.5 border-top w-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-top: 1px solid #fde68a !important;">
        <div class="row align-items-center g-3">
          <div class="col-12 col-lg-8">
            <div class="d-flex align-items-center" style="gap: 16px;">
              <div class="flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px; min-width: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">
                <i class="bi bi-broadcast fs-5"></i>
              </div>
              <div class="flex-grow-1 text-start">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="fw-bold text-dark" style="font-size: 15px;">Vendor Bidding in Progress</span>
                  <span class="badge bg-white text-dark border border-warning-subtle shadow-2xs px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                    Stage: Awaiting Quotes
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  RFQ dispatched to {{ $rfq->rfqVendors->count() }} invited vendors. Log vendor quotations directly in the Commercial Comparison Matrix below. The status automatically transitions to <strong>Quotes Received</strong> once quotes are saved.
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <button type="button" class="btn btn-outline-dark px-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" onclick="document.getElementById('quotationMatrixSection')?.scrollIntoView({behavior: 'smooth'})">
                <i class="bi bi-pencil-square"></i>
                <span>Enter Quotations &darr;</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal Procurement Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Procurement Rail</h6>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Step 1: PR Sanctioned -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Sanctioned">
              <i class="bi bi-file-earmark-check"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="PR Sanctioned">
                PR Sanctioned
              </div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Approved
                </span>
              </div>
              <div class="h-step-person" title="{{ $rfq->purchaseRequisition->pr_number }}">
                {{ $rfq->purchaseRequisition->pr_number }}
              </div>
              <div class="h-step-meta mt-0.5">
                ₹{{ number_format($rfq->purchaseRequisition->estimatedCostInclGst(), 2) }}
              </div>
            </div>
          </div>

          <!-- Step 2: RFQ Dispatched -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="RFQ Dispatched">
              <i class="bi bi-broadcast"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="RFQ Dispatched">
                RFQ Dispatched
              </div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Dispatched
                </span>
              </div>
              <div class="h-step-person">
                {{ $rfq->rfqVendors->count() }} Vendors Invited
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $rfq->created_at?->format('d M, H:i') ?? 'Completed' }}
              </div>
            </div>
          </div>

          <!-- Step 3: Commercial Quotes -->
          @php
            if ($quotesReceived) {
              $quotesStatus = 'is-done';
              $quotesIcon = 'done';
              $quotesCard = 'done-card';
            } else {
              $quotesStatus = 'is-active';
              $quotesIcon = 'active';
              $quotesCard = 'active-card';
            }
          @endphp
          <div class="h-timeline-step {{ $quotesStatus }}">
            <div class="h-step-icon {{ $quotesIcon }}">
              <i class="bi {{ $quotesReceived ? 'bi-check2-circle' : 'bi-hourglass-split' }}"></i>
            </div>
            <div class="h-step-card {{ $quotesCard }}">
              <div class="h-step-title {{ $quotesReceived ? 'text-success' : 'text-primary' }} mb-1" title="Commercial Quotes">
                Commercial Quotes
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $quotesReceived ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $quotesReceived ? 'Collected' : 'In Sourcing' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($quotesReceived)
                  <span class="text-success fw-semibold">Proposals Logged</span>
                @else
                  <span class="text-warning fw-semibold">Vendors Bidding</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $quotesReceived ? 'Received' : 'Awaiting Bids' }}
              </div>
            </div>
          </div>

          <!-- Step 4: Purchase Order (PO Handover) -->
          @php
            $hasPo = (bool) $rfq->purchaseRequisition?->purchaseOrders()->first();
            $poObj = $rfq->purchaseRequisition?->purchaseOrders()->first();
          @endphp
          <div class="h-timeline-step {{ $hasPo ? 'is-done' : ($quotesReceived ? 'is-active' : 'is-stopped') }}">
            <div class="h-step-icon {{ $hasPo ? 'done' : ($quotesReceived ? 'active' : 'upcoming') }}">
              <i class="bi {{ $hasPo ? 'bi-check-all' : 'bi-receipt-cutoff' }}"></i>
            </div>
            <div class="h-step-card {{ $hasPo ? 'done-card' : ($quotesReceived ? 'active-card' : 'stopped-card') }}">
              <div class="h-step-title {{ $hasPo ? 'text-success' : ($quotesReceived ? 'text-primary' : 'text-muted') }} mb-1" title="Purchase Order">
                Purchase Order
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $hasPo ? 'bg-success-subtle text-success' : ($quotesReceived ? 'bg-info-subtle text-info' : 'bg-light text-muted') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $hasPo ? 'PO Issued' : ($quotesReceived ? 'Ready to Issue' : 'Upcoming') }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($hasPo)
                  <a href="{{ route('po.show', $poObj) }}" class="text-decoration-none fw-bold text-success">
                    {{ $poObj->po_number }} &rarr;
                  </a>
                @elseif ($quotesReceived)
                  <span class="text-info fw-semibold">Actionable</span>
                @else
                  <span class="text-muted">Post-Quotation</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $hasPo ? $poObj->created_at?->format('d M, H:i') : ($quotesReceived ? 'Actionable' : '—') }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Requisition Material Specifications & Sourcing Dossier (Full Width) -->
  @php
    $subtotalExclGst = $rfq->purchaseRequisition->items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
    $totalGst = $rfq->purchaseRequisition->estimatedCostInclGst() - $subtotalExclGst;
  @endphp
  <div class="card shadow-sm border-0 mb-4">
    <!-- Header -->
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 38px; height: 38px;">
          <i class="bi bi-box-seam fs-5"></i>
        </div>
        <div>
          <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
            Requisition Material Specifications
          </h6>
          <div class="text-muted small mt-0.5">
            {{ $rfqDisplayItems->count() }} {{ Str::plural('line item', $rfqDisplayItems->count()) }} configured for procurement
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-secondary border font-monospace px-3 py-1.5 d-inline-flex align-items-center gap-1" style="font-size: 12px;">
          <i class="bi bi-link-45deg text-primary fs-6"></i> Linked PR: <strong class="text-dark ms-1">{{ $rfq->purchaseRequisition->pr_number }}</strong>
        </span>
      </div>
    </div>

    <!-- 4 Premium Executive Metric Cards -->
    <div class="p-3 bg-light-subtle border-bottom">
      <div class="row g-3">
        <!-- Card 1: Purchasing Requisition -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Purchasing Requisition
              </span>
              <div class="rounded-2 d-flex align-items-center justify-content-center text-primary flex-shrink-0" style="width: 32px; height: 32px; background: rgba(37, 99, 235, 0.08); font-size: 15px;">
                <i class="bi bi-file-earmark-text"></i>
              </div>
            </div>
            <div class="font-monospace fw-bold text-dark mb-1" style="font-size: 13px; line-height: 1.4; word-break: break-word;">
              {{ $rfq->purchaseRequisition->pr_number }}
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              <i class="bi bi-calendar3 me-1"></i>Lodged: {{ $rfq->purchaseRequisition->created_at?->format('d M Y') ?? '—' }}
            </div>
          </div>
        </div>

        <!-- Card 2: Department & Raised By -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Department &amp; Raised By
              </span>
              <div class="rounded-2 d-flex align-items-center justify-content-center text-info flex-shrink-0" style="width: 32px; height: 32px; background: rgba(14, 165, 233, 0.08); font-size: 15px;">
                <i class="bi bi-building"></i>
              </div>
            </div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
              {{ $rfq->purchaseRequisition->department }}
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              <i class="bi bi-person me-1"></i>{{ $rfq->purchaseRequisition->creator?->name ?? 'System' }}
            </div>
          </div>
        </div>

        <!-- Card 3: Estimated Budget -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Est. Total Budget
              </span>
              <div class="rounded-2 d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.08); font-size: 15px;">
                <i class="bi bi-cash-stack"></i>
              </div>
            </div>
            <div class="font-monospace fw-bold text-success mb-1" style="font-size: 14.5px; line-height: 1.4; word-break: break-word;">
              ₹{{ number_format($rfq->purchaseRequisition->estimatedCostInclGst(), 2) }}
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              Base: ₹{{ number_format($subtotalExclGst, 2) }}
            </div>
          </div>
        </div>

        <!-- Card 4: Sourcing Operations -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Sourcing Operations
              </span>
              <div class="rounded-2 d-flex align-items-center justify-content-center text-warning flex-shrink-0" style="width: 32px; height: 32px; background: rgba(245, 158, 11, 0.08); font-size: 15px;">
                <i class="bi bi-broadcast-pin"></i>
              </div>
            </div>
            <div class="mb-1">
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; font-size:11.5px; padding: 4px 9px; border-radius: 4px;">
                {{ $meta['label'] }}
              </span>
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              <i class="bi bi-shop me-1"></i>{{ $rfq->rfqVendors->count() }} {{ Str::plural('Vendor', $rfq->rfqVendors->count()) }} Dispatched
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Materials Specifications Table -->
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
          <thead class="table-light">
            <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700;">
              <th class="ps-3 text-center" style="width: 35px;">#</th>
              <th>ITEM &amp; DESCRIPTION</th>
              <th class="text-center" style="width: 95px;">QTY</th>
              <th class="text-end" style="width: 110px;">UNIT COST</th>
              <th class="text-center" style="width: 75px;">GST</th>
              <th class="text-end pe-3" style="width: 130px;">LINE TOTAL</th>
            </tr>
          </thead>
          <tbody>
            @php
              // Only approved lines move forward to RFQ (supporting line-item partial approval)
              $rfqDisplayItems = $rfq->purchaseRequisition->items->where('status', '!=', 'rejected');
              if ($rfqDisplayItems->isEmpty()) {
                $rfqDisplayItems = $rfq->purchaseRequisition->items;
              }
            @endphp
            @foreach ($rfqDisplayItems as $item)
            @php
              $baseSubtotal = (float)$item->unit_cost * (int)$item->quantity;
              $gstAmount = $baseSubtotal * ((float)$item->gst_percent / 100);
              $itemTotalWithGst = $item->lineTotalInclGst();
              $gstRateFormatted = (int)$item->gst_percent == (float)$item->gst_percent ? (int)$item->gst_percent : number_format((float)$item->gst_percent, 2);
            @endphp
            <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#itemDetailModal-rfq-{{ $item->id }}" title="Click to view full specifications & financials">
              <td class="ps-3 text-center text-muted fw-semibold" style="font-size: 12px;">
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
                  {{ $item->quantity }} {{ $item->unit ?: 'Units' }}
                </span>
              </td>
              <td class="text-end py-2.5 text-nowrap fw-semibold text-secondary font-monospace">
                ₹{{ number_format($item->unit_cost, 2) }}
              </td>
              <td class="text-center py-2.5 text-nowrap">
                <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 11px;">
                  {{ $gstRateFormatted }}%
                </span>
              </td>
              <td class="text-end pe-3 py-2.5 text-nowrap fw-bold text-dark font-monospace">
                ₹{{ number_format($itemTotalWithGst, 2) }}
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            @php
              $approvedSubtotal = $rfqDisplayItems->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
              $approvedTotalGst = $rfqDisplayItems->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity * ((float)$i->gst_percent / 100));
              $approvedGrandTotal = $rfqDisplayItems->sum(fn ($i) => $i->lineTotalInclGst());
            @endphp
            <tr style="font-size: 12.5px; color: #64748b; background: #f8fafc; border-top: 1.5px solid #e2e8f0;">
              <td colspan="5" class="text-end py-2 fw-semibold">Subtotal (Excl. GST):</td>
              <td class="text-end pe-3 py-2 font-monospace fw-semibold text-dark">₹{{ number_format($approvedSubtotal, 2) }}</td>
            </tr>
            <tr style="font-size: 12.5px; color: #64748b; background: #f8fafc;">
              <td colspan="5" class="text-end py-2 fw-semibold">Estimated GST (18%):</td>
              <td class="text-end pe-3 py-2 font-monospace fw-semibold text-dark">₹{{ number_format(max(0, $approvedTotalGst), 2) }}</td>
            </tr>
            <tr style="font-size: 13.5px; font-weight: 700; background: #f1f5f9; border-top: 2px solid #cbd5e1;">
              <td colspan="5" class="text-end py-2 text-dark">Grand Total (Incl. GST):</td>
              <td class="text-end pe-3 py-2 font-monospace text-primary fw-bold fs-6">
                ₹{{ number_format($approvedGrandTotal, 2) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Supporting Documents & Specifications Card (Specs, Drawings, Terms) -->
  @php
    $rfqDocs = $rfq->attachments;
    $prDocs = $rfq->purchaseRequisition?->attachments ?? collect();
    $totalDocsCount = $rfqDocs->count() + $prDocs->count();
  @endphp
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-2.5 px-3 d-flex align-items-center justify-content-between border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-paperclip text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">Attached Documents, Drawings &amp; Specifications</h6>
      </div>
      <span class="badge bg-light text-dark border">{{ $totalDocsCount }} {{ Str::plural('Document', $totalDocsCount) }}</span>
    </div>
    <div class="card-body p-3">
      @if ($totalDocsCount === 0)
        <div class="p-3 text-center text-muted small bg-light rounded-2 border">
          <i class="bi bi-folder2-open d-block fs-4 text-secondary mb-1"></i>
          No additional documents or technical drawings attached to this RFQ. Standard institutional terms apply.
        </div>
      @else
        <div class="row g-2">
          @foreach ($rfqDocs as $doc)
            @php
              $ext = strtolower(pathinfo($doc->original_name ?: $doc->file_path, PATHINFO_EXTENSION));
              $iconClass = match($ext) {
                'pdf' => 'bi-file-earmark-pdf-fill text-danger',
                'doc', 'docx' => 'bi-file-earmark-word-fill text-primary',
                'xls', 'xlsx' => 'bi-file-earmark-excel-fill text-success',
                'png', 'jpg', 'jpeg' => 'bi-file-earmark-image-fill text-info',
                'zip', 'rar' => 'bi-file-earmark-zip-fill text-warning',
                default => 'bi-file-earmark-text-fill text-secondary',
              };
            @endphp
            <div class="col-12 col-md-6 col-lg-4">
              <div class="p-2.5 rounded-2 border bg-light d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i class="bi {{ $iconClass }} fs-4"></i>
                  <div class="text-truncate">
                    <div class="fw-semibold text-dark text-truncate small" title="{{ $doc->original_name }}">{{ $doc->original_name ?: 'Document' }}</div>
                    <div class="text-muted" style="font-size: 11px;">RFQ Specification • {{ optional($doc->created_at)->format('d M Y') }}</div>
                  </div>
                </div>
                <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-shrink-0" title="Download Document">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </div>
          @endforeach

          @foreach ($prDocs as $doc)
            @php
              $ext = strtolower(pathinfo($doc->original_name ?: $doc->file_path, PATHINFO_EXTENSION));
              $iconClass = match($ext) {
                'pdf' => 'bi-file-earmark-pdf-fill text-danger',
                'doc', 'docx' => 'bi-file-earmark-word-fill text-primary',
                'xls', 'xlsx' => 'bi-file-earmark-excel-fill text-success',
                'png', 'jpg', 'jpeg' => 'bi-file-earmark-image-fill text-info',
                'zip', 'rar' => 'bi-file-earmark-zip-fill text-warning',
                default => 'bi-file-earmark-text-fill text-secondary',
              };
            @endphp
            <div class="col-12 col-md-6 col-lg-4">
              <div class="p-2.5 rounded-2 border bg-light d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i class="bi {{ $iconClass }} fs-4"></i>
                  <div class="text-truncate">
                    <div class="fw-semibold text-dark text-truncate small" title="{{ $doc->original_name }}">{{ $doc->original_name ?: 'Requisition Document' }}</div>
                    <div class="text-muted" style="font-size: 11px;">Linked PR Attachment</div>
                  </div>
                </div>
                <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-shrink-0" title="Download Document">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <!-- Item Full Detail Modals for RFQ -->
  @foreach ($rfqDisplayItems as $item)
  @php
    $itemBase = (float)$item->unit_cost * (int)$item->quantity;
    $itemGst = $itemBase * ((float)$item->gst_percent / 100);
    $itemTotal = $item->lineTotalInclGst();
    $gstFormatted = (int)$item->gst_percent == (float)$item->gst_percent ? (int)$item->gst_percent : number_format((float)$item->gst_percent, 2);
  @endphp
  <div class="modal fade" id="itemDetailModal-rfq-{{ $item->id }}" tabindex="-1" aria-labelledby="itemModalLabel-rfq-{{ $item->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg rounded-3">
        <div class="modal-header bg-light border-bottom py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
              #{{ $loop->iteration }}
            </div>
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0" id="itemModalLabel-rfq-{{ $item->id }}">
                {{ $item->item_name }}
              </h6>
              <div class="text-muted small" style="font-size: 11.5px;">Requisition Material Specification &amp; Pricing Breakdown</div>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- 4 Grid Metric Boxes -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="p-3 bg-light rounded-2 border">
                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Quantity</div>
                <div class="fw-bold text-dark fs-5 mt-1">{{ $item->quantity }} <span class="fs-6 text-muted fw-normal">{{ $item->unit ?: 'Units' }}</span></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 bg-light rounded-2 border">
                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Est. Unit Rate</div>
                <div class="fw-bold text-secondary fs-5 mt-1 font-monospace">₹{{ number_format($item->unit_cost, 2) }}</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 bg-light rounded-2 border">
                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">GST Rate</div>
                <div class="fw-bold text-dark fs-5 mt-1 font-monospace">{{ $gstFormatted }}%</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-3 bg-primary-subtle rounded-2 border border-primary-subtle">
                <div class="text-primary text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total (Incl. GST)</div>
                <div class="fw-bold text-primary fs-5 mt-1 font-monospace">₹{{ number_format($itemTotal, 2) }}</div>
              </div>
            </div>
          </div>

          <!-- Technical Specifications & Description -->
          <div class="mb-4">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
              <i class="bi bi-card-text text-primary me-1"></i> Technical Specifications &amp; Scope of Work
            </h6>
            @if (!empty($item->description))
              <div class="p-3 rounded-2 border bg-white text-dark shadow-2xs" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap; border-left: 3px solid #2563eb !important;">
                {{ $item->description }}
              </div>
            @else
              <div class="p-3 rounded-2 border bg-light-subtle text-muted fst-italic" style="font-size: 12.5px; line-height: 1.5;">
                <i class="bi bi-info-circle me-1"></i> No custom specifications provided. Standard procurement specifications apply for {{ $item->item_name }}. Refer to institutional quality requirements.
              </div>
            @endif
          </div>

          <!-- Vendor Quotes Link Notice -->
          <div class="alert alert-light border d-flex align-items-center justify-content-between p-2.5 mb-3" style="font-size: 12.5px;">
            <div>
              <i class="bi bi-shop text-primary me-1"></i>
              <span>Vendor bids and commercial terms for this RFQ are evaluated in the <strong>Quotation Matrix</strong> below.</span>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2" data-bs-dismiss="modal" onclick="document.getElementById('quotationMatrixSection')?.scrollIntoView({behavior: 'smooth'})">
              View Vendor Quotes &darr;
            </button>
          </div>

          <!-- Commercial & Tax Breakdown Table -->
          <div class="table-responsive border rounded-2 mb-3">
            <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th class="ps-3 py-2">Commercial Component</th>
                  <th class="text-center py-2">Calculation Basis</th>
                  <th class="text-end pe-3 py-2">Amount (INR)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="ps-3 py-2">Base Cost (Excl. Taxes)</td>
                  <td class="text-center py-2 text-muted">{{ $item->quantity }} {{ $item->unit ?: 'Units' }} &times; ₹{{ number_format($item->unit_cost, 2) }}</td>
                  <td class="text-end pe-3 py-2 font-monospace fw-semibold">₹{{ number_format($itemBase, 2) }}</td>
                </tr>
                <tr>
                  <td class="ps-3 py-2">Goods &amp; Services Tax (GST)</td>
                  <td class="text-center py-2 text-muted">{{ $gstFormatted }}% on ₹{{ number_format($itemBase, 2) }}</td>
                  <td class="text-end pe-3 py-2 font-monospace text-secondary">₹{{ number_format($itemGst, 2) }}</td>
                </tr>
                <tr class="table-light border-top fw-bold">
                  <td class="ps-3 py-2 text-dark">Estimated Line Item Budget (Total)</td>
                  <td class="text-center py-2 text-muted">All-inclusive</td>
                  <td class="text-end pe-3 py-2 font-monospace text-primary fs-6">₹{{ number_format($itemTotal, 2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Metadata Footer -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 text-muted small" style="font-size: 12px;">
            <span><i class="bi bi-diagram-2 me-1"></i> Requisition: <strong>{{ $rfq->purchaseRequisition->pr_number }}</strong></span>
            <span><i class="bi bi-building me-1"></i> Department: <strong>{{ $rfq->purchaseRequisition->department }}</strong></span>
            <span><i class="bi bi-clock-history me-1"></i> Required By: <strong>{{ optional($rfq->purchaseRequisition->required_by)->format('d M Y') ?? 'Standard' }}</strong></span>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4 border-top">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  @endforeach

  <!-- Full-Width Commercial Quotation Matrix Section (Comparison Parameters) -->
  @php
    $ccs = $rfq->costComparativeStatement;
    $quotes = $ccs?->quotes ?? collect();
    $quotesByVendor = $quotes->keyBy('vendor_id');
    $hasSavedQuotes = $quotes->isNotEmpty() && $quotes->contains(fn($q) => (float)$q->quoted_cost > 0);
    $selectedVendorId = $ccs?->selected_vendor_id;
  @endphp

  <div class="row mt-3 mb-4" id="quotationMatrixSection">
    <div class="col-12">
      <div class="card shadow-sm border-0">
        
        <!-- Clean Card Header with Navigation Tabs & Action Controls -->
        <div class="card-header bg-white py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 15px;">
              <i class="bi bi-table text-primary"></i>
              <span>Commercial Quotation Matrix &amp; Comparison Parameters</span>
            </h6>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-primary active fw-semibold px-3 py-1" id="tabBtnMatrix" onclick="switchMatrixTab('matrix')" style="font-size: 12px;">
                <i class="bi bi-layout-three-columns me-1"></i>Comparison Matrix
              </button>
              <button type="button" class="btn btn-outline-secondary text-dark fw-semibold px-3 py-1" id="tabBtnVendors" onclick="switchMatrixTab('vendors')" style="font-size: 12px;">
                <i class="bi bi-shop me-1"></i>Invited Vendors ({{ $rfq->rfqVendors->count() }})
              </button>
            </div>
            @if ($hasSavedQuotes)
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                <i class="bi bi-check2-circle me-1"></i>Quotes Evaluated
              </span>
            @else
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                <i class="bi bi-clock-history me-1"></i>Awaiting Quotes
              </span>
            @endif
          </div>

          @if ($canManage)
            <div>
              <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold shadow-sm" id="matrixModeToggleBtn" onclick="toggleMatrixMode()">
                <i class="bi {{ $hasSavedQuotes ? 'bi-pencil-square' : 'bi-eye' }}" id="modeIcon"></i>
                <span id="modeLabel">{{ $hasSavedQuotes ? 'Edit Quotations' : 'View Comparison' }}</span>
              </button>
            </div>
          @endif
        </div>

        <div class="card-body p-0">

          <!-- TAB 1: COMMERCIAL QUOTATIONS MATRIX -->
          <div id="tabContentMatrix">
            <!-- 1. COMPARISON MATRIX VIEW (Clean Light Table matching image parameters) -->
            <div id="matrixComparisonView" class="{{ $hasSavedQuotes ? 'd-block' : ($canManage ? 'd-none' : 'd-block') }}">
              @if ($rfq->rfqVendors->isEmpty())
                <div class="p-5 text-center text-muted">
                  No vendors dispatched for this RFQ.
                </div>
              @else
                <div class="table-responsive">
                  <table class="table table-bordered align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4 py-3 bg-light text-dark fw-bold" style="width: 220px; min-width: 200px; white-space: nowrap;">
                          Comparison Parameters
                        </th>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $rank = $savedQuote?->rank;
                            $isL1 = ($rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <th class="text-center py-3 px-3 {{ $isChosen ? 'bg-primary-subtle bg-opacity-25 border-primary' : ($isL1 ? 'bg-success-subtle bg-opacity-25' : '') }}" style="min-width: 185px;">
                            <div class="fw-bold text-dark" style="font-size: 14px;">
                              Vendor {{ chr(65 + $loop->index) }}
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-center gap-1 mt-1">
                              @if ($isChosen)
                                <span class="badge bg-primary text-white px-2 py-0.5 rounded-pill" style="font-size: 10.5px;">
                                  <i class="bi bi-award-fill me-1"></i>Selected Best
                                </span>
                              @endif
                              @if ($isL1)
                                <span class="badge bg-success text-white px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                  <i class="bi bi-trophy-fill me-1"></i>L1 Lowest
                                </span>
                              @elseif ($rank)
                                <span class="badge bg-secondary text-white px-2 py-0.5 rounded-pill" style="font-size: 10px;">
                                  {{ $rank }}
                                </span>
                              @endif
                            </div>
                          </th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Row 1: Vendor Name -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Vendor Name
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          <td class="text-center py-2.5">
                            <div class="fw-bold text-dark">{{ $rv->vendor->company_name }}</div>
                            <div class="text-muted font-monospace small" style="font-size: 11px;">{{ $rv->vendor->vendor_code }}</div>
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 2: Quoted Basic Price -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Quoted Basic Price
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5 font-monospace fw-medium text-dark">
                            ₹{{ number_format((float)($savedQuote?->basic_price ?? $savedQuote?->quoted_cost ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 3: GST -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          GST
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5 font-monospace text-secondary">
                            ₹{{ number_format((float)($savedQuote?->gst_amount ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 4: Transportation -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Transportation
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5 font-monospace text-secondary">
                            ₹{{ number_format((float)($savedQuote?->transportation_cost ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 5: Total Quoted Amount (Highlighted Matrix Row) -->
                      <tr class="table-light border-top border-bottom">
                        <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 14px;">
                          Total Quoted Amount
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isL1 = ($savedQuote?->rank === 'L1');
                          @endphp
                          <td class="text-center py-3 font-monospace {{ $isL1 ? 'bg-success-subtle bg-opacity-50 text-success fw-bolder' : 'fw-bold text-dark' }}" style="font-size: 15px;">
                            ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 6: Delivery Timeline -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Delivery Timeline
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5">
                            {{ $savedQuote?->delivery_days ? $savedQuote->delivery_days . ' Days' : '—' }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 7: Warranty -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Warranty
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5">
                            {{ $savedQuote?->warranty ?: '—' }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 8: Payment Terms -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Payment Terms
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5">
                            {{ $savedQuote?->payment_terms ?: '—' }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 9: Technical Compliance -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Technical Compliance
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $tc = $savedQuote?->technical_compliance ?: 'Yes';
                          @endphp
                          <td class="text-center py-2.5">
                            @if ($tc === 'Yes')
                              <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-semibold">
                                Yes
                              </span>
                            @elseif ($tc === 'Partial')
                              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 fw-semibold">
                                Partial
                              </span>
                            @else
                              <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-semibold">
                                No
                              </span>
                            @endif
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 10: Quotation Validity -->
                      <tr>
                        <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                          Quotation Validity
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                          <td class="text-center py-2.5">
                            {{ $savedQuote?->quotation_validity ?: '—' }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 11: Vendor Selection Action (Manual Selection with Remarks) -->
                      <tr class="table-light border-top-2">
                        <td class="ps-4 py-3 fw-bold text-dark bg-light">
                          <div>Award / Best-Vendor Decision</div>
                          <div class="small text-muted fw-normal" style="font-size: 11px;">Select vendor regardless of price rank</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                            $hasValidQuote = ($savedQuote && (float)$savedQuote->quoted_cost > 0);
                          @endphp
                          <td class="text-center py-3 px-2 {{ $isChosen ? 'bg-primary-subtle bg-opacity-25' : '' }}">
                            @if ($isChosen)
                              <div class="badge bg-primary text-white px-2.5 py-1.5 mb-1 d-inline-flex align-items-center gap-1 shadow-xs" style="font-size: 11.5px;">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Awarded Choice</span>
                              </div>
                              @if ($ccs?->selection_remarks)
                                <div class="small text-muted fst-italic px-1 text-wrap" style="font-size: 11px; max-width: 170px; margin: 0 auto; line-height: 1.3;" title="{{ $ccs->selection_remarks }}">
                                  "{{ Str::limit($ccs->selection_remarks, 65) }}"
                                </div>
                              @endif
                            @elseif ($canManage && $hasValidQuote)
                              <button type="button" 
                                      class="btn btn-sm btn-outline-primary py-1 px-2.5 text-nowrap d-inline-flex align-items-center gap-1 fw-semibold" 
                                      style="font-size: 11.5px;" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#selectVendorModal-{{ $rv->vendor_id }}"
                                      title="Choose {{ $rv->vendor->company_name }} as the best vendor for this requirement">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <span>Select as Best</span>
                              </button>
                            @else
                              <span class="text-muted small">—</span>
                            @endif
                          </td>
                        @endforeach
                      </tr>
                    </tbody>
                  </table>
                </div>

                @if ($selectedVendorId && $ccs?->selectedVendor)
                  <div class="m-3 p-3 bg-light border border-primary-subtle rounded-3 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 shadow-2xs">
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="bi bi-award-fill fs-6"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px;">
                          Selected Vendor for Project: <span class="text-primary">{{ $ccs->selectedVendor->company_name }}</span> ({{ $ccs->selectedVendor->vendor_code }})
                        </div>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                          <strong>Justification / Remarks:</strong> {{ $ccs->selection_remarks ?: 'Manual best-vendor selection.' }}
                          @if ($ccs->selectedByUser)
                            <span class="text-muted">• Selected by {{ $ccs->selectedByUser->name }} on {{ optional($ccs->selected_at)->format('d M Y, h:i A') }}</span>
                          @endif
                        </div>
                      </div>
                    </div>
                    @if ($canManage)
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($ccs?->selectedVendor?->email)
                          <form method="POST" action="{{ route('rfq.resend-award-email', $rfq) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-medium text-nowrap" style="font-size: 12px;" title="Send / Resend award notification email to {{ $ccs->selectedVendor->email }}">
                              <i class="bi bi-envelope-arrow-up"></i>
                              <span>{{ $ccs->award_email_sent_at ? 'Resend Award Email' : 'Send Award Email' }}</span>
                            </button>
                          </form>
                        @endif

                        @if (!$rfq->purchaseRequisition?->purchaseOrders()->exists() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
                          <a href="{{ route('po.create', ['pr_id' => $rfq->purchase_requisition_id, 'vendor_id' => $selectedVendorId]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-3 py-1.5 fw-semibold shadow-sm text-nowrap">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Generate PO</span>
                          </a>
                        @endif
                      </div>
                    @endif
                  </div>
                @endif
              @endif
            </div>

            <!-- 2. EDIT / DATA ENTRY FORM VIEW (Clean light form without text clutter) -->
            @if ($canManage)
              <div id="matrixEditView" class="{{ $hasSavedQuotes ? 'd-none' : 'd-block' }}">
                <form method="POST" action="{{ route('rfq.save-quotations', $rfq) }}" id="quotationMatrixEntryForm">
                  @csrf
                  <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 13px;">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-4 py-3 bg-light text-dark fw-bold text-uppercase" style="width: 220px; min-width: 200px; white-space: nowrap;">
                            Comparison Parameters
                          </th>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php
                              $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            @endphp
                            <th class="text-center py-3 px-3" style="min-width: 175px;">
                              <div class="fw-bold text-dark" style="font-size: 13.5px;">
                                Vendor {{ chr(65 + $loop->index) }}
                              </div>
                              <div class="text-secondary small fw-normal text-truncate" style="max-width: 165px; margin: 0 auto;">
                                {{ $rv->vendor->company_name }}
                              </div>
                              <div class="text-muted font-monospace small" style="font-size: 11px;">
                                {{ $rv->vendor->vendor_code }}
                              </div>
                              <input type="hidden" name="quotes[{{ $loop->index }}][vendor_id]" value="{{ $rv->vendor_id }}">
                            </th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Row 1: Vendor Name -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Vendor Name
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            <td class="text-center py-2.5 fw-bold text-dark">
                              {{ $rv->vendor->company_name }}
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 2: Quoted Basic Price -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Quoted Basic Price (₹)
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm flex-nowrap" style="max-width: 155px; margin: 0 auto;">
                                <span class="input-group-text bg-white px-2">₹</span>
                                <input type="number" step="0.01" min="0" 
                                       name="quotes[{{ $loop->index }}][basic_price]" 
                                       class="form-control text-end quote-basic" 
                                       data-index="{{ $loop->index }}" 
                                       placeholder="0.00" 
                                       value="{{ old('quotes.'.$loop->index.'.basic_price', $savedQuote?->basic_price ?? $savedQuote?->quoted_cost) }}" 
                                       oninput="recalcTotalAmount({{ $loop->index }})">
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 3: GST -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            GST (₹)
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm flex-nowrap" style="max-width: 155px; margin: 0 auto;">
                                <span class="input-group-text bg-white px-2">₹</span>
                                <input type="number" step="0.01" min="0" 
                                       name="quotes[{{ $loop->index }}][gst_amount]" 
                                       class="form-control text-end quote-gst" 
                                       data-index="{{ $loop->index }}" 
                                       placeholder="0.00" 
                                       value="{{ old('quotes.'.$loop->index.'.gst_amount', $savedQuote?->gst_amount ?? 0) }}" 
                                       oninput="recalcTotalAmount({{ $loop->index }})">
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 4: Transportation -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Transportation (₹)
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm flex-nowrap" style="max-width: 155px; margin: 0 auto;">
                                <span class="input-group-text bg-white px-2">₹</span>
                                <input type="number" step="0.01" min="0" 
                                       name="quotes[{{ $loop->index }}][transportation_cost]" 
                                       class="form-control text-end quote-transport" 
                                       data-index="{{ $loop->index }}" 
                                       placeholder="0.00" 
                                       value="{{ old('quotes.'.$loop->index.'.transportation_cost', $savedQuote?->transportation_cost ?? 0) }}" 
                                       oninput="recalcTotalAmount({{ $loop->index }})">
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 5: Total Quoted Amount -->
                        <tr class="table-active">
                          <td class="ps-4 py-2.5 fw-bold text-dark">
                            Total Quoted Amount (₹)
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="text-center py-2.5">
                              <span class="fw-bold text-primary font-monospace fs-6" id="editTotalDisplay_{{ $loop->index }}">
                                ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                              </span>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 6: Delivery Timeline -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Delivery Timeline
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm flex-nowrap" style="max-width: 130px; margin: 0 auto;">
                                <input type="number" min="1" 
                                       name="quotes[{{ $loop->index }}][delivery_days]" 
                                       class="form-control text-center" 
                                       placeholder="Days" 
                                       value="{{ old('quotes.'.$loop->index.'.delivery_days', $savedQuote?->delivery_days ?? 10) }}">
                                <span class="input-group-text bg-white px-2">Days</span>
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 7: Warranty -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Warranty
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <input type="text" 
                                     name="quotes[{{ $loop->index }}][warranty]" 
                                     class="form-control form-control-sm text-center" 
                                     style="max-width: 140px; margin: 0 auto;"
                                     placeholder="e.g. 2 Years" 
                                     value="{{ old('quotes.'.$loop->index.'.warranty', $savedQuote?->warranty ?? '2 Years') }}">
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 8: Payment Terms -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Payment Terms
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <input type="text" 
                                     name="quotes[{{ $loop->index }}][payment_terms]" 
                                     class="form-control form-control-sm text-center" 
                                     style="max-width: 140px; margin: 0 auto;"
                                     placeholder="e.g. 30 Days" 
                                     value="{{ old('quotes.'.$loop->index.'.payment_terms', $savedQuote?->payment_terms ?? '30 Days') }}">
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 9: Technical Compliance -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Technical Compliance
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2 text-center">
                              <select name="quotes[{{ $loop->index }}][technical_compliance]" class="form-select form-select-sm text-center" style="max-width: 120px; margin: 0 auto;">
                                <option value="Yes" {{ (old('quotes.'.$loop->index.'.technical_compliance', $savedQuote?->technical_compliance ?? 'Yes') === 'Yes') ? 'selected' : '' }}>Yes</option>
                                <option value="Partial" {{ (old('quotes.'.$loop->index.'.technical_compliance', $savedQuote?->technical_compliance) === 'Partial') ? 'selected' : '' }}>Partial</option>
                                <option value="No" {{ (old('quotes.'.$loop->index.'.technical_compliance', $savedQuote?->technical_compliance) === 'No') ? 'selected' : '' }}>No</option>
                              </select>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 10: Quotation Validity -->
                        <tr>
                          <td class="ps-4 py-2.5 fw-semibold text-secondary bg-light-subtle">
                            Quotation Validity
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <input type="text" 
                                     name="quotes[{{ $loop->index }}][quotation_validity]" 
                                     class="form-control form-control-sm text-center" 
                                     style="max-width: 140px; margin: 0 auto;"
                                     placeholder="e.g. 30 Days" 
                                     value="{{ old('quotes.'.$loop->index.'.quotation_validity', $savedQuote?->quotation_validity ?? '30 Days') }}">
                            </td>
                          @endforeach
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="p-3 bg-light d-flex justify-content-end border-top">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold shadow-sm">
                      <i class="bi bi-save2-fill"></i>
                      <span>Save &amp; Evaluate Quotations</span>
                    </button>
                  </div>
                </form>
              </div>
            @endif
          </div>

          <!-- TAB 2: INVITED VENDORS DIRECTORY -->
          <div id="tabContentVendors" class="d-none">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                  <tr class="text-muted" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <th class="ps-4 py-3">Vendor Name &amp; Code</th>
                    <th class="py-3">Category</th>
                    <th class="py-3">Contact Email / Phone</th>
                    <th class="text-center py-3">Quoted Total</th>
                    <th class="text-center py-3">Email Notification</th>
                    <th class="text-end pe-4 py-3">Status / Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($rfq->rfqVendors as $rv)
                    @php $sq = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                    <tr>
                      <td class="ps-4 py-3">
                        <div class="fw-bold text-dark" style="font-size: 13.5px;">{{ $rv->vendor->company_name ?? '—' }}</div>
                        <div class="text-muted font-monospace small" style="font-size: 11px;">{{ $rv->vendor->vendor_code ?? '—' }}</div>
                      </td>
                      <td class="py-3 text-secondary">
                        {{ $rv->vendor->category ?: 'General Goods' }}
                      </td>
                      <td class="py-3 text-secondary">
                        <div>{{ $rv->vendor->email ?: '—' }}</div>
                        @if ($rv->vendor->phone_number)
                          <div class="small text-muted">{{ $rv->vendor->phone_number }}</div>
                        @endif
                      </td>
                      <td class="text-center py-3 font-monospace fw-semibold">
                        @if ($sq && $sq->quoted_cost > 0)
                          <span class="text-dark">₹{{ number_format($sq->quoted_cost, 2) }}</span>
                          @if ($sq->rank === 'L1')
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10px;">L1</span>
                          @endif
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-center py-3">
                        @if ($rv->email_sent_at)
                          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;" title="Sent: {{ $rv->email_sent_at->format('d M Y, h:i A') }}">
                            <i class="bi bi-envelope-check me-1"></i>Sent ({{ $rv->email_sent_at->format('d M') }})
                          </span>
                        @else
                          <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">
                            <i class="bi bi-envelope-dash me-1"></i>Not Sent
                          </span>
                        @endif
                      </td>
                      <td class="text-end pe-4 py-3">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                          @if ($rv->response_status === 'received')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                              <i class="bi bi-check2-circle me-1"></i>Received
                            </span>
                          @else
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1">
                              <i class="bi bi-hourglass me-1"></i>Awaiting
                            </span>
                          @endif

                          @if ($canManage && $rv->vendor && !empty($rv->vendor->email))
                            <form method="POST" action="{{ route('rfq.vendor.resend-email', ['rfq' => $rfq, 'vendor' => $rv->vendor]) }}" class="d-inline">
                              @csrf
                              <button type="submit" class="btn btn-sm btn-outline-secondary py-0.5 px-2 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="Resend complete RFQ details email to {{ $rv->vendor->email }}">
                                <i class="bi bi-envelope-arrow-up"></i>
                                <span>{{ $rv->email_sent_at ? 'Resend' : 'Send' }}</span>
                              </button>
                            </form>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="p-4 text-center text-muted">
                        No vendors dispatched for this RFQ.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching between Matrix and Vendors Directory
    function switchMatrixTab(tab) {
      const matrixTab = document.getElementById('tabContentMatrix');
      const vendorsTab = document.getElementById('tabContentVendors');
      const btnMatrix = document.getElementById('tabBtnMatrix');
      const btnVendors = document.getElementById('tabBtnVendors');
      const toggleBtn = document.getElementById('matrixModeToggleBtn');

      if (tab === 'vendors') {
        matrixTab?.classList.add('d-none');
        vendorsTab?.classList.remove('d-none');
        btnMatrix?.classList.remove('btn-primary', 'active');
        btnMatrix?.classList.add('btn-outline-secondary', 'text-dark');
        btnVendors?.classList.add('btn-primary', 'active');
        btnVendors?.classList.remove('btn-outline-secondary', 'text-dark');
        if (toggleBtn) toggleBtn.style.display = 'none';
      } else {
        vendorsTab?.classList.add('d-none');
        matrixTab?.classList.remove('d-none');
        btnVendors?.classList.remove('btn-primary', 'active');
        btnVendors?.classList.add('btn-outline-secondary', 'text-dark');
        btnMatrix?.classList.add('btn-primary', 'active');
        btnMatrix?.classList.remove('btn-outline-secondary', 'text-dark');
        if (toggleBtn) toggleBtn.style.display = '';
      }
    }

    // Toggle between Comparison View and Edit Data Entry View
    function toggleMatrixMode() {
      const compView = document.getElementById('matrixComparisonView');
      const editView = document.getElementById('matrixEditView');
      const modeLabel = document.getElementById('modeLabel');
      const modeIcon = document.getElementById('modeIcon');

      if (!compView || !editView) return;

      if (editView.classList.contains('d-none')) {
        editView.classList.remove('d-none');
        editView.classList.add('d-block');
        compView.classList.remove('d-block');
        compView.classList.add('d-none');
        if (modeLabel) modeLabel.innerText = 'View Comparison';
        if (modeIcon) modeIcon.className = 'bi bi-eye';
      } else {
        editView.classList.remove('d-block');
        editView.classList.add('d-none');
        compView.classList.remove('d-none');
        compView.classList.add('d-block');
        if (modeLabel) modeLabel.innerText = 'Edit Quotations';
        if (modeIcon) modeIcon.className = 'bi bi-pencil-square';
      }
    }

    // Dynamic Recalculation for Data Entry
    function recalcTotalAmount(idx) {
      const basicInput = document.querySelector(`.quote-basic[data-index="${idx}"]`);
      const gstInput = document.querySelector(`.quote-gst[data-index="${idx}"]`);
      const transportInput = document.querySelector(`.quote-transport[data-index="${idx}"]`);
      const displayEl = document.getElementById(`editTotalDisplay_${idx}`);

      const basic = parseFloat(basicInput?.value || 0);
      const gst = parseFloat(gstInput?.value || 0);
      const transport = parseFloat(transportInput?.value || 0);
      const total = basic + gst + transport;

      if (displayEl) {
        displayEl.innerText = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.quote-basic').forEach(el => {
        const idx = el.getAttribute('data-index');
        if (idx !== null) recalcTotalAmount(idx);
      });
    });
  </script>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ Detail' });
@endsection

@section('scripts')
  <!-- Modals for Manual Best-Vendor Selection (rendered at body level outside container to prevent stacking-context or overflow clipping) -->
  @if ($canManage)
    @foreach ($rfq->rfqVendors as $rv)
      @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
      @if ($savedQuote && (float)$savedQuote->quoted_cost > 0)
        <div class="modal fade" id="selectVendorModal-{{ $rv->vendor_id }}" tabindex="-1" aria-labelledby="selectVendorModalLabel-{{ $rv->vendor_id }}" aria-hidden="true" style="z-index: 1060;">
          <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden text-start">
              <form method="POST" action="{{ route('rfq.select-vendor', $rfq) }}">
                @csrf
                <input type="hidden" name="vendor_id" value="{{ $rv->vendor_id }}">
                
                <div class="modal-header bg-white border-bottom py-3 px-4">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 17px;">
                      <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                      <h6 class="modal-title fw-bold text-dark mb-0" id="selectVendorModalLabel-{{ $rv->vendor_id }}">
                        Select {{ $rv->vendor->company_name }}
                      </h6>
                      <div class="text-muted small" style="font-size: 11.5px;">Designate as Awarded / Best Vendor for Project</div>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                  <div class="alert alert-info-subtle border border-info-subtle text-dark p-3 rounded-2 mb-3" style="font-size: 12.5px; line-height: 1.5;">
                    <i class="bi bi-info-circle-fill text-primary me-1.5"></i>
                    You are manually choosing <strong>{{ $rv->vendor->company_name }}</strong> as the best vendor. This decision overrides the automated price rank (e.g. L1 lowest) when creating the Purchase Order.
                  </div>

                  <!-- Commercial Snapshot -->
                  <div class="p-3 bg-light rounded-2 border mb-3">
                    <div class="row g-2 align-items-center">
                      <div class="col-7 border-end">
                        <div class="text-secondary small fw-semibold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Quoted Total (Incl. GST)</div>
                        <div class="fw-bold font-monospace text-dark fs-5 mt-0.5">₹{{ number_format((float)$savedQuote->quoted_cost, 2) }}</div>
                      </div>
                      <div class="col-5 ps-3 text-start">
                        <div class="text-secondary small fw-semibold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Price Ranking</div>
                        <div class="mt-1">
                          <span class="badge {{ $savedQuote->rank === 'L1' ? 'bg-success' : 'bg-secondary' }} px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                            {{ $savedQuote->rank ?: 'Ranked' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Remarks / Justification Field -->
                  <div class="mb-1 text-start">
                    <label class="form-label fw-bold text-dark small mb-1.5">Selection Justification / Remarks <span class="text-danger">*</span></label>
                    <textarea name="selection_remarks" 
                              class="form-control" 
                              rows="3" 
                              required 
                              style="font-size: 13px;"
                              placeholder="e.g. Proven track record, superior technical compliance, faster delivery timeline, institutional warranty terms..."></textarea>
                    <div class="form-text text-muted mt-1.5" style="font-size: 11.5px;">
                      <i class="bi bi-shield-check text-secondary me-1"></i>Remarks will be recorded in the official project audit trail and commercial evaluation dossiers.
                    </div>
                  </div>
                </div>

                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                  <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary btn-sm px-3.5 d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm">
                    <i class="bi bi-check2-circle"></i>
                    <span>Confirm Best-Vendor Selection</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      @endif
    @endforeach
  @endif
@endsection
