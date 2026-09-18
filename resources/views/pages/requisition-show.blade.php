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
  $stepMeta = [
    'pending'  => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
    'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A']
  ];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('requisition') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to Purchase Requisitions</span>
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
      <div class="detail-avatar" style="background:linear-gradient(135deg,#c8102e,#ef4444); color:#fff; border-radius:12px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:18px;">
        <i class="bi bi-file-earmark-text"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name" style="font-size:18px; font-weight:600; color:#0f172a;">{{ $pr->pr_number }}</div>
          @if ($pr->sole_source)
            <span class="badge" style="background:#fee2e2; color:#c8102e; font-size:11.5px; font-weight:600; padding:3px 7px;">
              <i class="bi bi-shield-lock"></i> Sole Source
            </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1" style="font-size:13px; color:#64748b;">
          <span><i class="bi bi-building"></i> {{ $pr->department }}</span>
          <span><i class="bi bi-person"></i> {{ $pr->creator?->name ?? 'System' }}</span>
          @if ($pr->created_at)
            <span><i class="bi bi-clock"></i> {{ $pr->created_at->format('d M Y') }}</span>
          @endif
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
    <!-- Left Column: Line Items (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600;">
              <i class="bi bi-boxes" style="color:var(--maroon);"></i>
              <span>Requisition Line Items ({{ $pr->items->count() }})</span>
            </div>
            @if ($pr->required_by)
              <span class="badge" style="background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; font-weight:600; font-size:12px; padding:5px 10px;">
                <i class="bi bi-calendar-check"></i> Required By: {{ $pr->required_by->format('d M Y') }}
              </span>
            @endif
          </div>

          <div style="overflow-x:auto;">
            <table class="table table-hover table-sm align-middle">
              <thead>
                <tr style="font-size:12.5px; text-transform:uppercase; border-bottom:2px solid #cbd5e1; color:#334155;">
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Unit Cost</th>
                  <th>GST %</th>
                  <th class="text-end">Line Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($pr->items as $item)
                  <tr style="font-size:14px;">
                    <td class="text-dark" style="font-weight:600;">{{ $item->item_name }}</td>
                    <td><span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-size:12px; font-weight:600;">{{ $item->quantity }} {{ $item->unit }}</span></td>
                    <td style="color:#1e293b; font-weight:600;">₹{{ number_format($item->unit_cost, 2) }}</td>
                    <td style="color:#475569; font-weight:600;">{{ rtrim(rtrim(number_format($item->gst_percent, 2), '0'), '.') }}%</td>
                    <td class="text-end" style="font-weight:600; color:#0f172a;">₹{{ number_format($item->lineTotalInclGst(), 2) }}</td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr style="font-size:15px; font-weight:600; background:#f8fafc; border-top:2px solid #cbd5e1;">
                  <td colspan="4" class="text-end" style="color:#334155;">Grand Total (GST Included):</td>
                  <td class="text-end" style="color:#c8102e; font-weight:600;">₹{{ number_format($pr->estimated_total, 2) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="pt-3 mt-3 border-top text-secondary d-flex justify-content-between" style="font-size:12.5px; font-weight:600; border-color:#e2e8f0 !important;">
          <span>Creator: {{ $pr->creator?->name ?? 'System' }}</span>
          <span>Department: {{ $pr->department }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Colored Icon Timeline & Decision Form (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="h6 mb-0 d-flex align-items-center gap-2" style="font-size:15px; font-weight:600; white-space:nowrap;">
              <i class="bi bi-diagram-3" style="color:var(--maroon);"></i>
              <span>Approval Chain Timeline</span>
            </div>
            <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11.5px; padding:4px 8px; white-space:nowrap;">
              {{ $pr->approvals->where('status', 'approved')->count() }} / {{ $pr->approvals->count() }} Signed
            </span>
          </div>

          <!-- Modern Timeline with Colored Icons -->
          <div class="workflow-timeline">
            @foreach ($pr->approvals->sortBy('level') as $i => $approval)
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
                  @if ($i < $pr->approvals->count() - 1)
                    <div class="timeline-step-connector"></div>
                  @endif
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-header">
                    <div>
                      <div class="timeline-step-role">L{{ $approval->level }} · {{ \App\Support\Roles::label($approval->role_required) }}</div>
                      <div class="text-muted" style="font-size:12px; font-weight:500;">
                        @if ($isApproved)
                          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Approved</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isRejected)
                          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                          @if ($approval->acted_at) · {{ $approval->acted_at->diffForHumans() }} @endif
                        @elseif ($isPending)
                          <span class="text-warning"><i class="bi bi-hourglass-split"></i> Awaiting review &amp; signature</span>
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
            <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="mb-2">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Approval comments (optional)">
              </div>
              <button type="submit" class="btn-brand w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:38px;">
                <i class="bi bi-check2-circle"></i>
                <span>Approve Requisition</span>
              </button>
            </form>
            <form method="POST" action="{{ route('requisition.reject', $pr) }}"
                  onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2" style="font-weight:600; height:36px;">
                <i class="bi bi-x-circle"></i>
                <span>Reject Requisition</span>
              </button>
            </form>
          </div>
        @elseif (in_array($pr->status, ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4']))
          <div class="note-bar mt-3 d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
            <div class="note-dot" style="background:#f59e0b;"></div>
            <div class="note-text text-muted">Awaiting assigned approver's signature — no action needed from your account.</div>
          </div>
        @endif

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'requisition', crumb: 'Procurement', title: 'PR Detail' });
@endsection
