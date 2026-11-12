@extends('layouts.app')

@section('title', 'Edit Purchase Requisition — ' . $pr->pr_number)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('requisition.show', $pr) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to {{ $pr->pr_number }}</span>
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

  @if ($pr->status === 'rejected')
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0 mt-0.5"></i>
      <div>
        <div class="fw-bold">This Purchase Requisition was previously declined.</div>
        <div class="small text-secondary">
          Update the requested fields or line item commercials below to address reviewer remarks, and re-submit. Upon re-submission, a new version will be recorded and the requisition will restart at Level 1 review.
        </div>
      </div>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">
        @php
          $isApprover = in_array(auth()->user()?->role, [\App\Support\Roles::PR_APPROVER_L1, \App\Support\Roles::PR_APPROVER_L2, \App\Support\Roles::CFO, \App\Support\Roles::SG, \App\Support\Roles::ASG1, \App\Support\Roles::ASG2], true);
        @endphp

        <div class="form-icon-header">
          <div class="form-icon-circle">
            <i class="bi bi-pencil-square"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">
              @if ($pr->status === 'rejected')
                Correct &amp; Re-submit Purchase Requisition
              @elseif ($isApprover)
                Modify &amp; Re-send Requisition (Approver Review)
              @else
                Edit Purchase Requisition
              @endif
            </h2>
            <div class="text-muted" style="font-size:13px;">
              Editing {{ $pr->pr_number }} &bull; Current version: v{{ $pr->versions->first()?->version_number ?? 1 }}
              @if ($isApprover)
                &bull; <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Acting as {{ \App\Support\Roles::label(auth()->user()->role) }}</span>
              @endif
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('requisition.update', $pr) }}" class="mt-2" id="prEditForm">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-tag text-muted me-1"></i> Requisition Title / Subject</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <input type="text" name="title" class="form-control" value="{{ old('title', $pr->title) }}" placeholder="e.g. IT Equipment Upgrade, Server Replacement..." style="font-size:13.5px;">
            </div>

            <div class="col-md-6">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-calendar-event text-muted me-1"></i> Event Name</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <input type="text" name="event_name" class="form-control" value="{{ old('event_name', $pr->event_name) }}" placeholder="e.g. Annual Leadership Summit 2026, Tech Expo..." style="font-size:13.5px;">
            </div>

            <div class="col-md-4">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span>Department *</span>
                <span id="deptBudgetBadge" class="badge bg-light text-muted border font-monospace py-0.5 px-2" style="font-size: 11px;">
                  Checking Budget...
                </span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-diagram-2"></i></span>
                <select name="department" id="departmentSelect" class="form-select" required>
                  <option value="">Select department…</option>
                  @foreach ($departments as $dept)
                  <option value="{{ $dept }}" {{ old('department', $pr->department) === $dept ? 'selected' : '' }}>{{ $dept }}</option>
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
                <input type="text" name="region" class="form-control" value="{{ old('region', $pr->region) }}" placeholder="e.g. North Region, Delhi HQ, Mumbai...">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">
                Required By Date
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                <input type="date" name="required_by" class="form-control" value="{{ old('required_by', optional($pr->required_by)->format('Y-m-d')) }}">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label d-flex align-items-center justify-content-between mb-1">
                <span><i class="bi bi-info-circle text-muted me-1"></i> Special Instructions</span>
                <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <textarea name="special_instructions" class="form-control" rows="2" style="font-size:13px;" placeholder="Provide overall context, business objective, delivery timelines, vendor constraints, or installation requirements...">{{ old('special_instructions', $pr->special_instructions ?: $pr->description) }}</textarea>
            </div>

            @if ($pr->status === 'rejected')
              <div class="col-12">
                <label class="form-label d-flex align-items-center justify-content-between mb-1">
                  <span><i class="bi bi-chat-left-dots text-primary me-1"></i> Re-submission Notes / Revision Summary</span>
                  <span class="text-muted small fw-normal">(Explaining changes made)</span>
                </label>
                <textarea name="resubmit_notes" class="form-control" rows="2" style="font-size:13px;" placeholder="Explain what revisions were made to address the decline reasons...">{{ old('resubmit_notes') }}</textarea>
              </div>
            @else
              <div class="col-12">
                <label class="form-label d-flex align-items-center justify-content-between mb-1">
                  <span><i class="bi bi-chat-left-dots text-primary me-1"></i> Approver Revision Notes</span>
                  <span class="text-muted small fw-normal">(Reason for updating line items / details before re-routing for approval)</span>
                </label>
                <textarea name="resubmit_notes" class="form-control" rows="2" style="font-size:13px;" placeholder="Explain what revisions or adjustments were made before re-sending for review...">{{ old('resubmit_notes') }}</textarea>
              </div>
            @endif

            <div class="col-12">
              <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                  <input class="form-check-input mt-0" type="checkbox" id="soleSourceCheck" name="sole_source" value="1" {{ old('sole_source', $pr->sole_source) ? 'checked' : '' }}>
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

          <div id="itemRows" class="d-flex flex-column gap-2">
            @foreach ($pr->items as $idx => $item)
              <div class="p-3 rounded-3 item-row" style="background:#f8fafc; border:1px solid #eef2f6;">
                <div class="row g-2 align-items-end">
                  <div class="col-12 col-md-4">
                    <label class="form-label small mb-1"><i class="bi bi-box-seam text-muted"></i> Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="items[{{ $idx }}][item_name]" class="form-control form-control-sm" value="{{ old("items.{$idx}.item_name", $item->item_name) }}" placeholder="e.g. Dell Latitude 5540" required>
                  </div>
                  <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Qty</label>
                    <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="{{ old("items.{$idx}.quantity", $item->quantity) }}" min="1" required>
                  </div>
                  <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Unit</label>
                    <input type="text" name="items[{{ $idx }}][unit]" class="form-control form-control-sm" placeholder="pcs / units" value="{{ old("items.{$idx}.unit", $item->unit ?: 'Units') }}">
                  </div>
                  <div class="col-6 col-md-2">
                    <label class="form-label small mb-1"><i class="bi bi-currency-rupee text-muted"></i> Unit Cost</label>
                    <input type="number" name="items[{{ $idx }}][unit_cost]" class="form-control form-control-sm" value="{{ old("items.{$idx}.unit_cost", $item->unit_cost) }}" placeholder="0.00" step="0.01" min="0" required>
                  </div>
                  <div class="col-4 col-md-1">
                    <label class="form-label small mb-1">GST %</label>
                    <input type="number" name="items[{{ $idx }}][gst_percent]" class="form-control form-control-sm" value="{{ old("items.{$idx}.gst_percent", $item->gst_percent) }}" step="0.01" min="0" max="100">
                  </div>
                  <div class="col-2 col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn w-100" title="Remove item" style="height:31px;">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </div>
                  <div class="col-12 mt-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small" style="font-size:11.5px; white-space:nowrap;"><i class="bi bi-file-text me-1"></i>Description / Specs <span class="text-muted fw-normal">(Optional):</span></span>
                      <input type="text" name="items[{{ $idx }}][description]" class="form-control form-control-sm bg-white" value="{{ old("items.{$idx}.description", $item->description) }}" placeholder="Brand, technical specs, warranty, or scope details..." style="font-size:12px;">
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

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

          <!-- Live Totals Bar -->
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
            <a href="{{ route('requisition.show', $pr) }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" id="submitPrBtn" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
              <i class="bi bi-send-check"></i>
              <span>
                @if ($pr->status === 'rejected')
                  Re-submit for Verification &amp; Approval
                @elseif ($isApprover)
                  Save Changes &amp; Re-send for Level 1 Approval
                @else
                  Save Changes &amp; Re-route
                @endif
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'Edit Purchase Requisition' });

(function () {
const departmentBudgets = @json($departmentBudgets ?? []);
const originalPrTotal = {{ $pr->status === 'rejected' ? 0.0 : $pr->estimatedCostInclGst() }};
const rowsWrap = document.getElementById('itemRows');
const template = document.getElementById('itemRowTemplate');
const deptSelect = document.getElementById('departmentSelect');
const deptBadge = document.getElementById('deptBudgetBadge');
const budgetBox = document.getElementById('budgetWarningBox');
const submitBtn = document.getElementById('submitPrBtn');
let i = {{ $pr->items->count() + 10 }};

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

  rows.forEach(row => {
    const qtyInput = row.querySelector('input[name*="[quantity]"]');
    const costInput = row.querySelector('input[name*="[unit_cost]"]');
    const gstInput = row.querySelector('input[name*="[gst_percent]"]');

    const qty = Math.max(0, parseFloat(qtyInput?.value) || 0);
    const unitCost = Math.max(0, parseFloat(costInput?.value) || 0);
    const gstRate = Math.max(0, parseFloat(gstInput?.value) || 0);

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
  const utilisedOtherPrs = Math.max(0, Number(deptData.utilised || 0) - (selectedDept === '{{ $pr->department }}' ? originalPrTotal : 0));
  // The available budget in DB plus whatever this PR had already reserved if currently non-rejected
  const effectiveAvailable = deptData.available + (selectedDept === '{{ $pr->department }}' ? originalPrTotal : 0);
  const remainingAfter = effectiveAvailable - grandTotal;
  const projectedUtilisationPct = allocatedBudget > 0 ? ((utilisedOtherPrs + grandTotal) / allocatedBudget) * 100 : 0;

  if (deptBadge) {
    if (grandTotal > effectiveAvailable) {
      deptBadge.textContent = `Avail: ${formatInr(effectiveAvailable)}`;
      deptBadge.className = 'badge bg-danger text-white border-0 font-monospace py-0.5 px-2';
    } else if (projectedUtilisationPct >= 90) {
      deptBadge.textContent = `Avail: ${formatInr(effectiveAvailable)} (${projectedUtilisationPct.toFixed(1)}% used · 90%+ Alert)`;
      deptBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace py-0.5 px-2';
    } else {
      deptBadge.textContent = `Avail: ${formatInr(effectiveAvailable)}`;
      deptBadge.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace py-0.5 px-2';
    }
  }

  budgetBox.classList.remove('d-none');
  const icon = document.getElementById('budgetStatusIcon');
  const title = document.getElementById('budgetStatusTitle');
  const desc = document.getElementById('budgetStatusDesc');
  const remainingEl = document.getElementById('budgetRemainingAfter');

  if (grandTotal > effectiveAvailable) {
    budgetBox.className = 'p-3 rounded-3 mt-3 border border-danger bg-danger-subtle text-danger';
    if (icon) icon.className = 'bi bi-x-octagon-fill fs-4 text-danger';
    if (title) title.textContent = 'Budget Limit Exceeded — Submission Blocked';
    if (desc) desc.textContent = `GST-inclusive total (${formatInr(grandTotal)}) exceeds ${selectedDept}'s available funds (${formatInr(effectiveAvailable)}) by ${formatInr(grandTotal - effectiveAvailable)}.`;
    if (remainingEl) {
      remainingEl.textContent = `Deficit: -${formatInr(Math.abs(remainingAfter))}`;
      remainingEl.className = 'fw-bold font-monospace text-danger';
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.title = `Total exceeds available funds`;
    }
  } else if (projectedUtilisationPct >= 90) {
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

function addRow() {
  const html = template.innerHTML.replaceAll('__i__', String(i++));
  const wrap = document.createElement('div');
  wrap.innerHTML = html;
  rowsWrap.appendChild(wrap.firstElementChild);
  updateLiveTotals();
}

document.getElementById('addItemBtn').addEventListener('click', addRow);
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
updateLiveTotals();
})();
@endsection

