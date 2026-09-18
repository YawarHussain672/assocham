@extends('layouts.app')

@section('title', $vendor->company_name)

@php
  $statusMeta = [
    'pending_admin_head' => ['label' => 'Pending — Admin Head', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l1'         => ['label' => 'Pending — L1 (ASG 1)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_l2'         => ['label' => 'Pending — L2 (ASG 2)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'pending_cfo'        => ['label' => 'Pending — CFO', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'approved'           => ['label' => 'Empanelled & Verified', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected'           => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
    'draft'              => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
  ];
  $meta = $statusMeta[$vendor->status] ?? $statusMeta['draft'];
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('empanelment') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Vendor Empanelment</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <!-- Header Card -->
  <div class="detail-header-card d-flex align-items-center justify-content-between p-3 rounded-3" style="background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 16px -2px rgba(15,23,42,0.04);">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#d97706,#f59e0b); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-shop"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $vendor->company_name }}</div>
          <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:11.5px; padding:3px 8px;">
            {{ $vendor->vendor_code }}
          </span>
          @if ($vendor->msme_status)
            <span class="badge" style="background:#ecfdf5; color:#059669; font-weight:600; font-size:11.5px; padding:3px 8px;">
              <i class="bi bi-patch-check"></i> MSME Registered
            </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
          <span><i class="bi bi-tags"></i> {{ $vendor->category ?: 'General Goods & Services' }}</span>
          <span><i class="bi bi-person"></i> Submitted By: {{ $vendor->submittedBy?->name ?? 'Buyer' }}</span>
        </div>
      </div>
    </div>
    <div>
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-size:13px; font-weight:600; padding:6px 12px; border-radius:20px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4 mt-2">
    <!-- Left Column: Vendor Corporate Profile (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-card-checklist" style="color:var(--maroon);"></i>
              <span>Statutory &amp; Banking Dossier</span>
            </div>
            <span class="badge" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:12px; padding:5px 10px;">
              Compliance Verified
            </span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-person-badge text-secondary"></i> Permanent Account No. (PAN)</div>
                <div class="field-value text-dark">{{ $vendor->pan ?: 'Not provided' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-file-earmark-check text-secondary"></i> GST Identification No. (GSTIN)</div>
                <div class="field-value text-dark">{{ $vendor->gstin ?: 'Unregistered' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-person text-secondary"></i> Designated Representative</div>
                <div class="field-value text-dark">{{ $vendor->contact_person ?: '—' }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">{{ $vendor->contact_email ?: 'No email' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-telephone text-secondary"></i> Phone / Mobile</div>
                <div class="field-value text-dark">{{ $vendor->contact_phone ?: '—' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-bank text-secondary"></i> Bank Account</div>
                <div class="field-value text-dark">{{ $vendor->bank_account_no ?: '—' }}</div>
                <div class="text-secondary mt-1" style="font-size:12px; font-weight:500;">IFSC: {{ $vendor->bank_ifsc ?: '—' }}</div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="dossier-field-box">
                <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-credit-card text-secondary"></i> Payment Terms</div>
                <div class="field-value text-dark">{{ $vendor->payment_terms ?: 'Net 30 Days' }}</div>
              </div>
            </div>

            @if ($vendor->address)
              <div class="col-12">
                <div class="dossier-field-box">
                  <div class="field-label d-flex align-items-center gap-1 mb-1"><i class="bi bi-geo-alt text-secondary"></i> Registered Business Address</div>
                  <div class="text-dark" style="font-size:13.5px; font-weight:600;">{{ $vendor->address }}</div>
                </div>
              </div>
            @endif
          </div>
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Category: {{ $vendor->category ?: 'General' }}</span>
          <span>Status: {{ $meta['label'] }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Colored Icon Timeline & Empanelment Approval (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Empanelment Approval Chain</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              {{ $vendor->approvals->where('status', 'approved')->count() }} / {{ $vendor->approvals->count() }} Signed
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($vendor->approvals->sortBy('level') as $i => $approval)
              @php
                $isApproved = $approval->status === 'approved';
                $isRejected = $approval->status === 'rejected';
                $isPending = $approval->status === 'pending';
                $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending' : 'upcoming'));
                $iconBi = $isApproved ? 'bi-check-lg' : ($isRejected ? 'bi-x-lg' : ($isPending ? 'bi-clock-history' : 'bi-circle'));
                $sm = $stepMeta[$approval->status] ?? $stepMeta['pending'];
              @endphp
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $iconClass }}">
                    <i class="bi {{ $iconBi }}"></i>
                  </div>
                  @if ($i < $vendor->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">L{{ $approval->level }} · {{ \App\Support\Roles::label($approval->role_required) }}</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Clearance Endorsed</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting scrutiny</span>
                        @else
                          <span class="text-muted"><i class="bi bi-dash-circle"></i> Upcoming tier</span>
                        @endif
                      </div>
                    </div>
                    <span class="badge-soft" style="background:{{ $sm['bg'] }}; color:{{ $sm['fg'] }}; font-weight:600; font-size:11.5px; padding:3px 8px;">
                      {{ $sm['label'] }}
                    </span>
                  </div>
                  @if ($approval->comments)
                    <div class="timeline-step-comment">
                      <i class="bi bi-chat-left-quote me-1 text-muted"></i> {{ $approval->comments }}
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <!-- Decision Box -->
        @if ($pendingApproval)
          <div class="mt-3 pt-3 border-top" style="border-color:#f1f5f9 !important;">
            <div class="field-label mb-2 d-flex align-items-center gap-1" style="font-size:13px; font-weight:600;">
              <i class="bi bi-pen" style="color:var(--maroon);"></i>
              <span>Your Action (L{{ $pendingApproval->level }} — {{ \App\Support\Roles::label($pendingApproval->role_required) }})</span>
            </div>
            <form method="POST" action="{{ route('empanelment.approve', $vendor) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Comments (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve &amp; Empanel Vendor</span>
              </button>
            </form>
            <form method="POST" action="{{ route('empanelment.reject', $vendor) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Application</span>
              </button>
            </form>
          </div>
        @elseif (in_array($vendor->status, ['pending_admin_head', 'pending_l1', 'pending_l2', 'pending_cfo']))
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting assigned officer's clearance — no action pending on your account.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'empanelment', crumb: 'Procurement', title: 'Vendor Detail' });
@endsection
