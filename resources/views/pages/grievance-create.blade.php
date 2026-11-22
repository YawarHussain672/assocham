@extends('layouts.app')

@section('title', 'Raise Grievance Ticket')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('grievance') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Grievance Redressal</span>
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
    <!-- Redesigned Grievance Form (Full Width) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-chat-square-quote"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Raise Grievance / Settlement Dispute</h1>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="font-size:11px; font-weight:600; letter-spacing:0.3px; border-color:#e2e8f0 !important;">
            GRIEVANCE TICKET
          </span>
        </div>

        <form method="POST" action="{{ route('grievance.store') }}" enctype="multipart/form-data">
          @csrf

          <!-- Section 1: Classification & Linked Reference -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-tag"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Classification &amp; Reference</h2>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="vendor-form-label" for="categorySelect">
                  Subject Category <span class="text-danger">*</span>
                </label>
                <select name="subject_category" id="categorySelect" class="form-select vendor-form-control @error('subject_category') is-invalid @enderror" required>
                  <option value="">Select category…</option>
                  @foreach ($categories as $cat)
                    <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                  @endforeach
                </select>
                @error('subject_category')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="vendor-form-label" for="reimbursementSelect">
                  Linked Reimbursement / Advance Claim <span class="text-muted fw-normal" style="font-size:12px;">(optional)</span>
                </label>
                <select name="reimbursement_id" id="reimbursementSelect" class="form-select vendor-form-control @error('reimbursement_id') is-invalid @enderror">
                  <option value="">None / General Operational Issue</option>
                  @foreach ($claims as $claim)
                    <option value="{{ $claim->id }}" {{ $selectedClaimId === (string) $claim->id ? 'selected' : '' }}>
                      {{ $claim->claim_number }} — {{ $claim->employee?->name ?? 'Claimant' }} (Net ₹{{ number_format((float) $claim->balance_amount, 2) }})
                    </option>
                  @endforeach
                </select>
                @error('reimbursement_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Dynamic Linked Claim Summary Strip -->
            <div id="claimPreviewNotice" class="d-none mt-3"></div>
          </div>

          <!-- Section 2: Incident Details & Supporting Evidence -->
          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-card-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Incident Details &amp; Evidence</h2>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="vendor-form-label" for="subjectInput">
                  Ticket Subject <span class="text-danger">*</span>
                </label>
                <input type="text" name="subject" id="subjectInput" class="form-control vendor-form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="e.g. Short payout or delay in advance settlement against CLM-1024" required maxlength="255">
                @error('subject')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="vendor-form-label" for="descriptionInput">
                  Detailed Incident Description <span class="text-danger">*</span>
                </label>
                <textarea name="description" id="descriptionInput" class="form-control vendor-form-control @error('description') is-invalid @enderror" rows="5" placeholder="Include claim numbers, settlement amounts, timestamps, or vendor/department details…" required>{{ old('description') }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Upload Supporting Document using Master Upload Card -->
              <div class="col-12">
                <div class="vendor-upload-card">
                  <div class="vendor-upload-title">
                    <i class="bi bi-paperclip text-primary fs-5"></i>
                    <span>Supporting Document / Screenshot (Optional)</span>
                  </div>
                  <div class="vendor-upload-hint">Attach relevant invoice, claim statement, email correspondence, or payment receipt (PDF, JPG, PNG up to 5 MB).</div>
                  <input type="file" name="proof" id="proofFile" class="form-control vendor-upload-input @error('proof') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                  @error('proof')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>
          </div>

          <!-- Action Footer -->
          <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
            <a href="{{ route('grievance') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
              Submit Grievance Ticket
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Raise Grievance Ticket' });

(function () {
  const claimSummaries = @json($claimSummaries ?? []);
  const claimSelect = document.getElementById('reimbursementSelect');
  const categorySelect = document.getElementById('categorySelect');
  const noticeEl = document.getElementById('claimPreviewNotice');
  const settlementCat = @json(\App\Http\Controllers\GrievanceController::CATEGORY_SETTLEMENT);

  function renderClaimNotice(claimId) {
    if (!noticeEl) return;
    const claim = claimSummaries[String(claimId)];
    if (!claimId || !claim) {
      noticeEl.className = 'd-none mt-3';
      noticeEl.replaceChildren();
      return;
    }

    if (categorySelect && !categorySelect.value) {
      categorySelect.value = settlementCat;
    }

    noticeEl.className = 'mt-3 rounded-2 px-3 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2';
    noticeEl.style.background = '#F8FAFC';
    noticeEl.style.borderLeft = '3px solid #c8102e';
    noticeEl.style.color = '#1E293B';

    const infoDiv = document.createElement('div');
    infoDiv.className = 'small';
    infoDiv.textContent =
      claim.claim_number + ' · ' + claim.trf_number +
      ' · Claim ₹' + Number(claim.claim_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
      ' · Advance ₹' + Number(claim.advance_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
      ' · Net ₹' + Number(claim.balance_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
      ' (' + claim.status + ')';

    const link = document.createElement('a');
    link.href = claim.url;
    link.className = 'small fw-semibold text-decoration-none text-primary';
    link.textContent = 'View Claim →';

    noticeEl.replaceChildren(infoDiv, link);
  }

  if (claimSelect) {
    claimSelect.addEventListener('change', () => renderClaimNotice(claimSelect.value));
    if (claimSelect.value) {
      renderClaimNotice(claimSelect.value);
    }
  }
})();
@endsection
