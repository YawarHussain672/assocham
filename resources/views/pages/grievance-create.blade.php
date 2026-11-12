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

  <div class="row g-4 align-items-start">
    <!-- Left: Clean Grievance Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card card-panel shadow-sm border-0 p-4">
        <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 pb-3 mb-3 border-bottom">
          <div>
            <h1 class="h5 mb-1 fw-bold text-dark">Raise Grievance / Settlement Dispute</h1>
            <div class="text-muted" style="font-size:13px;">Any user can report operational delays, vendor disputes, or reimbursement &amp; advance settlement issues.</div>
          </div>
          <span class="text-muted fw-semibold text-uppercase" style="font-size:11px; letter-spacing:0.6px;">Grievance Ticket</span>
        </div>

        <form method="POST" action="{{ route('grievance.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-dark mb-1" for="categorySelect">
                Subject Category <span class="text-danger">*</span>
              </label>
              <select name="subject_category" id="categorySelect" class="form-select @error('subject_category') is-invalid @enderror" required>
                <option value="">Select category…</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-dark mb-1" for="reimbursementSelect">
                Linked Reimbursement / Advance Claim <span class="text-muted fw-normal" style="font-size:12px;">(optional)</span>
              </label>
              <select name="reimbursement_id" id="reimbursementSelect" class="form-select @error('reimbursement_id') is-invalid @enderror">
                <option value="">None / General Operational Issue</option>
                @foreach ($claims as $claim)
                  <option value="{{ $claim->id }}" {{ $selectedClaimId === (string) $claim->id ? 'selected' : '' }}>
                    {{ $claim->claim_number }} — {{ $claim->employee?->name ?? 'Claimant' }} (Net ₹{{ number_format((float) $claim->balance_amount, 2) }})
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- Dynamic Linked Claim Summary Strip -->
          <div id="claimPreviewNotice" class="d-none mb-3"></div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-dark mb-1" for="subjectInput">
              Ticket Subject <span class="text-danger">*</span>
            </label>
            <input type="text" name="subject" id="subjectInput" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="e.g. Short payout or delay in advance settlement against CLM-1024" required maxlength="255">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-dark mb-1" for="descriptionInput">
              Detailed Incident Description <span class="text-danger">*</span>
            </label>
            <textarea name="description" id="descriptionInput" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Include claim numbers, settlement amounts, timestamps, or vendor/department details…" required>{{ old('description') }}</textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-dark mb-1" for="proofFile">
              Supporting Document / Screenshot <span class="text-muted fw-normal" style="font-size:12px;">(optional · PDF, JPG, PNG up to 5 MB)</span>
            </label>
            <input type="file" name="proof" id="proofFile" class="form-control @error('proof') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
          </div>

          <div class="pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="text-muted" style="font-size:12px;">
              <i class="bi bi-arrow-right-circle text-primary me-1"></i>
              Tickets route first to HOD and can escalate through Admin Head → CFO → SG.
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
              <a href="{{ route('grievance') }}" class="btn btn-light border px-3 fw-semibold text-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 fw-semibold">
                <i class="bi bi-send-plus"></i>
                <span>Submit Grievance Ticket</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Clean Escalation Hierarchy Card (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="card card-panel shadow-sm border-0 p-0 overflow-hidden">
        <div class="bg-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
          <div>
            <h2 class="h6 mb-0 fw-bold text-dark" style="font-size:14px;">4-Tier Redressal Hierarchy</h2>
            <div class="text-muted" style="font-size:11px;">Open to all roles · Escalates step-by-step</div>
          </div>
          <span class="badge bg-light text-secondary border" style="font-size:11px;">SLA Tracked</span>
        </div>

        <div class="list-group list-group-flush">
          <div class="list-group-item px-4 py-3 border-light-subtle">
            <div class="fw-semibold text-dark" style="font-size:13px;">1. Department Head (HOD)</div>
            <div class="text-muted mt-1" style="font-size:12px;">Initial triage and departmental review upon filing.</div>
          </div>
          <div class="list-group-item px-4 py-3 border-light-subtle">
            <div class="fw-semibold text-dark" style="font-size:13px;">2. Administration Head</div>
            <div class="text-muted mt-1" style="font-size:12px;">Cross-team coordination, logistics, and vendor intervention.</div>
          </div>
          <div class="list-group-item px-4 py-3 border-light-subtle">
            <div class="fw-semibold text-dark" style="font-size:13px;">3. Chief Financial Officer (CFO)</div>
            <div class="text-muted mt-1" style="font-size:12px;">Reimbursement &amp; advance settlement disputes and financial arbitration.</div>
          </div>
          <div class="list-group-item px-4 py-3 border-light-subtle">
            <div class="fw-semibold text-dark" style="font-size:13px;">4. Secretary General (SG)</div>
            <div class="text-muted mt-1" style="font-size:12px;">Final executive ruling and policy resolution.</div>
          </div>
        </div>
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
      noticeEl.className = 'd-none mb-3';
      noticeEl.replaceChildren();
      return;
    }

    if (categorySelect && !categorySelect.value) {
      categorySelect.value = settlementCat;
    }

    noticeEl.className = 'mb-3 rounded-2 px-3 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2';
    noticeEl.style.background = '#F8FAFC';
    noticeEl.style.borderLeft = '3px solid #2563EB';
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
