@extends('layouts.app')

@section('title', 'Travel Vendor Evaluation')

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Every travel vendor is rated per completed TRF across cost, timeliness, quality, responsiveness and accuracy. Ratings accumulate into a historical performance view that informs which vendors continue to receive bookings.</div>
    @if ($canCreate)
      <a href="{{ route('traveleval.create') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        Rate a Vendor
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Travel Partner Performance Ranking</div>
    @forelse ($leaderboard as $row)
      <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
        <div>
          <div class="field-value">{{ $row['vendor']->company_name }}</div>
          <div class="field-label" style="text-transform:none; letter-spacing:0;">{{ $row['count'] }} evaluation{{ $row['count'] === 1 ? '' : 's' }}</div>
        </div>
        <span class="badge-soft" style="background:#E6F3EC; color:#2E7D4F;">{{ $row['average'] }} / 5</span>
      </div>
    @empty
      <div class="text-muted">No ratings recorded yet.</div>
    @endforelse
  </div>

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Evaluation Registry</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="module-table-head">
        <div>Vendor</div>
        <div>Linked TRF</div>
        <div>Avg. Rating</div>
        <div>Rated By</div>
        <div>Comments</div>
      </div>

      @forelse ($evaluations as $eval)
        <a href="{{ route('traveleval.show', $eval) }}" class="module-row" style="display:grid; text-decoration:none; cursor:pointer;">
          <div class="module-row-id">{{ $eval->vendor->company_name }}</div>
          <div class="module-row-c1">{{ $eval->trf->trf_number }} · {{ $eval->trf->employee->name }}</div>
          <div class="module-row-c2">{{ $eval->averageRating() }} / 5</div>
          <div class="module-row-c3">{{ $eval->rater?->name ?? '—' }}</div>
          <div class="module-row-c3">{{ \Illuminate\Support\Str::limit($eval->comments, 40) ?: '—' }}</div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No vendor evaluations logged yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Travel Vendor Evaluation' });
@endsection
