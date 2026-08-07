@extends('layouts.app')

@section('title', 'Vendor Empanelment')

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
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Vendors are submitted by the Buyer and approved through Admin Head → ASG-1 (L1) → ASG-2 (L2) → CFO (final). Only empanelled, non-blacklisted vendors are selectable on RFQs and POs.</div>
    @if ($canSubmit)
      <a href="{{ route('onboarding') }}" class="btn-brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"></path></svg>
        Add Vendor
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif

  <div class="stat-strip">
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Total Vendors</div>
        <div class="stat-count">{{ $vendors->count() }}</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Empanelled</div>
        <div class="stat-count">{{ $vendors->where('status', 'approved')->count() }}</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-body">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-count">{{ $vendors->whereIn('status', ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo'])->count() }}</div>
      </div>
    </div>
  </div>

  <div class="card-panel p-0 mt-3 overflow-hidden">
    <div class="table-toolbar">
      <div class="table-toolbar-title">Empanelment Queue</div>
    </div>

    <div style="overflow-x:auto;">
      <div class="vendor-table-head">
        <div>Vendor</div>
        <div>PAN / GSTIN</div>
        <div>Category</div>
        <div>Contact</div>
        <div>MSME</div>
        <div>Status</div>
        <div></div>
      </div>

      @forelse ($vendors as $vendor)
        @php $meta = $statusMeta[$vendor->status] ?? $statusMeta['draft']; @endphp
        <a href="{{ route('empanelment.show', $vendor) }}" class="vendor-row" style="text-decoration:none;">
          <div class="vendor-name-cell">
            <div>
              <div class="vendor-code">{{ $vendor->vendor_code }}</div>
              <div class="vendor-name">{{ $vendor->company_name }}</div>
            </div>
          </div>
          <div class="pan-gst-cell">
            <span>{{ $vendor->pan ?: '—' }}</span>
            <span>{{ $vendor->gstin ?: '—' }}</span>
          </div>
          <div>{{ $vendor->category ?: '—' }}</div>
          <div>{{ $vendor->contact_person ?: '—' }}</div>
          <div>{{ $vendor->msme_status ? 'Yes' : 'No' }}</div>
          <div>
            <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
          </div>
          <div><span class="btn-view-drawer">View</span></div>
        </a>
      @empty
        <div class="p-4 text-center text-muted">No vendors submitted yet.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Empanelment' });
@endsection
