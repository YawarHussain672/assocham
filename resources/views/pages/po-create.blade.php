@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('po') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Orders</span>
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
    <!-- Left: PO Creation Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(37,99,235,0.12),rgba(37,99,235,0.04)); color:#2563eb; border-color:rgba(37,99,235,0.2);">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Issue Purchase Order</h2>
            <div class="text-muted" style="font-size:13px;">Auto-populated from approved Cost Comparative Statement (CCS) lowest compliant bidder.</div>
          </div>
        </div>

        @if ($statements->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Approved Statements Ready for PO</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              Purchase Orders can only be raised once a Cost Comparative Statement (CCS) has cleared Procurement Committee approval and has not yet been converted.
            </div>
            <a href="{{ route('ccs') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-file-earmark-bar-graph"></i>
              <span>View Comparative Statements</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('po.store') }}" class="mt-3">
            @csrf

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-file-earmark-ruled text-muted"></i> Approved Cost Comparative Statement *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <select name="cost_comparative_statement_id" class="form-select" required id="ccsSelect">
                  <option value="">Select evaluated CCS statement…</option>
                  @foreach ($statements as $ccs)
                    @php $l1 = $ccs->lowestQuote(); @endphp
                    <option value="{{ $ccs->id }}" {{ old('cost_comparative_statement_id') == $ccs->id ? 'selected' : '' }}>
                      {{ $ccs->ccs_number }} — {{ $ccs->rfq->purchaseRequisition->pr_number }} ({{ $ccs->rfq->purchaseRequisition->department }}) —
                      Awarded: {{ $l1?->vendor?->company_name }} @ ₹{{ number_format($l1?->quoted_cost ?? 0, 2) }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="text-muted mt-1" style="font-size:12px;">Selecting a statement binds the awarded vendor and negotiated contract price.</div>
            </div>

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-calendar3 text-muted"></i> Expected Delivery Target Date</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date') }}">
              </div>
              <div class="text-muted mt-1" style="font-size:12px;">Optional — defaults to vendor's quoted delivery window if omitted.</div>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('po') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
                <i class="bi bi-check2-circle"></i>
                <span>Generate Purchase Order</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Governance & Policy Guide (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>PO Governance Standards</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Strict Lowest-Bid Award:</strong> Orders are contractually tied to the lowest qualified compliant vendor from the CCS.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Thresholds:</strong> POs under ₹10 Lakhs route to Admin Head; orders &ge; ₹10 Lakhs require CFO &amp; SG signoff.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Inventory Receipt:</strong> Generating a PO unlocks partial or complete GRN receipts by the Store Exec.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1" style="font-weight:600;">
            <i class="bi bi-diagram-2" style="color:#2563eb;"></i>
            <span>Procurement Pipeline Stage</span>
          </div>
          <div class="text-muted">You are converting an approved comparison into an executive legal commitment with the vendor.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'po', crumb: 'Procurement', title: 'New Purchase Order' });
@endsection
