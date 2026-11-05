@php
$navSections = [
[
'label' => 'Overview',
'items' => [
['id' => 'dashboard', 'label' => 'Dashboard', 'iconClass' => 'bi bi-grid-1x2-fill'],
],
],
[
'label' => 'Procurement',
'items' => [
['id' => 'onboarding', 'label' => 'Vendor Onboarding', 'iconClass' => 'bi bi-person-plus-fill'],
['id' => 'empanelment', 'label' => 'Vendor Empanelment', 'iconClass' => 'bi bi-building-check'],
['id' => 'requisition', 'label' => 'Purchase Requisition', 'iconClass' => 'bi bi-file-earmark-text-fill'],
['id' => 'rfq', 'label' => 'RFQ & Quotations', 'iconClass' => 'bi bi-send-fill'],
['id' => 'ccs', 'label' => 'Cost Comparative (CCS)', 'iconClass' => 'bi bi-bar-chart-steps'],
['id' => 'po', 'label' => 'Purchase Orders', 'iconClass' => 'bi bi-receipt-cutoff'],
['id' => 'grn', 'label' => 'Goods Receipt (GRN)', 'iconClass' => 'bi bi-box-seam-fill'],
],
],
[
'label' => 'Travel & Logistics',
'items' => [
['id' => 'trf', 'label' => 'Travel Requisition (TRF)', 'iconClass' => 'bi bi-airplane-fill'],
['id' => 'flight', 'label' => 'Flight & Boarding', 'iconClass' => 'bi bi-ticket-perforated-fill'],
['id' => 'reimb', 'label' => 'Reimbursement & Advance', 'iconClass' => 'bi bi-wallet2'],
['id' => 'traveleval', 'label' => 'Travel Vendor Evaluation', 'iconClass' => 'bi bi-star-fill'],
],
],
[
'label' => 'Governance & Controls',
'items' => [
['id' => 'checks-balances', 'label' => 'Checks & Balances', 'iconClass' => 'bi bi-shield-check'],
['id' => 'exceptions', 'label' => 'Exception Handling', 'iconClass' => 'bi bi-exclamation-octagon-fill'],
['id' => 'grievance', 'label' => 'Grievance Redressal', 'iconClass' => 'bi bi-chat-square-text-fill'],
['id' => 'audit', 'label' => 'Audit Trail', 'iconClass' => 'bi bi-journal-text'],
],
],
[
'label' => 'System',
'items' => [
['id' => 'notifications', 'label' => 'Notifications', 'iconClass' => 'bi bi-bell-fill'],
],
],
];

$allowedSidebarModules = \App\Support\Roles::sidebarModulesFor(auth()->user()?->role);
$pendingTotal = $pendingTotal ?? ($pendingItems ? $pendingItems->count() : 0);
$pendingByModule = $pendingByModule ?? ($pendingItems ? $pendingItems->countBy('module') : collect());
$userInitials = collect(explode(' ', auth()->user()?->name ?? 'User'))
->filter()
->take(2)
->map(fn($w) => strtoupper(substr($w, 0, 1)))
->join('') ?: 'AP';
@endphp

<aside class="app-sidebar" id="sidebarSlot">
  <div class="sidebar-header">
    <a href="{{ route('dashboard') }}" class="brand-logo" title="ASSOCHAM Procurement Portal">
      <img src="{{ asset('assets/img/assocham-logo.jpg') }}" alt="ASSOCHAM Logo" class="brand-logo-img" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
      <span style="display:none; font-size:18px; font-weight:700; color:var(--primary); letter-spacing:0.04em;">ASSOCHAM</span>
    </a>
  </div>

  <div class="sidebar-nav-container">
    @foreach ($navSections as $sec)
    @php
    $visibleItems = array_filter($sec['items'], fn($item) => in_array($item['id'], $allowedSidebarModules, true));
    @endphp
    @if (count($visibleItems) > 0)
    <div class="nav-section-title">{{ $sec['label'] }}</div>
    <ul class="sidebar-nav">
      @foreach ($visibleItems as $item)
      @php
      $badge = $item['id'] === 'notifications' ? $pendingTotal : ($pendingByModule[$item['id']] ?? 0);
      $isActive = ($activeNav ?? '') === $item['id'] || request()->routeIs($item['id']) || request()->routeIs($item['id'].'.*');
      @endphp
      <li class="nav-item" data-module="{{ $item['id'] }}">
        <a href="{{ $appRoutes[$item['id']] ?? '#' }}" class="nav-link {{ $isActive ? 'active' : '' }}">
          <i class="{{ $item['iconClass'] }}"></i>
          <span class="nav-text">{{ $item['label'] }}</span>
          @if ($badge > 0)
          <span class="badge badge-soft-primary nav-badge">{{ $badge }}</span>
          @endif
        </a>
      </li>
      @endforeach
    </ul>
    @endif
    @endforeach
  </div>

  <div class="sidebar-footer d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2 min-w-0">
      <div class="avatar-wrapper">
        <div class="user-avatar-circle" id="userInitials">
          <img src="{{ asset('assets/img/avatar.jpg') }}" alt="User Avatar" class="avatar-img" onerror="this.remove(); this.parentElement.textContent='{{ $userInitials }}';">
        </div>
        <span class="status-indicator online"></span>
      </div>
      <div class="lh-sm min-w-0">
        <div class="fw-bold text-truncate current-user-name" id="userName" style="max-width: 130px;">{{ auth()->user()?->name }}</div>
        <small class="text-muted current-role-badge text-truncate d-block" id="userRoleLabel">{{ auth()->user()?->roleLabel() }}</small>
      </div>
    </div>
    <button type="button" id="sidebarLogoutBtn" class="nav-icon-btn border-0 text-muted" title="Log out" aria-label="Log out">
      <i class="bi bi-box-arrow-right fs-5"></i>
    </button>
  </div>
</aside>