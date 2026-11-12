@extends('layouts.app')

@section('title', 'New RFQ')

@section('content')
<div class="view-wrap" id="pageContent">

  <a href="{{ route('rfq') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to RFQs &amp; Quotations</span>
  </a>

  @if ($errors->any())
  <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <ul class="mb-0 ps-2">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <div class="row g-4">
    <!-- Left: RFQ Dispatch Form (col-lg-8) -->
    <div class="col-12 col-lg-12">
      <div class="card-panel p-4">
        <div class="form-icon-header">
          <!-- <div class="form-icon-circle" style="background:linear-gradient(135deg,rgba(217,119,6,0.12),rgba(217,119,6,0.04)); color:#d97706; border-color:rgba(217,119,6,0.2);">
            <i class="bi bi-broadcast-pin"></i>
          </div> -->
          <div>
            <h2 class="h5 mb-1" style="font-weight:600;">Dispatch Request for Quotation (RFQ)</h2>
            <!-- <div class="text-muted" style="font-size:13px;">Float requirements to verified empanelled vendors based on an approved Requisition.</div> -->
          </div>
        </div>

        @if ($purchaseRequisitions->isEmpty())
        <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
          <!-- <i class="bi bi-inbox text-muted" style="font-size:36px;"></i> -->
          <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Approved Requisitions Available</div>
          <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
            An RFQ can only be floated against a Purchase Requisition that has cleared its full departmental, financial, and executive approval chain.
          </div>
          <a href="{{ route('requisition') }}" class="btn-brand d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
            <i class="bi bi-file-earmark-text"></i>
            <span>View Requisitions</span>
          </a>
        </div>
        @else
        <form method="POST" action="{{ route('rfq.store') }}" enctype="multipart/form-data" class="mt-3">
          @csrf

          <div class="mb-3">
            <label class="form-label"> Approved Purchase Requisition *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
              <select name="purchase_requisition_id" id="prSelect" class="form-select" required onchange="handlePrChange(this)">
                <option value="">Select cleared PR reference…</option>
                @foreach ($purchaseRequisitions as $pr)
                <option value="{{ $pr->id }}" 
                        data-department="{{ $pr->department }}"
                        data-title="{{ $pr->title }}"
                        data-required-by="{{ optional($pr->required_by)->format('Y-m-d') ?? '' }}"
                        {{ old('purchase_requisition_id') == $pr->id ? 'selected' : '' }}>
                  {{ $pr->pr_number }} @if($pr->title)— {{ $pr->title }} @endif— {{ $pr->department }} (Est: ₹{{ number_format($pr->estimatedCostInclGst(), 2) }})
                </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Sector / Domain *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                <input type="text" name="sector" id="sectorInput" class="form-control" placeholder="e.g. IT & Telecom, Infrastructure" value="{{ old('sector') }}" required maxlength="100">
              </div>
              <div class="form-text text-muted" style="font-size:12px;">Pre-filled from Requisition's department.</div>
            </div>

            <div class="col-12 col-md-5">
              <label class="form-label">Unique Project Name *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-folder2-open"></i></span>
                <input type="text" name="project_name" id="projectNameInput" class="form-control @error('project_name') is-invalid @enderror" placeholder="e.g. Annual IT Infrastructure Upgrade FY26-27" value="{{ old('project_name') }}" required maxlength="255">
              </div>
              @error('project_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @else
                <div class="form-text text-muted" style="font-size:12px;">Must be unique across all RFQs.</div>
              @enderror
            </div>

            <div class="col-12 col-md-3">
              <label class="form-label">Quotation Due Date</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="date" name="due_date" id="dueDateInput" class="form-control" value="{{ old('due_date') }}">
              </div>
              <div class="form-text text-muted" style="font-size:12px;">Pre-filled from PR required date.</div>
            </div>
          </div>

          <!-- Attachments / Supporting Specs Upload -->
          <div class="mb-3">
            <label class="form-label d-flex align-items-center justify-content-between">
              <span>Supporting Documents &amp; Specifications (Optional)</span>
              <span class="text-muted small">Max 10MB each (PDF, DOC, XLS, ZIP, Images)</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-paperclip"></i></span>
              <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
            </div>
            <div class="form-text text-muted" style="font-size:12px;">Selected files will be automatically attached to the dispatch email sent to each vendor.</div>
          </div>

          <div class="mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div>
                <label class="form-label mb-0 fw-semibold text-dark">Dispatch To Empanelled Vendors *</label>
                <div class="text-muted" style="font-size:12px;">Minimum 3 vendors recommended for open bid</div>
              </div>

              <!-- Sector / Category Filter for Vendors -->
              @php
                $sectors = $vendors->pluck('category')->filter()->unique()->sort();
              @endphp
              @if ($sectors->isNotEmpty())
                <div class="d-flex align-items-center gap-2">
                  <label for="vendorSectorFilter" class="form-label mb-0 text-muted small fw-medium text-nowrap">Filter by Sector:</label>
                  <select id="vendorSectorFilter" class="form-select form-select-sm bg-white" style="width: auto; min-width: 170px;" onchange="filterVendorsBySector(this.value)">
                    <option value="all">All Sectors ({{ $vendors->count() }})</option>
                    @foreach ($sectors as $sector)
                      <option value="{{ strtolower($sector) }}">{{ $sector }} ({{ $vendors->where('category', $sector)->count() }})</option>
                    @endforeach
                  </select>
                </div>
              @endif
            </div>

            @if ($vendors->isEmpty())
            <div class="p-3 rounded-3" style="background:#fffbeb; border:1px solid #fef3c7;">
              <div class="d-flex align-items-center gap-2 text-warning" style="font-size:13px; font-weight:600;">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>No empanelled vendors currently available in the directory.</span>
              </div>
              <div class="text-muted mt-1" style="font-size:12px;">Vendors must clear the compliance onboarding chain before receiving quotation requests.</div>
            </div>
            @else
            <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-light rounded-2 border">
              <div class="form-check d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input mt-0" type="checkbox" id="selectAllVendors" onchange="toggleSelectAllVendors(this)">
                <label class="form-check-label fw-semibold text-dark" for="selectAllVendors" style="font-size: 13px; cursor: pointer;">
                  Select All Visible Vendors
                </label>
              </div>
              <span class="text-muted small" id="selectedVendorCountBadge">
                <span id="selectedVendorCount">0</span> of <span id="visibleVendorCount">{{ $vendors->count() }}</span> selected
              </span>
            </div>

            <div class="border rounded-3 p-3" style="max-height:280px; overflow-y:auto; background:#f8fafc;">
              <div class="d-flex flex-column gap-2" id="vendorListContainer">
                @foreach ($vendors as $vendor)
                <div class="p-2 rounded-2 bg-white border d-flex align-items-center justify-content-between rfq-vendor-item" data-sector="{{ strtolower($vendor->category ?: 'general') }}">
                  <div class="form-check d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input mt-0 vendor-checkbox" type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" id="vendor{{ $vendor->id }}"
                      onchange="updateSelectedVendorCount()"
                      {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }}>
                    <label class="form-check-label d-flex align-items-center gap-2" for="vendor{{ $vendor->id }}" style="font-size:13.5px; cursor:pointer; font-weight:600;">
                      <span>{{ $vendor->company_name }}</span>
                      <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:500;">
                        {{ $vendor->vendor_code }}
                      </span>
                    </label>
                  </div>
                  <span class="badge bg-light text-secondary border fw-medium" style="font-size:11.5px;">
                    <i class="bi bi-tag me-1"></i>{{ $vendor->category ?: 'General Goods' }}
                  </span>
                </div>
                @endforeach
                <div id="noVendorMatchAlert" class="text-center text-muted py-3 d-none">
                  <i class="bi bi-filter-circle fs-4 d-block mb-1 text-secondary"></i>
                  No empanelled vendors match the selected sector.
                </div>
              </div>
            </div>
            @endif
          </div>

          <div class="pt-4 border-top mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('rfq') }}" class="btn btn-outline-secondary" style="font-weight:600;">Cancel</a>
            <button type="submit" class="btn-brand d-inline-flex align-items-center gap-2" style="font-weight:600; padding:10px 20px;" {{ $vendors->isEmpty() ? 'disabled' : '' }}>
              <i class="bi bi-send"></i>
              <span>Dispatch Quotation Request</span>
            </button>
          </div>
        </form>
        @endif
      </div>
    </div>

    <!-- Right: Policy & Sourcing Guide (col-lg-4) -->

  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'rfq', crumb: 'Procurement', title: 'New RFQ' });

function handlePrChange(select) {
  const selectedOption = select.options[select.selectedIndex];
  if (!selectedOption || !selectedOption.value) return;

  const dept = selectedOption.getAttribute('data-department') || '';
  const title = selectedOption.getAttribute('data-title') || '';
  const reqBy = selectedOption.getAttribute('data-required-by') || '';
  const sectorInput = document.getElementById('sectorInput');
  const projectInput = document.getElementById('projectNameInput');
  const dueDateInput = document.getElementById('dueDateInput');

  if (sectorInput && !sectorInput.value) {
    sectorInput.value = dept;
  }
  if (projectInput && !projectInput.value) {
    projectInput.value = title;
  }
  if (dueDateInput && !dueDateInput.value && reqBy) {
    dueDateInput.value = reqBy;
  }
}

function filterVendorsBySector(sector) {
  const normalizedSector = (sector || 'all').toLowerCase().trim();
  const items = document.querySelectorAll('.rfq-vendor-item');
  let visibleCount = 0;

  items.forEach(item => {
    const itemSector = (item.getAttribute('data-sector') || '').toLowerCase().trim();
    if (normalizedSector === 'all' || itemSector === normalizedSector) {
      item.classList.remove('d-none');
      item.classList.add('d-flex');
      visibleCount++;
    } else {
      item.classList.remove('d-flex');
      item.classList.add('d-none');
    }
  });

  const noMatchAlert = document.getElementById('noVendorMatchAlert');
  if (noMatchAlert) {
    noMatchAlert.classList.toggle('d-none', visibleCount > 0);
  }

  const visibleCountEl = document.getElementById('visibleVendorCount');
  if (visibleCountEl) {
    visibleCountEl.textContent = visibleCount;
  }

  updateSelectedVendorCount();
}

function toggleSelectAllVendors(selectAllCheckbox) {
  const isChecked = selectAllCheckbox.checked;
  const visibleItems = document.querySelectorAll('.rfq-vendor-item:not(.d-none)');

  visibleItems.forEach(item => {
    const cb = item.querySelector('.vendor-checkbox');
    if (cb) {
      cb.checked = isChecked;
    }
  });

  updateSelectedVendorCount();
}

function updateSelectedVendorCount() {
  const visibleItems = document.querySelectorAll('.rfq-vendor-item:not(.d-none)');
  let selectedVisible = 0;

  visibleItems.forEach(item => {
    const cb = item.querySelector('.vendor-checkbox');
    if (cb && cb.checked) {
      selectedVisible++;
    }
  });

  const selectedCountEl = document.getElementById('selectedVendorCount');
  if (selectedCountEl) {
    selectedCountEl.textContent = selectedVisible;
  }

  const selectAllCheckbox = document.getElementById('selectAllVendors');
  if (selectAllCheckbox) {
    if (visibleItems.length === 0) {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
    } else if (selectedVisible === visibleItems.length) {
      selectAllCheckbox.checked = true;
      selectAllCheckbox.indeterminate = false;
    } else if (selectedVisible > 0) {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = true;
    } else {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
    }
  }
}

// Initial count on page load
document.addEventListener('DOMContentLoaded', () => {
  updateSelectedVendorCount();
});
@endsection