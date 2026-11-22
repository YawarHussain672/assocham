<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ASSOCHAM ERP — Manual Testing & UAT Execution Guide</title>
<style>
  @page {
    margin: 12mm 12mm 15mm 12mm;
    size: a4 portrait;
    @bottom-right {
      content: "Page " counter(page) " of " counter(pages);
      font-size: 8px;
      color: #64748b;
    }
  }
  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9px;
    line-height: 1.4;
    color: #1e293b;
    margin: 0;
    padding: 0;
  }
  .page-break {
    page-break-after: always;
  }
  .no-break {
    page-break-inside: avoid;
  }
  
  /* Header & Branding */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    border-bottom: 2px solid #c8102e;
    padding-bottom: 8px;
  }
  .header-table td {
    vertical-align: middle;
  }
  .logo-img {
    max-height: 48px;
    width: auto;
  }
  .doc-title {
    font-size: 14px;
    font-weight: bold;
    color: #c8102e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
  }
  .doc-subtitle {
    font-size: 9.5px;
    font-weight: 600;
    color: #0f172a;
    margin-top: 2px;
  }
  .doc-meta {
    font-size: 8px;
    color: #64748b;
    margin-top: 2px;
  }
  .badge-confidential {
    background-color: #fee2e2;
    color: #991b1b;
    font-size: 7.5px;
    font-weight: bold;
    padding: 3px 6px;
    border-radius: 3px;
    text-transform: uppercase;
    display: inline-block;
  }

  /* Section Titles */
  .section-banner {
    background-color: #0f172a;
    color: #ffffff;
    font-size: 10.5px;
    font-weight: bold;
    padding: 5px 8px;
    margin: 12px 0 8px 0;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .subsection-title {
    font-size: 9.5px;
    font-weight: bold;
    color: #c8102e;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 3px;
    margin: 8px 0 6px 0;
    text-transform: uppercase;
  }

  /* Tables */
  .styled-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    font-size: 8.5px;
  }
  .styled-table th {
    background-color: #f1f5f9;
    color: #334155;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 7.5px;
    padding: 5px 6px;
    border: 1px solid #cbd5e1;
    text-align: left;
    letter-spacing: 0.3px;
  }
  .styled-table td {
    padding: 5px 6px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
  }
  .styled-table tr:nth-child(even) td {
    background-color: #f8fafc;
  }

  /* Badges & Pills */
  .badge {
    display: inline-block;
    padding: 2px 5px;
    font-size: 7px;
    font-weight: bold;
    border-radius: 3px;
    text-transform: uppercase;
  }
  .badge-primary { background: #e0e7ff; color: #3730a3; }
  .badge-success { background: #dcfce7; color: #166534; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .badge-danger  { background: #fee2e2; color: #991b1b; }
  .badge-info    { background: #e0f2fe; color: #075985; }
  .badge-neutral { background: #f1f5f9; color: #475569; }

  /* Test Case Block */
  .tc-box {
    border: 1px solid #cbd5e1;
    border-left: 3.5px solid #0284c7;
    background: #ffffff;
    margin-bottom: 8px;
    border-radius: 3px;
    padding: 6px 8px;
  }
  .tc-box.tc-critical {
    border-left-color: #c8102e;
  }
  .tc-box.tc-success {
    border-left-color: #10b981;
  }
  .tc-header {
    margin-bottom: 4px;
  }
  .tc-id {
    font-weight: bold;
    color: #0f172a;
    font-size: 9px;
  }
  .tc-title {
    font-weight: bold;
    color: #1e293b;
    font-size: 9px;
    margin-left: 4px;
  }
  .tc-role {
    float: right;
    font-size: 7.5px;
    color: #64748b;
    font-weight: bold;
  }
  .tc-content-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
    margin-top: 3px;
  }
  .tc-content-table td {
    padding: 2px 4px;
    vertical-align: top;
    border: none;
  }
  .tc-label {
    width: 15%;
    font-weight: bold;
    color: #475569;
    text-transform: uppercase;
    font-size: 7.5px;
  }
  .tc-val {
    width: 85%;
    color: #1e293b;
  }
  .checkbox-box {
    display: inline-block;
    width: 9px;
    height: 9px;
    border: 1px solid #94a3b8;
    margin-right: 4px;
    vertical-align: middle;
  }

  /* Callout Boxes */
  .callout-box {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #f59e0b;
    padding: 6px 8px;
    margin-bottom: 8px;
    font-size: 8px;
    border-radius: 2px;
  }
  .callout-title {
    font-weight: bold;
    color: #92400e;
    text-transform: uppercase;
    margin-bottom: 2px;
  }
  
  .footer-text {
    font-size: 7.5px;
    color: #94a3b8;
    text-align: center;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
    margin-top: 15px;
  }
</style>
</head>
<body>

  <!-- ====================================================================
       DOCUMENT HEADER
       ==================================================================== -->
  <table class="header-table">
    <tr>
      <td style="width: 70px;">
        <img src="{{ public_path('assets/img/assocham-logo.jpg') }}" class="logo-img" alt="ASSOCHAM Logo">
      </td>
      <td>
        <h1 class="doc-title">ASSOCHAM Procurement &amp; Admin ERP</h1>
        <div class="doc-subtitle">Comprehensive Manual Testing &amp; UAT Execution Guide</div>
        <div class="doc-meta">
          <strong>System Release:</strong> Version 2.0 (Laravel 11 · Multi-Tenant RBAC) &nbsp;|&nbsp; 
          <strong>Date:</strong> November 2026 &nbsp;|&nbsp; 
          <strong>Target:</strong> QA Engineers, UAT Teams &amp; Auditors
        </div>
      </td>
      <td style="text-align: right; width: 110px;">
        <span class="badge-confidential">Official Test Guide</span>
        <div style="font-size: 7.5px; color: #64748b; margin-top: 4px;">
          Environment: <strong>Local / Staging</strong><br>
          URL: <strong>127.0.0.1:8000</strong>
        </div>
      </td>
    </tr>
  </table>

  <!-- ====================================================================
       SECTION 1: TESTER CREDENTIALS & ROLE MATRIX
       ==================================================================== -->
  <div class="section-banner">1. Tester Credentials &amp; 10-Role Delegation Roster</div>
  <p style="font-size: 8px; color: #475569; margin: 2px 0 6px 0;">
    Use these pre-seeded demo accounts to test workflows from initiation to executive sanction. All accounts share the default password: <code>password</code>.
  </p>

  <table class="styled-table">
    <thead>
      <tr>
        <th style="width: 12%;">Role Slug</th>
        <th style="width: 22%;">Designation &amp; Persona</th>
        <th style="width: 25%;">Login Email</th>
        <th style="width: 14%;">Allowed Modules</th>
        <th style="width: 27%;">Primary Testing Mandate</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="badge badge-primary">pr_sr_exec</span></td>
        <td><strong>Priya Nair</strong><br><small style="color:#64748b;">PR &amp; GRN Creator</small></td>
        <td><code>priya.nair@assocham.test</code></td>
        <td>PR, GRN, Universal</td>
        <td>Drafts PRs (line items/Excel); logs physical goods receipt (GRNs).</td>
      </tr>
      <tr>
        <td><span class="badge badge-info">buyer</span></td>
        <td><strong>Rohit Verma</strong><br><small style="color:#64748b;">Procurement Buyer</small></td>
        <td><code>rohit.verma@assocham.test</code></td>
        <td>Onboard, RFQ, PO, Events</td>
        <td>Onboards vendors; compiles CCS quotes; drafts PO lines (terms locked).</td>
      </tr>
      <tr>
        <td><span class="badge badge-danger">admin_head</span></td>
        <td><strong>Rahul Mehta</strong><br><small style="color:#64748b;">Admin Head (Super-Admin)</small></td>
        <td><code>admin@assocham.com</code></td>
        <td><strong>All 19 Modules</strong></td>
        <td>Budget Master CRUD; PO Contract Terms; Flight logs; Exceptions; Users.</td>
      </tr>
      <tr>
        <td><span class="badge badge-neutral">pr_approver_l1</span></td>
        <td><strong>Basu</strong><br><small style="color:#64748b;">DSG — PR Level 1</small></td>
        <td><code>basu@assocham.test</code></td>
        <td>PR, Universal</td>
        <td>First operational sign-off on PRs; can reject partial line items.</td>
      </tr>
      <tr>
        <td><span class="badge badge-neutral">pr_approver_l2</span></td>
        <td><strong>Ambika</strong><br><small style="color:#64748b;">Head of States — PR Level 2</small></td>
        <td><code>ambika@assocham.test</code></td>
        <td>PR, Universal</td>
        <td>Inter-state PR vetting; out-of-turn approval strictly blocked before L1.</td>
      </tr>
      <tr>
        <td><span class="badge badge-warning">asg1</span></td>
        <td><strong>Vikram Malhotra</strong><br><small style="color:#64748b;">ASG 1</small></td>
        <td><code>vikram.malhotra@assocham.test</code></td>
        <td>Empanelment, PR, TRF</td>
        <td>Vendor Empanelment L2; Domestic TRF approval (Anti-Self-Approval gate).</td>
      </tr>
      <tr>
        <td><span class="badge badge-warning">asg2</span></td>
        <td><strong>Ananya Roy</strong><br><small style="color:#64748b;">ASG 2</small></td>
        <td><code>ananya.roy@assocham.test</code></td>
        <td>Empanelment, PR, TRF</td>
        <td>Vendor Empanelment L3; Domestic TRF approval (interchangeable with ASG 1).</td>
      </tr>
      <tr>
        <td><span class="badge badge-success">cfo</span></td>
        <td><strong>Sanjay Menon</strong><br><small style="color:#64748b;">Chief Financial Officer</small></td>
        <td><code>cfo@assocham.com</code></td>
        <td>Budgets, PO, Reimb, Exceptions</td>
        <td>Annual Budget L1; Final Vendor Empanelment L4; PR L3; PO &ge;₹10L; Claims.</td>
      </tr>
      <tr>
        <td><span class="badge badge-danger">sg</span></td>
        <td><strong>Meenakshi Rao</strong><br><small style="color:#64748b;">Secretary General</small></td>
        <td><code>sg@assocham.com</code></td>
        <td>Budgets, PR, PO, TRF, Exceptions</td>
        <td>Annual Budget L2 Final; PR L4 (COPEX / &ge;₹5L); International TRF; High POs.</td>
      </tr>
      <tr>
        <td><span class="badge badge-primary">hod</span></td>
        <td><strong>Rajesh Verma</strong><br><small style="color:#64748b;">Council / Dept HOD</small></td>
        <td><code>rajesh.verma@assocham.test</code></td>
        <td>Annual Budget, GRN, Grievance</td>
        <td>Submits Annual Department Budget proposals; inspects and approves GRNs.</td>
      </tr>
    </tbody>
  </table>

  <!-- ====================================================================
       SECTION 2: GOLDEN RULES & TEST PRINCIPLES
       ==================================================================== -->
  <div class="callout-box">
    <div class="callout-title">Three Golden Testing Rules for QA Verification:</div>
    <strong>1. Budget Headroom is an Absolute Hard Gate:</strong> If a PR or PO price delta exceeds available headroom, the system MUST abort immediately with an inline error. No spend can ever be committed in a negative headroom state.<br>
    <strong>2. Segregation of Duties (SoD) is Non-Negotiable:</strong> A user cannot approve their own travel request. Buyers can draft PO items, but contract clauses are locked to Admin Head. Non-admins cannot switch roles.<br>
    <strong>3. Privacy Boundaries are Role-Gated:</strong> Financial budget charts, headroom balances, and 90% alerts MUST NOT be visible to operational staff (Buyers, Execs, HODs). Only CFO, SG, and Admin Head have financial visibility.
  </div>

  <div class="page-break"></div>

  <!-- ====================================================================
       SECTION 3: STEP-BY-STEP TEST CASES BY MODULE
       ==================================================================== -->
  <div class="section-banner">2. Step-by-Step Test Execution Checklists</div>

  <!-- SUBSECTION A: AUTHENTICATION & DASHBOARD -->
  <div class="subsection-title">Module A: Authentication, Security &amp; Dashboard UI</div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-AUTH-01:</span>
      <span class="tc-title">Guest Protection &amp; Login Security</span>
      <span class="tc-role">Target: Guest / All Roles</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. In private window, open <code>/index</code>. 2. Attempt login with wrong password. 3. Login with valid credentials.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Redirects to <code>/login</code>; displays clear red validation on error; valid login redirects to Dashboard with user avatar &amp; role.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-AUTH-02:</span>
      <span class="tc-title">Self-Service Password Management</span>
      <span class="tc-role">Target: Any User</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Click avatar &rarr; <strong>Change Password</strong> (<code>/account/password</code>). 2. Test incorrect current password, then valid new password.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Requires current password; enforces min 8 chars; rotates session token and records <code>Auth / password_changed</code> in audit trail.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-AUTH-03:</span>
      <span class="tc-title">Role Switcher Lock for Non-Admins</span>
      <span class="tc-role">Target: Buyer / PR Exec</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Login as <code>buyer</code>. 2. Inspect topbar for Switch Role dropdown. 3. Fire direct <code>POST /switch-role</code>.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Switcher dropdown completely absent from DOM (<code>canSwitchRole = false</code>); POST request returns hard <code>403 Forbidden</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-DASH-01:</span>
      <span class="tc-title">Clean Dashboard Stat Cards &amp; Financial Privacy Gating</span>
      <span class="tc-role">Target: CFO vs. Buyer</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Login as <code>cfo</code> &rarr; check 4 KPI cards. 2. Login as <code>buyer</code> &rarr; check 4 KPI cards.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Cards have clean white styling with top colored accent. CFO sees <strong>Budget Utilised (GST-Incl)</strong> with <code>₹1.28 Cr / ₹1.75 Cr</code>; Buyer sees <strong>Travel &amp; Claims</strong> card instead. Budget chart and 90% alerts hidden from Buyer.</td>
      </tr>
    </table>
  </div>

  <!-- SUBSECTION B: BUDGET PLANNING & MASTER -->
  <div class="subsection-title" style="margin-top: 10px;">Module B: Annual Budget Proposals &amp; Master Limits</div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-BUD-01:</span>
      <span class="tc-title">HOD Proposal Submission &amp; Itemized Math</span>
      <span class="tc-role">Target: HOD (Rajesh Verma)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Navigate to <code>/annual-budget</code> &rarr; <strong>Submit Annual Budget Proposal</strong>. 2. Enter Proposed Ceiling ₹50,00,000. 3. Add itemized rows: OPEX (₹30L), COPEX (₹15L), Travel (₹5L). 4. Test submitting with items totaling ₹55L.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Submitting > proposed ceiling blocks with error. When subtotal &le; ceiling, proposal submits with status <code>pending_cfo</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-BUD-02:</span>
      <span class="tc-title">Two-Tier Governance Endorsement (CFO &rarr; SG)</span>
      <span class="tc-role">Target: CFO &rarr; SG</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Login as <code>cfo</code> &rarr; Open proposal &rarr; Click <strong>Endorse to SG</strong>. 2. Login as <code>sg</code> &rarr; Click <strong>Sanction Budget</strong>.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Advances to <code>pending_sg</code>, then reaches <code>approved</code>. Operational roles receive <code>403 Forbidden</code> if accessing <code>/annual-budget</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-BUD-03:</span>
      <span class="tc-title">Budget Master Limits CRUD &amp; Read-Only Privacy</span>
      <span class="tc-role">Target: Admin Head vs. CFO</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. As <code>admin_head</code> in <code>/checks-balances</code>, add Department Budget (₹25L) &amp; Project Budget (₹15L). 2. Login as <code>cfo</code>.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Admin Head creates both; CFO sees full balances but Add/Edit buttons are absent; CFO direct mutations return <code>403 Forbidden</code>.</td>
      </tr>
    </table>
  </div>

  <div class="page-break"></div>

  <!-- SUBSECTION C: VENDOR ONBOARDING & EMPANELMENT -->
  <div class="subsection-title">Module C: Vendor Onboarding &amp; 4-Tier Empanelment</div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-VEN-01:</span>
      <span class="tc-title">Dossier Submission &amp; Regex Validations</span>
      <span class="tc-role">Target: Buyer (Rohit Verma)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Navigate to <code>/onboarding</code>. 2. Enter invalid PAN (e.g. <code>12345678</code>). 3. Enter valid PAN (<code>AAACS1234E</code>), GSTIN, MSME, Bank IFSC, and check Self-Declaration checkbox.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Invalid PAN rejected by regex (<code>^[A-Z]{5}[0-9]{4}[A-Z]$</code>); valid submission creates record in <code>pending_l1</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-VEN-02:</span>
      <span class="tc-title">4-Tier Sequential Approval &amp; Welcome Email</span>
      <span class="tc-role">Target: Admin Head &rarr; ASG 1 &rarr; ASG 2 &rarr; CFO</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Attempt approval as ASG 1 before L1 (must fail). 2. Approve L1 as <code>admin_head</code>. 3. Approve L2 as <code>asg1</code>. 4. Approve L3 as <code>asg2</code>. 5. Approve L4 as <code>cfo</code>.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Out-of-turn blocked (<code>403</code>); on Level 4 CFO approval, status becomes <code>approved</code> / <code>active</code>, record locks, and <code>VendorEmpanelledMail</code> is dispatched.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-VEN-03:</span>
      <span class="tc-title">Vendor Blacklisting Disqualification</span>
      <span class="tc-role">Target: Admin Head</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Open an approved vendor &rarr; Click <strong>Blacklist Vendor</strong> &rarr; enter reason &rarr; confirm. 2. Create RFQ as Buyer.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Vendor status set to <code>blacklisted</code>; vendor is permanently excluded and cannot be selected in RFQs or POs.</td>
      </tr>
    </table>
  </div>

  <!-- SUBSECTION D: PURCHASE REQUISITIONS -->
  <div class="subsection-title" style="margin-top: 10px;">Module D: Purchase Requisitions (PR) &amp; Modern Line Items Table</div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PR-01:</span>
      <span class="tc-title">Line Items Table Standards &amp; Live Calculation</span>
      <span class="tc-role">Target: PR Creator (Priya Nair)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Open <code>/requisition/create</code>. 2. Inspect `#prItemsTable` layout. 3. Select a catalog item (verify specs, unit, pre-negotiated cost, GST % auto-populate). 4. Add a custom row: Qty 10, Cost ₹1,000, GST 18%.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Inputs do not wrap or stack. Row displays ₹11,800.00. Subtotal, GST, and Grand Total update dynamically in real time.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PR-02:</span>
      <span class="tc-title">Hard Budget Headroom Overspend Enforcement</span>
      <span class="tc-role">Target: PR Creator</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Select a department with ₹50,000 headroom left. 2. Enter line items totaling ₹1,00,000. 3. Submit PR.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Submission aborted with message: <em>"Department budget limit exceeded. Available headroom: ₹50,000"</em>. Spend is NOT reserved.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PR-03:</span>
      <span class="tc-title">DoA Routing: OPEX &lt; ₹5L (CFO) vs. COPEX / &ge; ₹5L (SG)</span>
      <span class="tc-role">Target: Approvers L1 &rarr; L4</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Submit OPEX PR &lt; ₹5L &rarr; Approve L1 (Basu) &rarr; L2 (Ambika) &rarr; L3 (CFO).<br>2. Submit PR with 1 COPEX item &rarr; Approve L1 &rarr; L2 &rarr; L3.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Case 1 concludes at CFO (Level 3); Case 2 mandates Level 4 approval by Secretary General (SG).</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-success no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PR-04:</span>
      <span class="tc-title">Partial Line-Item Rejection with Instant Budget Refund</span>
      <span class="tc-role">Target: Approver L1 (Basu)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Open multi-item PR. 2. Reject Item #2 (value ₹20,000). 3. Click Approve PR.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">PR approves with remaining items; exactly ₹20,000 is instantly returned to department budget headroom.</td>
      </tr>
    </table>
  </div>

  <div class="page-break"></div>

  <!-- SUBSECTION E: RFQ, PO & GRN -->
  <div class="subsection-title">Module E: RFQ, Purchase Orders (PO) &amp; Goods Receipt Notes (GRN)</div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-RFQ-01:</span>
      <span class="tc-title">Cost Comparative Statement (CCS) &amp; Award</span>
      <span class="tc-role">Target: Buyer (Rohit Verma)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Create RFQ for approved PR. 2. Record bids from 3 vendors. 3. Review Comparative Statement matrix and award winning bid.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Matrix highlights lowest quote (L1); award triggers <code>VendorRfqAwardedMail</code> to winning supplier.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PO-01:</span>
      <span class="tc-title">Buyer Contract Terms Privacy Lock</span>
      <span class="tc-role">Target: Buyer vs. Admin Head</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Login as <code>buyer</code> &rarr; Edit PO draft &rarr; Inspect contract/legal terms. 2. Login as <code>admin_head</code> &rarr; Edit same PO.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Buyer sees contract clauses disabled/locked (read-only); Admin Head has full edit access to legal/payment terms.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-PO-02:</span>
      <span class="tc-title">Official DomPDF Generation &amp; Revision Resets</span>
      <span class="tc-role">Target: Buyer / Admin Head</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. On approved PO, click <strong>Download PDF</strong>. 2. Edit commercial value on approved PO.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">PDF downloads styled cleanly with logo and terms. Editing approved PO resets status to <code>pending</code> and marks version <code>Rev 1</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-GRN-01:</span>
      <span class="tc-title">Partial GRN &amp; Cumulative PO Quantity Cap</span>
      <span class="tc-role">Target: PR Creator &amp; HOD</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. For a 100-unit PO, log GRN #1 for 60 units (with invoice upload). 2. Attempt GRN #2 for 50 units.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">GRN #1 succeeds; GRN #2 blocked because 60 + 50 = 110 &gt; 100 PO quantity. Raiser's department HOD signs off.</td>
      </tr>
    </table>
  </div>

  <!-- SUBSECTION F: TRAVEL, LOGISTICS & GOVERNANCE -->
  <div class="subsection-title" style="margin-top: 10px;">Module F: Travel, Advance Settlements &amp; Governance</div>

  <div class="tc-box tc-critical no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-TRF-01:</span>
      <span class="tc-title">Anti-Self-Approval &amp; Domestic vs. International TRF</span>
      <span class="tc-role">Target: ASG 1 vs. ASG 2 &amp; SG</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. As <code>asg1</code>, file Domestic TRF for self. Check pending approvals. 2. File International TRF.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">ASG 1 cannot approve own TRF (excluded from queue); ASG 2 can approve. International TRF routes strictly to <code>sg</code>.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-TRF-02:</span>
      <span class="tc-title">30-Day Overdue Travel Settlement Lock</span>
      <span class="tc-role">Target: Travelling Employee</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Attempt filing a new TRF for an employee whose completed travel is &gt;30 days past without an approved claim.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Blocked with error: <em>"Outstanding travel settlement pending &gt; 30 days. File claim to unblock."</em></td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-REIMB-01:</span>
      <span class="tc-title">Anti-Tampering Advance Deduction Math</span>
      <span class="tc-role">Target: Employee &amp; CFO</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. File claim for trip where Advance = ₹15,000, Actual bills = ₹22,000. 2. Verify net balance calculation.</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">System computes Net Balance = +₹7,000 (Payable to Employee). CFO approval clears the 30-day travel lock.</td>
      </tr>
    </table>
  </div>

  <div class="tc-box no-break">
    <div class="tc-header">
      <span class="checkbox-box"></span>
      <span class="tc-id">TC-AUD-01:</span>
      <span class="tc-title">Immutable Audit Trail Verification</span>
      <span class="tc-role">Target: Any User (View Only)</span>
    </div>
    <table class="tc-content-table">
      <tr>
        <td class="tc-label">Test Steps:</td>
        <td class="tc-val">1. Navigate to <code>/audit</code>. 2. Filter by module (e.g. <code>requisition</code> or <code>budget</code>).</td>
      </tr>
      <tr>
        <td class="tc-label">Expected Result:</td>
        <td class="tc-val">Chronological append-only list showing actor, IP, timestamp, action type, and before/after delta JSON.</td>
      </tr>
    </table>
  </div>

  <!-- ====================================================================
       DOCUMENT FOOTER
       ==================================================================== -->
  <div class="footer-text">
    ASSOCHAM Procurement ERP &copy; 2026 &nbsp;|&nbsp; 
    Official Manual QA &amp; UAT Test Specification &nbsp;|&nbsp; 
    Strict Delegation of Authority (DoA) &amp; Financial Governance Compliance
  </div>

</body>
</html>
