@extends('layouts.app')

@section('title', 'Rate a Travel Vendor')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Vendor Evaluations</span>
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
    <!-- Left: Scorecard Form (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(217,119,6,0.12),rgba(217,119,6,0.04)); color:#d97706; border-color:rgba(217,119,6,0.2);">
            <i class="bi bi-star-half"></i>
          </div>
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Vendor Performance Evaluation</h2>
            <div class="text-muted" style="font-size:13px;">Rate service quality, punctuality, and cost competitiveness for completed travel.</div>
          </div>
        </div>

        @if ($rows->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-patch-check text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Pending Trip Evaluations</div>
            <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
              A vendor becomes eligible for rating after a trip has concluded (travel end date in the past) and a flight/hotel transaction was logged.
            </div>
            <a href="{{ route('trf') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-airplane"></i>
              <span>View Completed TRFs</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('traveleval.store') }}" class="mt-3">
            @csrf
            <input type="hidden" name="trf_id" id="trfIdInput" value="{{ old('trf_id') }}">
            <input type="hidden" name="vendor_id" id="vendorIdInput" value="{{ old('vendor_id') }}">

            <div class="mb-3">
              <label class="form-label"><i class="bi bi-building text-muted"></i> Completed Trip &amp; Travel Vendor *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-pin-map"></i></span>
                <select id="comboSelect" class="form-select" required>
                  <option value="">Select trip and vendor…</option>
                  @foreach ($rows as $row)
                    @foreach ($row['vendors'] as $vendor)
                      <option value="{{ $row['trf']->id }}:{{ $vendor->id }}">
                        {{ $row['trf']->trf_number }} — {{ $row['trf']->employee->name }} — {{ $vendor->company_name }}
                      </option>
                    @endforeach
                  @endforeach
                </select>
              </div>
            </div>

            <div class="mt-4 mb-2">
              <div class="fw-semibold text-dark mb-1" style="font-size:14px; font-weight:600;">Key Performance Criteria (1 to 5 Stars)</div>
              <div class="text-muted" style="font-size:12px;">5 = Outstanding, 3 = Acceptable, 1 = Unsatisfactory</div>
            </div>

            <div class="row g-3">
              @php
                $criteria = [
                  'cost_score' => ['label' => 'Cost Competitiveness', 'icon' => 'bi-cash-coin'],
                  'time_score' => ['label' => 'Timeliness & Punctuality', 'icon' => 'bi-clock'],
                  'quality_score' => ['label' => 'Service Quality', 'icon' => 'bi-award'],
                  'responsiveness_score' => ['label' => 'Vendor Responsiveness', 'icon' => 'bi-chat-heart'],
                  'accuracy_score' => ['label' => 'Booking Accuracy', 'icon' => 'bi-check2-circle'],
                ];
              @endphp

              @foreach ($criteria as $field => $c)
                <div class="col-md-6">
                  <div class="p-3 border rounded-3 bg-light">
                    <label class="form-label mb-1 d-flex align-items-center gap-1" style="font-size:13px; font-weight:600;">
                      <i class="bi {{ $c['icon'] }} text-primary"></i>
                      <span>{{ $c['label'] }} *</span>
                    </label>
                    <select name="{{ $field }}" class="form-select" required>
                      @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ old($field, 4) == $i ? 'selected' : '' }}>
                          {{ $i }} Stars — {{ str_repeat('★', $i) }}
                        </option>
                      @endfor
                    </select>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="mt-3">
              <label class="form-label"><i class="bi bi-chat-square-quote text-muted"></i> Qualitative Feedback &amp; Comments</label>
              <textarea name="comments" class="form-control" rows="3" placeholder="Provide specific feedback on airline seating, hotel conditions, or customer support issues…">{{ old('comments') }}</textarea>
            </div>

            <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('traveleval') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
              <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 24px;">
                <i class="bi bi-star"></i>
                <span>Submit Evaluation</span>
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Scoring Rubric (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-bar-chart-line" style="color:var(--maroon);"></i>
            <span>Evaluation Rubric</span>
          </div>
          <div class="p-3 d-flex flex-column gap-3" style="font-size:12.5px;">
            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f3ec; color:#2e7d4f; font-weight:600;">4.5 – 5.0</span>
              <div>
                <strong style="color:#1e293b;">Preferred Vendor:</strong>
                <div class="text-muted">High priority allocation for upcoming corporate bookings.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#e6f0fb; color:#1e5fa8; font-weight:600;">3.5 – 4.4</span>
              <div>
                <strong style="color:#1e293b;">Satisfactory:</strong>
                <div class="text-muted">Meets standard institutional SLA guidelines.</div>
              </div>
            </div>

            <div class="d-flex align-items-start gap-2">
              <span class="badge" style="background:#fbe7e9; color:#b0263a; font-weight:600;">&lt; 3.0</span>
              <div>
                <strong style="color:#1e293b;">Under Review:</strong>
                <div class="text-muted">Triggers automatic review by Admin Head during annual empanelment renewal.</div>
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
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Rate a Travel Vendor' });

document.getElementById('comboSelect')?.addEventListener('change', (e) => {
  const [trfId, vendorId] = (e.target.value || '').split(':');
  document.getElementById('trfIdInput').value = trfId || '';
  document.getElementById('vendorIdInput').value = vendorId || '';
});
@endsection

