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
    <!-- Left: Ticket Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width:42px; height:42px; font-size:1.25rem;">
              <i class="bi bi-ticket-perforated"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold text-dark">Raise Grievance / Operational Issue</h5>
              <div class="text-muted small">File a formal ticket regarding delays, vendor non-compliance, or procedural bottlenecks.</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5">Grievance Ticket</span>
        </div>

        <form method="POST" action="{{ route('grievance.store') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-medium text-secondary">Subject Category <span class="text-danger">*</span></label>
            <select name="subject_category" class="form-select" required>
              <option value="">Select category…</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat }}" {{ old('subject_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium text-secondary">Ticket Subject <span class="text-danger">*</span></label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="e.g. Unreasonable delay in travel ticket issuance by vendor" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium text-secondary">Detailed Incident Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="4" placeholder="Provide specific timestamps, requisition or PO references, and impact on operations…" required>{{ old('description') }}</textarea>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('grievance') }}" class="btn btn-light border px-4 fw-semibold">Cancel</a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold">
              <i class="bi bi-send-plus"></i>
              <span>Submit Grievance Ticket</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Escalation Protocol Sidebar (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <!-- 4-Tier Redressal Hierarchy Card -->
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width:32px; height:32px;">
                <i class="bi bi-diagram-3"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">4-Tier Redressal Hierarchy</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Escalation</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-primary-subtle text-primary border me-2 flex-shrink-0" style="width:34px; height:34px; font-size:14px; margin-top:2px;">
                <i class="bi bi-person"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Department Head (HOD)</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Initial review and departmental resolution.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-warning-subtle text-warning border me-2 flex-shrink-0" style="width:34px; height:34px; font-size:14px; margin-top:2px;">
                <i class="bi bi-person-gear"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Administration Head</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Inter-departmental coordination &amp; vendor intervention.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-danger-subtle text-danger border me-2 flex-shrink-0" style="width:34px; height:34px; font-size:14px; margin-top:2px;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Chief Financial Officer (CFO)</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Financial dispute arbitration &amp; contract penalties.</div>
              </div>
            </div>

            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-info-subtle text-info border me-2 flex-shrink-0" style="width:34px; height:34px; font-size:14px; margin-top:2px;">
                <i class="bi bi-award"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small mb-1">Secretary General (SG)</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Executive final decision &amp; policy amendment.</div>
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
Shell.init({ activeNav: 'grievance', crumb: 'Governance', title: 'Raise Grievance Ticket' });
@endsection

