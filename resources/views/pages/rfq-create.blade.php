@extends('layouts.app')

@section('title', 'New RFQ')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to RFQ &amp; Quotations
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
    <h2 class="h5 mb-1">New RFQ</h2>
    <p class="page-sub" style="margin-top:0;">Locked to an approved PR reference. Select one or more empanelled vendors to dispatch to.</p>

    @if ($purchaseRequisitions->isEmpty())
      <div class="note-bar mt-3">
        <div class="note-dot"></div>
        <div class="note-text">No approved Purchase Requisitions available yet — an RFQ can only be raised once a PR has cleared its full L1–L4 approval chain.</div>
      </div>
    @else
      <form method="POST" action="{{ route('rfq.store') }}" class="mt-3">
        @csrf

        <label class="form-label">Approved Purchase Requisition *</label>
        <select name="purchase_requisition_id" class="form-select" required>
          <option value="">Select PR…</option>
          @foreach ($purchaseRequisitions as $pr)
            <option value="{{ $pr->id }}" {{ old('purchase_requisition_id') == $pr->id ? 'selected' : '' }}>
              {{ $pr->pr_number }} — {{ $pr->department }} (₹{{ number_format($pr->estimatedCostInclGst(), 2) }})
            </option>
          @endforeach
        </select>

        <div class="mt-3">
          <label class="form-label">Dispatch To Vendors *</label>
          @if ($vendors->isEmpty())
            <div class="note-bar">
              <div class="note-dot"></div>
              <div class="note-text">No empanelled vendors yet — a vendor must complete the full Vendor Empanelment chain before it can receive an RFQ.</div>
            </div>
          @else
            <div class="border rounded p-2" style="max-height:260px; overflow-y:auto;">
              @foreach ($vendors as $vendor)
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" id="vendor{{ $vendor->id }}"
                    {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }}>
                  <label class="form-check-label" for="vendor{{ $vendor->id }}">
                    {{ $vendor->company_name }} <span class="text-muted">({{ $vendor->vendor_code }}{{ $vendor->category ? ' · '.$vendor->category : '' }})</span>
                  </label>
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <button type="submit" class="btn-brand mt-4" {{ $vendors->isEmpty() ? 'disabled' : '' }}>Dispatch RFQ</button>
      </form>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'New RFQ' });
@endsection
