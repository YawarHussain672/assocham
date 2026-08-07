@extends('layouts.app')

@section('title', 'Log Goods Receipt')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('grn') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Goods Receipt (GRN)
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
    <h2 class="h5 mb-1">Log Goods Receipt</h2>
    <p class="page-sub" style="margin-top:0;">Multiple GRNs can be raised against a single PO until the ordered quantity is fully delivered.</p>

    @if ($purchaseOrders->isEmpty())
      <div class="note-bar mt-3">
        <div class="note-dot"></div>
        <div class="note-text">No issued POs with quantity remaining to receive — a GRN can only be raised against a fully-approved PO that isn't already fully received.</div>
      </div>
    @else
      <form method="POST" action="{{ route('grn.store') }}" class="mt-3">
        @csrf

        <label class="form-label">Purchase Order *</label>
        <select name="purchase_order_id" class="form-select" required>
          <option value="">Select PO…</option>
          @foreach ($purchaseOrders as $po)
            <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
              {{ $po->po_number }} — {{ $po->vendor->company_name }} ({{ $po->receivedQty() }}/{{ $po->po_qty }} received, {{ $po->remainingQtyToReceive() }} remaining)
            </option>
          @endforeach
        </select>

        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Quantity Received *</label>
            <input type="number" name="received_qty" class="form-control" min="1" value="{{ old('received_qty') }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Receipt Date</label>
            <input type="date" name="receipt_date" class="form-control" value="{{ old('receipt_date', now()->toDateString()) }}">
          </div>
          <div class="col-12">
            <label class="form-label">Quality Remarks</label>
            <textarea name="quality_remarks" class="form-control" rows="2">{{ old('quality_remarks') }}</textarea>
          </div>
        </div>

        <button type="submit" class="btn-brand mt-4">Submit for HOD Approval</button>
      </form>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grn', crumb: 'Procurement', title: 'Log Goods Receipt' });
@endsection
