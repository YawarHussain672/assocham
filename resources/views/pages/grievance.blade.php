@extends('layouts.app')

@section('title', 'Grievance Redressal')

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'badge' => 'warning'],
    'admin_head' => ['label' => 'With Admin Head', 'badge' => 'warning'],
    'cfo'        => ['label' => 'With CFO', 'badge' => 'warning'],
    'sg'         => ['label' => 'With SG', 'badge' => 'warning'],
  ];

  $totalGrievances = $grievances->count();
  $activeGrievances = $grievances->where('status', '!=', 'resolved')->count();
  $resolvedGrievances = $grievances->where('status', 'resolved')->count();
  $seniorQueue = $grievances->whereIn('current_owner_role', ['cfo', 'sg'])->where('status', '!=', 'resolved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header & Action Bar -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Grievance Redressal &amp; Incident Tracker</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('grievance.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold px-3 py-1.5 shadow-sm">
        <i class="bi bi-ticket-perforated"></i>
        <span>Raise Ticket</span>
      </a>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>{{ session('status') }}</div>
    </div>
  @endif

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="grvSwitchBar">
    <button type="button" class="erp-switch-tab active" data-status="all" onclick="selectGrvTab('all')">
      All Tickets <span class="erp-switch-count">{{ $totalGrievances }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="active" onclick="selectGrvTab('active')">
      Active / In Review <span class="erp-switch-count">{{ $activeGrievances }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="senior" onclick="selectGrvTab('senior')">
      Senior Escalations <span class="erp-switch-count">{{ $seniorQueue }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-status="resolved" onclick="selectGrvTab('resolved')">
      Resolved <span class="erp-switch-count">{{ $resolvedGrievances }}</span>
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
          <input type="text" id="grvSearchInput" class="erp-search-input" placeholder="Search Ticket No, subject, category, raised by..." oninput="applyGrvFilters()">
        </div>

        <!-- Category Filter -->
        <div style="width: 220px;">
          <select id="grvCategoryFilter" class="erp-select" onchange="applyGrvFilters()">
            <option value="all">All Categories ({{ $allGrievancesCount ?? $totalGrievances }})</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}" {{ ($activeCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
        </div>

        @php
          $isDeptScopedHod = auth()->user()?->role === \App\Support\Roles::HOD
            && \App\Models\User::where('role', \App\Support\Roles::HOD)->where('is_active', true)->count() > 1;
          $scopeBadgeText = $isDeptScopedHod
            ? 'Department HOD View · ' . (auth()->user()?->department ?: 'Assigned') . ' Tickets'
            : (!empty($isGovernanceView) ? 'Handler View · All Employee Tickets' : 'Private View · Your Raised Tickets Only');
        @endphp
        <span class="visually-hidden">{{ $scopeBadgeText }}</span>
      </div>
    </div>

    <table class="erp-table" id="grvTable">
      <thead>
        <tr>
          <th class="ps-3" style="width: 17%;">TICKET NO.</th>
          <th style="width: 33%;">SUBJECT</th>
          <th style="width: 15%;">CATEGORY</th>
          <th style="width: 25%;">STATUS</th>
          <th class="text-end pe-3" style="width: 10%;">ACTION</th>
        </tr>
      </thead>
      <tbody id="grvTableBody">
        @forelse ($grievances as $g)
          @php
            $isResolved = $g->status === 'resolved';
            $badgeType = $isResolved ? 'erp-badge-success' : ($g->current_owner_role === 'cfo' || $g->current_owner_role === 'sg' ? 'erp-badge-danger' : 'erp-badge-warning');
            $badgeText = $isResolved ? 'Resolved' : ($ownerMeta[$g->current_owner_role]['label'] ?? 'Pending');
            $statusKey = $isResolved ? 'resolved' : 'active';
          @endphp
          <tr class="grv-row"
              data-ticket="{{ strtolower($g->ticket_number) }}"
              data-subject="{{ strtolower($g->subject) }}"
              data-category="{{ strtolower($g->subject_category) }}"
              data-raised="{{ strtolower($g->raisedBy?->name ?? '') }}"
              data-owner="{{ strtolower($g->current_owner_role ?? '') }}"
              data-status="{{ $statusKey }}">
            <!-- 1. Ticket No. -->
            <td class="ps-3">
              <a href="{{ route('grievance.show', $g) }}" class="erp-code-link">
                {{ $g->ticket_number }}
              </a>
            </td>

            <!-- 2. Subject -->
            <td>
              <span class="fw-semibold text-dark">
                {{ \Illuminate\Support\Str::limit($g->subject, 60) }}
              </span>
            </td>

            <!-- 3. Category -->
            <td>
              <span class="text-secondary">
                {{ $g->subject_category }}
              </span>
            </td>

            <!-- 4. Status -->
            <td>
              <div class="d-flex align-items-center gap-1.5 flex-nowrap">
                <span class="erp-badge {{ $badgeType }} text-nowrap">
                  {{ $badgeText }}
                </span>
                @if (($g->unread_updates_count ?? 0) > 0)
                  <span class="badge rounded-pill text-nowrap" style="font-size: 10.5px; padding: 2px 7px; background: #c8102e; color: #ffffff;">
                    {{ $g->unread_updates_count }} New {{ Str::plural('Update', $g->unread_updates_count) }}
                  </span>
                @endif
              </div>
            </td>

            <!-- 5. Action -->
            <td class="text-end pe-3">
              <div class="erp-row-actions">
                <a href="{{ route('grievance.show', $g) }}" class="erp-btn-action-view">
                  <i class="bi bi-eye"></i> View
                </a>
                <div class="dropdown">
                  <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                    <li>
                      <a class="dropdown-item" href="{{ route('grievance.show', $g) }}">
                        <i class="bi bi-eye text-secondary"></i> View Details
                      </a>
                    </li>
                    @if ($g->reimbursement_id && \App\Support\Roles::canAccessModuleForUser(auth()->user(), 'reimb'))
                      <li>
                        <a class="dropdown-item" href="{{ route('reimb.show', $g->reimbursement_id) }}">
                          <i class="bi bi-wallet2 text-secondary"></i> Linked Reimbursement
                        </a>
                      </li>
                    @endif
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        @empty
          <tr id="emptyTableRow">
            <td colspan="5" class="text-center py-5 text-muted">
              <i class="bi bi-chat-left-check fs-2 d-block mb-2 text-muted opacity-50"></i>
              No grievances raised yet.
            </td>
          </tr>
        @endforelse

        <tr id="grvFilterEmptyRow" class="d-none">
          <td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-chat-square-text fs-2 d-block mb-2 text-secondary opacity-50"></i>
            <div class="fw-bold text-dark fs-6 mb-1">No grievances found</div>
            <div class="small text-muted">No records match your selected filter or search keywords.</div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Client-side Pagination Footer -->
    <div class="erp-table-footer" id="grvPaginationBar" style="{{ $totalGrievances > 10 ? '' : 'display: none !important;' }}">
      <div class="text-muted small" id="grvPageInfo">Showing 1 to {{ min(10, $totalGrievances) }} of {{ $totalGrievances }} entries</div>
      <div class="d-flex align-items-center gap-1" id="grvPaginationControls">
        <!-- Rendered by JS -->
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Redressal' });

// Live Filter & Pagination (PR Page Alignment)
(function () {
  let currentPage = 1;
  const pageSize = 10;
  const rows = Array.from(document.querySelectorAll('#grvTableBody tr.grv-row'));
  const searchInput = document.getElementById('grvSearchInput');
  const statusFilter = document.getElementById('grvStatusFilter');
  const categoryFilter = document.getElementById('grvCategoryFilter');
  const entriesBadge = document.getElementById('grvEntriesBadge');
  const pageInfo = document.getElementById('grvPageInfo');
  const paginationControls = document.getElementById('grvPaginationControls');

  window.selectGrvTab = function(status) {
    document.querySelectorAll('#grvSwitchBar .erp-switch-tab').forEach(tab => {
      tab.classList.toggle('active', tab.dataset.status === status);
    });
    currentPage = 1;
    renderTable();
  };

  function getFilteredRows() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const cat = (categoryFilter?.value || 'all').toLowerCase();
    const activeTab = document.querySelector('#grvSwitchBar .erp-switch-tab.active')?.dataset.status || 'all';

    return rows.filter(row => {
      const ticket = row.dataset.ticket || '';
      const subject = row.dataset.subject || '';
      const category = row.dataset.category || '';
      const raised = row.dataset.raised || '';
      const status = row.dataset.status || '';
      const owner = row.dataset.owner || '';

      const matchQ = !q || ticket.includes(q) || subject.includes(q) || category.includes(q) || raised.includes(q);
      let matchSt = true;
      if (activeTab === 'senior') {
        matchSt = (owner === 'cfo' || owner === 'sg') && status !== 'resolved';
      } else if (activeTab !== 'all') {
        matchSt = (status === activeTab);
      }
      const matchCat = cat === 'all' || category === cat;

      return matchQ && matchSt && matchCat;
    });
  }

  function renderTable() {
    const filtered = getFilteredRows();
    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = (currentPage - 1) * pageSize;
    const endIdx = startIdx + pageSize;

    rows.forEach(r => r.style.display = 'none');

    filtered.slice(startIdx, endIdx).forEach(r => {
      r.style.display = '';
    });

    const filterEmptyRow = document.getElementById('grvFilterEmptyRow');
    if (filterEmptyRow) {
      if (total === 0) {
        filterEmptyRow.classList.remove('d-none');
      } else {
        filterEmptyRow.classList.add('d-none');
      }
    }

    if (entriesBadge) {
      entriesBadge.textContent = total + (total === 1 ? ' Entry' : ' Entries');
    }

    const counterEl = document.getElementById('grvCounterCount');
    if (counterEl) {
      counterEl.textContent = total;
    }

    if (pageInfo) {
      if (total === 0) {
        pageInfo.textContent = 'Showing 0 of 0 entries';
      } else {
        const displayStart = startIdx + 1;
        const displayEnd = Math.min(endIdx, total);
        pageInfo.textContent = `Showing ${displayStart} to ${displayEnd} of ${total} entries`;
      }
    }

    const paginationBar = document.getElementById('grvPaginationBar');
    if (paginationBar) {
      if (total > pageSize) {
        paginationBar.style.setProperty('display', 'flex', 'important');
      } else {
        paginationBar.style.setProperty('display', 'none', 'important');
      }
    }

    if (paginationControls) {
      paginationControls.replaceChildren();

      if (totalPages > 1) {
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pagination-btn';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i> Prev';
        prevBtn.addEventListener('click', () => {
          if (currentPage > 1) {
            currentPage--;
            renderTable();
          }
        });
        paginationControls.appendChild(prevBtn);

        for (let p = 1; p <= totalPages; p++) {
          const pagePill = document.createElement('button');
          pagePill.type = 'button';
          pagePill.className = 'pagination-page-pill' + (p === currentPage ? ' active' : '');
          pagePill.textContent = p;
          pagePill.addEventListener('click', () => {
            currentPage = p;
            renderTable();
          });
          paginationControls.appendChild(pagePill);
        }

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pagination-btn';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
        nextBtn.addEventListener('click', () => {
          if (currentPage < totalPages) {
            currentPage++;
            renderTable();
          }
        });
        paginationControls.appendChild(nextBtn);
      }
    }
  }

  window.applyGrvFilters = function () {
    currentPage = 1;
    renderTable();
  };

  renderTable();
})();
@endsection
