<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') — ASSOCHAM Procurement ERP</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>

<div class="app-shell">
  <aside class="app-sidebar" id="sidebarSlot"></aside>

  <div class="app-main">
    <header class="app-topbar" id="topbarSlot"></header>
    <div class="page-header-bar" id="pageHeaderSlot"></div>

    <main class="app-content">
      @yield('content')
    </main>
  </div>
</div>

{{-- Hidden logout form; js/shell.js submits it from the sidebar's "Log out" link. --}}
<form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
  @csrf
</form>

@php
  // Sidebar/topbar links are resolved through the named routes in routes/web.php,
  // not hardcoded here, so the two never drift out of sync.
  $appRoutes = [
    'dashboard'     => route('dashboard'),
    'onboarding'    => route('onboarding'),
    'empanelment'   => route('empanelment'),
    'requisition'   => route('requisition'),
    'rfq'           => route('rfq'),
    'ccs'           => route('ccs'),
    'po'            => route('po'),
    'grn'           => route('grn'),
    'trf'           => route('trf'),
    'flight'        => route('flight'),
    'reimb'         => route('reimb'),
    'traveleval'    => route('traveleval'),
    'notifications' => route('notifications'),
    'checks-balances' => route('checks-balances'),
    'exceptions'    => route('exceptions'),
    'audit'         => route('audit'),
    'grievance'     => route('grievance'),
  ];

  // Real authenticated user. `modules` is the server-enforced allow-list
  // (App\Support\Roles::modulesFor / EnsureModuleAccess middleware) — shell.js
  // filters the sidebar against it, but the actual gate is the middleware on
  // each route, not this list. `previewRole` seeds the legacy mock
  // role-switcher (public/js/data.js ROLES) to its closest match so the
  // prototype's placeholder dashboard content isn't randomly mismatched
  // from who's actually logged in — see App\Support\Roles::previewRoleFor.
  // `pendingByModule` drives the sidebar/topbar badge counts (shell.js) —
  // replaces the mock's hardcoded badge: '4' / badge: '6' with the same
  // real cross-module count the Dashboard and Notifications page show
  // (App\Support\PendingItems).
  $pendingItems = \App\Support\PendingItems::for(auth()->user());
  $authUser = [
    'name' => auth()->user()->name,
    'email' => auth()->user()->email,
    'role' => auth()->user()->role,
    'roleLabel' => auth()->user()->roleLabel(),
    'modules' => \App\Support\Roles::modulesFor(auth()->user()->role),
    'previewRole' => \App\Support\Roles::previewRoleFor(auth()->user()->role),
    'pendingCount' => $pendingItems->count(),
    'pendingByModule' => $pendingItems->countBy('module'),
  ];
@endphp
<script>
  window.APP_ROUTES = @json($appRoutes);
  window.AUTH_USER = @json($authUser);
</script>
<script src="{{ asset('js/data.js') }}"></script>
<script src="{{ asset('js/shell.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>@yield('init')</script>
</body>
</html>
