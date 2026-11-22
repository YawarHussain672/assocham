/* ASSOCHAM Procurement ERP — shared sidebar / topbar / page-header shell + persisted prefs.
   Every page includes this before app.js and calls Shell.init({...}). */

const Shell = (() => {
  const PREFS_KEY = 'assocham_erp_prefs';
  const defaultPrefs = { role: 'buyer', currency: 'INR' };

  function loadPrefs() {
    try {
      const saved = JSON.parse(localStorage.getItem(PREFS_KEY));
      if (saved && saved.role && saved.currency && ROLES.some(r => r.id === saved.role)) {
        if (window.AUTH_USER && window.AUTH_USER.previewRole) saved.role = window.AUTH_USER.previewRole;
        return saved;
      }
    } catch (e) { /* ignore corrupt storage */ }
    const prefs = { ...defaultPrefs };
    if (window.AUTH_USER && window.AUTH_USER.previewRole) prefs.role = window.AUTH_USER.previewRole;
    return prefs;
  }
  function savePrefs() { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); }

  let prefs = loadPrefs();
  const listeners = [];

  const NAV_SECTIONS = [
    { label: 'Overview', items: [
      { id: 'dashboard', href: 'index.html', label: 'Dashboard', iconClass: 'bi bi-grid-1x2-fill' },
    ] },
    { label: 'Procurement', items: [
      { id: 'onboarding', href: 'onboarding.html', label: 'Vendor Onboarding', iconClass: 'bi bi-person-plus-fill' },
      { id: 'empanelment', href: 'empanelment.html', label: 'Vendor Empanelment', iconClass: 'bi bi-building-check' },
      { id: 'requisition', href: 'requisition.html', label: 'Purchase Requisitions', iconClass: 'bi bi-file-earmark-text-fill' },
      { id: 'rfq', href: 'rfq.html', label: 'RFQ & Quotations', iconClass: 'bi bi-send-fill' },
      { id: 'po', href: 'po.html', label: 'Purchase Orders', iconClass: 'bi bi-receipt-cutoff' },
      { id: 'grn', href: 'grn.html', label: 'Goods Receipt (GRN)', iconClass: 'bi bi-box-seam-fill' },
      { id: 'events', href: 'events', label: 'Event-Wise PR & PO', iconClass: 'bi bi-calendar-event-fill' },
      { id: 'item-catalogs', href: 'item-catalogs', label: 'Item & Price Master', iconClass: 'bi bi-tags-fill' },
    ] },
    { label: 'Travel & Logistics', items: [
      { id: 'trf', href: 'trf.html', label: 'Travel Requisition (TRF)', iconClass: 'bi bi-airplane-fill' },
      { id: 'flight', href: 'flight.html', label: 'Flight & Boarding', iconClass: 'bi bi-ticket-perforated-fill' },
      { id: 'reimb', href: 'reimb.html', label: 'Reimbursement & Advance', iconClass: 'bi bi-wallet2' },
      // { id: 'traveleval', href: 'traveleval.html', label: 'Travel Vendor Evaluation', iconClass: 'bi bi-star-fill' },
    ] },
    { label: 'Governance & Controls', items: [
      { id: 'checks-balances', href: 'checks-balances.html', label: 'Budget Master Sheet', iconClass: 'bi bi-shield-check' },
      { id: 'annual-budget', href: 'annual-budget', label: 'Annual Budget', iconClass: 'bi bi-cash-stack' },
      // { id: 'exceptions', href: 'exceptions.html', label: 'Exception Handling', iconClass: 'bi bi-exclamation-octagon-fill' },
      { id: 'audit', href: 'audit.html', label: 'Audit Trail', iconClass: 'bi bi-journal-text' },
    ] },
    { label: 'System', items: [
      { id: 'grievance', href: 'grievance.html', label: 'Grievance Redressal', iconClass: 'bi bi-chat-square-text-fill' },
      { id: 'notifications', href: 'notifications.html', label: 'Notifications', iconClass: 'bi bi-bell-fill' },
      { id: 'users', href: 'users', label: 'User & Access Management', iconClass: 'bi bi-people-fill' },
    ] },
  ];

  function routeFor(id, fallbackHref) {
    return (window.APP_ROUTES && window.APP_ROUTES[id]) || fallbackHref;
  }

  function findNavItem(id) {
    for (const sec of NAV_SECTIONS) {
      const found = sec.items.find(i => i.id === id);
      if (found) return found;
    }
    return null;
  }

  function renderSidebar(activeNav) {
    const allowedModules = (window.AUTH_USER && window.AUTH_USER.modules) || [];
    const canSee = (id) => Array.isArray(allowedModules) ? allowedModules.includes(id) : false;

    const pendingByModule = (window.AUTH_USER && window.AUTH_USER.pendingByModule) || {};
    const pendingTotal = (window.AUTH_USER && window.AUTH_USER.pendingCount) || 0;
    const badgeFor = (id) => id === 'notifications' ? pendingTotal : (pendingByModule[id] || 0);

    const nav = NAV_SECTIONS.map(sec => {
      const visibleItems = sec.items.filter(item => canSee(item.id));
      if (visibleItems.length === 0) return '';
      return `
        <div class="nav-section-title">${sec.label}</div>
        <ul class="sidebar-nav">
          ${visibleItems.map(item => {
            const badge = badgeFor(item.id);
            const isActive = item.id === activeNav;
            return `
              <li class="nav-item" data-module="${item.id}">
                <a href="${routeFor(item.id, item.href)}" class="nav-link ${isActive ? 'active' : ''}">
                  <i class="${item.iconClass}"></i>
                  <span class="nav-text">${item.label}</span>
                  ${badge ? `<span class="badge badge-soft-primary nav-badge">${badge}</span>` : ''}
                </a>
              </li>
            `;
          }).join('')}
        </ul>
      `;
    }).join('');

    const sidebarSlot = document.getElementById('sidebarSlot');
    if (!sidebarSlot) return;

    sidebarSlot.innerHTML = `
      <div class="sidebar-header">
        <a href="${routeFor('dashboard', '/')}" class="brand-logo" title="ASSOCHAM Procurement Portal">
          <img src="/assets/img/assocham-logo.jpg" alt="ASSOCHAM Logo" class="brand-logo-img" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
          <span style="display:none; font-size:18px; font-weight:700; color:var(--primary); letter-spacing:0.04em;">ASSOCHAM</span>
        </a>
      </div>

      <div class="sidebar-nav-container">
        ${nav}
      </div>

      <div class="sidebar-footer d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 min-w-0">
          <div class="avatar-wrapper">
            <div class="user-avatar-circle" id="userInitials">
              <img src="${(window.AUTH_USER && window.AUTH_USER.avatar) || '/assets/img/avatar.jpg'}" alt="User Avatar" class="avatar-img" onerror="this.remove();">
            </div>
            <span class="status-indicator online"></span>
          </div>
          <div class="lh-sm min-w-0">
            <div class="fw-bold text-truncate current-user-name" id="userName" style="max-width: 160px;">User</div>
            <small class="text-muted current-role-badge text-truncate d-block" id="userRoleLabel">Role</small>
          </div>
        </div>
        <button type="button" id="sidebarLogoutBtn" class="nav-icon-btn border-0 text-muted" title="Log out" aria-label="Log out">
          <i class="bi bi-box-arrow-right fs-5"></i>
        </button>
      </div>
    `;

    const logoutBtn = document.getElementById('sidebarLogoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => {
        const form = document.getElementById('logoutForm');
        if (form) form.submit();
      });
    }
  }

  function renderUtilityBar() {
    const allowedModules = (window.AUTH_USER && window.AUTH_USER.modules) || [];
    const canSee = (id) => Array.isArray(allowedModules) ? allowedModules.includes(id) : false;
    const pendingTotal = (window.AUTH_USER && window.AUTH_USER.pendingCount) || 0;
    const topbarSlot = document.getElementById('topbarSlot');
    if (!topbarSlot) return;

    topbarSlot.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <button id="sidebarToggle" class="nav-icon-btn d-none d-lg-inline-flex" title="Toggle Sidebar">
          <i class="bi bi-layout-sidebar-inset fs-5"></i>
        </button>
        <button id="mobileSidebarToggle" class="nav-icon-btn d-lg-none" title="Open Menu">
          <i class="bi bi-list fs-4"></i>
        </button>
      </div>

      <div class="d-flex align-items-center gap-2 ms-auto flex-nowrap min-w-0">
        ${canSee('notifications') ? `<a class="nav-icon-btn position-relative flex-shrink-0" href="${routeFor('notifications', 'notifications.html')}" title="Notifications">
          <i class="bi bi-bell fs-5"></i>
          ${pendingTotal ? `<span class="notification-badge-dot"></span>` : ''}
        </a>` : ''}

        <div class="dropdown flex-shrink-0">
          <div class="user-profile-toggle" data-bs-toggle="dropdown" aria-expanded="false" role="button">
            <div class="avatar-wrapper flex-shrink-0">
              <div class="user-avatar-circle" id="topbarUserInitials">
                <img src="${(window.AUTH_USER && window.AUTH_USER.avatar) || '/assets/img/avatar.jpg'}" alt="User Avatar" class="avatar-img" onerror="this.remove();">
              </div>
              <span class="status-indicator online"></span>
            </div>
            <div class="d-none d-lg-block text-start lh-sm min-w-0" style="max-width: 170px;">
              <div class="fw-bold fs-6 current-user-name text-truncate" id="topbarUserName">User</div>
              <small class="text-muted current-role-badge text-truncate d-block" id="topbarUserRole" style="font-size: 11px;">Role</small>
            </div>
            <i class="bi bi-chevron-down text-muted small ms-1 d-none d-lg-inline flex-shrink-0"></i>
          </div>
          <ul class="dropdown-menu dropdown-menu-end shadow-dropdown user-profile-dropdown" style="min-width: 260px; max-height: calc(100vh - 75px); overflow-y: auto;">
            <li class="px-3 py-2 border-bottom">
              <div class="fw-bold current-user-name" id="dropdownUserName">User</div>
              <div class="text-muted small" id="dropdownUserEmail"></div>
            </li>
            <li><a class="dropdown-item py-1.5" href="${routeFor('dashboard', '/')}"><i class="bi bi-grid-1x2 me-2 text-primary"></i> Dashboard</a></li>
            ${canSee('notifications') ? `<li><a class="dropdown-item py-1.5" href="${routeFor('notifications', '/notifications')}"><i class="bi bi-bell me-2 text-warning"></i> Notifications ${pendingTotal ? `<span class="badge badge-soft-warning ms-1">${pendingTotal}</span>` : ''}</a></li>` : ''}
            ${canSee('audit') ? `<li><a class="dropdown-item py-1.5" href="${routeFor('audit', '/audit')}"><i class="bi bi-journal-text me-2 text-info"></i> Audit Trail</a></li>` : ''}
            <li><a class="dropdown-item py-1.5" href="${routeFor('change-password', '/account/password')}"><i class="bi bi-key me-2 text-secondary"></i> Change Password</a></li>
            ${window.AUTH_USER && window.AUTH_USER.canSwitchRole ? `
            <li><hr class="dropdown-divider my-1"></li>
            ${window.AUTH_USER.isImpersonating ? `
            <li class="px-2 py-1.5">
              <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold w-100 py-1" onclick="window.switchRole('admin_head')" style="font-size:11.5px;">
                Return to Admin Head
              </button>
            </li>
            ` : ''}
            <li class="px-3 pt-2 pb-1 text-muted small fw-bold text-uppercase d-flex align-items-center justify-content-between" style="font-size:10.5px; letter-spacing:0.04em;">
              <span>Switch Role (Admin Only)</span>
              <span class="badge bg-light text-secondary border" style="font-size:9.5px; font-weight:600;">10 Roles</span>
            </li>
            <li>
              <div class="role-switcher-scroll" style="max-height: 190px; overflow-y: auto; padding: 2px 0;">
                <ul class="list-unstyled mb-0">
                  ${Object.entries(window.ALL_ROLES || {}).map(([slug, label]) => {
                    const isCurrent = window.AUTH_USER && window.AUTH_USER.role === slug;
                    return `
                      <li>
                        <button type="button" class="dropdown-item d-flex align-items-center justify-content-between small py-1.5 px-3 ${isCurrent ? 'active fw-bold' : ''}" onclick="window.switchRole('${slug}')" style="font-size:12px;">
                          <span>${label}</span>
                          ${isCurrent ? '<i class="bi bi-check2 text-success ms-2"></i>' : ''}
                        </button>
                      </li>
                    `;
                  }).join('')}
                </ul>
              </div>
            </li>
            ` : ''}
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <button type="button" class="dropdown-item text-danger border-0 bg-transparent d-flex align-items-center py-2 px-3" id="topbarLogoutBtn">
                <i class="bi bi-box-arrow-right text-danger me-2"></i> Sign Out
              </button>
            </li>
          </ul>
        </div>
      </div>
    `;

    // Sidebar collapse state
    if (localStorage.getItem('portal_sidebar_collapsed') === 'true') {
      document.body.classList.add('sidebar-collapsed');
    }

    const desktopToggle = document.getElementById('sidebarToggle');
    if (desktopToggle) {
      desktopToggle.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('portal_sidebar_collapsed', document.body.classList.contains('sidebar-collapsed'));
      });
    }

    const mobileToggle = document.getElementById('mobileSidebarToggle');
    if (mobileToggle) {
      mobileToggle.addEventListener('click', () => {
        const shell = document.querySelector('.app-shell') || document.body;
        shell.classList.toggle('sidebar-mobile-open');
      });
    }

    const topbarLogout = document.getElementById('topbarLogoutBtn');
    if (topbarLogout) {
      topbarLogout.addEventListener('click', () => {
        const form = document.getElementById('logoutForm');
        if (form) form.submit();
      });
    }

    ensureMobileBackdrop();
  }

  function ensureMobileBackdrop() {
    if (document.getElementById('mobileBackdrop')) return;
    const el = document.createElement('div');
    el.id = 'mobileBackdrop';
    el.className = 'mobile-backdrop';
    el.addEventListener('click', () => {
      const shell = document.querySelector('.app-shell') || document.body;
      shell.classList.remove('sidebar-mobile-open');
    });
    const shell = document.querySelector('.app-shell') || document.body;
    shell.appendChild(el);
  }

  function renderPageHeader(activeNav, crumb, title) {
    const slot = document.getElementById('pageHeaderSlot');
    if (slot) slot.innerHTML = '';
  }

  function renderRoleState() {
    const auth = window.AUTH_USER;
    const name = auth ? auth.name : 'User';
    const roleLabel = auth ? auth.roleLabel : 'Member';
    const email = auth ? auth.email : '';
    const avatarUrl = (auth && auth.avatar) || '/assets/img/avatar.jpg';
    const initials = name.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'AP';

    const setAvatar = (id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.innerHTML = `<img src="${avatarUrl}" alt="${name}" class="avatar-img" onerror="this.remove(); this.parentElement.textContent='${initials}';">`;
    };

    setAvatar('userInitials');
    setAvatar('topbarUserInitials');

    const setContent = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    setContent('userName', name);
    setContent('userRoleLabel', roleLabel);

    setContent('topbarUserName', name);
    setContent('topbarUserRole', roleLabel);

    setContent('dropdownUserName', name);
    setContent('dropdownUserRole', roleLabel);
    setContent('dropdownUserEmail', email);
  }

  function init({ activeNav, crumb, title }) {
    renderSidebar(activeNav);
    renderUtilityBar();
    renderPageHeader(activeNav, crumb, title);
    renderRoleState();
    return api;
  }

  const api = {
    init,
    getPrefs: () => ({ ...prefs }),
    onChange: (fn) => listeners.push(fn),
    setTitle: (title) => { const el = document.getElementById('pageHeaderTitle'); if (el) el.textContent = title; },
  };
  return api;
})();
