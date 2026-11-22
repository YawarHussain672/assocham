<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') — ASSOCHAM Procurement ERP</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}?v=4" rel="stylesheet">
  <style>
    :root {
      --primary: #c8102e !important;
      --primary-hover: #a50d24 !important;
      --primary-subtle: #fdf2f4 !important;
      --btn-primary: #c8102e !important;
      --btn-primary-hover: #a50d24 !important;
      --border-color: #e2e8f0 !important;
      --border-subtle: #f1f5f9 !important;
      --bs-border-color: #e2e8f0 !important;
      --bs-border-color-translucent: #e2e8f0 !important;
      --body-bg: #F4F6F9 !important;
      --page-bg: #F4F6F9 !important;
      --sidebar-width: 260px !important;
      --navbar-height: 60px !important;
    }
  </style>
</head>

<body>

  @php
  // Sidebar/topbar links are resolved through the named routes in routes/web.php
  $appRoutes = [
  'dashboard' => route('dashboard'),
  'onboarding' => route('onboarding'),
  'empanelment' => route('empanelment'),
  'requisition' => route('requisition'),
  'item-catalogs' => route('item-catalogs.index'),
  'rfq' => route('rfq'),
  'po' => route('po'),
  'grn' => route('grn'),
  'trf' => route('trf'),
  'flight' => route('flight'),
  'reimb' => route('reimb'),
  'traveleval' => route('traveleval'),
  'notifications' => route('notifications'),
  'checks-balances' => route('checks-balances'),
  'annual-budget' => route('annual-budget'),
  'events' => route('events.index'),
  'exceptions' => route('exceptions'),
  'audit' => route('audit'),
  'grievance' => route('grievance'),
  'users' => route('users'),
  'change-password' => route('password.change'),
  ];

  $pendingItems = \App\Support\PendingItems::for(auth()->user());
  $pendingTotal = $pendingItems->count();
  $pendingByModule = $pendingItems->countBy('module')->all();
  $unreadGrievanceUpdates = \App\Models\GrievanceUpdate::whereHas('grievance', fn ($q) => $q->where('raised_by', auth()->id()))
  ->whereNull('read_at')
  ->count();
  if ($unreadGrievanceUpdates > 0) {
  $pendingByModule['grievance'] = ($pendingByModule['grievance'] ?? 0) + $unreadGrievanceUpdates;
  }
  $impersonatorAdminId = session('impersonator_admin_id');
  $isImpersonating = $impersonatorAdminId
  && \App\Models\User::whereKey($impersonatorAdminId)->where('role', \App\Support\Roles::ADMIN_HEAD)->exists();
  $canSwitchRole = (bool) config('app.enable_admin_role_switch')
  && (auth()->user()->role === \App\Support\Roles::ADMIN_HEAD || $isImpersonating);

  $authUser = [
  'name' => auth()->user()->name,
  'email' => auth()->user()->email,
  'role' => auth()->user()->role,
  'roleLabel' => auth()->user()->designationLabel(),
  'modules' => \App\Support\Roles::sidebarModulesForUser(auth()->user()),
  'previewRole' => \App\Support\Roles::previewRoleFor(auth()->user()->role),
  'pendingCount' => $pendingTotal,
  'pendingByModule' => $pendingByModule,
  'fiscalYear' => \App\Models\DepartmentBudget::currentFiscalYear(),
  'avatar' => asset('assets/img/avatar.jpg'),
  'canSwitchRole' => $canSwitchRole,
  'isImpersonating' => $isImpersonating,
  ];
  $allRoles = $canSwitchRole ? \App\Support\Roles::labels() : [];
  @endphp

  <div class="app-shell app-wrapper">
    @include('layouts.sidebar')

    <div class="app-main">
      <header class="app-topbar app-navbar d-flex align-items-center justify-content-between w-100" id="topbarSlot"></header>

      {{-- Unified Enterprise Toast Rack --}}
      <div class="erp-toast-container" id="erpToastContainer" aria-live="polite" aria-atomic="true">
        @if (session('status') || session('success'))
        <div class="erp-toast toast-success" data-auto-dismiss="5000">
          <div class="erp-toast-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="erp-toast-content">
            <div class="erp-toast-title">Operation Successful</div>
            <div class="erp-toast-body">{{ session('status') ?: session('success') }}</div>
          </div>
          <button type="button" class="erp-toast-close" onclick="this.closest('.erp-toast').remove()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
          <div class="erp-toast-progress" style="animation-duration: 5000ms;"></div>
        </div>
        @endif

        @if (session('error'))
        <div class="erp-toast toast-error">
          <div class="erp-toast-icon">
            <i class="bi bi-exclamation-octagon-fill"></i>
          </div>
          <div class="erp-toast-content">
            <div class="erp-toast-title">Action Blocked</div>
            <div class="erp-toast-body">{{ session('error') }}</div>
          </div>
          <button type="button" class="erp-toast-close" onclick="this.closest('.erp-toast').remove()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        @endif

        @if (session('warning'))
        <div class="erp-toast toast-warning">
          <div class="erp-toast-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="erp-toast-content">
            <div class="erp-toast-title">Attention Required</div>
            <div class="erp-toast-body">{{ session('warning') }}</div>
          </div>
          <button type="button" class="erp-toast-close" onclick="this.closest('.erp-toast').remove()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        @endif

        @if (session('info'))
        <div class="erp-toast toast-info" data-auto-dismiss="6000">
          <div class="erp-toast-icon">
            <i class="bi bi-info-circle-fill"></i>
          </div>
          <div class="erp-toast-content">
            <div class="erp-toast-title">Information</div>
            <div class="erp-toast-body">{{ session('info') }}</div>
          </div>
          <button type="button" class="erp-toast-close" onclick="this.closest('.erp-toast').remove()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
          <div class="erp-toast-progress" style="animation-duration: 6000ms;"></div>
        </div>
        @endif

        @if ($errors->any())
        <div class="erp-toast toast-error">
          <div class="erp-toast-icon">
            <i class="bi bi-shield-x"></i>
          </div>
          <div class="erp-toast-content">
            <div class="erp-toast-title">Validation Notice ({{ $errors->count() }} {{ Str::plural('issue', $errors->count()) }})</div>
            <div class="erp-toast-body">
              @if ($errors->count() === 1)
              {{ $errors->first() }}
              @else
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
              </ul>
              @endif
            </div>
          </div>
          <button type="button" class="erp-toast-close" onclick="this.closest('.erp-toast').remove()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        @endif
      </div>

      <main class="app-content">
        @yield('content')
      </main>
    </div>
  </div>

  {{-- Hidden logout form; js/shell.js submits it from the sidebar's "Log out" link. --}}
  <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
  </form>

  @if ($canSwitchRole)
  {{-- Hidden switch role form strictly available to Admin Head (or active Admin impersonation session) --}}
  <form id="switchRoleForm" action="{{ route('switch-role') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="role" id="switchRoleInput">
  </form>
  @endif

  <script>
    window.APP_ROUTES = @json($appRoutes);
    window.AUTH_USER = @json($authUser);
    window.ALL_ROLES = @json($allRoles);
    window.switchRole = function(role) {
      const form = document.getElementById('switchRoleForm');
      const input = document.getElementById('switchRoleInput');
      if (form && input) {
        input.value = role;
        form.submit();
      }
    };
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Global Bootstrap Dropdown configuration: Use Popper 'fixed' strategy and viewport boundary
    // so table action menus float freely over cards/tables without getting clipped or causing scrollbars
    if (window.bootstrap && bootstrap.Dropdown) {
      bootstrap.Dropdown.Default.boundary = 'viewport';
      bootstrap.Dropdown.Default.popperConfig = function(defaultConfig) {
        return Object.assign({}, defaultConfig, {
          strategy: 'fixed'
        });
      };
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="{{ asset('js/data.js') }}"></script>
  <script src="{{ asset('js/shell.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    // Unified ERP Toast Manager
    window.showToast = function(message, type = 'success', duration = 5000) {
      const container = document.getElementById('erpToastContainer');
      if (!container) return;

      const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-octagon-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
      };
      const titles = {
        success: 'Operation Successful',
        error: 'Action Blocked',
        warning: 'Attention Required',
        info: 'Information'
      };

      const toast = document.createElement('div');
      toast.className = `erp-toast toast-${type}`;
      toast.innerHTML = `
      <div class="erp-toast-icon">
        <i class="bi ${icons[type] || icons.info}"></i>
      </div>
      <div class="erp-toast-content">
        <div class="erp-toast-title">${titles[type] || 'Notification'}</div>
        <div class="erp-toast-body">${message}</div>
      </div>
      <button type="button" class="erp-toast-close" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
      ${duration > 0 ? `<div class="erp-toast-progress" style="animation-duration: ${duration}ms;"></div>` : ''}
    `;

      toast.querySelector('.erp-toast-close').addEventListener('click', () => {
        toast.classList.add('toast-hiding');
        setTimeout(() => toast.remove(), 300);
      });

      container.appendChild(toast);

      if (duration > 0) {
        setTimeout(() => {
          if (toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
          }
        }, duration);
      }
    };

    // Auto-dismiss initial session toasts
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.erp-toast[data-auto-dismiss]').forEach(toast => {
        const ms = parseInt(toast.getAttribute('data-auto-dismiss'), 10) || 5000;
        setTimeout(() => {
          if (toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
          }
        }, ms);
      });
    });
  </script>
  <script>
    @yield('init')
  </script>
  @yield('scripts')
</body>

</html>