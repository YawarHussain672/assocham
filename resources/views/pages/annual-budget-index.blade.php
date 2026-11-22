@extends('layouts.app')

@section('title', 'Annual Budget Proposals')

@section('content')
<div class="view-wrap" id="pageContent">

  <style>
    .stat-card {
      cursor: default !important;
      user-select: none;
      outline: none !important;
    }
    .budget-table-card {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #ffffff;
      overflow: hidden;
    }
    .budget-table {
      width: 100% !important;
      margin-bottom: 0;
      border-collapse: collapse;
    }
    .budget-table thead th {
      font-size: 11.5px !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #475569 !important;
      background-color: #f8fafc !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 11px 14px !important;
      vertical-align: middle !important;
      white-space: nowrap;
    }
    .budget-table tbody td {
      padding: 12px 14px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #f1f5f9 !important;
      font-size: 13px !important;
      background-color: #ffffff;
      line-height: 1.35;
    }
    .budget-table tbody tr:hover td {
      background-color: #f8faff;
    }
    .budget-table tbody tr:last-child td {
      border-bottom: none !important;
    }
  </style>

  {{-- Page Header & Action Bar --}}
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-0">Annual Budget Proposals</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      @if ($canSubmit)
        <a href="{{ route('annual-budget.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm">
          <i class="bi bi-plus-circle"></i>
          <span>Submit Annual Budget</span>
        </a>
      @endif
      @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'checks-balances'))
        <a href="{{ route('checks-balances') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-shield-check"></i>
          <span>Budget Master</span>
        </a>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-octagon-fill fs-5"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @php
    $curStatus = $filters['status'] ?? '';
  @endphp

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar">
    <a href="{{ route('annual-budget', array_filter(array_merge(request()->except('status', 'page'), []))) }}" class="erp-switch-tab {{ empty($curStatus) ? 'active' : '' }}">
      All Proposals
    </a>
    <a href="{{ route('annual-budget', array_merge(request()->except('status', 'page'), ['status' => 'pending_cfo'])) }}" class="erp-switch-tab {{ $curStatus === 'pending_cfo' ? 'active' : '' }}">
      Pending CFO
    </a>
    <a href="{{ route('annual-budget', array_merge(request()->except('status', 'page'), ['status' => 'pending_sg'])) }}" class="erp-switch-tab {{ $curStatus === 'pending_sg' ? 'active' : '' }}">
      Pending SG
    </a>
    <a href="{{ route('annual-budget', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}" class="erp-switch-tab {{ $curStatus === 'approved' ? 'active' : '' }}">
      Approved
    </a>
    <a href="{{ route('annual-budget', array_merge(request()->except('status', 'page'), ['status' => 'on_hold'])) }}" class="erp-switch-tab {{ $curStatus === 'on_hold' ? 'active' : '' }}">
      On Hold
    </a>
    <a href="{{ route('annual-budget', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}" class="erp-switch-tab {{ $curStatus === 'rejected' ? 'active' : '' }}">
      Rejected
    </a>
  </div>

  {{-- Unified ERP Table Container --}}
  <div class="erp-table-container">
    {{-- Integrated Toolbar --}}
    <div class="erp-table-toolbar">
      <form method="GET" action="{{ route('annual-budget') }}" class="d-flex flex-wrap align-items-center gap-2 w-100 mb-0">
        <div class="erp-search-wrap flex-grow-1" style="min-width: 220px;">
          <i class="bi bi-search erp-search-icon"></i>
          <input type="text" name="q" class="erp-search-input" placeholder="Search proposal #, department, remarks..." value="{{ $filters['q'] }}">
        </div>
        <div style="width: 140px;">
          <select name="fy" class="erp-select" onchange="this.form.submit()">
            <option value="">All Fiscal Years</option>
            @foreach ($fiscalYears as $fy)
              <option value="{{ $fy }}" @selected($filters['fy'] === $fy)>{{ $fy }}</option>
            @endforeach
          </select>
        </div>
        <div style="width: 165px;">
          <select name="dept" class="erp-select" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach ($departments as $dept)
              <option value="{{ $dept }}" @selected($filters['dept'] === $dept)>{{ $dept }}</option>
            @endforeach
          </select>
        </div>
        @if (!empty($filters['status']))
          <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif
        <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">Filter</button>
        @if ($filters['q'] || $filters['fy'] || $filters['dept'] || $filters['status'])
          <a href="{{ route('annual-budget') }}" class="erp-btn-reset" title="Reset Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        @endif
      </form>
    </div>

    {{-- Proposals Table --}}
    <div class="table-responsive border-0 mb-0" style="overflow-x: auto;">
      <table class="table erp-table align-middle w-100" id="budgetTable">
        <thead>
          <tr>
            <th class="ps-3" style="width: 14%;">Proposal #</th>
            <th style="width: 16%;">Department</th>
            <th style="width: 10%;">Fiscal Year</th>
            <th style="width: 16%;">Submitted By</th>
            <th style="width: 13%;" class="text-end">Proposed Limit</th>
            <th style="width: 13%;" class="text-end">Approved Limit</th>
            <th style="width: 10%;" class="text-center">Status</th>
            <th style="width: 8%;" class="text-end pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($budgets as $b)
            <tr>
              <td class="ps-3">
                <a href="{{ route('annual-budget.show', $b) }}" class="erp-code-link">
                  {{ $b->budget_number }}
                </a>
              </td>
              <td>
                <span class="fw-semibold text-dark">{{ $b->department }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $b->fiscal_year }}</span>
              </td>
              <td>
                <div class="fw-medium text-dark" style="font-size: 13px;">{{ $b->submitter?->name ?? '—' }}</div>
                <div class="text-muted" style="font-size: 11px;">{{ $b->created_at->format('d M Y') }}</div>
              </td>
              <td class="text-end fw-semibold font-monospace text-dark" style="font-size: 12.5px;">
                ₹{{ number_format((float) $b->proposed_amount, 2) }}
              </td>
              <td class="text-end fw-bold font-monospace" style="font-size: 12.5px;">
                @if ($b->isApproved())
                  <span class="text-success">₹{{ number_format((float) ($b->approved_amount ?: $b->proposed_amount), 2) }}</span>
                @elseif ($b->approved_amount)
                  <span class="text-info">₹{{ number_format((float) $b->approved_amount, 2) }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="text-center">
                <span class="badge {{ $b->statusBadgeClass() }} px-2 py-1">
                  {{ $b->statusLabel() }}
                </span>
              </td>
              <td class="text-end pe-3">
                <div class="erp-row-actions">
                  <a href="{{ route('annual-budget.show', $b) }}" class="erp-btn-action-view">
                    <i class="bi bi-eye"></i> View
                  </a>
                  <div class="dropdown">
                    <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                      <li>
                        <a class="dropdown-item" href="{{ route('annual-budget.show', $b) }}">
                          <i class="bi bi-eye text-secondary"></i> View Proposal
                        </a>
                      </li>
                      @if ($b->canBeEditedBy(auth()->user()))
                        <li>
                          <a class="dropdown-item" href="{{ route('annual-budget.edit', $b) }}">
                            <i class="bi bi-pencil text-secondary"></i> Edit Proposal
                          </a>
                        </li>
                      @endif
                    </ul>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-wallet2 d-block fs-3 mb-2 opacity-50"></i>
                No annual budget proposals matched your criteria.
                @if ($canSubmit)
                  <div class="mt-2">
                    <a href="{{ route('annual-budget.create') }}" class="btn btn-sm btn-primary fw-semibold">
                      Submit First Annual Budget
                    </a>
                  </div>
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($budgets->hasPages())
      <div class="erp-table-footer">
        {{ $budgets->links() }}
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'annual-budget', crumb: 'Governance & Controls', title: 'Annual Budget Proposals' });
@endsection
