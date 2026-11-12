@extends('layouts.app')

@section('title', 'New Reimbursement Claim')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('reimb') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Reimbursements &amp; Advances</span>
  </a>

  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <ul class="mb-0 ps-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left: Claim Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">File Reimbursement &amp; Settlement Claim</h5>
              <div class="text-muted small">Submit expense statements, calculate advance recovery/disbursement, and route for audit approval.</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Settlement Filing</span>
        </div>

        <form method="POST" action="{{ route('reimb.store') }}" id="reimbForm" enctype="multipart/form-data">
          @csrf

          <div class="p-3 border rounded-3 mb-3 bg-light d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-patch-exclamation text-warning fs-5 me-1"></i>
              <div>
                <label class="form-check-label mb-0 fw-semibold text-dark small" for="offTrfCheck" style="cursor:pointer;">
                  Off-TRF Direct Claim (No linked travel requisition)
                </label>
                <div class="text-muted" style="font-size:11.5px;">Requires mandatory justification and SG approval override.</div>
              </div>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="offTrfCheck">
            </div>
          </div>

          <div id="trfSelectWrap" class="mb-3">
            <label class="form-label fw-medium text-secondary">Linked Approved Travel Requisition (TRF) <span class="text-danger">*</span></label>
            <select name="trf_id" id="trfSelect" class="form-select">
              <option value="">Select TRF to auto-populate advance…</option>
              @foreach ($trfs as $trf)
                @php
                  $flightCost = (float) $trf->flightLogs->sum('cost');
                  $flightCount = $trf->flightLogs->count();
                @endphp
                <option value="{{ $trf->id }}"
                        data-advance="{{ $trf->requested_advance }}"
                        data-flight-cost="{{ $flightCost }}"
                        data-flight-count="{{ $flightCount }}"
                        {{ (string) old('trf_id', request('trf_id')) === (string) $trf->id ? 'selected' : '' }}>
                  {{ $trf->trf_number }} — {{ $trf->route ?: 'route not specified' }} (Advance Released: ₹{{ number_format($trf->requested_advance, 2) }})
                </option>
              @endforeach
            </select>
            @if ($trfs->isEmpty())
              <div class="text-muted mt-1 small">
                You have no completed approved TRFs pending settlement.
                @if (!empty($upcomingTrfs) && $upcomingTrfs->isNotEmpty())
                  ({{ $upcomingTrfs->count() }} approved upcoming {{ Str::plural('trip', $upcomingTrfs->count()) }} will unlock after trip completion.)
                @endif
              </div>
            @endif
            <div id="trfLogisticsHint" class="alert alert-light border mt-2 py-2 px-3 small d-none mb-0" style="font-size:12px;">
              <i class="bi bi-airplane text-primary me-1"></i>
              <span id="trfLogisticsText"></span>
            </div>
          </div>

          <!-- Itemised Expense Breakdown with Per-Item Bill Attachment -->
          <div class="mb-3 p-3 rounded-3 border bg-light" style="border-color:#e2e8f0 !important;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
              <div>
                <div class="fw-semibold text-dark" style="font-size:13.5px;">
                  <i class="bi bi-list-check text-primary me-1"></i>Itemised Expense Breakdown
                </div>
                <div class="text-muted" style="font-size:11.5px;">Add each expense (date, category, amount) and attach its bill. Total calculates automatically.</div>
              </div>
              <button type="button" id="addExpenseRowBtn" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 fw-semibold">
                <i class="bi bi-plus-lg"></i>
                <span>Add Expense</span>
              </button>
            </div>

            <div id="expenseItemsList">
              @php
                $oldItems = old('items', [['expense_date' => now()->toDateString(), 'category' => $categories[0] ?? 'Hotel / Accommodation', 'description' => '', 'amount' => '']]);
              @endphp
              @foreach ($oldItems as $idx => $oldItem)
                <div class="expense-item-row bg-white p-3 rounded border mb-2" data-row-index="{{ $idx }}">
                  <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-4">
                      <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Date <span class="text-danger">*</span></label>
                      <input type="date" name="items[{{ $idx }}][expense_date]" class="form-control form-control-sm" value="{{ $oldItem['expense_date'] ?? now()->toDateString() }}" required>
                    </div>
                    <div class="col-12 col-sm-5">
                      <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Category <span class="text-danger">*</span></label>
                      <select name="items[{{ $idx }}][category]" class="form-select form-select-sm" required>
                        @foreach ($categories ?? \App\Http\Controllers\ReimbursementController::EXPENSE_CATEGORIES as $cat)
                          <option value="{{ $cat }}" {{ ($oldItem['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-12 col-sm-3">
                      <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Amount (₹) <span class="text-danger">*</span></label>
                      <input type="number" name="items[{{ $idx }}][amount]" class="form-control form-control-sm item-amount-input" step="0.01" min="0.01" value="{{ $oldItem['amount'] ?? '' }}" placeholder="0.00" required>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Description</label>
                      <input type="text" name="items[{{ $idx }}][description]" class="form-control form-control-sm" value="{{ $oldItem['description'] ?? '' }}" placeholder="e.g. Hotel stay / airport cab">
                    </div>
                    <div class="col-10 col-md-5">
                      <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Bill Attachment</label>
                      <input type="file" name="items[{{ $idx }}][bill]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="col-2 col-md-1 text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-expense-row" style="height:31px;" title="Remove row" {{ count($oldItems) <= 1 ? 'disabled' : '' }}>
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Total Claim Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="claim_amount" id="claimAmountInput" class="form-control bg-light fw-semibold" step="0.01" min="0" value="{{ old('claim_amount', '0.00') }}" placeholder="0.00" readonly required>
              </div>
              <div class="text-muted mt-1 small" style="font-size:11.5px;">Auto-totalled from itemised expenses above.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Advance Already Released (₹)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="advance_amount" id="advanceInput" class="form-control" step="0.01" min="0" value="{{ old('advance_amount', '0.00') }}" placeholder="0.00">
              </div>
              <div class="text-muted mt-1 small" style="font-size:11.5px;">Auto-populated from linked TRF or enter 0.</div>
            </div>

            <!-- Interactive Net Balance Live Summary -->
            <div class="col-12">
              <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between bg-light" id="netBalanceCard">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle d-flex align-items-center justify-content-center me-1 flex-shrink-0" style="width:38px; height:38px; font-size:17px; background:#e2e8f0; color:#475569;" id="netIconWrap">
                    <i class="bi bi-calculator" id="netIcon"></i>
                  </div>
                  <div>
                    <div class="text-muted fw-semibold small text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Net Financial Settlement Preview</div>
                    <div id="netStatusText" class="fw-bold text-dark" style="font-size:14px;">Enter claim amount to view balance</div>
                  </div>
                </div>
                <div class="text-end">
                  <div id="netAmountDisplay" class="fw-bold text-dark" style="font-size:1.25rem;">₹0.00</div>
                  <div class="text-muted small" id="netSubText" style="font-size:11px;">Claim − Advance</div>
                </div>
              </div>
            </div>

            <div class="col-12" id="reasonWrap">
              <label class="form-label fw-medium text-secondary" id="reasonLabel">
                Overall Claim Remarks
                <span class="text-danger d-none" id="reasonRequiredStar">*</span>
              </label>
              <textarea name="reason" id="reasonInput" class="form-control" rows="2" placeholder="Optional settlement notes (mandatory justification if filing an Off-TRF claim)…">{{ old('reason') }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium text-secondary" for="receiptsInput">Additional Summary Documents (Optional, PDF / JPG / PNG)</label>
              <input type="file" name="receipts[]" id="receiptsInput" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple>
              <div class="form-text text-muted small">Use this only for consolidated cover sheets; per-expense bills are attached on each row above.</div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('reimb') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-send-check"></i>
              <span>Submit Claim for Audit</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Reimbursement Policy Sidebar (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">

        <!-- Settlement Rules Card -->
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-cash-coin"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Settlement Rules</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Policy</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-success border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-wallet2 fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Net Payable</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">If Claim &gt; Advance, Finance will disburse the remaining balance directly to the employee's verified account.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-warning border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-arrow-repeat fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Advance Recovery</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">If Advance &gt; Claim, unspent funds must be refunded back to the institution's bank with deposit proof.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-primary border me-2 flex-shrink-0" style="width:34px; height:34px; margin-top:2px;">
                <i class="bi bi-receipt fs-6"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Tax Invoices</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Only original GST tax invoices with itemized tax components are admissible for expense audit clearance.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 7-Day Audit SLA Card -->
        <div class="card-panel p-3">
          <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success me-1 flex-shrink-0" style="width:28px; height:28px;">
                <i class="bi bi-clock-history"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">7-Day Audit SLA</h6>
            </div>
            <div class="text-muted small" style="font-size:12.5px; line-height:1.5;">
              Reimbursements are vetted by Accounts, verified by CFO, and disbursed on the subsequent weekly payroll cycle.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'reimb', crumb: 'Travel & Logistics', title: 'New Reimbursement Claim' });

(function () {
  const offTrfCheck = document.getElementById('offTrfCheck');
  const trfWrap = document.getElementById('trfSelectWrap');
  const trfSelect = document.getElementById('trfSelect');
  const reasonWrap = document.getElementById('reasonWrap');
  const advanceInput = document.getElementById('advanceInput');
  const claimInput = document.getElementById('claimAmountInput');
  const netAmountDisplay = document.getElementById('netAmountDisplay');
  const netStatusText = document.getElementById('netStatusText');
  const netIconWrap = document.getElementById('netIconWrap');
  const netIcon = document.getElementById('netIcon');

  function updateNet() {
    const claim = parseFloat(claimInput.value) || 0;
    const adv = parseFloat(advanceInput.value) || 0;
    const net = claim - adv;
    
    if (claim === 0 && adv === 0) {
      netAmountDisplay.textContent = '₹0.00';
      netStatusText.textContent = 'Enter claim amount to view balance';
      netAmountDisplay.style.color = '#1e293b';
      netIconWrap.style.background = '#e2e8f0';
      netIconWrap.style.color = '#475569';
      netIcon.className = 'bi bi-calculator';
      return;
    }

    if (Math.abs(net) < 0.01) {
      netAmountDisplay.textContent = '₹0.00';
      netStatusText.textContent = 'Nil Balance (Advance Fully Adjusted)';
      netAmountDisplay.style.color = '#0f766e';
      netIconWrap.style.background = '#ccfbf1';
      netIconWrap.style.color = '#0f766e';
      netIcon.className = 'bi bi-check-circle-fill';
    } else if (net > 0) {
      netAmountDisplay.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      netStatusText.textContent = 'Payable to Employee (Disbursement)';
      netAmountDisplay.style.color = '#2e7d4f';
      netIconWrap.style.background = '#e6f3ec';
      netIconWrap.style.color = '#2e7d4f';
      netIcon.className = 'bi bi-arrow-up-right-circle-fill';
    } else {
      const posNet = Math.abs(net);
      netAmountDisplay.textContent = '₹' + posNet.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      netStatusText.textContent = 'Recovery from Employee (Refundable to Org)';
      netAmountDisplay.style.color = '#b0263a';
      netIconWrap.style.background = '#fbe7e9';
      netIconWrap.style.color = '#b0263a';
      netIcon.className = 'bi bi-arrow-down-left-circle-fill';
    }
  }

  const reasonRequiredStar = document.getElementById('reasonRequiredStar');
  const reasonInput = document.getElementById('reasonInput');

  function syncOffTrf() {
    const isOff = offTrfCheck.checked;
    trfWrap.style.display = isOff ? 'none' : '';
    trfSelect.disabled = isOff;
    if (reasonRequiredStar) reasonRequiredStar.classList.toggle('d-none', !isOff);
    if (reasonInput) reasonInput.required = isOff;
    if (isOff) {
      advanceInput.value = '0.00';
      advanceInput.readOnly = false;
      advanceInput.classList.remove('bg-light');
      if (trfLogisticsHint) trfLogisticsHint.classList.add('d-none');
    } else if (trfSelect.value) {
      syncAdvanceFromSelectedTrf();
    }
    updateNet();
  }

  offTrfCheck.addEventListener('change', syncOffTrf);

  const trfLogisticsHint = document.getElementById('trfLogisticsHint');
  const trfLogisticsText = document.getElementById('trfLogisticsText');

  function syncAdvanceFromSelectedTrf() {
    const opt = trfSelect.selectedOptions[0];
    const hasTrf = Boolean(trfSelect.value);
    advanceInput.value = opt ? (opt.dataset.advance || '0.00') : '0.00';
    advanceInput.readOnly = hasTrf;
    advanceInput.classList.toggle('bg-light', hasTrf);
    if (trfLogisticsHint && trfLogisticsText) {
      const fCount = parseInt(opt?.dataset?.flightCount || '0', 10);
      const fCost = parseFloat(opt?.dataset?.flightCost || '0') || 0;
      if (fCount > 0) {
        trfLogisticsText.textContent = `Corporate Travel Logistics Logged: ${fCount} booking(s) totalling ₹${fCost.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} recorded by Admin (for audit reconciliation; claim only out-of-pocket expenses above).`;
        trfLogisticsHint.classList.remove('d-none');
      } else {
        trfLogisticsHint.classList.add('d-none');
      }
    }
    updateNet();
  }

  syncOffTrf();
  trfSelect.addEventListener('change', syncAdvanceFromSelectedTrf);

  @if (!old('advance_amount') && request('trf_id'))
    if (trfSelect.value) {
      syncAdvanceFromSelectedTrf();
    }
  @endif

  const expenseList = document.getElementById('expenseItemsList');
  const addExpenseBtn = document.getElementById('addExpenseRowBtn');
  const expenseCategories = @json($categories ?? \App\Http\Controllers\ReimbursementController::EXPENSE_CATEGORIES);
  let nextItemIndex = {{ count($oldItems ?? [1]) }};

  function recalculateItemisedTotal() {
    if (!expenseList) return;
    const amounts = expenseList.querySelectorAll('.item-amount-input');
    let sum = 0;
    amounts.forEach((inp) => {
      const val = parseFloat(inp.value);
      if (!Number.isNaN(val) && val > 0) sum += val;
    });
    claimInput.value = sum.toFixed(2);
    const removeBtns = expenseList.querySelectorAll('.remove-expense-row');
    removeBtns.forEach((btn) => { btn.disabled = removeBtns.length <= 1; });
    updateNet();
  }

  function buildExpenseRow(idx) {
    const wrapper = document.createElement('div');
    wrapper.className = 'expense-item-row bg-white p-3 rounded border mb-2';
    wrapper.dataset.rowIndex = String(idx);

    const catOptions = expenseCategories.map((c) => `<option value="${c}">${c}</option>`).join('');
    const todayIso = new Date().toISOString().slice(0, 10);

    wrapper.innerHTML = `
      <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Date <span class="text-danger">*</span></label>
          <input type="date" name="items[${idx}][expense_date]" class="form-control form-control-sm" value="${todayIso}" required>
        </div>
        <div class="col-12 col-sm-5">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Category <span class="text-danger">*</span></label>
          <select name="items[${idx}][category]" class="form-select form-select-sm" required>${catOptions}</select>
        </div>
        <div class="col-12 col-sm-3">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Amount (₹) <span class="text-danger">*</span></label>
          <input type="number" name="items[${idx}][amount]" class="form-control form-control-sm item-amount-input" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Description</label>
          <input type="text" name="items[${idx}][description]" class="form-control form-control-sm" placeholder="e.g. Hotel stay / airport cab">
        </div>
        <div class="col-10 col-md-5">
          <label class="form-label text-muted mb-1" style="font-size:11px; font-weight:600;">Bill Attachment</label>
          <input type="file" name="items[${idx}][bill]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div class="col-2 col-md-1 text-end">
          <button type="button" class="btn btn-sm btn-outline-danger remove-expense-row" style="height:31px;" title="Remove row">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    `;
    return wrapper;
  }

  if (expenseList && addExpenseBtn) {
    expenseList.addEventListener('input', (e) => {
      if (e.target.classList.contains('item-amount-input')) {
        recalculateItemisedTotal();
      }
    });
    expenseList.addEventListener('click', (e) => {
      const btn = e.target.closest('.remove-expense-row');
      if (!btn || expenseList.querySelectorAll('.expense-item-row').length <= 1) return;
      btn.closest('.expense-item-row').remove();
      recalculateItemisedTotal();
    });
    addExpenseBtn.addEventListener('click', () => {
      expenseList.appendChild(buildExpenseRow(nextItemIndex++));
      recalculateItemisedTotal();
    });
    recalculateItemisedTotal();
  }

  claimInput.addEventListener('input', updateNet);
  advanceInput.addEventListener('input', updateNet);
  updateNet();
})();
@endsection

