@extends('layouts.app')

@section('title', 'Edit Member Access')

@section('content')
<div class="view-wrap" id="pageContent">
  <a href="{{ route('users') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to User &amp; Access Management</span>
  </a>

  <form method="POST" action="{{ route('users.update', $member) }}">
    @csrf
    @method('PUT')
    @include('pages._user_form', ['member' => $member])
  </form>

  @if ($member->id !== auth()->id())
    @php
      $isActive = $member->isActive();
      $recordCountTotal = array_sum($operationalRecords ?? []);
    @endphp
    <div class="card-panel mt-4">
      <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
        <div class="d-flex align-items-center gap-3">
          <div class="vendor-form-icon-box">
            <i class="bi bi-shield-lock"></i>
          </div>
          <div>
            <h2 class="h5 mb-0 fw-bold text-dark">Account Status &amp; Lifecycle Controls</h2>
            <div class="text-secondary small mt-0.5">Manage member active status, security sessions, and account lifecycle</div>
          </div>
        </div>
        @if ($isActive)
          <span class="badge fw-semibold px-2.5 py-1.5" style="font-size: 11px; background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">Active Account</span>
        @else
          <span class="badge fw-semibold px-2.5 py-1.5" style="font-size: 11px; background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA;">
            Deactivated{{ $member->deactivated_at ? ' · ' . $member->deactivated_at->format('d M Y') : '' }}
          </span>
        @endif
      </div>

      <div class="row g-3 align-items-center">
        <div class="col-12 col-lg-6">
          <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between gap-2" style="background: #F8FAFC;">
            <div>
              <div class="fw-semibold text-dark" style="font-size: 13px;">
                {{ $isActive ? 'Deactivate Account' : 'Reactivate Account' }}
              </div>
              <div class="text-muted" style="font-size: 12px;">
                {{ $isActive
                    ? 'Blocks sign-in, password reset, and terminates active sessions immediately. All historical PRs, POs, TRFs, and approvals stay intact.'
                    : 'Restores portal login access with the member\'s configured role and module permissions.' }}
              </div>
            </div>
            <form method="POST" action="{{ route('users.toggle-status', $member) }}" onsubmit="return confirm({{ json_encode($isActive ? "Deactivate {$member->name}? They will be logged out immediately." : "Reactivate {$member->name}'s portal account?") }});">
              @csrf
              <button type="submit" class="btn btn-sm {{ $isActive ? 'btn-outline-warning text-dark' : 'btn-success' }} fw-semibold px-3">
                {{ $isActive ? 'Deactivate Account' : 'Reactivate Account' }}
              </button>
            </form>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between gap-2" style="background: #FFFBFB; border-color: #FECACA !important;">
            <div>
              <div class="fw-semibold text-danger" style="font-size: 13px;">Delete User Account</div>
              @if ($recordCountTotal > 0)
                <div class="text-muted" style="font-size: 12px;">
                  This member has linked ERP records ({{ collect($operationalRecords)->map(fn ($c, $l) => "{$c} {$l}")->take(3)->implode(', ') }}).
                  Deleting this user will revoke portal access while safely preserving all historical records, approvals, completed work, and audit history.
                </div>
              @else
                <div class="text-muted" style="font-size: 12px;">
                  This member has zero linked ERP records and will be permanently removed from the system.
                </div>
              @endif
            </div>
            @if ($recordCountTotal > 0)
              <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm({{ json_encode("Delete {$member->name} ({$member->email})? Their portal access will be permanently revoked. All completed work ({$recordCountTotal} linked records) and historical audit data will be safely preserved.") }});">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3">
                  <i class="bi bi-trash3 me-1"></i> Delete User (Retain History)
                </button>
              </form>
            @else
              <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm({{ json_encode("Permanently delete {$member->name} ({$member->email})? This cannot be undone.") }});">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3">
                  <i class="bi bi-trash3 me-1"></i> Delete Permanently
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'users', crumb: 'System', title: 'Edit Member Access' });
@endsection
