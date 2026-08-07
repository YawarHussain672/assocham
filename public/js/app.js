/* ASSOCHAM Procurement ERP — per-page rendering & interactions (vanilla JS).
   Each HTML page calls one init*Page() function; Shell (shell.js) owns the
   sidebar/topbar chrome and persisted role/currency prefs. */

/* ---------- shared stat-card (used by empanelment, module & grievance strips) ---------- */

function statCardIcon(label) {
  const l = label.toLowerCase();
  if (/(await|pending|draft|under review|to be booked|open|watch)/.test(l)) {
    return 'bi-clock-history';
  }
  if (/(reject|return|blacklist|over budget|non-compliant|flagged|hold|query)/.test(l)) {
    return 'bi-x-circle';
  }
  if (/(approv|empanel|submit|onboard|resolved|on-time|received|accept|confirm|active|award|settle)/.test(l)) {
    return 'bi-check-circle';
  }
  if (/(value|spend|amount|cost|₹|\bcr\b|quote|score)/.test(l)) {
    return 'bi-graph-up';
  }
  return 'bi-info-circle';
}

function statCardHtml(label, count, color) {
  return `
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">${label}</div>
        <div class="stat-count">${count}</div>
      </div>
      <div class="stat-icon-circle" style="background:#f0f2fa;color:#363f7e;">
        <i class="bi ${statCardIcon(label)}" style="font-size:18px;"></i>
      </div>
    </div>
  `;
}

/* ---------- dashboard ---------- */

function renderDashboardContent(prefs) {
  const d = dashboardData(prefs.currency);
  const roleName = ROLES.find(r => r.id === prefs.role).name;

  document.getElementById('greeting').textContent = 'Good morning, ' + roleName.split(' ')[0];
  document.getElementById('greetingSub').textContent = "Here's the procurement snapshot across all six modules for today.";

  document.getElementById('kpiRow').innerHTML = d.kpis.map(k => `
    <div class="kpi-card">
      <div class="kpi-body">
        <div class="kpi-label">${k.label}</div>
        <div class="kpi-value">${k.value}</div>
        <div class="kpi-delta-row">
          <span class="kpi-delta" style="background:${k.up ? '#E5F9F0' : '#FDE6E9'};color:${k.up ? '#0bc472' : '#e40032'};">${k.up ? '↑' : '↓'} ${k.delta}</span>
          <span class="kpi-delta-sub">${k.sub}</span>
        </div>
      </div>
      <div class="kpi-icon-circle" style="background: ${k.iconBg}; color: ${k.iconColor};">
        <i class="bi ${k.icon}" style="font-size:21px;"></i>
      </div>
    </div>
  `).join('');

  document.getElementById('currencyName').textContent = prefs.currency === 'INR' ? 'INR (₹ Cr)' : 'USD ($ M)';
  document.getElementById('spendTotal').textContent = d.spendTotal + ' total';

  document.getElementById('barChart').innerHTML = d.months.map(m => `
    <div class="bar-col">
      <div class="bar-amount">${m.amount}</div>
      <div class="bar-fill" style="height:${m.pct}%;"></div>
    </div>
  `).join('');
  document.getElementById('barLabels').innerHTML = d.months.map(m => `<div class="bar-label">${m.label}</div>`).join('');

  const donutArcs = d.donut.map(seg => `<circle cx="90" cy="90" r="70" fill="none" stroke="${seg.color}" stroke-width="22" stroke-dasharray="${seg.dashArray}" stroke-dashoffset="${seg.dashOffset}" stroke-linecap="butt"></circle>`).join('');
  document.getElementById('donutWrap').innerHTML = `
    <svg width="176" height="176" viewBox="0 0 180 180">
      <circle cx="90" cy="90" r="70" fill="none" stroke="#F1ECE7" stroke-width="22"></circle>
      <g transform="rotate(-90 90 90)">${donutArcs}</g>
      <text x="90" y="84" text-anchor="middle" style="font-family:'Poppins',sans-serif; font-size:30px; font-weight:700; fill:#241F1C;">${d.donutTotal}</text>
      <text x="90" y="104" text-anchor="middle" style="font-family:'Poppins',sans-serif; font-size:11px; fill:#9A8F86;">workflows</text>
    </svg>`;
  document.getElementById('donutLegend').innerHTML = d.donut.map(seg => `
    <div class="donut-legend-row">
      <span class="donut-swatch" style="background:${seg.color};"></span>
      <span class="donut-legend-label">${seg.label}</span>
      <span class="donut-legend-count">${seg.count}</span>
      <span class="donut-legend-pct">${seg.pctLabel}</span>
    </div>
  `).join('');

  document.getElementById('activityList').innerHTML = d.activity.map(a => `
    <div class="activity-item">
      <span class="activity-dot" style="background:${a.dot};"></span>
      <div class="min-w-0">
        <div class="activity-text">${a.text}</div>
        <div class="activity-meta">${a.meta}</div>
      </div>
    </div>
  `).join('');
}

function initDashboardPage() {
  Shell.init({ activeNav: 'dashboard', crumb: 'Overview', title: 'Procurement Dashboard' });
  const draw = () => renderDashboardContent(Shell.getPrefs());
  draw();
  Shell.onChange(draw);
}

/* ---------- vendor empanelment (list / detail / new-vendor) ---------- */

function renderEmpanelmentListInto() {
  document.getElementById('empanelStatStrip').innerHTML = EMPANEL_STATS.map(s => statCardHtml(s.label, s.count, s.color)).join('');

  const vendors = vendorData();
  const head = `
    <div class="vendor-table-head">
      <div>Code</div><div>Vendor Name</div><div>Category</div><div>PAN / GST Status</div><div>Location</div><div>Stage Status</div><div>Action</div>
    </div>`;
  const rows = vendors.map(v => `
    <div class="vendor-row" data-vendor-idx="${v.idx}">
      <div class="vendor-code">${v.code}</div>
      <div class="vendor-name-cell">
        <div class="min-w-0">
          <div class="vendor-name">${v.name}</div>
        </div>
      </div>
      <div class="module-row-c2">${v.cat}</div>
      <div class="pan-gst-cell">
        <span class="${v.panOk ? 'check-ok' : 'check-bad'}">PAN ${v.panOk ? '✓' : '✕'}</span>
        <span class="${v.gstOk ? 'check-ok' : 'check-bad'}">GST ${v.gstOk ? '✓' : '✕'}</span>
      </div>
      <div class="module-row-c2">${v.loc}</div>
      <div><span class="pill" style="background:${v.pillBg};color:${v.pillColor};">${v.status}</span></div>
      <div><button type="button" class="btn-view-drawer" data-vendor-idx="${v.idx}">View Drawer</button></div>
    </div>
  `).join('');
  document.getElementById('vendorTable').innerHTML = head + rows;
}

function renderVendorDetailInto(local) {
  const v = vendorData()[local.selectedVendor];
  const detail = buildDetail(v);
  const tabs = [
    { id: 'details', label: 'Details & Verification' },
    { id: 'documents', label: 'Documents' },
    { id: 'audit', label: 'Audit Log' },
  ];

  const tabsHtml = tabs.map(t => `<button type="button" class="detail-tab-btn ${local.detailTab === t.id ? 'active' : ''}" data-tab="${t.id}">${t.label}</button>`).join('');

  let tabBody = '';
  if (local.detailTab === 'details') {
    tabBody = detail.sections.map(sec => `
      <div class="card-panel">
        <div class="section-card-title">${sec.title}</div>
        <div class="field-grid">
          ${sec.fields.map(f => `
            <div>
              <div class="field-label">${f.label}</div>
              <div class="field-value-row">
                <span class="field-value">${f.value}</span>
                ${f.hasBadge ? `<span class="badge-soft" style="background:${f.ok ? '#E5F9F0' : '#FFF0E5'};color:${f.ok ? '#0bc472' : '#ff6f02'};">${f.badge}</span>` : ''}
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `).join('') + `
      <div class="card-panel">
        <div class="section-card-title mb-1">Self-Declaration &amp; Certification</div>
        <div style="font-size:12.5px;color:#8A8079;margin-bottom:14px;line-height:1.5;">The undersigned warrants the information is correct and declares that the vendor:</div>
        <div class="d-flex flex-column gap-2">
          ${detail.declaration.map(d => `
            <div class="declaration-row">
              <span class="declaration-check"><i class="bi bi-check-lg" style="font-size:12px;"></i></span>
              <span class="declaration-text">${d}</span>
            </div>
          `).join('')}
        </div>
        <div class="signed-row">
          <i class="bi bi-vector-pen" style="font-size:17px;color:var(--maroon);"></i>
          <div>Signed by <strong>${detail.signedBy}</strong> · ${detail.signedDate}</div>
        </div>
      </div>
      <div class="callout">
        <div class="callout-icon"><i class="bi bi-shield-check" style="font-size:17px;"></i></div>
        <div>
          <div class="callout-title">Automated Verification Passed</div>
          <div class="callout-text">PAN format &amp; checksum validated against NSDL pattern. GSTIN structure verified and active on GSTN. Bank account penny-drop confirmed.</div>
        </div>
      </div>`;
  } else if (local.detailTab === 'documents') {
    tabBody = `
      <div class="card-panel">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="section-card-title mb-0">Uploaded Documents</div>
          <button type="button" style="height:34px;padding:0 13px;background:#FBF3EC;color:#5D544C;border:1px dashed #E4B79A;border-radius:4px;font-weight:600;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-cloud-upload" style="font-size:14px;"></i>
            Upload
          </button>
        </div>
        <div class="d-flex flex-column gap-2">
          ${detail.documents.map(doc => `
            <div class="doc-row">
              <div class="doc-ext">${doc.ext}</div>
              <div class="flex-fill min-w-0">
                <div class="doc-name">${doc.name}</div>
                <div class="doc-meta">${doc.meta}</div>
              </div>
              <span class="pill" style="background:${doc.ok ? '#E5F9F0' : '#FFF0E5'};color:${doc.ok ? '#0bc472' : '#ff6f02'};">${doc.state}</span>
              <a href="#" style="font-size:12px;font-weight:600;">View</a>
            </div>
          `).join('')}
        </div>
      </div>`;
  } else {
    tabBody = `
      <div class="card-panel">
        <div class="section-card-title">Change &amp; Audit Log</div>
        <div class="d-flex flex-column">
          ${detail.audit.map((e, i) => `
            <div class="timeline-row">
              <div class="timeline-rail">
                <span class="timeline-dot" style="background:${e.dot};box-shadow:0 0 0 1.5px ${e.dot};"></span>
                ${i < detail.audit.length - 1 ? '<span class="timeline-line"></span>' : ''}
              </div>
              <div class="timeline-body">
                <div class="timeline-text"><strong>${e.actor}</strong> ${e.action}</div>
                <div class="timeline-meta">${e.time} · ${e.ip}</div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>`;
  }

  const stagesHtml = detail.stages.map(st => `
    <div class="stage-row">
      <div class="timeline-rail">
        <span class="stage-node" style="background:${st.st === 'pending' ? '#EFE8E1' : st.nodeColor};${st.st === 'pending' ? 'color:#B0A69D;' : ''}${st.st === 'active' ? 'box-shadow:0 0 0 4px #FBEFE2;' : ''}">${st.icon}</span>
        ${st.isLast ? '' : `<span class="stage-connector" style="background:${st.connectorColor};"></span>`}
      </div>
      <div class="timeline-body">
        <div class="stage-level">${st.level}</div>
        <div class="stage-title">${st.title}</div>
        <div class="stage-role">${st.role}</div>
        ${st.hasMeta ? `<div class="stage-meta" style="background:${st.metaBg};color:${st.metaColor};">${st.meta}</div>` : ''}
      </div>
    </div>
  `).join('');

  const commentsHtml = detail.comments.map(c => `
    <div class="comment-row">
      <div class="comment-avatar" style="background:${c.bg};">${c.initials}</div>
      <div class="min-w-0">
        <div><span class="comment-name">${c.name}</span> <span class="comment-time">· ${c.time}</span></div>
        <div class="comment-text">${c.text}</div>
      </div>
    </div>
  `).join('');

  document.getElementById('vendorDetailWrap').innerHTML = `
    <button type="button" class="back-link" data-action="close-vendor">
      <i class="bi bi-arrow-left" style="font-size:15px;"></i>
      Back to Empanelment Queue
    </button>

    <div class="detail-header-card">
      <div class="detail-avatar" style="background:${detail.avatarBg};">${detail.initials}</div>
      <div class="min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="detail-name">${detail.name}</span>
          <span class="pill" style="background:${detail.pillBg};color:${detail.pillColor};">${detail.status}</span>
        </div>
        <div class="detail-meta">${detail.code} · ${detail.cat} · ${detail.loc}</div>
      </div>
      <div class="flex-fill"></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn-danger-outline">Blacklist</button>
        <button type="button" class="btn-approve">
          <i class="bi bi-check-lg" style="font-size:15px;"></i>
          Approve &amp; Advance
        </button>
      </div>
    </div>

    <div class="detail-tabs">${tabsHtml}</div>

    <div class="detail-grid">
      <div class="detail-col">${tabBody}</div>
      <div class="detail-col">
        <div class="card-panel">
          <div class="section-card-title mb-1">Approval Workflow</div>
          <div style="font-size:11.5px;color:#9A8F86;margin-bottom:16px;">Role-based, sequential sign-off</div>
          <div class="d-flex flex-column">${stagesHtml}</div>
        </div>
        <div class="card-panel">
          <div class="section-card-title">Comments</div>
          <div class="d-flex flex-column gap-3">${commentsHtml}</div>
          <div class="comment-input-row">
            <input type="text" placeholder="Add a comment…">
            <button type="button" class="comment-send"><i class="bi bi-send" style="font-size:14px;"></i></button>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderNewVendorFormInto(local) {
  const uploadSlots = [
    'Government-issued ID Proof',
    'Cancelled Cheque / Bank Statement',
    'PAN Card Copy',
    'GST Registration Letter',
    'MSME Certificate',
    'Incorporation Certificate',
    'Partnership Deed'
  ];
  document.getElementById('newVendorWrap').innerHTML = `
    <button type="button" class="back-link" data-action="close-new-vendor">
      <i class="bi bi-arrow-left" style="font-size:15px;"></i>
      Back to Empanelment Queue
    </button>
    <div class="form-title">New Vendor Registration</div>
    <div class="form-subhead">Complete all sections of the ASSOCHAM Vendor Registration Form. PAN and GST are validated for format at entry.</div>

    <div class="section-card">
      <div class="section-card-title">Personal / Firm Details</div>
      <div class="field-grid">
        <div><div class="field-label-input">Name / Incorporation / Entity</div><input class="field-input" id="newVendorName" placeholder="Legal name of person or firm"></div>
        <div><div class="field-label-input">Contact Person in case of entity</div><input class="field-input" id="newVendorContact" placeholder="Full name of contact person"></div>
        <div>
          <div class="field-label-input">Government ID Number</div>
          <input class="field-input" id="newVendorGovId" placeholder="Aadhaar / Passport / DL / PAN / Election Card">
          <div style="font-size:10px;color:#8A8079;margin-top:4px;">Must match supporting government-issued ID.</div>
        </div>
        <div><div class="field-label-input">Mobile Number</div><input class="field-input" id="newVendorMobile" placeholder="+91"></div>
        <div class="field-col-full"><div class="field-label-input">Registered Address</div><input class="field-input" id="newVendorLoc" placeholder="Full registered address"></div>
        <div><div class="field-label-input">Email ID</div><input class="field-input" id="newVendorEmail" placeholder="name@company.in"></div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-title">Banking Details</div>
      <div class="field-grid">
        <div><div class="field-label-input">Bank Name</div><input class="field-input" id="newVendorBankName" placeholder="e.g. ICICI Bank, HDFC Bank"></div>
        <div><div class="field-label-input">Branch Address</div><input class="field-input" id="newVendorBankBranch" placeholder="Branch name and address"></div>
        <div><div class="field-label-input">Account Holder Name</div><input class="field-input" id="newVendorBankAccHolder" placeholder="Must match bank records exactly"></div>
        <div><div class="field-label-input">Account Number</div><input class="field-input" id="newVendorBankAccNum" placeholder="Account number"></div>
        <div>
          <div class="field-label-input">Type of Account</div>
          <select class="field-input field-select" id="newVendorBankAccType">
            <option value="Current">Current Account</option>
            <option value="Saving">Savings Account</option>
          </select>
        </div>
        <div><div class="field-label-input">IFSC Code</div><input class="field-input" id="newVendorBankIfsc" placeholder="e.g. ICIC0000104"></div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-title">Business &amp; References</div>
      <div class="field-grid">
        <div><div class="field-label-input">Business Category (deals in)</div><input class="field-input" id="newVendorCat" placeholder="e.g. IT &amp; Software, Printing, Facility Mgmt"></div>
        <div>
          <div class="field-label-input">MSME Classification</div>
          <select class="field-input field-select" id="newVendorMsmeType">
            <option value="Micro">Micro Enterprise</option>
            <option value="Small">Small Enterprise</option>
            <option value="Medium">Medium Enterprise</option>
            <option value="Non-MSME">Non-MSME</option>
          </select>
        </div>
        <div><div class="field-label-input">Reference 1</div><input class="field-input" id="newVendorRef1" placeholder="Name, Organization &amp; Contact info"></div>
        <div><div class="field-label-input">Reference 2 (minimum 2 references)</div><input class="field-input" id="newVendorRef2" placeholder="Name, Organization &amp; Contact info"></div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-title">Tax &amp; Statutory Details</div>
      <div class="field-grid">
        <div>
          <div class="field-label-input">PAN Card</div>
          <input class="field-input" id="panInputEl" placeholder="ABCDE1234F" value="${local.panInput}">
          <span id="panBadge"></span>
        </div>
        <div>
          <div class="field-label-input">GST Registration</div>
          <input class="field-input" id="gstInputEl" placeholder="27ABCDE1234F1Z5" value="${local.gstInput}">
          <span id="gstBadge"></span>
        </div>
        <div><div class="field-label-input">MSME / Udyam Number</div><input class="field-input" id="newVendorMsmeNum" placeholder="e.g. UDYAM-XX-00-0000000"></div>
        <div><div class="field-label-input">Incorporation Certificate Number</div><input class="field-input" id="newVendorIncorpCert" placeholder="e.g. U74999DL2022PTC123456"></div>
        <div><div class="field-label-input">Partnership Deed / Other Registration</div><input class="field-input" id="newVendorPartnershipDeed" placeholder="Partnership Deed / Registration Details (if applicable)"></div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-title mb-1">Supporting Documents</div>
      <div style="font-size:12px;color:#8A8079;margin-bottom:14px;">Upload required documents (PDF / JPG). Required: Government ID, Bank Proof (cancelled cheque/statement), PAN card, and optional GST/MSME/Incorp certs.</div>
      <div class="upload-grid">
        ${uploadSlots.map(u => `
          <button type="button" class="upload-btn">
            <span class="upload-icon"><i class="bi bi-cloud-upload" style="font-size:15px;"></i></span>
            <div><div class="upload-title">${u}</div><div class="upload-sub">Click to upload</div></div>
          </button>
        `).join('')}
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-title">Declaration &amp; Certification</div>
      
      <div class="declare-cert-banner">
        <strong>Certification:</strong> I, the undersigned hereby accept and warrant that the information provided in this form is correct and, in the event of changes, details will be provided as soon as possible.
      </div>

      <div class="declare-subhead">Self-Declaration: I, the undersigned, declare that:</div>
      
      <div class="d-flex flex-column declare-clauses-box">
        <div class="declare-clause-item">
          <span class="declare-clause-num">a)</span>
          <span class="declare-clause-desc">I am not involved in any fraudulent or corrupt activities and has not been in the past and is not currently under any investigation for any such activities which would render by me unsuitable for business dealing with ASSOCHAM.</span>
        </div>
        <div class="declare-clause-item">
          <span class="declare-clause-num">b)</span>
          <span class="declare-clause-desc">I am not on, or associated with a company or individual, groups, undertakings and entities that are on the consolidated list established and maintained by the committee established by ASSOCHAM.</span>
        </div>
        <div class="declare-clause-item">
          <span class="declare-clause-num">c)</span>
          <span class="declare-clause-desc">I am not currently removed, invalidated, or suspended by any other Agency.</span>
        </div>
        <div class="declare-clause-item">
          <span class="declare-clause-num">d)</span>
          <span class="declare-clause-desc">I certifies that it has not knowingly provided and will not knowingly provide, in violation of applicable laws, material support or resources to any individual or organization that advocates, plans, sponsors, engages in, or are or has engaged in an act of terrorism.</span>
        </div>
        <div class="declare-clause-item">
          <span class="declare-clause-num">e)</span>
          <span class="declare-clause-desc">I am not related to any employee/consultant at ASSOCHAM.</span>
        </div>
        <div class="declare-clause-item">
          <span class="declare-clause-num">f)</span>
          <span class="declare-clause-desc">I do hereby declare that there is no child labour engaged in our establishment.</span>
        </div>
      </div>

      <div class="field-grid mb-3">
        <div>
          <div class="field-label-input">Authorized Signatory Name</div>
          <input class="field-input" id="newVendorSignatory" placeholder="Full name of signatory" value="${local.signatoryName || ''}">
        </div>
        <div>
          <div class="field-label-input">Signatory Designation</div>
          <input class="field-input" id="newVendorSignatoryTitle" placeholder="e.g. Director / Partner / Proprietor" value="${local.signatoryTitle || ''}">
        </div>
      </div>

      <div class="declare-box declare-checkbox-wrap">
        <input type="checkbox" id="declarationCheck" ${local.declarationChecked ? 'checked' : ''}>
        <div class="declare-text">I hereby accept, warrant, and sign this registration form and self-declaration on behalf of the registered entity.</div>
      </div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn-outline-brand" data-action="close-new-vendor">Cancel</button>
      <button type="button" class="btn-green" id="submitNewVendorBtn" data-action="submit-new-vendor">
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        Submit for Empanelment
      </button>
    </div>
  `;
  updateVendorFormValidation(local);
}

function updateVendorFormValidation(local) {
  const panOk = /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(local.panInput);
  const gstOk = !local.gstInput || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/.test(local.gstInput);
  const panBadge = document.getElementById('panBadge');
  const gstBadge = document.getElementById('gstBadge');
  if (panBadge) panBadge.innerHTML = local.panInput ? `<span class="badge-soft" style="background:${panOk ? '#E5F9F0' : '#FDE6E9'};color:${panOk ? '#0bc472' : '#e40032'};display:inline-block;margin-top:6px;">${panOk ? 'Valid format' : 'Invalid format'}</span>` : '';
  if (gstBadge) gstBadge.innerHTML = local.gstInput ? `<span class="badge-soft" style="background:${gstOk ? '#E5F9F0' : '#FDE6E9'};color:${gstOk ? '#0bc472' : '#e40032'};display:inline-block;margin-top:6px;">${gstOk ? 'Valid format' : 'Invalid format'}</span>` : '';
  const btn = document.getElementById('submitNewVendorBtn');
  if (btn) {
    const isSignatoryOk = !!(local.signatoryName && local.signatoryName.trim());
    const isChecked = !!local.declarationChecked;
    const canSubmit = panOk && gstOk && isSignatoryOk && isChecked;
    btn.style.background = canSubmit ? '#0bc472' : '#C9BEB4';
    btn.style.cursor = canSubmit ? 'pointer' : 'not-allowed';
  }
}

function initEmpanelmentPage() {
  Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Empanelment' });

  const params = new URLSearchParams(location.search);
  const local = {
    selectedVendor: null,
    detailTab: 'details',
    showNewVendor: params.get('new') === '1',
    panInput: '', gstInput: '',
    signatoryName: '', signatoryTitle: '', declarationChecked: false,
  };

  function draw() {
    document.getElementById('view-empanelment-list').classList.add('d-none');
    document.getElementById('view-empanelment-detail').classList.add('d-none');
    document.getElementById('view-new-vendor').classList.add('d-none');

    if (local.showNewVendor) {
      document.getElementById('view-new-vendor').classList.remove('d-none');
      Shell.setTitle('Vendor Empanelment');
      renderNewVendorFormInto(local);
    } else if (local.selectedVendor !== null) {
      document.getElementById('view-empanelment-detail').classList.remove('d-none');
      Shell.setTitle('Vendor Detail');
      renderVendorDetailInto(local);
    } else {
      document.getElementById('view-empanelment-list').classList.remove('d-none');
      Shell.setTitle('Vendor Empanelment');
      renderEmpanelmentListInto();
    }
  }

  document.getElementById('pageContent').addEventListener('click', (e) => {
    if (e.target.closest('#addVendorBtn')) { local.showNewVendor = true; local.selectedVendor = null; draw(); return; }

    const vendorRow = e.target.closest('.vendor-row');
    if (vendorRow) { local.selectedVendor = parseInt(vendorRow.dataset.vendorIdx, 10); local.detailTab = 'details'; draw(); return; }

    const tabBtn = e.target.closest('.detail-tab-btn');
    if (tabBtn) { local.detailTab = tabBtn.dataset.tab; draw(); return; }

    if (e.target.closest('.btn-approve')) {
      if (local.selectedVendor !== null) {
        const v = VENDORS_RAW[local.selectedVendor];
        let nextStage = v.stage;
        let nextStatus = v.status;
        if (v.stage === 0) { nextStage = 1; nextStatus = 'Under Review'; }
        else if (v.stage === 1) { nextStage = 3; nextStatus = 'L2 – ASG'; }
        else if (v.stage === 3) { nextStage = 4; nextStatus = 'L3 – CFO'; }
        else if (v.stage === 4) { nextStage = 5; nextStatus = 'Empanelled'; }

        v.stage = nextStage;
        v.status = nextStatus;

        if (!v.customAudit) {
          const detail = buildDetail(v);
          v.customAudit = [...detail.audit];
        }
        const activePrefs = Shell.getPrefs();
        const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
        const timestamp = formatCurrentDate();
        
        v.customAudit.push({
          actor: `${activeRoleObj.name} (${activeRoleObj.label})`,
          action: `approved at Stage Level and advanced status to ${nextStatus}.`,
          time: timestamp,
          ip: '10.2.14.7',
          dot: '#2E7D4F'
        });

        AUDIT_ROWS.unshift({
          time: timestamp,
          user: activeRoleObj.name,
          role: activeRoleObj.label,
          action: `Approved vendor ${v.code} (${v.name}) status -> ${nextStatus}`,
          module: 'Empanelment'
        });

        draw();
      }
      return;
    }

    if (e.target.closest('.btn-danger-outline')) {
      if (local.selectedVendor !== null) {
        const v = VENDORS_RAW[local.selectedVendor];
        v.status = 'Blacklisted';
        
        if (!v.customAudit) {
          const detail = buildDetail(v);
          v.customAudit = [...detail.audit];
        }
        const activePrefs = Shell.getPrefs();
        const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
        const timestamp = formatCurrentDate();
        
        v.customAudit.push({
          actor: `${activeRoleObj.name} (${activeRoleObj.label})`,
          action: `flagged and blacklisted this vendor record.`,
          time: timestamp,
          ip: '10.2.14.7',
          dot: '#B0263A'
        });

        AUDIT_ROWS.unshift({
          time: timestamp,
          user: activeRoleObj.name,
          role: activeRoleObj.label,
          action: `Blacklisted vendor ${v.code} (${v.name})`,
          module: 'Empanelment'
        });

        draw();
      }
      return;
    }

    const action = e.target.closest('[data-action]');
    if (action) {
      switch (action.dataset.action) {
        case 'close-vendor': local.selectedVendor = null; draw(); break;
        case 'close-new-vendor':
          local.showNewVendor = false;
          local.panInput = '';
          local.gstInput = '';
          local.signatoryName = '';
          local.signatoryTitle = '';
          local.declarationChecked = false;
          draw();
          break;
        case 'submit-new-vendor': {
          const panOk = /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(local.panInput);
          const gstOk = !local.gstInput || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/.test(local.gstInput);
          const isSignatoryOk = !!(local.signatoryName && local.signatoryName.trim());
          const isChecked = !!local.declarationChecked;

          if (!panOk || !gstOk || !isSignatoryOk || !isChecked) {
            alert('Please check all required fields, valid formats, and accept self-declaration before submitting.');
            return;
          }
          const name = document.getElementById('newVendorName').value.trim();
          const contact = document.getElementById('newVendorContact').value.trim();
          const govId = document.getElementById('newVendorGovId').value.trim();
          const mobile = document.getElementById('newVendorMobile').value.trim();
          const loc = document.getElementById('newVendorLoc').value.trim();
          const email = document.getElementById('newVendorEmail').value.trim();

          const bankName = document.getElementById('newVendorBankName').value.trim();
          const bankBranch = document.getElementById('newVendorBankBranch').value.trim();
          const bankAccHolder = document.getElementById('newVendorBankAccHolder').value.trim();
          const bankAccNum = document.getElementById('newVendorBankAccNum').value.trim();
          const bankAccType = document.getElementById('newVendorBankAccType').value;
          const bankIfsc = document.getElementById('newVendorBankIfsc').value.trim();

          const cat = document.getElementById('newVendorCat').value.trim();
          const msme = document.getElementById('newVendorMsmeType').value;
          const ref1 = document.getElementById('newVendorRef1').value.trim();
          const ref2 = document.getElementById('newVendorRef2').value.trim();

          const msmeNum = document.getElementById('newVendorMsmeNum').value.trim();
          const incorpCert = document.getElementById('newVendorIncorpCert').value.trim();
          const partnershipDeed = document.getElementById('newVendorPartnershipDeed').value.trim();

          if (!name) {
            alert('Please enter Vendor Name.');
            return;
          }
          const newCode = 'VND-' + (2000 + VENDORS_RAW.length + 15);
          const newV = {
            name: name,
            code: newCode,
            cat: cat || 'General Supplies',
            pan: local.panInput,
            gst: local.gstInput || '—',
            status: 'Under Review',
            stage: 1,
            av: '#2F6DB0',
            panOk: true,
            gstOk: !!local.gstInput,
            loc: loc || 'New Delhi, DL',
            msme: msme || 'Micro',
            contact: contact,
            govId: govId,
            mobile: mobile,
            email: email,
            bankName: bankName,
            bankBranch: bankBranch,
            bankAccHolder: bankAccHolder,
            bankAccNum: bankAccNum,
            bankAccType: bankAccType,
            bankIfsc: bankIfsc,
            ref1: ref1,
            ref2: ref2,
            msmeNum: msmeNum,
            incorpCert: incorpCert,
            partnershipDeed: partnershipDeed,
            signatoryName: local.signatoryName.trim(),
            signatoryTitle: local.signatoryTitle.trim(),
            signedDate: formatCurrentDate().split(',')[0]
          };
          VENDORS_RAW.unshift(newV);

          const activePrefs = Shell.getPrefs();
          const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
          AUDIT_ROWS.unshift({
            time: formatCurrentDate(),
            user: activeRoleObj.name,
            role: activeRoleObj.label,
            action: `Registered new vendor: ${newCode} (${name})`,
            module: 'Empanelment'
          });

          local.showNewVendor = false;
          local.panInput = '';
          local.gstInput = '';
          local.signatoryName = '';
          local.signatoryTitle = '';
          local.declarationChecked = false;
          draw();
          break;
        }
      }
    }
  });

  document.addEventListener('input', (e) => {
    if (e.target.id === 'panInputEl') {
      local.panInput = (e.target.value || '').toUpperCase();
      if (e.target.value !== local.panInput) e.target.value = local.panInput;
      updateVendorFormValidation(local);
    } else if (e.target.id === 'gstInputEl') {
      local.gstInput = (e.target.value || '').toUpperCase();
      if (e.target.value !== local.gstInput) e.target.value = local.gstInput;
      updateVendorFormValidation(local);
    } else if (e.target.id === 'newVendorSignatory') {
      local.signatoryName = e.target.value;
      updateVendorFormValidation(local);
    } else if (e.target.id === 'newVendorSignatoryTitle') {
      local.signatoryTitle = e.target.value;
      updateVendorFormValidation(local);
    }
  });

  document.addEventListener('change', (e) => {
    if (e.target.id === 'declarationCheck') {
      local.declarationChecked = e.target.checked;
      updateVendorFormValidation(local);
    }
  });

  draw();
}

/* ---------- generic module shell (list) + create form ---------- */

function renderModuleInto(nav, currency, opts) {
  opts = opts || {};
  const c = moduleConfig(currency)[nav];
  const cta = opts.ctaHref
    ? `<a class="btn-brand" href="${opts.ctaHref}">
        <i class="bi bi-plus-lg" style="font-size:15px;"></i>
        ${c.cta}
      </a>`
    : `<button type="button" class="btn-brand" data-action="open-form">
        <i class="bi bi-plus-lg" style="font-size:15px;"></i>
        ${c.cta}
      </button>`;

  document.getElementById('moduleWrap').innerHTML = `
    <div class="page-head-row">
      <div class="page-sub" style="margin-top:0;">${c.desc}</div>
      ${cta}
    </div>

    <div class="stat-strip">
      ${c.stats.map(s => statCardHtml(s[1], s[0], s[2])).join('')}
    </div>

    <div class="card-panel p-0 mt-3 overflow-hidden">
      <div class="table-toolbar-title" style="padding:14px 18px;border-bottom:1px solid #F0EAE4;">${c.tableTitle}</div>
      <div class="module-table-head">
        ${c.columns.map(col => `<div>${col}</div>`).join('')}
      </div>
      ${c.rows.map(r => `
        <div class="module-row">
          <div class="module-row-id">${r[0]}</div>
          <div class="module-row-c1">${r[1]}</div>
          <div class="module-row-c2">${r[2]}</div>
          <div class="module-row-c3">${r[3]}</div>
          <div><span class="pill" style="background:${r[5]};color:${r[6]};">${r[4]}</span></div>
        </div>
      `).join('')}
    </div>

    <div class="note-bar"><span class="note-dot"></span><span class="note-text">${c.note}</span></div>
  `;
}

function renderModuleFormInto(nav, currency) {
  const c = moduleConfig(currency)[nav] || {};
  const spec = MODULE_FORMS[nav] || { fields: [] };
  const uploads = spec.uploads || [];

  document.getElementById('moduleFormWrap').innerHTML = `
    <button type="button" class="back-link" data-action="close-form">
      <i class="bi bi-arrow-left" style="font-size:15px;"></i>
      Back
    </button>
    <div class="form-title">${c.cta || 'New Entry'}</div>
    <div class="form-subhead">New entry · ${c.title || ''}</div>

    ${spec.excel ? `
      <div class="excel-banner">
        <div class="excel-icon"><i class="bi bi-file-earmark-excel" style="font-size:17px;"></i></div>
        <div class="flex-fill" style="min-width:200px;"><div class="excel-title">Bulk-import from Excel</div><div class="excel-sub">Upload a filled template to create multiple requisitions at once — each row is budget-checked on import.</div></div>
        <button type="button" class="btn-white-green">Import .xlsx</button>
      </div>` : ''}

    <div class="section-card">
      <div class="section-card-title">Details</div>
      <div class="field-grid">
        ${spec.fields.map(f => `
          <div class="${(f.w || f.a) ? 'field-col-full' : ''}">
            <div class="field-label-input">${f.l}</div>
            ${f.a ? `<textarea class="field-textarea" placeholder="${f.p}"></textarea>` : `<input class="field-input" placeholder="${f.p}">`}
          </div>
        `).join('')}
      </div>
    </div>

    ${uploads.length ? `
      <div class="section-card">
        <div class="section-card-title">Supporting Documents</div>
        <div class="upload-grid">
          ${uploads.map(u => `
            <button type="button" class="upload-btn">
              <span class="upload-icon"><i class="bi bi-cloud-upload" style="font-size:15px;"></i></span>
              <div><div class="upload-title">${u}</div><div class="upload-sub">Click to upload</div></div>
            </button>
          `).join('')}
        </div>
      </div>` : ''}

    <div class="note-bar"><span class="note-dot"></span><span class="note-text">${c.note || ''}</span></div>

    <div class="form-actions">
      <button type="button" class="btn-outline-brand" data-action="close-form">Cancel</button>
      <button type="button" class="btn-brand" style="height:44px;" data-action="submit-form">
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        Submit for Approval
      </button>
    </div>
  `;
}

function initOnboardingPage() {
  Shell.init({ activeNav: 'onboarding', crumb: 'Procurement', title: 'Vendor Onboarding' });
  const draw = () => renderModuleInto('onboarding', Shell.getPrefs().currency, { ctaHref: 'empanelment.html?new=1' });
  draw();
  Shell.onChange(draw);
}

function handleGenericFormSubmit(nav) {
  const container = document.getElementById('moduleFormWrap');
  if (!container) return;

  const inputs = Array.from(container.querySelectorAll('.field-input, .field-textarea'));
  const vals = inputs.map(i => i.value.trim());

  let newRow = [];
  const refNum = Math.floor(Math.random() * 900) + 100;

  switch (nav) {
    case 'requisition': {
      const title = vals[0] || 'New Requisition';
      const dept = vals[1] || 'Admin';
      const val = parseFloat(vals[4]) || 0;
      newRow = [
        `PR-${8800 + refNum}`,
        title,
        dept,
        `₹${val.toFixed(1)} L`,
        'L1 · Pending',
        '#FBF0DD',
        '#B07A12'
      ];
      break;
    }
    case 'rfq': {
      const pr = vals[0] || 'PR-8835';
      const summary = vals[2] || 'General Procurement';
      const closes = vals[1] || '31 Jul 2026';
      newRow = [
        `RFQ-${3300 + refNum}`,
        pr,
        summary,
        closes,
        'Open',
        '#E7F0FA',
        '#2F6DB0'
      ];
      break;
    }
    case 'po': {
      const pr = vals[0] || 'PR-8835';
      const vendor = vals[1] || 'Delta Office Supplies';
      const val = parseFloat(vals[3]) || 0;
      newRow = [
        `PO-${19000 + refNum}`,
        vendor,
        pr,
        `₹${val.toFixed(1)} L`,
        'Approved',
        '#E5F9F0',
        '#0bc472'
      ];
      break;
    }
    case 'grn': {
      const ref = vals[0] || 'PR-8835 / PO-19190';
      const inv = vals[1] || 'INV-9999';
      const ordQty = parseInt(vals[2], 10) || 10;
      const recQty = parseInt(vals[3], 10) || 10;
      newRow = [
        `GRN-${7700 + refNum}`,
        ref,
        inv,
        `${recQty} / ${ordQty}`,
        'Awaiting',
        '#FBF0DD',
        '#B07A12'
      ];
      break;
    }
    case 'trf': {
      const traveller = vals[0] || 'Test Traveller';
      const dest = vals[3] || 'Delhi';
      const val = parseFloat(vals[12]) || 0;
      newRow = [
        `TRF-${5500 + refNum}`,
        traveller,
        dest,
        `₹${val.toFixed(1)} L`,
        'L1 pending',
        '#FBF0DD',
        '#B07A12'
      ];
      break;
    }
    case 'flight': {
      const traveller = vals[1] || 'Test Traveller';
      const route = vals[2] || 'DEL → BOM';
      const date = vals[3] || '30 Jul 2026';
      newRow = [
        `BKG-${3300 + refNum}`,
        traveller,
        route,
        date,
        'To be booked',
        '#FCEEE0',
        '#C77A00'
      ];
      break;
    }
    case 'reimb': {
      const traveller = vals[1] || 'Test Traveller';
      const adv = parseFloat(vals[2]) || 0;
      const claim = parseFloat(vals[3]) || 0;
      const net = claim - adv;
      newRow = [
        `CLM-${2200 + refNum}`,
        traveller,
        `₹${adv.toFixed(1)} L / ₹${claim.toFixed(1)} L`,
        `${net >= 0 ? '' : '−'}₹${Math.abs(net).toFixed(1)} L`,
        'Awaiting finance',
        '#FBF0DD',
        '#B07A12'
      ];
      break;
    }
    case 'traveleval': {
      const vendor = vals[0] || 'Orion Travel Desk LLP';
      const cat = vals[1] || 'Travel Agency';
      const score = parseFloat(vals[3]) || 4.0;
      newRow = [
        vendor,
        cat,
        '1',
        score.toFixed(1),
        'Active',
        '#E7F0FA',
        '#2F6DB0'
      ];
      break;
    }
  }

  if (newRow.length > 0) {
    if (!MODULE_ROWS[nav]) {
      MODULE_ROWS[nav] = [];
    }
    MODULE_ROWS[nav].unshift(newRow);

    const activePrefs = Shell.getPrefs();
    const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
    const moduleLabel = moduleConfig(activePrefs.currency)[nav].title;

    AUDIT_ROWS.unshift({
      time: formatCurrentDate(),
      user: activeRoleObj.name,
      role: activeRoleObj.label,
      action: `Created new ${moduleLabel} record: ${newRow[0]}`,
      module: moduleLabel
    });
  }
}

function initModulePage(nav, crumb, title) {
  Shell.init({ activeNav: nav, crumb, title });
  const local = { showForm: false };

  function draw() {
    document.getElementById('view-module').classList.toggle('d-none', local.showForm);
    document.getElementById('view-module-form').classList.toggle('d-none', !local.showForm);
    const currency = Shell.getPrefs().currency;
    if (local.showForm) renderModuleFormInto(nav, currency);
    else renderModuleInto(nav, currency);
  }

  document.getElementById('pageContent').addEventListener('click', (e) => {
    const action = e.target.closest('[data-action]');
    if (!action) return;
    if (action.dataset.action === 'open-form') { local.showForm = true; draw(); }
    else if (action.dataset.action === 'close-form' || action.dataset.action === 'submit-form') {
      if (action.dataset.action === 'submit-form') {
        handleGenericFormSubmit(nav);
      }
      local.showForm = false;
      draw();
    }
  });

  Shell.onChange(draw);
  draw();
}

/* ---------- cost comparative statement ---------- */

function renderCCSInto() {
  const ccs = ccsData();
  const isApproved = ccs.committeeStatus === 'Approved by Procurement Committee';

  document.getElementById('ccsWrap').innerHTML = `
    <div class="page-head-row">
      <div class="page-sub" style="margin-top:0;">Prepared by the Buyer and compared side-by-side with delivery timelines, compliance and remarks. The lowest compliant quote (L1) is flagged, but a Purchase Order cannot be created until the Procurement Committee approves this statement.</div>
      <button type="button" class="btn-brand" id="newCcsBtn">
        <i class="bi bi-plus-lg" style="font-size:15px;"></i>
        New Comparative Statement
      </button>
    </div>

    <div class="ccs-stat-strip">
      <div class="ccs-stat-card"><div class="ccs-stat-label">RFQ</div><div class="ccs-stat-value mono">${ccs.rfq}</div></div>
      <div class="ccs-stat-card"><div class="ccs-stat-label">Linked PR</div><div class="ccs-stat-value mono">${ccs.pr}</div></div>
      <div class="ccs-stat-card"><div class="ccs-stat-label">Prepared By</div><div class="ccs-stat-value">${ccs.preparedBy}</div></div>
      <div class="ccs-stat-card"><div class="ccs-stat-label">Quotes</div><div class="ccs-stat-value" style="color:#0bc472;">${ccs.quotes}</div></div>
      <div class="ccs-stat-card"><div class="ccs-stat-label">Committee Status</div><div class="ccs-stat-value" style="color:${isApproved ? '#0bc472' : '#ff6f02'};font-size:13px;">${ccs.committeeStatus}</div></div>
    </div>

    <div class="card-panel p-0 mt-3 overflow-hidden">
      <div class="table-toolbar-title" style="padding:14px 18px;border-bottom:1px solid #F0EAE4;">${ccs.requirement}</div>
      <div class="ccs-table-head">
        <div>Vendor</div><div>Quoted (incl. GST)</div><div>Delivery</div><div>Compliance</div><div>Docs</div><div>Remarks</div>
      </div>
      ${ccs.rows.map(r => `
        <div class="ccs-row ${r.l1 ? 'is-l1' : ''}">
          <div class="d-flex align-items-center gap-2 min-w-0">
            <div class="ccs-vendor-avatar">${r.initials}</div>
            <div class="min-w-0">
              <div class="vendor-name">${r.vendor}</div>
              ${r.l1 ? '<span class="l1-badge">L1 · LOWEST COMPLIANT</span>' : ''}
            </div>
          </div>
          <div class="ccs-amount">${r.amount}</div>
          <div class="module-row-c2">${r.delivery}</div>
          <div><span class="pill" style="background:${r.pillBg};color:${r.pillColor};">${r.compliance}</span></div>
          <div><a href="#">${r.docs}</a></div>
          <div class="ccs-remarks">${r.remarks}</div>
        </div>
      `).join('')}
    </div>

    <div class="note-bar mt-3">
      <span class="note-dot" style="background:#F4A81D;"></span>
      <span class="note-text">L1 is the lowest quote among fully compliant vendors. Non-compliant quotes are disqualified regardless of price. Procurement Committee approval is mandatory before a PO can be created.</span>
    </div>
    <div class="d-flex align-items-center justify-content-end gap-3 mt-3 flex-wrap">
      <button type="button" class="btn-brand" id="submitToCommitteeBtn" ${isApproved ? 'disabled style="opacity: .6; cursor: not-allowed;"' : ''}>
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        ${isApproved ? 'Submitted &amp; Approved' : 'Submit to Procurement Committee'}
      </button>
      <button type="button" class="btn-green" id="proceedToPoBtn" ${isApproved ? '' : 'disabled title="Locked until Procurement Committee approves" style="opacity:.55;cursor:not-allowed;"'}>
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        Proceed to PO with L1 vendor
      </button>
    </div>
  `;
}

function renderCCSFormInto() {
  const container = document.getElementById('ccsFormWrap');
  if (!container) return;

  container.innerHTML = `
    <button type="button" class="back-link" data-action="close-ccs-form">
      <i class="bi bi-arrow-left" style="font-size:15px;"></i>
      Back
    </button>
    <div class="form-title">New Cost Comparative Statement</div>
    <div class="form-subhead">Prepare a new comparative sheet against an RFQ</div>

    <div class="section-card">
      <div class="section-card-title">Comparative Details</div>
      <div class="field-grid">
        <div>
          <div class="field-label-input">Linked RFQ</div>
          <input class="field-input" id="ccsRFQ" placeholder="e.g. RFQ-3310" value="RFQ-3310">
        </div>
        <div>
          <div class="field-label-input">Linked Purchase Requisition</div>
          <input class="field-input" id="ccsPR" placeholder="e.g. PR-8842" value="PR-8842">
        </div>
        <div class="field-col-full">
          <div class="field-label-input">Requirement Summary</div>
          <input class="field-input" id="ccsRequirement" placeholder="e.g. 40 business laptops" value="40 business laptops">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn-outline-brand" data-action="close-ccs-form">Cancel</button>
      <button type="button" class="btn-brand" id="submitNewCCSBtn" style="height:44px;">
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        Create Statement
      </button>
    </div>
  `;
}

function initCCSPage() {
  Shell.init({ activeNav: 'ccs', crumb: 'Procurement', title: 'Cost Comparative Statement' });
  const local = { showForm: false };

  function draw() {
    document.getElementById('view-ccs-list').classList.toggle('d-none', local.showForm);
    document.getElementById('view-ccs-form').classList.toggle('d-none', !local.showForm);

    if (local.showForm) {
      renderCCSFormInto();
    } else {
      renderCCSInto();
    }
  }

  document.getElementById('pageContent').addEventListener('click', (e) => {
    if (e.target.closest('#newCcsBtn')) {
      local.showForm = true;
      draw();
      return;
    }

    const action = e.target.closest('[data-action]');
    if (action) {
      if (action.dataset.action === 'close-ccs-form') {
        local.showForm = false;
        draw();
        return;
      }
    }

    if (e.target.closest('#submitNewCCSBtn')) {
      const rfq = document.getElementById('ccsRFQ').value.trim() || 'RFQ-3310';
      const pr = document.getElementById('ccsPR').value.trim() || 'PR-8842';
      const req = document.getElementById('ccsRequirement').value.trim() || 'General Procurement';

      CCS_STATUS.rfq = rfq;
      CCS_STATUS.pr = pr;
      CCS_STATUS.requirement = req;
      CCS_STATUS.committeeStatus = 'Pending Procurement Committee Approval';

      CCS_ROWS_RAW.length = 0;
      CCS_ROWS_RAW.push(
        { vendor: 'Bharat Infotech Systems', amount: '₹14.8 L', delivery: '2 weeks', compliance: 'Full', docs: '4 docs', remarks: 'Standard OEM warranty', comp: 'ok', l1: true },
        { vendor: 'Sunrise Facility Services', amount: '₹15.2 L', delivery: '3 weeks', compliance: 'Full', docs: '4 docs', remarks: 'Slightly higher unit cost', comp: 'ok', l1: false },
        { vendor: 'Delta Office Supplies', amount: '₹13.9 L', delivery: '4 weeks', compliance: 'Non-compliant', docs: '2 docs', remarks: 'Failed commercial compliance check', comp: 'rej', l1: false }
      );

      const activePrefs = Shell.getPrefs();
      const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
      AUDIT_ROWS.unshift({
        time: formatCurrentDate(),
        user: activeRoleObj.name,
        role: activeRoleObj.label,
        action: `Prepared new Cost Comparative Statement for ${rfq}`,
        module: 'Cost Comparative'
      });

      local.showForm = false;
      draw();
      return;
    }

    if (e.target.closest('#submitToCommitteeBtn')) {
      CCS_STATUS.committeeStatus = 'Approved by Procurement Committee';

      const activePrefs = Shell.getPrefs();
      const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
      AUDIT_ROWS.unshift({
        time: formatCurrentDate(),
        user: activeRoleObj.name,
        role: activeRoleObj.label,
        action: `Procurement Committee approved Cost Comparative Statement for ${CCS_STATUS.rfq}`,
        module: 'Cost Comparative'
      });

      draw();
      return;
    }

    if (e.target.closest('#proceedToPoBtn')) {
      alert('Cost Comparative Statement successfully approved. Redirecting to PO Creation workflow...');
      window.location.href = 'po.html';
    }
  });

  draw();
}

/* ---------- notifications / audit ---------- */

function renderNotificationsInto() {
  document.getElementById('notificationsList').innerHTML = NOTIFICATIONS.map(n => `
    <div class="notif-row" style="background:${n.unread ? n.bg : '#fff'};">
      <div class="notif-icon" style="background:${n.iconBg};color:${n.iconColor};">${n.glyph}</div>
      <div class="flex-fill min-w-0">
        <div class="notif-text">${n.text}</div>
        <div class="notif-time">${n.time}</div>
      </div>
      ${n.unread ? '<span class="notif-unread-dot"></span>' : ''}
    </div>
  `).join('');
}

function initNotificationsPage() {
  Shell.init({ activeNav: 'notifications', crumb: 'System', title: 'Notifications' });
  renderNotificationsInto();
  document.getElementById('markAllReadBtn').addEventListener('click', () => {
    NOTIFICATIONS.forEach(n => { n.unread = false; });
    renderNotificationsInto();
  });
}

function initAuditPage() {
  Shell.init({ activeNav: 'audit', crumb: 'System', title: 'Audit Trail' });
  document.getElementById('auditList').innerHTML = AUDIT_ROWS.map(a => `
    <div class="audit-row">
      <div class="audit-time">${a.time}</div>
      <div><div class="audit-user">${a.user}</div><div class="audit-role">${a.role}</div></div>
      <div class="audit-action">${a.action}</div>
      <div><span class="audit-module-chip">${a.module}</span></div>
    </div>
  `).join('');

  document.getElementById('exportAuditBtn').addEventListener('click', () => {
    const header = ['Timestamp', 'User', 'Role', 'Action', 'Module'];
    const csvEscape = (v) => `"${String(v).replace(/"/g, '""')}"`;
    const lines = [header, ...AUDIT_ROWS.map(a => [a.time, a.user, a.role, a.action, a.module])]
      .map(row => row.map(csvEscape).join(','));
    const blob = new Blob([lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'assocham-audit-trail.csv';
    link.click();
    URL.revokeObjectURL(url);
  });
}

/* ---------- grievance redressal ---------- */

function renderGrievanceFormInto() {
  const container = document.getElementById('grievanceFormWrap');
  if (!container) return;

  container.innerHTML = `
    <button type="button" class="back-link" data-action="close-grievance-form">
      <i class="bi bi-arrow-left" style="font-size:15px;"></i>
      Back
    </button>
    <div class="form-title">Raise Grievance</div>
    <div class="form-subhead">Escalate an issue through the organizational hierarchy</div>

    <div class="section-card">
      <div class="section-card-title">Grievance Details</div>
      <div class="field-grid">
        <div class="field-col-full">
          <div class="field-label-input">Grievance Subject</div>
          <input class="field-input" id="grvSubject" placeholder="e.g. Delayed payment for PO-19201">
        </div>
        <div>
          <div class="field-label-input">Related Module</div>
          <select class="field-input" id="grvModule" style="background-color: var(--field-bg); height: 42px;">
            <option value="Vendor Onboarding">Vendor Onboarding</option>
            <option value="Vendor Empanelment">Vendor Empanelment</option>
            <option value="Purchase Requisition">Purchase Requisition</option>
            <option value="RFQ & Quotations">RFQ & Quotations</option>
            <option value="Cost Comparative (CCS)">Cost Comparative (CCS)</option>
            <option value="Purchase Order">Purchase Order</option>
            <option value="Goods Receipt (GRN)">Goods Receipt (GRN)</option>
            <option value="Travel Requisition">Travel Requisition</option>
            <option value="Flight & Boarding">Flight & Boarding</option>
            <option value="Reimbursement & Advance">Reimbursement & Advance</option>
            <option value="Travel Vendor Evaluation">Travel Vendor Evaluation</option>
          </select>
        </div>
        <div>
          <div class="field-label-input">Escalation Stage / Assigned To</div>
          <select class="field-input" id="grvStage" style="background-color: var(--field-bg); height: 42px;">
            <option value="Admin Head">Admin Head</option>
            <option value="CFO">CFO</option>
            <option value="Secretary General">Secretary General</option>
          </select>
        </div>
        <div class="field-col-full">
          <div class="field-label-input">Grievance Details</div>
          <textarea class="field-textarea" id="grvDetails" placeholder="Explain the issue in detail" style="min-height: 120px;"></textarea>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn-outline-brand" data-action="close-grievance-form">Cancel</button>
      <button type="button" class="btn-brand" id="submitGrievanceBtn" style="height:44px;">
        <i class="bi bi-check-lg" style="font-size:15px;"></i>
        Submit Grievance
      </button>
    </div>
  `;
}

function initGrievancePage() {
  Shell.init({ activeNav: 'grievance', crumb: 'System', title: 'Grievance Redressal' });
  const local = { showForm: false };
  const rowCols = 'grid-template-columns:0.9fr 1.9fr 1.2fr 1.1fr 1fr 0.9fr;';

  function draw() {
    document.getElementById('view-grievance-list').classList.toggle('d-none', local.showForm);
    document.getElementById('view-grievance-form').classList.toggle('d-none', !local.showForm);

    const g = grievanceData();
    document.getElementById('grievanceStatStrip').innerHTML = g.stats.map(s => statCardHtml(s.label, s.count, s.color)).join('');

    document.getElementById('grievanceList').innerHTML = g.rows.map(row => `
      <div class="module-row" style="${rowCols}">
        <div class="module-row-id">${row.ref}</div>
        <div class="module-row-c1">${row.subject}</div>
        <div class="module-row-c2">${row.raisedBy}</div>
        <div class="module-row-c2">${row.module}</div>
        <div><span class="pill" style="background:${row.pillBg};color:${row.pillColor};">${row.stage}</span></div>
        <div class="module-row-c3">${row.stage === 'Resolved' ? '—' : row.daysOpen + ' d'}</div>
      </div>
    `).join('');

    if (local.showForm) {
      renderGrievanceFormInto();
    }
  }

  document.getElementById('pageContent').addEventListener('click', (e) => {
    if (e.target.closest('#raiseGrievanceBtn')) {
      local.showForm = true;
      draw();
      return;
    }

    const action = e.target.closest('[data-action]');
    if (action) {
      if (action.dataset.action === 'close-grievance-form') {
        local.showForm = false;
        draw();
        return;
      }
    }

    if (e.target.closest('#submitGrievanceBtn')) {
      const subject = document.getElementById('grvSubject').value.trim();
      const module = document.getElementById('grvModule').value;
      const stage = document.getElementById('grvStage').value;

      if (!subject) {
        alert('Please enter a grievance subject.');
        return;
      }

      const activePrefs = Shell.getPrefs();
      const activeRoleObj = ROLES.find(r => r.id === activePrefs.role) || ROLES[0];
      const newRef = 'GRV-' + (1000 + GRIEVANCE_ROWS_RAW.length + 5);

      GRIEVANCE_ROWS_RAW.unshift({
        ref: newRef,
        subject: subject,
        raisedBy: `${activeRoleObj.name} (${activeRoleObj.label})`,
        module: module,
        stage: stage,
        daysOpen: 1
      });

      AUDIT_ROWS.unshift({
        time: formatCurrentDate(),
        user: activeRoleObj.name,
        role: activeRoleObj.label,
        action: `Escalated new grievance: ${newRef} — ${subject}`,
        module: 'Grievance'
      });

      local.showForm = false;
      draw();
    }
  });

  draw();
}
