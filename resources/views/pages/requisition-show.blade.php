@extends('layouts.app')

@section('title', $pr->pr_number)

@php
  $statusMeta = [
    'pending_l1'  => ['label' => 'Pending — L1 (Basu)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l2'  => ['label' => 'Pending — L2 (Ambika)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l3'  => ['label' => 'Pending — L3 (CFO)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l4'  => ['label' => 'Pending — L4 (SG)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'    => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$pr->status] ?? $statusMeta['draft'];
  $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
               'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
               'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('requisition') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Purchase Requisition
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">PR</div>
    <div>
      <div class="detail-name">{{ $pr->pr_number }}</div>
      <div class="detail-meta">{{ $pr->department }} · Raised by {{ $pr->creator?->name ?? '—' }} @if($pr->sole_source)· Sole Source @endif</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Line Items</div>
      <div style="overflow-x:auto;">
        <table class="table table-sm">
          <thead>
            <tr class="text-muted" style="font-size:11px; text-transform:uppercase;">
              <th>Item</th><th>Qty</th><th>Unit Cost</th><th>GST%</th><th class="text-end">Line Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pr->items as $item)
              <tr style="font-size:13px;">
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }} {{ $item->unit }}</td>
                <td>₹{{ number_format($item->unit_cost, 2) }}</td>
                <td>{{ rtrim(rtrim(number_format($item->gst_percent, 2), '0'), '.') }}%</td>
                <td class="text-end">₹{{ number_format($item->lineTotalInclGst(), 2) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr style="font-size:13px; font-weight:700;">
              <td colspan="4" class="text-end">Total (GST-incl.)</td>
              <td class="text-end">₹{{ number_format($pr->estimatedCostInclGst(), 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
      @if ($pr->required_by)
        <div class="mt-2"><div class="field-label">Required By</div><div class="field-value">{{ $pr->required_by->format('d M Y') }}</div></div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval Chain</div>
      @foreach ($pr->approvals as $approval)
        @php $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">L{{ $approval->level }} — {{ \App\Support\Roles::label($approval->role_required) }}</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision (L{{ $pendingApproval->level }} — {{ \App\Support\Roles::label($pendingApproval->role_required) }})</div>
          <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('requisition.reject', $pr) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif (in_array($pr->status, ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4']))
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">Awaiting a different role's action — nothing for you to do here right now.</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'PR Detail' });
@endsection
