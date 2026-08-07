@extends('layouts.app')

@section('title', $trf->trf_number)

@php
  $statusMeta = [
    'pending_asg' => ['label' => 'Pending — ASG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_sg'  => ['label' => 'Pending — SG', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'    => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'pushed_back' => ['label' => 'Pushed Back', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
    'draft'       => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$trf->status] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('trf') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Travel Requisition
  </a>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar">{{ strtoupper(substr($trf->employee->name, 0, 2)) }}</div>
    <div>
      <div class="detail-name">{{ $trf->trf_number }} — {{ $trf->employee->name }}</div>
      <div class="detail-meta">{{ $trf->route ?: 'Route not specified' }} @if($trf->is_international)· International @endif</div>
    </div>
    <div class="ms-auto">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }};">{{ $meta['label'] }}</span>
    </div>
  </div>

  <div class="detail-grid mt-3">
    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Trip Details</div>
      <div class="row row-cols-2 g-3">
        <div><div class="field-label">Purpose</div><div class="field-value">{{ $trf->purpose ?: '—' }}</div></div>
        <div><div class="field-label">Booking Type</div><div class="field-value">{{ $trf->booking_type === 'on_behalf' ? 'On Behalf Of' : 'Self' }}</div></div>
        <div><div class="field-label">Travel Start</div><div class="field-value">{{ optional($trf->travel_start)->format('d M Y') ?? '—' }}</div></div>
        <div><div class="field-label">Travel End</div><div class="field-value">{{ optional($trf->travel_end)->format('d M Y') ?? '—' }}</div></div>
        <div><div class="field-label">Insurance</div><div class="field-value">{{ $trf->insurance ? 'Yes' : 'No' }}</div></div>
        <div><div class="field-label">Accommodation</div><div class="field-value">{{ $trf->accommodation ? 'Yes' : 'No' }}</div></div>
        <div><div class="field-label">Requested Advance</div><div class="field-value">₹{{ number_format($trf->requested_advance, 2) }}</div></div>
      </div>

      @if ($trf->status === 'approved' && auth()->user()->role === \App\Support\Roles::ADMIN_HEAD)
        <a href="{{ route('flight.create') }}" class="btn-brand w-100 d-block text-center mt-3">Log Flight &amp; Boarding Cost</a>
      @endif
      @if ($trf->status === 'approved' && auth()->id() === $trf->employee_id)
        <a href="{{ route('reimb.create') }}" class="btn btn-outline-secondary w-100 d-block text-center mt-2">File Reimbursement Claim</a>
      @endif
    </div>

    <div class="detail-col card-panel p-3">
      <div class="h6 mb-3">Approval</div>
      @foreach ($trf->approvals as $approval)
        @php $stepMeta = ['pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
                          'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
                          'pushed_back' => ['label' => 'Pushed Back', 'bg' => '#FDEBD3', 'fg' => '#B5601A'],
                          'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']];
             $sm = $stepMeta[$approval->status] ?? $stepMeta['pending']; @endphp
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border-lighter);">
          <div>
            <div class="field-value">{{ \App\Support\Roles::labelAny($approval->role_required) }}</div>
            @if ($approval->comments)
              <div class="field-label mt-1" style="text-transform:none; letter-spacing:0;">{{ $approval->comments }}</div>
            @endif
          </div>
          <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }};">{{ $sm['label'] }}</span>
        </div>
      @endforeach

      @if ($pendingApproval)
        <div class="mt-3 pt-2">
          <div class="field-label mb-2">Your decision ({{ \App\Support\Roles::labelAny($pendingApproval->role_required) }})</div>
          <form method="POST" action="{{ route('trf.approve', $trf) }}" class="mb-2">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Comments (optional)">
            <button type="submit" class="btn-brand w-100">Approve</button>
          </form>
          <form method="POST" action="{{ route('trf.pushback', $trf) }}"
                onsubmit="return this.comments.value.trim() !== '' || (alert('Review comments are required to push back.'), false);">
            @csrf
            <input type="text" name="comments" class="form-control form-control-sm mb-2" placeholder="Review comments (required)">
            <button type="submit" class="btn btn-outline-warning w-100">Push Back</button>
          </form>
        </div>
      @elseif (in_array($trf->status, ['pending_asg', 'pending_sg']))
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">Awaiting a different role's action — nothing for you to do here right now.</div>
        </div>
      @elseif ($trf->status === 'pushed_back')
        <div class="note-bar mt-3">
          <div class="note-dot"></div>
          <div class="note-text">Pushed back with review comments above — the requester can revise the request (resubmission isn't built yet).</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'trf', crumb: 'Travel & Logistics', title: 'TRF Detail' });
@endsection
