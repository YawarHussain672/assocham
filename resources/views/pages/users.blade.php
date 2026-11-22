@extends('layouts.app')

@section('title', 'User & Access Management')

@php
  $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['role'] ?? '') !== '' || ($filters['department'] ?? '') !== '' || ($filters['status'] ?? '') !== '';
  $moduleLabels = [];
  foreach (\App\Support\Roles::moduleCatalog() as $groupItems) {
      foreach ($groupItems as $item) {
          $moduleLabels[$item['id']] = $item['label'];
      }
  }
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">User &amp; Access Management</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary fw-semibold px-3 shadow-sm">
        <i class="bi bi-person-plus me-1"></i> Add Member
      </a>
    </div>
  </div>

  @php
    $curUserStatus = $filters['status'] ?? '';
  @endphp

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar">
    <a href="{{ route('users', array_filter(array_merge(request()->except('status', 'page'), []))) }}" class="erp-switch-tab {{ empty($curUserStatus) ? 'active' : '' }}">
      All Members <span class="erp-switch-count">{{ $stats['total'] }}</span>
    </a>
    <a href="{{ route('users', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}" class="erp-switch-tab {{ $curUserStatus === 'active' ? 'active' : '' }}">
      Active <span class="erp-switch-count">{{ $stats['active'] }}</span>
    </a>
    <a href="{{ route('users', array_merge(request()->except('status', 'page'), ['status' => 'deactivated'])) }}" class="erp-switch-tab {{ $curUserStatus === 'deactivated' ? 'active' : '' }}">
      Deactivated <span class="erp-switch-count">{{ $stats['deactivated'] }}</span>
    </a>
    <a href="{{ route('users', array_merge(request()->except('status', 'page'), ['status' => 'deleted'])) }}" class="erp-switch-tab {{ $curUserStatus === 'deleted' ? 'active' : '' }}">
      Archived <span class="erp-switch-count">{{ $stats['deleted'] }}</span>
    </a>
  </div>

  <!-- Unified ERP Table Container -->
  <div class="erp-table-container">
    <!-- Integrated Toolbar -->
    <div class="erp-table-toolbar">
      <form method="GET" action="{{ route('users') }}" class="d-flex flex-wrap align-items-center gap-2 mb-0 w-100" id="usersFilterForm">
        <span class="d-none">Member Roster</span>
        <div class="erp-search-wrap flex-grow-1" style="min-width: 220px; max-width: 340px;">
          <i class="bi bi-search erp-search-icon"></i>
          <input
            type="text"
            name="q"
            id="userSearchInput"
            value="{{ $filters['q'] }}"
            class="erp-search-input"
            placeholder="Search name, email, designation…"
            aria-label="Search members"
            oninput="applyUserFiltersLive()">
        </div>

        <select name="department" id="userDeptFilter" class="erp-select" onchange="this.form.submit()" aria-label="Filter by department" style="min-width: 160px; max-width: 200px;">
          <option value="">All Departments</option>
          @foreach ($departments as $dept)
            <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
          @endforeach
        </select>

        <select name="role" id="userRoleFilter" class="erp-select" onchange="this.form.submit()" aria-label="Filter by role" style="min-width: 160px; max-width: 210px;">
          <option value="">All Roles</option>
          @foreach ($roles as $slug => $label)
            <option value="{{ $slug }}" @selected($filters['role'] === $slug)>{{ $label }}</option>
          @endforeach
        </select>

        <select name="status" id="userStatusFilter" class="d-none" aria-hidden="true" tabindex="-1">
          <option value="">All Status</option>
          <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
          <option value="deactivated" @selected(($filters['status'] ?? '') === 'deactivated')>Deactivated</option>
          <option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Deleted / Archived</option>
        </select>

        <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold text-nowrap">Apply</button>
        @if ($hasFilters)
          <a href="{{ route('users') }}" class="erp-btn-reset" title="Reset Filters" aria-label="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        @endif
      </form>
    </div>

    <table class="table erp-table" id="usersTable">
      <thead>
        <tr>
          <th class="ps-3" style="width: 25%;">MEMBER</th>
          <th style="width: 25%;">DESIGNATION</th>
          <th style="width: 25%;">DEPARTMENT</th>
          <th style="width: 13%;">STATUS</th>
          <th class="text-end pe-3" style="width: 12%;">ACTION</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $member)
          @php
            $isActive = $member->isActive();
          @endphp
          <tr class="user-table-row"
              data-name="{{ strtolower($member->name) }}"
              data-email="{{ strtolower($member->email) }}"
              data-desig="{{ strtolower($member->designationLabel()) }}"
              data-dept="{{ strtolower($member->department ?: 'general') }}">
            <!-- 1. Member -->
            <td class="ps-3">
              <a href="{{ route('users.edit', $member) }}" class="fw-semibold text-dark text-decoration-none">
                {{ $member->name }}
              </a>
              <div class="text-muted small" style="font-size: 11.5px;">{{ $member->email }}</div>
            </td>

            <!-- 2. Designation -->
            <td>
              <span class="text-secondary">
                {{ $member->designationLabel() }}
              </span>
            </td>

            <!-- 3. Department -->
            <td>
              <span class="text-secondary">
                {{ $member->department ?: 'General' }}
              </span>
            </td>

            <!-- 4. Status -->
            <td>
              @if ($member->trashed())
                <span class="erp-badge-neutral">Archived</span>
              @elseif ($isActive)
                <span class="erp-badge-success">Active</span>
              @else
                <span class="erp-badge-danger">Deactivated</span>
              @endif
            </td>

            <!-- 5. Action -->
            <td class="text-end pe-3">
              @if ($member->trashed())
                <span class="badge bg-light text-muted border px-2 py-1" title="Historical records retained">Archived</span>
              @else
                <div class="erp-row-actions">
                  <a href="{{ route('users.edit', $member) }}" class="erp-btn-action-edit btn-warning text-dark" title="Edit Member Access">
                    <i class="bi bi-pencil"></i> Edit
                  </a>
                  @if ($member->id !== auth()->id())
                    <div class="dropdown">
                      <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                        <li>
                          <form method="POST" action="{{ route('users.toggle-status', $member) }}" onsubmit="return confirm({{ json_encode($member->isActive() ? "Deactivate {$member->name}? They will be logged out immediately." : "Reactivate {$member->name}'s portal account?") }});" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item {{ $member->isActive() ? 'text-warning' : 'text-success' }}">
                              <i class="bi {{ $member->isActive() ? 'bi-person-slash text-warning' : 'bi-person-check text-success' }}"></i>
                              {{ $member->isActive() ? 'Deactivate Account' : 'Reactivate Account' }}
                            </button>
                          </form>
                        </li>
                        <li>
                          <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm({{ json_encode("Delete {$member->name}? If operational records exist, account will be safely archived.") }});" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="bi bi-trash text-danger"></i> Delete Account
                            </button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  @endif
                </div>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              No team members matched your current filters.
            </td>
          </tr>
        @endforelse
        <tr id="emptyUserFilterRow" class="d-none">
          <td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-search fs-3 d-block mb-1 opacity-50"></i>
            No team members match your search criteria.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
function applyUserFiltersLive() {
  const query = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('.user-table-row');
  let matched = 0;

  rows.forEach(row => {
    const name = row.getAttribute('data-name') || '';
    const email = row.getAttribute('data-email') || '';
    const desig = row.getAttribute('data-desig') || '';
    const dept = row.getAttribute('data-dept') || '';

    const matchesQuery = !query || name.includes(query) || email.includes(query) || desig.includes(query) || dept.includes(query);

    if (matchesQuery) {
      row.style.display = '';
      matched++;
    } else {
      row.style.display = 'none';
    }
  });

  const emptyRow = document.getElementById('emptyUserFilterRow');
  if (emptyRow) {
    emptyRow.classList.toggle('d-none', matched > 0 || rows.length === 0);
  }
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'users', crumb: 'System', title: 'User & Access Management' });
@endsection
