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
    'pending'  => ['label' => 'Pending', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Urgent bypass, single-quotation exceptions, PO force-close, GRN reversal and budget overrides — every exception is logged with justification and requires sign-off from the correct governance queue.</div>
    @if ($canCreate)
      <a href="{{ route('exceptions.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        File Exception
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Exception Handling Registry</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Type</div>
        <div>Justification</div>
        <div>Requested By</div>
        <div>Approver Queue</div>
        <div>Status</div>
      </div>

      @forelse ($exceptions as $exception)
        @php $meta = $statusMeta[$exception->status] ?? $statusMeta['pending']; @endphp
        <a href="{{ route('exceptions.show', $exception) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }}</div>
          <div class="module-row-c1">{{ \Illuminate\Support\Str::limit($exception->justification, 50) }}</div>
          <div class="module-row-c2">{{ $exception->requester->name }}</div>
          <div class="module-row-c3">{{ \App\Support\Roles::label($exception->approver_queue) }}</div>
          <div><span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No exceptions filed yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Handling' });
@endsection
