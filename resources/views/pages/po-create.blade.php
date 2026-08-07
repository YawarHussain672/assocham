@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('po') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Purchase Orders
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
    <h2 class="h5 mb-1">New Purchase Order</h2>
    <p class="page-sub" style="margin-top:0;">Vendor and amount are auto-populated from the CCS's L1 (lowest-cost) quote — the documented justification for selection.</p>

    @if ($statements->isEmpty())
      <div class="note-bar mt-3">
        <div class="note-dot"></div>
        <div class="note-text">No approved Cost Comparative Statements available — a PO can only be raised once a CCS has cleared Procurement Committee approval and doesn't already have a PO.</div>
      </div>
    @else
      <form method="POST" action="{{ route('po.store') }}" class="mt-3">
        @csrf

        <label class="form-label">Approved Cost Comparative Statement *</label>
        <select name="cost_comparative_statement_id" class="form-select" required id="ccsSelect">
          <option value="">Select CCS…</option>
          @foreach ($statements as $ccs)
            @php $l1 = $ccs->lowestQuote(); @endphp
            <option value="{{ $ccs->id }}" {{ old('cost_comparative_statement_id') == $ccs->id ? 'selected' : '' }}>
              {{ $ccs->ccs_number }} — {{ $ccs->rfq->purchaseRequisition->pr_number }} ({{ $ccs->rfq->purchaseRequisition->department }}) —
              L1: {{ $l1?->vendor?->company_name }} @ ₹{{ number_format($l1?->quoted_cost ?? 0, 2) }}
            </option>
          @endforeach
        </select>

        <div class="mt-3">
          <label class="form-label">Delivery Date</label>
          <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date') }}">
          <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">Optional — defaults to the vendor's quoted delivery timeline if left blank.</div>
        </div>

        <button type="submit" class="btn-brand mt-4">Create Purchase Order</button>
      </form>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'New Purchase Order' });
@endsection
