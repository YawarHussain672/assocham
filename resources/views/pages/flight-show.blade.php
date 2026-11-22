@extends('layouts.app')

@section('title', $log->ref)

@php
  $trf = $log->trf;
  $approvedClaim = $trf?->reimbursements->firstWhere('status', 'approved');
  $pendingClaim = $trf?->reimbursements->whereIn('status', ['pending_cfo', 'pending_sg'])->sortByDesc('id')->first();

  if ($approvedClaim) {
    $statusBadge = ['label' => 'Settled / Reconciled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'];
  } elseif ($pendingClaim) {
    $statusBadge = ['label' => 'Settlement Under Audit', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'];
  } else {
    $statusBadge = ['label' => 'Ready to Settle', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'];
  }

  $ext = $log->file_path ? strtolower(pathinfo($log->file_path, PATHINFO_EXTENSION)) : null;
  $proofUrl = $log->file_path ? route('flight.proof', $log) : null;

  $tripEnded = $trf && $trf->travel_end && $trf->travel_end->lt(now());
  $vendorEval = ($trf && $log->vendor_id) ? $trf->evaluations->firstWhere('vendor_id', $log->vendor_id) : null;
  $latestMail = $log->mailTransactions->first();
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <span>&larr; Back to Flight &amp; Boarding Logistics</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success mb-3">
      {{ session('status') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger mb-3">
      {{ $errors->first() }}
    </div>
  @endif

  <!-- Unified Header Card (Vendor Dossier Style) -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="text-start">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $log->ref }}</span>
            <span class="badge" style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
              {{ $statusBadge['label'] }}
            </span>
          </div>
          <div class="detail-meta d-flex flex-wrap align-items-center gap-2">
            <span class="detail-meta-chip"><i class="bi bi-airplane"></i> <span class="meta-label">Type:</span> <strong>{{ $log->type }}</strong></span>
            @if ($trf?->employee)
              <span class="detail-meta-chip"><i class="bi bi-person"></i> <span class="meta-label">Traveler:</span> <strong>{{ $trf->employee->name }}</strong></span>
            @endif
            @if ($trf?->route)
              <span class="detail-meta-chip"><i class="bi bi-geo-alt"></i> <span class="meta-label">Route:</span> <strong>{{ $trf->route }}</strong></span>
            @endif
            @if ($trf)
              <span class="detail-meta-chip">
                <i class="bi bi-file-earmark-text"></i>
                <span class="meta-label">TRF:</span>
                @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'trf'))
                  <a href="{{ route('trf.show', $trf) }}" class="text-primary text-decoration-none fw-semibold font-monospace">{{ $trf->trf_number }}</a>
                @else
                  <strong class="font-monospace text-primary">{{ $trf->trf_number }}</strong>
                @endif
              </span>
            @endif
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <span class="badge bg-light text-dark border px-3 py-2 font-monospace fw-bold" style="font-size:13px;">
            ₹{{ number_format((float) $log->cost, 2) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Integrated Traveller Confirmation Email Strip -->
    <div class="w-100 px-3 px-md-4 py-3 border-top d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3" style="background:#f8fafc;">
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

      <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
        @if ($latestMail && $latestMail->body_html)
          <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold text-nowrap" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#flightMailPreviewModal">
            Preview Mail
          </button>
        @endif
        @if (auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD)
          <form method="POST" action="{{ route('flight.resend-email', $log) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary px-3 py-1 fw-semibold text-nowrap" style="font-size:12px;">
              {{ $latestMail ? 'Resend Email' : 'Send Confirmation Email' }}
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <!-- Main Transaction Dossier (Matching Vendor Dossier UI) -->
  <div class="row g-4 align-items-start">
    <div class="col-12">
      <div class="card shadow-sm border-0 p-4" style="border: 1px solid #e2e8f0 !important; border-radius: 10px; background: #ffffff;">
        <div class="pb-3 mb-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color: #e2e8f0 !important;">
          <h2 class="h5 mb-0 fw-bold text-dark" style="font-size: 19px; letter-spacing: -0.01em;">Transaction Dossier</h2>
          @if ($trf && $trf->travel_start)
            <span class="text-muted small fw-medium" style="font-size: 12.5px;">
              Trip Dates: {{ $trf->travel_start->format('d M') }} &ndash; {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
            </span>
          @endif
        </div>

        <!-- Booking & Travel Details -->
        <div class="mb-4">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Booking &amp; Travel Details</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Linked TRF</div>
              <div class="text-dark font-monospace fw-semibold" style="font-size: 13.5px;">
                @if ($trf)
                  <a href="{{ route('trf.show', $trf) }}" class="text-primary text-decoration-none">{{ $trf->trf_number }}</a>
                @else
                  —
                @endif
              </div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Traveling Employee</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $trf?->employee?->name ?? '—' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Department</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $trf?->department ?: 'General' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Booking Type</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $log->type }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Service Provider</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $log->vendor->company_name ?? 'Direct Booking' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Booking Cost</div>
              <div class="text-dark font-monospace fw-bold" style="font-size: 14.5px;">₹{{ number_format((float) $log->cost, 2) }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Recorded By</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $log->uploader?->name ?? 'System' }}</div>
            </div>

            <div class="col-md-4">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Recorded On</div>
              <div class="text-dark fw-semibold" style="font-size: 13.5px;">{{ optional($log->created_at)->format('d M Y, H:i') ?? '—' }}</div>
            </div>

            @if ($log->vendor && ($vendorEval || ($tripEnded && auth()->user()?->role === \App\Support\Roles::ADMIN_HEAD)))
              <div class="col-md-4">
                <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Partner Evaluation</div>
                @if ($vendorEval)
                  <div class="text-success fw-semibold" style="font-size: 13.5px;">Vendor Rated {{ number_format($vendorEval->averageRating(), 1) }}/5</div>
                @else
                  <a href="{{ route('traveleval.create', ['trf_id' => $trf->id, 'vendor_id' => $log->vendor_id]) }}" class="text-primary fw-semibold text-decoration-none" style="font-size: 13px;">
                    Rate Vendor Performance &rarr;
                  </a>
                @endif
              </div>
            @endif
          </div>
        </div>

        <div style="border-bottom: 1px solid #e2e8f0; margin: 28px 0;"></div>

        <!-- Proof & Settlement Status -->
        <div class="mb-0">
          <div class="mb-3 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <h3 class="h6 mb-0 fw-bold text-dark" style="font-size: 15px;">Proof &amp; Settlement</h3>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Ticket / Boarding Pass Proof</div>
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                @if ($proofUrl)
                  <span class="text-dark fw-semibold" style="font-size: 13px;">{{ strtoupper($ext) }} document attached for audit</span>
                  <a href="{{ $proofUrl }}" target="_blank" class="btn btn-sm btn-outline-primary px-2.5 py-1 fw-semibold text-nowrap" style="font-size: 11.5px;">
                    View File
                  </a>
                @else
                  <span class="text-muted" style="font-size: 13px;">No digital voucher attached yet</span>
                @endif
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-secondary small mb-1" style="font-size: 11.5px; font-weight: 500;">Settlement Status</div>
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                  <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $statusBadge['label'] }}</div>
                  <div class="text-muted" style="font-size: 12px;">
                    @if ($approvedClaim)
                      Reconciled via {{ $approvedClaim->claim_number }}
                    @elseif ($pendingClaim)
                      Linked claim {{ $pendingClaim->claim_number }} pending Finance/SG approval
                    @else
                      Linked to {{ $trf?->trf_number ?? 'TRF' }} for post-travel reconciliation
                    @endif
                  </div>
                </div>
                @if ($approvedClaim || $pendingClaim)
                  <a href="{{ route('reimb.show', $approvedClaim ?? $pendingClaim) }}" class="btn btn-sm btn-outline-secondary px-2.5 py-1 fw-semibold text-nowrap" style="font-size: 11.5px;">
                    View Claim
                  </a>
                @endif
              </div>
            </div>
          </div>
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
