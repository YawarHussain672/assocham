@extends('layouts.app')

@section('title', 'Exception #'.$exception->id)

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
  $meta = $statusMeta[$exception->status] ?? $statusMeta['pending'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('exceptions') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Exception Handling
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">EXC</div>
    <div>
      <div class="detail-name">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }}</div>
      <div class="detail-meta">Filed by {{ $exception->requester->name }} · Reference: {{ $exception->reference_type ?? '—' }}{{ $exception->reference_id ? ' #'.$exception->reference_id : '' }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="field-label">Justification</div>
    <div class="field-value mb-3">{{ $exception->justification }}</div>

    <div class="field-label">Approver Queue</div>
    <div class="field-value mb-3">{{ \App\Support\Roles::label($exception->approver_queue) }}</div>

    @if ($exception->status !== 'pending')
      <div class="note-bar">
        <div class="note-dot"></div>
        <div class="note-text">{{ ucfirst($exception->status) }} by {{ $exception->decidedBy?->name ?? '—' }} on {{ $exception->decided_at?->format('d M Y, H:i') }}.</div>
      </div>
    @elseif ($canDecide)
      <div class="d-flex gap-2 mt-3">
        <form method="POST" action="{{ route('exceptions.approve', $exception) }}">
          @csrf
          <button type="submit" class="btn-brand">Approve</button>
        </form>
        <form method="POST" action="{{ route('exceptions.reject', $exception) }}">
          @csrf
          <button type="submit" class="btn btn-outline-danger">Reject</button>
        </form>
      </div>
    @else
      <div class="note-bar">
        <div class="note-dot"></div>
        <div class="note-text">Awaiting {{ \App\Support\Roles::label($exception->approver_queue) }} — nothing for you to do here right now.</div>
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Detail' });
@endsection
