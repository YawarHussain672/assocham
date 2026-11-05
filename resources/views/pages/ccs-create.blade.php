@extends('layouts.app')

@section('title', 'New Cost Comparative Statement')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq.show', $rfq) }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to {{ $rfq->rfq_number }}</span>
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
    <!-- Left: Quotes Entry Form (col-lg-8) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <!-- <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(124,58,237,0.12),rgba(124,58,237,0.04)); color:#7c3aed; border-color:rgba(124,58,237,0.2);">
            <i class="bi bi-table"></i>
          </div> -->
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Record Quotations &amp; Prepare CCS</h2>
            <!-- <div class="text-muted" style="font-size:13px;">Compile received commercial proposals for RFQ: {{ $rfq->rfq_number }}. Automatic ranking by lowest evaluated cost.</div> -->
          </div>
        </div>

        <form method="POST" action="{{ route('ccs.store', $rfq) }}" class="mt-3">
          @csrf

          <div class="d-flex flex-column gap-3">
            @foreach ($rfq->rfqVendors as $rv)
            <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
              <input type="hidden" name="quotes[{{ $loop->index }}][vendor_id]" value="{{ $rv->vendor_id }}">

              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span style="width:24px; height:24px; border-radius:50%; background:#e0e7ff; color:#4338ca; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600;">
                    {{ $loop->iteration }}
                  </span>
                  <span class="fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">{{ $rv->vendor->company_name }}</span>
                  <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:500;">
                    {{ $rv->vendor->vendor_code }}
                  </span>
                </div>
                <span class="text-muted" style="font-size:12px;">{{ $rv->vendor->category ?: 'Vendor Partner' }}</span>
              </div>

              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label small mb-1"> Quoted Cost (₹) *</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">₹</span>
                    <input type="number" name="quotes[{{ $loop->index }}][quoted_cost]" class="form-control" placeholder="0.00" step="0.01" min="0"
                      value="{{ old('quotes.'.$loop->index.'.quoted_cost') }}" required>
                  </div>
                </div>

                <div class="col-md-3">
                  <label class="form-label small mb-1"> Delivery (Days)</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="number" name="quotes[{{ $loop->index }}][delivery_days]" class="form-control" placeholder="Days" min="0"
                      value="{{ old('quotes.'.$loop->index.'.delivery_days') }}">
                  </div>
                </div>

                <div class="col-md-5">
                  <label class="form-label small mb-1"> Remarks / Caveats</label>
                  <input type="text" name="quotes[{{ $loop->index }}][remarks]" class="form-control form-control-sm" placeholder="e.g. 1 year warranty included"
                    value="{{ old('quotes.'.$loop->index.'.remarks') }}">
                </div>
              </div>
            </div>
            @endforeach
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;">
              <i class="bi bi-send-check"></i>
              <span>Submit for Committee Review</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Committee & Lowest Bid Evaluation Policy (col-lg-4) -->
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'New Cost Comparative Statement' });
@endsection