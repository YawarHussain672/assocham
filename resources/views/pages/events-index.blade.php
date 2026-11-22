@extends('layouts.app')

@section('title', 'Event-Wise PR & PO Management')

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-0">Event-Wise PR &amp; PO Management</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      {{-- Fiscal Year Selector --}}
      <form method="GET" action="{{ route('events.index') }}" class="d-flex align-items-center">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white text-secondary border-end-0">
            <i class="bi bi-calendar-range"></i>
          </span>
          <select name="fy" class="form-select form-select-sm fw-semibold text-dark border-start-0"
                  style="min-width: 135px; cursor: pointer; font-size: 12.5px;"
                  onchange="this.form.submit()" aria-label="Fiscal Year">
            @foreach ($fiscalYears as $fy)
              <option value="{{ $fy }}" {{ $fy === $fiscalYear ? 'selected' : '' }}>
                {{ $fy }}{{ $fy === $currentFy ? ' (Active)' : '' }}
              </option>
            @endforeach
          </select>
        </div>
      </form>

      @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
        <a href="{{ route('requisition.create') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-plus-circle"></i>
          <span>New PR</span>
        </a>
      @endif

      @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'checks-balances'))
        <a href="{{ route('checks-balances', ['view' => 'project']) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm">
          <i class="bi bi-folder2-open"></i>
          <span>Project Budgets</span>
        </a>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @php
    $totalEvents = $events->count();
    $eventsWithPr = $events->filter(fn($e) => $e->pr_count > 0)->count();
    $eventsWithPo = $events->filter(fn($e) => $e->po_count > 0)->count();
  @endphp

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="eventSwitchBar">
    <button type="button" class="erp-switch-tab active" data-act="all" onclick="selectEventTab('all')">
      All Events <span class="erp-switch-count">{{ $totalEvents }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-act="with_prs" onclick="selectEventTab('with_prs')">
      With PRs <span class="erp-switch-count">{{ $eventsWithPr }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-act="with_pos" onclick="selectEventTab('with_pos')">
      With POs <span class="erp-switch-count">{{ $eventsWithPo }}</span>
    </button>
  </div>

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container">
    <!-- Integrated Filter & Search Toolbar -->
    <div class="erp-table-toolbar">
      <div class="d-flex flex-wrap align-items-center gap-2 w-100">
        <!-- Live Search -->
        <div class="erp-search-wrap flex-grow-1" style="min-width: 240px;">
          <i class="bi bi-search erp-search-icon"></i>
          <input type="text" id="eventSearchInput" class="erp-search-input"
                 placeholder="Search Event, Department, Council, Location, or Lead..." oninput="applyEventFilters()">
        </div>

        <!-- Department Filter -->
        <div style="width: 170px;">
          <select id="eventDeptFilter" class="erp-select" onchange="applyEventFilters()">
            <option value="all">All Departments</option>
            @foreach ($departments as $dept)
              <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <!-- Reset Button -->
        <button type="button" class="erp-btn-reset d-none" id="clearEventFilterBtn" onclick="resetEventFilters()" title="Reset Filters">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </div>

    <table class="table erp-table" id="eventsTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 38%;">Event / Project Name</th>
            <th style="width: 14%;">Department</th>
            <th style="width: 14%; text-align: center;">Location &amp; Month</th>
            <th style="width: 12%; text-align: center;">PRs Linked</th>
            <th style="width: 12%; text-align: center;">POs Issued</th>
            <th class="pe-3 text-end" style="width: 10%;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($events as $event)
            @php
              $prs = $event->event_prs;
              $pos = $event->event_pos;
              $prCount = $event->pr_count;
              $poCount = $event->po_count;
              $remaining = $event->remainingAmount();
              $pct = $event->utilisationPercent();
              $searchBlob = strtolower($event->project_name . ' ' . $event->department . ' ' . $event->council . ' ' . $event->location . ' ' . $event->hod . ' ' . $event->category);
            @endphp
            <tr class="event-row"
                data-department="{{ $event->department }}"
                data-pr-count="{{ $prCount }}"
                data-po-count="{{ $poCount }}"
                data-search="{{ $searchBlob }}">
              <!-- Event Name & Details -->
              <td class="ps-3">
                <a href="{{ route('events.show', $event) }}" class="fw-bold text-dark text-decoration-none hover-primary d-block" style="font-size: 13.5px; line-height: 1.35;">
                  {{ $event->project_name }}
                </a>
                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap text-muted" style="font-size: 11px;">
                  @if ($event->council)
                    <span>{{ $event->council }}</span>
                  @endif
                  @if ($event->council && $event->hod)
                    <span class="text-secondary opacity-50">&bull;</span>
                  @endif
                  @if ($event->hod)
                    <span>Lead: {{ $event->hod }}</span>
                  @endif
                </div>
              </td>

              <!-- Department -->
              <td>
                <span class="badge badge-soft-info" style="font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 5px;">
                  {{ strtoupper($event->department) }}
                </span>
              </td>

              <!-- Location & Month -->
              <td class="text-center">
                <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $event->location ?: 'Delhi' }}</div>
                <div class="text-muted small" style="font-size: 11px;">{{ $event->event_month ?: 'FY Period' }}</div>
              </td>

              <!-- PRs Count & View Trigger -->
              <td class="text-center">
                @if ($prCount > 0)
                  <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-2.5 rounded-pill d-inline-flex align-items-center gap-1"
                          data-bs-toggle="modal" data-bs-target="#eventPrModal-{{ $event->id }}"
                          style="font-size: 11.5px; font-weight: 600;" title="View all linked Purchase Requisitions">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>{{ $prCount }} PR{{ $prCount > 1 ? 's' : '' }}</span>
                  </button>
                @else
                  <span class="text-muted small" style="font-size: 11.5px;">0 PRs</span>
                @endif
              </td>

              <!-- POs Count & View Trigger -->
              <td class="text-center">
                @if ($poCount > 0)
                  <button type="button" class="btn btn-xs btn-outline-success py-0.5 px-2.5 rounded-pill d-inline-flex align-items-center gap-1"
                          data-bs-toggle="modal" data-bs-target="#eventPoModal-{{ $event->id }}"
                          style="font-size: 11.5px; font-weight: 600;" title="View all issued Purchase Orders">
                    <i class="bi bi-receipt"></i>
                    <span>{{ $poCount }} PO{{ $poCount > 1 ? 's' : '' }}</span>
                  </button>
                @else
                  <span class="text-muted small" style="font-size: 11.5px;">0 POs</span>
                @endif
              </td>

              <!-- Action: View and Delete Buttons -->
              <td class="pe-3 text-end">
                <div class="erp-row-actions">
                  <a href="{{ route('events.show', $event) }}" class="erp-btn-action-view">
                    <i class="bi bi-eye"></i> View
                  </a>
                  <form method="POST" action="{{ route('events.destroy', $event) }}" class="d-inline m-0" onsubmit="return confirm('Delete event \'{{ addslashes($event->project_name) }}\'? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="erp-btn-action-delete" title="Delete Event">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <div class="fw-semibold">No events configured for {{ $fiscalYear }}.</div>
              </td>
            </tr>
          @endforelse

          <tr id="emptyEventFilterRow" class="d-none">
            <td colspan="6" class="text-center py-4 text-muted">
              <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary opacity-50"></i>
              <span>No events match your search or filter criteria.</span>
            </td>
          </tr>
        </tbody>
      </table>
  </div>

  <!-- Event PR & PO Quick Inspect Modals -->
  @foreach ($events as $event)
    <!-- PR Modal for Event {{ $event->id }} -->
    @if ($event->pr_count > 0)
      <div class="modal fade" id="eventPrModal-{{ $event->id }}" tabindex="-1" aria-labelledby="eventPrModalLabel-{{ $event->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
              <div>
                <span class="badge bg-primary text-white" style="font-size: 10px;">PR Overview</span>
                <h6 class="modal-title fw-bold text-dark mb-0 mt-1" id="eventPrModalLabel-{{ $event->id }}">
                  {{ $event->project_name }} &mdash; Requisitions
                </h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
              <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                  <thead class="table-light text-secondary text-uppercase" style="font-size: 10.5px;">
                    <tr>
                      <th class="ps-3 py-2">PR Number</th>
                      <th class="py-2">Title / Items</th>
                      <th class="py-2">Requester</th>
                      <th class="py-2 text-center">Status</th>
                      <th class="py-2 text-end">Est. Cost (₹)</th>
                      <th class="pe-3 py-2 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($event->event_prs as $pr)
                      <tr>
                        <td class="ps-3 py-2 font-monospace fw-semibold">
                          <a href="{{ route('requisition.show', $pr) }}" class="text-primary text-decoration-none">
                            {{ $pr->pr_number }}
                          </a>
                        </td>
                        <td class="py-2">
                          <div class="text-dark fw-medium">{{ $pr->title ?: 'Procurement Requisition' }}</div>
                          <div class="text-muted" style="font-size: 10.5px;">
                            {{ $pr->items->count() }} item(s) &bull; {{ $pr->created_at->format('d-M-Y') }}
                          </div>
                        </td>
                        <td class="py-2 text-secondary">{{ $pr->creator?->name ?? 'System' }}</td>
                        <td class="py-2 text-center">
                          <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                            {{ ucfirst(str_replace('_', ' ', $pr->status)) }}
                          </span>
                        </td>
                        <td class="py-2 text-end font-monospace fw-bold">
                          ₹{{ number_format($pr->estimatedCostInclGst(), 2) }}
                        </td>
                        <td class="pe-3 py-2 text-center">
                          <a href="{{ route('requisition.show', $pr) }}" class="btn btn-primary dash-review-btn">
                            View
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer py-2 px-4 border-top bg-light">
              <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- PO Modal for Event {{ $event->id }} -->
    @if ($event->po_count > 0)
      <div class="modal fade" id="eventPoModal-{{ $event->id }}" tabindex="-1" aria-labelledby="eventPoModalLabel-{{ $event->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
              <div>
                <span class="badge bg-success text-white" style="font-size: 10px;">PO Overview</span>
                <h6 class="modal-title fw-bold text-dark mb-0 mt-1" id="eventPoModalLabel-{{ $event->id }}">
                  {{ $event->project_name }} &mdash; Purchase Orders
                </h6>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
              <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                  <thead class="table-light text-secondary text-uppercase" style="font-size: 10.5px;">
                    <tr>
                      <th class="ps-3 py-2">PO Number</th>
                      <th class="py-2">Vendor</th>
                      <th class="py-2">Linked PR</th>
                      <th class="py-2 text-center">Status</th>
                      <th class="py-2 text-end">Amount (₹)</th>
                      <th class="pe-3 py-2 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($event->event_pos as $po)
                      <tr>
                        <td class="ps-3 py-2 font-monospace fw-semibold">
                          <a href="{{ route('po.show', $po) }}" class="text-primary text-decoration-none">
                            {{ $po->po_number }}
                          </a>
                        </td>
                        <td class="py-2">
                          <div class="text-dark fw-medium">{{ $po->vendor?->company_name ?? '—' }}</div>
                          <div class="text-muted" style="font-size: 10.5px;">{{ $po->created_at->format('d-M-Y') }}</div>
                        </td>
                        <td class="py-2 font-monospace text-secondary">{{ $po->purchaseRequisition?->pr_number ?? '—' }}</td>
                        <td class="py-2 text-center">
                          <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                            {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                          </span>
                        </td>
                        <td class="py-2 text-end font-monospace fw-bold">
                          ₹{{ number_format((float) $po->amount_incl_gst, 2) }}
                        </td>
                        <td class="pe-3 py-2 text-center">
                          <a href="{{ route('po.show', $po) }}" class="btn btn-primary dash-review-btn">
                            View
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer py-2 px-4 border-top bg-light">
              <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endforeach

</div>
@endsection

@section('scripts')
<script>
let currentEventActivity = 'all';

function selectEventTab(act) {
  currentEventActivity = act;
  applyEventFilters();
}

function applyEventFilters() {
  const search = (document.getElementById('eventSearchInput')?.value || '').trim().toLowerCase();
  const dept = document.getElementById('eventDeptFilter')?.value || 'all';
  const activity = currentEventActivity;

  document.querySelectorAll('#eventSwitchBar .erp-switch-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.act === activity);
  });

  const rows = Array.from(document.querySelectorAll('.event-row'));
  let matched = 0;

  rows.forEach(row => {
    const rowDept = row.getAttribute('data-department') || '';
    const prCount = parseInt(row.getAttribute('data-pr-count'), 10) || 0;
    const poCount = parseInt(row.getAttribute('data-po-count'), 10) || 0;
    const searchBlob = row.getAttribute('data-search') || '';

    const matchesSearch = !search || searchBlob.includes(search);
    const matchesDept = (dept === 'all') || (rowDept === dept);
    let matchesActivity = true;
    if (activity === 'with_prs') matchesActivity = (prCount > 0);
    else if (activity === 'with_pos') matchesActivity = (poCount > 0);
    else if (activity === 'with_both') matchesActivity = (prCount > 0 && poCount > 0);

    if (matchesSearch && matchesDept && matchesActivity) {
      row.classList.remove('d-none');
      matched++;
    } else {
      row.classList.add('d-none');
    }
  });

  const emptyRow = document.getElementById('emptyEventFilterRow');
  if (emptyRow) {
    if (matched === 0 && rows.length > 0) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  const badge = document.getElementById('eventRecordCountBadge');
  if (badge) {
    const isFiltered = (dept !== 'all' || activity !== 'all' || search !== '');
    badge.textContent = isFiltered ? `${matched} of ${rows.length} Events` : `${rows.length} Events`;
  }

  const counterEl = document.getElementById('eventCounterCount');
  if (counterEl) {
    counterEl.textContent = matched;
  }

  const clearBtn = document.getElementById('clearEventFilterBtn');
  if (clearBtn) {
    const isFiltered = (dept !== 'all' || activity !== 'all' || search !== '');
    if (isFiltered) clearBtn.classList.remove('d-none');
    else clearBtn.classList.add('d-none');
  }
}

function resetEventFilters() {
  if (document.getElementById('eventSearchInput')) document.getElementById('eventSearchInput').value = '';
  if (document.getElementById('eventDeptFilter')) document.getElementById('eventDeptFilter').value = 'all';
  currentEventActivity = 'all';
  applyEventFilters();
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'events', crumb: 'Governance & Controls', title: 'Event-Wise PR & PO' });
@endsection
