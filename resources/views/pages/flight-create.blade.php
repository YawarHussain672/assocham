@extends('layouts.app')

@section('title', 'Upload Flight & Boarding Transaction')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Flight &amp; Boarding Logistics
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
    <h2 class="h5 mb-1">Upload Transaction</h2>
    <p class="page-sub" style="margin-top:0;">Only approved TRFs are eligible — cost is mapped one-to-one against the TRF number.</p>

    @if ($trfs->isEmpty())
      <div class="note-bar mt-3">
        <div class="note-dot"></div>
        <div class="note-text">No approved TRFs available — travel must clear its approval first.</div>
      </div>
    @else
      <form method="POST" action="{{ route('flight.store') }}" class="mt-3" enctype="multipart/form-data">
        @csrf

        <label class="form-label">Approved TRF *</label>
        <select name="trf_id" class="form-select" required>
          <option value="">Select TRF…</option>
          @foreach ($trfs as $trf)
            <option value="{{ $trf->id }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
              {{ $trf->trf_number }} — {{ $trf->employee->name }} ({{ $trf->route ?: 'route not specified' }})
            </option>
          @endforeach
        </select>

        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Type *</label>
            <select name="type" class="form-select" required>
              <option value="Flight ticket" {{ old('type') === 'Flight ticket' ? 'selected' : '' }}>Flight ticket</option>
              <option value="Hotel" {{ old('type') === 'Hotel' ? 'selected' : '' }}>Hotel</option>
              <option value="Transport" {{ old('type') === 'Transport' ? 'selected' : '' }}>Transport</option>
              <option value="Other" {{ old('type') === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Vendor</label>
            <select name="vendor_id" class="form-select">
              <option value="">None</option>
              @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->company_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Cost (₹) *</label>
            <input type="number" name="cost" class="form-control" step="0.01" min="0" value="{{ old('cost') }}" required>
          </div>
          <div class="col-12">
            <label class="form-label">Proof (boarding pass / mail transaction)</label>
            <input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">PDF or image, up to 5MB. Optional.</div>
          </div>
        </div>

        <button type="submit" class="btn-brand mt-4">Log Transaction</button>
      </form>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Upload Transaction' });
@endsection
