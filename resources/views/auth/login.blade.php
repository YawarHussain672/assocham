<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — ASSOCHAM Procurement ERP</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('css/style.css') }}" rel="stylesheet">
<style>
  body.login-body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, var(--maroon-dark), var(--maroon) 60%);
    padding: 24px;
  }
  .login-card {
    width: 100%;
    max-width: 380px;
    background: var(--card-bg);
    border-radius: 14px;
    padding: 36px 32px;
    box-shadow: 0 20px 50px rgba(0,0,0,.25);
  }
  .login-brand { font-weight: 700; font-size: 20px; color: var(--maroon); margin-bottom: 4px; }
  .login-sub { color: var(--text-muted2); font-size: 13px; margin-bottom: 28px; }
  .login-card label { font-size: 13px; font-weight: 500; color: var(--text-body); }
  .login-card .form-control { background: var(--field-bg); border-color: var(--field-border); font-size: 14px; }
  .btn-login {
    background: var(--maroon); border-color: var(--maroon); width: 100%;
    font-weight: 500; padding: 10px; margin-top: 8px;
  }
  .btn-login:hover { background: var(--maroon-dark); border-color: var(--maroon-dark); }
  .login-error { font-size: 12.5px; color: #dc2626; margin-top: 4px; }
</style>
</head>
<body class="login-body">

<div class="login-card">
  <div class="login-brand">ASSOCHAM · Admin &amp; Procurement Portal</div>
  <div class="login-sub">Sign in with your role-based account.</div>

  @if ($errors->any())
    <div class="alert alert-danger py-2 px-3" style="font-size:13px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('login.store') }}">
    @csrf
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="remember" name="remember">
      <label class="form-check-label" for="remember" style="font-size:13px;">Remember me</label>
    </div>
    <button type="submit" class="btn btn-primary btn-login">Sign In</button>
  </form>
</div>

</body>
</html>
