@extends('layouts.app')

@section('title', $event->project_name . ' — Event Dossier')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Back Navigation Link -->
  <div class="mb-3">
    <a href="{{ route('events.index') }}" class="back-link d-inline-flex align-items-center gap-1.5 text-secondary text-decoration-none small fw-semibold">
      <i class="bi bi-arrow-left"></i>
      <span>Back to Event-Wise Management</span>
    </a>
  </div>

  <style>
    .erp-btn-action-view i {
      color: #64748b !important;
    }
    .erp-btn-action-view:hover i {
      color: #b45f00 !important;
    }
  </style>

  <!-- Header Dossier Box Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 w-100 position-relative" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base); background: #ffffff;">
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <!-- Event Identity & Info -->
        <div class="text-start">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $event->project_name }}</span>
            <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 12px; font-weight: 600; border-radius: 6px;">
              {{ $event->fiscal_year }}
            </span>
            <span class="badge bg-light text-dark border px-2.5 py-1" style="font-size: 12px; font-weight: 600; border-radius: 6px;">
              {{ strtoupper($event->department) }}
            </span>
          </div>
          <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
            @if ($event->location)
              <span class="detail-meta-chip"><i class="bi bi-geo-alt"></i> <span class="meta-label">Location:</span> <strong>{{ $event->location }}</strong></span>
            @endif
            @if ($event->event_month)
              <span class="detail-meta-chip"><i class="bi bi-calendar3"></i> <span class="meta-label">Month:</span> <strong>{{ $event->event_month }}</strong></span>
            @endif
            @if ($event->hod)
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Lead:</span> <strong>{{ $event->hod }}</strong></span>
            @endif
          </div>
        </div>

        <!-- Download & Export Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
          <a href="{{ route('events.pdf', $event) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5" style="border-radius: 6px; font-size: 12.5px;" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>Consolidated PDF</span>
          </a>
          <a href="{{ route('events.zip', $event) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5" style="border-radius: 6px; font-size: 12.5px;">
            <i class="bi bi-file-earmark-zip"></i>
            <span>Download All ZIP</span>
          </a>
          <a href="{{ route('events.sheet', $event) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5" style="border-radius: 6px; font-size: 12.5px;">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>Export CSV</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Budget & Procurement KPI Stat Cards -->
  @php
    $eventAllocated = (float) $event->allocated_amount;
    $eventUtilised = (float) $event->utilised_amount;
    $eventHeadroom = max(0, $eventAllocated - $eventUtilised);
    $eventPct = $eventAllocated > 0 ? round(($eventUtilised / $eventAllocated) * 100, 1) : 0;
  @endphp
  <div class="row g-2 mb-3" id="eventBudgetKpiRow">
    <!-- Ceiling / Total Limit -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Project Budget</span>
            <div class="stat-icon-box icon-primary">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="stat-value">₹{{ number_format($eventAllocated, 2) }}</div>
          <div class="stat-footer text-muted">
            <span>{{ $event->fiscal_year }} allocation</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Committed / Incurred Expense -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-warning card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Project Expense</span>
            <div class="stat-icon-box icon-warning">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="stat-value">₹{{ number_format($eventUtilised, 2) }}</div>
          <div class="stat-footer text-muted">
            <span><strong class="text-dark">{{ $eventPct }}%</strong> utilised of ceiling</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Headroom / Surplus -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Project Surplus</span>
            <div class="stat-icon-box icon-success">
              <i class="bi bi-check-circle"></i>
            </div>
          </div>
          <div class="stat-value">₹{{ number_format($eventHeadroom, 2) }}</div>
          <div class="stat-footer">
            @if ($eventHeadroom > 0)
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-medium">
                <i class="bi bi-shield-check me-1"></i>Available headroom
              </span>
            @else
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill fw-medium">
                <i class="bi bi-exclamation-octagon me-1"></i>Ceiling Reached
              </span>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Active PO Spend -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Committed PO Spend</span>
            <div class="stat-icon-box icon-info">
              <i class="bi bi-receipt"></i>
            </div>
          </div>
          <div class="stat-value">₹{{ number_format($totalPoSpend, 2) }}</div>
          <div class="stat-footer text-muted">
            <span>{{ $pos->count() }} PO{{ $pos->count() === 1 ? '' : 's' }} issued/pending</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Enseur / Budget Master Segmented Switch Bar -->
  <div class="erp-switch-bar mb-3" id="eventDossierSwitchBar">
    <button type="button" class="erp-switch-tab active" data-target="prSection" onclick="switchEventTab('prSection', this)">
      Purchase Requisitions <span class="erp-switch-count">{{ $prs->count() }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-target="poSection" onclick="switchEventTab('poSection', this)">
      Purchase Orders <span class="erp-switch-count">{{ $pos->count() }}</span>
    </button>
  </div>

  <!-- SECTION 1: PURCHASE REQUISITIONS (PR) -->
  <div id="prSection" class="event-tab-pane">
    <div class="erp-table-container">
      <div class="erp-table-toolbar d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">
          Purchase Requisitions (PR)
        </h6>
        @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
          <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5" style="border-radius: 8px; font-size: 12.5px;">
            <i class="bi bi-plus-circle"></i>
            <span>Raise PR</span>
          </a>
        @endif
      </div>

      <table class="erp-table align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-3" style="width: 20%;">PR NUMBER</th>
            <th style="width: 32%;">TITLE / SCOPE OF WORK</th>
            <th style="width: 18%;">REQUESTER</th>
            <th style="width: 12%; text-align: center;">STATUS</th>
            <th style="width: 18%; text-align: right;">AMOUNT (INCL GST)</th>
            <th class="pe-3 text-end" style="width: 10%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($prs as $pr)
            @php
              $prCost = $pr->estimatedCostInclGst();
              $badgeClass = match($pr->status) {
                'approved' => 'erp-badge-success',
                'rejected' => 'erp-badge-danger',
                default => 'erp-badge-warning',
              };
            @endphp
            <tr>
              <td class="ps-3">
                <a href="{{ route('requisition.show', $pr) }}" class="erp-code-link" style="font-size: 13.5px;">
                  {{ $pr->pr_number }}
                </a>
              </td>
              <td>
                <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $pr->title ?: 'Procurement Requisition' }}</div>
                <div class="text-muted small mt-0.5" style="font-size: 11px;">
                  {{ $pr->items->count() }} line item(s) &bull; {{ $pr->created_at->format('d M Y') }}
                  &bull; <span class="badge bg-light text-secondary border" style="font-size: 9.5px;">{{ $pr->isCopex() ? 'CapEx' : ($pr->expense_type === 'opex' ? 'OpEx' : 'Unclassified') }}</span>
                </div>
              </td>
              <td>
                <div class="text-dark fw-medium" style="font-size: 13px;">{{ $pr->creator?->name ?? 'System' }}</div>
                <div class="text-muted small" style="font-size: 11px;">{{ $pr->department }}</div>
              </td>
              <td class="text-center">
                <span class="{{ $badgeClass }}">
                  {{ ucfirst(str_replace('_', ' ', $pr->status)) }}
                </span>
              </td>
              <td class="text-end font-monospace fw-bold {{ $pr->status === 'rejected' ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                ₹{{ number_format($prCost, 2) }}
              </td>
              <td class="pe-3 text-end">
                <div class="erp-row-actions">
                  <a href="{{ route('requisition.show', $pr) }}" class="erp-btn-action-view">
                    <i class="bi bi-eye"></i> View
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                <div class="fw-medium text-secondary">No purchase requisitions linked to this event yet.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- SECTION 2: PURCHASE ORDERS (PO) -->
  <div id="poSection" class="event-tab-pane d-none">
    <div class="erp-table-container">
      <div class="erp-table-toolbar d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">
          Purchase Orders (PO)
        </h6>
        @if (in_array(auth()->user()?->role, [\App\Support\Roles::BUYER, \App\Support\Roles::ADMIN_HEAD], true))
          <a href="{{ route('po.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5" style="border-radius: 8px; font-size: 12.5px;">
            <i class="bi bi-plus-circle"></i>
            <span>Create PO</span>
          </a>
        @endif
      </div>

      <table class="erp-table align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-3" style="width: 20%;">PO NUMBER</th>
            <th style="width: 30%;">CONTRACTED VENDOR</th>
            <th style="width: 18%;">LINKED PR</th>
            <th style="width: 12%; text-align: center;">STATUS</th>
            <th style="width: 20%; text-align: right;">AMOUNT (INCL GST)</th>
            <th class="pe-3 text-end" style="width: 10%;">ACTION</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($pos as $po)
            @php
              $poBadgeClass = match($po->status) {
                'approved' => 'erp-badge-success',
                'rejected' => 'erp-badge-danger',
                default => 'erp-badge-warning',
              };
            @endphp
            <tr>
              <td class="ps-3">
                <a href="{{ route('po.show', $po) }}" class="erp-code-link" style="font-size: 13.5px;">
                  {{ $po->po_number }}
                </a>
              </td>
              <td>
                <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $po->vendor?->company_name ?? '—' }}</div>
                <div class="text-muted small mt-0.5" style="font-size: 11px;">
                  Date: {{ $po->created_at->format('d M Y') }}
                  @if ($po->vendor?->gstin) &bull; GSTIN: {{ $po->vendor->gstin }} @endif
                </div>
              </td>
              <td>
                @if ($po->purchaseRequisition && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
                  <a href="{{ route('requisition.show', $po->purchaseRequisition) }}" class="font-monospace text-secondary text-decoration-none fw-medium">
                    {{ $po->purchaseRequisition->pr_number }}
                  </a>
                @elseif ($po->purchaseRequisition)
                  <span class="font-monospace text-secondary fw-medium">{{ $po->purchaseRequisition->pr_number }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="text-center">
                <span class="{{ $poBadgeClass }}">
                  {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                </span>
              </td>
              <td class="text-end font-monospace fw-bold text-dark">
                ₹{{ number_format((float) $po->amount_incl_gst, 2) }}
              </td>
              <td class="pe-3 text-end">
                <div class="erp-row-actions">
                  <a href="{{ route('po.show', $po) }}" class="erp-btn-action-view">
                    <i class="bi bi-eye"></i> View
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-receipt-cutoff fs-2 d-block mb-1 text-secondary opacity-50"></i>
                <div class="fw-medium text-secondary">No purchase orders issued for this event yet.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
function switchEventTab(targetId, btn) {
  document.querySelectorAll('#eventDossierSwitchBar .erp-switch-tab').forEach(t => t.classList.remove('active'));
  if (btn) btn.classList.add('active');

  document.querySelectorAll('.event-tab-pane').forEach(pane => pane.classList.add('d-none'));
  const target = document.getElementById(targetId);
  if (target) target.classList.remove('d-none');
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'events', crumb: 'Governance & Controls', title: 'Event Procurement Dossier' });
@endsection
