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
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="form-icon-circle">
              <i class="bi bi-file-earmark-plus"></i>
            </div>
            <div>
              <h2 class="h5 mb-0" style="font-weight:600;">Create Purchase Requisition</h2>
              <div class="text-muted small" style="font-size:12px;">Automatically validated against department fiscal year allocations prior to approval routing.</div>
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('requisition.store') }}" class="mt-2" id="prForm">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-tag text-muted me-1"></i> Requisition Title / Subject</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. IT Equipment Upgrade, Server Replacement..." style="font-size:13.5px;">
            </div>

            <div class="col-md-6">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-calendar-event text-muted me-1"></i> Event Name</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <input type="text" name="event_name" class="form-control" value="{{ old('event_name') }}" placeholder="e.g. Annual Leadership Summit 2026, Tech Expo..." style="font-size:13.5px;">
            </div>

            <div class="col-md-4">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span>Department *</span>
                <span id="deptBudgetBadge" class="badge bg-light text-muted border font-monospace py-0.5 px-2" style="font-size: 11px;">
                  Select Department
                </span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-diagram-2"></i></span>
                <select name="department" id="departmentSelect" class="form-select" required>
                  <option value="">Select department…</option>
                  @foreach ($departments as $dept)
                  <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span><i class="bi bi-geo-alt text-muted me-1"></i> Region / Location</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-pin-map"></i></span>
                <input type="text" name="region" class="form-control" value="{{ old('region') }}" placeholder="e.g. North Region, Delhi HQ, Mumbai...">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">
                Required By Date
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                <input type="date" name="required_by" class="form-control" value="{{ old('required_by') }}">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-info-circle text-muted me-1"></i> Special Instructions</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <textarea name="special_instructions" class="form-control" rows="2" style="font-size:13px;" placeholder="Provide overall context, business objective, delivery timelines, vendor constraints, or installation requirements...">{{ old('special_instructions', old('description')) }}</textarea>
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
                  <label class="form-label small mb-1"><i class="bi bi-box-seam text-muted"></i> Item Name <span class="text-danger">*</span></label>
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
                <div class="col-12 mt-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" style="font-size:11.5px; white-space:nowrap;"><i class="bi bi-file-text me-1"></i>Description / Specs <span class="text-muted fw-normal">(Optional):</span></span>
                    <input type="text" name="items[__i__][description]" class="form-control form-control-sm bg-white" placeholder="Brand, technical specs, warranty, or scope details..." style="font-size:12px;">
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div class="p-3 rounded-3 mt-3 border bg-light-subtle d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-white text-secondary border px-2.5 py-1" id="itemsCountBadge">0 Items</span>
              <span class="text-muted small" style="font-size: 12px;">Calculation: &Sigma; (Qty &times; Unit Cost + GST)</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 text-end">
              <div>
                <span class="text-muted small d-block" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Subtotal (Excl. GST)</span>
                <span class="fw-semibold text-secondary font-monospace" id="liveSubtotal" style="font-size: 13.5px;">₹0.00</span>
              </div>
              <div class="border-start ps-3">
                <span class="text-muted small d-block" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Estimated GST</span>
                <span class="fw-semibold text-secondary font-monospace" id="liveGst" style="font-size: 13.5px;">₹0.00</span>
              </div>
              <div class="border-start ps-3">
                <span class="text-dark small d-block" style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Grand Total (GST Included)</span>
                <span class="fw-bold text-primary font-monospace fs-5" id="liveGrandTotal">₹0.00</span>
              </div>
            </div>
          </div>

          <!-- Real-Time Budget Check Callout (GST-Inclusive Comparison) -->
          <div id="budgetWarningBox" class="p-3 rounded-3 mt-3 border d-none">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2" id="budgetWarningIconText">
                <i class="bi bi-shield-check fs-5 text-success" id="budgetStatusIcon"></i>
                <div>
                  <div class="fw-bold" style="font-size: 13px;" id="budgetStatusTitle">Budget Clearance Available</div>
                  <div class="small text-secondary" id="budgetStatusDesc">GST-inclusive commitment is within department funds.</div>
                </div>
              </div>
              <div class="text-end">
                <span class="text-muted small d-block" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Remaining After PR</span>
                <span class="fw-bold font-monospace" id="budgetRemainingAfter" style="font-size: 13.5px;">₹0.00</span>
              </div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('requisition') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" id="submitPrBtn" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
              <i class="bi bi-send-check"></i>
              <span>Submit for Verification &amp; Approval</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Contextual Policy & Routing Panel (col-lg-4) -->

  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'New Purchase Requisition' });

(function () {
const departmentBudgets = @json($departmentBudgets ?? []);
const rowsWrap = document.getElementById('itemRows');
const template = document.getElementById('itemRowTemplate');
const deptSelect = document.getElementById('departmentSelect');
const deptBadge = document.getElementById('deptBudgetBadge');
const budgetBox = document.getElementById('budgetWarningBox');
const submitBtn = document.getElementById('submitPrBtn');
let i = 0;

function formatInr(val) {
  return '₹' + Number(val).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function updateLiveTotals() {
  const rows = rowsWrap.querySelectorAll('.item-row');
  let subtotal = 0;
  let totalGst = 0;
  let validItems = 0;

  rows.forEach(row => {
    const qtyInput = row.querySelector('input[name*="[quantity]"]');
    const costInput = row.querySelector('input[name*="[unit_cost]"]');
    const gstInput = row.querySelector('input[name*="[gst_percent]"]');

    const qty = Math.max(0, parseFloat(qtyInput?.value) || 0);
    const unitCost = Math.max(0, parseFloat(costInput?.value) || 0);
    const gstRate = Math.max(0, parseFloat(gstInput?.value) || 0);

    if (qty > 0 && unitCost > 0) {
      validItems++;
    }

    const lineBase = qty * unitCost;
    const lineGst = lineBase * (gstRate / 100);

    subtotal += lineBase;
    totalGst += lineGst;
  });

  const grandTotal = Math.round((subtotal + totalGst) * 100) / 100;

  const countEl = document.getElementById('itemsCountBadge');
  const subtotalEl = document.getElementById('liveSubtotal');
  const gstEl = document.getElementById('liveGst');
  const grandTotalEl = document.getElementById('liveGrandTotal');

  if (countEl) countEl.textContent = `${rows.length} ${rows.length === 1 ? 'Item' : 'Items'}`;
  if (subtotalEl) subtotalEl.textContent = formatInr(subtotal);
  if (gstEl) gstEl.textContent = formatInr(totalGst);
  if (grandTotalEl) grandTotalEl.textContent = formatInr(grandTotal);

  checkRealtimeBudget(grandTotal);
}

function checkRealtimeBudget(grandTotal) {
  const selectedDept = deptSelect?.value;
  if (!selectedDept || !budgetBox) {
    if (deptBadge) {
      deptBadge.className = 'badge bg-light text-muted border font-monospace py-0.5 px-2';
      deptBadge.textContent = 'Select Department';
    }
    budgetBox.classList.add('d-none');
    if (submitBtn) submitBtn.disabled = false;
    return;
  }

  const deptData = departmentBudgets[selectedDept] || { allocated: 0, utilised: 0, available: 0 };
  const allocatedBudget = Math.max(0, Number(deptData.allocated || 0));
  const utilisedBudget = Math.max(0, Number(deptData.utilised || 0));
  const availableBudget = Math.max(0, deptData.available);
  const remainingAfter = availableBudget - grandTotal;
  const projectedUtilisationPct = allocatedBudget > 0 ? ((utilisedBudget + grandTotal) / allocatedBudget) * 100 : 0;

  // Update department badge
  if (deptBadge) {
    if (grandTotal > availableBudget) {
      deptBadge.textContent = `Avail: ${formatInr(availableBudget)}`;
      deptBadge.className = 'badge bg-danger text-white border-0 font-monospace py-0.5 px-2';
    } else if (projectedUtilisationPct >= 90) {
      deptBadge.textContent = `Avail: ${formatInr(availableBudget)} (${projectedUtilisationPct.toFixed(1)}% used · 90%+ Alert)`;
      deptBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace py-0.5 px-2';
    } else {
      deptBadge.textContent = `Avail: ${formatInr(availableBudget)}`;
      deptBadge.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace py-0.5 px-2';
    }
  }

  budgetBox.classList.remove('d-none');
  const icon = document.getElementById('budgetStatusIcon');
  const title = document.getElementById('budgetStatusTitle');
  const desc = document.getElementById('budgetStatusDesc');
  const remainingEl = document.getElementById('budgetRemainingAfter');

  if (grandTotal > availableBudget) {
    // Exceeded: WARN and BLOCK
    budgetBox.className = 'p-3 rounded-3 mt-3 border border-danger bg-danger-subtle text-danger';
    if (icon) icon.className = 'bi bi-x-octagon-fill fs-4 text-danger';
    if (title) title.textContent = 'Budget Limit Exceeded — Submission Blocked';
    if (desc) desc.textContent = `GST-inclusive total (${formatInr(grandTotal)}) exceeds ${selectedDept}'s available balance (${formatInr(availableBudget)}) by ${formatInr(grandTotal - availableBudget)}. Only CFO can override via Budget Exception.`;
    if (remainingEl) {
      remainingEl.textContent = `Deficit: -${formatInr(Math.abs(remainingAfter))}`;
      remainingEl.className = 'fw-bold font-monospace text-danger';
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.title = `Total exceeds ${selectedDept} available funds`;
    }
  } else if (projectedUtilisationPct >= 90) {
    // 90% budget utilisation threshold alert (before overrun)
    budgetBox.className = 'p-3 rounded-3 mt-3 border border-warning bg-warning-subtle text-warning-emphasis';
    if (icon) icon.className = 'bi bi-exclamation-triangle-fill fs-4 text-warning';
    if (title) title.textContent = '90% Budget Utilisation Alert';
    if (desc) desc.textContent = `${selectedDept} will reach ${projectedUtilisationPct.toFixed(1)}% of its fiscal allocation (${formatInr(remainingAfter)} remaining before budget overrun).`;
    if (remainingEl) {
      remainingEl.textContent = formatInr(remainingAfter);
      remainingEl.className = 'fw-bold font-monospace text-warning-emphasis';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.title = '';
    }
  } else {
    // Clear within budget
    budgetBox.className = 'p-3 rounded-3 mt-3 border border-success-subtle bg-success-subtle text-success';
    if (icon) icon.className = 'bi bi-shield-check fs-4 text-success';
    if (title) title.textContent = 'Budget Clearance Verified';
    if (desc) desc.textContent = `GST-inclusive amount (${formatInr(grandTotal)}) is within ${selectedDept}'s fiscal allocation.`;
    if (remainingEl) {
      remainingEl.textContent = formatInr(remainingAfter);
      remainingEl.className = 'fw-bold font-monospace text-success';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.title = '';
    }
  }
}

function addRow(data = null) {
  const html = template.innerHTML.replaceAll('__i__', String(i++));
  const wrap = document.createElement('div');
  wrap.innerHTML = html;
  const rowEl = wrap.firstElementChild;

  if (data) {
    if (data.item_name !== undefined) {
      const el = rowEl.querySelector('input[name*="[item_name]"]');
      if (el) el.value = data.item_name;
    }
    if (data.quantity !== undefined) {
      const el = rowEl.querySelector('input[name*="[quantity]"]');
      if (el) el.value = data.quantity;
    }
    if (data.unit !== undefined) {
      const el = rowEl.querySelector('input[name*="[unit]"]');
      if (el) el.value = data.unit;
    }
    if (data.unit_cost !== undefined) {
      const el = rowEl.querySelector('input[name*="[unit_cost]"]');
      if (el) el.value = data.unit_cost;
    }
    if (data.gst_percent !== undefined) {
      const el = rowEl.querySelector('input[name*="[gst_percent]"]');
      if (el) el.value = data.gst_percent;
    }
    if (data.description !== undefined) {
      const el = rowEl.querySelector('input[name*="[description]"]');
      if (el) el.value = data.description;
    }
  }

  rowsWrap.appendChild(rowEl);
  updateLiveTotals();
}


document.getElementById('addItemBtn').addEventListener('click', () => addRow());
deptSelect?.addEventListener('change', updateLiveTotals);
rowsWrap.addEventListener('click', (e) => {
  const btn = e.target.closest('.remove-item-btn');
  if (btn) {
    if (rowsWrap.querySelectorAll('.item-row').length > 1) {
      btn.closest('.item-row').remove();
      updateLiveTotals();
    } else {
      alert('At least one line item is required.');
    }
  }
});

rowsWrap.addEventListener('input', updateLiveTotals);

// Start with two blank rows
addRow();
addRow();
})();
@endsection