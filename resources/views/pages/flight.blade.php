@extends('layouts.app')

@section('title', 'Flight & Boarding Logistics')

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Once travel is approved, boarding passes and mail transactions are uploaded as proof of booking, with every cost mapped one-to-one against its TRF number for downstream reconciliation.</div>
    @if ($canCreate)
      <a href="{{ route('flight.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        Upload Transaction
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Flight &amp; Boarding Logistics</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Ref</div>
        <div>Linked TRF</div>
        <div>Type</div>
        <div>Vendor</div>
        <div>Cost</div>
      </div>

      @forelse ($logs as $log)
        <a href="{{ route('flight.show', $log) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $log->ref }}</div>
          <div class="module-row-c1">{{ $log->trf->trf_number }} · {{ $log->trf->employee->name }}</div>
          <div class="module-row-c2">{{ $log->type }}</div>
          <div class="module-row-c2">{{ $log->vendor->company_name ?? '—' }}</div>
          <div class="module-row-c3">₹{{ number_format($log->cost, 2) }} {{ $log->file_path ? '📎' : '' }}</div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No flight & boarding transactions logged yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Logistics' });
@endsection
