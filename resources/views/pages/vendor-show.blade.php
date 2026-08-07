@extends('layouts.app')

@section('title', $vendor->company_name)

@php
  $statusMeta = [
    'pending_admin_head' => ['label' => 'Pending — Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l1'          => ['label' => 'Pending — L1 (ASG 1)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l2'          => ['label' => 'Pending — L2 (ASG 2)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_cfo'         => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'            => ['label' => 'Empanelled', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'            => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'               => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$vendor->status] ?? $statusMeta['draft'];
  $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
               'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
               'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('empanelment') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Empanelment
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">{{ strtoupper(substr($vendor->company_name, 0, 2)) }}</div>
    <div>
      <div class="detail-name">{{ $vendor->company_name }}</div>
      <div class="detail-meta">{{ $vendor->vendor_code }} · {{ $vendor->category ?: 'Uncategorised' }}</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Vendor Details</div>
      <div class="row row-cols-2 g-3">
        <div><div class="field-label">PAN</div><div class="field-value">{{ $vendor->pan ?: '—' }}</div></div>
        <div><div class="field-label">GSTIN</div><div class="field-value">{{ $vendor->gstin ?: '—' }}</div></div>
        <div><div class="field-label">Contact Person</div><div class="field-value">{{ $vendor->contact_person ?: '—' }}</div></div>
        <div><div class="field-label">Contact Phone</div><div class="field-value">{{ $vendor->contact_phone ?: '—' }}</div></div>
        <div><div class="field-label">Contact Email</div><div class="field-value">{{ $vendor->contact_email ?: '—' }}</div></div>
        <div><div class="field-label">MSME</div><div class="field-value">{{ $vendor->msme_status ? 'Yes' : 'No' }}</div></div>
        <div><div class="field-label">Bank A/C</div><div class="field-value">{{ $vendor->bank_account_no ?: '—' }}</div></div>
        <div><div class="field-label">IFSC</div><div class="field-value">{{ $vendor->bank_ifsc ?: '—' }}</div></div>
        <div><div class="field-label">Payment Terms</div><div class="field-value">{{ $vendor->payment_terms ?: '—' }}</div></div>
        <div><div class="field-label">Submitted By</div><div class="field-value">{{ $vendor->submittedBy?->name ?? '—' }}</div></div>
      </div>
      @if ($vendor->address)
        <div class="mt-3"><div class="field-label">Address</div><div class="field-value">{{ $vendor->address }}</div></div>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval Chain</div>
      @foreach ($vendor->approvals as $approval)
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
          <form method="POST" action="{{ route('empanelment.approve', $vendor) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('empanelment.reject', $vendor) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Reason for rejection (required)">
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      @elseif (in_array($vendor->status, ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo']))
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
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Detail' });
@endsection
