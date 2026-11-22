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
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <ul class="mb-0 ps-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <style>
    /* Line items header */
    .pr-items-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding-bottom: 14px;
      margin-bottom: 16px;
      border-bottom: 1px solid #e2e8f0;
    }
    .pr-items-subtitle {
      margin: 2px 0 0;
      font-size: 12.5px;
      color: #64748b;
    }
    .pr-items-count {
      font-size: 11px;
      font-weight: 600;
      color: #475569;
      background-color: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 4px 9px;
      white-space: nowrap;
    }
    .pr-add-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 36px;
      padding: 0 14px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 7px;
    }

    /* Table shell */
    .pr-table-shell {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      background-color: #ffffff;
    }
    #prItemsTable {
      width: 100% !important;
      min-width: 0 !important;
      margin-bottom: 0 !important;
      table-layout: fixed !important;
      border-collapse: separate !important;
      border-spacing: 0 !important;
    }
    #prItemsTable thead th {
      background-color: #f8fafc !important;
      color: #64748b !important;
      font-size: 10px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.03em !important;
      padding: 11px 12px !important;
      border-bottom: 1px solid #e2e8f0 !important;
      white-space: normal !important;
      line-height: 1.3 !important;
      vertical-align: middle !important;
    }
    #prItemsTable tbody td {
      padding: 10px 10px !important;
      vertical-align: middle !important;
      border-bottom: 1px solid #eef2f7 !important;
      background-color: #ffffff !important;
    }
    #prItemsTable thead th:first-child,
    #prItemsTable tbody td:first-child,
    #prItemsTable thead th:last-child,
    #prItemsTable tbody td:last-child,
    #prItemsTable thead th:nth-child(4),
    #prItemsTable tbody td:nth-child(4),
    #prItemsTable thead th:nth-child(7),
    #prItemsTable tbody td:nth-child(7) {
      padding-left: 6px !important;
      padding-right: 6px !important;
    }
    #prItemsTable tbody tr:last-child td {
      border-bottom: 0 !important;
    }
    #prItemsTable tbody tr:hover td {
      background-color: #fbfcfe !important;
    }

    /* Inputs */
    #prItemsTable .form-control,
    #prItemsTable .form-select {
      height: 38px !important;
      font-size: 13px !important;
      padding: 7px 10px !important;
      border-radius: 7px !important;
      border: 1px solid #dbe2ea !important;
      background-color: #ffffff !important;
      color: #0f172a !important;
      box-shadow: none !important;
    }
    #prItemsTable .form-select {
      padding-right: 2.25rem !important;
    }
    #prItemsTable .form-control:focus,
    #prItemsTable .form-select:focus {
      border-color: #c8102e !important;
      box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.1) !important;
    }
    #prItemsTable .item-desc-input {
      height: 32px !important;
      font-size: 12px !important;
      background-color: #f8fafc !important;
      color: #475569 !important;
    }
    #prItemsTable .item-name-input {
      height: 36px !important;
    }
    #prItemsTable .item-qty-input,
    #prItemsTable .item-gst-input,
    #prItemsTable .item-cost-input {
      padding-left: 6px !important;
      padding-right: 6px !important;
    }
    #prItemsTable input[type="number"]::-webkit-outer-spin-button,
    #prItemsTable input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    #prItemsTable input[type="number"] {
      -moz-appearance: textfield;
      appearance: textfield;
    }

    /* Currency / percent affixes */
    .pr-money .input-group-text {
      height: 38px;
      padding: 0 10px;
      font-size: 12px;
      font-weight: 600;
      color: #64748b;
      background-color: #f8fafc;
      border: 1px solid #dbe2ea;
    }
    .pr-money:focus-within .input-group-text {
      border-color: #c8102e;
    }
    .pr-money-prefix .input-group-text {
      border-right: 0;
      border-radius: 7px 0 0 7px;
    }
    #prItemsTable .pr-money-prefix .form-control {
      border-left: 0 !important;
      border-radius: 0 7px 7px 0 !important;
    }
    .pr-money-suffix .input-group-text {
      border-left: 0;
      border-radius: 0 7px 7px 0;
    }
    #prItemsTable .pr-money-suffix .form-control {
      border-right: 0 !important;
      border-radius: 7px 0 0 7px !important;
    }

    /* Row number + line total + delete */
    .pr-row-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      border-radius: 6px;
      background-color: #f1f5f9;
      color: #64748b;
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 11.5px;
      font-weight: 700;
    }
    .pr-line-total {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 13.5px;
      font-weight: 700;
      color: #0f172a;
      white-space: nowrap;
    }
    .pr-remove-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      padding: 0;
      font-size: 13px;
      color: #94a3b8;
      background: transparent;
      border: 1px solid transparent;
      border-radius: 7px;
      transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .pr-remove-btn:hover {
      color: #dc2626;
      background-color: #fef2f2;
      border-color: #fecaca;
    }
    .expense-type-select[data-expense="copex"],
    .expense-type-select[data-expense="capex"] {
      background-color: #fffbeb !important;
      border-color: #fcd34d !important;
      color: #b45309 !important;
      font-weight: 600 !important;
    }
    .expense-type-select[data-expense="opex"] {
      background-color: #f8fafc !important;
      color: #475569 !important;
      font-weight: 600 !important;
    }

    /* Summary band */
    .pr-summary {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      gap: 14px 24px;
      padding: 14px 16px;
      background-color: #f8fafc;
      border-top: 1px solid #e2e8f0;
    }
    .pr-summary-meta {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px 12px;
    }
    .pr-approval-note {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #64748b;
    }
    .pr-summary-totals {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 270px;
      margin-left: auto;
    }
    .pr-total-line {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 24px;
      font-size: 12.5px;
      color: #64748b;
    }
    .pr-total-line span:last-child {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-weight: 600;
      color: #334155;
    }
    .pr-total-line.pr-grand {
      margin-top: 4px;
      padding-top: 8px;
      border-top: 1px dashed #dbe2ea;
      font-size: 13px;
      font-weight: 700;
      color: #0f172a;
    }
    .pr-total-line.pr-grand span:last-child {
      font-size: 16px;
      font-weight: 800;
      color: #ea580c;
    }
    #budgetWarningBox {
      font-size: 12px !important;
    }
    #classificationBadge {
      border-radius: 6px !important;
      letter-spacing: 0.04em !important;
    }
    .item-type-badge,
    .cost-fixed-hint {
      display: none !important;
    }
  </style>

  <div class="row g-4">
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">

        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-cart-plus"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Create Purchase Requisition</h1>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:11px; font-weight:600; letter-spacing:0.3px; border-color:#e2e8f0 !important;">PROCUREMENT INITIATION</span>
        </div>

        <form method="POST" action="{{ route('requisition.store') }}" id="prForm">
          @csrf

          <!-- Section 1: Requisition Information -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Requisition Information</h2>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="vendor-form-label">Requisition Title / Subject <span class="text-muted small fw-normal">(Optional)</span></label>
                <input type="text" name="title" class="form-control vendor-form-control" value="{{ old('title') }}" placeholder="e.g. IT Equipment Upgrade, Server Replacement...">
              </div>

              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="vendor-form-label mb-0">Department <span class="text-danger">*</span></label>
                  <span id="deptBudgetBadge" class="badge bg-light text-muted border font-monospace py-0.5 px-2" style="font-size: 11px;">
                    Select Department
                  </span>
                </div>
                @php
                  $userDept = auth()->user()?->department;
                  $defaultDept = old('department', ($userDept && $departments->contains($userDept)) ? $userDept : '');
                @endphp
                <select name="department" id="departmentSelect" class="form-select vendor-form-control" required>
                  <option value="">Select department…</option>
                  @foreach ($departments as $dept)
                    <option value="{{ $dept }}" {{ $defaultDept === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="vendor-form-label mb-0">Associated Event / Project Budget</label>
                  <span id="projBudgetBadge" class="badge bg-light text-muted border font-monospace py-0.5 px-2" style="font-size: 11px; display: none;"></span>
                </div>
                <select name="project_budget_id" id="projectBudgetSelect" class="form-select vendor-form-control">
                  <option value="">None / General Department Requisition</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="vendor-form-label">Event / Project Title (Display) <span class="text-muted small fw-normal">(Optional)</span></label>
                <input type="text" name="event_name" id="eventNameInput" class="form-control vendor-form-control" value="{{ old('event_name') }}" placeholder="Auto-fills from project, or enter custom event...">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">Required By Date</label>
                <input type="date" name="required_by" class="form-control vendor-form-control" min="{{ now()->toDateString() }}" value="{{ old('required_by') }}">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">Region / Location <span class="text-muted small fw-normal">(Optional)</span></label>
                <input type="text" name="region" class="form-control vendor-form-control" value="{{ old('region') }}" placeholder="e.g. North Region, Delhi HQ...">
              </div>

              <div class="col-md-4 d-flex align-items-center">
                <div class="p-2.5 px-3 rounded-2 border w-100 bg-light" style="border-color: #e2e8f0 !important; margin-top: 19px;">
                  <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="soleSourceCheck" name="sole_source" value="1" {{ old('sole_source') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold small text-dark ms-1" for="soleSourceCheck">
                      Sole Source Procurement
                    </label>
                  </div>
                  <div class="text-muted" style="font-size: 11px; margin-left: 2.3rem;">Single-vendor constraint / proprietary</div>
                </div>
              </div>

              <div class="col-12">
                <label class="vendor-form-label">Special Instructions / Scope <span class="text-muted small fw-normal">(Optional)</span></label>
                <textarea name="special_instructions" class="form-control vendor-form-control" rows="2" placeholder="Provide overall context, business objective, delivery timelines, vendor constraints, or installation requirements...">{{ old('special_instructions', old('description')) }}</textarea>
              </div>
            </div>
          </div>

          <!-- Section 2: Line Items -->
          <div class="mb-4">
            <div class="pr-items-header">
              <div class="d-flex align-items-center gap-2.5">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-list-check"></i>
                </div>
                <div>
                  <h2 class="vendor-form-section-title mb-0">Line Items</h2>
                  <p class="pr-items-subtitle">Add each item with its classification, quantity and estimated cost.</p>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="pr-items-count" id="itemsCountBadge">1 Item</span>
                <button type="button" class="btn btn-primary pr-add-btn shadow-sm" id="addItemBtn">
                  <i class="bi bi-plus-lg"></i>
                  <span>Add Item</span>
                </button>
              </div>
            </div>

            <div class="pr-table-shell">
              <div class="table-responsive">
                <table class="table align-middle mb-0" id="prItemsTable">
                  <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 24.5%;">
                    <col style="width: 11.5%;">
                    <col style="width: 9.5%;">
                    <col style="width: 8.5%;">
                    <col style="width: 12.5%;">
                    <col style="width: 11.5%;">
                    <col style="width: 13%;">
                    <col style="width: 5%;">
                  </colgroup>
                  <thead>
                    <tr>
                      <th class="text-center">#</th>
                      <th>Item &amp; Specifications <span class="text-danger">*</span></th>
                      <th>Classification</th>
                      <th class="text-center">Qty <span class="text-danger">*</span></th>
                      <th class="text-center">Unit</th>
                      <th class="text-end">Unit Cost (₹) <span class="text-danger">*</span></th>
                      <th class="text-center">GST %</th>
                      <th class="text-end">Total (Incl. GST)</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody id="itemRows"></tbody>
                </table>
              </div>

              <div class="pr-summary">
                <div class="pr-summary-meta">
                  <span id="classificationBadge" class="badge bg-success text-white px-2 py-0.5 fw-bold text-uppercase" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.04em;">OPEX</span>
                  <span class="pr-approval-note">
                    <i class="bi bi-diagram-3"></i>
                    <span id="approvalRuleNotice">Requisition under ₹5L concludes at CFO (Level 3)</span>
                  </span>
                  <div id="budgetWarningBox" class="d-none py-1 px-2.5 rounded-2 border">
                    <i class="bi bi-shield-check text-success" id="budgetStatusIcon"></i>
                    <span class="fw-semibold" id="budgetStatusTitle">Budget Verified</span>
                    <span class="text-muted">(Avail: <span class="fw-bold font-monospace" id="budgetRemainingAfter">₹0.00</span>)</span>
                  </div>
                </div>
                <div class="pr-summary-totals">
                  <div class="pr-total-line">
                    <span>Subtotal (Excl. GST)</span>
                    <span id="liveSubtotal">₹0.00</span>
                  </div>
                  <div class="pr-total-line">
                    <span>Est. GST</span>
                    <span id="liveGst">₹0.00</span>
                  </div>
                  <div class="pr-total-line pr-grand">
                    <span>Grand Total</span>
                    <span id="liveGrandTotal">₹0.00</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <template id="itemRowTemplate">
            <tr class="item-row">
              <td class="text-center">
                <span class="pr-row-num item-row-num">1</span>
              </td>
              <td>
                <div class="pr-item-cell d-flex flex-column gap-1.5">
                  <select class="form-select form-select-sm catalog-picker" style="font-weight: 500;">
                    <option value="">-- Select Catalog Item (Optional) --</option>
                    @if (!empty($itemCatalogs))
                      <optgroup label="── CapEx Capital Assets (Mandatory SG Approval) ──">
                        @foreach ($itemCatalogs->filter(fn ($c) => $c->isCopex()) as $cat)
                          <option value="{{ $cat->id }}" 
                                  data-name="{{ $cat->name }}" 
                                  data-type="capex" 
                                  data-price="{{ $cat->fixed_price }}" 
                                  data-unit="{{ $cat->unit }}" 
                                  data-gst="{{ $cat->gst_percent }}" 
                                  data-desc="{{ $cat->description }}">
                            [CapEx] {{ $cat->name }} — ₹{{ number_format((float)$cat->fixed_price, 2) }}
                          </option>
                        @endforeach
                      </optgroup>
                      <optgroup label="── OpEx Standard Operating Expenses ──">
                        @foreach ($itemCatalogs->filter(fn ($c) => $c->isOpex()) as $cat)
                          <option value="{{ $cat->id }}" 
                                  data-name="{{ $cat->name }}" 
                                  data-type="opex" 
                                  data-price="{{ $cat->fixed_price }}" 
                                  data-unit="{{ $cat->unit }}" 
                                  data-gst="{{ $cat->gst_percent }}" 
                                  data-desc="{{ $cat->description }}">
                            [OpEx] {{ $cat->name }} — ₹{{ number_format((float)$cat->fixed_price, 2) }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endif
                    <option value="__custom__">✏️ Custom Item (Enter Name Manually)</option>
                  </select>
                  <input type="text" name="items[__i__][item_name]" class="form-control form-control-sm item-name-input d-none" placeholder="Type custom item name...">
                  <input type="hidden" name="items[__i__][item_catalog_id]" class="item-catalog-id-input">
                  <input type="text" name="items[__i__][description]" class="form-control form-control-sm item-desc-input" placeholder="Specs, brand, model, or scope (optional)">
                </div>
              </td>
              <td>
                <select name="items[__i__][expense_type]" class="form-select form-select-sm expense-type-select" data-expense="">
                  <option value="">-- None --</option>
                  <option value="opex">OpEx</option>
                  <option value="capex">CapEx</option>
                </select>
              </td>
              <td>
                <input type="number" name="items[__i__][quantity]" class="form-control form-control-sm item-qty-input text-center font-monospace fw-semibold" value="1" min="1" required>
              </td>
              <td>
                <input type="text" name="items[__i__][unit]" class="form-control form-control-sm item-unit-input text-center text-secondary" placeholder="Units" value="Units">
              </td>
              <td>
                <div class="input-group input-group-sm pr-money pr-money-prefix">
                  <span class="input-group-text">₹</span>
                  <input type="number" name="items[__i__][unit_cost]" class="form-control form-control-sm item-cost-input font-monospace text-end fw-semibold" placeholder="0.00" step="0.01" min="0">
                </div>
              </td>
              <td>
                <div class="input-group input-group-sm pr-money pr-money-suffix">
                  <input type="number" name="items[__i__][gst_percent]" class="form-control form-control-sm item-gst-input text-center font-monospace" value="18" step="0.01" min="0" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </td>
              <td class="text-end">
                <span class="pr-line-total line-total-display">₹0.00</span>
              </td>
              <td class="text-center">
                <button type="button" class="pr-remove-btn remove-item-btn" title="Remove item">
                  <i class="bi bi-trash3"></i>
                </button>
              </td>
            </tr>
          </template>

          <!-- Form Actions -->
          <div class="erp-form-footer d-flex align-items-center justify-content-between mt-4">
            <a href="{{ route('requisition') }}" class="btn btn-outline-secondary px-4 py-2.5 fw-semibold" style="font-size: 14px; height: 44px; border-radius: 8px;">Cancel</a>
            <button type="submit" id="submitPrBtn" class="btn btn-primary px-4 py-2.5 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 14.5px; height: 44px; border-radius: 8px;">
              <i class="bi bi-send-check"></i>
              <span>Submit Requisition for Approval</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
@php
  $projectCatalogJson = $projectBudgets->map(fn ($pb) => [
    'id' => (string) $pb->id,
    'name' => $pb->project_name,
    'department' => $pb->department,
    'allocated' => (float) $pb->allocated_amount,
    'utilised' => (float) $pb->utilised_amount,
    'remaining' => (float) $pb->remainingAmount(),
  ])->values();
  $initialProjectIdJson = old('project_budget_id') ? (string) old('project_budget_id') : '';
  $oldItemsJson = array_values((array) old('items', []));
@endphp
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'New Purchase Requisition' });

(function () {
const departmentBudgets = @json($departmentBudgets ?? []);
const allProjects = @json($projectCatalogJson);
const initialProjectId = @json($initialProjectIdJson);
const oldItems = @json($oldItemsJson);
const rowsWrap = document.getElementById('itemRows');
const template = document.getElementById('itemRowTemplate');
const deptSelect = document.getElementById('departmentSelect');
const deptBadge = document.getElementById('deptBudgetBadge');
const projectSelect = document.getElementById('projectBudgetSelect');
const projBadge = document.getElementById('projBudgetBadge');
const eventNameInput = document.getElementById('eventNameInput');
const budgetBox = document.getElementById('budgetWarningBox');
const submitBtn = document.getElementById('submitPrBtn');
let i = 0;
let lastAutoFilledEventName = '';

function formatInr(val) {
  return '₹' + Number(val).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function getSelectedProject() {
  const id = projectSelect?.value || '';
  if (!id) return null;
  return allProjects.find(p => p.id === String(id)) || null;
}

function populateProjectsForDepartment(preferredId = '') {
  if (!projectSelect) return;
  const selectedDept = (deptSelect?.value || '').trim();
  const prevValue = preferredId || projectSelect.value || '';

  projectSelect.innerHTML = '';
  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  if (!selectedDept) {
    defaultOpt.textContent = 'Select a department first (or General Requisition)';
    projectSelect.appendChild(defaultOpt);
    if (eventNameInput && lastAutoFilledEventName && eventNameInput.value === lastAutoFilledEventName) {
      eventNameInput.value = '';
      lastAutoFilledEventName = '';
    }
    return;
  }

  const matching = allProjects.filter(p => (p.department || '').toLowerCase() === selectedDept.toLowerCase());
  defaultOpt.textContent = matching.length > 0
    ? 'None / General Department Requisition'
    : `None / General ${selectedDept} Requisition (no project budgets)`;
  projectSelect.appendChild(defaultOpt);

  let matchedPrev = false;
  matching.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = `${p.name} (₹${Number(p.remaining).toLocaleString('en-IN', { maximumFractionDigits: 0 })} avail)`;
    if (p.id === String(prevValue)) {
      opt.selected = true;
      matchedPrev = true;
    }
    projectSelect.appendChild(opt);
  });

  if (!matchedPrev) {
    projectSelect.value = '';
    if (eventNameInput && lastAutoFilledEventName && eventNameInput.value === lastAutoFilledEventName) {
      eventNameInput.value = '';
      lastAutoFilledEventName = '';
    }
  }
}

function updateLiveTotals() {
  const rows = rowsWrap.querySelectorAll('.item-row');
  let subtotal = 0;
  let totalGst = 0;
  let hasCopex = false;

  rows.forEach((row, idx) => {
    const numEl = row.querySelector('.item-row-num');
    if (numEl) numEl.textContent = idx + 1;

    const qtyInput = row.querySelector('input[name*="[quantity]"]');
    const costInput = row.querySelector('input[name*="[unit_cost]"]');
    const gstInput = row.querySelector('input[name*="[gst_percent]"]');
    const expSelect = row.querySelector('select[name*="[expense_type]"]');
    const lineTotalEl = row.querySelector('.line-total-display');

    const expVal = (expSelect?.value || '').toLowerCase();
    const isCopex = expVal === 'capex' || expVal === 'copex';
    if (expSelect) expSelect.setAttribute('data-expense', isCopex ? 'capex' : (expVal === 'opex' ? 'opex' : ''));
    if (isCopex) hasCopex = true;

    const qty = Math.max(0, parseFloat(qtyInput?.value) || 0);
    const unitCost = Math.max(0, parseFloat(costInput?.value) || 0);
    const gstRate = Math.max(0, parseFloat(gstInput?.value) || 0);

    const lineBase = qty * unitCost;
    const lineGst = lineBase * (gstRate / 100);
    const lineTotal = lineBase + lineGst;

    if (lineTotalEl) lineTotalEl.textContent = formatInr(lineTotal);

    subtotal += lineBase;
    totalGst += lineGst;
  });

  const grandTotal = Math.round((subtotal + totalGst) * 100) / 100;

  const countEl = document.getElementById('itemsCountBadge');
  const classBadge = document.getElementById('classificationBadge');
  const alertBox = document.getElementById('approvalRuleAlertBox');
  const alertIcon = document.getElementById('approvalRuleIcon');
  const alertHeading = document.getElementById('approvalRuleHeading');
  const alertNotice = document.getElementById('approvalRuleNotice');
  const subtotalEl = document.getElementById('liveSubtotal');
  const gstEl = document.getElementById('liveGst');
  const grandTotalEl = document.getElementById('liveGrandTotal');

  if (countEl) countEl.textContent = `${rows.length} ${rows.length === 1 ? 'Item' : 'Items'}`;
  if (subtotalEl) subtotalEl.textContent = formatInr(subtotal);
  if (gstEl) gstEl.textContent = formatInr(totalGst);
  if (grandTotalEl) grandTotalEl.textContent = formatInr(grandTotal);

  if (hasCopex) {
    if (classBadge) {
      classBadge.className = 'badge bg-warning text-dark border-0 px-2 py-0.5 fw-bold text-uppercase';
      classBadge.style.fontSize = '10.5px';
      classBadge.style.letterSpacing = '0.04em';
      classBadge.style.borderRadius = '4px';
      classBadge.textContent = 'CapEx';
    }
    if (alertBox) {
      alertBox.style.background = '#fffbeb';
      alertBox.style.borderColor = '#fde68a';
    }
    if (alertIcon) alertIcon.className = 'bi bi-shield-lock-fill fs-5 text-warning';
    if (alertHeading) alertHeading.textContent = 'Capital Expenditure (CapEx) Policy Enforced';
    if (alertNotice) alertNotice.textContent = 'Mandatory SG approval enforced for capital items regardless of amount';
  } else if (grandTotal >= 500000) {
    if (classBadge) {
      classBadge.className = 'badge bg-primary text-white border-0 px-2 py-0.5 fw-bold text-uppercase';
      classBadge.style.fontSize = '10.5px';
      classBadge.style.letterSpacing = '0.04em';
      classBadge.style.borderRadius = '4px';
      classBadge.textContent = 'OPEX';
    }
    if (alertBox) {
      alertBox.style.background = '#eff6ff';
      alertBox.style.borderColor = '#bfdbfe';
    }
    if (alertIcon) alertIcon.className = 'bi bi-info-circle-fill fs-5 text-primary';
    if (alertHeading) alertHeading.textContent = 'High-Value Operating Expense (≥ ₹5 Lakh)';
    if (alertNotice) alertNotice.textContent = 'Total commitment reaches ₹5L ceiling, approval escalates to SG (Level 4)';
  } else {
    if (classBadge) {
      classBadge.className = 'badge bg-success text-white border-0 px-2 py-0.5 fw-bold text-uppercase';
      classBadge.style.fontSize = '10.5px';
      classBadge.style.letterSpacing = '0.04em';
      classBadge.style.borderRadius = '4px';
      classBadge.textContent = 'OPEX';
    }
    if (alertBox) {
      alertBox.style.background = '#f0fdf4';
      alertBox.style.borderColor = '#bbf7d0';
    }
    if (alertIcon) alertIcon.className = 'bi bi-check-circle-fill fs-5 text-success';
    if (alertHeading) alertHeading.textContent = 'Operating Expense (OPEX) Workflow';
    if (alertNotice) alertNotice.textContent = 'Requisition under ₹5L concludes at CFO (Level 3)';
  }

  checkRealtimeBudget(grandTotal);
}

function checkRealtimeBudget(grandTotal) {
  const selectedDept = deptSelect?.value;
  if (!selectedDept || !budgetBox) {
    if (deptBadge) {
      deptBadge.className = 'badge bg-light text-muted border font-monospace py-0.5 px-2';
      deptBadge.textContent = 'Select Department';
    }
    if (projBadge) projBadge.style.display = 'none';
    budgetBox.classList.add('d-none');
    if (submitBtn) submitBtn.disabled = false;
    return;
  }

  const deptData = departmentBudgets[selectedDept] || { allocated: 0, utilised: 0, available: 0 };
  const allocatedBudget = Math.max(0, Number(deptData.allocated || 0));
  const utilisedBudget = Math.max(0, Number(deptData.utilised || 0));
  const availableBudget = Math.max(0, Number(deptData.available || 0));
  const remainingAfter = availableBudget - grandTotal;
  const projectedUtilisationPct = allocatedBudget > 0 ? ((utilisedBudget + grandTotal) / allocatedBudget) * 100 : 0;

  const proj = getSelectedProject();
  const hasProject = proj !== null;
  const projectRemaining = hasProject ? Math.max(0, Number(proj.remaining || 0)) : 0;
  const projectAllocated = hasProject ? Math.max(0, Number(proj.allocated || 0)) : 0;
  const projectUtilised = hasProject ? Math.max(0, Number(proj.utilised || 0)) : 0;
  const projectRemainingAfter = projectRemaining - grandTotal;
  const projectedProjPct = (hasProject && projectAllocated > 0) ? ((projectUtilised + grandTotal) / projectAllocated) * 100 : 0;
  const projectName = hasProject ? proj.name : '';

  // Update project badge
  if (projBadge) {
    if (hasProject) {
      projBadge.style.display = 'inline-block';
      if (grandTotal > projectRemaining) {
        projBadge.textContent = `Project Headroom: ${formatInr(projectRemaining)} (Overrun)`;
        projBadge.className = 'badge bg-danger text-white border-0 font-monospace py-0.5 px-2';
      } else if (projectedProjPct >= 90) {
        projBadge.textContent = `Project Avail: ${formatInr(projectRemaining)} (${projectedProjPct.toFixed(1)}% · 90%+ Alert)`;
        projBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace py-0.5 px-2';
      } else {
        projBadge.textContent = `Project Headroom: ${formatInr(projectRemaining)}`;
        projBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle font-monospace py-0.5 px-2';
      }
    } else {
      projBadge.style.display = 'none';
    }
  }

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
  const dualRemainingText = hasProject
    ? `Dept: ${formatInr(remainingAfter)} · Project: ${formatInr(projectRemainingAfter)}`
    : formatInr(remainingAfter);

  if (grandTotal > availableBudget) {
    budgetBox.className = 'd-inline-flex align-items-center gap-2 py-1 px-2.5 rounded-2 border border-danger bg-danger-subtle text-danger';
    if (icon) icon.className = 'bi bi-x-octagon-fill text-danger';
    if (title) title.textContent = 'Department Budget Limit Exceeded';
    if (remainingEl) {
      remainingEl.textContent = `Deficit: -${formatInr(Math.abs(remainingAfter))}`;
      remainingEl.className = 'fw-bold font-monospace text-danger ms-1';
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.title = `Total exceeds ${selectedDept} available funds`;
    }
  } else if (hasProject && grandTotal > projectRemaining) {
    budgetBox.className = 'd-inline-flex align-items-center gap-2 py-1 px-2.5 rounded-2 border border-danger bg-danger-subtle text-danger';
    if (icon) icon.className = 'bi bi-x-octagon-fill text-danger';
    if (title) title.textContent = 'Project Budget Exceeded';
    if (remainingEl) {
      remainingEl.textContent = `Project Deficit: -${formatInr(Math.abs(projectRemainingAfter))}`;
      remainingEl.className = 'fw-bold font-monospace text-danger ms-1';
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.title = `Total exceeds ${projectName} available headroom`;
    }
  } else if (projectedUtilisationPct >= 90 || (hasProject && projectedProjPct >= 90)) {
    budgetBox.className = 'd-inline-flex align-items-center gap-2 py-1 px-2.5 rounded-2 border border-warning bg-warning-subtle text-warning-emphasis';
    if (icon) icon.className = 'bi bi-exclamation-triangle-fill text-warning';
    if (title) title.textContent = '90% Budget Utilisation Alert';
    if (remainingEl) {
      remainingEl.textContent = dualRemainingText;
      remainingEl.className = 'fw-bold font-monospace text-warning-emphasis ms-1';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.title = '';
    }
  } else {
    budgetBox.className = 'd-inline-flex align-items-center gap-2 py-1 px-2.5 rounded-2 border border-success-subtle bg-success-subtle text-success';
    if (icon) icon.className = 'bi bi-shield-check text-success';
    if (title) title.textContent = 'Budget Clearance Verified';
    if (remainingEl) {
      remainingEl.textContent = dualRemainingText;
      remainingEl.className = 'fw-bold font-monospace text-success ms-1';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.title = '';
    }
  }
}

function addRow(data = null) {
  const html = template.innerHTML.replaceAll('__i__', String(i++));
  const wrap = document.createElement('tbody');
  wrap.innerHTML = html.trim();
  const rowEl = wrap.firstElementChild;

  const picker = rowEl.querySelector('.catalog-picker');
  const nameInput = rowEl.querySelector('.item-name-input');
  const idInput = rowEl.querySelector('.item-catalog-id-input');
  const fixedHint = rowEl.querySelector('.cost-fixed-hint');
  const unitInput = rowEl.querySelector('.item-unit-input');

  if (data) {
    if (data.item_catalog_id) {
      if (idInput) idInput.value = data.item_catalog_id;
      if (picker) picker.value = data.item_catalog_id;
      if (nameInput) {
        nameInput.value = data.item_name || '';
        nameInput.classList.add('d-none');
        nameInput.required = false;
      }
      if (fixedHint) fixedHint.textContent = '(Fixed)';
    } else if (data.item_name) {
      if (picker) picker.value = '__custom__';
      if (nameInput) {
        nameInput.value = data.item_name;
        nameInput.classList.remove('d-none');
        nameInput.required = true;
      }
      if (idInput) idInput.value = '';
      if (fixedHint) fixedHint.textContent = '(Custom)';
      if (unitInput && !unitInput.value) unitInput.value = 'Units';
    }

    if (data.expense_type !== undefined && data.expense_type !== null) {
      const el = rowEl.querySelector('select[name*="[expense_type]"]');
      if (el) el.value = data.expense_type;
    }
    if (data.quantity !== undefined && data.quantity !== null) {
      const el = rowEl.querySelector('input[name*="[quantity]"]');
      if (el) el.value = data.quantity;
    }
    if (data.unit !== undefined && data.unit !== null) {
      const el = rowEl.querySelector('input[name*="[unit]"]');
      if (el) el.value = data.unit;
    }
    if (data.unit_cost !== undefined && data.unit_cost !== null) {
      const el = rowEl.querySelector('input[name*="[unit_cost]"]');
      if (el) el.value = data.unit_cost;
    }
    if (data.gst_percent !== undefined && data.gst_percent !== null) {
      const el = rowEl.querySelector('input[name*="[gst_percent]"]');
      if (el) el.value = data.gst_percent;
    }
    if (data.description !== undefined && data.description !== null) {
      const el = rowEl.querySelector('.item-desc-input') || rowEl.querySelector('[name*="[description]"]');
      if (el) el.value = data.description;
    }
  }

  rowsWrap.appendChild(rowEl);
  updateLiveTotals();
}

rowsWrap.addEventListener('change', (e) => {
  const picker = e.target.closest('.catalog-picker');
  if (picker) {
    const row = picker.closest('.item-row');
    const selectedOpt = picker.options[picker.selectedIndex];
    const nameInput = row.querySelector('.item-name-input');
    const idInput = row.querySelector('.item-catalog-id-input');
    const typeSelect = row.querySelector('.expense-type-select');
    const costInput = row.querySelector('.item-cost-input');
    const unitInput = row.querySelector('.item-unit-input');
    const gstInput = row.querySelector('.item-gst-input');
    const descInput = row.querySelector('.item-desc-input');
    const fixedHint = row.querySelector('.cost-fixed-hint');

    if (picker.value === '__custom__') {
      if (nameInput) {
        nameInput.classList.remove('d-none');
        nameInput.value = '';
        nameInput.required = true;
        nameInput.focus();
      }
      if (idInput) idInput.value = '';
      if (fixedHint) fixedHint.textContent = '(Custom)';
      if (unitInput && !unitInput.value) unitInput.value = 'Units';
    } else if (picker.value) {
      const name = selectedOpt.getAttribute('data-name');
      const type = selectedOpt.getAttribute('data-type');
      const price = selectedOpt.getAttribute('data-price');
      const unit = selectedOpt.getAttribute('data-unit');
      const gst = selectedOpt.getAttribute('data-gst');
      const desc = selectedOpt.getAttribute('data-desc');

      if (nameInput) {
        nameInput.value = name || '';
        nameInput.classList.add('d-none');
        nameInput.required = false;
      }
      if (idInput) idInput.value = picker.value;
      if (typeSelect && type) typeSelect.value = type;
      if (costInput && price !== null) costInput.value = parseFloat(price).toFixed(2);
      if (unitInput && unit) unitInput.value = unit;
      if (gstInput && gst !== null) gstInput.value = parseFloat(gst).toFixed(2);
      if (descInput && desc) descInput.value = desc;
      if (fixedHint) fixedHint.textContent = '(Fixed)';
    } else {
      if (nameInput) {
        nameInput.classList.add('d-none');
        nameInput.value = '';
        nameInput.required = false;
      }
      if (idInput) idInput.value = '';
      if (fixedHint) fixedHint.textContent = '';
    }
    updateLiveTotals();
  }

  if (e.target.closest('.expense-type-select')) {
    updateLiveTotals();
  }
});

document.getElementById('addItemBtn')?.addEventListener('click', () => addRow());
document.getElementById('addItemBtn2')?.addEventListener('click', () => addRow());

deptSelect?.addEventListener('change', () => {
  populateProjectsForDepartment();
  updateLiveTotals();
});

projectSelect?.addEventListener('change', () => {
  const proj = getSelectedProject();
  if (eventNameInput) {
    if (proj) {
      if (!eventNameInput.value.trim() || eventNameInput.value === lastAutoFilledEventName) {
        eventNameInput.value = proj.name;
        lastAutoFilledEventName = proj.name;
      }
    } else if (lastAutoFilledEventName && eventNameInput.value === lastAutoFilledEventName) {
      eventNameInput.value = '';
      lastAutoFilledEventName = '';
    }
  }
  updateLiveTotals();
});

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

document.getElementById('prForm')?.addEventListener('submit', (e) => {
  const rows = rowsWrap.querySelectorAll('.item-row');
  for (let idx = 0; idx < rows.length; idx++) {
    const row = rows[idx];
    const nameInput = row.querySelector('.item-name-input');
    const picker = row.querySelector('.catalog-picker');
    if (!nameInput || !nameInput.value.trim()) {
      e.preventDefault();
      alert(`Please select an item from the catalog or enter a custom item name for line item #${idx + 1}.`);
      if (picker && picker.value !== '__custom__') {
        picker.focus();
      } else if (nameInput) {
        nameInput.focus();
      }
      return false;
    }
  }
});

// Initial department-filtered project dropdown & restore old items (or start cleanly with 1 row)
populateProjectsForDepartment(initialProjectId);
if (Array.isArray(oldItems) && oldItems.length > 0) {
  oldItems.forEach(item => addRow(item));
} else {
  addRow();
}
})();
@endsection