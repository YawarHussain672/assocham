<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Requisition {{ $pr->pr_number }}</title>
<style>
  @page {
    margin: 12mm 15mm 12mm 15mm;
    size: a4 portrait;
  }
  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 10px;
    line-height: 1.35;
    color: #1e293b;
    margin: 0;
    padding: 0;
  }
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  .header-table td {
    vertical-align: middle;
  }
  .logo-img {
    max-height: 50px;
    width: auto;
  }
  .org-title {
    font-size: 12.5px;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
  }
  .org-sub {
    font-size: 8.5px;
    color: #64748b;
    margin-top: 2px;
  }
  .pr-banner {
    background-color: #0f172a;
    color: #ffffff;
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
    padding: 5px 0;
    margin-bottom: 8px;
    text-transform: uppercase;
    border-radius: 2px;
  }
  .info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  .info-table td {
    vertical-align: top;
    padding: 5px 8px;
    border: 1px solid #cbd5e1;
    font-size: 9.5px;
  }
  .meta-label {
    font-weight: bold;
    color: #334155;
    background-color: #f8fafc;
    width: 18%;
    white-space: nowrap;
  }
  .meta-val {
    color: #0f172a;
    width: 32%;
  }
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  .items-table th {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 6px 6px;
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #334155;
    text-align: left;
  }
  .items-table td {
    border: 1px solid #cbd5e1;
    padding: 5px 6px;
    font-size: 9px;
    vertical-align: top;
  }
  .items-table tfoot td {
    background-color: #f8fafc;
    font-weight: bold;
  }
  .text-center { text-align: center; }
  .text-end { text-align: right; }
  .text-nowrap { white-space: nowrap; }

  .status-badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 8.5px;
    font-weight: bold;
    border-radius: 3px;
    text-transform: uppercase;
  }
  .badge-approved { background-color: #dcfce7; color: #15803d; }
  .badge-pending { background-color: #fef3c7; color: #b45309; }
  .badge-rejected { background-color: #fee2e2; color: #b91c1c; }

  .approvals-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    page-break-inside: avoid;
  }
  .approvals-table th {
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 5px 6px;
    font-size: 8.5px;
    text-transform: uppercase;
    color: #475569;
  }
  .approvals-table td {
    border: 1px solid #cbd5e1;
    padding: 5px 6px;
    font-size: 8.5px;
    vertical-align: middle;
  }
  .footer-note {
    font-size: 8px;
    color: #94a3b8;
    text-align: center;
    margin-top: 10px;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
  }
</style>
</head>
<body>

  @php
    $logoPath = public_path('assets/img/assocham-logo.jpg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

    $items = $pr->items ?? collect();
    $subtotal = $items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
    $totalGst = $items->sum(fn ($i) => ((float)$i->unit_cost * (int)$i->quantity) * ((float)$i->gst_percent / 100));
    $grandTotal = $subtotal + $totalGst;
    $status = $pr->status;
  @endphp

  <!-- Header Section with ASSOCHAM Emblem -->
  <table class="header-table">
    <tr>
      @if($logoBase64)
        <td style="width: 75px; text-align: left;">
          <img src="{{ $logoBase64 }}" class="logo-img" alt="ASSOCHAM Logo">
        </td>
      @endif
      <td style="text-align: {{ $logoBase64 ? 'left' : 'center' }}; padding-left: 10px;">
        <div class="org-title">THE ASSOCIATED CHAMBERS OF COMMERCE &amp; INDUSTRY OF INDIA</div>
        <div class="org-sub">
          YMCA Cultural Centre Library Building, 01, Jai Singh Road, New Delhi – 110001<br>
          Phone: +91-11-46550555 | Email: assocham@nic.in | GSTIN: 07AAATT4704C1ZV
        </div>
      </td>
    </tr>
  </table>

  <!-- Purchase Requisition Banner -->
  <div class="pr-banner">
    PURCHASE REQUISITION (PR)
  </div>

  <!-- Requisition Metadata -->
  <table class="info-table">
    <tr>
      <td class="meta-label">PR NUMBER:</td>
      <td class="meta-val"><strong>{{ $pr->pr_number }}</strong></td>
      <td class="meta-label">DATE CREATED:</td>
      <td class="meta-val">{{ $pr->created_at->format('d M Y, h:i A') }}</td>
    </tr>
    <tr>
      <td class="meta-label">EVENT / PROJECT:</td>
      <td class="meta-val">
        <strong>{{ $pr->projectBudget?->project_name ?: ($pr->event_name ?: 'General Operations') }}</strong>
        @if ($pr->projectBudget)
          <span style="font-size:8px; color:#64748b;">({{ $pr->projectBudget->fiscal_year }})</span>
        @endif
      </td>
      <td class="meta-label">STATUS:</td>
      <td class="meta-val">
        @if ($status === 'approved')
          <span class="status-badge badge-approved">Approved &amp; Cleared</span>
        @elseif ($status === 'rejected')
          <span class="status-badge badge-rejected">Rejected</span>
        @else
          <span class="status-badge badge-pending">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
        @endif
      </td>
    </tr>
    <tr>
      <td class="meta-label">DEPARTMENT:</td>
      <td class="meta-val">{{ $pr->department }}</td>
      <td class="meta-label">REQUESTED BY:</td>
      <td class="meta-val">{{ $pr->creator?->name ?? 'System' }} ({{ $pr->creator?->email ?? '—' }})</td>
    </tr>
    <tr>
      <td class="meta-label">EXPENSE TYPE:</td>
      <td class="meta-val">
        <strong>{{ strtoupper($pr->expense_type ?? ($pr->isCopex() ? 'COPEX' : 'OPEX')) }}</strong>
        {{ $pr->isCopex() ? '(Capital Expenditure)' : '(Operating Expenditure)' }}
      </td>
      <td class="meta-label">REQUIRED BY DATE:</td>
      <td class="meta-val">{{ $pr->required_by ? $pr->required_by->format('d M Y') : 'Immediate' }}</td>
    </tr>
    @if ($pr->title || $pr->special_instructions || $pr->description)
    <tr>
      <td class="meta-label">PURPOSE / TITLE:</td>
      <td colspan="3" style="font-size: 9px; line-height: 1.4;">
        @if ($pr->title) <strong>{{ $pr->title }}</strong><br> @endif
        @if ($pr->special_instructions) <span>{{ $pr->special_instructions }}</span><br> @endif
        @if ($pr->description && $pr->description !== $pr->special_instructions) <span>{{ $pr->description }}</span> @endif
      </td>
    </tr>
    @endif
  </table>

  <!-- Requisition Line Items Table -->
  <table class="items-table">
    <thead>
      <tr>
        <th style="width: 5%; text-align: center;">#</th>
        <th style="width: 38%;">Item Name &amp; Description</th>
        <th style="width: 12%; text-align: center;">Expense</th>
        <th style="width: 9%; text-align: center;">Qty</th>
        <th style="width: 12%; text-align: right;">Unit Cost (₹)</th>
        <th style="width: 9%; text-align: center;">GST %</th>
        <th style="width: 15%; text-align: right;">Total (Incl GST)</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($items as $idx => $item)
        @php
          $itemBase = (float)$item->unit_cost * (int)$item->quantity;
          $itemGst = $itemBase * ((float)$item->gst_percent / 100);
          $itemTotal = $itemBase + $itemGst;
        @endphp
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td>
            <strong>{{ $item->item_name }}</strong>
            @if ($item->description)
              <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ $item->description }}</div>
            @endif
          </td>
          <td class="text-center">
            <span style="font-size: 8px; font-weight: bold; color: {{ $item->isCopex() ? '#b45309' : '#0284c7' }};">
              {{ strtoupper($item->expense_type ?? ($item->isCopex() ? 'COPEX' : 'OPEX')) }}
            </span>
          </td>
          <td class="text-center">{{ $item->quantity }} {{ $item->unit ?: 'Nos' }}</td>
          <td class="text-end font-monospace">{{ number_format((float)$item->unit_cost, 2) }}</td>
          <td class="text-center font-monospace">{{ (float)$item->gst_percent }}%</td>
          <td class="text-end font-monospace"><strong>{{ number_format($itemTotal, 2) }}</strong></td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center text-muted py-3">No line items recorded for this requisition.</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="text-end">Subtotal (Excl. Taxes):</td>
        <td colspan="3" class="text-end font-monospace">₹{{ number_format($subtotal, 2) }}</td>
      </tr>
      <tr>
        <td colspan="4" class="text-end">Total GST:</td>
        <td colspan="3" class="text-end font-monospace">₹{{ number_format($totalGst, 2) }}</td>
      </tr>
      <tr style="background-color: #f1f5f9;">
        <td colspan="4" class="text-end" style="font-size: 10px;"><strong>Grand Total (Incl. GST):</strong></td>
        <td colspan="3" class="text-end font-monospace" style="font-size: 10px;"><strong>₹{{ number_format($grandTotal, 2) }}</strong></td>
      </tr>
    </tfoot>
  </table>

  <!-- Approvals Audit Trail -->
  @if ($pr->approvals->isNotEmpty())
    <div style="font-size: 9px; font-weight: bold; color: #334155; text-transform: uppercase; margin-top: 8px; margin-bottom: 4px;">
      Governance &amp; Approval Sanctions:
    </div>
    <table class="approvals-table">
      <thead>
        <tr>
          <th style="width: 8%; text-align: center;">Stage</th>
          <th style="width: 25%;">Role Required</th>
          <th style="width: 25%;">Decided By</th>
          <th style="width: 16%; text-align: center;">Decision</th>
          <th style="width: 26%;">Timestamp / Remarks</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($pr->approvals->sortBy('level') as $appr)
          <tr>
            <td class="text-center">L{{ $appr->level }}</td>
            <td><strong>{{ \App\Support\Roles::label($appr->role_required) }}</strong></td>
            <td>{{ $appr->approver?->name ?? 'Pending Review' }}</td>
            <td class="text-center">
              @if ($appr->status === 'approved')
                <span class="status-badge badge-approved">Approved</span>
              @elseif ($appr->status === 'rejected')
                <span class="status-badge badge-rejected">Rejected</span>
              @elseif ($appr->status === 'on_hold')
                <span class="status-badge badge-pending">On Hold</span>
              @else
                <span class="status-badge badge-pending">Pending</span>
              @endif
            </td>
            <td style="font-size: 8px;">
              {{ $appr->acted_at ? $appr->acted_at->format('d M Y, h:i A') : 'Awaiting sign-off' }}
              @if ($appr->comments)
                <div style="color: #64748b; margin-top: 1px;">"{{ $appr->comments }}"</div>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- Linked Purchase Orders -->
  @if ($pr->purchaseOrders->isNotEmpty())
    <div style="font-size: 9px; font-weight: bold; color: #334155; text-transform: uppercase; margin-top: 8px; margin-bottom: 4px;">
      Associated Purchase Orders:
    </div>
    <table class="items-table" style="margin-bottom: 8px;">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Contracted Vendor</th>
          <th>PO Date</th>
          <th>Status</th>
          <th class="text-end">PO Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($pr->purchaseOrders as $po)
          <tr>
            <td><strong>{{ $po->po_number }}</strong></td>
            <td>{{ $po->vendor?->company_name ?? '—' }}</td>
            <td>{{ $po->created_at->format('d M Y') }}</td>
            <td>{{ ucfirst($po->status) }}</td>
            <td class="text-end font-monospace">₹{{ number_format((float)$po->amount_incl_gst, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <div class="footer-note">
    This is an officially recorded ASSOCHAM Procurement ERP Document. Generated on {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()?->name ?? 'System' }}.
  </div>

</body>
</html>
