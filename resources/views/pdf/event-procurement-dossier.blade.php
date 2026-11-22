<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event Procurement Dossier — {{ $event->project_name }}</title>
<style>
  @page {
    margin: 12mm 15mm 12mm 15mm;
    size: a4 portrait;
  }
  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9.5px;
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
    max-height: 48px;
    width: auto;
  }
  .org-title {
    font-size: 12px;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
  }
  .org-sub {
    font-size: 8px;
    color: #64748b;
    margin-top: 2px;
  }
  .event-banner {
    background-color: #0f172a;
    color: #ffffff;
    text-align: center;
    font-size: 11.5px;
    font-weight: bold;
    letter-spacing: 1px;
    padding: 5px 0;
    margin-bottom: 8px;
    text-transform: uppercase;
    border-radius: 2px;
  }
  .kpi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
  }
  .kpi-table td {
    border: 1px solid #cbd5e1;
    padding: 6px 8px;
    vertical-align: top;
    text-align: center;
    background-color: #f8fafc;
    width: 25%;
  }
  .kpi-title {
    font-size: 8px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: bold;
    margin-bottom: 2px;
  }
  .kpi-val {
    font-size: 11.5px;
    font-weight: bold;
    color: #0f172a;
  }
  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
  }
  .meta-table td {
    border: 1px solid #cbd5e1;
    padding: 4px 6px;
    font-size: 8.5px;
  }
  .meta-label {
    background-color: #f1f5f9;
    font-weight: bold;
    width: 18%;
    color: #334155;
  }
  .meta-val {
    width: 32%;
    color: #0f172a;
  }
  .section-heading {
    font-size: 9.5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0f172a;
    border-bottom: 1.5px solid #0f172a;
    padding-bottom: 3px;
    margin-top: 10px;
    margin-bottom: 5px;
  }
  .data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
  }
  .data-table th {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 5px 6px;
    font-size: 8.5px;
    font-weight: bold;
    text-transform: uppercase;
    color: #334155;
    text-align: left;
  }
  .data-table td {
    border: 1px solid #cbd5e1;
    padding: 4px 6px;
    font-size: 8.5px;
    vertical-align: top;
  }
  .data-table tfoot td {
    background-color: #f8fafc;
    font-weight: bold;
  }
  .text-center { text-align: center; }
  .text-end { text-align: right; }
  .text-nowrap { white-space: nowrap; }
  .badge {
    display: inline-block;
    padding: 1px 4px;
    border-radius: 2px;
    font-size: 7.5px;
    font-weight: bold;
    text-transform: uppercase;
  }
  .badge-success { background-color: #dcfce7; color: #15803d; }
  .badge-warning { background-color: #fef3c7; color: #b45309; }
  .badge-danger { background-color: #fee2e2; color: #b91c1c; }
  .badge-secondary { background-color: #f1f5f9; color: #475569; }
  .footer-note {
    font-size: 8px;
    color: #94a3b8;
    text-align: center;
    margin-top: 14px;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
  }
</style>
</head>
<body>

  @php
    $logoPath = public_path('assets/img/assocham-logo.jpg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

    $totalAllocated = (float) $event->allocated_amount;
    $totalUtilised = (float) $event->utilised_amount;
    $remaining = max(0.0, $totalAllocated - $totalUtilised);
    $pct = $event->utilisationPercent();

    $prApprovedTotal = (float) $prs->where('status', 'approved')->sum(fn($p) => $p->estimatedCostInclGst());
    $prPendingTotal = (float) $prs->where('status', '!=', 'approved')->where('status', '!=', 'rejected')->sum(fn($p) => $p->estimatedCostInclGst());
    $poIssuedTotal = (float) $pos->where('status', 'approved')->sum('amount_incl_gst');
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

  <!-- Event Procurement Dossier Banner -->
  <div class="event-banner">
    EVENT PROCUREMENT DOSSIER (COMPLETE PR &amp; PO RECORD)
  </div>

  <!-- Event Specifications -->
  <table class="meta-table">
    <tr>
      <td class="meta-label">EVENT / PROJECT:</td>
      <td class="meta-val" colspan="3"><strong style="font-size: 10px;">{{ $event->project_name }}</strong></td>
    </tr>
    <tr>
      <td class="meta-label">DEPARTMENT:</td>
      <td class="meta-val">{{ $event->department }}</td>
      <td class="meta-label">FISCAL YEAR:</td>
      <td class="meta-val">{{ $event->fiscal_year }}</td>
    </tr>
    <tr>
      <td class="meta-label">COUNCIL:</td>
      <td class="meta-val">{{ $event->council ?: '—' }}</td>
      <td class="meta-label">LEAD / HOD:</td>
      <td class="meta-val">{{ $event->hod ?: '—' }}</td>
    </tr>
    <tr>
      <td class="meta-label">LOCATION &amp; MONTH:</td>
      <td class="meta-val">{{ $event->location ?: 'Delhi' }} ({{ $event->event_month ?: 'FY' }})</td>
      <td class="meta-label">CATEGORY:</td>
      <td class="meta-val">{{ $event->category ?: 'National Summit' }}</td>
    </tr>
  </table>

  <!-- KPI Matrix -->
  <table class="kpi-table">
    <tr>
      <td>
        <div class="kpi-title">Allocated Budget</div>
        <div class="kpi-val">₹{{ number_format($totalAllocated, 2) }}</div>
      </td>
      <td>
        <div class="kpi-title">Committed Spend</div>
        <div class="kpi-val" style="color:#0284c7;">₹{{ number_format($totalUtilised, 2) }}</div>
        <div style="font-size:7.5px; color:#64748b;">{{ $pct }}% Utilised</div>
      </td>
      <td>
        <div class="kpi-title">Available Headroom</div>
        <div class="kpi-val" style="color:{{ $remaining > 0 ? '#16a34a' : '#dc2626' }};">₹{{ number_format($remaining, 2) }}</div>
      </td>
      <td>
        <div class="kpi-title">POs Issued (Approved)</div>
        <div class="kpi-val" style="color:#16a34a;">₹{{ number_format($poIssuedTotal, 2) }}</div>
        <div style="font-size:7.5px; color:#64748b;">{{ $pos->where('status', 'approved')->count() }} Order(s)</div>
      </td>
    </tr>
  </table>

  <!-- SECTION 1: PURCHASE REQUISITIONS -->
  <div class="section-heading">
    1. Purchase Requisitions (PR) Linked to Event (Total: {{ $prs->count() }})
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width: 5%; text-align: center;">#</th>
        <th style="width: 22%;">PR Number</th>
        <th style="width: 28%;">Title / Scope of Work</th>
        <th style="width: 15%;">Requester</th>
        <th style="width: 12%; text-align: center;">Status</th>
        <th style="width: 18%; text-align: right;">Est. Cost (₹)</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($prs as $idx => $pr)
        @php
          $prCost = $pr->estimatedCostInclGst();
          $prBadge = match($pr->status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-warning',
          };
        @endphp
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td><strong>{{ $pr->pr_number }}</strong></td>
          <td>
            <strong>{{ $pr->title ?: 'Procurement Requisition' }}</strong>
            @if ($pr->items->isNotEmpty())
              <div style="font-size:7.5px; color:#64748b;">
                {{ $pr->items->pluck('item_name')->take(3)->join(', ') }}{{ $pr->items->count() > 3 ? '...' : '' }} ({{ $pr->items->count() }} items)
              </div>
            @endif
          </td>
          <td>{{ $pr->creator?->name ?? 'System' }}</td>
          <td class="text-center"><span class="badge {{ $prBadge }}">{{ ucfirst(str_replace('_', ' ', $pr->status)) }}</span></td>
          <td class="text-end font-monospace {{ $pr->status === 'rejected' ? 'text-decoration-line-through' : '' }}">
            ₹{{ number_format($prCost, 2) }}
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center py-2 text-muted">No Purchase Requisitions linked to this event.</td>
        </tr>
      @endforelse
    </tbody>
    @if ($prs->isNotEmpty())
      <tfoot>
        <tr>
          <td colspan="5" class="text-end">Total Approved PRs Value:</td>
          <td class="text-end font-monospace">₹{{ number_format($prApprovedTotal, 2) }}</td>
        </tr>
        <tr>
          <td colspan="5" class="text-end">Total Pipeline (Pending) PRs:</td>
          <td class="text-end font-monospace">₹{{ number_format($prPendingTotal, 2) }}</td>
        </tr>
      </tfoot>
    @endif
  </table>

  <!-- SECTION 2: PURCHASE ORDERS -->
  <div class="section-heading">
    2. Purchase Orders (PO) Associated with Event (Total: {{ $pos->count() }})
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width: 5%; text-align: center;">#</th>
        <th style="width: 20%;">PO Number</th>
        <th style="width: 26%;">Contracted Vendor</th>
        <th style="width: 17%;">Linked PR</th>
        <th style="width: 14%; text-align: center;">Status</th>
        <th style="width: 18%; text-align: right;">Amount (Incl. GST)</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($pos as $idx => $po)
        @php
          $poBadge = match($po->status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-warning',
          };
        @endphp
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td><strong>{{ $po->po_number }}</strong></td>
          <td>
            <strong>{{ $po->vendor?->company_name ?? '—' }}</strong>
            @if ($po->vendor?->gstin)
              <div style="font-size:7.5px; color:#64748b;">GSTIN: {{ $po->vendor->gstin }}</div>
            @endif
          </td>
          <td>{{ $po->purchaseRequisition?->pr_number ?? '—' }}</td>
          <td class="text-center"><span class="badge {{ $poBadge }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span></td>
          <td class="text-end font-monospace">₹{{ number_format((float)$po->amount_incl_gst, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center py-2 text-muted">No Purchase Orders issued for this event yet.</td>
        </tr>
      @endforelse
    </tbody>
    @if ($pos->isNotEmpty())
      <tfoot>
        <tr style="background-color: #f1f5f9;">
          <td colspan="5" class="text-end"><strong>Total PO Spend (Approved):</strong></td>
          <td class="text-end font-monospace"><strong>₹{{ number_format($poIssuedTotal, 2) }}</strong></td>
        </tr>
      </tfoot>
    @endif
  </table>

  <div class="footer-note">
    Official Event Procurement Summary &mdash; ASSOCHAM Enterprise ERP. Generated on {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()?->name ?? 'System' }}.
  </div>

</body>
</html>
