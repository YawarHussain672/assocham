<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — ASSOCHAM Procurement ERP</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root {
    --maroon: #c8102e;
    --maroon-dark: #a50d24;
    --maroon-deep: #700c1b;
    --orange: #ea580c;
    --gold: #f59e0b;
    --text-dark: #0f172a;
    --text-body: #475569;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  html, body {
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
    background-color: #ffffff;
    color: var(--text-dark);
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
  }

  .login-wrapper {
    display: flex;
    width: 100vw;
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
  }

  /* Left Form Column */
  .login-left-col {
    flex: 0 0 53%;
    width: 53%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: clamp(16px, 3vh, 28px) clamp(28px, 4.5vw, 64px);
    background: #ffffff;
    height: 100vh;
    max-height: 100vh;
    overflow-y: auto;
    scrollbar-width: none;
  }

  .login-left-col::-webkit-scrollbar {
    display: none;
  }

  .login-header-nav {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: clamp(8px, 1.8vh, 16px);
  }

  .brand-logo-img {
    height: clamp(60px, 5vh, 52px);
    width: auto;
    max-width: 220px;
    object-fit: contain;
    display: block;
  }

  .login-form-center {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
  }

  .login-greeting-title {
    font-size: clamp(26px, 3.2vh, 34px);
    font-weight: 600;
    color: #111827;
    line-height: 1.15;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
  }

  .login-greeting-sub {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-muted);
    margin-bottom: clamp(12px, 2vh, 18px);
    line-height: 1.4;
  }

  .form-group-custom {
    margin-bottom: clamp(8px, 1.4vh, 12px);
  }

  .form-label-custom {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
  }

  .input-wrap-custom {
    position: relative;
    display: flex;
    align-items: center;
  }

  .form-control-custom {
    width: 100%;
    height: clamp(42px, 4.8vh, 46px);
    padding: 0 16px;
    font-size: 13.5px;
    font-weight: 500;
    color: #111827;
    background: #ffffff;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
    font-family: inherit;
  }

  .form-control-custom::placeholder {
    color: #9ca3af;
    font-weight: 400;
  }

  .form-control-custom:focus {
    border-color: var(--maroon);
    box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.12);
  }

  .password-toggle-btn {
    position: absolute;
    right: 12px;
    background: transparent;
    border: none;
    color: #6b7280;
    font-size: 17px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    transition: color 0.15s ease;
  }

  .password-toggle-btn:hover {
    color: var(--maroon);
  }

  .login-options-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 25px;
    margin-bottom: clamp(25px, 1.8vh, 16px);
    font-size: 12.5px;
  }

  .custom-checkbox-wrap {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    user-select: none;
    font-weight: 500;
    color: #4b5563;
  }

  .custom-checkbox-wrap input {
    width: 16px;
    height: 16px;
    accent-color: var(--maroon);
    cursor: pointer;
    border-radius: 4px;
  }

  .forgot-link {
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    font-size: 12.5px;
    transition: color 0.15s ease;
  }

  .forgot-link:hover {
    color: var(--maroon);
    text-decoration: underline;
  }

  .btn-submit-login {
    width: 100%;
    height: clamp(42px, 4.8vh, 46px);
    background: linear-gradient(135deg, #c8102e 0%, #a50d24 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 14.5px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(200, 16, 46, 0.28);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .btn-submit-login:hover {
    background: linear-gradient(135deg, #a50d24 0%, #7f1d1d 100%);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(200, 16, 46, 0.36);
  }



  /* Right Visual Showcase Column (Smaller than left) */
  .login-right-col {
    flex: 0 0 47%;
    width: 47%;
    padding: clamp(14px, 2.2vh, 22px) clamp(16px, 2.5vw, 28px) clamp(14px, 2.2vh, 22px) clamp(12px, 1.8vw, 20px);
    height: 100vh;
    max-height: 100vh;
    display: flex;
    align-items: stretch;
    justify-content: stretch;
    overflow: hidden;
  }

  .showcase-card {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 16px 40px rgba(165, 13, 36, 0.2);
    background: #700c1b;
  }

  .showcase-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    border-radius: 12px;
    transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  /* .showcase-card:hover .showcase-image {
    transform: scale(1.02);
  } */


  /* Responsive Rules */
  @media (max-width: 991px) {
    html, body {
      height: auto;
      max-height: none;
      overflow-y: auto;
    }
    .login-wrapper {
      flex-direction: column;
      height: auto;
      max-height: none;
    }
    .login-left-col {
      flex: 1 1 100%;
      width: 100%;
      max-width: 100%;
      padding: 32px 20px;
      height: auto;
      max-height: none;
    }
    .login-right-col {
      display: none;
    }
  }
</style>
</head>
<body>

<div class="login-wrapper">

  <!-- Left Column: Modern Authentication Form -->
  <div class="login-left-col">
    <!-- Top Header / Brand -->
    <div class="login-header-nav">
      <img src="{{ asset('assets/img/assocham-logo.jpg') }}" alt="ASSOCHAM" class="brand-logo-img">
    </div>

    <!-- Center Form Card -->
    <div class="login-form-center">
      <h1 class="login-greeting-title">Holla,<br>Welcome Back</h1>
      <p class="login-greeting-sub">Hey, welcome back to your procurement workspace</p>

      @if (session('status'))
        <div class="alert alert-success py-2 px-3 mb-2 d-flex align-items-center gap-2" style="font-size:12.5px; border-radius:8px;">
          <i class="bi bi-check-circle-fill fs-6"></i>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-2 d-flex align-items-center gap-2" style="font-size:12.5px; border-radius:8px;">
          <i class="bi bi-exclamation-circle-fill fs-6"></i>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('login.store') }}" id="loginForm">
        @csrf

        @php
          $showDemoLoginHelpers = app()->environment('local', 'testing');
        @endphp

        <!-- Email Field -->
        <div class="form-group-custom">
          <label for="emailInput" class="form-label-custom">Email Address</label>
          <div class="input-wrap-custom">
            <input
              type="email"
              class="form-control-custom"
              id="emailInput"
              name="email"
              value="{{ old('email', $showDemoLoginHelpers ? 'rahul.mehta@assocham.test' : '') }}"
              placeholder="{{ $showDemoLoginHelpers ? 'rahul.mehta@assocham.test' : 'Enter your official email' }}"
              required
              autofocus
            >
          </div>
        </div>

        <!-- Password Field -->
        <div class="form-group-custom">
          <label for="passwordInput" class="form-label-custom">Password</label>
          <div class="input-wrap-custom">
            <input
              type="password"
              class="form-control-custom"
              id="passwordInput"
              name="password"
              @if ($showDemoLoginHelpers) value="password" @endif
              placeholder="••••••••••••"
              autocomplete="current-password"
              required
            >
            <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Show or hide password" aria-label="Show or hide password">
              <i class="bi bi-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <!-- Options: Remember Me + Forgot Password -->
        <div class="login-options-row">
          <label class="custom-checkbox-wrap" for="rememberMe">
            <input type="checkbox" id="rememberMe" name="remember" @if ($showDemoLoginHelpers) checked @endif>
            <span>Remember me</span>
          </label>
          <a href="{{ route('password.request') }}" class="forgot-link">Forgot Password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-submit-login">
          <span>Sign In</span>
          <i class="bi bi-arrow-right"></i>
        </button>

        @if ($showDemoLoginHelpers)
          @php
            $demoLogins = [
              ['role' => 'pr_sr_exec', 'email' => 'priya.nair@assocham.test', 'label' => 'PR Creator'],
              ['role' => 'buyer', 'email' => 'rohit.verma@assocham.test', 'label' => 'Buyer'],
              ['role' => 'admin_head', 'email' => 'rahul.mehta@assocham.test', 'label' => 'Admin Head'],
              ['role' => 'pr_approver_l1', 'email' => 'basu@assocham.test', 'label' => 'DSG'],
              ['role' => 'pr_approver_l2', 'email' => 'ambika@assocham.test', 'label' => 'Head of States'],
              ['role' => 'asg1', 'email' => 'asg1@assocham.test', 'label' => 'ASG 1'],
              ['role' => 'asg2', 'email' => 'asg2@assocham.test', 'label' => 'ASG 2'],
              ['role' => 'cfo', 'email' => 'sanjay.menon@assocham.test', 'label' => 'CFO'],
              ['role' => 'sg', 'email' => 'meenakshi.rao@assocham.test', 'label' => 'Secretary Gen'],
              ['role' => 'hod', 'email' => 'hod@assocham.test', 'label' => 'CH / HOD'],
            ];
          @endphp

          <!-- Quick 10-Role Demo Switcher (Local / Testing Only — Automatically Hidden in Production) -->
          <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
              <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">
                <i class="bi bi-person-badge me-1"></i> Quick Role Login (10 Logins)
              </span>
              <span style="font-size:10.5px; color:#94a3b8;">Click to sign in</span>
            </div>
            <div class="d-flex flex-wrap gap-1">
              @foreach ($demoLogins as $demo)
                <button type="button" class="btn btn-sm btn-light border py-1 px-2 text-dark" style="font-size:11px; font-weight:600; border-radius:6px;" onclick="quickLogin('{{ $demo['email'] }}')">
                  {{ $demo['label'] }}
                </button>
              @endforeach
            </div>
          </div>
        @endif

      </form>
    </div>

    <!-- Bottom copyright -->
    <div style="font-size:11px; color:#9ca3af; text-align:left; margin-top:6px;">
      &copy; {{ date('Y') }} ASSOCHAM India. All Rights Reserved.
    </div>
  </div>

  <!-- Right Column: Themed Visual Art Showcase with Actual Image -->
  <div class="login-right-col">
    <div class="showcase-card">
      <img
        src="{{ asset('assets/img/login-showcase.jpg') }}"
        alt="ASSOCHAM Procurement Portal Security"
        class="showcase-image"
      >
    </div>
  </div>

</div>

<script>
  // Password Visibility Toggle
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const passwordInput = document.getElementById('passwordInput');
  const toggleIcon = document.getElementById('toggleIcon');

  if (toggleBtn && passwordInput && toggleIcon) {
    toggleBtn.addEventListener('click', () => {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  @if ($showDemoLoginHelpers)
  function quickLogin(email) {
    const emailInput = document.getElementById('emailInput');
    const passwordInput = document.getElementById('passwordInput');
    const form = document.getElementById('loginForm');
    if (emailInput && passwordInput && form) {
      emailInput.value = email;
      passwordInput.value = 'password';
      form.submit();
    }
  }
  @endif
</script>

</body>
</html>
