<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Travel Booking Confirmation</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: #334155;
    }
    .email-container {
      max-width: 580px;
      margin: 28px auto;
      background: #ffffff;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      overflow: hidden;
    }
    .header {
      padding: 24px 28px 18px 28px;
      border-bottom: 1px solid #f1f5f9;
      background: #ffffff;
    }
    .badge-confirmed {
      display: inline-block;
      background-color: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 9999px;
      margin-bottom: 10px;
    }
    .title {
      font-size: 19px;
      font-weight: 700;
      color: #0f172a;
      margin: 0 0 4px 0;
    }
    .subtitle {
      font-size: 13px;
      color: #64748b;
      margin: 0;
    }
    .content {
      padding: 24px 28px;
    }
    .greeting {
      font-size: 14.5px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 12px;
    }
    .paragraph {
      font-size: 13.5px;
      line-height: 1.6;
      color: #475569;
      margin-bottom: 18px;
    }
    .card-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 16px 18px;
      margin-bottom: 20px;
    }
    .card-title {
      font-size: 11px;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 10px;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 6px;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    .info-table td {
      padding: 5px 0;
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
    .footer {
      padding: 16px 28px;
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      font-size: 12px;
      color: #64748b;
    }
  </style>
</head>
<body>
  @php
    $trf = $flightLog->trf;
    $travellerName = $trf?->employee?->name ?? 'Colleague';
  @endphp
  <div class="email-container">
    <div class="header">
      <div class="badge-confirmed">Booking Confirmed</div>
      <h1 class="title">{{ $flightLog->ref }} — {{ $flightLog->type }}</h1>
      <p class="subtitle">Travel &amp; Boarding Logistics Confirmation for {{ $trf?->trf_number ?? 'Approved TRF' }}</p>
    </div>

    <div class="content">
      <div class="greeting">Dear {{ $travellerName }},</div>
      <p class="paragraph">
        Your official travel booking has been confirmed and logged in the ASSOCHAM Procurement &amp; Travel Portal against requisition <strong>{{ $trf?->trf_number ?? '—' }}</strong>.
      </p>

      <div class="card-box">
        <div class="card-title">Booking &amp; Itinerary Summary</div>
        <table class="info-table">
          <tr>
            <td class="info-label">Booking Reference</td>
            <td class="info-value">{{ $flightLog->ref }}</td>
          </tr>
          <tr>
            <td class="info-label">Linked TRF</td>
            <td class="info-value">{{ $trf?->trf_number ?? '—' }}</td>
          </tr>
          <tr>
            <td class="info-label">Booking Type</td>
            <td class="info-value">{{ $flightLog->type }}</td>
          </tr>
          <tr>
            <td class="info-label">Travel Route</td>
            <td class="info-value">{{ $trf?->route ?: '—' }}</td>
          </tr>
          <tr>
            <td class="info-label">Travel Dates</td>
            <td class="info-value">
              @if ($trf && $trf->travel_start)
                {{ $trf->travel_start->format('d M Y') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
              @else
                —
              @endif
            </td>
          </tr>
          <tr>
            <td class="info-label">Service Provider</td>
            <td class="info-value">{{ $flightLog->vendor?->company_name ?? 'Direct Booking' }}</td>
          </tr>
          <tr>
            <td class="info-label">Booked Amount</td>
            <td class="info-value">₹{{ number_format((float) $flightLog->cost, 2) }}</td>
          </tr>
        </table>
      </div>

      <p class="paragraph" style="margin-bottom:0;">
        @if ($flightLog->file_path)
          Your ticket / voucher proof has been archived against {{ $trf?->trf_number }}. Please retain boarding passes and submit your settlement claim within 7 working days after trip completion.
        @else
          Please retain your physical or digital boarding passes for post-travel reimbursement and audit reconciliation.
        @endif
      </p>
    </div>

    <div class="footer">
      Recorded by {{ $flightLog->uploader?->name ?? 'Travel Admin Desk' }} &bull; ASSOCHAM Travel &amp; Logistics ERP
    </div>
  </div>
</body>
</html>

