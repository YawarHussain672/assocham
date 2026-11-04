@extends('layouts.app')

@section('title', $evaluation->vendor->company_name)

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Vendor Evaluations</span>
  </a>

  <div class="detail-header-card">
    <div class="detail-avatar" style="background:linear-gradient(135deg,rgba(217,119,6,0.15),rgba(217,119,6,0.05)); color:#d97706; border:1px solid rgba(217,119,6,0.2);">
      <i class="bi bi-star-fill fs-4"></i>
    </div>
    <div>
      <div class="detail-name">{{ $evaluation->vendor->company_name }}</div>
      <div class="detail-meta d-flex align-items-center gap-2 mt-1">
        <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">Trip: {{ $evaluation->trf->trf_number }}</span>
        <span>•</span>
        <span>{{ $evaluation->trf->employee->name }}</span>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge-soft d-inline-flex align-items-center gap-1" style="background:#E6F3EC; color:#2E7D4F; font-size:15px; font-weight:600; padding:8px 16px;">
        <i class="bi bi-star-fill text-warning"></i>
        <span>{{ $evaluation->averageRating() }} / 5.0</span>
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left: Score Meters -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-bar-chart-fill text-primary"></i>
          <span>Performance Breakdown</span>
        </h3>

        @php
          $scores = [
            ['label' => 'Cost Competitiveness', 'val' => $evaluation->cost_score, 'icon' => 'bi-cash-coin'],
            ['label' => 'Timeliness & Punctuality', 'val' => $evaluation->time_score, 'icon' => 'bi-clock'],
            ['label' => 'Service Quality', 'val' => $evaluation->quality_score, 'icon' => 'bi-award'],
            ['label' => 'Vendor Responsiveness', 'val' => $evaluation->responsiveness_score, 'icon' => 'bi-chat-heart'],
            ['label' => 'Booking Accuracy', 'val' => $evaluation->accuracy_score, 'icon' => 'bi-check2-circle'],
          ];
        @endphp

        <div class="d-flex flex-column gap-3">
          @foreach ($scores as $s)
            @php
              $pct = ($s['val'] / 5) * 100;
              $color = $s['val'] >= 4 ? '#2e7d4f' : ($s['val'] >= 3 ? '#d97706' : '#b0263a');
            @endphp
            <div>
              <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                <span class="d-flex align-items-center gap-2 fw-semibold text-dark" style="font-weight:600;">
                  <i class="bi {{ $s['icon'] }} text-muted"></i>
                  <span>{{ $s['label'] }}</span>
                </span>
                <span class="fw-semibold" style="color:{{ $color }}; font-weight:600;">{{ $s['val'] }} / 5 ({{ str_repeat('★', $s['val']) }})</span>
              </div>
              <div class="progress" style="height:6px; background:#f1f5f9;">
                <div class="progress-bar" style="width:{{ $pct }}%; background-color:{{ $color }};" role="progressbar"></div>
              </div>
            </div>
          @endforeach
        </div>

        @if ($evaluation->comments)
          <div class="mt-4 pt-3 border-top">
            <div class="field-label mb-2"><i class="bi bi-chat-quote me-1"></i> Evaluator Feedback</div>
            <div class="p-3 rounded-3" style="background:#f8fafc; border-left:3px solid var(--maroon); font-size:13.5px; color:#334155;">
              "{{ $evaluation->comments }}"
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Right: Metadata & Audit -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-shield-check text-primary"></i>
          <span>Evaluation Metadata</span>
        </h3>

        <div class="d-flex flex-column gap-3">
          <div>
            <div class="field-label">Evaluated Vendor</div>
            <div class="field-value mt-1" style="font-weight:600;">{{ $evaluation->vendor->company_name }}</div>
            <div class="text-muted" style="font-size:12px;">Code: {{ $evaluation->vendor->vendor_code }}</div>
          </div>

          <div>
            <div class="field-label">Linked Travel Requisition</div>
            <div class="field-value mt-1">
              <a href="{{ route('trf.show', $evaluation->trf) }}" class="text-decoration-none" style="color:var(--maroon); font-weight:600;">
                <i class="bi bi-airplane me-1"></i>{{ $evaluation->trf->trf_number }}
              </a>
            </div>
          </div>

          <div>
            <div class="field-label">Evaluator Officer</div>
            <div class="field-value mt-1 d-flex align-items-center gap-2">
              <i class="bi bi-person-check text-muted"></i>
              <span>{{ $evaluation->rater?->name ?? 'System' }}</span>
            </div>
          </div>

          <div>
            <div class="field-label">Evaluation Date</div>
            <div class="field-value mt-1 text-muted" style="font-size:13px;">
              <i class="bi bi-calendar-event me-1"></i>{{ $evaluation->created_at?->format('d M Y, H:i') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Evaluation Detail' });
@endsection

