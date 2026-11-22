@extends('layouts.app')

@section('title', 'Notifications & Action Center')

@php
  $pendingCount = count($items);
  $reviewedCount = count($reviewedItems ?? []);
@endphp

@section('content')
<div class="view-wrap" id="pageContent">

  <!-- Page Header -->
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title">Action Center &amp; Pending Approvals</h1>
    </div>
  </div>

  @if (!empty($grievanceUpdates) && $grievanceUpdates->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-chat-square-text-fill text-primary"></i>
          <h6 class="mb-0 fw-bold text-dark">Grievance Status Updates</h6>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
          {{ $grievanceUpdates->count() }} Unread {{ Str::plural('Update', $grievanceUpdates->count()) }}
        </span>
      </div>
      <div class="list-group list-group-flush">
        @foreach ($grievanceUpdates as $gUpdate)
          <div class="list-group-item px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div style="min-width:0;">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge {{ $gUpdate->status === 'resolved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }}">
                  {{ $gUpdate->actionLabel() }}
                </span>
                <span class="fw-bold text-dark" style="font-size:13.5px;">
                  {{ $gUpdate->grievance?->ticket_number }} — {{ $gUpdate->grievance?->subject }}
                </span>
              </div>
              <div class="text-dark small">{{ $gUpdate->message }}</div>
              <div class="text-muted mt-1" style="font-size:11.5px;">
                {{ $gUpdate->actor?->name ?? 'Handler' }} ({{ \App\Support\Roles::label($gUpdate->owner_role) }}) · {{ $gUpdate->created_at?->format('d M Y, H:i') }}
              </div>
            </div>
            @if ($gUpdate->grievance)
              <a href="{{ route('grievance.show', $gUpdate->grievance) }}" class="btn btn-sm btn-outline-primary fw-semibold text-nowrap">
                Track Ticket →
              </a>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" role="tablist">
    <button class="erp-switch-tab active" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending-pane" type="button" role="tab" aria-controls="pending-pane" aria-selected="true" onclick="updateActionCenterCounter({{ $pendingCount }}, 'pending action items', this)">
      Pending Decisions <span class="erp-switch-count">{{ $pendingCount }}</span>
    </button>
    <button class="erp-switch-tab" id="approved-tab" data-bs-toggle="pill" data-bs-target="#approved-pane" type="button" role="tab" aria-controls="approved-pane" aria-selected="false" onclick="updateActionCenterCounter({{ $reviewedCount }}, 'reviewed action items', this)">
      Reviewed / Approved <span class="erp-switch-count">{{ $reviewedCount }}</span>
    </button>
  </div>

  <!-- Switch View Content Panes -->
  <div class="tab-content" id="actionCenterContent">
    <!-- Pending View Pane -->
    <div class="tab-pane fade show active" id="pending-pane" role="tabpanel" aria-labelledby="pending-tab">
      <div class="erp-table-container">
        <table class="table erp-table align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-4" style="width: 25%;">REFERENCE</th>
              <th style="width: 35%;">DOCUMENT TYPE</th>
              <th style="width: 25%;">STATUS</th>
              <th class="text-end pe-4" style="width: 15%;">ACTION</th>
            </tr>
          </thead>
          <tbody id="notificationsList">
            @forelse ($items as $item)
              <tr>
                <td class="ps-4">
                  <a href="{{ $item['url'] }}" class="erp-code-link" style="font-size: 13.5px;">
                    {{ $item['number'] }}
                  </a>
                </td>
                <td>
                  <span class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $item['label'] }}</span>
                </td>
                <td>
                  <span class="erp-badge erp-badge-warning">Awaiting Decision</span>
                </td>
                <td class="text-end pe-4">
                  <div class="erp-row-actions">
                    <a href="{{ $item['url'] }}" class="erp-btn-action-review">
                      <i class="bi bi-eye"></i> Review
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                  <i class="bi bi-check-circle-fill text-success" style="font-size: 36px;"></i>
                  <div class="mt-2 fw-bold text-dark">Nothing waiting on you right now</div>
                  <div class="small text-muted mt-1">There are currently no items assigned to your queue.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approved View Pane -->
    <div class="tab-pane fade" id="approved-pane" role="tabpanel" aria-labelledby="approved-tab">
      <div class="erp-table-container">
        <table class="table erp-table align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-4" style="width: 25%;">REFERENCE</th>
              <th style="width: 30%;">DOCUMENT TYPE</th>
              <th style="width: 20%;">DECISION</th>
              <th style="width: 15%;">REVIEWED</th>
              <th class="text-end pe-4" style="width: 10%;">ACTION</th>
            </tr>
          </thead>
          <tbody id="reviewedNotificationsList">
            @forelse ($reviewedItems as $rev)
              @php
                $decLower = strtolower($rev['decision']);
                $decBadge = str_contains($decLower, 'reject')
                  ? 'erp-badge-danger'
                  : (str_contains($decLower, 'push') ? 'erp-badge-warning' : 'erp-badge-success');
              @endphp
              <tr>
                <td class="ps-4">
                  <a href="{{ $rev['url'] }}" class="erp-code-link" style="font-size: 13.5px;">
                    {{ $rev['number'] }}
                  </a>
                </td>
                <td>
                  <span class="text-dark fw-semibold" style="font-size: 13.5px;">{{ $rev['label'] }}</span>
                </td>
                <td>
                  <span class="erp-badge {{ $decBadge }}">{{ $rev['decision'] }}</span>
                </td>
                <td class="text-muted small">
                  {{ $rev['reviewed_at']?->diffForHumans() }}
                </td>
                <td class="text-end pe-4">
                  <div class="erp-row-actions">
                    <a href="{{ $rev['url'] }}" class="erp-btn-action-view">
                      <i class="bi bi-eye"></i> View
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <div class="fw-medium text-secondary">No approved items</div>
                  <div class="small text-muted mt-0.5">Items you review or approve will appear here.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
function updateActionCenterCounter(count, label, tabEl) {
  const counter = document.getElementById('actionCenterCounter');
  const labelEl = document.getElementById('actionCenterCounterLabel');
  if (counter) counter.textContent = count;
  if (labelEl) labelEl.textContent = label;

  document.querySelectorAll('.erp-switch-bar .erp-switch-tab').forEach(t => t.classList.remove('active'));
  if (tabEl) tabEl.classList.add('active');
}
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'notifications', crumb: 'System', title: 'Notifications' });
@endsection
