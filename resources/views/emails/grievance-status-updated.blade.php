<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grievance Status Update</title>
  <style>
    body { margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #334155; }
    .container { max-width: 580px; margin: 28px auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
    .header { padding: 22px 26px 16px; border-bottom: 1px solid #f1f5f9; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px; }
    .pill-open { background: #fef3c7; color: #92400e; }
    .pill-resolved { background: #dcfce7; color: #166534; }
    .title { margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #0f172a; }
    .sub { margin: 0; font-size: 13px; color: #64748b; }
    .body { padding: 22px 26px; font-size: 13.5px; line-height: 1.6; }
    .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin: 14px 0; }
    .btn { display: inline-block; margin-top: 8px; padding: 9px 18px; background: #0f172a; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px; }
    .footer { padding: 14px 26px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11.5px; color: #64748b; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <span class="pill {{ $grievance->status === 'resolved' ? 'pill-resolved' : 'pill-open' }}">
        {{ $update->actionLabel() }}
      </span>
      <h1 class="title">{{ $grievance->ticket_number }} — {{ $grievance->subject }}</h1>
      <p class="sub">Category: {{ $grievance->subject_category }} · Current Owner: {{ $ownerLabel }}</p>
    </div>
    <div class="body">
      <p style="margin-top:0;">
        @if ($recipientAudience === 'handler')
          A grievance ticket has been assigned to your role queue (<strong>{{ $ownerLabel }}</strong>) for review and response.
        @else
          There is a new update on your grievance ticket from <strong>{{ $actorName }}</strong>:
        @endif
      </p>

      <div class="box">
        <div style="font-size:12px; color:#64748b; margin-bottom:4px;">
          Updated by {{ $actorName }} · Status: <strong>{{ $grievance->status === 'resolved' ? 'Resolved & Closed' : 'Under Review (' . $ownerLabel . ')' }}</strong>
        </div>
        <div style="color:#0f172a;">{{ $update->message }}</div>
      </div>

      @if ($grievance->reimbursement)
        <p style="font-size:12.5px; color:#475569;">
          Linked Claim: <strong>{{ $grievance->reimbursement->claim_number }}</strong> (Net ₹{{ number_format((float) $grievance->reimbursement->balance_amount, 2) }})
        </p>
      @endif

      <a href="{{ route('grievance.show', $grievance) }}" class="btn">Track Ticket Details</a>
    </div>
    <div class="footer">
      ASSOCHAM Procurement &amp; Governance Portal · Automated Grievance Redressal Notification
    </div>
  </div>
</body>
</html>

