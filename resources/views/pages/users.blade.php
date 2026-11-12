@extends('layouts.app')

@section('title', 'User & Access Management')

@php
  $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['role'] ?? '') !== '' || ($filters['department'] ?? '') !== '';
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
      <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary fw-semibold px-3">
        <i class="bi bi-person-plus me-1"></i> Add Member
      </a>
    </div>
  </div>

  <!-- Compact KPI Strip -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-primary card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Total Members</span>
            <div class="stat-icon-box icon-primary" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-people"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">{{ $stats['total'] }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-accent card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Custom Access</span>
            <div class="stat-icon-box icon-accent" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-sliders"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">{{ $stats['custom_access'] }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-success card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Departments</span>
            <div class="stat-icon-box icon-success" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-building"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">{{ $stats['departments'] }}</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="card stat-card stat-info card-lift h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.72rem;">Workflow Roles</span>
            <div class="stat-icon-box icon-info" style="width: 28px; height: 28px; font-size: 0.85rem; border-radius: 7px;">
              <i class="bi bi-shield-lock"></i>
            </div>
          </div>
          <div class="stat-value mb-0" style="font-size: 1.35rem; font-weight: 700;">{{ $stats['roles'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Standalone Filter Bar (Matching PO/TRF/Audit) -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2 px-3">
      <form method="GET" action="{{ route('users') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group erp-filter-ctrl">
            <span class="input-group-text bg-white border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="text"
              name="q"
              value="{{ $filters['q'] }}"
              class="form-control bg-white border-start-0 ps-0"
              placeholder="Search name, email, designation…"
              aria-label="Search members">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select name="department" class="form-select erp-filter-ctrl bg-white" onchange="this.form.submit()" aria-label="Filter by department">
            <option value="">All Departments</option>
            @foreach ($departments as $dept)
              <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select name="role" class="form-select erp-filter-ctrl bg-white" onchange="this.form.submit()" aria-label="Filter by role">
            <option value="">All Roles</option>
            @foreach ($roles as $slug => $label)
              <option value="{{ $slug }}" @selected($filters['role'] === $slug)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex align-items-center gap-2 justify-content-md-end">
          <button type="submit" class="btn btn-primary erp-filter-ctrl fw-semibold px-3 flex-grow-1">Apply</button>
          @if ($hasFilters)
            <a href="{{ route('users') }}" class="btn btn-outline-secondary erp-filter-ctrl px-2" title="Reset Filters" aria-label="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Minimal Tight Directory Card -->
  <div class="card card-panel shadow-sm border-0 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <h2 class="h6 mb-0 fw-bold text-dark">Member Roster</h2>
        <span class="badge bg-light text-secondary border" style="font-size:11px;">{{ $users->count() }} {{ $users->count() === 1 ? 'member' : 'members' }}</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light text-uppercase" style="font-size: 11px; letter-spacing: 0.4px;">
          <tr>
            <th class="ps-4 py-2">Member</th>
            <th class="py-2">Designation</th>
            <th class="py-2" style="width: 150px;">Department</th>
            <th class="py-2">Module Access</th>
            <th class="pe-4 py-2 text-end" style="width: 96px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $member)
            @php
              $initials = collect(explode(' ', $member->name))->filter()->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('') ?: 'US';
              $effectiveModules = array_values(array_diff(\App\Support\Roles::sidebarModulesForUser($member), ['dashboard']));
              $isCustom = is_array($member->allowed_modules);
              $resolvedNames = array_map(fn ($id) => $moduleLabels[$id] ?? \Illuminate\Support\Str::headline($id), $effectiveModules);
              $previewList = implode(', ', array_slice($resolvedNames, 0, 3));
              $extraCount = max(0, count($resolvedNames) - 3);
            @endphp
            <tr>
              <td class="ps-4 py-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 11px; background: #EFF6FF; color: #1D4ED8; border: 1px solid #DBEAFE;">
                    {{ $initials }}
                  </div>
                  <div>
                    <div class="fw-semibold text-dark" style="font-size: 12.5px; line-height: 1.3;">{{ $member->name }}</div>
                    <div class="text-muted" style="font-size: 11.5px; line-height: 1.2;">{{ $member->email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-2">
                <div class="fw-medium text-dark" style="font-size: 12.5px;">{{ $member->designationLabel() }}</div>
                @if ($member->designation && $member->designation !== $member->roleLabel())
                  <div class="text-muted" style="font-size: 11px;">Role: {{ $member->roleLabel() }}</div>
                @endif
              </td>
              <td class="py-2 text-nowrap">
                <span class="badge fw-medium px-2 py-1" style="font-size: 11px; background: #F8FAFC; color: #334155; border: 1px solid #E2E8F0;">
                  {{ $member->department ?: 'General' }}
                </span>
              </td>
              <td class="py-2">
                <div class="d-flex align-items-center gap-2 flex-wrap" title="{{ implode(', ', $resolvedNames) }}">
                  <span class="badge fw-semibold px-2 py-1" style="font-size: 11px; {{ $isCustom ? 'background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE;' : 'background:#F1F5F9; color:#475569; border:1px solid #E2E8F0;' }}">
                    {{ $isCustom ? 'Custom' : 'Default' }} · {{ count($effectiveModules) }}
                  </span>
                  <span class="text-muted" style="font-size: 12px;">
                    @if (count($resolvedNames) === 0)
                      Dashboard only
                    @elseif ($extraCount > 0)
                      {{ $previewList }} <span class="text-secondary fw-medium">+{{ $extraCount }}</span>
                    @else
                      {{ $previewList }}
                    @endif
                  </span>
                </div>
              </td>
              <td class="pe-4 py-2 text-end text-nowrap">
                <a href="{{ route('users.edit', $member) }}" class="btn btn-sm btn-warning text-dark px-2 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 600;" title="Edit Member Access">
                  <i class="bi bi-pencil-square"></i>
                  <span>Edit</span>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                No team members matched your current filters.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'users', crumb: 'System', title: 'User & Access Management' });
@endsection
