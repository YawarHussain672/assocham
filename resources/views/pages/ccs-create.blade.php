@extends('layouts.app')

@section('title', 'New Cost Comparative Statement')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('rfq.show', $rfq) }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to {{ $rfq->rfq_number }}
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
    <h2 class="h5 mb-1">Cost Comparative Statement — {{ $rfq->rfq_number }}</h2>
    <p class="page-sub" style="margin-top:0;">Enter the quote each dispatched vendor came back with. Ranked automatically by lowest cost (L1) once submitted, and routed to the Procurement Committee (Admin Head) for approval.</p>

    <form method="POST" action="{{ route('ccs.store', $rfq) }}" class="mt-3">
      @csrf

      @foreach ($rfq->rfqVendors as $rv)
        <div class="row g-2 align-items-end mb-3 pb-3" style="border-bottom:1px solid var(--border-lighter);">
          <input type="hidden" name="quotes[{{ $loop->index }}][vendor_id]" value="{{ $rv->vendor_id }}">
          <div class="col-md-3">
            <label class="form-label small">Vendor</label>
            <div class="field-value">{{ $rv->vendor->company_name }}</div>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Quoted Cost (₹)</label>
            <input type="number" name="quotes[{{ $loop->index }}][quoted_cost]" class="form-control form-control-sm" step="0.01" min="0"
              value="{{ old('quotes.'.$loop->index.'.quoted_cost') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Delivery (days)</label>
            <input type="number" name="quotes[{{ $loop->index }}][delivery_days]" class="form-control form-control-sm" min="0"
              value="{{ old('quotes.'.$loop->index.'.delivery_days') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Remarks</label>
            <input type="text" name="quotes[{{ $loop->index }}][remarks]" class="form-control form-control-sm"
              value="{{ old('quotes.'.$loop->index.'.remarks') }}">
          </div>
        </div>
      @endforeach

      <button type="submit" class="btn-brand mt-2">Submit for Committee Approval</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'New Cost Comparative Statement' });
@endsection
