@extends('layouts.app')

@section('title', 'Raise Grievance Ticket')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('grievance') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Grievance Redressal
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
    <h2 class="h5 mb-1">Raise a Ticket</h2>
    <p class="page-sub" style="margin-top:0;">Routed first to HOD, escalating through Admin Head → CFO → SG if not resolved.</p>

    <form method="POST" action="{{ route('grievance.store') }}" class="mt-3">
      @csrf

      <label class="form-label">Subject Category *</label>
      <select name="subject_category" class="form-select" required>
        <option value="">Select category…</option>
        @foreach ($categories as $cat)
          <option value="{{ $cat }}" {{ old('subject_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
      </select>

      <div class="mt-3">
        <label class="form-label">Subject *</label>
        <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="e.g. Delay in travel vendor booking process" required>
      </div>

      <div class="mt-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Describe the operational or technical bottleneck…">{{ old('description') }}</textarea>
      </div>

      <button type="submit" class="btn-brand mt-4">Submit Grievance Ticket</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Raise Grievance Ticket' });
@endsection
