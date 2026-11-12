@extends('layouts.app')

@section('title', 'Exception #'.$exception->id)

@php
  $typeMeta = [
    'single_quotation' => 'Single Quotation',
    'budget_exceed' => 'Budget Exceed Override',
    'po_force_close' => 'Purchase Order Force Close',
    'grn_reversal' => 'GRN Reversal',
    'bypass_empanelment_check' => 'Bypass Empanelment Compliance Check',
  ];
  $statusMeta = [
    'pending'  => ['label' => 'Pending Review', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
  ];
  $meta = $statusMeta[$exception->status] ?? $statusMeta['pending'];
  $isApproved = $exception->status === 'approved';
  $isRejected = $exception->status === 'rejected';
  $isPending  = $exception->status === 'pending';
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('exceptions') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Exception Handling</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <!-- Unified Header & Action Dossier Card -->
  <div class="card detail-header-card shadow-sm border-0 mb-4 overflow-hidden w-100" style="border: 1px solid var(--border-color) !important; border-radius: var(--border-radius-base);">
    
    <!-- Top Row: Exception Identity, Metadata, Badges & Actions -->
    <div class="p-3 p-md-4 bg-white w-100">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 w-100 text-start">
        <div class="d-flex align-items-start align-items-sm-center gap-3 text-start">
          <div class="detail-avatar flex-shrink-0" style="background:linear-gradient(135deg,rgba(176,38,58,0.15),rgba(176,38,58,0.05)); color:var(--maroon); border:1px solid rgba(176,38,58,0.2); width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="bi bi-shield-exclamation fs-4"></i>
          </div>
          <div class="text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="detail-name fw-bold text-dark" style="font-size: 1.35rem; line-height: 1.2;">{{ $typeMeta[$exception->exception_type] ?? $exception->exception_type }} #{{ $exception->id }}</span>
              <span class="badge" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px;">
                {{ $meta['label'] }}
              </span>
            </div>
            <div class="detail-meta d-flex flex-wrap align-items-center gap-2 text-muted" style="font-size: 13px;">
              <span><i class="bi bi-person text-secondary me-1"></i>Filed by {{ $exception->requester->name }}</span>
              <span class="text-secondary">•</span>
              <span><i class="bi bi-shield-lock text-secondary me-1"></i>Authority: {{ \App\Support\Roles::label($exception->approver_queue) }}</span>
              @if ($exception->created_at)
                <span class="text-secondary">•</span>
                <span><i class="bi bi-calendar3 text-secondary me-1"></i>{{ $exception->created_at->format('d M Y, H:i') }}</span>
              @endif
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 text-start">
          <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size:12px; font-weight:600;">
            Ref: {{ strtoupper($exception->reference_type ?: 'General') }} #{{ $exception->reference_id ?: '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Horizontal Governance & Audit Rail Timeline Card (On Top) -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-2.5 px-3 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3 text-primary fs-5"></i>
        <h6 class="mb-0 fw-bold text-main" style="font-size: 14.5px;">Governance &amp; Audit Rail</h6>
      </div>
    </div>
    <div class="card-body p-3.5">
      <div class="h-timeline-container">
        <div class="h-timeline-track py-2">

          <!-- Stage 0: Exception Filed -->
          <div class="h-timeline-step is-done">
            <div class="h-step-icon done" title="Exception Filed">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="h-step-card done-card">
              <div class="h-step-title text-success mb-1" title="Exception Lodged">
                Exception Filed
              </div>
              <div class="mb-1.5">
                <span class="badge bg-success-subtle text-success border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  Lodged
                </span>
              </div>
              <div class="h-step-person" title="{{ $exception->requester->name }}">
                {{ $exception->requester->name }}
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $exception->created_at->format('d M, H:i') }}
              </div>
            </div>
          </div>

          <!-- Stage 1: Authority Review -->
          @php
            if ($isApproved) {
              $revStatus = 'is-done';
              $revIcon = 'done';
              $revCard = 'done-card';
            } elseif ($isRejected) {
              $revStatus = 'is-declined';
              $revIcon = 'rejected';
              $revCard = 'active-card border-danger-subtle';
            } else {
              $revStatus = 'is-active';
              $revIcon = 'active';
              $revCard = 'active-card';
            }
          @endphp
          <div class="h-timeline-step {{ $revStatus }}">
            <div class="h-step-icon {{ $revIcon }}">
              @if ($isApproved)
                <i class="bi bi-check-lg"></i>
              @elseif ($isRejected)
                <i class="bi bi-x-lg"></i>
              @else
                <i class="bi bi-hourglass-split"></i>
              @endif
            </div>
            <div class="h-step-card {{ $revCard }}">
              <div class="h-step-title {{ $isApproved ? 'text-success' : ($isRejected ? 'text-danger' : 'text-warning-emphasis') }} mb-1" title="Authority Review">
                Authority Review
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : ($isRejected ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis') }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $meta['label'] }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($exception->decidedBy)
                  <span class="{{ $isRejected ? 'text-danger fw-semibold' : '' }}">{{ $exception->decidedBy->name }}</span>
                @else
                  <span class="text-warning fw-bold">{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $exception->decided_at?->format('d M, H:i') ?? 'Action Required' }}
              </div>
            </div>
          </div>

          <!-- Stage 2: Audit Trail Entry -->
          @php
            if ($isApproved) {
              $audStatus = 'is-done';
              $audIcon = 'done';
              $audCard = 'done-card';
            } elseif ($isRejected) {
              $audStatus = 'is-stopped';
              $audIcon = 'upcoming';
              $audCard = 'stopped-card';
            } else {
              $audStatus = 'is-stopped';
              $audIcon = 'upcoming';
              $audCard = 'stopped-card';
            }
          @endphp
          <div class="h-timeline-step {{ $audStatus }}">
            <div class="h-step-icon {{ $audIcon }}">
              <i class="bi {{ $isApproved ? 'bi-shield-check' : 'bi-lock' }}"></i>
            </div>
            <div class="h-step-card {{ $audCard }}">
              <div class="h-step-title {{ $isApproved ? 'text-success' : 'text-muted' }} mb-1" title="Audit Trail Logged">
                Audit Trail
              </div>
              <div class="mb-1.5">
                <span class="badge {{ $isApproved ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} border py-0.5 px-2" style="font-size: 10px; font-weight: 600;">
                  {{ $isApproved ? 'Committed' : 'Pending' }}
                </span>
              </div>
              <div class="h-step-person">
                @if ($isApproved)
                  <span class="text-success fw-semibold">Recorded in Ledger</span>
                @elseif ($isRejected)
                  <span class="text-danger">Deviation Blocked</span>
                @else
                  <span class="text-muted">Post-Review</span>
                @endif
              </div>
              <div class="h-step-meta mt-0.5">
                {{ $isApproved ? 'Active' : ($isRejected ? 'Blocked' : 'Standby') }}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 align-items-start">
    <!-- Left: Exception Justification & Metadata -->
    <div class="col-12 {{ $canDecide ? 'col-lg-8' : '' }}">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text text-primary"></i>
            <h6 class="mb-0 fw-bold text-dark">Exception Justification &amp; Metadata</h6>
          </div>
          <span class="badge bg-light text-secondary border">
            Ref: {{ strtoupper($exception->reference_type ?: 'General') }} #{{ $exception->reference_id ?: '—' }}
          </span>
        </div>
        <div class="card-body p-4">
          <div class="field-label">Exception Narrative &amp; Justification</div>
          <div class="p-3 rounded-3 mt-1 mb-4 border" style="background:#f8fafc; font-size:14px; line-height:1.6; color:#1e293b;">
            {{ $exception->justification }}
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Requester</div>
                <div class="field-value text-dark" style="font-weight:600;">
                  <i class="bi bi-person text-muted me-1"></i>
                  <span>{{ $exception->requester->name }}</span>
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Filed On</div>
                <div class="field-value text-dark" style="font-weight:600;">
                  <i class="bi bi-clock-history text-muted me-1"></i>
                  <span>{{ $exception->created_at->format('d M Y, H:i') }}</span>
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Linked Transaction</div>
                <div class="field-value text-dark" style="font-weight:600;">
                  @if ($exception->reference_type && $exception->reference_id)
                    <span class="badge badge-soft-primary">
                      {{ strtoupper($exception->reference_type) }} #{{ $exception->reference_id }}
                    </span>
                  @else
                    <span class="text-muted">General Policy Deviation</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label mb-1">Deciding Authority Queue</div>
                <div class="field-value text-dark" style="font-weight:600;">
                  <i class="bi bi-person-badge text-muted me-1"></i>
                  <span>{{ \App\Support\Roles::label($exception->approver_queue) }}</span>
                </div>
              </div>
            </div>
          </div>

          @if ($exception->status !== 'pending')
            <div class="p-3 rounded-3 mt-4 border d-flex align-items-center gap-2" style="background:{{ $exception->status === 'approved' ? '#f0fdf4' : '#fef2f2' }}; border-color:{{ $exception->status === 'approved' ? '#bbf7d0' : '#fecaca' }} !important;">
              <i class="bi {{ $exception->status === 'approved' ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i>
              <div style="font-size:13px; color:#1e293b;">
                <strong>{{ ucfirst($exception->status) }}</strong> by {{ $exception->decidedBy?->name ?? 'System' }} on {{ $exception->decided_at?->format('d M Y, H:i') }}.
              </div>
            </div>
          @elseif (!$canDecide)
            <div class="p-3 rounded-3 mt-4 border d-flex align-items-center gap-2" style="background:#f8fafc;">
              <i class="bi bi-hourglass-split text-warning fs-5"></i>
              <div style="font-size:13px; color:#64748b;">
                Awaiting review from {{ \App\Support\Roles::label($exception->approver_queue) }}. No action required from your role at this time.
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right: Action Decision Area (When Can Decide) -->
    @if ($canDecide && $exception->status === 'pending')
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:22px; height:22px; font-size:11px;">
                <i class="bi bi-pen"></i>
              </div>
              <span style="font-size: 13.5px;">Authority Action Due</span>
            </div>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
              Decision Required
            </span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2">
            <form method="POST" action="{{ route('exceptions.approve', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                <i class="bi bi-check-circle"></i>
                <span>Approve Exception</span>
              </button>
            </form>
            <form method="POST" action="{{ route('exceptions.reject', $exception) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                <i class="bi bi-x-circle"></i>
                <span>Reject Exception</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    @endif
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'exceptions', crumb: 'Governance', title: 'Exception Detail' });
@endsection
