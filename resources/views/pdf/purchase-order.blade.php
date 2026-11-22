<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Order {{ $po->po_number }}</title>
<style>
  @page {
    margin: 15mm 15mm 15mm 15mm;
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
    max-height: 55px;
    width: auto;
  }
  .org-title {
    font-size: 13px;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
  }
  .org-sub {
    font-size: 9px;
    color: #64748b;
    margin-top: 2px;
  }
  .po-banner {
    background-color: #0f172a;
    color: #ffffff;
    text-align: center;
    font-size: 13px;
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
    padding: 6px 8px;
    border: 1px solid #cbd5e1;
  }
  .info-header {
    font-size: 9px;
    font-weight: bold;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 2px;
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
    font-size: 9.5px;
    vertical-align: top;
  }
  .items-table tfoot td {
    background-color: #f8fafc;
    font-weight: bold;
  }
  .text-center { text-align: center; }
  .text-end { text-align: right; }
  .text-nowrap { white-space: nowrap; }

  .order-meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  .order-meta-table td {
    border: 1px solid #cbd5e1;
    padding: 5px 8px;
    font-size: 9px;
    vertical-align: top;
  }
  .meta-label {
    font-weight: bold;
    color: #1e293b;
    width: 16%;
    white-space: nowrap;
  }

  .sign-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    page-break-inside: avoid;
  }
  .sign-table td {
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
    vertical-align: top;
    width: 50%;
  }

  .esign-box {
    border: 1.5px solid #10b981;
    background-color: #f0fdf4;
    border-radius: 4px;
    padding: 6px 8px;
    margin-top: 4px;
  }
  .esign-badge {
    color: #047857;
    font-weight: bold;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
  }
  .esign-name {
    font-size: 11px;
    font-weight: bold;
    color: #065f46;
  }
  .esign-meta {
    font-size: 8px;
    color: #047857;
    margin-top: 2px;
    line-height: 1.3;
  }

  .terms-section {
    page-break-inside: avoid;
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
    background-color: #fafafa;
    border-radius: 2px;
  }
  .terms-title {
    font-size: 9.5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0f172a;
    margin-bottom: 5px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 3px;
  }
  .terms-list {
    margin: 0;
    padding-left: 14px;
    font-size: 8px;
    color: #334155;
    line-height: 1.35;
  }
  .terms-list li {
    margin-bottom: 3px;
  }
</style>
</head>
<body>

  @php
    $logoPath = public_path('assets/img/assocham-logo.jpg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

    $pr = $po->purchaseRequisition;
    $vendor = $po->vendor;
    $items = $items ?? $po->effective_items;

    $subtotalExclGst = $items->sum(fn ($i) => (float)$i->unit_cost * (int)$i->quantity);
    $totalAmountInclGst = (float)$po->amount_incl_gst;
    $totalGst = max(0, $totalAmountInclGst - $subtotalExclGst);

    $isApproved = $po->status === 'approved';
    $finalAppr = $po->finalApproval();
  @endphp

  <!-- Header Section with ASSOCHAM Emblem -->
  <table class="header-table">
    <tr>
      @if($logoBase64)
        <td style="width: 80px; text-align: left;">
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

  <!-- Purchase Order Banner -->
  <div class="po-banner">
    PURCHASE ORDER
  </div>

  <!-- Supplier & PO Details Block (2-Columns) -->
  <table class="info-table">
    <tr>
      <!-- Left: Supplier Info -->
      <td style="width: 55%;">
        <div class="info-header">Supplier Name &amp; Address:</div>
        <div style="font-size: 11px; font-weight: bold; color: #0f172a; margin-bottom: 2px;">
          {{ $vendor->company_name }}
        </div>
        <div style="color: #334155;">
          @if ($vendor->address)
            {!! nl2br(e($vendor->address)) !!}<br>
          @elseif ($vendor->location)
            {{ $vendor->location }}<br>
          @endif
          @if ($vendor->gstin) <strong>GSTIN:</strong> {{ $vendor->gstin }} &nbsp;|&nbsp; @endif
          @if ($vendor->pan) <strong>PAN:</strong> {{ $vendor->pan }}<br> @endif
          @if ($vendor->contact_person) <strong>Contact:</strong> {{ $vendor->contact_person }} @endif
          @if ($vendor->contact_phone) ({{ $vendor->contact_phone }}) @endif
          @if ($vendor->contact_email) | {{ $vendor->contact_email }} @endif
        </div>
      </td>

      <!-- Right: PO Details -->
      <td style="width: 45%;">
        <div class="info-header">Order Reference Details:</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 9.5px;">
          <tr>
            <td style="border: none; padding: 1.5px 0; font-weight: bold; width: 45%;">PO No.:</td>
            <td style="border: none; padding: 1.5px 0; font-weight: bold; color: #0f172a;">{{ $po->po_number }}@if ($po->revision_number > 0) (Rev {{ $po->revision_number }})@endif</td>
          </tr>
          <tr>
            <td style="border: none; padding: 1.5px 0; color: #475569;">PO Issue Date:</td>
            <td style="border: none; padding: 1.5px 0;">{{ optional($po->created_at)->format('d M Y') }}</td>
          </tr>
          <tr>
            <td style="border: none; padding: 1.5px 0; color: #475569;">Linked PR No.:</td>
            <td style="border: none; padding: 1.5px 0;">{{ $pr->pr_number ?? '—' }}</td>
          </tr>
          <tr>
            <td style="border: none; padding: 1.5px 0; color: #475569;">Target Department:</td>
            <td style="border: none; padding: 1.5px 0;">{{ $pr->department ?? 'General' }}</td>
          </tr>
          @php
            $pdfProjectName = $pr?->projectBudget?->project_name ?: ($po->event_name ?: $pr?->event_name);
          @endphp
          @if ($pdfProjectName)
          <tr>
            <td style="border: none; padding: 1.5px 0; color: #475569;">Event / Project:</td>
            <td style="border: none; padding: 1.5px 0; font-weight: bold; color: #0f172a;">{{ $pdfProjectName }}</td>
          </tr>
          @endif
          <tr>
            <td style="border: none; padding: 1.5px 0; color: #475569;">Order Status:</td>
            <td style="border: none; padding: 1.5px 0; font-weight: bold; color: {{ $isApproved ? '#10b981' : '#f59e0b' }};">
              {{ $isApproved ? 'ISSUED & E-SIGNED' : 'UNDER APPROVAL' }}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Contracted Line Items Table -->
  <table class="items-table">
    <thead>
      <tr>
        <th class="text-center" style="width: 6%;">S. No.</th>
        <th style="width: 48%;">Particulars / Item Description</th>
        <th class="text-center" style="width: 12%;">Quantity</th>
        <th class="text-end" style="width: 16%;">Rate (in INR)</th>
        <th class="text-end" style="width: 18%;">Amount (in INR)</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($items as $item)
        @php
          $itemBase = (float)$item->unit_cost * (int)$item->quantity;
        @endphp
        <tr>
          <td class="text-center">{{ $loop->iteration }}</td>
          <td>
            <strong>{{ $item->item_name }}</strong>
            @if ($item->description)
              <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">
                {{ $item->description }}
              </div>
            @endif
          </td>
          <td class="text-center">{{ $item->quantity }} {{ $item->unit }}</td>
          <td class="text-end font-monospace">Rs. {{ number_format($item->unit_cost, 2) }}</td>
          <td class="text-end font-monospace">Rs. {{ number_format($itemBase, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="text-center" style="padding: 12px; color: #64748b;">
            Contracted goods and services as specified in linked requisition.
          </td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="text-end">Subtotal (Excl. GST):</td>
        <td class="text-end">Rs. {{ number_format($subtotalExclGst, 2) }}</td>
      </tr>
      <tr>
        <td colspan="4" class="text-end">Applicable GST:</td>
        <td class="text-end">Rs. {{ number_format($totalGst, 2) }}</td>
      </tr>
      <tr style="background-color: #e2e8f0;">
        <td colspan="4" class="text-end" style="font-size: 10.5px;">TOTAL ORDER VALUE (INCL. GST):</td>
        <td class="text-end" style="font-size: 11px; color: #0f172a;">Rs. {{ number_format($totalAmountInclGst, 2) }}</td>
      </tr>
      <tr>
        <td colspan="5" style="background-color: #ffffff; padding: 4px 6px; font-weight: normal; font-size: 9px;">
          <strong>Amount in Words:</strong> {{ $po->amountInWords() }}
        </td>
      </tr>
    </tfoot>
  </table>

  <!-- Commercial, Delivery & Event Details Table -->
  <table class="order-meta-table">
    <tr>
      <td class="meta-label">* Payment Terms:</td>
      <td colspan="3">{{ $po->effective_payment_terms }}</td>
    </tr>
    <tr>
      <td class="meta-label">* Delivery Terms:</td>
      <td colspan="3">{{ $po->effective_delivery_terms }}</td>
    </tr>
    <tr>
      <td class="meta-label">* Event Name:</td>
      <td colspan="3"><strong>{{ $po->event_name ?: ($pr->department . ' Requisition Procurement') }}</strong></td>
    </tr>
    <tr>
      <td style="width: 50%;" colspan="2">
        <div style="font-weight: bold; margin-bottom: 2px;">* Billing Address:</div>
        <div style="color: #334155; line-height: 1.3;">
          {!! nl2br(e($po->effective_billing_address)) !!}
        </div>
      </td>
      <td style="width: 50%;" colspan="2">
        <div style="font-weight: bold; margin-bottom: 2px;">* Delivery Address:</div>
        <div style="color: #334155; line-height: 1.3;">
          @if ($po->delivery_address)
            {!! nl2br(e($po->delivery_address)) !!}
          @else
            THE ASSOCIATED CHAMBERS OF COMMERCE &amp; INDUSTRY OF INDIA<br>
            ASSOCHAM Central Stores / Delhi Office, YMCA Building, New Delhi – 110001
          @endif
        </div>
      </td>
    </tr>
    <tr>
      <td class="meta-label">* Contact Person:</td>
      <td style="width: 34%;">{{ $po->contact_person ?: ($pr->creator?->name ?? 'Purchase Indentor') }}</td>
      <td class="meta-label" style="width: 16%;">* Contact Number:</td>
      <td style="width: 34%;">{{ $po->contact_number ?: ($pr->creator?->email ?? 'procurement@assocham.com') }}</td>
    </tr>
  </table>

  <!-- Signatures Block (Issued By & E-Signed Authorized Signatory) -->
  <table class="sign-table">
    <tr>
      <!-- Issued By -->
      <td>
        <div style="font-weight: bold; font-size: 9.5px; text-transform: uppercase; color: #475569; margin-bottom: 4px;">
          * Issued By:
        </div>
        <div style="font-size: 10.5px; font-weight: bold; color: #0f172a;">
          {{ $po->creator?->name ?? 'Buyer / Procurement Team' }}
        </div>
        <div style="font-size: 8.5px; color: #64748b;">
          {{ $po->creator?->roleLabel() ?? 'Procurement Officer' }}<br>
          ASSOCHAM Administration &amp; Procurement Wing
        </div>
        <div style="font-size: 8px; color: #94a3b8; margin-top: 6px;">
          Generated via ASSOCHAM ERP on {{ now()->format('d M Y, H:i') }}
        </div>
      </td>

      <!-- Authorized Signatory (E-Signed) -->
      <td>
        <div style="font-weight: bold; font-size: 9.5px; text-transform: uppercase; color: #475569; margin-bottom: 4px;">
          * Authorized Signatory:
        </div>

        @if ($po->isESigned())
          <div class="esign-box">
            <div class="esign-badge">
              &#10003; DIGITALLY E-SIGNED &amp; AUTHORIZED
            </div>
            <div class="esign-name">
              {{ $po->eSignedBy()?->name ?? 'Authorized Signatory' }}
            </div>
            <div class="esign-meta">
              <strong>Designation:</strong> {{ $po->eSignedRoleLabel() }}<br>
              <strong>Signed On:</strong> {{ optional($finalAppr?->acted_at)->format('d M Y, H:i:s') }} IST<br>
              <strong>Verification ID:</strong> {{ $po->eSignVerificationHash() }}<br>
              <em>Authenticity guaranteed via ASSOCHAM Secure Digital Governance</em>
            </div>
          </div>
        @else
          <div style="border: 1px dashed #cbd5e1; background-color: #fffbeb; padding: 10px; border-radius: 4px; text-align: center; color: #92400e;">
            <strong>Awaiting Official Digital E-Signature</strong><br>
            <span style="font-size: 8.5px;">Pending final signoff by Authorized Signatory (Admin Head / CFO)</span>
          </div>
        @endif
      </td>
    </tr>
  </table>

  <!-- Editable Terms & Conditions Section -->
  <div class="terms-section">
    <div class="terms-title">* Terms &amp; Conditions:</div>
    <ol class="terms-list">
      @foreach ($po->terms_list as $clause)
        <li>{{ $clause }}</li>
      @endforeach
    </ol>
  </div>

</body>
</html>

