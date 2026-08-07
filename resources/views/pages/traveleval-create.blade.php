@extends('layouts.app')

@section('title', 'Rate a Travel Vendor')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Travel Vendor Evaluation
  </a>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-panel p-4">
    <h2 class="h5 mb-1">Rate a Travel Vendor</h2>
    <p class="page-sub" style="margin-top:0;">Only vendors with a logged Flight &amp; Boarding transaction against a completed (approved, travel ended) TRF are eligible — and only once per vendor per TRF.</p>

    @if ($rows->isEmpty())
      <div class="note-bar mt-3">
        <div class="note-dot"></div>
        <div class="note-text">No completed trips with an unrated vendor yet — a trip needs an approved TRF, travel end date in the past, and a logged Flight &amp; Boarding transaction.</div>
      </div>
    @else
      <form method="POST" action="{{ route('traveleval.store') }}" class="mt-3">
        @csrf
        <input type="hidden" name="trf_id" id="trfIdInput" value="{{ old('trf_id') }}">
        <input type="hidden" name="vendor_id" id="vendorIdInput" value="{{ old('vendor_id') }}">

        <label class="form-label">Trip / Vendor *</label>
        <select id="comboSelect" class="form-select" required>
          <option value="">Select trip and vendor…</option>
          @foreach ($rows as $row)
            @foreach ($row['vendors'] as $vendor)
              <option value="{{ $row['trf']->id }}:{{ $vendor->id }}">
                {{ $row['trf']->trf_number }} — {{ $row['trf']->employee->name }} — {{ $vendor->company_name }}
              </option>
            @endforeach
          @endforeach
        </select>

        <div class="row g-3 mt-1">
          @foreach (['cost_score' => 'Cost', 'time_score' => 'Time', 'quality_score' => 'Quality', 'responsiveness_score' => 'Responsiveness', 'accuracy_score' => 'Accuracy'] as $field => $label)
            <div class="col-md-4">
              <label class="form-label">{{ $label }} (1–5) *</label>
              <select name="{{ $field }}" class="form-select" required>
                @for ($i = 5; $i >= 1; $i--)
                  <option value="{{ $i }}" {{ old($field, 4) == $i ? 'selected' : '' }}>{{ $i }} {{ str_repeat('★', $i) }}</option>
                @endfor
              </select>
            </div>
          @endforeach
        </div>

        <div class="mt-3">
          <label class="form-label">Comments</label>
          <textarea name="comments" class="form-control" rows="2">{{ old('comments') }}</textarea>
        </div>

        <button type="submit" class="btn-brand mt-4">Submit Rating</button>
      </form>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Rate a Travel Vendor' });

document.getElementById('comboSelect')?.addEventListener('change', (e) => {
  const [trfId, vendorId] = (e.target.value || '').split(':');
  document.getElementById('trfIdInput').value = trfId || '';
  document.getElementById('vendorIdInput').value = vendorId || '';
});
@endsection
