<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password — ASSOCHAM Procurement ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    color: #0f172a;
  }
  .auth-card {
    width: 100%;
    max-width: 440px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 36px 32px;
    box-shadow: 0 10px 30px -12px rgba(15, 23, 42, 0.12);
  }
  .btn-maroon {
    background: #c8102e;
    border-color: #c8102e;
    color: #ffffff;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 9px;
  }
  .btn-maroon:hover {
    background: #a50d24;
    border-color: #a50d24;
    color: #ffffff;
  }
  .form-control {
    border-radius: 9px;
    padding: 10px 14px;
    font-size: 13.5px;
    border: 1.5px solid #e2e8f0;
  }
  .form-control:focus {
    border-color: #c8102e;
    box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.12);
  }
</style>
</head>
<body>
  <div class="auth-card">
    <div class="mb-4">
      <a href="{{ route('login') }}" class="text-decoration-none text-muted small fw-semibold">&larr; Back to Sign In</a>
      <h1 class="h4 fw-bold text-dark mt-3 mb-1">Set New Password</h1>
      <p class="text-muted small mb-0">Choose a strong password (minimum 8 characters) for your ASSOCHAM ERP account.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger small py-2.5 px-3 mb-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="mb-3">
        <label for="email" class="form-label small fw-semibold text-dark">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $email) }}" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label small fw-semibold text-dark">New Password</label>
        <input type="password" id="password" name="password" class="form-control" minlength="8" placeholder="Minimum 8 characters" required autofocus>
      </div>

      <div class="mb-4">
        <label for="password_confirmation" class="form-label small fw-semibold text-dark">Confirm New Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8" placeholder="Re-enter new password" required>
      </div>

      <button type="submit" class="btn btn-maroon w-100">Reset Password</button>
    </form>
  </div>
</body>
</html>
