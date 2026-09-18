@extends('layouts.app')

@section('title', $log->ref)

@php
  $typeIcons = [
    'Flight ticket' => 'bi-airplane',
    'Hotel' => 'bi-building',
    'Transport' => 'bi-car-front',
    'Other' => 'bi-receipt',
  ];
  $typeIcon = $typeIcons[$log->type] ?? 'bi-receipt';
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('flight') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Flight &amp; Boarding Logistics</span>
  </a>

  <div class="detail-header-card">
    <div class="detail-avatar" style="background:linear-gradient(135deg,rgba(30,95,168,0.15),rgba(30,95,168,0.05)); color:var(--maroon); border:1px solid rgba(30,95,168,0.2);">
      <i class="bi {{ $typeIcon }} fs-4"></i>
    </div>
    <div>
      <div class="detail-name">{{ $log->ref }} — {{ $log->type }}</div>
      <div class="detail-meta d-flex align-items-center gap-2 mt-1">
        <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">TRF: {{ $log->trf->trf_number }}</span>
        <span>•</span>
        <span>{{ $log->trf->employee->name }}</span>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge-soft" style="background:#E6F0FB; color:#1E5FA8; font-weight:600; padding:6px 14px;">
        <i class="bi bi-check2-all me-1"></i> Ready to Settle
      </span>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <!-- Left: Transaction Metadata -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-info-circle text-primary"></i>
          <span>Transaction Dossier</span>
        </h3>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="field-label">Linked Travel Requisition</div>
            <div class="field-value mt-1">
              <a href="{{ route('trf.show', $log->trf) }}" class="d-inline-flex align-items-center gap-1 text-decoration-none" style="color:var(--maroon); font-weight:600;">
                <i class="bi bi-airplane"></i>
                <span>{{ $log->trf->trf_number }}</span>
              </a>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Traveling Employee</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1">
              <i class="bi bi-person text-muted"></i>
              <span>{{ $log->trf->employee->name }}</span>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Transaction Category</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1">
              <i class="bi {{ $typeIcon }} text-muted"></i>
              <span>{{ $log->type }}</span>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Service Provider / Vendor</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1">
              <i class="bi bi-shop text-muted"></i>
              <span>{{ $log->vendor->company_name ?? 'Direct Provider / Non-empanelled' }}</span>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Total Booking Cost</div>
            <div class="field-value mt-1" style="font-size:18px; font-weight:600; color:#1e293b;">
              ₹{{ number_format($log->cost, 2) }}
            </div>
          </div>

          <div class="col-sm-6">
            <div class="field-label">Uploaded / Recorded By</div>
            <div class="field-value mt-1 d-flex align-items-center gap-1">
              <i class="bi bi-person-check text-muted"></i>
              <span>{{ $log->uploader?->name ?? 'System' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Proof Voucher & Documents -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4">
        <h3 class="h6 mb-3 d-flex align-items-center gap-2" style="font-weight:600;">
          <i class="bi bi-paperclip text-primary"></i>
          <span>Voucher / Boarding Pass Proof</span>
        </h3>

        @if ($log->file_path)
          <div class="p-3 rounded-3 border text-center" style="background:#f8fafc;">
            <i class="bi bi-file-earmark-pdf text-danger" style="font-size:42px;"></i>
            <div class="mt-2 text-dark" style="font-weight:600; font-size:13.5px;">Document Attached</div>
            <div class="text-muted" style="font-size:12px;">Boarding pass / electronic ticket confirmation</div>
            <a href="{{ asset('storage/'.$log->file_path) }}" target="_blank" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none; padding:8px 18px;">
              <i class="bi bi-box-arrow-up-right"></i>
              <span>View / Download File</span>
            </a>
          </div>
        @else
          <div class="p-4 rounded-3 text-center border" style="background:#f8fafc; border-style:dashed !important;">
            <i class="bi bi-file-earmark-x text-muted" style="font-size:36px;"></i>
            <div class="mt-2 text-muted" style="font-size:13px;">No digital voucher attached to this transaction record.</div>
          </div>
        @endif

        <div class="admin-sidebar-card mt-3">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>Settlement Linkage</span>
          </div>
          <div class="p-3" style="font-size:12px; color:#64748b;">
            This cost is automatically factored into reconciliation when the employee files their travel settlement.
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'flight', crumb: 'Travel & Logistics', title: 'Flight & Boarding Detail' });
@endsection

