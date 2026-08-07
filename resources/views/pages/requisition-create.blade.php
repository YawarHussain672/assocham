@extends('layouts.app')

@section('title', 'New Purchase Requisition')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('requisition') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Purchase Requisition
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
    <h2 class="h5 mb-1">New Purchase Requisition</h2>
    <p class="page-sub" style="margin-top:0;">Checked against the department's GST-inclusive fiscal-year budget before it can be submitted. Routes to Basu (L1) → Ambika (L2) → CFO (L3) → SG (L4).</p>

    <form method="POST" action="{{ route('requisition.store') }}" class="mt-3" id="prForm">
      @csrf

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Department *</label>
          <select name="department" class="form-select" required>
            <option value="">Select department…</option>
            @foreach ($departments as $dept)
              <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Required By</label>
          <input type="date" name="required_by" class="form-control" value="{{ old('required_by') }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="soleSourceCheck" name="sole_source" value="1" {{ old('sole_source') ? 'checked' : '' }}>
            <label class="form-check-label" for="soleSourceCheck">Sole Source</label>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold" style="font-size:13px;">Line Items</div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="addItemBtn">+ Add Item</button>
      </div>

      <div id="itemRows"></div>

      <template id="itemRowTemplate">
        <div class="row g-2 align-items-end item-row mb-2">
          <div class="col-md-4">
            <label class="form-label small">Item</label>
            <input type="text" name="items[__i__][item_name]" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Qty</label>
            <input type="number" name="items[__i__][quantity]" class="form-control form-control-sm" value="1" min="1">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Unit</label>
            <input type="text" name="items[__i__][unit]" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Unit Cost (₹)</label>
            <input type="number" name="items[__i__][unit_cost]" class="form-control form-control-sm" step="0.01" min="0">
          </div>
          <div class="col-md-1">
            <label class="form-label small">GST %</label>
            <input type="number" name="items[__i__][gst_percent]" class="form-control form-control-sm" value="18" step="0.01" min="0" max="100">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">✕</button>
          </div>
        </div>
      </template>

      <button type="submit" class="btn-brand mt-4">Submit for L1 Approval</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'New Purchase Requisition' });

(function () {
  const rowsWrap = document.getElementById('itemRows');
  const template = document.getElementById('itemRowTemplate');
  let i = 0;

  function addRow() {
    const html = template.innerHTML.replaceAll('__i__', String(i++));
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    rowsWrap.appendChild(wrap.firstElementChild);
  }

  document.getElementById('addItemBtn').addEventListener('click', addRow);
  rowsWrap.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-item-btn');
    if (btn) btn.closest('.item-row').remove();
  });

  // Start with two blank rows so the form isn't empty on load.
  addRow();
  addRow();
})();
@endsection
