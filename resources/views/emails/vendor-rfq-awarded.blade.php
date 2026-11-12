<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor Award Notification - {{ $rfq->rfq_number }}</title>
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
      max-width: 600px;
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
    .badge-award {
      display: inline-block;
      background-color: #ecfdf5;
      color: #059669;
      border: 1px solid #a7f3d0;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      padding: 4px 12px;
      border-radius: 9999px;
      margin-bottom: 12px;
    }
    .title {
      font-size: 21px;
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
      margin-bottom: 22px;
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
    }
    .quote-box {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
      padding: 16px 20px;
      margin-bottom: 22px;
    }
    .quote-title {
      font-size: 11.5px;
      font-weight: 700;
      color: #1e40af;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 10px;
    }
    .quote-amount {
      font-size: 24px;
      font-weight: 700;
      color: #1e3a8a;
      font-family: Consolas, Monaco, "Courier New", monospace;
    }
    .footer {
      padding: 20px 32px;
      background: #f8fafc;
      border-top: 1px solid #f1f5f9;
      font-size: 12px;
      color: #94a3b8;
      text-align: center;
      line-height: 1.5;
    }
    .next-steps {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-left: 3px solid #059669;
      border-radius: 6px;
      padding: 14px 16px;
      margin-bottom: 20px;
      font-size: 13px;
      color: #334155;
      line-height: 1.5;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <span class="badge-award">Quotation Accepted / Vendor Awarded</span>
      <h1 class="title">{{ $rfq->rfq_number }}</h1>
      <p class="subtitle">
        {{ $rfq->project_name ?: ($rfq->purchaseRequisition?->title ?: 'Procurement Requirement') }}
      </p>
    </div>

    <div class="content">
      <div class="greeting">
        Dear {{ $vendor->contact_person_name ?: $vendor->company_name }},
      </div>

      <p class="paragraph">
        We are pleased to inform you that following the commercial and technical evaluation of bids submitted for <strong>{{ $rfq->rfq_number }}</strong> ({{ $rfq->project_name }}), your company has been selected as the awarded vendor for this project.
      </p>

      @php
        $winningQuote = $ccs->quotes()->where('vendor_id', $vendor->id)->first();
      @endphp

      @if ($winningQuote)
      <div class="quote-box">
        <div class="quote-title">Accepted Commercial Bid Details</div>
        <div class="quote-amount">
          ₹{{ number_format((float)$winningQuote->quoted_cost, 2) }}
          <span style="font-size: 13px; font-weight: normal; color: #475569;">(GST Included)</span>
        </div>
        <div style="font-size: 12.5px; color: #475569; margin-top: 8px;">
          @if ($winningQuote->basic_price > 0)
            Base Cost: ₹{{ number_format((float)$winningQuote->basic_price, 2) }} • 
          @endif
          Delivery: {{ $winningQuote->delivery_days ? $winningQuote->delivery_days . ' Days' : 'As agreed' }} • 
          Warranty: {{ $winningQuote->warranty ?: 'Standard' }}
        </div>
      </div>
      @endif

      <!-- Project & Order Dossier -->
      <div class="card-box">
        <div class="card-title">Project Reference Dossier</div>
        <table class="info-table">
          <tr>
            <td class="info-label">RFQ Reference</td>
            <td class="info-value">{{ $rfq->rfq_number }}</td>
          </tr>
          <tr>
            <td class="info-label">Project Name</td>
            <td class="info-value">{{ $rfq->project_name ?: '—' }}</td>
          </tr>
          <tr>
            <td class="info-label">Sector / Department</td>
            <td class="info-value">{{ $rfq->sector ?: ($rfq->purchaseRequisition?->department ?? 'General') }}</td>
          </tr>
          @if ($rfq->purchaseRequisition?->region)
          <tr>
            <td class="info-label">Region / Location</td>
            <td class="info-value">{{ $rfq->purchaseRequisition->region }}</td>
          </tr>
          @endif
          <tr>
            <td class="info-label">Vendor Code</td>
            <td class="info-value">{{ $vendor->vendor_code }}</td>
          </tr>
          @if ($ccs->selection_remarks)
          <tr>
            <td class="info-label">Remarks / Notes</td>
            <td class="info-value" style="font-weight: 400; font-size: 13px;">{{ $ccs->selection_remarks }}</td>
          </tr>
          @endif
        </table>
      </div>

      <div class="next-steps">
        <strong>Next Steps:</strong> The formal Purchase Order (PO) with final delivery timelines, milestone schedules, and billing terms is being drawn up by our procurement team and will be issued shortly.
      </div>

      <p class="paragraph" style="margin-bottom: 0;">
        If you have any questions, please reply directly to this email or contact our procurement department.
      </p>
    </div>

    <div class="footer">
      ASSOCHAM Procurement &amp; Sourcing Operations<br>
      This is an official procurement award notification.
    </div>
  </div>
</body>
</html>

