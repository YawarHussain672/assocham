@extends('layouts.app')

@section('title', $evaluation->vendor->company_name)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Travel Vendor Evaluation
  </a>

  <div class="detail-header-card">
    <div class="detail-avatar">★</div>
    <div>
      <div class="detail-name">{{ $evaluation->vendor->company_name }}</div>
      <div class="detail-meta">{{ $evaluation->trf->trf_number }} · {{ $evaluation->trf->employee->name }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:#E6F3EC; color:#2E7D4F;">{{ $evaluation->averageRating() }} / 5</span>
    </div>
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Scores</div>
    <div class="row row-cols-2 row-cols-md-5 g-3">
      <div><div class="field-label">Cost</div><div class="field-value">{{ $evaluation->cost_score }} / 5</div></div>
      <div><div class="field-label">Time</div><div class="field-value">{{ $evaluation->time_score }} / 5</div></div>
      <div><div class="field-label">Quality</div><div class="field-value">{{ $evaluation->quality_score }} / 5</div></div>
      <div><div class="field-label">Responsiveness</div><div class="field-value">{{ $evaluation->responsiveness_score }} / 5</div></div>
      <div><div class="field-label">Accuracy</div><div class="field-value">{{ $evaluation->accuracy_score }} / 5</div></div>
    </div>
    @if ($evaluation->comments)
      <div class="mt-3"><div class="field-label">Comments</div><div class="field-value">{{ $evaluation->comments }}</div></div>
    @endif
    <div class="mt-3"><div class="field-label">Rated By</div><div class="field-value">{{ $evaluation->rater?->name ?? '—' }}</div></div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Evaluation Detail' });
@endsection
