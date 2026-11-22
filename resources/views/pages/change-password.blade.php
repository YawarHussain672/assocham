@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('dashboard') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <span>&larr; Back to Dashboard</span>
  </a>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-key"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Change Account Password</h1>
              <div class="text-secondary small mt-0.5">Update your institutional account credentials and security authentication</div>
            </div>
          </div>
        </div>

        @if (session('status'))
          <div class="alert alert-success mb-3">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="current_password" class="vendor-form-label">Current Password <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="current_password" class="form-control vendor-form-control" required autocomplete="current-password">
          </div>

          <div class="mb-3">
            <label for="password" class="vendor-form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="password" class="form-control vendor-form-control" minlength="8" placeholder="Minimum 8 characters" required autocomplete="new-password">
          </div>

          <div class="mb-4">
            <label for="password_confirmation" class="vendor-form-label">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control vendor-form-control" minlength="8" placeholder="Re-enter new password" required autocomplete="new-password">
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
            <a href="{{ route('dashboard') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'dashboard', crumb: 'Account', title: 'Change Password' });
@endsection
