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
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(46,125,79,0.12),rgba(46,125,79,0.04)); color:#2e7d4f; border-color:rgba(46,125,79,0.2);">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">File Reimbursement &amp; Settlement Claim</h2>
            <div class="text-muted" style="font-size:13px;">Submit expense statements, calculate advance recovery/disbursement, and route for audit approval.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('reimb.store') }}" class="mt-3" id="reimbForm">
          @csrf

          <div class="p-3 border rounded-3 mb-3 bg-light d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-patch-exclamation text-warning fs-5"></i>
              <div>
                <label class="form-check-label mb-0" for="offTrfCheck" style="font-size:13px; font-weight:600; cursor:pointer;">
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
            <label class="form-label"><i class="bi bi-link-45deg text-muted"></i> Linked Approved Travel Requisition (TRF) *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-airplane"></i></span>
              <select name="trf_id" id="trfSelect" class="form-select">
                <option value="">Select TRF to auto-populate advance…</option>
                @foreach ($trfs as $trf)
                  <option value="{{ $trf->id }}" data-advance="{{ $trf->requested_advance }}" {{ old('trf_id') == $trf->id ? 'selected' : '' }}>
                    {{ $trf->trf_number }} — {{ $trf->route ?: 'route not specified' }} (Advance Released: ₹{{ number_format($trf->requested_advance, 2) }})
                  </option>
                @endforeach
              </select>
            </div>
            @if ($trfs->isEmpty())
              <div class="text-muted mt-1" style="font-size:12px;">You have no approved TRFs currently pending settlement.</div>
            @endif
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-cash-stack text-muted"></i> Total Claim Amount (₹) *</label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="claim_amount" id="claimAmountInput" class="form-control" step="0.01" min="0" value="{{ old('claim_amount') }}" placeholder="0.00" required>
              </div>
              <div class="text-muted mt-1" style="font-size:11.5px;">Gross total of all attached tax invoices.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-wallet2 text-muted"></i> Advance Already Released (₹)</label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="advance_amount" id="advanceInput" class="form-control" step="0.01" min="0" value="{{ old('advance_amount', '0.00') }}" placeholder="0.00">
              </div>
              <div class="text-muted mt-1" style="font-size:11.5px;">Auto-populated from linked TRF or enter 0.</div>
            </div>

            <!-- Interactive Net Balance Live Summary -->
            <div class="col-12">
              <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between" style="background:#f8fafc;" id="netBalanceCard">
                <div class="d-flex align-items-center gap-3">
                  <div class="form-icon-circle" style="width:36px; height:36px; font-size:16px; background:#e2e8f0; color:#475569;" id="netIconWrap">
                    <i class="bi bi-calculator" id="netIcon"></i>
                  </div>
                  <div>
                    <div class="text-muted" style="font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Net Financial Settlement Preview</div>
                    <div id="netStatusText" style="font-size:14px; font-weight:600; color:#1e293b;">Enter claim amount to view balance</div>
                  </div>
                </div>
                <div class="text-end">
                  <div id="netAmountDisplay" style="font-size:18px; font-weight:600; color:#1e293b;">₹0.00</div>
                  <div class="text-muted" id="netSubText" style="font-size:11px;">Claim − Advance</div>
                </div>
              </div>
            </div>

            <div class="col-12" id="reasonWrap" style="display:none;">
              <label class="form-label"><i class="bi bi-chat-left-text text-muted"></i> Reason / Justification (Mandatory for off-TRF claims) *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-file-earmark-medical"></i></span>
                <textarea name="reason" class="form-control" rows="3" placeholder="Explain why this expense occurred without an advance travel requisition…">{{ old('reason') }}</textarea>
              </div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('reimb') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
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

        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-cash-coin" style="color:var(--maroon);"></i>
            <span>Settlement Rules</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-3" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Net Payable:</strong> If Claim &gt; Advance, Finance will disburse the remaining balance to employee account.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-warning mt-1"></i>
              <span><strong>Recovery:</strong> If Advance &gt; Claim, unspent funds must be refunded back to the institution's bank.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Tax Invoices:</strong> Only original GST tax invoices with itemized tax components are admissible.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1" style="font-weight:600;">
            <i class="bi bi-clock-history" style="color:#2e7d4f;"></i>
            <span>7-Day Audit SLA</span>
          </div>
          <div class="text-muted">Reimbursements are vetted by Accounts, verified by CFO, and disbursed on the subsequent weekly payroll cycle.</div>
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

