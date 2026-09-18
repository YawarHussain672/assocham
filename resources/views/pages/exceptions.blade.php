@extends('layouts.app')

@section('title', 'Exception Handling')

@php
  $typeMeta = [
    'single_quotation' => 'Single Quotation',
    'budget_exceed' => 'Budget Exceed',
    'po_force_close' => 'PO Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Check',
  ];
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];

  $totalExc = $exceptions->count();
  $pendingExc = $exceptions->where('status', 'pending')->count();
  $budgetOverrides = $exceptions->where('exception_type', 'budget_exceed')->count();
  $approvedExc = $exceptions->where('status', 'approved')->count();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row mb-3">
    <div>
      <h1 class="h4 mb-1" style="font-weight:600;">Governance Exceptions &amp; Overrides</h1>
      <div class="page-sub" style="margin-top:0;">Sole-quotation urgencies, PO force-closures, GRN reversals, and CFO budget exceed authorizations. Every request is recorded with full justification.</div>
    </div>
    @if ($canCreate)
      <a href="{{ route('exceptions.create') }}" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:9px 20px;">
        <i class="bi bi-shield-plus"></i>
        <span>File Exception</span>
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Top Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-blue p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Filed</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $totalExc }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Exception cases logged</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(30,95,168,0.12); color:#1E5FA8;">
            <i class="bi bi-shield-exclamation fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-amber p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Pending Decisions</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $pendingExc }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Awaiting CFO/SG review</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(217,119,6,0.12); color:#D97706;">
            <i class="bi bi-hourglass-split fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-purple p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Budget Overrides</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $budgetOverrides }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">CFO fund re-allocations</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(124,58,237,0.12); color:#7C3AED;">
            <i class="bi bi-cash-stack fs-5"></i>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card-panel stat-card-green p-3 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Approved &amp; Executed</div>
            <div class="mt-2" style="font-size:22px; font-weight:600; color:#1e293b;">{{ $approvedExc }}</div>
            <div class="text-muted mt-1" style="font-size:11.5px;">Clearing overrides</div>
          </div>
          <span class="stat-icon-circle" style="background:rgba(46,125,79,0.12); color:#2E7D4F;">
            <i class="bi bi-check2-all fs-5"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 overflow-hidden">
    <div class="table-toolbar px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="table-toolbar-title d-flex align-items-center gap-2">
        <i class="bi bi-shield-lock text-primary"></i>
        <span>Exception Governance Registry</span>
      </div>
      <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">{{ $totalExc }} Records</span>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Exception Type</div>
        <div>Justification Narrative</div>
        <div>Requested By</div>
        <div>Approver Queue</div>
        <div>Status</div>
      </div>

      @forelse ($exceptions as $exception)
        @php $meta = $statusMeta[$exception->status] ?? $statusMeta['pending']; @endphp
        <a href="{{ route('exceptions.show', $exception) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id d-flex align-items-center gap-1">
            <i class="bi bi-shield text-muted"></i>
            <span>{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }}</span>
          </div>
          <div class="module-row-c1">{{ \Illuminate\Support\Str::limit($exception->justification, 50) }}</div>
          <div class="module-row-c2">{{ $exception->requester->name }}</div>
          <div class="module-row-c3">{{ \App\Support\Roles::label($exception->approver_queue) }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600;">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No exception cases filed yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Handling' });
@endsection
