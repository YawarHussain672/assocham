@extends('layouts.app')

@section('title', $pr->pr_number)

@php
$statusMeta = [
'pending_l1' => ['label' => 'Pending — Operational Review (Basu)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
'pending_l2' => ['label' => 'Pending — Administrative Review (Ambika)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
'pending_l3' => ['label' => 'Pending — Finance Clearance (CFO)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
'pending_l4' => ['label' => 'Pending — Executive Sanction (SG)', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
'approved' => ['label' => 'Approved', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
'rejected' => ['label' => 'Rejected', 'bg' => '#FBE7E9', 'fg' => '#B0263A'],
'draft' => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
];
$meta = $statusMeta[$pr->status] ?? $statusMeta['draft'];
$stepMeta = [
'pending' => ['label' => 'Pending', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
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
  <div class="detail-header-card">
    <div class="d-flex align-items-center gap-3">
      <div class="detail-avatar" style="background:linear-gradient(135deg,#c8102e,#ef4444);">
        <i class="bi bi-file-earmark-text"></i>
      </div>
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="detail-name">{{ $pr->pr_number }}</div>
          @if ($pr->sole_source)
          <span class="badge" style="background:#fee2e2; color:#c8102e; font-size:11.5px; font-weight:600; padding:3px 7px;">
            <i class="bi bi-shield-lock"></i> Sole Source
          </span>
          @endif
        </div>
        <div class="detail-meta d-flex align-items-center gap-3 mt-1">
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

  <div class="row g-4">
    <!-- Left Column: Line Items (col-lg-7) -->
    <div class="col-12 col-lg-7">
      <div class="card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:32px; height:32px;">
                <i class="bi bi-boxes"></i>
              </div>
              <h6 class="mb-0 fw-bold text-dark">Requisition Line Items ({{ $pr->items->count() }})</h6>
            </div>
            @if ($pr->required_by)
            <span class="badge bg-light text-primary border px-2.5 py-1.5">
              <i class="bi bi-calendar-check me-1"></i> Required By: {{ $pr->required_by->format('d M Y') }}
            </span>
            @endif
          </div>

          <div style="overflow-x:auto;">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr style="font-size:12px; text-transform:uppercase; color:#475569;">
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
                  <td class="text-dark fw-semibold">{{ $item->item_name }}</td>
                  <td><span class="badge bg-light text-dark border px-2 py-1">{{ $item->quantity }} {{ $item->unit }}</span></td>
                  <td class="fw-semibold text-secondary">₹{{ number_format($item->unit_cost, 2) }}</td>
                  <td class="text-muted">{{ rtrim(rtrim(number_format($item->gst_percent, 2), '0'), '.') }}%</td>
                  <td class="text-end fw-bold text-dark">₹{{ number_format($item->lineTotalInclGst(), 2) }}</td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr style="font-size:15px; font-weight:600; background:#f8fafc; border-top:2px solid #e2e8f0;">
                  <td colspan="4" class="text-end text-secondary">Grand Total (GST Included):</td>
                  <td class="text-end text-primary fw-bold">₹{{ number_format($pr->estimated_total, 2) }}</td>
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

    <!-- Right Column: Vertical Connected Workflow Timeline & Decision Form (col-lg-5) -->
    <div class="col-12 col-lg-5">
      <div class="travel-timeline-card card-panel p-4 h-100 d-flex flex-column justify-content-between">
        <div>
          <!-- Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-2 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
              <!-- <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px; height:36px; background:linear-gradient(135deg, #c8102e, #ef4444); color:#fff; box-shadow:0 3px 8px rgba(200,16,46,0.25);">
                <i class="bi bi-diagram-3-fill fs-6"></i>
              </div> -->
              <div>
                <h6 class="mb-0 fw-bold text-dark">Approval Chain Timeline</h6>
                <div class="text-muted" style="font-size:11.5px;">Multi-Tier Procurement Authorization</div>
              </div>
            </div>
            @php
            $approvals = $pr->approvals->unique('level')->sortBy('level');
            $approvedCount = $approvals->where('status', 'approved')->count();
            $totalCount = $approvals->count();
            $isAllApproved = $totalCount > 0 && $approvedCount === $totalCount;
            @endphp
            <span class="badge {{ $isAllApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($pr->status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border') }} px-2.5 py-1.5 fw-semibold" style="font-size:12px;">
              @if ($isAllApproved)
              <i class="bi bi-check-circle-fill me-1"></i>All Clear ({{ $approvedCount }}/{{ $totalCount }})
              @elseif ($pr->status === 'rejected')
              <i class="bi bi-x-circle-fill me-1"></i>Rejected
              @else
              <i class="bi bi-clock-history me-1"></i>{{ $approvedCount }}/{{ $totalCount }} Endorsed
              @endif
            </span>
          </div>

          <!-- Connected Workflow Timeline -->
          <div class="workflow-timeline position-relative">

            <!-- Step 1: Requisition Lodged -->
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon approved">
                  <i class="bi bi-check-lg"></i>
                </div>
                <div class="timeline-step-connector approved"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card approved-stage">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role text-success">Requisition Initiated</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Submitted
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>{{ $pr->creator?->name ?? 'Requester' }} ({{ $pr->department }})</span>
                    <span>{{ $pr->created_at?->format('d M, H:i') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Approval Stages -->
            @php
            $stageNames = [
            1 => 'Operational Review (Basu)',
            2 => 'Administrative Compliance (Ambika)',
            3 => 'Finance Clearance (CFO)',
            4 => 'Executive Sanction (Secretary General)',
            ];
            @endphp
            @foreach ($approvals as $approval)
            @php
            $isApproved = $approval->status === 'approved';
            $isRejected = $approval->status === 'rejected';
            $isPending = $approval->status === 'pending';

            $connectorClass = $isApproved ? 'approved' : ($isPending ? 'pending dashed' : 'dashed');
            $iconClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : ($isPending ? 'pending pulse-ring' : 'upcoming'));
            $cardStageClass = $isPending ? 'active-stage' : ($isApproved ? 'approved-stage' : '');
            $stageTitle = $stageNames[$approval->level] ?? \App\Support\Roles::label($approval->role_required);
            @endphp
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon {{ $iconClass }}">
                  @if ($isApproved)
                  <i class="bi bi-check-lg"></i>
                  @elseif ($isRejected)
                  <i class="bi bi-x-lg"></i>
                  @elseif ($isPending)
                  <i class="bi bi-hourglass-split"></i>
                  @else
                  <i class="bi bi-circle"></i>
                  @endif
                </div>
                <div class="timeline-step-connector {{ $loop->last && !$pr->rfq ? '' : $connectorClass }}"></div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card {{ $cardStageClass }}">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role {{ $isApproved ? 'text-success' : ($isPending ? 'text-primary' : '') }}">
                      {{ $stageTitle }}
                    </span>
                    @if ($isApproved)
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Endorsed
                    </span>
                    @elseif ($isRejected)
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Declined
                    </span>
                    @elseif ($isPending)
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size:10px;">
                      Action Due
                    </span>
                    @else
                    <span class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size:10px;">
                      Standby
                    </span>
                    @endif
                  </div>

                  <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                    <span>
                      @if ($approval->approver)
                      {{ $approval->approver->name }}
                      @elseif ($isPending)
                      <span class="text-warning fw-semibold">Action Required</span>
                      @else
                      <span>Awaiting Stage</span>
                      @endif
                    </span>
                    @if ($approval->acted_at)
                    <span>{{ $approval->acted_at->format('d M, H:i') }}</span>
                    @endif
                  </div>

                  @if ($approval->comments)
                  <div class="timeline-quote-bubble">
                    &ldquo;{{ $approval->comments }}&rdquo;
                  </div>
                  @endif
                </div>
              </div>
            </div>
            @endforeach

            <!-- Step: RFQ Floating Handover (if approved) -->
            @if ($isAllApproved || $pr->rfq)
            <div class="timeline-step-item">
              <div class="timeline-step-rail">
                <div class="timeline-step-icon {{ $pr->rfq ? 'approved' : 'info pulse-ring' }}">
                  <i class="bi {{ $pr->rfq ? 'bi-check-all' : 'bi-broadcast' }}"></i>
                </div>
              </div>
              <div class="timeline-step-body">
                <div class="timeline-step-card {{ $pr->rfq ? 'approved-stage' : 'active-stage' }}">
                  <div class="d-flex align-items-center justify-content-between gap-1">
                    <span class="timeline-step-role {{ $pr->rfq ? 'text-success' : 'text-primary' }}">Procurement RFQ Handover</span>
                    <span class="badge {{ $pr->rfq ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-info-subtle text-info border border-info-subtle' }} py-0.5 px-1.5" style="font-size:10px;">
                      {{ $pr->rfq ? 'RFQ Floated' : 'Ready' }}
                    </span>
                  </div>
                  <div class="timeline-step-time mt-0.5">
                    @if ($pr->rfq)
                    @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'rfq'))
                    <span>Linked: <a href="{{ route('rfq.show', $pr->rfq) }}" class="text-decoration-none fw-bold" style="color:var(--maroon);">{{ $pr->rfq->rfq_number }} &rarr;</a></span>
                    @else
                    <span>Linked RFQ: <strong>{{ $pr->rfq->rfq_number }}</strong></span>
                    @endif
                    @else
                    <span>Sanctioned &bull; Ready for buyer dispatch</span>
                    @endif
                  </div>
                </div>
              </div>
            </div>
            @endif

          </div>
        </div>

        <!-- Decision Box -->
        @if ($pendingApproval)
        @php
        $currentStageTitle = $stageNames[$pendingApproval->level] ?? \App\Support\Roles::label($pendingApproval->role_required);
        @endphp
        <div class="mt-3 pt-3 border-top" style="border-color:#e2e8f0 !important;">
          <div class="p-3 rounded-3" style="background:#fffcf5; border:1px solid #fde68a; box-shadow:0 2px 8px rgba(245,158,11,0.06);">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                <!-- <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:20px; height:20px; font-size:11px;">
                  <i class="bi bi-pen"></i>
                </div> -->
                <span>Your Action Required</span>
              </div>
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:11px;">
                {{ $currentStageTitle }}
              </span>
            </div>

            <!-- <div class="text-secondary small mb-2.5" style="font-size:12px;">
              Review line items, specifications, and departmental budget commitment before authorizing.
            </div> -->

            <!-- Quick Comment Chips -->
            <!-- <div class="d-flex align-items-center flex-wrap gap-1.5 mb-2.5">
              <span class="quick-comment-chip" onclick="setPrComment('Approved as requested.')">✓ Approved as Requested</span>
              <span class="quick-comment-chip" onclick="setPrComment('Specifications and quantities verified.')">📦 Specs Verified</span>
              <span class="quick-comment-chip" onclick="setPrComment('Budget allocation cleared.')">💰 Budget Cleared</span>
            </div> -->

            <form method="POST" action="{{ route('requisition.approve', $pr) }}" class="mb-2">
              @csrf
              <!-- <div class="input-group input-group-sm mb-2">
                <span class="input-group-text bg-white"><i class="bi bi-chat-text text-secondary"></i></span>
                <input type="text" name="comments" id="prCommentsInput" class="form-control" placeholder="Approval comments or notes (optional)">
              </div> -->
              <button type="submit" class="btn btn-sm btn-success w-100 justify-content-center d-flex align-items-center gap-2 py-2 fw-semibold shadow-sm">
                <i class="bi bi-check2-circle"></i>
                <span>Approve &amp; Advance Requisition</span>
              </button>
            </form>

            <form method="POST" action="{{ route('requisition.reject', $pr) }}"
              onsubmit="return this.comments.value.trim() !== '' || (alert('A reason is required to reject.'), false);">
              @csrf
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text bg-white"><i class="bi bi-exclamation-triangle text-danger"></i></span>
                <input type="text" name="comments" class="form-control" placeholder="Reason for rejection (required)">
              </div>
              <button type="submit" class="btn btn-outline-danger btn-sm w-100 justify-content-center d-flex align-items-center gap-2 py-1.5 fw-semibold">
                <i class="bi bi-x-circle"></i>
                <span>Decline Requisition</span>
              </button>
            </form>

            <script>
              function setPrComment(text) {
                var el = document.getElementById('prCommentsInput');
                if (el) {
                  el.value = text;
                  el.focus();
                }
              }
            </script>
          </div>
        </div>
        @elseif (in_array($pr->status, ['pending_l1', 'pending_l2', 'pending_l3', 'pending_l4']))
        <div class="alert alert-warning d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-warning-subtle text-warning-emphasis">
          <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
          <div>
            <div class="fw-bold mb-0.5">Awaiting Assigned Reviewer</div>
            <div class="text-secondary" style="font-size:12px;">
              Currently queued with <strong>{{ \App\Support\Roles::label($approvals->where('status', 'pending')->first()?->role_required ?? 'Reviewing Authority') }}</strong>.
            </div>
          </div>
        </div>
        @elseif ($pr->status === 'approved')
        <div class="alert alert-success d-flex align-items-center gap-2.5 mt-3 mb-0 p-3 small rounded-3 border-0 bg-success-subtle text-success-emphasis">
          <i class="bi bi-shield-fill-check fs-4 text-success flex-shrink-0"></i>
          <div>
            <div class="fw-bold mb-0.5">Requisition Fully Approved</div>
            <div class="text-secondary" style="font-size:12px;">
              All operational, administrative, and finance sanctions have been granted.
            </div>
          </div>
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