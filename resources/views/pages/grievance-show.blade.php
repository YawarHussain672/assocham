@extends('layouts.app')

@section('title', $grievance->ticket_number)

@php
  $ownerMeta = [
    'hod'        => ['label' => 'With HOD', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'admin_head' => ['label' => 'With Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'cfo'        => ['label' => 'With CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'sg'         => ['label' => 'With SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
  ];
  $meta = $grievance->status === 'resolved' ? ['label' => 'Resolved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'] : ($ownerMeta[$grievance->current_owner_role] ?? $ownerMeta['hod']);
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grievance') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Grievance Redressal
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">GRV</div>
    <div>
      <div class="detail-name">{{ $grievance->ticket_number }} — {{ $grievance->subject }}</div>
      <div class="detail-meta">{{ $grievance->subject_category }} · Raised by {{ $grievance->raisedBy->name }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Escalation Path</div>
    <div class="d-flex gap-2 flex-wrap mb-3">
      @foreach (\App\Models\Grievance::ESCALATION_ORDER as $role)
        @php
          $isPast = array_search($role, \App\Models\Grievance::ESCALATION_ORDER) < array_search($grievance->current_owner_role, \App\Models\Grievance::ESCALATION_ORDER);
          $isCurrent = $role === $grievance->current_owner_role && $grievance->status !== 'resolved';
        @endphp
        <span class="badge-soft" style="background:{{ $isCurrent ? '#FBF0DC' : ($isPast || $grievance->status === 'resolved' ? '#E6F3EC' : '#EEF0F3') }}; color:{{ $isCurrent ? '#8A5A12' : ($isPast || $grievance->status === 'resolved' ? '#2E7D4F' : '#64748B') }};">
          {{ \App\Support\Roles::label($role) }}
        </span>
      @endforeach
    </div>

    @if ($grievance->description)
      <div class="field-label">Description</div>
      <div class="field-value mb-3">{{ $grievance->description }}</div>
    @endif

    @if ($grievance->status === 'resolved')
      <div class="note-bar">
        <div class="note-dot"></div>
        <div class="note-text">Resolved by {{ $grievance->resolvedBy?->name ?? '—' }} on {{ $grievance->resolved_at?->format('d M Y, H:i') }}.</div>
      </div>
    @else
      <div class="d-flex gap-2 mt-3">
        @if ($canEscalate)
          <form method="POST" action="{{ route('grievance.escalate', $grievance) }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Escalate to {{ \App\Support\Roles::label($grievance->nextOwnerRole() ?? '') }}</button>
          </form>
        @endif
        @if ($canResolve)
          <form method="POST" action="{{ route('grievance.resolve', $grievance) }}">
            @csrf
            <button type="submit" class="btn-brand">Mark Resolved</button>
          </form>
        @endif
        @if (! $canEscalate && ! $canResolve)
          <div class="note-bar">
            <div class="note-dot"></div>
            <div class="note-text">Currently with {{ \App\Support\Roles::label($grievance->current_owner_role) }} — nothing for you to do here right now.</div>
          </div>
        @endif
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Grievance Detail' });
@endsection
