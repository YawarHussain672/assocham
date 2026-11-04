@extends('layouts.app')

@section('title', 'New RFQ')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to RFQs &amp; Quotations</span>
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
    <!-- Left: RFQ Dispatch Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(217,119,6,0.12),rgba(217,119,6,0.04)); color:#d97706; border-color:rgba(217,119,6,0.2);">
            <i class="bi bi-broadcast-pin"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Dispatch Request for Quotation (RFQ)</h2>
            <div class="text-muted" style="font-size:13px;">Float requirements to verified empanelled vendors based on an approved Requisition.</div>
          </div>
        </div>

        @if ($purchaseRequisitions->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-inbox text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Approved Requisitions Available</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              An RFQ can only be floated against a Purchase Requisition that has cleared its full departmental, financial, and executive approval chain.
            </div>
            <a href="{{ route('requisition') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-file-earmark-text"></i>
              <span>View Requisitions</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('rfq.store') }}" class="mt-3">
            @csrf

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-file-earmark-text text-muted"></i> Approved Purchase Requisition *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                <select name="purchase_requisition_id" class="form-select" required>
                  <option value="">Select cleared PR reference…</option>
                  @foreach ($purchaseRequisitions as $pr)
                    <option value="{{ $pr->id }}" {{ old('purchase_requisition_id') == $pr->id ? 'selected' : '' }}>
                      {{ $pr->pr_number }} — {{ $pr->department }} (Est: ₹{{ number_format($pr->estimatedCostInclGst(), 2) }})
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0"><i class="bi bi-shop text-muted"></i> Dispatch To Empanelled Vendors *</label>
                <span class="text-muted" style="font-size:12px;">Minimum 3 vendors recommended for open bid</span>
              </div>

              @if ($vendors->isEmpty())
                <div class="p-3 rounded-3" style="background:#fffbeb; border:1px solid #fef3c7;">
                  <div class="d-flex align-items-center gap-2 text-warning" style="font-size:13px; font-weight:600;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>No empanelled vendors currently available in the directory.</span>
                  </div>
                  <div class="text-muted mt-1" style="font-size:12px;">Vendors must clear the compliance onboarding chain before receiving quotation requests.</div>
                </div>
              @else
                <div class="border rounded-3 p-3" style="max-height:280px; overflow-y:auto; background:#f8fafc;">
                  <div class="d-flex flex-column gap-2">
                    @foreach ($vendors as $vendor)
                      <div class="p-2 rounded-2 bg-white border d-flex align-items-center justify-content-between">
                        <div class="form-check d-flex align-items-center gap-2 mb-0">
                          <input class="form-check-input mt-0" type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" id="vendor{{ $vendor->id }}"
                            {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }}>
                          <label class="form-check-label d-flex align-items-center gap-2" for="vendor{{ $vendor->id }}" style="font-size:13.5px; cursor:pointer; font-weight:600;">
                            <span>{{ $vendor->company_name }}</span>
                            <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:500;">
                              {{ $vendor->vendor_code }}
                            </span>
                          </label>
                        </div>
                        <span class="text-muted" style="font-size:12px;">
                          {{ $vendor->category ?: 'General Goods' }}
                        </span>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('rfq') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;" {{ $vendors->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-send"></i>
                <span>Dispatch Quotation Request</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Policy & Sourcing Guide (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>Bidding Guidelines</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Vendor Pool:</strong> Quotes may only be solicited from verified and approved vendors.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Comparison:</strong> All submitted bids will compile into a side-by-side Cost Comparative Statement (CCS).</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span><strong>Ranking:</strong> The system automatically evaluates lowest compliant bid upon quote submission.</span>
            </li>
          </ul>
        </div>

        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
          <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1" style="font-weight:600;">
            <i class="bi bi-clock-history" style="color:#d97706;"></i>
            <span>Typical Response Window</span>
          </div>
          <div class="text-muted">Vendors normally submit quotations within 3–5 business days, including delivery schedules and tax components.</div>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'New RFQ' });
@endsection
