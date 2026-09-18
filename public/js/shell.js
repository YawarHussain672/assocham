/* ASSOCHAM Procurement ERP — shared sidebar / topbar / page-header shell + persisted prefs.
   Every page includes this before app.js and calls Shell.init({...}). */

const Shell = (() => {
  const PREFS_KEY = 'assocham_erp_prefs';
  const defaultPrefs = { role: 'buyer', currency: 'INR' };

  function loadPrefs() {
    try {
      const saved = JSON.parse(localStorage.getItem(PREFS_KEY));
      if (saved && saved.role && saved.currency && ROLES.some(r => r.id === saved.role)) {
        // The mock preview-role switcher is separate from real access control
        // (see App\Support\Roles::previewRoleFor), but always reseed it to
        // match whoever's actually logged in — otherwise a stale value from
        // a previous login on the same browser shows the wrong person's
        // placeholder dashboard content next to the real logged-in name.
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
      { id: 'dashboard', href: 'index.html', label: 'Dashboard',
        icon: '<rect x="3" y="3" width="7" height="9" rx="1.3"></rect><rect x="14" y="3" width="7" height="5" rx="1.3"></rect><rect x="14" y="12" width="7" height="9" rx="1.3"></rect><rect x="3" y="16" width="7" height="5" rx="1.3"></rect>' },
    ] },
    { label: 'Procurement', items: [
      { id: 'onboarding', href: 'onboarding.html', label: 'Vendor Onboarding',
        icon: '<circle cx="9" cy="8" r="3.2"></circle><path d="M3.5 20c.6-3.4 3-5.2 5.5-5.2S13.9 16.6 14.5 20"></path><path d="M17 8h5M19.5 5.5v5"></path>' },
      { id: 'empanelment', href: 'empanelment.html', label: 'Vendor Empanelment',
        icon: '<path d="M12 3l7 3v5c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"></path><path d="M9 12l2 2 4-4.2"></path>' },
      { id: 'requisition', href: 'requisition.html', label: 'Purchase Requisition',
        icon: '<rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 8h6M9 12h6M9 16h4"></path>' },
      { id: 'rfq', href: 'rfq.html', label: 'RFQ &amp; Quotations',
        icon: '<path d="M4 5h16v10H8l-4 4z"></path><path d="M8 9h8M8 12h5"></path>' },
      { id: 'ccs', href: 'ccs.html', label: 'Cost Comparative (CCS)', strokeLinecap: true,
        icon: '<path d="M6 20V10M12 20V5M18 20v-7"></path><path d="M3 20h18"></path>' },
      { id: 'po', href: 'po.html', label: 'Purchase Orders',
        icon: '<path d="M6 6h15l-1.6 8H7.5z"></path><circle cx="9" cy="19" r="1.4"></circle><circle cx="18" cy="19" r="1.4"></circle><path d="M6 6L5 3H3"></path>' },
      { id: 'grn', href: 'grn.html', label: 'Goods Receipt (GRN)',
        icon: '<path d="M3 8l9-4 9 4-9 4z"></path><path d="M3 8v8l9 4 9-4V8"></path><path d="M12 12v8"></path>' },
    ] },
    { label: 'Travel &amp; Logistics', items: [
      { id: 'trf', href: 'trf.html', label: 'Travel Requisition',
        icon: '<rect x="4" y="8" width="16" height="12" rx="2"></rect><path d="M9 8V6a2 2 0 012-2h2a2 2 0 012 2v2M12 12v4"></path>' },
      { id: 'flight', href: 'flight.html', label: 'Flight &amp; Boarding', linejoin: true,
        icon: '<path d="M4 13l16-8-5 16-3-6z"></path><path d="M12 15l3 5"></path>' },
      { id: 'reimb', href: 'reimb.html', label: 'Reimbursement &amp; Advance',
        icon: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"></path><path d="M9 8h6M9 12h6M9 16h3"></path>' },
      { id: 'traveleval', href: 'traveleval.html', label: 'Travel Vendor Evaluation', linejoin: true,
        icon: '<path d="M12 4l2.4 5 5.6.5-4.2 3.7 1.3 5.3L12 16.9 7.9 18.5l1.3-5.3L5 9.5l5.6-.5z"></path>' },
    ] },
    { label: 'System', items: [
      { id: 'notifications', href: 'notifications.html', label: 'Notifications',
        icon: '<path d="M18 8.5a6 6 0 10-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5z"></path><path d="M10.5 20a2 2 0 003 0"></path>' },
    ] },
    { label: 'Governance', items: [
      { id: 'checks-balances', href: 'checks-balances.html', label: 'Checks & Balances',
        icon: '<path d="M12 3v18M8 7l-5 9h10zM16 7l-5 9h10z"></path>' },
      { id: 'exceptions', href: 'exceptions.html', label: 'Exception Handling',
        icon: '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path>' },
      { id: 'grievance', href: 'grievance.html', label: 'Grievance Redressal',
        icon: '<path d="M12 9v4M12 16.5h.01"></path><path d="M10.3 3.9L2.9 17.1c-.6 1 .1 2.4 1.3 2.4h15.6c1.2 0 1.9-1.3 1.3-2.4L13.7 3.9c-.6-1.1-2.1-1.1-2.7 0z"></path>' },
      { id: 'audit', href: 'audit.html', label: 'Audit Trail',
        icon: '<circle cx="11" cy="11" r="7"></circle><path d="M11 7.5V11l2.4 1.4M20 20l-3.5-3.5"></path>' },
    ] },
  ];

  // Resolve a nav id to its URL via the route map emitted from web.php
  // (window.APP_ROUTES, set in layouts/app.blade.php). Falls back to the
  // item's literal href if the map isn't available for some reason.
  function routeFor(id, fallbackHref) {
    return (window.APP_ROUTES && window.APP_ROUTES[id]) || fallbackHref;
  }

  function svgIcon(item) {
    const extra = item.strokeLinecap ? ' stroke-linecap="round"' : (item.linejoin ? ' stroke-linejoin="round"' : '');
    return `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"${extra}>${item.icon}</svg>`;
  }

  function findNavItem(id) {
    for (const sec of NAV_SECTIONS) {
      const found = sec.items.find(i => i.id === id);
      if (found) return found;
    }
    return null;
  }

  function renderSidebar(activeNav) {
    // Server-enforced allow-list (App\Support\Roles::modulesFor, checked again
    // per-request by the `module:<id>` route middleware). Filtering here is
    // just so the sidebar doesn't offer links the middleware would 403 on —
    // a missing/undefined AUTH_USER.modules falls back to "show everything"
    // rather than silently hiding the whole sidebar.
    const allowedModules = (window.AUTH_USER && window.AUTH_USER.modules) || null;
    const canSee = (id) => !allowedModules || allowedModules.includes(id);

    // Real counts (App\Support\PendingItems, via window.AUTH_USER) replace
    // the mock's hardcoded badge: '4' / badge: '6' — 'notifications' shows
    // the total across every module, everything else shows its own count.
    const pendingByModule = (window.AUTH_USER && window.AUTH_USER.pendingByModule) || {};
    const pendingTotal = (window.AUTH_USER && window.AUTH_USER.pendingCount) || 0;
    const badgeFor = (id) => id === 'notifications' ? pendingTotal : (pendingByModule[id] || 0);

    const nav = NAV_SECTIONS.map(sec => {
      const visibleItems = sec.items.filter(item => canSee(item.id));
      if (visibleItems.length === 0) return '';
      return `
      <div class="nav-section-label">${sec.label}</div>
      ${visibleItems.map(item => {
        const badge = badgeFor(item.id);
        return `
        <a href="${routeFor(item.id, item.href)}" class="nav-item ${item.id === activeNav ? 'active' : ''}">
          ${svgIcon(item)}
          <span>${item.label}</span>
          ${badge ? `<span class="nav-badge">${badge}</span>` : ''}
        </a>
      `;
      }).join('')}
    `;
    }).join('');

    document.getElementById('sidebarSlot').innerHTML = `
      <div class="sidebar-brand">
        <a href="/" style="display:flex; align-items:center; justify-content:center; width:100%;">
          <img src="/assets/img/assocham-logo.jpg" alt="ASSOCHAM" class="brand-logo" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
          <div style="display:none; font-size:18px; font-weight:600; color:var(--maroon); letter-spacing:0.05em;">ASSOCHAM</div>
        </a>
      </div>
      <nav class="sidebar-nav">${nav}</nav>
      <div class="sidebar-user">
        <div class="user-avatar" id="userInitials"></div>
        <div class="user-meta">
          <div class="user-name" id="userName"></div>
          <div class="user-role" id="userRoleLabel"></div>
        </div>
        <button type="button" id="sidebarLogoutBtn" class="icon-btn" title="Log out" aria-label="Log out">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </div>
    `;

    document.getElementById('sidebarLogoutBtn').addEventListener('click', () => {
      const form = document.getElementById('logoutForm');
      if (form) form.submit();
    });
  }

  function renderUtilityBar() {
    const pendingTotal = (window.AUTH_USER && window.AUTH_USER.pendingCount) || 0;
    document.getElementById('topbarSlot').innerHTML = `
      <button type="button" class="menu-toggle-btn" id="menuToggleBtn" title="Toggle sidebar" aria-label="Toggle sidebar">
        <i class="bi bi-list" style="font-size:19px;"></i>
      </button>

      <div class="topbar-search">
        <div class="search-box">
          <i class="bi bi-search search-box-icon"></i>
          <input type="text" placeholder="Search PRs, RFQs, Vendors…">
          <span class="search-shortcut">&#8984; F</span>
        </div>
      </div>

      <a class="icon-btn" href="${routeFor('notifications', 'notifications.html')}" title="Notifications">
        <i class="bi bi-bell" style="font-size:17px;"></i>
        ${pendingTotal ? `<span class="icon-btn-badge">${pendingTotal}</span>` : ''}
      </a>
    `;

    const mobileQuery = window.matchMedia('(max-width: 780px)');
    document.getElementById('menuToggleBtn').addEventListener('click', () => {
      const shell = document.querySelector('.app-shell');
      shell.classList.toggle(mobileQuery.matches ? 'sidebar-mobile-open' : 'sidebar-collapsed');
    });
    ensureMobileBackdrop();
    mobileQuery.addEventListener('change', () => {
      document.querySelector('.app-shell').classList.remove('sidebar-mobile-open', 'sidebar-collapsed');
    });
  }

  function ensureMobileBackdrop() {
    if (document.getElementById('mobileBackdrop')) return;
    const el = document.createElement('div');
    el.id = 'mobileBackdrop';
    el.className = 'mobile-backdrop';
    el.addEventListener('click', () => {
      document.querySelector('.app-shell').classList.remove('sidebar-mobile-open');
    });
    document.querySelector('.app-shell').appendChild(el);
  }

  function renderPageHeader(activeNav, crumb, title) {
    const slot = document.getElementById('pageHeaderSlot');
    if (!slot) return;
    const navItem = findNavItem(activeNav);
    const section = navItem ? navItem.label : '';
    slot.innerHTML = `
      <div class="page-header-title" id="pageHeaderTitle">${title}</div>
      <div class="page-header-crumb">
        <span>${crumb}</span>
        <i class="bi bi-chevron-right" style="font-size:11px;color:#B0A69D;"></i>
        <span class="crumb-current">${section}</span>
      </div>
    `;
  }

  function renderRoleState() {
    const role = ROLES.find(r => r.id === prefs.role);
    const initials = role.name.split(' ').map(w => w[0]).join('');
    document.getElementById('userInitials').textContent = initials;
    document.getElementById('userName').textContent = role.name;
    document.getElementById('userRoleLabel').textContent = role.label;
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
