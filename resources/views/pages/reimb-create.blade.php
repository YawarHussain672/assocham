@extends('layouts.app')

@section('title', 'New Reimbursement Claim')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Reimbursement &amp; Advance
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
    <h2 class="h5 mb-1">New Reimbursement Claim</h2>
    <p class="page-sub" style="margin-top:0;">Select your TRF to auto-fill the advance already released. Off-TRF claims need SG override and a mandatory reason.</p>

    <form method="POST" action="{{ route('reimb.store') }}" class="mt-3" id="reimbForm">
      @csrf

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="offTrfCheck">
        <label class="form-check-label" for="offTrfCheck">This is an off-TRF claim (no linked travel requisition)</label>
      </div>

      <div id="trfSelectWrap">
        <label class="form-label">Travel Requisition *</label>
        <select name="trf_id" id="trfSelect" class="form-select">
          <option value="">Select TRF…</option>
          @foreach ($trfs as $trf)
            <option value="{{ $trf->id }}" data-advance="{{ $trf->requested_advance }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
              {{ $trf->trf_number }} — {{ $trf->route ?: 'route not specified' }} (advance ₹{{ number_format($trf->requested_advance, 2) }})
            </option>
          @endforeach
        </select>
        @if ($trfs->isEmpty())
          <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">You have no approved TRFs of your own to claim against.</div>
        @endif
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label class="form-label">Claim Amount (₹) *</label>
          <input type="number" name="claim_amount" class="form-control" step="0.01" min="0" value="{{ old('claim_amount') }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Advance Already Released (₹)</label>
          <input type="number" name="advance_amount" id="advanceInput" class="form-control" step="0.01" min="0" value="{{ old('advance_amount') }}">
        </div>
        <div class="col-12" id="reasonWrap" style="display:none;">
          <label class="form-label">Reason (required for off-TRF claims) *</label>
          <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
        </div>
      </div>

      <button type="submit" class="btn-brand mt-4">Submit Claim</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'New Reimbursement Claim' });

(function () {
  const offTrfCheck = document.getElementById('offTrfCheck');
  const trfWrap = document.getElementById('trfSelectWrap');
  const trfSelect = document.getElementById('trfSelect');
  const reasonWrap = document.getElementById('reasonWrap');
  const advanceInput = document.getElementById('advanceInput');

  function syncOffTrf() {
    const isOff = offTrfCheck.checked;
    trfWrap.style.display = isOff ? 'none' : '';
    reasonWrap.style.display = isOff ? '' : 'none';
    trfSelect.disabled = isOff;
    if (isOff) advanceInput.value = '';
  }
  offTrfCheck.addEventListener('change', syncOffTrf);
  syncOffTrf();

  trfSelect.addEventListener('change', () => {
    const opt = trfSelect.selectedOptions[0];
    advanceInput.value = opt ? (opt.dataset.advance || '') : '';
  });
})();
@endsection
