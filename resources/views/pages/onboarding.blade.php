@extends('layouts.app')

@section('title', 'Vendor Registration Form')

@section('content')
<div class="view-wrap" id="pageContent">

  <style>
    .vendor-form-section-title {
      font-size: 16.5px;
      font-weight: 700;
      color: #d40d16;
      letter-spacing: -0.01em;
    }

    .vendor-form-icon-box {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background-color: #ffefef;
      color: #d40d16;
      font-size: 18px;
      flex-shrink: 0;
    }

    .vendor-form-label {
      font-size: 14px !important;
      font-weight: 600 !important;
      color: #1e293b !important;
      margin-bottom: 7px !important;
      display: block;
    }

    .vendor-form-control {
      font-size: 14.5px !important;
      height: 46px;
      padding: 10px 14px !important;
      border-radius: 8px !important;
      border: 1.5px solid #c1c1c1 !important;
      color: #0f172a !important;
      background-color: #ffffff !important;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .vendor-form-control:focus {
      border-color: #c8102e !important;
      box-shadow: 0 0 0 3.5px rgba(200, 16, 46, 0.14) !important;
      outline: none !important;
    }

    .vendor-form-control::placeholder {
      color: #94a3b8 !important;
      font-size: 14px;
    }

    textarea.vendor-form-control {
      height: auto !important;
      min-height: 90px;
      line-height: 1.5 !important;
    }

    .vendor-upload-card {
      background: #f8fafc;
      border: 1.5px dashed #ebebeb;
      border-radius: 10px;
      padding: 18px 20px;
      transition: border-color 0.15s ease-in-out, background-color 0.15s ease-in-out;
    }

    .vendor-upload-card:hover {
      border-color: #c8102e;
      background: #fdf2f4;
    }

    .vendor-upload-title {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .vendor-upload-hint {
      font-size: 13px;
      color: #64748b;
      line-height: 1.5;
      margin-top: 3px;
      margin-bottom: 12px;
    }

    .vendor-upload-input {
      background: #ffffff !important;
      font-size: 14px !important;
      padding: 9px 12px !important;
      height: 46px !important;
      border-radius: 8px !important;
      border: 1.5px solid #ebebeb !important;
    }

    .vendor-upload-meta {
      font-size: 12px;
      color: #64748b;
      margin-top: 6px;
      font-weight: 500;
    }

    .btn-template-yellow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 600;
      padding: 7px 15px;
      border-radius: 8px;
      border: 1px solid #f59e0b;
      background-color: #fffbeb;
      color: #b45309;
      text-decoration: none;
      transition: all 0.15s ease-in-out;
    }

    .btn-template-yellow:hover {
      background-color: #f59e0b;
      border-color: #d97706;
      color: #ffffff;
      box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25);
    }
  </style>

  <!-- <div class="mb-3">
    <a href="{{ route('empanelment') }}" class="back-link d-inline-flex align-items-center gap-1.5 text-secondary text-decoration-none fw-medium" style="font-size: 13.5px;">
      <i class="bi bi-arrow-left"></i>
      <span>Back to Empanelment Directory</span>
    </a>
  </div> -->

  @if ($errors->any())
  <div class="alert alert-danger shadow-xs border-0 d-flex align-items-center gap-2 mb-3 py-2.5 px-3" style="border-radius: 8px;">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
    <div>
      <strong class="d-block mb-0.5" style="font-size: 13.5px;">Please correct the highlighted issues:</strong>
      <ul class="mb-0 ps-3" style="font-size: 13px;">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <div class="row g-4">
    <div class="col-12">
      <div class="card-panel">

        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div>
            <h1 class="h5 mb-0 fw-bold text-dark">Vendor Registration Form</h1>
          </div>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" enctype="multipart/form-data">
          @csrf

          <!-- Hidden default parameters for procurement metadata -->
          <input type="hidden" name="location" value="{{ old('location', 'New Delhi') }}">
          <input type="hidden" name="payment_terms" value="{{ old('payment_terms', 'Net 30') }}">

          <!-- ============================================================= -->
          <!-- PERSONAL / FIRM DETAILS                                       -->
          <!-- ============================================================= -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-building"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">1. Personal / Firm Details</h2>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="vendor-form-label">
                  Name / Incorporation / Entity <span class="text-danger">*</span>
                </label>
                <input type="text" name="company_name" class="form-control vendor-form-control" value="{{ old('company_name') }}" placeholder="Full legal name of person or firm" required>
              </div>

              <div class="col-md-6">
                <label class="vendor-form-label">
                  Contact Person in Case of Entity
                </label>
                <input type="text" name="contact_person" class="form-control vendor-form-control" value="{{ old('contact_person') }}" placeholder="Name of primary contact person">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  ID Number
                </label>
                <input type="text" name="id_number" class="form-control vendor-form-control" value="{{ old('id_number') }}" placeholder="Aadhaar / Passport / Voter ID / CIN / Reg. No.">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Mobile Number
                </label>
                <input type="text" name="contact_phone" class="form-control vendor-form-control" value="{{ old('contact_phone') }}" placeholder="+91 98765 43210">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Official Email Address
                </label>
                <input type="email" name="contact_email" class="form-control vendor-form-control" value="{{ old('contact_email') }}" placeholder="official@vendor.com">
              </div>

              <div class="col-12">
                <label class="vendor-form-label">
                  Registered Postal Address
                </label>
                <textarea name="address" class="form-control vendor-form-control" rows="3" placeholder="Complete postal address with state and PIN code">{{ old('address') }}</textarea>
              </div>

              <div class="col-12">
                <div class="vendor-upload-card">
                  <div class="vendor-upload-title">
                    <i class="bi bi-person-vcard text-primary fs-5"></i>
                    <span>Identity Verification Document (Supporting Document for Personal / Firm Details)</span>
                  </div>
                  <div class="vendor-upload-hint">
                    Any government issued ID which covers all details (Aadhaar / Passport / Election Card / Incorporation Certificate / Driving License / PAN).
                  </div>
                  <input type="file" name="doc_entity_id" class="form-control vendor-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                  <div class="vendor-upload-meta"><i class="bi bi-info-circle me-1"></i>Max 10MB (PDF, PNG, JPG, DOC, ZIP)</div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4 text-muted">

          <!-- ============================================================= -->
          <!-- BANKING DETAILS                                               -->
          <!-- ============================================================= -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-bank"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">2. Banking Details</h2>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="vendor-form-label">
                  Bank Name
                </label>
                <input type="text" name="bank_name" class="form-control vendor-form-control" value="{{ old('bank_name') }}" placeholder="e.g. State Bank of India, HDFC Bank">
              </div>

              <div class="col-md-6">
                <label class="vendor-form-label">
                  Branch Address
                </label>
                <input type="text" name="bank_branch" class="form-control vendor-form-control" value="{{ old('bank_branch') }}" placeholder="Branch name and city">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Account Holder Name
                </label>
                <input type="text" name="bank_account_holder" class="form-control vendor-form-control" value="{{ old('bank_account_holder') }}" placeholder="Exact name as in bank records">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Account Number
                </label>
                <input type="text" name="bank_account_no" class="form-control vendor-form-control font-monospace" value="{{ old('bank_account_no') }}" placeholder="Bank account number">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Type of Account
                </label>
                <select name="bank_account_type" class="form-select vendor-form-control">
                  <option value="">Select account type…</option>
                  <option value="Current" {{ old('bank_account_type') == 'Current' ? 'selected' : '' }}>Current Account</option>
                  <option value="Saving" {{ old('bank_account_type') == 'Saving' ? 'selected' : '' }}>Savings Account</option>
                  <option value="Cash Credit" {{ old('bank_account_type') == 'Cash Credit' ? 'selected' : '' }}>Cash Credit / Overdraft</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  IFSC Code
                </label>
                <input type="text" name="bank_ifsc" class="form-control vendor-form-control text-uppercase font-monospace" value="{{ old('bank_ifsc') }}" placeholder="SBIN0001234" maxlength="20">
              </div>

              <div class="col-12">
                <div class="vendor-upload-card">
                  <div class="vendor-upload-title">
                    <i class="bi bi-file-earmark-check text-primary fs-5"></i>
                    <span>Banking Settlement Proof (Supporting Document for Banking Details)</span>
                  </div>
                  <div class="vendor-upload-hint">
                    Cancelled cheque in own name / firm's name / Bank statement / screenshot of bank mobile application.
                  </div>
                  <input type="file" name="doc_cancelled_cheque" class="form-control vendor-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                  <div class="vendor-upload-meta"><i class="bi bi-info-circle me-1"></i>Max 10MB (PDF, PNG, JPG, DOC, ZIP)</div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4 text-muted">

          <!-- ============================================================= -->
          <!-- BUSINESS & REFERENCES                                         -->
          <!-- ============================================================= -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-briefcase"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">3. Business &amp; References</h2>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="vendor-form-label">
                  Business Category (Deals In)
                </label>
                <input type="text" name="category" class="form-control vendor-form-control" value="{{ old('category') }}" placeholder="e.g. IT & Software, Creative Media, Logistics, Canteen">
              </div>

              <div class="col-12">
                <label class="vendor-form-label">
                  Client / Professional References (minimum 2)
                </label>
                <textarea name="references" class="form-control vendor-form-control" rows="3" placeholder="1. Name, Organization, Phone, Email&#10;2. Name, Organization, Phone, Email">{{ old('references') }}</textarea>
                <div class="form-text text-muted mt-1.5" style="font-size: 12.5px;">Provide at least two professional or client references.</div>
              </div>
            </div>
          </div>

          <hr class="my-4 text-muted">

          <!-- ============================================================= -->
          <!-- TAX & STATUTORY DETAILS                                       -->
          <!-- ============================================================= -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-receipt-cutoff"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">4. Tax Details</h2>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="vendor-form-label">
                  PAN Card Number <span class="text-danger">*</span>
                </label>
                <input type="text" name="pan" class="form-control vendor-form-control text-uppercase font-monospace" value="{{ old('pan') }}" placeholder="ABCDE1234F" maxlength="10">
              </div>

              <div class="col-md-6">
                <label class="vendor-form-label">
                  GSTIN / GST Registration
                </label>
                <input type="text" name="gstin" class="form-control vendor-form-control text-uppercase font-monospace" value="{{ old('gstin') }}" placeholder="07AAAAA0000A1Z5" maxlength="20">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  MSME / Udyam Number
                </label>
                <input type="text" name="msme_number" class="form-control vendor-form-control" value="{{ old('msme_number') }}" placeholder="e.g. UDYAM-XX-00-0000000">
                <div class="form-check mt-2 d-flex align-items-center gap-1.5">
                  <input class="form-check-input" type="checkbox" id="msmeCheck" name="msme_status" value="1" {{ old('msme_status') ? 'checked' : '' }} style="width: 17px; height: 17px; cursor: pointer;">
                  <label class="form-check-label text-dark fw-semibold" for="msmeCheck" style="font-size: 13px; cursor: pointer;">
                    Registered MSME Enterprise
                  </label>
                </div>
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Incorporation Certificate
                </label>
                <input type="text" name="incorporation_certificate_no" class="form-control vendor-form-control" value="{{ old('incorporation_certificate_no') }}" placeholder="CIN or RoC Registration No.">
              </div>

              <div class="col-md-4">
                <label class="vendor-form-label">
                  Partnership Deed / RoF Number
                </label>
                <input type="text" name="partnership_deed_no" class="form-control vendor-form-control" value="{{ old('partnership_deed_no') }}" placeholder="Partnership Deed / RoF Reg. No.">
              </div>

              <div class="col-12">
                <div class="vendor-upload-card">
                  <div class="vendor-upload-title">
                    <i class="bi bi-file-earmark-medical text-primary fs-5"></i>
                    <span>Tax &amp; Statutory Certificates (Supporting Document for Tax Details)</span>
                  </div>
                  <div class="vendor-upload-hint">
                    PAN Card / GST registration letter / MSME Registration letter / incorporation certificate / partnership deed.
                  </div>
                  <input type="file" name="doc_tax_registration" class="form-control vendor-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                  <div class="vendor-upload-meta"><i class="bi bi-info-circle me-1"></i>Max 10MB (PDF, PNG, JPG, DOC, ZIP)</div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4 text-muted">

          <!-- ============================================================= -->
          <!-- DECLARATION WITH SIGNATURE / DATE                             -->
          <!-- ============================================================= -->
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2.5">
                <div class="vendor-form-icon-box">
                  <i class="bi bi-shield-check"></i>
                </div>
                <h2 class="vendor-form-section-title mb-0">Declaration with Signature / Date</h2>
              </div>
            </div>

            <!-- Signature & Signed Declaration Upload Box -->
            <div class="p-4 mb-4 bg-light rounded-3 border" style="border-color: #ebebeb;">
              <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <label class="vendor-form-label mb-0">
                  Upload Signed Self-Declaration Form / Official Stamp <span class="text-danger">*</span>
                </label>
                <a href="{{ route('empanelment.declaration.template') }}" target="_blank" class="btn-template-yellow">
                  <i class="bi bi-download"></i> Download Blank Template
                </a>
              </div>
              <input type="file" name="doc_signature" class="form-control vendor-upload-input mt-2" accept=".png,.jpg,.jpeg,.pdf" required>
              <div class="vendor-upload-meta mt-1.5"><i class="bi bi-info-circle me-1"></i>Scanned copy of signed Self-Declaration Undertaking, specimen signature, or official stamp (Max 10MB)</div>
            </div>

            <!-- Certification & Undertaking Confirmation -->
            <div class="p-3.5 border rounded-3 bg-white mb-3" style="border-color: #ebebeb;">
              <div class="form-check d-flex align-items-start gap-2.5 mb-0">
                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="declaration_accepted" id="declarationAccepted" value="1" required {{ old('declaration_accepted') ? 'checked' : '' }} style="width: 19px; height: 19px; cursor: pointer;">
                <div>
                  <label class="form-check-label fw-bold text-dark d-block mb-1" for="declarationAccepted" style="cursor: pointer; font-size: 14px;">
                    Certification &amp; Compliance Undertaking <span class="text-danger">*</span>
                  </label>
                  <div class="text-secondary" style="font-size: 13.5px; line-height: 1.6;">
                    I, the undersigned hereby accept and warrant that the information provided in this form is correct and, in the event of changes, details will be provided as soon as possible.
                  </div>
                  <div class="text-muted mt-1.5" style="font-size: 12px; line-height: 1.5;">
                    Signed Self-Declaration confirms: I am not involved in any fraudulent or corrupt activities; no child labour engaged in our establishment; and compliance with all ASSOCHAM vendor policies.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Action Buttons -->
          <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="{{ route('empanelment') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
              Submit Vendor Registration
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'onboarding', crumb: 'Procurement', title: 'Vendor Registration Form' });
@endsection