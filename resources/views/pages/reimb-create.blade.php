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

        <form method="POST" action="{{ route('reimb.store') }}" id="reimbForm">
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
                <option value="{{ $trf->id }}" data-advance="{{ $trf->requested_advance }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
                  {{ $trf->trf_number }} — {{ $trf->route ?: 'route not specified' }} (Advance Released: ₹{{ number_format($trf->requested_advance, 2) }})
                </option>
              @endforeach
            </select>
            @if ($trfs->isEmpty())
              <div class="text-muted mt-1 small">You have no approved TRFs currently pending settlement.</div>
            @endif
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">Total Claim Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted">₹</span>
                <input type="number" name="claim_amount" id="claimAmountInput" class="form-control" step="0.01" min="0" value="{{ old('claim_amount') }}" placeholder="0.00" required>
              </div>
              <div class="text-muted mt-1 small" style="font-size:11.5px;">Gross total of all attached tax invoices.</div>
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

            <div class="col-12" id="reasonWrap" style="display:none;">
              <label class="form-label fw-medium text-secondary">Reason / Justification (Mandatory for off-TRF claims) <span class="text-danger">*</span></label>
              <textarea name="reason" class="form-control" rows="3" placeholder="Explain why this expense occurred without an advance travel requisition…">{{ old('reason') }}</textarea>
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

    if (net >= 0) {
      netAmountDisplay.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      netStatusText.textContent = 'Payable to Employee (Disbursement)';
      netAmountDisplay.style.color = '#2e7d4f';
      netIconWrap.style.background = '#e6f3ec';
      netIconWrap.style.color = '#2e7d4f';
      netIcon.className = 'bi bi-arrow-up-right-circle-fill';
    } else {
      const posNet = Math.abs(net);
      netAmountDisplay.textContent = '₹' + posNet.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      netStatusText.textContent = 'Recovery from Employee (Refund to Org)';
      netAmountDisplay.style.color = '#b0263a';
      netIconWrap.style.background = '#fbe7e9';
      netIconWrap.style.color = '#b0263a';
      netIcon.className = 'bi bi-arrow-down-left-circle-fill';
    }
  }

  function syncOffTrf() {
    const isOff = offTrfCheck.checked;
    trfWrap.style.display = isOff ? 'none' : '';
    reasonWrap.style.display = isOff ? '' : 'none';
    trfSelect.disabled = isOff;
    if (isOff) advanceInput.value = '0.00';
    updateNet();
  }

  offTrfCheck.addEventListener('change', syncOffTrf);
  syncOffTrf();

  trfSelect.addEventListener('change', () => {
    const opt = trfSelect.selectedOptions[0];
    advanceInput.value = opt ? (opt.dataset.advance || '0.00') : '0.00';
    updateNet();
  });

  claimInput.addEventListener('input', updateNet);
  advanceInput.addEventListener('input', updateNet);
  updateNet();
})();
@endsection

