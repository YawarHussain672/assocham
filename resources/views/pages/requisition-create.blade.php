@extends('layouts.app')

@section('title', 'New Purchase Requisition')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('requisition') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Requisitions</span>
  </a>

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-octagon-fill fs-5"></i>
      <ul class="mb-0 ps-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left: Main Form Panel (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle">
            <i class="bi bi-file-earmark-plus"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Create Purchase Requisition</h2>
            <div class="text-muted" style="font-size:13px;">Automatically validated against department fiscal year allocations prior to approval routing.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('requisition.store') }}" class="mt-2" id="prForm">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-building text-muted"></i> Department *
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-diagram-2"></i></span>
                <select name="department" class="form-select" required>
                  <option value="">Select department…</option>
                  @foreach ($departments as $dept)
                    <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-calendar-event text-muted"></i> Required By Date
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                <input type="date" name="required_by" class="form-control" value="{{ old('required_by') }}">
              </div>
            </div>

            <div class="col-12">
              <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                  <input class="form-check-input mt-0" type="checkbox" id="soleSourceCheck" name="sole_source" value="1" {{ old('sole_source') ? 'checked' : '' }}>
                  <label class="form-check-label d-flex align-items-center gap-2" for="soleSourceCheck" style="font-size:13.5px; font-weight:600; cursor:pointer;">
                    <i class="bi bi-shield-lock" style="color:var(--maroon);"></i>
                    <span>Sole Source Procurement</span>
                  </label>
                </div>
                <div class="text-muted ms-4 mt-1" style="font-size:12px;">Check if proprietary, standardized, or single-vendor constraint applies without open bidding.</div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-boxes" style="color:var(--maroon); font-size:18px;"></i>
              <div class="fw-semibold" style="font-size:15px; font-weight:600;">Line Items</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1" id="addItemBtn" style="font-weight:600;">
              <i class="bi bi-plus-circle"></i> Add Item Row
            </button>
          </div>

          <div id="itemRows" class="d-flex flex-column gap-2"></div>

          <template id="itemRowTemplate">
            <div class="p-3 rounded-3 item-row" style="background:#f8fafc; border:1px solid #eef2f6;">
              <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                  <label class="form-label small mb-1"><i class="bi bi-box-seam text-muted"></i> Item Description *</label>
                  <input type="text" name="items[__i__][item_name]" class="form-control form-control-sm" placeholder="e.g. Dell Latitude 5540" required>
                </div>
                <div class="col-6 col-md-2">
                  <label class="form-label small mb-1">Qty</label>
                  <input type="number" name="items[__i__][quantity]" class="form-control form-control-sm" value="1" min="1" required>
                </div>
                <div class="col-6 col-md-2">
                  <label class="form-label small mb-1">Unit</label>
                  <input type="text" name="items[__i__][unit]" class="form-control form-control-sm" placeholder="pcs / units" value="Units">
                </div>
                <div class="col-6 col-md-2">
                  <label class="form-label small mb-1"><i class="bi bi-currency-rupee text-muted"></i> Unit Cost</label>
                  <input type="number" name="items[__i__][unit_cost]" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0" required>
                </div>
                <div class="col-4 col-md-1">
                  <label class="form-label small mb-1">GST %</label>
                  <input type="number" name="items[__i__][gst_percent]" class="form-control form-control-sm" value="18" step="0.01" min="0" max="100">
                </div>
                <div class="col-2 col-md-1 text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn w-100" title="Remove item" style="height:31px;">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </div>
            </div>
          </template>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('requisition') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
              <i class="bi bi-send-check"></i>
              <span>Submit for L1 Approval</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Contextual Policy & Routing Panel (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <!-- Workflow Approval Route Card -->
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-diagram-3-fill" style="color:var(--maroon);"></i>
            <span>Approval Sequence</span>
          </div>
          <div class="text-muted mb-3" style="font-size:12.5px;">Standard multi-tier governance applied to this purchase:</div>
          
          <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center gap-3 p-2 rounded-2" style="background:#f8fafc; border:1px solid #eef2f6;">
              <span style="width:24px; height:24px; border-radius:50%; background:#fee2e2; color:#c8102e; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600;">1</span>
              <div>
                <div class="fw-semibold text-dark" style="font-size:13px; font-weight:600;">L1 · Basu (Approver)</div>
                <div class="text-muted" style="font-size:11.5px;">Initial verification &amp; operational review</div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 p-2 rounded-2" style="background:#f8fafc; border:1px solid #eef2f6;">
              <span style="width:24px; height:24px; border-radius:50%; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600;">2</span>
              <div>
                <div class="fw-semibold text-dark" style="font-size:13px; font-weight:600;">L2 · Ambika (Approver)</div>
                <div class="text-muted" style="font-size:11.5px;">Administrative compliance check</div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 p-2 rounded-2" style="background:#f8fafc; border:1px solid #eef2f6;">
              <span style="width:24px; height:24px; border-radius:50%; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600;">3</span>
              <div>
                <div class="fw-semibold text-dark" style="font-size:13px; font-weight:600;">L3 · CFO (Finance)</div>
                <div class="text-muted" style="font-size:11.5px;">Budget allocation &amp; fund clearance</div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 p-2 rounded-2" style="background:#f8fafc; border:1px solid #eef2f6;">
              <span style="width:24px; height:24px; border-radius:50%; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600;">4</span>
              <div>
                <div class="fw-semibold text-dark" style="font-size:13px; font-weight:600;">L4 · Secretary General</div>
                <div class="text-muted" style="font-size:11.5px;">Final executive endorsement</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Procurement Policy Checks Card -->
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:#059669;"></i>
            <span>Policy Guidelines</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>All items must specify accurate unit costs inclusive of prevailing GST rates.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>Requisitions exceeding department allocations will require an exception waiver.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>Sole source requests must attach technical or proprietary exclusivity certificates.</span>
            </li>
          </ul>
        </div>

        <!-- Quick Help Card -->
        <div class="p-3 rounded-3" style="background:#f1f5f9; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1" style="font-weight:600;">
            <i class="bi bi-question-circle" style="color:var(--maroon);"></i>
            <span>Need Assistance?</span>
          </div>
          <div class="text-muted">Reach out to the Procurement Helpdesk for inquiries regarding vendor catalog items or expedited approvals.</div>
        </div>

      </div>
    </div>
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
    if (btn) {
      if (rowsWrap.querySelectorAll('.item-row').length > 1) {
        btn.closest('.item-row').remove();
      } else {
        alert('At least one line item is required.');
      }
    }
  });

  // Start with two blank rows
  addRow();
  addRow();
})();
@endsection
