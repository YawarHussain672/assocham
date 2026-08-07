@extends('layouts.app')

@section('title', 'File Exception')

@php
  $typeLabels = [
    'single_quotation' => 'Single Quotation (urgent, sole quote)',
    'budget_exceed' => 'Budget Exceed Override',
    'po_force_close' => 'PO Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Check',
  ];
  $approverFor = [
    'single_quotation' => 'SG',
    'budget_exceed' => 'CFO',
    'po_force_close' => 'SG',
    'grn_reversal' => 'SG',
    'bypass_empanelment_check' => 'SG',
  ];
@endphp

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('exceptions') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Exception Handling
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
    <h2 class="h5 mb-1">File an Exception</h2>
    <p class="page-sub" style="margin-top:0;">PO Force Close and GRN Reversal apply their action automatically once approved — the others are logged and decided, without an automatic system action.</p>

    <form method="POST" action="{{ route('exceptions.store') }}" class="mt-3" id="excForm">
      @csrf
      <input type="hidden" name="reference_type" id="referenceTypeInput" value="">

      <label class="form-label">Exception Type *</label>
      <select name="exception_type" id="typeSelect" class="form-select" required>
        <option value="">Select type…</option>
        @foreach ($types as $type)
          <option value="{{ $type }}" data-approver="{{ $approverFor[$type] }}" {{ old('exception_type') === $type ? 'selected' : '' }}>
            {{ $typeLabels[$type] }} — routes to {{ $approverFor[$type] }}
          </option>
        @endforeach
      </select>

      <div class="mt-3" id="poWrap" style="display:none;">
        <label class="form-label">Purchase Order (pending approval)</label>
        <select id="poSelect" class="form-select">
          <option value="">Select PO…</option>
          @foreach ($purchaseOrders as $po)
            <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->vendor->company_name }} (₹{{ number_format($po->amount_incl_gst, 2) }})</option>
          @endforeach
        </select>
        @if ($purchaseOrders->isEmpty())
          <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">No POs currently pending approval.</div>
        @endif
      </div>

      <div class="mt-3" id="grnWrap" style="display:none;">
        <label class="form-label">GRN (approved, to reverse)</label>
        <select id="grnSelect" class="form-select">
          <option value="">Select GRN…</option>
          @foreach ($grns as $grn)
            <option value="{{ $grn->id }}">{{ $grn->grn_number }} — {{ $grn->purchaseOrder->po_number }} ({{ $grn->received_qty }} received)</option>
          @endforeach
        </select>
        @if ($grns->isEmpty())
          <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">No approved GRNs available to reverse.</div>
        @endif
      </div>

      <div class="mt-3">
        <label class="form-label">Justification *</label>
        <textarea name="justification" class="form-control" rows="3" required>{{ old('justification') }}</textarea>
      </div>

      <button type="submit" class="btn-brand mt-4">File Exception</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'File Exception' });

(function () {
  const typeSelect = document.getElementById('typeSelect');
  const poWrap = document.getElementById('poWrap');
  const grnWrap = document.getElementById('grnWrap');
  const poSelect = document.getElementById('poSelect');
  const grnSelect = document.getElementById('grnSelect');
  const refTypeInput = document.getElementById('referenceTypeInput');

  function sync() {
    const type = typeSelect.value;
    poWrap.style.display = type === 'po_force_close' ? '' : 'none';
    grnWrap.style.display = type === 'grn_reversal' ? '' : 'none';
    poSelect.name = type === 'po_force_close' ? 'reference_id' : '';
    grnSelect.name = type === 'grn_reversal' ? 'reference_id' : '';
    refTypeInput.value = type === 'po_force_close' ? 'purchase_orders' : (type === 'grn_reversal' ? 'grns' : '');
  }
  typeSelect.addEventListener('change', sync);
  sync();
})();
@endsection
