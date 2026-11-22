
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor Self-Declaration Undertaking — {{ $vendor?->vendor_code ?? 'ASSOCHAM' }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary: #c8102e;
      --text-main: #0f172a;
      --text-muted: #475569;
      --border-color: #cbd5e1;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: #f1f5f9;
      color: var(--text-main);
      font-size: 13px;
      line-height: 1.55;
      padding: 24px 16px;
    }
    .sheet-wrapper {
      max-width: 820px;
      margin: 0 auto;
    }
    .action-bar {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 12px 18px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-weight: 600;
      font-size: 12.5px;
      padding: 7px 16px;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: 1px solid transparent;
    }
    .btn-primary {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
    }
    .btn-primary:hover {
      background: #a50d24;
      border-color: #a50d24;
    }
    .btn-outline {
      background: #ffffff;
      color: #334155;
      border-color: #cbd5e1;
    }
    .btn-outline:hover {
      background: #f8fafc;
      border-color: #94a3b8;
    }
    .document-page {
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 48px 52px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
      position: relative;
    }
    .doc-header {
      border-bottom: 2px solid #0f172a;
      padding-bottom: 16px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .brand-title {
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      line-height: 1.25;
    }
    .brand-sub {
      font-size: 11px;
      color: #64748b;
      margin-top: 3px;
    }
    .doc-banner {
      background: #f8fafc;
      border: 1.5px solid #0f172a;
      padding: 10px 16px;
      text-align: center;
      margin-bottom: 22px;
      border-radius: 4px;
    }
    .doc-banner h1 {
      font-size: 13.5px;
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin: 0;
    }
    .doc-banner .doc-subtitle {
      font-size: 11px;
      color: #475569;
      font-weight: 600;
      margin-top: 2px;
    }
    .particulars-box {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      background: #fafafa;
      padding: 14px 16px;
      margin-bottom: 22px;
    }
    .particulars-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }
    .particulars-table td {
      padding: 4px 8px;
      vertical-align: top;
    }
    .particulars-table .lbl {
      color: #64748b;
      font-weight: 600;
      width: 25%;
      white-space: nowrap;
    }
    .particulars-table .val {
      color: #0f172a;
      font-weight: 600;
    }
    .mono {
      font-family: 'JetBrains Mono', monospace;
    }
    .section-block {
      margin-bottom: 20px;
    }
    .section-title {
      font-size: 12.5px;
      font-weight: 700;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      margin-bottom: 8px;
      padding-bottom: 4px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .clause-box {
      padding: 10px 14px;
      border-radius: 4px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      font-size: 12px;
      color: #1e293b;
      line-height: 1.6;
    }
    .clause-list {
      list-style-type: lower-alpha;
      padding-left: 20px;
      margin-top: 6px;
    }
    .clause-list li {
      margin-bottom: 8px;
      font-size: 12px;
      color: #1e293b;
      line-height: 1.6;
    }
    .clause-list li:last-child {
      margin-bottom: 0;
    }
    .execution-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-top: 26px;
      padding-top: 18px;
      border-top: 1.5px solid #cbd5e1;
    }
    .exec-box {
      font-size: 12px;
    }
    .exec-box .exec-row {
      display: flex;
      margin-bottom: 6px;
    }
    .exec-box .exec-lbl {
      width: 100px;
      color: #64748b;
      font-weight: 600;
      flex-shrink: 0;
    }
    .exec-box .exec-val {
      color: #0f172a;
      font-weight: 600;
    }
    .sig-frame {
      border: 1.5px dashed #94a3b8;
      border-radius: 6px;
      height: 110px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #ffffff;
      padding: 8px;
      margin-top: 8px;
      text-align: center;
    }
    .sig-img {
      max-height: 90px;
      max-width: 100%;
      object-fit: contain;
    }
    .doc-footer {
      margin-top: 32px;
      padding-top: 12px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 10px;
      color: #94a3b8;
    }

    @media print {
      body {
        background: #ffffff;
        padding: 0;
      }
      .no-print {
        display: none !important;
      }
      .document-page {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
      }
      .sig-frame {
        border-color: #cbd5e1 !important;
      }
    }
  </style>
</head>
<body>

<div class="sheet-wrapper">
  
  <!-- Printable & Action Toolbar -->
  <div class="action-bar no-print">
    <div style="display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-file-earmark-check-fill text-danger fs-5"></i>
      <div>
        <div style="font-weight: 700; font-size: 13.5px;">Vendor Self-Declaration &amp; Certification Undertaking</div>
        <div style="font-size: 11px; color: #64748b;">
          {{ $vendor ? $vendor->company_name . ' (' . $vendor->vendor_code . ')' : 'Blank Form Template for Signing' }}
        </div>
      </div>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i>
        <span>Print / Save as PDF</span>
      </button>
      @if ($vendor)
        <a href="{{ route('empanelment.show', $vendor) }}" class="btn btn-outline">
          <i class="bi bi-arrow-left"></i>
          <span>Back to Dossier</span>
        </a>
      @else
        <a href="{{ route('onboarding') }}" class="btn btn-outline">
          <i class="bi bi-arrow-left"></i>
          <span>Back to Registration</span>
        </a>
      @endif
    </div>
  </div>

  <!-- Main Legal Document Page -->
  <div class="document-page">
    
    <!-- Official Institutional Header with Logo -->
    <div class="doc-header">
      <div style="display: flex; align-items: center; gap: 16px;">
        <img src="{{ asset('assets/img/assocham-logo.jpg') }}" alt="ASSOCHAM Logo" style="height: 52px; width: auto; object-fit: contain;" onerror="this.style.display='none';">
        <div>
          <div class="brand-title">THE ASSOCIATED CHAMBERS OF COMMERCE AND INDUSTRY OF INDIA</div>
          <div class="brand-sub">ASSOCHAM Corporate Office • 4th Floor, YMCA Cultural Centre and Library Building, 1, Jai Singh Road, New Delhi – 110001</div>
        </div>
      </div>
      <div style="text-align: right; flex-shrink: 0;">
        <span style="font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 700; color: #c8102e; border: 1.5px solid #c8102e; padding: 4px 10px; border-radius: 4px; letter-spacing: 0.5px;">
          ANNEXURE - VSD
        </span>
      </div>
    </div>

    <!-- Title Banner -->
    <div class="doc-banner">
      <h1>VENDOR EMPANELMENT SELF-DECLARATION &amp; CERTIFICATION FORM</h1>
      <div class="doc-subtitle">Mandatory Institutional Undertaking for Vendor Onboarding &amp; Empanelment Roster</div>
    </div>

    <!-- Section 1: Certification -->
    <div class="section-block">
      <div class="section-title">
        <span>1. Certification of Authenticity &amp; Accuracy</span>
      </div>
      <div class="clause-box">
        <strong>I, the undersigned hereby accept and warrant that the information provided in this form is correct and, in the event of changes, details will be provided as soon as possible.</strong>
      </div>
    </div>

    <!-- Section 2: Self-Declaration -->
    <div class="section-block">
      <div class="section-title">
        <span>2. Self – Declaration Undertaking</span>
      </div>
      <div class="clause-box">
        <div style="font-weight: 700; margin-bottom: 6px;">
          I, the undersigned, declare that:
        </div>
        <ol class="clause-list">
          <li>I am not involved in any fraudulent or corrupt activities and has not been in the past and is not currently under any investigation for any such activities which would render by me unsuitable for business dealing with ASSOCHAM.</li>
          <li>I am not on, or associated with a company or individual, groups, undertakings and entities that are on the consolidated list established and maintained by the committee established by ASSOCHAM.</li>
          <li>I am not currently removed, invalidated, or suspended by any other Agency.</li>
          <li>I certifies that it has not knowingly provided and will not knowingly provide, in violation of applicable laws, material support or resources to any individual or organization that advocates, plans, sponsors, engages in, or are or has engaged in an act of terrorism.</li>
          <li>I am not related to any employee/consultant at ASSOCHAM.</li>
          <li>I do hereby declare that there is no child labour engaged in our establishment.</li>
        </ol>
      </div>
    </div>

    <!-- Section 3: Execution, Signatory & Stamp -->
    <div class="execution-grid">
      <div class="exec-box">
        <div class="exec-row">
          <span class="exec-lbl">Place:</span>
          <span class="exec-val">{{ $vendor?->location ?: 'New Delhi' }}</span>
        </div>
        <div class="exec-row">
          <span class="exec-lbl">Date:</span>
          <span class="exec-val">{{ optional($vendor?->declaration_date)->format('d-M-Y') ?? optional($vendor?->created_at)->format('d-M-Y') ?? date('d-M-Y') }}</span>
        </div>
        <div class="exec-row">
          <span class="exec-lbl">Signatory:</span>
          <span class="exec-val">{{ $vendor?->declaration_signatory_name ?: ($vendor?->contact_person ?: 'Authorized Signatory') }}</span>
        </div>
        <div class="exec-row">
          <span class="exec-lbl">Designation:</span>
          <span class="exec-val">{{ $vendor?->declaration_designation ?: 'Authorized Signatory' }}</span>
        </div>
        <div style="margin-top: 14px; font-size: 11px; color: #64748b; line-height: 1.45;">
          This undertaking is digitally affirmed and recorded under the institutional governance protocols of ASSOCHAM.
        </div>
      </div>

      <div class="exec-box" style="text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
          Specimen Signature &amp; Official Stamp
        </div>

        <div class="sig-frame">
          @if ($signature)
            @if ($signature->isImage())
              <img src="{{ $signature->url }}" alt="Authorized Signature / Stamp" class="sig-img">
            @else
              <div style="color: #2563eb; font-weight: 600; font-size: 11.5px;">
                <i class="bi bi-file-earmark-check-fill fs-5 d-block mb-1"></i>
                <span>Document Specimen Attached:</span>
                <div class="mono small mt-0.5 text-muted">{{ $signature->original_name }}</div>
              </div>
            @endif
          @else
            <div style="color: #94a3b8; font-size: 11px;">
              [ Affix Authorized Signature &amp; Company Stamp ]
            </div>
          @endif
        </div>

        <div style="font-size: 10.5px; color: #64748b; margin-top: 6px;">
          Authorized Representative / Seal of {{ $vendor?->company_name ?? 'Firm' }}
        </div>
      </div>
    </div>

    <!-- Official Document Footnote & Governance Audit Reference -->
    <div class="doc-footer">
      <div>
        Security Reference: <span class="mono">{{ $vendor ? 'VSD-' . $vendor->vendor_code . '-' . optional($vendor->created_at)->format('Ymd') : 'VSD-TEMPLATE' }}</span>
      </div>
      <div>
        ASSOCHAM Procurement Directorate • System Generated Form
      </div>
    </div>

  </div>

</div>

</body>
</html>
