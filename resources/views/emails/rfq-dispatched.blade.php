<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request for Quotation - {{ $rfq->rfq_number }}</title>
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
      max-width: 620px;
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
    .badge-rfq {
      display: inline-block;
      background-color: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
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
      width: 38%;
      color: #64748b;
      font-weight: 500;
    }
    .info-value {
      width: 62%;
      color: #0f172a;
      font-weight: 600;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      margin-top: 8px;
    }
    .items-table th {
      background: #e2e8f0;
      color: #334155;
      font-weight: 600;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      padding: 8px 10px;
      text-align: left;
      border: 1px solid #cbd5e1;
    }
    .items-table td {
      padding: 10px;
      border: 1px solid #e2e8f0;
      color: #334155;
      vertical-align: top;
    }
    .items-table tr:nth-child(even) td {
      background-color: #ffffff;
    }
    .items-table tr:nth-child(odd) td {
      background-color: #f8fafc;
    }
    .specs-text {
      font-size: 12px;
      color: #64748b;
      margin-top: 4px;
      line-height: 1.4;
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
    .due-date-highlight {
      background: #fffbeb;
      border-left: 3px solid #f59e0b;
      padding: 12px 14px;
      border-radius: 4px;
      margin: 18px 0;
      font-size: 13px;
      color: #92400e;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <span class="badge-rfq">Request for Quotation</span>
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
        You are invited to submit your competitive quotation for the project outlined below. Please review the requirement details, bill of quantities, and technical specifications, and respond with your best commercial terms on or before the indicated due date.
      </p>

      <!-- RFQ Overview Card -->
      <div class="card-box">
        <div class="card-title">Project &amp; RFQ Information</div>
        <table class="info-table">
          <tr>
            <td class="info-label">RFQ Reference</td>
            <td class="info-value">{{ $rfq->rfq_number }}</td>
          </tr>
          <tr>
            <td class="info-label">Project / Title</td>
            <td class="info-value">{{ $rfq->project_name ?: ($rfq->purchaseRequisition?->title ?: '—') }}</td>
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
            <td class="info-label">Issue Date</td>
            <td class="info-value">{{ optional($rfq->requisition_date ?? $rfq->created_at)->format('d F Y') }}</td>
          </tr>
          <tr>
            <td class="info-label">Quotation Due Date</td>
            <td class="info-value" style="color: #b45309;">
              {{ $rfq->due_date ? $rfq->due_date->format('d F Y') : (optional($rfq->purchaseRequisition?->required_by)->format('d F Y') ?? 'Immediate / 7 Days from issue') }}
            </td>
          </tr>
          @if ($rfq->purchaseRequisition?->special_instructions)
          <tr>
            <td class="info-label">Instructions</td>
            <td class="info-value" style="font-weight: 400; font-size: 13px;">{{ $rfq->purchaseRequisition->special_instructions }}</td>
          </tr>
          @endif
        </table>
      </div>

      <!-- Required Items & Scope Table -->
      @php
        $items = $rfq->purchaseRequisition?->items->where('status', '!=', 'rejected') ?? collect();
        if ($items->isEmpty() && $rfq->purchaseRequisition) {
          $items = $rfq->purchaseRequisition->items;
        }
      @endphp
      <div class="card-box" style="margin-bottom: 20px;">
        <div class="card-title">Bill of Quantities &amp; Technical Specifications ({{ $items->count() }} {{ \Illuminate\Support\Str::plural('Item', $items->count()) }})</div>
        <table class="items-table">
          <thead>
            <tr>
              <th style="width: 30px; text-align: center;">#</th>
              <th>Item Name &amp; Description / Specs</th>
              <th style="width: 90px; text-align: center;">Quantity</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $item)
            <tr>
              <td style="text-align: center; color: #64748b;">{{ $loop->iteration }}</td>
              <td>
                <strong style="color: #0f172a;">{{ $item->item_name }}</strong>
                @if ($item->description)
                  <div class="specs-text">{{ $item->description }}</div>
                @endif
              </td>
              <td style="text-align: center; font-weight: 600; white-space: nowrap;">
                {{ $item->quantity }} {{ $item->unit ?: 'Units' }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="3" style="text-align: center; color: #94a3b8; padding: 16px;">
                Standard scope of work applies. Please contact the procurement coordinator for specific details.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Due Date Notice -->
      <div class="due-date-highlight">
        <strong>Submission Deadline:</strong> Please ensure your commercial quote includes basic unit rate, applicable GST %, transportation charges, delivery timeline, payment terms, and validity period. Submit your quotation by <strong>{{ $rfq->due_date ? $rfq->due_date->format('d F Y') : (optional($rfq->purchaseRequisition?->required_by)->format('d F Y') ?? 'Within 7 Days') }}</strong>.
      </div>

      @if ($rfq->attachments->isNotEmpty() || ($rfq->purchaseRequisition && $rfq->purchaseRequisition->attachments->isNotEmpty()))
      <p class="paragraph" style="font-size: 13px; color: #475569;">
        📎 <strong>Attachments Included:</strong> Supporting documents, specifications, or drawings have been attached to this email.
      </p>
      @endif

      <p class="paragraph" style="margin-bottom: 0;">
        If you have questions regarding this RFQ, please reply directly to this email or contact the procurement department.
      </p>
    </div>

    <div class="footer">
      This is an automated request for quotation from the Procurement &amp; Sourcing System.<br>
      Please submit all quotes prior to the closing deadline.
    </div>
  </div>
</body>
</html>

