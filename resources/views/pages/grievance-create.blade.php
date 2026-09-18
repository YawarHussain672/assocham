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
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(176,38,58,0.12),rgba(176,38,58,0.04)); color:var(--maroon); border-color:rgba(176,38,58,0.2);">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Raise Grievance / Operational Issue</h2>
            <div class="text-muted" style="font-size:13px;">File a formal ticket regarding delays, vendor non-compliance, or procedural bottlenecks.</div>
          </div>
        </div>

        <form method="POST" action="{{ route('grievance.store') }}" class="mt-3">
          @csrf

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-tags text-muted"></i> Subject Category *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-collection"></i></span>
              <select name="subject_category" class="form-select" required>
                <option value="">Select category…</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat }}" {{ old('subject_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-chat-left-dots text-muted"></i> Ticket Subject *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
              <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="e.g. Unreasonable delay in travel ticket issuance by vendor" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-text-paragraph text-muted"></i> Detailed Incident Description *</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Provide specific timestamps, requisition or PO references, and impact on operations…" required>{{ old('description') }}</textarea>
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('grievance') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
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
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
            <span>4-Tier Redressal Hierarchy</span>
          </div>
          <div class="p-3 d-flex flex-column gap-3" style="font-size:12.5px;">
            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f0fb; color:#1e5fa8; font-weight:600;">Tier 1</span>
              <div>
                <strong style="color:#1e293b;">Department Head (HOD):</strong>
                <div class="text-muted">Initial review and departmental resolution.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#fbf0dc; color:#8a5a12; font-weight:600;">Tier 2</span>
              <div>
                <strong style="color:#1e293b;">Administration Head:</strong>
                <div class="text-muted">Inter-departmental coordination &amp; vendor intervention.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#fbe7e9; color:#b0263a; font-weight:600;">Tier 3</span>
              <div>
                <strong style="color:#1e293b;">Chief Financial Officer (CFO):</strong>
                <div class="text-muted">Financial dispute arbitration &amp; contract penalties.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#f3e8ff; color:#6b21a8; font-weight:600;">Tier 4</span>
              <div>
                <strong style="color:#1e293b;">Secretary General (SG):</strong>
                <div class="text-muted">Executive final decision &amp; policy amendment.</div>
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

