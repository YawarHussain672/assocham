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
        
        <!-- Left: RFQ Number + Status + Meta Details -->
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $rfq->rfq_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
            </div>
            
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
              @if ($rfq->sector)
                <span class="detail-meta-chip"><i class="bi bi-tag"></i> <span class="meta-label">Sector:</span> <strong>{{ $rfq->sector }}</strong></span>
              @endif
              @if ($rfq->purchaseRequisition)
                <span class="detail-meta-chip">
                  <i class="bi bi-file-earmark-text"></i>
                  <span class="meta-label">PR:</span>
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
                    <a href="{{ route('requisition.show', $rfq->purchaseRequisition) }}">{{ $rfq->purchaseRequisition->pr_number }}</a>
                  @else
                    <strong>{{ $rfq->purchaseRequisition->pr_number }}</strong>
                  @endif
                </span>
              @endif
              @php
                $rfqProjectName = $rfq->project_name ?: ($rfq->purchaseRequisition?->projectBudget?->project_name ?: $rfq->purchaseRequisition?->event_name);
              @endphp
              @if ($rfqProjectName)
                <span class="detail-meta-chip"><i class="bi bi-building"></i> <span class="meta-label">Project:</span> <strong>{{ $rfqProjectName }}</strong></span>
              @endif
            </div>
          </div>
        </div>

        <!-- Right: Actions -->
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          @if ($canManage && $rfq->status === 'dispatched')
            <button type="button" class="btn btn-sm btn-primary px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px; border-radius: 6px;" onclick="document.getElementById('quotationMatrixSection')?.scrollIntoView({behavior: 'smooth'})">
              <i class="bi bi-pencil-square"></i>
              <span>Enter Quotations &darr;</span>
            </button>
          @endif

          @if ($canManage && isset($activeBuyers) && $activeBuyers->count() > 1)
            <form method="POST" action="{{ route('rfq.assign-buyer', $rfq) }}" class="d-inline-flex align-items-center gap-1.5">
              @csrf
              <select name="poc_user_id" class="form-select form-select-sm bg-white" style="min-width: 175px; font-size: 12px; border-radius: 6px;" aria-label="Assign Buyer / POC">
                @foreach ($activeBuyers as $buyerOption)
                  <option value="{{ $buyerOption->id }}" @selected((int) $rfq->poc_user_id === (int) $buyerOption->id)>
                    {{ $buyerOption->name }}{{ $buyerOption->department ? ' (' . $buyerOption->department . ')' : '' }}
                  </option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap fw-semibold" style="font-size: 12px; border-radius: 6px;">
                Reassign
              </button>
            </form>
          @endif
          @php
            $ccs = $rfq->costComparativeStatement;
            $winningVendorId = $ccs?->selected_vendor_id ?: $ccs?->winningQuote()?->vendor_id;
            $existingPos = $rfq->purchaseRequisition?->purchaseOrders ?? collect();
            $existingPo = $existingPos->first();
            $unassignedItemsCount = $rfq->purchaseRequisition?->items->whereNull('purchase_order_id')->where('status', '!=', 'rejected')->count() ?? 0;
          @endphp
          @if ($existingPos->count() > 1)
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
              <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-2.5 fw-bold font-monospace" style="font-size: 12px;">
                <i class="bi bi-check-all me-1"></i> {{ $existingPos->count() }} POs Generated
              </span>
              @foreach ($existingPos as $po)
                <a href="{{ route('po.show', $po) }}" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-semibold text-decoration-none font-monospace" style="font-size: 12px;" title="{{ $po->vendor?->company_name }}">
                  <i class="bi bi-file-earmark-text me-1"></i> {{ $po->po_number }}
                </a>
              @endforeach
              @if ($unassignedItemsCount > 0 && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po') && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
                <a href="{{ route('po.create', ['pr_id' => $rfq->purchase_requisition_id]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-2.5 py-1 fw-semibold text-decoration-none" style="font-size: 12px; border-radius: 6px;">
                  <i class="bi bi-plus-circle"></i>
                  <span>PO for Remaining Items</span>
                </a>
              @endif
            </div>
          @elseif ($existingPo)
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
              <a href="{{ route('po.show', $existingPo) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold text-decoration-none" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-eye"></i>
                <span>View PO ({{ $existingPo->po_number }})</span>
              </a>
              @if ($unassignedItemsCount > 0 && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po') && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
                <a href="{{ route('po.create', ['pr_id' => $rfq->purchase_requisition_id]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-semibold text-decoration-none" style="font-size: 12px; border-radius: 6px;">
                  <i class="bi bi-plus-circle"></i>
                  <span>PO for Remaining Items</span>
                </a>
              @endif
            </div>
          @elseif ($quotesReceived)
            @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'po') && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
              <a href="{{ route('po.create', array_filter(['pr_id' => $rfq->purchase_requisition_id, 'vendor_id' => $winningVendorId])) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold shadow-sm text-decoration-none" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Generate PO</span>
              </a>
            @endif
          @endif
        </div>

      </div>
    </div>

  </div>

  <!-- Horizontal Procurement Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Step 1: PR Sanctioned -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Requisition Sanctioned">
              <span class="fw-bold" style="font-size: 11px;">1</span>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="PR Sanctioned">
                PR Sanctioned
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
              <span class="fw-bold" style="font-size: 11px;">2</span>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="RFQ Dispatched">
                RFQ Dispatched
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
              <span class="fw-bold" style="font-size: 11px;">3</span>
            </div>
            <div class="h-step-card {{ $quotesCard }}">
              <div class="h-step-title {{ $quotesReceived ? 'text-success' : 'text-primary' }} mb-1" title="Commercial Quotes">
                Commercial Quotes
              </div>
              <div class="h-step-person">
                @if ($quotesReceived)
                  <span class="text-success fw-semibold">Proposals Logged</span>
                @else
                  <span class="text-warning fw-semibold">Awaiting Bids</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $quotesReceived ? 'Received' : 'In Sourcing' }}
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
              <span class="fw-bold" style="font-size: 11px;">4</span>
            </div>
            <div class="h-step-card {{ $hasPo ? 'done-card' : ($quotesReceived ? 'active-card' : 'stopped-card') }}">
              <div class="h-step-title {{ $hasPo ? 'text-success' : ($quotesReceived ? 'text-primary' : 'text-muted') }} mb-1" title="Purchase Order">
                Purchase Order
              </div>
              <div class="h-step-person">
                @if ($hasPo)
                  <a href="{{ route('po.show', $poObj) }}" class="text-decoration-none fw-bold text-success">
                    {{ $poObj->po_number }} &rarr;
                  </a>
                @elseif ($quotesReceived)
                  <span class="text-info fw-semibold">Ready to Issue</span>
                @else
                  <span class="text-muted">Post-Quotation</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $hasPo ? $poObj->created_at?->format('d M, H:i') : ($quotesReceived ? 'Pending Issue' : '—') }}
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
      <div>
        <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
          PR Items &amp; Vendor Award Selection
        </h6>
        <div class="text-muted small mt-0.5" style="font-size: 12px;">
          Select awarded vendors for each PR item below to generate Purchase Orders (single or multi-vendor PO split).
        </div>
      </div>
      @if ($canManage)
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 fw-semibold" style="font-size: 11.5px;">
          <i class="bi bi-diagram-3-fill me-1"></i> Multi-PO Supported
        </span>
      @endif
    </div>

    <!-- 4 Premium Executive Metric Cards -->
    <div class="p-3 bg-light-subtle border-bottom">
      <div class="row g-3">
        <!-- Card 1: Purchasing Requisition -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Purchasing Requisition
              </span>
            </div>
            <div class="font-monospace fw-bold text-dark mb-1" style="font-size: 13px; line-height: 1.4; word-break: break-word;">
              {{ $rfq->purchaseRequisition->pr_number }}
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              Lodged: {{ $rfq->purchaseRequisition->created_at?->format('d M Y') ?? '—' }}
            </div>
          </div>
        </div>

        <!-- Card 2: Department & Raised By -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Department &amp; Raised By
              </span>
            </div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
              {{ $rfq->purchaseRequisition->department }}
            </div>
            <div class="text-muted small" style="font-size: 11.5px;">
              {{ $rfq->purchaseRequisition->creator?->name ?? 'System' }}
            </div>
          </div>
        </div>

        <!-- Card 3: Estimated Budget -->
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
            <div class="mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Est. Total Budget
              </span>
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
            <div class="mb-2">
              <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Sourcing Operations
              </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 12px;">
              {{ $rfq->rfqVendors->count() }} {{ Str::plural('Vendor', $rfq->rfqVendors->count()) }} Dispatched
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
              <th style="min-width: 240px; width: 280px;" class="pe-3">AWARDED VENDOR &amp; PO</th>
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
              $itemVendorId = $item->vendor_id;
            @endphp
            <tr>
              <td class="ps-3 text-center text-muted fw-semibold" style="font-size: 12px;">
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
              <td class="pe-3 py-2">
                @if ($item->purchase_order_id)
                  <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center gap-1 font-monospace" style="font-size: 11.5px;">
                      <i class="bi bi-check-circle-fill"></i>
                      <a href="{{ route('po.show', $item->purchase_order_id) }}" class="text-success text-decoration-none fw-bold">
                        {{ $item->purchaseOrder?->po_number ?? 'PO #' . $item->purchase_order_id }}
                      </a>
                    </span>
                    <span class="text-dark small fw-medium text-truncate" style="max-width: 130px;" title="{{ $item->vendor?->company_name }}">
                      {{ $item->vendor?->company_name ?? 'Awarded Vendor' }}
                    </span>
                  </div>
                @elseif ($canManage)
                  <select name="item_vendors[{{ $item->id }}]" 
                          class="form-select form-select-sm rfq-item-vendor-select" 
                          data-item-id="{{ $item->id }}" 
                          data-item-name="{{ $item->item_name }}"
                          data-line-total="{{ $itemTotalWithGst }}"
                          style="font-size: 12px; border-radius: 6px;"
                          onchange="updateRfqAwardSummary()">
                    <option value="">— Select Awarded Vendor —</option>
                    @foreach ($rfq->rfqVendors as $rv)
                      @php
                        $quote = $quotesByVendor[$rv->vendor_id] ?? null;
                        $rank = $quote?->rank;
                        $isSelected = ($itemVendorId == $rv->vendor_id);
                      @endphp
                      <option value="{{ $rv->vendor_id }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $rv->vendor->company_name }} {{ $rank ? "($rank)" : '' }}
                      </option>
                    @endforeach
                  </select>
                @else
                  @if ($item->vendor)
                    <span class="badge bg-light text-dark border px-2 py-1">
                      {{ $item->vendor->company_name }}
                    </span>
                  @else
                    <span class="text-muted small fst-italic">Pending Evaluation</span>
                  @endif
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot class="border-top">
            @php
              $approvedSubtotal = $rfqDisplayItems->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
              $approvedTotalGst = $rfqDisplayItems->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity * ((float)$i->gst_percent / 100));
              $approvedGrandTotal = $rfqDisplayItems->sum(fn ($i) => $i->lineTotalInclGst());
              $rfqGstPercent = $approvedSubtotal > 0 ? ($approvedTotalGst / $approvedSubtotal) * 100 : 0;
              $rfqGstFormatted = (int)$rfqGstPercent == (float)$rfqGstPercent ? (int)$rfqGstPercent : number_format($rfqGstPercent, 2);
            @endphp
            <tr style="font-size: 13px; background: #ffffff;">
              <td colspan="5" class="text-end py-2 text-muted fw-normal">Subtotal (Excl. GST):</td>
              <td class="text-end pe-3 py-2 font-monospace text-secondary fw-semibold">₹{{ number_format($approvedSubtotal, 2) }}</td>
              <td></td>
            </tr>
            <tr style="font-size: 13px; background: #ffffff;">
              <td colspan="5" class="text-end py-1 text-muted fw-normal">Estimated GST ({{ $rfqGstFormatted }}%):</td>
              <td class="text-end pe-3 py-1 font-monospace text-secondary fw-semibold">₹{{ number_format(max(0, $approvedTotalGst), 2) }}</td>
              <td></td>
            </tr>
            <tr style="font-size: 14px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
              <td colspan="5" class="text-end py-3 text-dark fw-bold">Grand Total (Incl. GST):</td>
              <td class="text-end pe-3 py-3 font-monospace fw-bold fs-6" style="color: #c8102e;">
                ₹{{ number_format($approvedGrandTotal, 2) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- Item-Wise Multi-Vendor Award & PO Generation Panel --}}
    @php
      $unassignedItems = $rfqDisplayItems->whereNull('purchase_order_id');
    @endphp
    @if ($canManage && $unassignedItems->isNotEmpty())
      <div class="card-footer bg-light p-3 border-top">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white" style="font-size: 11px;">Multi-Vendor Awards</span>
            <span class="fw-semibold text-dark small" id="rfqAwardSummaryTitle">
              Assign vendors in the table above to award PR items
            </span>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
            @if ($winningVendorId)
              <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" onclick="applyWinningVendorToAllItems({{ $winningVendorId }})">
                <i class="bi bi-arrow-repeat"></i>
                <span>Award All to Winner (L1)</span>
              </button>
            @endif
            @if (!$quotesReceived)
              <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5" onclick="document.getElementById('quotationMatrixSection')?.scrollIntoView({behavior: 'smooth'})">
                <i class="bi bi-pencil-square"></i>
                <span>Enter / Compare Quotes &darr;</span>
              </button>
            @endif
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold shadow-sm" onclick="openGeneratePosModal()">
              <i class="bi bi-receipt-cutoff"></i>
              <span id="rfqGeneratePoBtnLabel">Generate Separate POs</span>
            </button>
          </div>
        </div>
      </div>
    @endif
  </div>

  <!-- Supporting Documents & Specifications Card (Specs, Drawings, Terms) -->
  @php
    $rfqDocs = $rfq->attachments;
    $prDocs = $rfq->purchaseRequisition?->attachments ?? collect();
    $totalDocsCount = $rfqDocs->count() + $prDocs->count();
  @endphp
  @if ($totalDocsCount > 0)
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-2.5 px-3 d-flex align-items-center justify-content-between border-bottom">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">Attached Documents, Drawings &amp; Specifications</h6>
      </div>
      <span class="badge bg-light text-dark border">{{ $totalDocsCount }} {{ Str::plural('Document', $totalDocsCount) }}</span>
    </div>
    <div class="card-body p-3">
      <div class="row g-2">
        @foreach ($rfqDocs as $doc)
          <div class="col-12 col-md-6 col-lg-4">
            <div class="p-2.5 rounded-2 border bg-light d-flex align-items-center justify-content-between gap-2">
              <div class="text-truncate">
                <div class="fw-semibold text-dark text-truncate small" title="{{ $doc->original_name }}">{{ $doc->original_name ?: 'Document' }}</div>
                <div class="text-muted" style="font-size: 11px;">RFQ Specification • {{ optional($doc->created_at)->format('d M Y') }}</div>
              </div>
              <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-shrink-0" title="Download Document">
                <i class="bi bi-download"></i>
              </a>
            </div>
          </div>
        @endforeach

        @foreach ($prDocs as $doc)
          <div class="col-12 col-md-6 col-lg-4">
            <div class="p-2.5 rounded-2 border bg-light d-flex align-items-center justify-content-between gap-2">
              <div class="text-truncate">
                <div class="fw-semibold text-dark text-truncate small" title="{{ $doc->original_name }}">{{ $doc->original_name ?: 'Requisition Document' }}</div>
                <div class="text-muted" style="font-size: 11px;">Linked PR Attachment</div>
              </div>
              <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2.5 flex-shrink-0" title="Download Document">
                <i class="bi bi-download"></i>
              </a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <!-- Full-Width Commercial Quotation Matrix Section (Comparison Parameters) -->
  @php
    $ccs = $rfq->costComparativeStatement;
    $quotes = $ccs?->quotes ?? collect();
    $quotesByVendor = $quotes->keyBy('vendor_id');
    $hasSavedQuotes = $quotes->isNotEmpty() && $quotes->contains(fn($q) => (float)$q->quoted_cost > 0);
    $selectedVendorId = $ccs?->selected_vendor_id;
  @endphp

  <style>
    .quotation-matrix-card {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #ffffff;
      box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.03);
      overflow: hidden;
    }
    .matrix-table-wrapper {
      overflow-x: auto;
      position: relative;
      width: 100%;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 #f8fafc;
    }
    .matrix-table-wrapper::-webkit-scrollbar {
      height: 6px;
    }
    .matrix-table-wrapper::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }
    .matrix-table-wrapper::-webkit-scrollbar-track {
      background: #f8fafc;
    }
    .matrix-custom-table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      min-width: 880px;
    }
    .matrix-custom-table th, .matrix-custom-table td {
      border-bottom: 1px solid #edf2f7;
      border-right: 1px solid #edf2f7;
      padding: 11px 16px;
      vertical-align: middle;
      font-size: 13px;
    }
    .matrix-custom-table thead th {
      background-color: #ffffff;
      border-top: none;
      border-bottom: 1px solid #cbd5e1;
      padding: 14px 14px 12px;
    }
    .matrix-custom-table .sticky-col {
      position: sticky;
      left: 0;
      z-index: 10;
      background-color: #ffffff;
      border-right: 1.5px solid #cbd5e1;
      min-width: 250px;
      max-width: 280px;
      box-shadow: 4px 0 8px -3px rgba(0, 0, 0, 0.04);
    }
    .matrix-custom-table thead th.sticky-col {
      z-index: 20;
      background-color: #fafbfc;
    }
    .matrix-section-divider {
      background-color: #f8fafc !important;
    }
    .matrix-section-divider td {
      padding: 8px 16px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      letter-spacing: 0.06em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      border-top: 1px solid #e2e8f0;
      border-bottom: 1px solid #e2e8f0;
    }
    .matrix-section-divider .sticky-col {
      background-color: #f1f5f9 !important;
      border-left: 3px solid #64748b;
    }
    .matrix-total-row {
      background-color: #f8fafc;
    }
    .matrix-total-row td {
      border-top: 2px solid #cbd5e1 !important;
      border-bottom: 2px solid #cbd5e1 !important;
      padding: 13px 16px;
    }
    .matrix-total-row .sticky-col {
      background-color: #f1f5f9;
    }

    /* Vendor Header Tile Styling */
    .vendor-th-card {
      padding: 11px 12px;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      transition: all 0.2s ease;
      text-align: left;
    }
    .vendor-th-card.is-l1 {
      background: #f0fdf4;
      border-color: #86efac;
      box-shadow: 0 1px 3px rgba(16, 185, 129, 0.08);
    }
    .vendor-th-card.is-selected {
      background: #eff6ff;
      border-color: #93c5fd;
      box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
    }
    .vendor-col-l1 {
      background-color: rgba(16, 185, 129, 0.02) !important;
    }
    .vendor-col-selected {
      background-color: rgba(37, 99, 235, 0.025) !important;
    }
    .l1-pill-badge {
      display: inline-flex !important;
      align-items: center !important;
      gap: 3px !important;
      background: #059669 !important;
      color: #ffffff !important;
      font-size: 10px !important;
      font-weight: 700 !important;
      padding: 2.5px 8px !important;
      border-radius: 9999px !important;
      letter-spacing: 0.02em !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      line-height: 1.2 !important;
      box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
    }
    .selected-pill-badge {
      display: inline-flex !important;
      align-items: center !important;
      gap: 3px !important;
      background: #c8102e !important;
      color: #ffffff !important;
      font-size: 10px !important;
      font-weight: 700 !important;
      padding: 2.5px 8px !important;
      border-radius: 9999px !important;
      letter-spacing: 0.02em !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      line-height: 1.2 !important;
      box-shadow: 0 1px 2px rgba(29, 78, 216, 0.2);
    }
    .rank-pill-badge {
      display: inline-flex !important;
      align-items: center !important;
      background: #f1f5f9 !important;
      color: #334155 !important;
      border: 1px solid #cbd5e1 !important;
      font-size: 10px !important;
      font-weight: 700 !important;
      padding: 2.5px 7px !important;
      border-radius: 9999px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      line-height: 1.2 !important;
    }
    .l1-price-callout {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      padding: 5px 12px;
      background: #ecfdf5;
      border: 1px solid #86efac;
      border-radius: 6px;
    }
  </style>

  <div class="row mt-3 mb-4" id="quotationMatrixSection">
    <div class="col-12">
      <div class="card quotation-matrix-card border-0 shadow-sm">
        
        <!-- Modern Card Header with Segmented Navigation & Action Controls -->
        <div class="card-header bg-white py-3 px-3.5 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
          <div>
            <div class="d-flex align-items-center gap-2">
              <h5 class="mb-0 fw-bold text-dark" style="font-size: 15.5px; letter-spacing: -0.01em;">
                Commercial Quotation Matrix &amp; Comparison Parameters
              </h5>
              @if ($hasSavedQuotes)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                  Quotes Evaluated
                </span>
              @else
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                  Awaiting Quotes
                </span>
              @endif
            </div>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="bg-light p-1 rounded-pill border d-inline-flex" role="group">
              <button type="button" class="btn btn-sm text-white shadow-2xs fw-semibold rounded-pill px-3 py-1 border-0" id="tabBtnMatrix" onclick="switchMatrixTab('matrix')" style="font-size: 12px; background-color: #c8102e;">
                Comparison Matrix
              </button>
              <button type="button" class="btn btn-sm text-secondary fw-semibold rounded-pill px-3 py-1 border-0" id="tabBtnVendors" onclick="switchMatrixTab('vendors')" style="font-size: 12px;">
                Invited Vendors ({{ $rfq->rfqVendors->count() }})
              </button>
            </div>

            @if ($canManage)
              <form method="POST" action="{{ route('rfq.auto-populate-quotes', $rfq) }}" class="m-0 d-inline-block">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold rounded-2 shadow-2xs" title="Automatically populate competitive quotations for invited vendors based on PR estimates">
                  <i class="bi bi-magic"></i>
                  <span>{{ $hasSavedQuotes ? 'Re-Populate Quotes' : 'Auto-Populate Quotes' }}</span>
                </button>
              </form>

              <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5 fw-semibold rounded-2 shadow-2xs" id="matrixModeToggleBtn" onclick="toggleMatrixMode()">
                <i class="bi {{ $hasSavedQuotes ? 'bi-pencil-square' : 'bi-eye' }}" id="modeIcon"></i>
                <span id="modeLabel">{{ $hasSavedQuotes ? 'Edit Quotations' : 'View Comparison' }}</span>
              </button>
            @endif
          </div>
        </div>

        <div class="card-body p-0">

          <!-- TAB 1: COMMERCIAL QUOTATIONS MATRIX -->
          <div id="tabContentMatrix">
            <!-- 1. COMPARISON MATRIX VIEW (Redesigned Executive Comparative Statement) -->
            <div id="matrixComparisonView" class="{{ $hasSavedQuotes ? 'd-block' : ($canManage ? 'd-none' : 'd-block') }}">
              @if ($rfq->rfqVendors->isEmpty())
                <div class="p-5 text-center text-muted">
                  No vendors dispatched for this RFQ.
                </div>
              @else
                <div class="matrix-table-wrapper">
                  <table class="matrix-custom-table align-middle mb-0">
                    <thead>
                      <tr>
                        <th class="sticky-col py-3 px-3">
                          <div class="text-dark fw-bold" style="font-size: 13px;">
                            Comparison Parameters
                          </div>
                        </th>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $rank = $savedQuote?->rank;
                            $isL1 = ($rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <th class="py-3 px-3 {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}" style="min-width: 210px; max-width: 240px;">
                            <div class="vendor-th-card {{ $isChosen ? 'is-selected' : ($isL1 ? 'is-l1' : '') }}">
                              <div class="d-flex align-items-center justify-content-between gap-2 mb-1.5 flex-nowrap">
                                <span class="text-uppercase fw-bold text-muted text-nowrap" style="font-size: 10px; letter-spacing: 0.04em; flex-shrink: 0;">
                                  Vendor {{ chr(65 + $loop->index) }}
                                </span>
                                @if ($isChosen)
                                  <span class="selected-pill-badge text-nowrap">
                                    ★ Awarded
                                  </span>
                                @elseif ($isL1)
                                  <span class="l1-pill-badge text-nowrap">
                                    L1 &middot; Lowest
                                  </span>
                                @elseif ($rank)
                                  <span class="rank-pill-badge text-nowrap">
                                    {{ $rank }}
                                  </span>
                                @endif
                              </div>
                              <div class="fw-bold text-dark text-truncate mb-0.5" style="font-size: 13.5px;" title="{{ $rv->vendor->company_name }}">
                                {{ $rv->vendor->company_name }}
                              </div>
                              <div class="text-muted font-monospace" style="font-size: 11px;">
                                {{ $rv->vendor->vendor_code }}
                              </div>
                            </div>
                          </th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Row 1: Quoted Basic Price -->
                      <tr>
                        <td class="sticky-col py-2.5 ps-3">
                          <div class="fw-semibold text-dark">Quoted Basic Price</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php 
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isL1 = ($savedQuote?->rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <td class="text-center py-2.5 font-monospace fw-semibold text-dark {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}" style="font-size: 13.5px;">
                            ₹{{ number_format((float)($savedQuote?->basic_price ?? $savedQuote?->quoted_cost ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 2: GST -->
                      <tr>
                        <td class="sticky-col py-2.5 ps-3">
                          <div class="fw-medium text-secondary">GST (Tax Amount)</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php 
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isL1 = ($savedQuote?->rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <td class="text-center py-2.5 font-monospace text-secondary {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}" style="font-size: 13px;">
                            ₹{{ number_format((float)($savedQuote?->gst_amount ?? 0), 2) }}
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 3: Total Quoted Amount (Highlighted Landed Cost) -->
                      <tr class="matrix-total-row">
                        <td class="sticky-col py-3 ps-3">
                          <div class="fw-bold text-dark" style="font-size: 13.5px;">Total Quoted Amount</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isL1 = ($savedQuote?->rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <td class="text-center py-3 {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}">
                            @if ($isL1)
                              <div class="l1-price-callout">
                                <span class="font-monospace fw-bold" style="font-size: 15px; color: #065f46;">
                                  ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                                </span>
                                <span class="text-success fw-bold text-uppercase mt-0.5" style="font-size: 9.5px; letter-spacing: 0.5px;">
                                  Lowest Evaluated (L1)
                                </span>
                              </div>
                            @elseif ($isChosen)
                              <div class="d-inline-flex flex-column align-items-center py-1.5 px-3 rounded-2" style="background: #fdf2f4; border: 1.5px solid #c8102e;">
                                <span class="font-monospace fw-bold" style="font-size: 14.5px; color: #c8102e;">
                                  ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                                </span>
                                <span class="fw-bold text-uppercase mt-0.5" style="font-size: 9.5px; letter-spacing: 0.5px; color: #c8102e;">
                                  Awarded Cost
                                </span>
                              </div>
                            @else
                              <span class="font-monospace fw-bold text-dark" style="font-size: 14px;">
                                ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                              </span>
                            @endif
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 4: Remarks & Evaluation Notes -->
                      <tr>
                        <td class="sticky-col py-2.5 ps-3">
                          <div class="fw-semibold text-dark">Remarks &amp; Notes</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php 
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isL1 = ($savedQuote?->rank === 'L1');
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                          @endphp
                          <td class="text-center py-2.5 px-3 {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}">
                            @if ($savedQuote?->remarks)
                              <div class="text-dark small text-wrap" style="font-size: 11.5px; line-height: 1.35; max-width: 200px; margin: 0 auto;">
                                {{ $savedQuote->remarks }}
                              </div>
                            @elseif ($isChosen && $ccs?->selection_remarks)
                              <div class="text-primary small text-wrap fst-italic" style="font-size: 11.5px; line-height: 1.35; max-width: 200px; margin: 0 auto;">
                                {{ $ccs->selection_remarks }}
                              </div>
                            @else
                              <span class="text-muted small">—</span>
                            @endif
                          </td>
                        @endforeach
                      </tr>

                      <!-- Row 5: Award Decision -->
                      <tr>
                        <td class="sticky-col py-3 ps-3 bg-light-subtle">
                          <div class="fw-bold text-dark" style="font-size: 13px;">Award Decision</div>
                        </td>
                        @foreach ($rfq->rfqVendors as $rv)
                          @php
                            $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            $isChosen = ($selectedVendorId == $rv->vendor_id);
                            $isL1 = ($savedQuote?->rank === 'L1');
                            $hasValidQuote = ($savedQuote && (float)$savedQuote->quoted_cost > 0);
                          @endphp
                          <td class="text-center py-3 px-2 {{ $isChosen ? 'vendor-col-selected' : ($isL1 ? 'vendor-col-l1' : '') }}">
                            @if ($isChosen)
                              <div class="badge bg-primary text-white px-3 py-1.5 rounded-pill mb-1 d-inline-flex align-items-center gap-1.5 shadow-2xs fw-semibold" style="font-size: 11.5px;">
                                <i class="bi bi-award-fill"></i>
                                <span>Awarded Vendor</span>
                              </div>
                              @if ($ccs?->selection_remarks)
                                <div class="small text-muted fst-italic px-1 text-wrap mt-0.5" style="font-size: 11px; max-width: 175px; margin: 0 auto; line-height: 1.3;" title="{{ $ccs->selection_remarks }}">
                                  "{{ Str::limit($ccs->selection_remarks, 65) }}"
                                </div>
                              @endif
                            @elseif ($canManage && $hasValidQuote)
                              <button type="button" 
                                      class="btn btn-sm {{ $isL1 ? 'btn-outline-success' : 'btn-outline-primary' }} py-1 px-3 rounded-pill text-nowrap d-inline-flex align-items-center gap-1.5 fw-semibold shadow-2xs" 
                                      style="font-size: 11.5px;" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#selectVendorModal-{{ $rv->vendor_id }}"
                                      title="Award this requirement to {{ $rv->vendor->company_name }}">
                                <i class="bi bi-award"></i>
                                <span>Award Vendor</span>
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
                    @if ($canManage)
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($ccs?->selectedVendor?->email)
                          <form method="POST" action="{{ route('rfq.resend-award-email', $rfq) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-medium text-nowrap rounded-2" style="font-size: 12px;" title="Send / Resend award notification email to {{ $ccs->selectedVendor->email }}">
                              <i class="bi bi-envelope-arrow-up"></i>
                              <span>{{ $ccs->award_email_sent_at ? 'Resend Award Email' : 'Send Award Email' }}</span>
                            </button>
                          </form>
                        @endif

                        @if (!$rfq->purchaseRequisition?->purchaseOrders()->exists() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
                          <a href="{{ route('po.create', ['pr_id' => $rfq->purchase_requisition_id, 'vendor_id' => $selectedVendorId]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-3 py-1.5 fw-semibold shadow-sm text-nowrap rounded-2">
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

            <!-- 2. EDIT / DATA ENTRY FORM VIEW (Clean Aligned Inputs with Tabular Layout) -->
            @if ($canManage)
              <div id="matrixEditView" class="{{ $hasSavedQuotes ? 'd-none' : 'd-block' }}">
                <form method="POST" action="{{ route('rfq.save-quotations', $rfq) }}" id="quotationMatrixEntryForm">
                  @csrf
                  <div class="matrix-table-wrapper">
                    <table class="matrix-custom-table align-middle mb-0" style="font-size: 13px;">
                      <thead>
                        <tr>
                          <th class="sticky-col py-3 px-3">
                            <div class="text-dark fw-bold" style="font-size: 13px;">
                              Comparison Parameters
                            </div>
                          </th>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php
                              $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null;
                            @endphp
                            <th class="py-3 px-3" style="min-width: 200px; max-width: 230px;">
                              <div class="vendor-th-card">
                                <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10.5px; letter-spacing: 0.04em;">
                                  Vendor {{ chr(65 + $loop->index) }}
                                </div>
                                <div class="fw-bold text-dark text-truncate mb-0.5" style="font-size: 13.5px;" title="{{ $rv->vendor->company_name }}">
                                  {{ $rv->vendor->company_name }}
                                </div>
                                <div class="text-muted font-monospace" style="font-size: 11px;">
                                  {{ $rv->vendor->vendor_code }}
                                </div>
                                <input type="hidden" name="quotes[{{ $loop->index }}][vendor_id]" value="{{ $rv->vendor_id }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][transportation_cost]" value="{{ $savedQuote?->transportation_cost ?? 0 }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][warranty]" value="{{ $savedQuote?->warranty ?? '1 Year' }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][payment_terms]" value="{{ $savedQuote?->payment_terms ?? '30 Days' }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][quotation_validity]" value="{{ $savedQuote?->quotation_validity ?? '30 Days' }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][delivery_days]" value="{{ $savedQuote?->delivery_days ?? 10 }}">
                                <input type="hidden" name="quotes[{{ $loop->index }}][technical_compliance]" value="{{ $savedQuote?->technical_compliance ?? 'Yes' }}">
                              </div>
                            </th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Row 1: Quoted Basic Price -->
                        <tr>
                          <td class="sticky-col py-2.5 ps-3">
                            <div class="fw-semibold text-dark">Quoted Basic Price (₹)</div>
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm rounded-2 overflow-hidden shadow-2xs border bg-white" style="max-width: 175px; margin: 0 auto;">
                                <span class="input-group-text bg-light text-muted border-0 fw-semibold px-2.5">₹</span>
                                <input type="number" step="0.01" min="0" 
                                       name="quotes[{{ $loop->index }}][basic_price]" 
                                       class="form-control border-0 text-end quote-basic font-monospace fw-semibold" 
                                       style="font-size: 13px;"
                                       data-index="{{ $loop->index }}" 
                                       placeholder="0.00" 
                                       value="{{ old('quotes.'.$loop->index.'.basic_price', $savedQuote?->basic_price ?? $savedQuote?->quoted_cost) }}" 
                                       oninput="recalcTotalAmount({{ $loop->index }})">
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 2: GST -->
                        <tr>
                          <td class="sticky-col py-2.5 ps-3">
                            <div class="fw-medium text-secondary">GST (₹)</div>
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <div class="input-group input-group-sm rounded-2 overflow-hidden shadow-2xs border bg-white" style="max-width: 175px; margin: 0 auto;">
                                <span class="input-group-text bg-light text-muted border-0 fw-semibold px-2.5">₹</span>
                                <input type="number" step="0.01" min="0" 
                                       name="quotes[{{ $loop->index }}][gst_amount]" 
                                       class="form-control border-0 text-end quote-gst font-monospace text-secondary fw-medium" 
                                       style="font-size: 13px;"
                                       data-index="{{ $loop->index }}" 
                                       placeholder="0.00" 
                                       value="{{ old('quotes.'.$loop->index.'.gst_amount', $savedQuote?->gst_amount ?? 0) }}" 
                                       oninput="recalcTotalAmount({{ $loop->index }})">
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 3: Total Quoted Amount (Live Recalculated) -->
                        <tr class="matrix-total-row">
                          <td class="sticky-col py-3 ps-3">
                            <div class="fw-bold text-dark" style="font-size: 13.5px;">Total Quoted Amount (₹)</div>
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="text-center py-2.5">
                              <div class="py-1.5 px-3 rounded-2 bg-light border text-center shadow-2xs d-inline-block" style="min-width: 140px;">
                                <span class="fw-bold text-primary font-monospace fs-6" id="editTotalDisplay_{{ $loop->index }}">
                                  ₹{{ number_format((float)($savedQuote?->quoted_cost ?? 0), 2) }}
                                </span>
                              </div>
                            </td>
                          @endforeach
                        </tr>

                        <!-- Row 4: Remarks & Evaluation Notes -->
                        <tr>
                          <td class="sticky-col py-2.5 ps-3">
                            <div class="fw-semibold text-dark">Remarks &amp; Notes</div>
                          </td>
                          @foreach ($rfq->rfqVendors as $rv)
                            @php $savedQuote = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                            <td class="px-2.5 py-2">
                              <input type="text" 
                                     name="quotes[{{ $loop->index }}][remarks]" 
                                     class="form-control form-control-sm rounded-2 text-start shadow-2xs bg-white text-dark" 
                                     style="min-width: 170px; max-width: 220px; margin: 0 auto; font-size: 12px;" 
                                     placeholder="e.g. Terms negotiated, delivery included" 
                                     value="{{ old('quotes.'.$loop->index.'.remarks', $savedQuote?->remarks) }}">
                            </td>
                          @endforeach
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="p-3 bg-light d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top">
                    <div class="text-muted small d-flex align-items-center gap-2">
                      <i class="bi bi-info-circle text-primary"></i>
                      <span>Enter commercial figures quoted by vendors. System automatically evaluates all-inclusive costs and computes L1–L{{ $rfq->rfqVendors->count() }} ranks.</span>
                    </div>
                    <div>
                      <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold shadow-sm rounded-2">
                        <i class="bi bi-check-circle"></i>
                        <span>Save &amp; Evaluate Quotations</span>
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            @endif
          </div>

          <!-- TAB 2: INVITED VENDORS DIRECTORY -->
          <div id="tabContentVendors" class="d-none">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
                <thead class="table-light">
                  <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <th class="ps-3 py-2 text-nowrap">Vendor</th>
                    <th class="py-2 text-nowrap">Category</th>
                    <th class="py-2 text-nowrap">Contact</th>
                    <th class="text-end py-2 text-nowrap">Quoted Total</th>
                    <th class="text-center py-2 text-nowrap">Status</th>
                    <th class="text-end pe-3 py-2 text-nowrap">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($rfq->rfqVendors as $rv)
                    @php $sq = $quotesByVendor[$rv->vendor_id] ?? null; @endphp
                    <tr>
                      <td class="ps-3 py-2 text-nowrap">
                        <div class="fw-bold text-dark" style="font-size: 13px;">{{ $rv->vendor->company_name ?? '—' }}</div>
                        <div class="text-muted font-monospace small" style="font-size: 11px;">{{ $rv->vendor->vendor_code ?? '—' }}</div>
                      </td>
                      <td class="py-2 text-secondary text-nowrap" style="font-size: 12px;">
                        <span class="badge bg-light text-secondary border fw-normal px-2 py-0.5">
                          {{ $rv->vendor->category ?: 'General Goods' }}
                        </span>
                      </td>
                      <td class="py-2 text-secondary text-nowrap">
                        <div style="font-size: 12px; line-height: 1.2;">{{ $rv->vendor->email ?: '—' }}</div>
                        @if ($rv->vendor->phone_number)
                          <div class="text-muted" style="font-size: 11px;">{{ $rv->vendor->phone_number }}</div>
                        @endif
                      </td>
                      <td class="text-end py-2 font-monospace fw-semibold text-nowrap" style="font-size: 12.5px;">
                        @if ($sq && $sq->quoted_cost > 0)
                          <span class="text-dark">₹{{ number_format($sq->quoted_cost, 2) }}</span>
                          @if ($sq->rank === 'L1')
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10px;">L1</span>
                          @endif
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-center py-2 text-nowrap">
                        <div class="d-flex flex-column align-items-center gap-1">
                          @if ($rv->response_status === 'received')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 11px;">
                              Received
                            </span>
                          @else
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5" style="font-size: 11px;">
                              Awaiting
                            </span>
                          @endif

                          @if ($rv->email_sent_at)
                            <span class="text-muted" style="font-size: 10px;" title="Sent: {{ $rv->email_sent_at->format('d M Y, h:i A') }}">
                              <i class="bi bi-envelope-check text-success"></i> {{ $rv->email_sent_at->format('d M') }}
                            </span>
                          @else
                            <span class="text-muted" style="font-size: 10px;">
                              <i class="bi bi-envelope text-muted"></i> Not Sent
                            </span>
                          @endif
                        </div>
                      </td>
                      <td class="text-end pe-3 py-2 text-nowrap">
                        @if ($canManage && $rv->vendor && !empty($rv->vendor->email))
                          <form method="POST" action="{{ route('rfq.vendor.resend-email', ['rfq' => $rfq, 'vendor' => $rv->vendor]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0.5 px-2 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="Resend complete RFQ details email to {{ $rv->vendor->email }}">
                              <i class="bi bi-envelope-arrow-up"></i>
                              <span>{{ $rv->email_sent_at ? 'Resend' : 'Send' }}</span>
                            </button>
                          </form>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
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
        btnMatrix?.classList.remove('text-white', 'shadow-2xs');
        btnMatrix?.classList.add('text-secondary');
        if (btnMatrix) btnMatrix.style.backgroundColor = 'transparent';

        btnVendors?.classList.add('text-white', 'shadow-2xs');
        btnVendors?.classList.remove('text-secondary');
        if (btnVendors) btnVendors.style.backgroundColor = '#c8102e';

        if (toggleBtn) toggleBtn.style.display = 'none';
      } else {
        vendorsTab?.classList.add('d-none');
        matrixTab?.classList.remove('d-none');
        btnVendors?.classList.remove('text-white', 'shadow-2xs');
        btnVendors?.classList.add('text-secondary');
        if (btnVendors) btnVendors.style.backgroundColor = 'transparent';

        btnMatrix?.classList.add('text-white', 'shadow-2xs');
        btnMatrix?.classList.remove('text-secondary');
        if (btnMatrix) btnMatrix.style.backgroundColor = '#c8102e';

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

    // Auto-fill realistic sample quotes based on PR estimate
    window.autoFillSampleQuotes = function() {
      const prBase = {{ (float)($subtotalExclGst ?? 250000) }};
      const gstRate = {{ (float)(isset($rfqGstPercent) && $rfqGstPercent > 0 ? ($rfqGstPercent / 100) : 0.18) }};
      const vendorInputs = document.querySelectorAll('.quote-basic');
      if (!vendorInputs.length) return;

      const editView = document.getElementById('matrixEditView');
      const compView = document.getElementById('matrixComparisonView');
      if (editView && editView.classList.contains('d-none')) {
        editView.classList.remove('d-none');
        editView.classList.add('d-block');
        if (compView) {
          compView.classList.remove('d-block');
          compView.classList.add('d-none');
        }
        const modeLabel = document.getElementById('modeLabel');
        if (modeLabel) modeLabel.innerText = 'View Comparison';
        const modeIcon = document.getElementById('modeIcon');
        if (modeIcon) modeIcon.className = 'bi bi-eye';
      }

      const varianceMultipliers = [0.94, 0.97, 0.99, 1.01, 1.03, 1.06, 1.09, 1.12, 1.15, 1.18];
      vendorInputs.forEach((input, idx) => {
        const mult = varianceMultipliers[idx % varianceMultipliers.length];
        const basic = Math.round((prBase * mult) / 100) * 100;
        const gst = Math.round(basic * gstRate);

        input.value = basic.toFixed(2);
        const gstInput = document.querySelector(`.quote-gst[data-index="${idx}"]`);
        if (gstInput) gstInput.value = gst.toFixed(2);

        const remarksInput = document.querySelector(`input[name="quotes[${idx}][remarks]"]`);
        if (remarksInput && !remarksInput.value) {
          if (idx === 0) remarksInput.value = 'Lowest evaluated quote with standard delivery';
          else if (idx === 1) remarksInput.value = 'Competitive bid, payment terms 30 days';
          else remarksInput.value = 'Standard quotation terms';
        }

        recalcTotalAmount(idx);
      });
    };

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
                  <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="selectVendorModalLabel-{{ $rv->vendor_id }}">
                      Award Contract to {{ $rv->vendor->company_name }}
                    </h6>
                    <div class="text-muted small" style="font-size: 11.5px;">Designate as Awarded Vendor for RFQ Requirement</div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                  <div class="alert alert-info-subtle border border-info-subtle text-dark p-3 rounded-2 mb-3" style="font-size: 12.5px; line-height: 1.5;">
                    You are designating <strong>{{ $rv->vendor->company_name }}</strong> as the awarded vendor for this requisition. This decision locks the quotation and prepares the order for Purchase Order generation.
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
                    <label class="form-label fw-bold text-dark small mb-1.5">Award Justification / Evaluation Remarks <span class="text-danger">*</span></label>
                    <textarea name="selection_remarks" 
                              class="form-control" 
                              rows="3" 
                              required 
                              style="font-size: 13px;"
                              placeholder="e.g. Lowest evaluated commercial quote, negotiated terms, institutional supplier empanelment..."></textarea>
                    <div class="form-text text-muted mt-1.5" style="font-size: 11.5px;">
                      Remarks will be recorded in the official project audit trail and commercial evaluation dossiers.
                    </div>
                  </div>
                </div>

                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                  <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary btn-sm px-3.5 d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm">
                    <i class="bi bi-award-fill"></i>
                    <span>Confirm Award Decision</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      @endif
    @endforeach

    <!-- Modal: Multi-Vendor Award & Generate Separate Purchase Orders -->
    <div class="modal fade" id="generatePosModal" tabindex="-1" aria-labelledby="generatePosModalLabel" aria-hidden="true" style="z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden text-start">
          <form method="POST" action="{{ route('rfq.generate-pos', $rfq) }}" id="generatePosDirectForm">
            @csrf

            <div class="modal-header bg-white border-bottom py-3 px-4">
              <div>
                <h6 class="modal-title fw-bold text-dark mb-0" id="generatePosModalLabel">
                  <i class="bi bi-receipt-cutoff text-primary me-1.5"></i>
                  <span>Award Items &amp; Generate Purchase Orders</span>
                </h6>
                <div class="text-muted small" style="font-size: 11.5px;">
                  Automatic generation of separate POs for each awarded vendor based on PR line items
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
              <div class="alert alert-info-subtle border border-info-subtle text-dark p-3 rounded-2 mb-3" style="font-size: 12.5px; line-height: 1.5;">
                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                Review the item-wise vendor awards below. The system will create <strong>separate Purchase Orders</strong> for each selected vendor, covering only their awarded items.
              </div>

              <!-- Dynamic Multi-Vendor Award Breakdown -->
              <div class="mb-4">
                <div class="fw-bold text-dark small mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                  Award &amp; PO Breakdown by Vendor
                </div>
                <div id="modalAwardBreakdownTable" class="table-responsive rounded-2 border">
                  <!-- Populated via JS -->
                </div>
              </div>

              <!-- Commercial / Delivery Options -->
              <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold text-dark small mb-1">Target Delivery Date</label>
                  <input type="date" name="delivery_date" class="form-control form-control-sm" value="{{ now()->addDays(14)->toDateString() }}" style="font-size: 13px;">
                  <div class="text-muted small" style="font-size: 11px;">Defaults to 14 days from today if not specified.</div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold text-dark small mb-1">Payment Terms</label>
                  <input type="text" name="payment_terms" class="form-control form-control-sm" placeholder="Within 30 days of verified invoice" style="font-size: 13px;">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label fw-bold text-dark small mb-1">Award Remarks / Commercial Evaluation Notes</label>
                <textarea name="selection_remarks" class="form-control" rows="2" style="font-size: 12.5px;" placeholder="Optional remarks to document the item-wise award rationale in the audit trail..."></textarea>
              </div>

              <!-- Hidden container for item_vendors to be submitted -->
              <div id="modalHiddenItemVendors"></div>
            </div>

            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm" id="modalSubmitPoBtn">
                <i class="bi bi-check-circle-fill"></i>
                <span id="modalSubmitPoBtnText">Confirm &amp; Generate POs</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  <script>
    window.updateRfqAwardSummary = function() {
      const selects = document.querySelectorAll('.rfq-item-vendor-select');
      if (!selects.length) return;

      const vendorMap = {};
      let totalAssignedItems = 0;
      let totalItems = selects.length;

      selects.forEach(sel => {
        const vId = sel.value;
        const itemId = sel.dataset.itemId;
        const itemName = sel.dataset.itemName;
        const lineTotal = parseFloat(sel.dataset.lineTotal || 0);

        if (vId) {
          totalAssignedItems++;
          if (!vendorMap[vId]) {
            const opt = sel.options[sel.selectedIndex];
            vendorMap[vId] = {
              id: vId,
              name: opt ? opt.text.split('(')[0].trim() : 'Vendor #' + vId,
              items: [],
              total: 0
            };
          }
          vendorMap[vId].items.push({ id: itemId, name: itemName, total: lineTotal });
          vendorMap[vId].total += lineTotal;
        }
      });

      const vendorCount = Object.keys(vendorMap).length;
      const titleEl = document.getElementById('rfqAwardSummaryTitle');
      const textEl = document.getElementById('rfqAwardSummaryText');
      const btnLabelEl = document.getElementById('rfqGeneratePoBtnLabel');

      if (titleEl) {
        if (vendorCount === 0) {
          titleEl.textContent = 'No vendors assigned to PR items yet';
        } else if (vendorCount === 1) {
          titleEl.textContent = `Single Vendor Award (${totalAssignedItems}/${totalItems} items assigned → 1 PO)`;
        } else {
          titleEl.textContent = `Multiple Vendor Awards Active (${vendorCount} Vendors across ${totalAssignedItems} items → ${vendorCount} Separate POs)`;
        }
      }

      if (textEl) {
        if (vendorCount === 0) {
          textEl.innerHTML = 'Assign participating vendors from the dropdowns above for each line item. You can award different items to different vendors to automatically generate separate Purchase Orders.';
        } else if (vendorCount === 1) {
          const v = Object.values(vendorMap)[0];
          textEl.innerHTML = `All assigned items (${totalAssignedItems}/${totalItems}) are awarded to <strong>${v.name}</strong>. A single Purchase Order for ₹${v.total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} will be generated.`;
        } else {
          const breakdown = Object.values(vendorMap).map(v => `<strong>${v.name}</strong> (${v.items.length} items — ₹${v.total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`).join(', ');
          textEl.innerHTML = `<strong>Multi-Vendor Split Active:</strong> The system will generate <strong>${vendorCount} separate Purchase Orders</strong>: ${breakdown}.`;
        }
      }

      if (btnLabelEl) {
        btnLabelEl.textContent = vendorCount > 1 ? `Generate ${vendorCount} Separate POs` : 'Generate Purchase Order';
      }
    };

    window.applyWinningVendorToAllItems = function(vendorId) {
      if (!vendorId) return;
      document.querySelectorAll('.rfq-item-vendor-select').forEach(sel => {
        sel.value = vendorId;
      });
      window.updateRfqAwardSummary();
    };

    window.openGeneratePosModal = function() {
      const selects = document.querySelectorAll('.rfq-item-vendor-select');
      const vendorMap = {};
      const hiddenContainer = document.getElementById('modalHiddenItemVendors');
      if (hiddenContainer) hiddenContainer.innerHTML = '';

      let hasSelection = false;

      selects.forEach(sel => {
        const vId = sel.value;
        const itemId = sel.dataset.itemId;
        const itemName = sel.dataset.itemName;
        const lineTotal = parseFloat(sel.dataset.lineTotal || 0);

        if (vId) {
          hasSelection = true;
          if (!vendorMap[vId]) {
            const opt = sel.options[sel.selectedIndex];
            vendorMap[vId] = {
              id: vId,
              name: opt ? opt.text.split('(')[0].trim() : 'Vendor #' + vId,
              items: [],
              total: 0
            };
          }
          vendorMap[vId].items.push({ id: itemId, name: itemName, total: lineTotal });
          vendorMap[vId].total += lineTotal;

          if (hiddenContainer) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = `item_vendors[${itemId}]`;
            inp.value = vId;
            hiddenContainer.appendChild(inp);
          }
        }
      });

      if (!hasSelection) {
        alert('Please assign at least one vendor to the PR items before generating Purchase Orders.');
        return;
      }

      const tableContainer = document.getElementById('modalAwardBreakdownTable');
      if (tableContainer) {
        const vendors = Object.values(vendorMap);
        let html = `
          <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
            <thead class="table-light">
              <tr style="font-size: 11px; text-transform: uppercase; color: #64748b;">
                <th class="ps-3 py-2">PO #</th>
                <th>AWARDED VENDOR</th>
                <th>AWARDED ITEMS</th>
                <th class="text-end pe-3">ESTIMATED PO TOTAL (INCL. GST)</th>
              </tr>
            </thead>
            <tbody>
        `;

        vendors.forEach((v, idx) => {
          const itemNames = v.items.map(i => `<span class="badge bg-light text-dark border me-1">${i.name}</span>`).join('');
          html += `
            <tr>
              <td class="ps-3 py-2.5 font-monospace fw-bold text-primary">PO #${idx + 1}</td>
              <td class="fw-semibold text-dark">${v.name}</td>
              <td>${itemNames}</td>
              <td class="text-end pe-3 font-monospace fw-bold text-dark">₹${v.total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
          `;
        });

        const grandTotal = vendors.reduce((acc, v) => acc + v.total, 0);
        html += `
            </tbody>
            <tfoot class="table-light border-top">
              <tr class="fw-bold">
                <td colspan="3" class="text-end py-2 text-dark">Total (${vendors.length} ${vendors.length > 1 ? 'POs' : 'PO'}):</td>
                <td class="text-end pe-3 py-2 font-monospace fs-6" style="color: #c8102e;">₹${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
              </tr>
            </tfoot>
          </table>
        `;
        tableContainer.innerHTML = html;
      }

      const modalBtnText = document.getElementById('modalSubmitPoBtnText');
      if (modalBtnText) {
        const count = Object.keys(vendorMap).length;
        modalBtnText.textContent = count > 1 ? `Confirm & Generate ${count} Separate POs` : 'Confirm & Generate Purchase Order';
      }

      const modal = new bootstrap.Modal(document.getElementById('generatePosModal'));
      modal.show();
    };

    document.addEventListener('DOMContentLoaded', () => {
      window.updateRfqAwardSummary();
    });
  </script>
@endsection
