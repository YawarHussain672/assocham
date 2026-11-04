@extends('layouts.app')

@section('title', $rfq->rfq_number)

@php
  $statusMeta = [
    'draft'           => ['label' => 'Draft', 'bg' => '#EEF0F3', 'fg' => '#64748B'],
    'dispatched'      => ['label' => 'Dispatched to Vendors', 'bg' => '#FBF0DC', 'fg' => '#8A5A12'],
    'quotes_received' => ['label' => 'Quotes Received', 'bg' => '#E6F0FB', 'fg' => '#1E5FA8'],
    'approved'        => ['label' => 'Awarded / Completed', 'bg' => '#E6F3EC', 'fg' => '#2E7D4F'],
  ];
  $meta = $statusMeta[$rfq->status] ?? $statusMeta['draft'];
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to RFQs &amp; Quotations</span>
  </a>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <div class="detail-header-card">
    <div class="detail-avatar" style="background:linear-gradient(135deg,rgba(217,119,6,0.15),rgba(217,119,6,0.05)); color:#d97706; border:1px solid rgba(217,119,6,0.2);">
      <i class="bi bi-broadcast-pin fs-4"></i>
    </div>
    <div>
      <div class="detail-name">{{ $rfq->rfq_number }}</div>
      <div class="detail-meta d-flex align-items-center gap-2 mt-1">
        <span>Linked PR: <strong>{{ $rfq->purchaseRequisition->pr_number }}</strong></span>
        <span>•</span>
        <span>{{ $rfq->purchaseRequisition->department }}</span>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge-soft" style="background:{{ $meta['bg'] }}; color:{{ $meta['fg'] }}; font-weight:600; padding:6px 14px;">
        {{ $meta['label'] }}
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left: Items & Dispatched Vendors (col-lg-8) -->
    <div class="col-12 col-lg-8">
      <div class="d-flex flex-column gap-4">

        <!-- Requisition Items Panel -->
        <div class="card-panel p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0 d-flex align-items-center gap-2" style="font-weight:600;">
              <i class="bi bi-box-seam text-primary"></i>
              <span>Requisition Material Specifications ({{ $rfq->purchaseRequisition->items->count() }})</span>
            </h3>
            <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:500;">
              Est. Total: ₹{{ number_format($rfq->purchaseRequisition->estimatedCostInclGst(), 2) }}
            </span>
          </div>

          <div style="overflow-x:auto;">
            <table class="table table-hover align-middle">
              <thead>
                <tr class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">
                  <th>Item Name &amp; Description</th>
                  <th class="text-center">Quantity</th>
                  <th class="text-end">Est. Unit Cost</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody style="font-size:13.5px;">
                @foreach ($rfq->purchaseRequisition->items as $item)
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark" style="font-weight:600;">{{ $item->item_name }}</div>
                      @if ($item->description)
                        <div class="text-muted" style="font-size:11.5px;">{{ $item->description }}</div>
                      @endif
                    </td>
                    <td class="text-center">
                      <span class="badge" style="background:#f8fafc; color:#334155; font-weight:600;">
                        {{ $item->quantity }} {{ $item->unit }}
                      </span>
                    </td>
                    <td class="text-end">₹{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-end fw-semibold text-dark" style="font-weight:600;">
                      ₹{{ number_format($item->quantity * $item->unit_cost, 2) }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <!-- Vendor Dispatch Roster -->
        <div class="card-panel p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0 d-flex align-items-center gap-2" style="font-weight:600;">
              <i class="bi bi-shop text-primary"></i>
              <span>Invited Empanelled Vendors ({{ $rfq->rfqVendors->count() }})</span>
            </h3>
            <span class="text-muted" style="font-size:12px;">Automated procurement broadcast</span>
          </div>

          <div class="d-flex flex-column gap-2">
            @forelse ($rfq->rfqVendors as $rv)
              <div class="p-3 border rounded-3 bg-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                  <span class="stat-icon-circle" style="background:#f8fafc; color:#475569; width:34px; height:34px; font-size:13px;">
                    <i class="bi bi-building"></i>
                  </span>
                  <div>
                    <div class="fw-semibold text-dark" style="font-size:14px; font-weight:600;">{{ $rv->vendor->company_name }}</div>
                    <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size:12px;">
                      <span>Code: {{ $rv->vendor->vendor_code }}</span>
                      <span>•</span>
                      <span>{{ $rv->vendor->category ?: 'General Goods' }}</span>
                    </div>
                  </div>
                </div>

                <span class="badge-soft" style="background:{{ $rv->response_status === 'received' ? '#E6F3EC' : '#FBF0DC' }}; color:{{ $rv->response_status === 'received' ? '#2E7D4F' : '#8A5A12' }}; font-weight:600;">
                  <i class="bi {{ $rv->response_status === 'received' ? 'bi-check2-circle' : 'bi-hourglass' }} me-1"></i>
                  {{ ucfirst($rv->response_status) }}
                </span>
              </div>
            @empty
              <div class="p-4 text-center text-muted border rounded-3" style="background:#f8fafc;">
                No vendors dispatched.
              </div>
            @endforelse
          </div>

          <!-- Action Buttons -->
          @if ($canManage && $rfq->status === 'dispatched')
            <form method="POST" action="{{ route('rfq.quotes-received', $rfq) }}" class="mt-4 pt-3 border-top">
              @csrf
              <button type="submit" class="btn-brand w-100 d-inline-flex align-items-center justify-content-center gap-2" style="font-weight:600; padding:10px 20px;">
                <i class="bi bi-check2-all"></i>
                <span>Mark Quotes Received</span>
              </button>
              <div class="text-muted text-center mt-2" style="font-size:12px;">Once vendors deliver proposals, mark this RFQ received to unlock the Cost Comparative Statement (CCS).</div>
            </form>
          @elseif ($rfq->status === 'quotes_received' && ! $rfq->costComparativeStatement)
            @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'ccs') && auth()->user()->role === \App\Support\Roles::BUYER)
              <div class="mt-4 pt-3 border-top">
                <a href="{{ route('ccs.create', $rfq) }}" class="btn-brand w-100 d-inline-flex align-items-center justify-content-center gap-2" style="font-weight:600; padding:10px 20px; text-decoration:none;">
                  <i class="bi bi-file-earmark-spreadsheet"></i>
                  <span>Prepare Cost Comparative Statement (CCS)</span>
                </a>
              </div>
            @endif
          @elseif ($rfq->costComparativeStatement)
            @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'ccs'))
              <div class="mt-4 pt-3 border-top">
                <a href="{{ route('ccs.show', $rfq->costComparativeStatement) }}" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2" style="font-weight:600; padding:10px 20px; text-decoration:none;">
                  <i class="bi bi-eye"></i>
                  <span>View Generated Comparative Statement</span>
                </a>
              </div>
            @endif
          @endif
        </div>

      </div>
    </div>

    <!-- Right: Vertical Connected Lifecycle Timeline & Sourcing Policy (col-lg-4) -->
    <div class="col-12 col-lg-4">
      <div class="d-flex flex-column gap-3">

        <div class="travel-timeline-card">
          <div class="travel-timeline-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <div class="icon-bubble" style="background:#fef2f2; color:var(--maroon); width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px;">
                  <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                  <h6 class="mb-0 fw-bold" style="font-size:14.5px; color:#0f172a;">Procurement Rail</h6>
                  <div class="text-muted" style="font-size:11.5px;">Bidding &bull; Market Sourcing</div>
                </div>
              </div>
              <span class="badge" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-weight:600; font-size:11px; padding:4px 9px;">
                {{ $meta['label'] }}
              </span>
            </div>

            <!-- Micro Lifecycle Progress Bar -->
            @php
              $quotesReceived = in_array($rfq->status, ['quotes_received', 'approved']);
              $ccsExists = (bool) $rfq->costComparativeStatement;
              $isAwarded = $rfq->status === 'approved';
            @endphp
            <div class="travel-lifecycle-bar" title="Procurement Progress">
              <div class="bar-segment done" title="1. PR Sanction"></div>
              <div class="bar-segment done" title="2. Dispatched"></div>
              <div class="bar-segment {{ $quotesReceived ? 'done' : 'active' }}" title="3. Quotes"></div>
              <div class="bar-segment {{ $ccsExists ? 'done' : '' }}" title="4. CCS Award"></div>
            </div>
          </div>

          <div class="travel-timeline-body">
            <div class="workflow-timeline position-relative">

              <!-- Step 1: PR Cleared -->
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon approved">
                    <i class="bi bi-file-earmark-check"></i>
                  </div>
                  <div class="timeline-step-connector approved"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card approved-stage">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role text-success">Requisition Sanctioned</span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Approved
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>{{ $rfq->purchaseRequisition->pr_number }} ({{ $rfq->purchaseRequisition->department }})</span>
                      <span>₹{{ number_format($rfq->purchaseRequisition->estimatedCostInclGst(), 2) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 2: RFQ Dispatched -->
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon approved">
                    <i class="bi bi-broadcast"></i>
                  </div>
                  <div class="timeline-step-connector {{ $quotesReceived ? 'approved' : '' }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card approved-stage">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role text-success">Vendor Broadcast</span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size:10px;">
                        Dispatched
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>{{ $rfq->rfqVendors->count() }} Vendors Invited</span>
                      <span>{{ $rfq->created_at?->format('d M, H:i') }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 3: Quotes Received -->
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $quotesReceived ? 'approved' : 'pending pulse-ring' }}">
                    @if ($quotesReceived)
                      <i class="bi bi-check-lg"></i>
                    @else
                      <i class="bi bi-hourglass-split"></i>
                    @endif
                  </div>
                  <div class="timeline-step-connector {{ $ccsExists ? 'approved' : '' }}"></div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $quotesReceived ? 'approved-stage' : 'active-stage' }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $quotesReceived ? 'text-success' : 'text-primary' }}">Commercial Quotes</span>
                      <span class="badge {{ $quotesReceived ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} py-0.5 px-1.5" style="font-size:10px;">
                        {{ $quotesReceived ? 'Received' : 'In Progress' }}
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      <span>{{ $quotesReceived ? 'All bids tabulated' : 'Awaiting submissions' }}</span>
                      <span class="{{ $quotesReceived ? 'text-success' : 'text-warning' }}">{{ $quotesReceived ? 'Tabulated' : 'Collecting' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 4: CCS & Award -->
              <div class="timeline-step-item">
                <div class="timeline-step-rail">
                  <div class="timeline-step-icon {{ $ccsExists ? 'approved' : 'upcoming' }}">
                    @if ($ccsExists)
                      <i class="bi bi-award"></i>
                    @else
                      <i class="bi bi-circle"></i>
                    @endif
                  </div>
                </div>
                <div class="timeline-step-body">
                  <div class="timeline-step-card {{ $ccsExists ? 'approved-stage' : '' }}">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                      <span class="timeline-step-role {{ $ccsExists ? 'text-success' : 'text-muted' }}">Comparative Statement (CCS)</span>
                      <span class="badge {{ $ccsExists ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }} py-0.5 px-1.5" style="font-size:10px;">
                        {{ $ccsExists ? 'Compiled' : 'Upcoming' }}
                      </span>
                    </div>
                    <div class="timeline-step-time mt-0.5 d-flex align-items-center justify-content-between">
                      @if ($ccsExists)
                        @if (\App\Support\Roles::canAccessModule(auth()->user()->role, 'ccs'))
                          <a href="{{ route('ccs.show', $rfq->costComparativeStatement) }}" class="text-decoration-none fw-bold text-success">
                            {{ $rfq->costComparativeStatement->ccs_number }} &rarr;
                          </a>
                        @else
                          <span class="fw-bold text-success">{{ $rfq->costComparativeStatement->ccs_number }}</span>
                        @endif
                        <span class="text-success">Ready</span>
                      @else
                        <span>Pending bid tabulation</span>
                        <span>&mdash;</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="admin-sidebar-card">
          <div class="admin-sidebar-header">
            <i class="bi bi-shield-check" style="color:var(--maroon);"></i>
            <span>Sourcing Governance</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-3" style="font-size:12.5px; color:#475569;">
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>All quotes must feature itemized GST breakdown.</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check-circle-fill text-success mt-1"></i>
              <span>Lowest conforming bid is automatically highlighted in the CCS sheet.</span>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'RFQ Detail' });
@endsection

