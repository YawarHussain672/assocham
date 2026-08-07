@extends('layouts.app')

@section('title', $log->ref)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Flight &amp; Boarding Logistics
  </a>

  <div class="detail-header-card">
    <div class="detail-avatar">{{ $log->type === 'Flight ticket' ? '✈' : '🧾' }}</div>
    <div>
      <div class="detail-name">{{ $log->ref }} — {{ $log->type }}</div>
      <div class="detail-meta">{{ $log->trf->trf_number }} · {{ $log->trf->employee->name }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:#E6F0FB; color:#1E5FA8;">Ready to Settle</span>
    </div>
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Transaction Details</div>
    <div class="row row-cols-2 g-3">
      <div><div class="field-label">Linked TRF</div><div class="field-value">{{ $log->trf->trf_number }}</div></div>
      <div><div class="field-label">Employee</div><div class="field-value">{{ $log->trf->employee->name }}</div></div>
      <div><div class="field-label">Type</div><div class="field-value">{{ $log->type }}</div></div>
      <div><div class="field-label">Vendor</div><div class="field-value">{{ $log->vendor->company_name ?? '—' }}</div></div>
      <div><div class="field-label">Cost</div><div class="field-value">₹{{ number_format($log->cost, 2) }}</div></div>
      <div><div class="field-label">Uploaded By</div><div class="field-value">{{ $log->uploader?->name ?? '—' }}</div></div>
    </div>

    <div class="mt-3">
      <div class="field-label">Proof</div>
      @if ($log->file_path)
        <a href="{{ asset('storage/'.$log->file_path) }}" target="_blank" class="field-value" style="color:var(--maroon);">View uploaded file</a>
      @else
        <div class="field-value">No file uploaded.</div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Detail' });
@endsection
