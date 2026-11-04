@extends('layouts.app')

@section('title', 'Vendor Onboarding')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('empanelment') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Empanelment Directory</span>
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
    <!-- Left: Vendor Application Form (col-lg-8) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-building-add"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">Vendor Empanelment Application</h5>
              <!-- <div class="text-muted small">Submit vendor commercial dossier for multi-tier executive and financial compliance clearing.</div> -->
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Vendor Onboarding</span>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" class="mt-3">
          @csrf

          <!-- Section 1: Corporate Identity -->
          <div class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size:14px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:26px; height:26px; font-size:13px;">
              <i class="bi bi-briefcase"></i>
            </div>
            <span>Corporate Identity</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <label class="form-label fw-medium text-secondary">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="e.g. Paramount Tech Solutions Pvt Ltd" required>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium text-secondary">Category / Sector</label>
              <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. IT Hardware / Logistics">
            </div>

            <div class="col-12">
              <label class="form-label fw-medium text-secondary">Business Address</label>
              <textarea name="address" class="form-control" rows="2" placeholder="Full postal address with PIN code…">{{ old('address') }}</textarea>
            </div>

            <div class="col-12">
              <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-patch-check text-success fs-5 me-1"></i>
                  <div>
                    <label class="form-check-label mb-0 fw-semibold text-dark small" for="msmeCheck" style="cursor:pointer;">
                      MSME / UDYAM Registered Enterprise
                    </label>
                    <div class="text-muted" style="font-size:11.5px;">Entitled to priority 45-day statutory payment terms.</div>
                  </div>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="msmeCheck" name="msme_status" value="1" {{ old('msme_status') ? 'checked' : '' }}>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Statutory Tax Compliance -->
          <div class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size:14px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:26px; height:26px; font-size:13px;">
              <i class="bi bi-shield-check"></i>
            </div>
            <span>Tax Compliance</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">PAN <span class="text-danger">*</span></label>
              <input type="text" name="pan" class="form-control text-uppercase" value="{{ old('pan') }}" placeholder="ABCDE1234F" maxlength="10">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium text-secondary">GSTIN</label>
              <input type="text" name="gstin" class="form-control text-uppercase" value="{{ old('gstin') }}" placeholder="07AAAAA0000A1Z5" maxlength="20">
            </div>
          </div>

          <!-- Section 3: Commercial & Banking Dossier -->
          <div class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size:14px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:26px; height:26px; font-size:13px;">
              <i class="bi bi-bank"></i>
            </div>
            <span>Banking &amp; Settlement Credentials</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-5">
              <label class="form-label fw-medium text-secondary">Bank Account Number</label>
              <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no') }}" placeholder="Bank A/C No.">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium text-secondary">Bank IFSC Code</label>
              <input type="text" name="bank_ifsc" class="form-control text-uppercase" value="{{ old('bank_ifsc') }}" placeholder="HDFC0001234">
            </div>

            <div class="col-md-3">
              <label class="form-label fw-medium text-secondary">Agreed Payment Terms</label>
              <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', 'Net 30') }}" placeholder="Net 30">
            </div>
          </div>

          <!-- Section 4: Authorized Contact Representative -->
          <div class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size:14px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:26px; height:26px; font-size:13px;">
              <i class="bi bi-person-lines-fill"></i>
            </div>
            <span>Contact Representative</span>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-medium text-secondary">Contact Person</label>
              <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="Full Name">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium text-secondary">Contact Phone</label>
              <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}" placeholder="+91 98765 43210">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium text-secondary">Contact Email</label>
              <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}" placeholder="procurement@vendor.com">
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('empanelment') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-send-check"></i>
              <span>Submit for Executive Empanelment Review</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'onboarding', crumb: 'Procurement', title: 'Vendor Onboarding' });
@endsection