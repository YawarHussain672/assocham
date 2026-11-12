@extends('layouts.app')

@section('title', $log->ref)

@php
  $typeIcons = [
    'Flight ticket' => 'bi-airplane',
    'Hotel' => 'bi-building',
    'Transport' => 'bi-car-front',
    'Other' => 'bi-receipt',
  ];
  $typeIcon = $typeIcons[$log->type] ?? 'bi-receipt';

  $trf = $log->trf;
  $approvedClaim = $trf?->reimbursements->firstWhere('status', 'approved');
  $pendingClaim = $trf?->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg'])->sortByDesc('id')->first();

  if ($approvedClaim) {
    $statusBadge = ['label' => 'Settled / Reconciled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F', 'icon' => 'bi-check-circle-fill'];
  } elseif ($pendingClaim) {
    $statusBadge = ['label' => 'Settlement Under Audit', 'bg' => '#FBF0DC', 'fg' => '#8A5A12', 'icon' => 'bi-clock-history'];
  } else {
    $statusBadge = ['label' => 'Ready to Settle', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8', 'icon' => 'bi-hourglass-split'];
  }

  $ext = $log->file_path ? strtolower(pathinfo($log->file_path, PATHINFO_EXTENSION)) : null;
  $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
  $proofUrl = $log->file_path ? route('flight.proof', $log) : null;

  $tripEnded = $trf && $trf->travel_end && $trf->travel_end->lt(now());
  $vendorEval = ($trf && $log->vendor_id) ? $trf->evaluations->firstWhere('vendor_id', $log->vendor_id) : null;
  $latestMail = $log->mailTransactions->first();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Flight &amp; Boarding Logistics</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <!-- Executive Header Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0 d-flex align-items-center justify-content-center bg-primary-subtle text-primary border border-primary-subtle" style="width:46px; height:46px; border-radius:10px;">
            <i class="bi {{ $typeIcon }} fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.3rem; line-height: 1.2;">{{ $log->ref }} — {{ $log->type }}</span>
              <span class="badge d-inline-flex align-items-center gap-1" style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['fg'] }}; font-size:11.5px; font-weight:600; padding:4px 9px; border-radius:6px;">
                {{ $statusBadge['label'] }}
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span>{{ $trf?->trf_number ?? '—' }}</span>
              <span class="text-secondary">&bull;</span>
              <span>{{ $trf?->employee?->name ?? '—' }}</span>
              @if ($trf?->route)
                <span class="text-secondary">&bull;</span>
                <span>{{ $trf->route }}</span>
              @endif
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <span class="badge bg-light text-dark border px-3 py-2 fw-bold" style="font-size:13px;">
            ₹{{ number_format((float) $log->cost, 2) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Integrated Traveller Confirmation Email Action Strip -->
    <div class="w-100 px-3 px-md-4 py-3 border-top d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3" style="background:#f8fafc;">
      <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
        <div class="rounded-2 d-flex align-items-center justify-content-center bg-white border flex-shrink-0 {{ $latestMail?->status === 'failed' ? 'text-danger' : ($latestMail ? 'text-success' : 'text-secondary') }}" style="width:36px; height:36px;">
          <i class="bi {{ $latestMail?->status === 'failed' ? 'bi-envelope-exclamation' : ($latestMail ? 'bi-envelope-check' : 'bi-envelope') }}"></i>
        </div>
        <div class="min-w-0 flex-grow-1">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold text-dark" style="font-size:13px;">Traveller Confirmation Email</span>
            @if ($latestMail)
              <span class="badge {{ $latestMail->status === 'sent' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}" style="font-size:10.5px;">
                {{ $latestMail->status === 'sent' ? 'Sent' : 'Failed' }}
              </span>
            @endif
          </div>
          <div class="text-muted" style="font-size:12px;">
            @if ($latestMail)
              {{ $latestMail->status === 'sent' ? 'Sent' : 'Attempted' }} to {{ $latestMail->recipient_email }} &bull; {{ optional($latestMail->sent_at)->format('d M Y, H:i') }} ({{ $log->mailTransactions->count() }} {{ Str::plural('mail', $log->mailTransactions->count()) }} stored on {{ $trf?->trf_number ?? 'TRF' }})
            @else
              No confirmation email dispatched yet for {{ $log->ref }}.
            @endif
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
        @if ($latestMail && $latestMail->body_html)
          <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold text-nowrap" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#flightMailPreviewModal">
            Preview Mail
          </button>
        @endif
        @if (auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD)
          <form method="POST" action="{{ route('flight.resend-email', $log) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-3 py-1 fw-semibold text-nowrap" style="font-size:12px;">
              <i class="bi bi-send"></i>
              <span>{{ $latestMail ? 'Resend Email' : 'Send Confirmation Email' }}</span>
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="row g-4">
    <!-- Full-Width Transaction Dossier -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
          <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
            <i class="bi bi-compass text-primary"></i>
            <span>Transaction Dossier</span>
          </div>
          @if ($trf && $trf->travel_start)
            <span class="text-muted small" style="font-size:12px;">
              Trip Dates: {{ $trf->travel_start->format('d M') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
            </span>
          @endif
        </div>

        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Linked TRF</div>
                <div class="field-value" style="font-size:13.5px; font-weight:600;">
                  @if ($trf)
                    <a href="{{ route('trf.show', $trf) }}" class="text-primary text-decoration-none">
                      {{ $trf->trf_number }}
                    </a>
                  @else
                    —
                  @endif
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Traveling Employee</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $trf?->employee?->name ?? '—' }}
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Booking Type</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $log->type }}
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Service Provider</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $log->vendor->company_name ?? 'Direct Booking' }}
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Booking Cost</div>
                <div class="field-value text-dark" style="font-size:14.5px; font-weight:700;">
                  ₹{{ number_format((float) $log->cost, 2) }}
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-md-4">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Recorded By</div>
                <div class="field-value text-dark" style="font-size:13.5px; font-weight:600;">
                  {{ $log->uploader?->name ?? 'System' }}
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 mt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2 text-muted" style="font-size:12px;">
            <span>Department: {{ $trf?->department ?: 'General' }} &bull; Recorded {{ optional($log->created_at)->format('d M Y, H:i') }}</span>
            @if ($log->vendor && $vendorEval)
              <span class="text-success fw-semibold"><i class="bi bi-star-fill me-1"></i>Vendor Rated {{ number_format($vendorEval->averageRating(), 1) }}/5</span>
            @elseif ($log->vendor && $tripEnded && auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD)
              <a href="{{ route('traveleval.create', ['trf_id' => $trf->id, 'vendor_id' => $log->vendor_id]) }}" class="text-primary fw-semibold text-decoration-none">Rate Vendor Performance &rarr;</a>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Equal-Height Row: Proof & Settlement -->
    <div class="col-12 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
          @if ($proofUrl)
            <div class="d-flex align-items-center gap-3 min-w-0">
              <div class="rounded-2 d-flex align-items-center justify-content-center bg-light border flex-shrink-0 {{ $isImage ? 'text-primary' : 'text-danger' }}" style="width:40px; height:40px;">
                <i class="bi {{ $isImage ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }} fs-5"></i>
              </div>
              <div class="min-w-0">
                <div class="fw-semibold text-dark" style="font-size:13.5px;">Ticket / Boarding Pass Proof</div>
                <div class="text-muted" style="font-size:12px;">
                  {{ strtoupper($ext) }} document attached for audit
                </div>
              </div>
            </div>
            <a href="{{ $proofUrl }}" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-3 py-1 fw-semibold text-nowrap flex-shrink-0">
              <i class="bi bi-box-arrow-up-right"></i>
              <span>View File</span>
            </a>
          @else
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-2 d-flex align-items-center justify-content-center bg-light border text-muted flex-shrink-0" style="width:40px; height:40px;">
                <i class="bi bi-file-earmark-x fs-5"></i>
              </div>
              <div>
                <div class="fw-semibold text-dark" style="font-size:13.5px;">Ticket / Boarding Pass Proof</div>
                <div class="text-muted" style="font-size:12px;">No digital voucher attached yet</div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3 min-w-0">
            <div class="rounded-2 d-flex align-items-center justify-content-center bg-light border flex-shrink-0" style="width:40px; height:40px; color:{{ $statusBadge['fg'] }};">
              <i class="bi {{ $statusBadge['icon'] }} fs-5"></i>
            </div>
            <div class="min-w-0">
              <div class="fw-semibold text-dark" style="font-size:13.5px;">Settlement: {{ $statusBadge['label'] }}</div>
              <div class="text-muted" style="font-size:12px;">
                @if ($approvedClaim)
                  Reconciled via {{ $approvedClaim->claim_number }}
                @elseif ($pendingClaim)
                  Linked claim {{ $pendingClaim->claim_number }} pending Finance/SG approval
                @else
                  Linked to {{ $trf?->trf_number ?? 'TRF' }} for post-travel reconciliation
                @endif
              </div>
            </div>
          </div>
          @if ($approvedClaim || $pendingClaim)
            <a href="{{ route('reimb.show', $approvedClaim ?? $pendingClaim) }}" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold text-nowrap flex-shrink-0">
              View Claim
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if ($latestMail && $latestMail->body_html)
    <div class="modal fade" id="flightMailPreviewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light py-2.5 px-3.5 border-bottom">
            <div>
              <h6 class="modal-title fw-bold text-dark mb-0">{{ $latestMail->subject }}</h6>
              <div class="text-muted small" style="font-size: 11.5px;">
                To: {{ $latestMail->recipient_email }} &bull; {{ optional($latestMail->sent_at)->format('d M Y, H:i') }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3 bg-light">
            <iframe srcdoc="{{ $latestMail->body_html }}" style="width:100%; height:420px; border:1px solid #e2e8f0; border-radius:8px; background:#fff;" title="Confirmation Email Preview"></iframe>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Detail' });
@endsection
