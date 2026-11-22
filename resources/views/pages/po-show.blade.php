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

  $items = $po->effective_items;
  $subtotalExclGst = $items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
  $totalGst = (float)$po->amount_incl_gst - $subtotalExclGst;
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <style>
    .btn-pdf-action {
      background-color: #fdf2f4 !important;
      color: #c8102e !important;
      border: 1px solid #c8102e !important;
      font-size: 13px !important;
      border-radius: 6px !important;
      transition: all 0.15s ease-in-out !important;
      text-decoration: none !important;
    }
    .btn-pdf-action:hover, .btn-pdf-action:focus {
      background-color: #c8102e !important;
      color: #ffffff !important;
      border-color: #c8102e !important;
      box-shadow: 0 2px 4px rgba(200, 16, 46, 0.22) !important;
    }
    .btn-pdf-action:hover i, .btn-pdf-action:focus i {
      color: #ffffff !important;
    }
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
  </style>

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1.5 text-secondary text-decoration-none">
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

  @if ($po->status === 'on_hold')
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3.5 mb-3" style="border-radius: 8px; background: #fffbeb; border-left: 5px solid #f59e0b !important;">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-dark fs-6 mb-0.5">This Purchase Order is Currently On Hold</div>
          <div class="text-secondary small">
            Review is paused at this approval stage pending vendor clarification or updated terms.
          </div>
        </div>
      </div>
      <span class="badge bg-warning text-dark px-3 py-1.5 fw-semibold" style="font-size: 11.5px;">On Hold</span>
    </div>
  @endif

  @php
    $canRegeneratePo = $po->canRegenerate() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true);
    $hasMultiVendorSplit = $po->purchaseRequisition && $po->purchaseRequisition->purchaseOrders()->count() > 1;
  @endphp

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100" style="overflow: visible !important; border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
    
    @if ($hasMultiVendorSplit)
      <!-- Multi-Vendor Split Navigation Strip (Integrated directly in card header) -->
      <div class="px-3.5 py-2.5 border-bottom d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2.5" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0 !important; border-radius: var(--border-radius-base) var(--border-radius-base) 0 0;">
        <div class="d-flex align-items-center flex-wrap gap-2" style="font-size: 12.5px;">
          <span class="badge bg-white text-dark border px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" style="border-color: #cbd5e1 !important;">
            <i class="bi bi-diagram-3 text-secondary"></i>
            <span>Multi-Vendor Order Split</span>
          </span>
          <span class="text-muted">
            Generated from PR
            @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
              <a href="{{ route('requisition.show', $po->purchaseRequisition) }}" class="fw-semibold text-primary text-decoration-none">{{ $po->purchaseRequisition->pr_number }}</a>
            @else
              <strong class="font-monospace text-dark">{{ $po->purchaseRequisition->pr_number }}</strong>
            @endif
            across {{ $po->purchaseRequisition->purchaseOrders()->count() }} vendors
          </span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 12px;">
          <span class="text-muted small fw-medium">Switch Order:</span>
          <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
            @foreach ($po->purchaseRequisition->purchaseOrders as $sibPo)
              @if ($sibPo->id === $po->id)
                <span class="px-2.5 py-1 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap rounded-2" style="font-size: 12px; background: #fff7ed; border: 1px solid #fdba74; color: #b45f00;">
                  <i class="bi bi-receipt text-warning-emphasis"></i>
                  <span>{{ $sibPo->po_number }} &middot; {{ Str::limit($sibPo->vendor->company_name, 20) }}</span>
                  <span class="badge bg-warning text-dark px-1.5 py-0.5 ms-1 fw-bold" style="font-size: 9px; border-radius: 3px;">Active</span>
                </span>
              @else
                <a href="{{ route('po.show', $sibPo) }}" class="px-2.5 py-1 fw-medium text-decoration-none d-inline-flex align-items-center gap-1.5 text-nowrap rounded-2 bg-white text-secondary border hover-primary" style="font-size: 12px; border-color: #e2e8f0; color: #475569 !important;">
                  <i class="bi bi-receipt text-muted"></i>
                  <span>{{ $sibPo->po_number }} &middot; {{ Str::limit($sibPo->vendor->company_name, 20) }}</span>
                </a>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    @endif

    <!-- Main Row: PO Identity, Metadata Chips & Document Actions -->
    <div class="p-3 p-md-4 bg-white w-100" style="border-radius: {{ $hasMultiVendorSplit ? '0 0 var(--border-radius-base) var(--border-radius-base)' : 'var(--border-radius-base)' }};">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        
        <!-- Left: PO Number + Status + Structured Meta Chips -->
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $po->po_number }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
              @if ($po->revision_number > 0)
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                  Rev {{ $po->revision_number }}
                </span>
              @endif
            </div>
            
            @php
              $poProjName = $po->event_name ?: ($po->purchaseRequisition?->projectBudget?->project_name ?: $po->purchaseRequisition?->event_name);
              $poDeptName = $po->department ?: ($po->purchaseRequisition?->department ?? 'General');
            @endphp
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="detail-meta-chip">
                <i class="bi bi-building"></i>
                <span class="meta-label">Vendor:</span>
                <strong>{{ $po->vendor->company_name }}</strong>
              </span>

              <span class="detail-meta-chip">
                <i class="bi bi-file-earmark-text"></i>
                <span class="meta-label">PR:</span>
                @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
                  <a href="{{ route('requisition.show', $po->purchaseRequisition) }}" title="View Purchase Requisition">
                    {{ $po->purchaseRequisition->pr_number }}
                  </a>
                @else
                  <strong>{{ $po->purchaseRequisition->pr_number }}</strong>
                @endif
              </span>

              @if ($poDeptName)
                <span class="detail-meta-chip">
                  <i class="bi bi-diagram-3"></i>
                  <span class="meta-label">Dept:</span>
                  <strong>{{ $poDeptName }}</strong>
                </span>
              @endif

              @if ($poProjName)
                <span class="detail-meta-chip">
                  <i class="bi bi-folder2"></i>
                  <span class="meta-label">Project:</span>
                  <strong>{{ $poProjName }}</strong>
                </span>
              @endif
            </div>
          </div>
        </div>

        <!-- Right: Primary Actions & More Dropdown -->
        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0 text-start">
          @if ($pendingApproval)
            <!-- Approve Button Form (Primary Action) -->
            <form method="POST" action="{{ route('po.approve', $po) }}" class="m-0 d-inline">
              @csrf
              <input type="hidden" name="comments" value="Approved for issuance.">
              <button type="submit" class="btn btn-sm btn-success px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap shadow-sm" style="font-size: 13px; border-radius: 6px;">
                <i class="bi bi-check-lg"></i>
                <span>Approve &amp; Issue PO</span>
              </button>
            </form>

            <!-- Decline PO Button (Directly Visible Next to Approve) -->
            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-nowrap" style="font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#declinePoModal">
              <i class="bi bi-x-lg"></i>
              <span>Decline PO</span>
            </button>
          @endif

          @if ($canRegeneratePo)
            <form method="POST" action="{{ route('po.regenerate', $po) }}" class="d-inline mb-0">
              @csrf
              <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 shadow-sm px-3 py-1.5" style="font-size: 13px; font-weight: 600; border-radius: 6px;" title="Regenerate PO PDF & increment revision after re-approval">
                <i class="bi bi-arrow-repeat"></i>
                <span>Regenerate PO</span>
              </button>
            </form>
          @endif

          <!-- More Actions Dropdown (PDF and Secondary Operations) -->
          <div class="dropdown d-inline-block position-relative">
            <button class="btn btn-sm btn-more-action dropdown-toggle d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 fw-semibold" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="More Actions">
              <i class="bi bi-three-dots"></i>
              <span>More</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border py-1" style="font-size: 13px; min-width: 205px; z-index: 1060; border-radius: 8px;">
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark fw-medium" href="{{ route('po.pdf', $po) }}" id="btnDownloadPdf" target="_blank">
                  <i class="bi bi-file-earmark-pdf-fill text-danger fs-6"></i>
                  <span>Download PDF</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('po.preview-pdf', $po) }}" target="_blank" id="btnPreviewPdf">
                  <i class="bi bi-eye text-primary fs-6"></i>
                  <span>Preview in Browser</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-muted" href="{{ route('po.preview-pdf', [$po, 'regenerate' => 1]) }}" target="_blank">
                  <i class="bi bi-arrow-repeat text-secondary fs-6"></i>
                  <span>Force Regenerate Cache</span>
                </a>
              </li>

              @if ($pendingApproval)
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning-emphasis fw-medium" data-bs-toggle="modal" data-bs-target="#holdPoModal">
                    <i class="bi bi-pause-circle text-warning fs-6"></i>
                    <span>Put On Hold</span>
                  </button>
                </li>
              @endif

              @if ($po->purchaseRequisition?->projectBudget && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'events'))
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-primary fw-medium" href="{{ route('events.show', $po->purchaseRequisition->projectBudget) }}" title="View Event Procurement Dossier">
                    <i class="bi bi-calendar-event fs-6"></i>
                    <span>Event Dossier</span>
                  </a>
                </li>
              @endif

              @if (in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true) && $po->status !== 'cancelled')
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-dark" href="{{ route('po.edit', $po) }}" title="Edit PO details">
                    <i class="bi bi-pencil text-secondary fs-6"></i>
                    <span>Edit PO</span>
                  </a>
                </li>
                @if (in_array($po->status, ['pending', 'approved', 'rejected'], true) && !$po->grns()->exists())
                  <li>
                    <form method="POST" action="{{ route('po.cancel', $po) }}" onsubmit="return confirm('Are you sure you want to cancel this Purchase Order and release reserved budget headroom?');" class="m-0">
                      @csrf
                      <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger">
                        <i class="bi bi-x-circle fs-6"></i>
                        <span>Cancel PO</span>
                      </button>
                    </form>
                  </li>
                @endif
              @endif
            </ul>
          </div>
        </div>

      </div>
    </div>

    @if ($po->status === 'rejected' && !$po->grns()->exists() && in_array(auth()->user()->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
      <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #fef2f2; border-top: 1px solid #fecaca !important;">
        <div class="text-danger-emphasis small" style="font-size: 12.5px;">
          <strong>PO Rejected:</strong> Edit &amp; resubmit this PO for re-approval, or click <strong>Cancel</strong> to release any reserved department/project budget headroom.
        </div>
      </div>
    @endif

    @if ($canRegeneratePo)
      <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #f0fdf4; border-top: 1px solid #bbf7d0 !important;">
        <div class="d-flex align-items-center gap-2 text-success-emphasis small" style="font-size: 12.5px;">
          <i class="bi bi-check2-circle text-success fs-5"></i>
          <span>This Purchase Order has been <strong>re-approved</strong> following post-approval edits. Click <strong>Regenerate PO</strong> to issue the updated revision document.</span>
        </div>
      </div>
    @endif

  </div>

  <!-- Horizontal PO Governance Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: PO Created -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Order Drafted">
              <i class="bi bi-check-lg"></i>
            </div>
            <div class="h-step-card done-card">
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
                  <div class="h-step-quote {{ $isApproved ? 'quote-approved' : '' }}" 
                       data-bs-toggle="modal" 
                       data-bs-target="#poApprovalCommentModal-{{ $approval->id }}"
                       title="Click to view full comment">
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
          <div>
            <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
              Contracted Material Specifications
            </h6>
          </div>
        </div>

        <!-- Key Information Grid -->
        <div class="p-3 bg-white border-bottom">
          <div class="erp-kv-grid p-0">
            <!-- Item 1: Contracted Vendor -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Contracted Vendor</span>
              <div class="erp-kv-val">
                @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'empanelment'))
                  <a href="{{ route('empanelment.show', $po->vendor) }}" class="text-decoration-none text-dark hover-primary">
                    {{ $po->vendor->company_name }}
                  </a>
                @else
                  {{ $po->vendor->company_name }}
                @endif
              </div>
              <div class="text-muted font-monospace" style="font-size: 11px;">
                Code: {{ $po->vendor->vendor_code }}
              </div>
            </div>

            <!-- Item 2: Department & Indentor -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Department &amp; Raised By</span>
              <div class="erp-kv-val">
                {{ $po->purchaseRequisition->department }}
              </div>
              <div class="text-muted" style="font-size: 11px;">
                {{ $po->purchaseRequisition->creator?->name ?? 'System' }}
              </div>
            </div>

            <!-- Item 3: Total Contract Value -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Total Contract Value</span>
              <div class="erp-kv-val font-monospace text-success" style="font-size: 1rem;">
                ₹{{ number_format($po->amount_incl_gst, 2) }}
              </div>
              <div class="text-muted font-monospace" style="font-size: 11px;">
                Base: ₹{{ number_format($subtotalExclGst, 2) }}
              </div>
            </div>

            <!-- Item 4: Delivery Target & Status -->
            <div class="erp-kv-item">
              <span class="erp-kv-lbl">Delivery &amp; Fulfillment</span>
              <div class="erp-kv-val">
                {{ optional($po->delivery_date)->format('d M Y') ?? 'Immediate / On Call' }}
              </div>
              <div class="text-muted" style="font-size: 11px;">
                {{ $po->receivedQty() }}/{{ $po->po_qty }} Units Received
              </div>
            </div>
          </div>
        </div>

        <!-- Contracted Materials Table -->
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
                  <th class="text-end" style="width: 115px;">SUBTOTAL</th>
                  <th class="text-end pe-3" style="width: 125px;">LINE TOTAL</th>
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
              <tfoot class="border-top">
                @php
                  $itemsTotalGst = $items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity * ((float)$i->gst_percent / 100));
                  $itemsGrandTotal = $items->sum(fn ($i) => $i->lineTotalInclGst());
                  $hasCustomPoAmount = (float)$po->amount_incl_gst > 0 && abs((float)$po->amount_incl_gst - $itemsGrandTotal) > 0.01;
                  $displayGrandTotal = $hasCustomPoAmount ? (float)$po->amount_incl_gst : $itemsGrandTotal;
                  $poGstPercent = $subtotalExclGst > 0 ? ($itemsTotalGst / $subtotalExclGst) * 100 : 0;
                  $poGstFormatted = (int)$poGstPercent == (float)$poGstPercent ? (int)$poGstPercent : number_format($poGstPercent, 2);
                @endphp
                <tr style="font-size: 13px; background: #ffffff;">
                  <td colspan="6" class="text-end py-2 text-muted fw-normal">Subtotal (Excl. GST):</td>
                  <td class="text-end pe-3 py-2 font-monospace text-secondary fw-semibold">₹{{ number_format($subtotalExclGst, 2) }}</td>
                </tr>
                <tr style="font-size: 13px; background: #ffffff;">
                  <td colspan="6" class="text-end py-1 text-muted fw-normal">Estimated GST ({{ $poGstFormatted }}%):</td>
                  <td class="text-end pe-3 py-1 font-monospace text-secondary fw-semibold">₹{{ number_format(max(0, $itemsTotalGst), 2) }}</td>
                </tr>
                <tr style="font-size: 14px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                  <td colspan="6" class="text-end py-3 text-dark fw-bold">Grand Total (Incl. GST):</td>
                  <td class="text-end pe-3 py-3 font-monospace fw-bold fs-6" style="color: #c8102e;">
                    ₹{{ number_format($displayGrandTotal, 2) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

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
                <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">Goods Receipt Progress</h6>
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
                  @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'grn'))
                    <a href="{{ route('grn.show', $poGrn) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-3 py-1 shadow-xs" style="font-size: 12px;">
                      <i class="bi bi-box-seam"></i>
                      <span>View GRN ({{ $poGrn->grn_number }})</span>
                    </a>
                  @else
                    <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size: 11.5px;">
                      GRN: {{ $poGrn->grn_number }} ({{ ucfirst(str_replace('_', ' ', $poGrn->status)) }})
                    </span>
                  @endif
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
    <!-- Place Purchase Order On Hold Modal -->
    <div class="modal fade" id="holdPoModal" tabindex="-1" aria-labelledby="holdPoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form method="POST" action="{{ route('po.hold', $po) }}" class="modal-content border-0 shadow">
          @csrf
          <div class="modal-header bg-warning-subtle py-2.5 px-3.5 border-bottom border-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis">
              <i class="bi bi-pause-circle-fill fs-5 text-warning"></i>
              <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="holdPoModalLabel">Place Purchase Order On Hold</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5">
            <p class="text-secondary small mb-3">
              Placing <strong>{{ $po->po_number }}</strong> on hold temporarily pauses the approval review at this stage until terms or commercial points are resolved.
            </p>
            <div class="mb-1">
              <label for="holdPoCommentsInput" class="form-label fw-semibold text-dark small">Reason for Placing On Hold <span class="text-danger">*</span></label>
              <textarea name="comments" id="holdPoCommentsInput" class="form-control" rows="3" placeholder="State reason for hold (e.g. Confirming delivery timeline with vendor, revising payment terms)..." required></textarea>
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

  {{-- PO Approval Comments Modals --}}
  @foreach ($po->approvals as $approval)
    @if ($approval->comments)
      @php
        $stageTitle = $approval->role_name ?? ('Approver (Tier ' . $approval->level . ')');
        $isAppr = $approval->status === 'approved';
        $isRej = $approval->status === 'rejected';
      @endphp
      <div class="modal fade" id="poApprovalCommentModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
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
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'PO Detail' });
@endsection
