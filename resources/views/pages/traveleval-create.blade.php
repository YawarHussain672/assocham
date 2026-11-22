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
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-star"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Vendor Performance Evaluation</h1>
            </div>
          </div>
          <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="border-color:#e2e8f0 !important;">Scorecard</span>
        </div>

        @if ($rows->isEmpty())
          <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
            <i class="bi bi-patch-check text-muted" style="font-size:36px;"></i>
            <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px;">No Pending Trip Evaluations</div>
            <div class="text-muted mt-1 small" style="max-width:480px; margin:0 auto;">
              A vendor becomes eligible for rating after a trip has concluded (travel end date in the past) and a flight/hotel transaction was logged.
            </div>
            <a href="{{ route('trf') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 fw-semibold">
              <i class="bi bi-airplane"></i>
              <span>View Completed TRFs</span>
            </a>
          </div>
        @else
          <form method="POST" action="{{ route('traveleval.store') }}">
            @csrf
            <input type="hidden" name="trf_id" id="trfIdInput" value="{{ $preselectedTrfId ?? old('trf_id') }}">
            <input type="hidden" name="vendor_id" id="vendorIdInput" value="{{ $preselectedVendorId ?? old('vendor_id') }}">

            <div class="mb-4">
              <label class="vendor-form-label">Completed Trip &amp; Travel Vendor <span class="text-danger">*</span></label>
              <select id="comboSelect" class="form-select vendor-form-control" required>
                <option value="">Select trip and vendor…</option>
                @foreach ($rows as $row)
                  @foreach ($row['vendors'] as $vendor)
                    @php $comboVal = "{$row['trf']->id}:{$vendor->id}"; @endphp
                    <option value="{{ $comboVal }}" {{ ($preselectedCombo ?? '') === $comboVal ? 'selected' : '' }}>
                      {{ $row['trf']->trf_number }} — {{ $row['trf']->employee->name }} ({{ $row['trf']->route ?: 'Trip' }}) — {{ $vendor->company_name }}
                    </option>
                  @endforeach
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <div class="fw-bold text-dark mb-1" style="font-size: 15px;">Key Performance Criteria (1 to 5 Stars)</div>
              <div class="text-muted" style="font-size: 12.5px;">5 = Outstanding, 3 = Acceptable, 1 = Unsatisfactory</div>
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
                  <div class="p-3 rounded-3 bg-light border" style="border-color:#e2e8f0 !important;">
                    <label class="vendor-form-label small mb-1.5 d-flex align-items-center justify-content-between">
                      <span><i class="bi {{ $c['icon'] }} text-primary me-1"></i> {{ $c['label'] }}</span>
                      <span class="text-danger small">*</span>
                    </label>
                    <select name="{{ $field }}" class="form-select vendor-form-control" required>
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

            <div class="mt-4">
              <label class="vendor-form-label">Qualitative Feedback &amp; Comments</label>
              <textarea name="comments" class="form-control vendor-form-control" rows="3" placeholder="Provide specific feedback on airline seating, hotel conditions, or customer support issues…">{{ old('comments') }}</textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
              <a href="{{ route('traveleval') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
              <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
                Submit Evaluation
              </button>
            </div>
          </form>
        @endif
      </div>
    </div>

    <!-- Right: Scoring Rubric (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">
        <div class="card-panel p-0 overflow-hidden">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-bar-chart-line"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Evaluation Rubric</h6>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1">Score Matrix</span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <!-- Preferred Vendor -->
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2.5 py-1.5 mt-0.5 me-2" style="flex-shrink:0; min-width:68px; text-align:center;">4.5 – 5.0</span>
              <div>
                <div class="fw-bold text-dark small mb-1">Preferred Vendor</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">High priority allocation for upcoming corporate bookings.</div>
              </div>
            </div>

            <!-- Satisfactory -->
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2.5 py-1.5 mt-0.5 me-2" style="flex-shrink:0; min-width:68px; text-align:center;">3.5 – 4.4</span>
              <div>
                <div class="fw-bold text-dark small mb-1">Satisfactory</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Meets standard institutional SLA guidelines.</div>
              </div>
            </div>

            <!-- Under Review -->
            <div class="p-3 rounded-3 bg-light d-flex align-items-start gap-3">
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold px-2.5 py-1.5 mt-0.5 me-2" style="flex-shrink:0; min-width:68px; text-align:center;">&lt; 3.0</span>
              <div>
                <div class="fw-bold text-dark small mb-1">Under Review</div>
                <div class="text-muted small" style="font-size:12.5px; line-height:1.45;">Automatic review by Admin Head during annual renewal.</div>
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

const comboEl = document.getElementById('comboSelect');
function syncComboSelection() {
  if (!comboEl) return;
  const [trfId, vendorId] = (comboEl.value || '').split(':');
  document.getElementById('trfIdInput').value = trfId || '';
  document.getElementById('vendorIdInput').value = vendorId || '';
}
comboEl?.addEventListener('change', syncComboSelection);
syncComboSelection();
@endsection

