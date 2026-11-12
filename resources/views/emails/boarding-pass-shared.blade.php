<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Boarding Pass Shared</title>
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
    .badge-pass {
      display: inline-block;
      background-color: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
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
      width: 38%;
      color: #64748b;
      font-weight: 500;
    }
    .info-value {
      width: 62%;
      color: #0f172a;
      font-weight: 600;
    }
    .pass-list {
      margin: 0;
      padding-left: 18px;
      font-size: 13px;
      color: #0f172a;
    }
    .pass-list li {
      margin-bottom: 6px;
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
  <div class="email-container">
    <div class="header">
      <div class="badge-pass">Boarding Pass Attached</div>
      <h1 class="title">{{ $trf->trf_number }} — Boarding Pass</h1>
      <p class="subtitle">{{ $trf->route ?: 'Official ASSOCHAM Travel' }}</p>
    </div>

    <div class="content">
      <div class="greeting">Dear {{ $trf->employee?->name ?? 'Colleague' }},</div>
      <p class="paragraph">
        Your boarding pass{{ $boardingPasses->count() > 1 ? 'es' : '' }} for travel requisition <strong>{{ $trf->trf_number }}</strong> {{ $boardingPasses->count() > 1 ? 'have' : 'has' }} been uploaded and shared for your upcoming journey.
      </p>

      <div class="card-box">
        <div class="card-title">Trip Summary</div>
        <table class="info-table">
          <tr>
            <td class="info-label">TRF Number</td>
            <td class="info-value">{{ $trf->trf_number }}</td>
          </tr>
          <tr>
            <td class="info-label">Travel Route</td>
            <td class="info-value">{{ $trf->route ?: '—' }}</td>
          </tr>
          <tr>
            <td class="info-label">Travel Dates</td>
            <td class="info-value">
              @if ($trf->travel_start)
                {{ $trf->travel_start->format('d M Y') }} – {{ optional($trf->travel_end)->format('d M Y') ?? '—' }}
              @else
                —
              @endif
            </td>
          </tr>
          <tr>
            <td class="info-label">Travel Mode</td>
            <td class="info-value">{{ $trf->travel_mode ?: 'Flight' }}</td>
          </tr>
        </table>
      </div>

      <div class="card-box" style="margin-bottom:0;">
        <div class="card-title">Stored Boarding Passes ({{ $boardingPasses->count() }})</div>
        <ul class="pass-list">
          @foreach ($boardingPasses as $pass)
            <li>{{ $pass->original_name ?: basename($pass->file_path) }}</li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="footer">
      Stored against {{ $trf->trf_number }} &bull; ASSOCHAM Travel &amp; Logistics ERP
    </div>
  </div>
</body>
</html>

