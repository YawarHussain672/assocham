@extends('layouts.app')

@section('title', 'Submit Annual Budget Proposal')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('annual-budget') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Annual Budget Proposals</span>
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

  <style>
    .view-wrap,
    .card-panel {
      overflow-x: hidden !important;
      max-width: 100% !important;
    }
    #itemsTable {
      width: 100% !important;
      margin-bottom: 0 !important;
    }
    #itemsTable th {
      background-color: #f8fafc !important;
      color: #475569 !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.04em !important;
      padding: 10px 12px !important;
      border-bottom: 1.5px solid #e2e8f0 !important;
      white-space: nowrap !important;
    }
    #itemsTable td {
      padding: 8px 12px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #e2e8f0 !important;
    }
    #itemsTable .form-control,
    #itemsTable .form-select {
      height: 34px !important;
      font-size: 12.5px !important;
      padding: 5px 10px !important;
      border-radius: 6px !important;
      border: 1px solid #cbd5e1 !important;
      background-color: #ffffff;
    }
    #itemsTable .form-control:focus,
    #itemsTable .form-select:focus {
      border-color: #c8102e !important;
      box-shadow: 0 0 0 2px rgba(244, 145, 4, 0.15) !important;
    }
  </style>

  <div class="row g-4">
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">

        {{-- Form Header --}}
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-cash-stack"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Submit Annual Budget Proposal</h1>
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('annual-budget.store') }}" id="annualBudgetForm">
          @csrf

          {{-- Section 1: Proposal Details --}}
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Proposal Details</h2>
            </div>

            <div class="row g-3">
              {{-- Department --}}
              <div class="col-md-4">
                <label class="vendor-form-label">Department <span class="text-danger">*</span></label>
                @if ($isHod && auth()->user()->department)
                  <input type="text" class="form-control vendor-form-control bg-light fw-semibold text-dark" value="{{ auth()->user()->department }}" readonly>
                  <input type="hidden" name="department" value="{{ auth()->user()->department }}">
                @else
                  <select name="department" class="form-select vendor-form-control" required id="departmentSelect">
                    <option value="">Select Department…</option>
                    @foreach ($departments as $dept)
                      <option value="{{ $dept }}" @selected(old('department', $defaultDepartment) === $dept)>{{ $dept }}</option>
                    @endforeach
                  </select>
                @endif
              </div>

              {{-- Fiscal Year --}}
              <div class="col-md-4">
                <label class="vendor-form-label">Fiscal Year <span class="text-danger">*</span></label>
                <select name="fiscal_year" class="form-select vendor-form-control" required id="fySelect">
                  @foreach ($fiscalYears as $fy)
                    <option value="{{ $fy }}" @selected(old('fiscal_year', $nextFy) === $fy)>{{ $fy }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Proposed Annual Budget Ceiling --}}
              <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="vendor-form-label mb-0">Proposed Ceiling (₹) <span class="text-danger">*</span></label>
                  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold" id="btnSumFromItems" style="font-size: 11.5px;">
                    <i class="bi bi-arrow-down-left-square me-1"></i> Sync from breakdown
                  </button>
                </div>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted fw-bold font-monospace" style="border-color: #cbd5e1; font-size: 14px;">₹</span>
                  <input type="number" step="0.01" min="1" max="999999999999" name="proposed_amount" id="proposedAmountInput" class="form-control vendor-form-control font-monospace fw-bold" placeholder="0.00" value="{{ old('proposed_amount') }}" required style="font-size: 15px; border-color: #cbd5e1;">
                </div>
              </div>

              {{-- Objectives & Expenditure Justification --}}
              <div class="col-12">
                <label class="vendor-form-label">Objectives &amp; Justification <span class="text-danger">*</span></label>
                <textarea name="justification" class="form-control vendor-form-control" rows="3" placeholder="Enter annual departmental objectives and justification..." required style="line-height: 1.5; resize: vertical; min-height: 80px;">{{ old('justification') }}</textarea>
              </div>
            </div>
          </div>

          {{-- Section 2: Itemized Expenditure Breakdown --}}
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-list-check"></i>
                </div>
                <h2 class="vendor-form-section-title mb-0">Itemized Breakdown</h2>
              </div>
              <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3" id="btnAddItemRow" style="font-weight: 600; border-radius: 6px; height: 34px;">
                <i class="bi bi-plus-circle"></i>
                <span>Add Item</span>
              </button>
            </div>

            {{-- Table: Clean, Minimal Full-Width Table --}}
            <div class="border rounded-3 bg-white mb-4" style="border-color: #e2e8f0 !important; overflow: hidden; width: 100%;">
              <table class="table align-middle mb-0 w-100" id="itemsTable">
                <thead style="background-color: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                  <tr>
                    <th style="width: 40px;" class="text-center ps-2.5">#</th>
                    <th style="width: 210px;">Category <span class="text-danger">*</span></th>
                    <th style="min-width: 260px;">Item / Head Name <span class="text-danger">*</span></th>
                    <th style="width: 180px;" class="text-end">Amount (₹) <span class="text-danger">*</span></th>
                    <th style="min-width: 200px;">Remarks</th>
                    <th style="width: 40px;" class="text-center pe-2.5"></th>
                  </tr>
                </thead>
                <tbody id="itemsTableBody">
                  <tr class="item-row">
                    <td class="text-center text-muted fw-semibold item-row-num ps-2.5" style="font-size: 12px;">1</td>
                    <td>
                      <select name="items[0][category]" class="form-select form-select-sm" style="font-weight: 500;">
                        <option value="OPEX">OPEX (Operational)</option>
                        <option value="COPEX">COPEX / Capital</option>
                        <option value="Events & Conferences">Events & Conferences</option>
                        <option value="Software & Cloud">Software & Cloud</option>
                        <option value="Travel & Logistics">Travel & Logistics</option>
                        <option value="General Admin">General Admin</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" name="items[0][title]" class="form-control form-control-sm" placeholder="e.g. Operational Expenses" value="Operational Expenses" required>
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted font-monospace" style="border-color: #cbd5e1; font-size: 11px;">₹</span>
                        <input type="number" step="0.01" min="0" name="items[0][amount]" class="form-control form-control-sm item-amount-input font-monospace text-end fw-semibold" placeholder="0.00">
                      </div>
                    </td>
                    <td>
                      <input type="text" name="items[0][notes]" class="form-control form-control-sm" placeholder="Remarks">
                    </td>
                    <td class="text-center pe-2.5">
                      <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove line"><i class="bi bi-trash3 fs-6"></i></button>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="border-top" style="border-color: #e2e8f0 !important; background-color: #f8fafc;">
                  <tr>
                    <td colspan="3" class="py-2.5 ps-3">
                      <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-secondary border font-monospace" id="itemsCountBadge" style="font-size: 11px;">1 Item</span>
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 py-1 px-2.5 fw-semibold" id="btnSyncFromFooter" style="font-size: 11.5px; border-radius: 5px;">
                          <i class="bi bi-arrow-repeat"></i>
                          <span>Set Ceiling = Itemized Subtotal</span>
                        </button>
                      </div>
                    </td>
                    <td class="text-end py-2.5 fw-semibold text-secondary small">Itemized Subtotal:</td>
                    <td colspan="2" class="text-start py-2.5 pe-3 font-monospace fw-bold" style="font-size: 15px; color: #ea580c;" id="itemsSubtotalDisplay">₹0.00</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          {{-- Form Actions Footer --}}
          <div class="erp-form-footer d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
            <a href="{{ route('annual-budget') }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold" style="font-size: 14px; border-radius: 8px;">Cancel</a>
            <div class="d-flex align-items-center gap-3">
              <div class="text-end d-none d-sm-block">
                <span class="text-muted small me-1">Proposed Ceiling:</span>
                <span class="font-monospace fw-bold text-primary" style="font-size: 15px;" id="footerProposedDisplay">₹0.00</span>
              </div>
              <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 14px; border-radius: 8px;">
                <i class="bi bi-send-check"></i>
                <span>Submit Proposal</span>
              </button>
            </div>
          </div>

        </form>

      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let rowIndex = 1;
  const tbody = document.getElementById('itemsTableBody');
  const addBtn = document.getElementById('btnAddItemRow');
  const subtotalDisplay = document.getElementById('itemsSubtotalDisplay');
  const itemsCountBadge = document.getElementById('itemsCountBadge');
  const proposedInput = document.getElementById('proposedAmountInput');
  const footerProposedDisplay = document.getElementById('footerProposedDisplay');
  const btnSum = document.getElementById('btnSumFromItems');
  const btnSyncFromFooter = document.getElementById('btnSyncFromFooter');

  function formatInr(val) {
    return '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateRowNumbers() {
    const rows = tbody.querySelectorAll('.item-row');
    rows.forEach((row, idx) => {
      const numEl = row.querySelector('.item-row-num');
      if (numEl) numEl.textContent = idx + 1;
    });
    if (itemsCountBadge) {
      itemsCountBadge.textContent = `${rows.length} ${rows.length === 1 ? 'Item' : 'Items'}`;
    }
  }

  function calculateSubtotal() {
    let sum = 0;
    document.querySelectorAll('.item-amount-input').forEach(input => {
      const val = parseFloat(input.value) || 0;
      sum += val;
    });
    subtotalDisplay.textContent = formatInr(sum);
    updateRowNumbers();
    return sum;
  }

  function updateProposedSummary() {
    const val = parseFloat(proposedInput.value) || 0;
    if (footerProposedDisplay) {
      footerProposedDisplay.textContent = formatInr(val);
    }
  }

  function syncProposedCeiling() {
    const total = calculateSubtotal();
    if (total > 0) {
      proposedInput.value = total.toFixed(2);
      updateProposedSummary();
    }
  }

  if (proposedInput) {
    proposedInput.addEventListener('input', updateProposedSummary);
    updateProposedSummary();
  }

  if (btnSum) {
    btnSum.addEventListener('click', syncProposedCeiling);
  }

  if (btnSyncFromFooter) {
    btnSyncFromFooter.addEventListener('click', syncProposedCeiling);
  }

  tbody.addEventListener('input', function (e) {
    if (e.target.classList.contains('item-amount-input')) {
      calculateSubtotal();
    }
  });

  tbody.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-remove-row');
    if (btn) {
      const rows = tbody.querySelectorAll('.item-row');
      if (rows.length > 1) {
        btn.closest('tr').remove();
        calculateSubtotal();
      } else {
        alert('At least one line item is required.');
      }
    }
  });

  addBtn.addEventListener('click', function () {
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
      <td class="text-center text-muted fw-semibold item-row-num ps-2.5" style="font-size: 12px;">${rowIndex + 1}</td>
      <td>
        <select name="items[${rowIndex}][category]" class="form-select form-select-sm" style="font-weight: 500;">
          <option value="OPEX">OPEX (Operational)</option>
          <option value="COPEX">COPEX / Capital</option>
          <option value="Events & Conferences">Events & Conferences</option>
          <option value="Software & Cloud">Software & Cloud</option>
          <option value="Travel & Logistics">Travel & Logistics</option>
          <option value="General Admin">General Admin</option>
        </select>
      </td>
      <td>
        <input type="text" name="items[${rowIndex}][title]" class="form-control form-control-sm" placeholder="e.g. Outreach, Cloud, Supplies..." required>
      </td>
      <td>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light text-muted font-monospace" style="border-color: #cbd5e1; font-size: 11px;">₹</span>
          <input type="number" step="0.01" min="0" name="items[${rowIndex}][amount]" class="form-control form-control-sm item-amount-input font-monospace text-end fw-semibold" placeholder="0.00">
        </div>
      </td>
      <td>
        <input type="text" name="items[${rowIndex}][notes]" class="form-control form-control-sm" placeholder="Remarks">
      </td>
      <td class="text-center pe-2.5">
        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove line"><i class="bi bi-trash3 fs-6"></i></button>
      </td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
    calculateSubtotal();
  });

  calculateSubtotal();
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'annual-budget', crumb: 'Governance & Controls', title: 'Submit Annual Budget' });
@endsection
