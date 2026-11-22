@extends('layouts.app')

@section('title', $evaluation->vendor->company_name)

@php
  $avg = $evaluation->averageRating();
  if ($avg >= 4.5) {
    $tier = ['label' => 'Preferred Partner', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'];
  } elseif ($avg >= 3.5) {
    $tier = ['label' => 'Satisfactory', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'];
  } else {
    $tier = ['label' => 'Needs Review', 'bg' => '#FBE7E9', 'fg' => '#B0263A'];
  }

  $scores = [
    ['label' => 'Cost Competitiveness', 'val' => $evaluation->cost_score, 'icon' => 'bi-cash-coin'],
    ['label' => 'Timeliness & Punctuality', 'val' => $evaluation->time_score, 'icon' => 'bi-clock'],
    ['label' => 'Service Quality', 'val' => $evaluation->quality_score, 'icon' => 'bi-award'],
    ['label' => 'Vendor Responsiveness', 'val' => $evaluation->responsiveness_score, 'icon' => 'bi-chat-heart'],
    ['label' => 'Booking Accuracy', 'val' => $evaluation->accuracy_score, 'icon' => 'bi-check2-circle'],
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('traveleval') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Travel Vendor Evaluations</span>
  </a>

  <!-- Executive Header Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $evaluation->vendor->company_name }}</span>
              <span class="badge d-inline-flex align-items-center gap-1" style="background:{{ $tier['bg'] }}; color:{{ $tier['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                <i class="bi bi-star-fill text-warning"></i> {{ $avg }} / 5.0 &bull; {{ $tier['label'] }}
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
              <span class="detail-meta-chip">
                <i class="bi bi-file-earmark-text"></i>
                <span class="meta-label">TRF:</span>
                @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
                  <a href="{{ route('trf.show', $evaluation->trf) }}">{{ $evaluation->trf->trf_number }}</a>
                @else
                  <strong>{{ $evaluation->trf->trf_number }}</strong>
                @endif
              </span>
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Traveler:</span> <strong>{{ $evaluation->trf->employee->name }}</strong></span>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <a href="{{ route('traveleval.vendor-history', $evaluation->vendor) }}" class="btn btn-sm btn-outline-primary px-3 py-1 fw-semibold">
            <i class="bi bi-graph-up-arrow me-1"></i>Vendor History
          </a>
          <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold" style="font-size:12px;">
            Evaluated {{ optional($evaluation->created_at)->format('d M Y') }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Equal-Height Detail Grid -->
  <div class="row g-4 align-items-stretch">
    <!-- Left: Score Breakdown -->
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-bar-chart-fill text-primary"></i>
            <span>Performance Breakdown</span>
          </div>
          <span class="text-muted small" style="font-size:12px;">Average: <strong class="text-dark">{{ $avg }} / 5.0</strong></span>
        </div>

        <div class="card-body p-4 d-flex flex-column justify-content-between gap-3">
          <div class="d-flex flex-column gap-3">
            @foreach ($scores as $s)
              @php
                $pct = ($s['val'] / 5) * 100;
                $color = $s['val'] >= 4 ? '#16a34a' : ($s['val'] >= 3 ? '#d97706' : '#dc2626');
              @endphp
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                  <span class="d-flex align-items-center gap-2 fw-semibold text-dark">
                    <i class="bi {{ $s['icon'] }} text-muted"></i>
                    <span>{{ $s['label'] }}</span>
                  </span>
                  <span class="fw-semibold" style="color:{{ $color }};">{{ $s['val'] }} / 5 ({{ str_repeat('★', $s['val']) }})</span>
                </div>
                <div class="progress" style="height:6px; background:#f1f5f9;">
                  <div class="progress-bar" style="width:{{ $pct }}%; background-color:{{ $color }};" role="progressbar"></div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="pt-3 border-top">
            <div class="field-label mb-1">Evaluator Feedback</div>
            <div class="p-3 rounded-3 bg-light border text-dark" style="font-size:13px; line-height:1.5;">
              {{ $evaluation->comments ?: 'No qualitative remarks recorded.' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Evaluation Metadata & Trip Context -->
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-shield-check text-primary"></i>
            <span>Evaluation Dossier</span>
          </div>
        </div>

        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Evaluated Partner Agency</div>
                <div class="field-value fw-semibold text-dark" style="font-size:14px;">{{ $evaluation->vendor->company_name }}</div>
                <div class="text-muted" style="font-size:12px;">Code: {{ $evaluation->vendor->vendor_code }} &bull; {{ $evaluation->vendor->category ?: 'Travel Services' }}</div>
              </div>
            </div>

            <div class="col-12">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Completed Travel Requisition</div>
                <div class="field-value fw-semibold" style="font-size:13.5px;">
                  <a href="{{ route('trf.show', $evaluation->trf) }}" class="text-primary text-decoration-none">
                    {{ $evaluation->trf->trf_number }}
                  </a>
                </div>
                <div class="text-muted" style="font-size:12px;">
                  {{ $evaluation->trf->route ?: '—' }}
                  @if ($evaluation->trf->travel_start)
                    &bull; {{ $evaluation->trf->travel_start->format('d M') }} – {{ optional($evaluation->trf->travel_end)->format('d M Y') ?? '—' }}
                  @endif
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Traveler</div>
                <div class="field-value fw-semibold text-dark" style="font-size:13px;">{{ $evaluation->trf->employee->name }}</div>
                <div class="text-muted" style="font-size:11.5px;">{{ $evaluation->trf->department }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Evaluated By</div>
                <div class="field-value fw-semibold text-dark" style="font-size:13px;">{{ $evaluation->rater?->name ?? 'System' }}</div>
                <div class="text-muted" style="font-size:11.5px;">{{ optional($evaluation->created_at)->format('d M Y, H:i') }}</div>
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
Shell.init({ activeNav: 'traveleval', crumb: 'Travel & Logistics', title: 'Evaluation Detail' });
@endsection
