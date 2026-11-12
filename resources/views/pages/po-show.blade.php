@extends('layouts.app')

@section('title', $po->po_number)

@php
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'PO Issued', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'cancelled'=> ['label' => 'Cancelled', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$po->status] ?? $statusMeta['pending'];

  $approvals = $po->approvals->sortByDesc('id')->unique('level')->sortBy('level');
  $poApprovedCount = $approvals->where('status', 'approved')->count();
  $poTotalCount = $approvals->count();
  $isPoFullyApproved = $poTotalCount > 0 && $poApprovedCount === $poTotalCount;
  $isPoRejected = $approvals->where('status', 'rejected')->isNotEmpty() || $po->status === 'rejected';
  $activePendingLevel = optional($approvals->where('status', 'pending')->sortBy('level')->first())->level;

  $poStageTitles = [
    \App\Support\Roles::ADMIN_HEAD => 'Admin Head',
    \App\Support\Roles::CFO => 'CFO',
    \App\Support\Roles::SG => 'Secretary General',
  ];

  $items = $po->purchaseRequisition->items ?? collect();
  $subtotalExclGst = $items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
  $totalGst = (float)$po->amount_incl_gst - $subtotalExclGst;
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Orders</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  @php
    $canRegeneratePo = $po->canRegenerate() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
  @endphp

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100" style="overflow: visible; border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: PO Identity, Metadata, Badges & Document Actions -->
    <div class="p-3 p-md-4 bg-white w-100" style="border-radius: {{ ($pendingApproval || $canRegeneratePo) ? 'var(--border-radius-base) var(--border-radius-base) 0 0' : 'var(--border-radius-base)' }};">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        
        <!-- Left: Avatar + PO Number + Meta items in a clean non-breaking layout -->
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(37,99,235,0.15),rgba(37,99,235,0.05)); color:#2563eb; border:1px solid rgba(37,99,235,0.2); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-receipt fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $po->po_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              @if ($po->revision_number > 0)
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  Rev {{ $po->revision_number }}
                </span>
              @endif
              @if ($po->isHighValue())
                <span class="badge" style="background:#fef3c7; color:#d97706; font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  <i class="bi bi-star-fill me-1"></i> High Value (&ge; ₹10L)
                </span>
              @endif
            </div>
            
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span class="text-dark fw-semibold"><i class="bi bi-shop text-primary me-1"></i>{{ $po->vendor->company_name }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-file-earmark-text text-secondary me-1"></i>PR: <strong class="text-dark">{{ $po->purchaseRequisition->pr_number }}</strong> ({{ $po->purchaseRequisition->department }})</span>
              @if ($po->created_at)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-calendar3 text-secondary me-1"></i>{{ $po->created_at->format('d M Y') }}</span>
              @endif
            </div>
          </div>
        </div>

        <!-- Right: Primary Actions (Download PDF, Edit, Cancel) -->
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
          <div class="btn-group shadow-sm">
            <a href="{{ route('po.pdf', $po) }}" id="btnDownloadPdf" class="btn btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1.5" style="font-weight:600; font-size:13px;">
              <i class="bi bi-file-earmark-pdf"></i>
              <span>Download PDF</span>
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split px-2" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="More PDF options">
              <span class="visually-hidden">Toggle PDF options</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="font-size:12.5px; z-index: 1060;">
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-1.5" href="{{ route('po.preview-pdf', $po) }}" target="_blank" id="btnPreviewPdf">
                  <i class="bi bi-eye text-primary"></i>
                  <span>Preview in Browser</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-1.5" href="{{ route('po.preview-pdf', [$po, 'regenerate' => 1]) }}" target="_blank">
                  <i class="bi bi-arrow-repeat text-secondary"></i>
                  <span>Force Regenerate Cache</span>
                </a>
              </li>
            </ul>
          </div>

          @if ($canRegeneratePo)
            <form method="POST" action="{{ route('po.regenerate', $po) }}" class="d-inline mb-0">
              @csrf
              <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5" style="font-size:13px; font-weight:600;" title="Regenerate PO PDF & increment revision after re-approval">
                <i class="bi bi-arrow-repeat"></i>
                <span>Regenerate PO</span>
              </button>
            </form>
          @endif

          @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $po->status !== 'cancelled')
            <a href="{{ route('po.edit', $po) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5" style="font-size:13px;" title="Edit PO details">
              <i class="bi bi-pencil"></i>
              <span>Edit PO</span>
            </a>
            @if (($po->status === 'pending' || $po->status === 'approved') && !$po->grns()->exists())
              <form method="POST" action="{{ route('po.cancel', $po) }}" onsubmit="return confirm('Are you sure you want to cancel this Purchase Order?');" class="d-inline mb-0">
                @csrf
                <button type="submit" class="btn btn-outline-danger d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5" style="font-size:13px;" title="Cancel PO">
                  <i class="bi bi-x-circle"></i>
                  <span>Cancel</span>
                </button>
              </form>
            @endif
          @endif
        </div>

      </div>
    </div>

    @if ($canRegeneratePo)
      <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #f0fdf4; border-top: 1px solid #bbf7d0 !important; border-radius: {{ $pendingApproval ? '0' : '0 0 var(--border-radius-base) var(--border-radius-base)' }};">
        <div class="d-flex align-items-center gap-2 text-success-emphasis small" style="font-size: 12.5px;">
          <i class="bi bi-check2-circle text-success fs-5"></i>
          <span>This Purchase Order has been <strong>re-approved</strong> following post-approval edits. Click <strong>Regenerate PO</strong> to issue the updated revision document.</span>
        </div>
      </div>
    @endif

    <!-- Integrated Action Required Section (When pending current user's approval) -->
    @if ($pendingApproval)
      @php
        $pendingPoStage = $poStageTitles[$pendingApproval->role_required] ?? ('Stage ' . $pendingApproval->level . ' · ' . \App\Support\Roles::label($pendingApproval->role_required));
      @endphp
      <div class="p-3 p-md-3.5 border-top" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-top: 1px solid #fde68a !important; border-radius: 0 0 var(--border-radius-base) var(--border-radius-base);">
        <div class="row align-items-center g-3">
          
          <!-- Left: Stage Notice & Instructions with clear non-overlapping spacing -->
          <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center" style="gap: 16px;">
              <div class="flex-shrink-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px; min-width: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">
                <i class="bi bi-shield-exclamation fs-5"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="fw-bold text-dark" style="font-size: 15px;">Your Action Required</span>
                  <span class="badge bg-white text-dark border border-warning-subtle shadow-2xs px-2.5 py-1" style="font-size: 11px; font-weight: 600;">
                    Stage: {{ $pendingPoStage }}
                  </span>
                </div>
                <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                  Verify contractual terms, vendor empanelment status, and delivery schedule before authorizing this Purchase Order.
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Clean & Minimal Action Buttons (No Inline Input) -->
          <div class="col-12 col-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
              <!-- Approve Button Form -->
              <form method="POST" action="{{ route('po.approve', $po) }}" class="m-0">
                @csrf
                <input type="hidden" name="comments" value="Approved for issuance.">
                <button type="submit" class="btn btn-success px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;">
                  <i class="bi bi-check2-circle fs-6"></i>
                  <span>Approve &amp; Issue PO</span>
                </button>
              </form>

              <!-- Decline Button (Triggers Clean Small Modal Window) -->
              <button type="button" class="btn btn-outline-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm text-nowrap" style="font-size: 13px;" data-bs-toggle="modal" data-bs-target="#declinePoModal">
                <i class="bi bi-x-circle fs-6"></i>
                <span>Decline PO</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal PO Governance Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">PO Governance Timeline</h6>
      </div>
    </div>
    <div class="card-body p-3">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: PO Created -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Order Drafted">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1">Order Drafted</div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Drafted
                </span>
              </div>
              <div class="h-step-person" title="{{ $po->creator?->name ?? 'Buyer' }}">
                {{ $po->creator?->name ?? 'Buyer' }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $po->created_at?->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Multi-Tier PO Approval Stages -->
          @foreach ($approvals as $approval)
            @php
              $isApproved = $approval->status === 'approved';
              $isRejected = $approval->status === 'rejected';
              $isPending = $approval->status === 'pending';
              $isCurrentDue = (!$isPoRejected && $isPending && $approval->level === $activePendingLevel);
              $roleShort = $poStageTitles[$approval->role_required] ?? \App\Support\Roles::label($approval->role_required);

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
                  Stage {{ $approval->level }}: {{ $roleShort }}
                </div>
                <div class="mb-1.5">
                  <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : ($isCurrentDue ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted')) }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                    {{ $roleShort }}
                  </span>
                </div>
                <div class="h-step-person">
                  @if ($approval->approver)
                    <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $approval->approver->name }}</span>
                  @elseif ($isCurrentDue)
                    <span class="text-warning fw-bold">Action Due</span>
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

          <!-- Final Step: Goods Receipt Fulfillment -->
          @php
            $hasGrn = $po->grns && $po->grns->isNotEmpty();
            if ($hasGrn) {
              $grnStatus = 'is-done';
              $grnIcon = 'done';
              $grnCard = 'done-card';
            } elseif ($isPoFullyApproved) {
              $grnStatus = 'is-active';
              $grnIcon = 'active';
              $grnCard = 'active-card';
            } else {
              $grnStatus = 'is-stopped';
              $grnIcon = 'upcoming';
              $grnCard = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $grnStatus }}">
            <div class="h-step-icon {{ $grnIcon }}">
              <i class="bi {{ $hasGrn ? 'bi-check-all' : 'bi-truck' }}"></i>
            </div>
            <div class="h-step-card {{ $grnCard }}">
              <div class="h-step-title {{ $hasGrn ? 'text-success' : ($isPoFullyApproved ? 'text-primary' : 'text-muted') }} mb-1" title="Delivery &amp; Goods Receipt">
                Goods Receipt
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $hasGrn ? 'bg-success-subtle text-success' : ($isPoFullyApproved ? 'bg-info-subtle text-info' : 'bg-light text-muted') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $hasGrn ? 'Received' : ($isPoFullyApproved ? 'In Delivery' : 'Upcoming') }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($hasGrn)
                  <span>{{ $po->receivedQty() }}/{{ $po->po_qty }} Received</span>
                @elseif ($isPoFullyApproved)
                  <span class="text-info fw-semibold">Pending Delivery</span>
                @else
                  <span class="text-muted">Post-Approval</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $hasGrn ? 'Fulfilled' : ($isPoFullyApproved ? 'Inward Due' : '—') }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Contracted Material Specifications & Executive Dossier (Full Width col-12) -->
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
                Contracted Material Specifications
              </h6>
              <div class="text-muted small mt-0.5">
                {{ $items->count() }} {{ Str::plural('line item', $items->count()) }} contracted under {{ $po->po_number }}
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border font-monospace px-3 py-1.5" style="font-size: 12px;">
              Linked PR: <strong class="text-dark">{{ $po->purchaseRequisition->pr_number }}</strong>
            </span>
          </div>
        </div>

        <!-- 4 Premium Executive Metric Cards -->
        <div class="p-3 bg-light-subtle border-bottom">
          <div class="row g-3">
            <!-- Card 1: Contracted Vendor -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Contracted Vendor
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-primary flex-shrink-0" style="width: 32px; height: 32px; background: rgba(37, 99, 235, 0.08); font-size: 15px;">
                    <i class="bi bi-shop"></i>
                  </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'empanelment'))
                    <a href="{{ route('empanelment.show', $po->vendor) }}" class="text-decoration-none text-dark hover-primary">
                      {{ $po->vendor->company_name }}
                    </a>
                  @else
                    {{ $po->vendor->company_name }}
                  @endif
                </div>
                <div class="text-muted small font-monospace" style="font-size: 11.5px;">
                  Code: {{ $po->vendor->vendor_code }}
                </div>
              </div>
            </div>

            <!-- Card 2: Department & Indentor -->
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
                  {{ $po->purchaseRequisition->department }}
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  <i class="bi bi-person me-1"></i>{{ $po->purchaseRequisition->creator?->name ?? 'System' }}
                </div>
              </div>
            </div>

            <!-- Card 3: Total Contract Value -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Total Contract Value
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.08); font-size: 15px;">
                    <i class="bi bi-cash-stack"></i>
                  </div>
                </div>
                <div class="font-monospace fw-bold text-success mb-1" style="font-size: 14.5px; line-height: 1.4; word-break: break-word;">
                  ₹{{ number_format($po->amount_incl_gst, 2) }}
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  Base: ₹{{ number_format($subtotalExclGst, 2) }}
                </div>
              </div>
            </div>

            <!-- Card 4: Delivery Target & Status -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card executive-metric-card h-100 border-0 shadow-xs rounded-3 bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Delivery &amp; Fulfillment
                  </span>
                  <div class="rounded-2 d-flex align-items-center justify-content-center text-warning flex-shrink-0" style="width: 32px; height: 32px; background: rgba(245, 158, 11, 0.08); font-size: 15px;">
                    <i class="bi bi-truck"></i>
                  </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 13.5px; line-height: 1.4; word-break: break-word;">
                  {{ optional($po->delivery_date)->format('d M Y') ?? 'Immediate / On Call' }}
                </div>
                <div class="text-muted small" style="font-size: 11.5px;">
                  <i class="bi bi-box-seam me-1"></i>{{ $po->receivedQty() }}/{{ $po->po_qty }} Units Received
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Contracted Materials Table -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" style="font-size: 13px;">
              <thead class="table-light border-bottom">
                <tr class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                  <th class="ps-3 py-2.5 text-center" style="width: 35px;">#</th>
                  <th class="py-2.5">Item Name &amp; Description</th>
                  <th class="text-center py-2.5" style="width: 95px;">Quantity</th>
                  <th class="text-end py-2.5" style="width: 110px;">Unit Rate</th>
                  <th class="text-center py-2.5" style="width: 75px;">GST</th>
                  <th class="text-end py-2.5" style="width: 115px;">Subtotal (Base)</th>
                  <th class="text-end pe-3 py-2.5" style="width: 130px;">Line Total (Incl. GST)</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($items as $item)
                @php
                  $baseSubtotal = (float)$item->unit_cost * (int)$item->quantity;
                  $itemGst = $baseSubtotal * ((float)$item->gst_percent / 100);
                  $itemTotalWithGst = $item->lineTotalInclGst();
                  $gstRateFormatted = (int)$item->gst_percent == (float)$item->gst_percent ? (int)$item->gst_percent : number_format((float)$item->gst_percent, 2);
                @endphp
                <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#itemDetailModal-po-{{ $item->id }}" title="Click to view full specifications & financials">
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
                      {{ $item->quantity }} {{ $item->unit ?: 'Units' }}
                    </span>
                  </td>
                  <td class="text-end py-2.5 text-secondary font-monospace text-nowrap">
                    ₹{{ number_format($item->unit_cost, 2) }}
                  </td>
                  <td class="text-center py-2.5 text-nowrap">
                    <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 11px;">
                      {{ $gstRateFormatted }}%
                    </span>
                  </td>
                  <td class="text-end py-2.5 text-secondary font-monospace text-nowrap">
                    ₹{{ number_format($baseSubtotal, 2) }}
                  </td>
                  <td class="text-end pe-3 py-2.5 fw-bold text-dark font-monospace text-nowrap">
                    ₹{{ number_format($itemTotalWithGst, 2) }}
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    No material specifications linked to this order.
                  </td>
                </tr>
                @endforelse
              </tbody>
              <tfoot class="table-light border-top">
                <tr style="font-size: 13px;">
                  <td colspan="5" class="text-end py-2.5 text-secondary fw-semibold">
                    Total Contract Order Value:
                  </td>
                  <td class="text-end py-2.5 font-monospace text-dark fw-bold">
                    ₹{{ number_format($subtotalExclGst, 2) }}
                  </td>
                  <td class="text-end pe-3 py-2.5 font-monospace text-success fw-bold" style="font-size: 14.5px;">
                    ₹{{ number_format($po->amount_incl_gst, 2) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Item Full Detail Modals for PO -->
        @foreach ($items as $item)
        @php
          $itemBase = (float)$item->unit_cost * (int)$item->quantity;
          $itemGst = $itemBase * ((float)$item->gst_percent / 100);
          $itemTotal = $item->lineTotalInclGst();
          $gstFormatted = (int)$item->gst_percent == (float)$item->gst_percent ? (int)$item->gst_percent : number_format((float)$item->gst_percent, 2);
        @endphp
        <div class="modal fade" id="itemDetailModal-po-{{ $item->id }}" tabindex="-1" aria-labelledby="itemModalLabel-po-{{ $item->id }}" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
              <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                    #{{ $loop->iteration }}
                  </div>
                  <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="itemModalLabel-po-{{ $item->id }}">
                      {{ $item->item_name }}
                    </h6>
                    <div class="text-muted small" style="font-size: 11.5px;">Purchase Order Contracted Material Specifications</div>
                  </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                <!-- 4 Grid Metric Boxes -->
                <div class="row g-3 mb-4">
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Contracted Qty</div>
                      <div class="fw-bold text-dark fs-5 mt-1">{{ $item->quantity }} <span class="fs-6 text-muted fw-normal">{{ $item->unit ?: 'Units' }}</span></div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-2 border">
                      <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Agreed Unit Rate</div>
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
                    <div class="p-3 bg-success-subtle rounded-2 border border-success-subtle">
                      <div class="text-success text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Line Total (Incl. GST)</div>
                      <div class="fw-bold text-success fs-5 mt-1 font-monospace">₹{{ number_format($itemTotal, 2) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Technical Specifications & Description -->
                <div class="mb-4">
                  <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                    <i class="bi bi-card-text text-primary me-1"></i> Technical Specifications &amp; Scope of Supply
                  </h6>
                  <div class="p-3 rounded-2 border bg-light-subtle text-dark" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap;">{{ $item->description ?: 'Contracted supply specifications for ' . $item->item_name . '. Must conform to order delivery terms and statutory quality benchmarks.' }}</div>
                </div>

                <!-- Commercial Breakdown Table -->
                <div class="table-responsive border rounded-2 mb-3">
                  <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3 py-2">Commercial Item</th>
                        <th class="text-center py-2">Calculation Basis</th>
                        <th class="text-end pe-3 py-2">Amount (INR)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-3 py-2">Subtotal Base Amount</td>
                        <td class="text-center py-2 text-muted">{{ $item->quantity }} {{ $item->unit ?: 'Units' }} &times; ₹{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end pe-3 py-2 font-monospace fw-semibold">₹{{ number_format($itemBase, 2) }}</td>
                      </tr>
                      <tr>
                        <td class="ps-3 py-2">Statutory Tax (GST)</td>
                        <td class="text-center py-2 text-muted">{{ $gstFormatted }}% on ₹{{ number_format($itemBase, 2) }}</td>
                        <td class="text-end pe-3 py-2 font-monospace text-secondary">₹{{ number_format($itemGst, 2) }}</td>
                      </tr>
                      <tr class="table-light border-top fw-bold">
                        <td class="ps-3 py-2 text-dark">Contract Order Total</td>
                        <td class="text-center py-2 text-muted">All-inclusive</td>
                        <td class="text-end pe-3 py-2 font-monospace text-success fs-6">₹{{ number_format($itemTotal, 2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Metadata Footer -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 text-muted small" style="font-size: 12px;">
                  <span><i class="bi bi-receipt me-1"></i> PO: <strong>{{ $po->po_number }}</strong></span>
                  <span><i class="bi bi-shop me-1"></i> Vendor: <strong>{{ $po->vendor->company_name }}</strong></span>
                  <span><i class="bi bi-truck me-1"></i> Delivery: <strong>{{ optional($po->delivery_date)->format('d M Y') ?? 'Immediate' }}</strong></span>
                </div>
              </div>
              <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @endforeach

        <!-- Fulfillment & Goods Receipt (GRN) Status Panel -->
        @if ($po->status === 'approved')
          @php
            $receivedQty = $po->receivedQty();
            $totalPoQty = $po->po_qty;
            $remainingQty = $po->remainingQtyToReceive();
            $recvPct = $totalPoQty > 0 ? min(100, round(($receivedQty / $totalPoQty) * 100)) : 0;
            $canLogGrn = in_array(auth()->user()->role, [\App\Support\Roles::PR_SR_EXEC, \App\Support\Roles::ADMIN_HEAD], true);
          @endphp
          <div class="border-top p-3 bg-light-subtle">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-truck text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">Store Inward &amp; Goods Receipt Progress</h6>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white border text-dark font-monospace px-2.5 py-1" style="font-size: 12px;">
                  <strong class="text-primary">{{ $receivedQty }}</strong> / {{ $totalPoQty }} Units ({{ $recvPct }}%)
                </span>
                @if ($remainingQty === 0)
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                    <i class="bi bi-check2-circle me-1"></i>Fully Received
                  </span>
                @else
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                    {{ $remainingQty }} {{ Str::plural('Unit', $remainingQty) }} Pending
                  </span>
                @endif
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress mb-2" style="height: 8px; border-radius: 6px; background: #e2e8f0;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $recvPct }}%;" aria-valuenow="{{ $recvPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <!-- Contextual Status & Actions Bar -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-1">
              <div class="text-secondary small d-flex align-items-center gap-1.5" style="font-size: 12px;">
                @if ($remainingQty > 0)
                  <i class="bi bi-info-circle text-warning"></i>
                  <span><strong>{{ $remainingQty }}</strong> of {{ $totalPoQty }} contracted units remain to be delivered and inspected at the store.</span>
                @else
                  <i class="bi bi-check-circle-fill text-success"></i>
                  <span>All {{ $totalPoQty }} contracted units have been inspected and accounted for in departmental inventory.</span>
                @endif
              </div>

              <div class="d-flex align-items-center gap-2">
                @if ($po->grns && $po->grns->isNotEmpty())
                  @php $poGrn = $po->grns->first(); @endphp
                  <a href="{{ route('grn.show', $poGrn) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1 shadow-xs" style="font-size: 12px;">
                    <i class="bi bi-box-seam"></i>
                    <span>View GRN ({{ $poGrn->grn_number }})</span>
                  </a>
                @endif

                @if ($remainingQty > 0 && $canLogGrn)
                  <a href="{{ route('grn.create', ['po_id' => $po->id]) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 py-1 shadow-sm" style="font-size: 12px;">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <span>Log Goods Receipt (GRN)</span>
                  </a>
                @endif
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

  </div> <!-- End row g-4 -->

  @if ($pendingApproval)
    <!-- Decline Purchase Order Modal (Small, Clean & Minimal Window) -->
    <div class="modal fade" id="declinePoModal" tabindex="-1" aria-labelledby="declinePoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('po.reject', $po) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-danger-subtle py-2.5 px-3.5 border-bottom border-danger-subtle">
            <div class="d-flex align-items-center gap-2 text-danger">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0" id="declinePoModalLabel">Decline Purchase Order</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Please state the specific reason for declining <strong>{{ $po->po_number }}</strong>. This feedback will be recorded in the audit trail and routed back to the buyer for revision.
            </p>
            <div class="mb-1">
              <label for="rejectCommentsInput" class="form-label fw-semibold text-dark small">Reason for Rejection <span class="text-danger">*</span></label>
              <textarea name="comments" id="rejectCommentsInput" class="form-control" rows="3" placeholder="e.g. Price deviation exceeds approved budget, delivery timeline unrealistic, etc." required></textarea>
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
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'PO Detail' });
@endsection
