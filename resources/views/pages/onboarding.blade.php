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
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(30,95,168,0.12),rgba(30,95,168,0.04)); color:var(--maroon); border-color:rgba(30,95,168,0.2);">
            <i class="bi bi-building-add"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Corporate Vendor Empanelment Application</h2>
            <div class="text-muted" style="font-size:13px;">Submit vendor commercial dossier for multi-tier executive and financial compliance clearing.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" class="mt-3">
          @csrf

          <!-- Section 1: Corporate Identity -->
          <div class="fw-semibold text-dark mb-2 d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600;">
            <i class="bi bi-briefcase text-primary"></i>
            <span>Corporate Identity</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <label class="form-label">Registered Company Name *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-building"></i></span>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="e.g. Paramount Tech Solutions Pvt Ltd" required>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Category / Sector</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-tags"></i></span>
                <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. IT Hardware / Logistics">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Registered Business Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                <textarea name="address" class="form-control" rows="2" placeholder="Full postal address with PIN code…">{{ old('address') }}</textarea>
              </div>
            </div>

            <div class="col-12">
              <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-patch-check text-success fs-5"></i>
                  <div>
                    <label class="form-check-label mb-0" for="msmeCheck" style="font-size:13px; font-weight:600; cursor:pointer;">
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
          <div class="fw-semibold text-dark mb-2 d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600;">
            <i class="bi bi-shield-check text-primary"></i>
            <span>Statutory Tax Compliance</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Permanent Account Number (PAN) *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                <input type="text" name="pan" class="form-control text-uppercase" value="{{ old('pan') }}" placeholder="ABCDE1234F" maxlength="10">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Goods &amp; Services Tax ID (GSTIN)</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-file-earmark-lock"></i></span>
                <input type="text" name="gstin" class="form-control text-uppercase" value="{{ old('gstin') }}" placeholder="07AAAAA0000A1Z5" maxlength="20">
              </div>
            </div>
          </div>

          <!-- Section 3: Commercial & Banking Dossier -->
          <div class="fw-semibold text-dark mb-2 d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600;">
            <i class="bi bi-bank text-primary"></i>
            <span>Banking &amp; Settlement Credentials</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-5">
              <label class="form-label">Bank Account Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no') }}" placeholder="Bank A/C No.">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Bank IFSC Code</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-qr-code"></i></span>
                <input type="text" name="bank_ifsc" class="form-control text-uppercase" value="{{ old('bank_ifsc') }}" placeholder="HDFC0001234">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Agreed Payment Terms</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', 'Net 30') }}" placeholder="Net 30">
              </div>
            </div>
          </div>

          <!-- Section 4: Authorized Contact Representative -->
          <div class="fw-semibold text-dark mb-2 d-flex align-items-center gap-2" style="font-size:13.5px; font-weight:600;">
            <i class="bi bi-person-lines-fill text-primary"></i>
            <span>Authorized Contact Representative</span>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Contact Person</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="Full Name">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Contact Phone</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}" placeholder="+91 98765 43210">
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Contact Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}" placeholder="procurement@vendor.com">
              </div>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('empanelment') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
              <i class="bi bi-send-check"></i>
              <span>Submit for Executive Empanelment Review</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Compliance Roadmap Sidebar (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
            <span>Empanelment Clearance Chain</span>
          </div>
          <div class="p-3 d-flex flex-column gap-3" style="font-size:12.5px;">
            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f0fb; color:#1e5fa8; font-weight:600;">L0</span>
              <div>
                <strong style="color:#1e293b;">Admin Head:</strong>
                <div class="text-muted">Document completeness and PAN/GSTIN validity audit.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#fbf0dc; color:#8a5a12; font-weight:600;">L1</span>
              <div>
                <strong style="color:#1e293b;">Assistant Sec General (ASG 1):</strong>
                <div class="text-muted">Operational capability &amp; service sector clearance.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#f3e8ff; color:#6b21a8; font-weight:600;">L2</span>
              <div>
                <strong style="color:#1e293b;">Assistant Sec General (ASG 2):</strong>
                <div class="text-muted">Comparative market pricing &amp; conflict-of-interest check.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-weight:600;">L3</span>
              <div>
                <strong style="color:#1e293b;">Chief Financial Officer (CFO):</strong>
                <div class="text-muted">Bank details verification and final empanelment code assignment.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-check-all" style="color:var(--maroon);"></i>
            <span>Empanelled Privileges</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-3" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>Selectable on all automated RFQ bidding tenders.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>Eligible for direct Purchase Order contracts up to delegated limits.</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'onboarding', crumb: 'Procurement', title: 'Vendor Onboarding' });
@endsection

