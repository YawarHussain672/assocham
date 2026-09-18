@extends('layouts.app')

@section('title', 'File Exception')

@php
  $typeLabels = [
    'single_quotation' => 'Single Quotation (urgent procurement, sole quote)',
    'budget_exceed' => 'Budget Exceed Override',
    'po_force_close' => 'Purchase Order Force Close',
    'grn_reversal' => 'GRN Goods Receipt Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Compliance Check',
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
<div class="view-wrap" id="pageContent">

  <a href="{{ route('exceptions') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Exception Handling</span>
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
    <!-- Left: Exception Filing Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(176,38,58,0.12),rgba(176,38,58,0.04)); color:var(--maroon); border-color:rgba(176,38,58,0.2);">
            <i class="bi bi-shield-exclamation"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">File Operational Governance Exception</h2>
            <div class="text-muted" style="font-size:13px;">Request authorized deviation from standard procurement policies, budgets, or logistics rules.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('exceptions.store') }}" class="mt-3" id="excForm">
          @csrf
          <input type="hidden" name="reference_type" id="referenceTypeInput" value="">

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-diagram-2 text-muted"></i> Exception Category *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-patch-question"></i></span>
              <select name="exception_type" id="typeSelect" class="form-select" required>
                <option value="">Select exception category…</option>
                @foreach ($types as $type)
                  <option value="{{ $type }}" data-approver="{{ $approverFor[$type] }}" {{ old('exception_type') === $type ? 'selected' : '' }}>
                    {{ $typeLabels[$type] }} — Decision Authority: {{ $approverFor[$type] }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mb-3" id="poWrap" style="display:none;">
            <label class="form-label"><i class="bi bi-file-earmark-check text-muted"></i> Target Purchase Order (Pending approval) *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-receipt"></i></span>
              <select id="poSelect" class="form-select">
                <option value="">Select PO to force close…</option>
                @foreach ($purchaseOrders as $po)
                  <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->vendor->company_name }} (₹{{ number_format($po->amount_incl_gst, 2) }})</option>
                @endforeach
              </select>
            </div>
            @if ($purchaseOrders->isEmpty())
              <div class="text-muted mt-1" style="font-size:12px;">No active POs currently eligible for force closure.</div>
            @endif
          </div>

          <div class="mb-3" id="grnWrap" style="display:none;">
            <label class="form-label"><i class="bi bi-box-seam text-muted"></i> Target Goods Receipt Note (Approved, to reverse) *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-arrow-counterclockwise"></i></span>
              <select id="grnSelect" class="form-select">
                <option value="">Select GRN to reverse…</option>
                @foreach ($grns as $grn)
                  <option value="{{ $grn->id }}">{{ $grn->grn_number }} — {{ $grn->purchaseOrder->po_number }} ({{ $grn->received_qty }} received)</option>
                @endforeach
              </select>
            </div>
            @if ($grns->isEmpty())
              <div class="text-muted mt-1" style="font-size:12px;">No approved GRNs available to reverse.</div>
            @endif
          </div>

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-file-earmark-text text-muted"></i> Justification &amp; Business Urgency *</label>
            <textarea name="justification" class="form-control" rows="4" placeholder="Provide full justification, operational context, and reasons why standard procedures cannot be observed…" required>{{ old('justification') }}</textarea>
            <div class="text-muted mt-1" style="font-size:12px;">All filed exceptions are permanently recorded in the immutable institutional Audit Log.</div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('exceptions') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
              <i class="bi bi-shield-lock"></i>
              <span>File Exception Request</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Exception Matrix Sidebar (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>Authority Matrix</span>
          </div>
          <div class="p-3 d-flex flex-column gap-3" style="font-size:12.5px;">
            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#fbf0dc; color:#8a5a12; font-weight:600;">CFO</span>
              <div>
                <strong style="color:#1e293b;">Budget Exceed Overrides:</strong>
                <div class="text-muted">Requires Chief Financial Officer clearing for funds re-allocation.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f0fb; color:#1e5fa8; font-weight:600;">SG</span>
              <div>
                <strong style="color:#1e293b;">Executive Decisions:</strong>
                <div class="text-muted">Single quotation, PO force close, GRN reversal, and compliance bypass route to Secretary General.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-weight:600;">Auto</span>
              <div>
                <strong style="color:#1e293b;">Automated Action:</strong>
                <div class="text-muted">Approved PO force close and GRN reversal immediately trigger system status updates.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
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

