<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor Empanelment Approved</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: #334155;
      -webkit-font-smoothing: antialiased;
    }
    .email-container {
      max-width: 580px;
      margin: 32px auto;
      background: #ffffff;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .header {
      padding: 28px 32px 20px 32px;
      border-bottom: 1px solid #f1f5f9;
      background: #ffffff;
    }
    .badge-success {
      display: inline-block;
      background-color: #ecfdf5;
      color: #059669;
      border: 1px solid #a7f3d0;
      font-size: 11.5px;
      font-weight: 600;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 9999px;
      margin-bottom: 12px;
    }
    .title {
      font-size: 20px;
      font-weight: 700;
      color: #0f172a;
      margin: 0 0 6px 0;
      line-height: 1.3;
    }
    .subtitle {
      font-size: 13.5px;
      color: #64748b;
      margin: 0;
      line-height: 1.5;
    }
    .content {
      padding: 28px 32px;
    }
    .greeting {
      font-size: 15px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 14px;
    }
    .paragraph {
      font-size: 14px;
      line-height: 1.6;
      color: #475569;
      margin-bottom: 20px;
    }
    .card-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 18px 20px;
      margin-bottom: 24px;
    }
    .card-title {
      font-size: 11.5px;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 12px;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 6px;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
    }
    .info-table td {
      padding: 6px 0;
      vertical-align: top;
    }
    .info-label {
      width: 40%;
      color: #64748b;
      font-weight: 500;
    }
    .info-value {
      width: 60%;
      color: #0f172a;
      font-weight: 600;
      text-align: right;
    }
    .code-pill {
      display: inline-block;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      background: #e2e8f0;
      color: #0f172a;
      padding: 2px 7px;
      border-radius: 5px;
      font-size: 12.5px;
    }
    .notes-box {
      border-left: 3px solid #2563eb;
      background: #eff6ff;
      padding: 12px 16px;
      border-radius: 0 6px 6px 0;
      margin-bottom: 24px;
      font-size: 13px;
      line-height: 1.5;
      color: #1e40af;
    }
    .footer {
      padding: 20px 32px;
      background-color: #f8fafc;
      border-top: 1px solid #f1f5f9;
      text-align: center;
      font-size: 12px;
      color: #94a3b8;
      line-height: 1.5;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <!-- Header -->
    <div class="header">
      <span class="badge-success">&#10003; Empanelment Confirmed</span>
      <h1 class="title">Vendor Empanelment Approved</h1>
      <p class="subtitle">Your organization is officially registered in our enterprise procurement directory.</p>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="greeting">
        Dear {{ $vendor->contact_person ?: $vendor->company_name }},
      </div>

      <p class="paragraph">
        We are pleased to inform you that your application for vendor empanelment with <strong>{{ config('app.name', 'Procurement Portal') }}</strong> has successfully cleared all audit, administrative, and financial clearances.
      </p>

      <!-- Key Details Card -->
      <div class="card-box">
        <div class="card-title">Registration Summary</div>
        <table class="info-table">
          <tr>
            <td class="info-label">Vendor Code</td>
            <td class="info-value"><span class="code-pill">{{ $vendor->vendor_code }}</span></td>
          </tr>
          <tr>
            <td class="info-label">Company Name</td>
            <td class="info-value">{{ $vendor->company_name }}</td>
          </tr>
          @if ($vendor->category)
          <tr>
            <td class="info-label">Category / Sector</td>
            <td class="info-value">{{ $vendor->category }}</td>
          </tr>
          @endif
          @if ($vendor->gstin)
          <tr>
            <td class="info-label">GSTIN</td>
            <td class="info-value">{{ $vendor->gstin }}</td>
          </tr>
          @endif
          @if ($vendor->pan)
          <tr>
            <td class="info-label">PAN</td>
            <td class="info-value">{{ $vendor->pan }}</td>
          </tr>
          @endif
          @if ($vendor->payment_terms)
          <tr>
            <td class="info-label">Payment Terms</td>
            <td class="info-value">{{ $vendor->payment_terms }}</td>
          </tr>
          @endif
          <tr>
            <td class="info-label">Status</td>
            <td class="info-value" style="color: #059669;">Empanelled &amp; Active</td>
          </tr>
          <tr>
            <td class="info-label">Approval Date</td>
            <td class="info-value">{{ now()->format('d M Y') }}</td>
          </tr>
        </table>
      </div>

      <div class="notes-box">
        <strong>Next Steps:</strong> You will now be invited to participate in upcoming Requests for Quotation (RFQs) and tenders relevant to your business category. Please quote your Vendor Code in all future communications and purchase orders.
      </div>

      <p class="paragraph" style="margin-bottom: 0;">
        If you have any questions or require updates to your vendor profile or bank details, please contact the procurement office.
      </p>
    </div>

    <!-- Minimal Footer -->
    <div class="footer">
      <p style="margin: 0 0 4px 0;">This is an automated notification from <strong>{{ config('app.name', 'Procurement Portal') }}</strong>.</p>
      <p style="margin: 0;">Please do not reply directly to this email.</p>
    </div>
  </div>
</body>
</html>

