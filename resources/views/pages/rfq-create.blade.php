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
      <div class="card-panel">
        <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="vendor-form-icon-box">
              <i class="bi bi-send"></i>
            </div>
            <div>
              <h1 class="h5 mb-0 fw-bold text-dark">Dispatch Request for Quotation (RFQ)</h1>
            </div>
          </div>
        </div>

        @if ($purchaseRequisitions->isEmpty())
        <div class="p-4 rounded-3 text-center my-3" style="background:#f8fafc; border:1px dashed #cbd5e1;">
          <div class="mt-2 fw-semibold text-dark" style="font-size:14.5px; font-weight:600;">No Approved Requisitions Available</div>
          <div class="text-muted mt-1" style="font-size:13px; max-width:480px; margin:0 auto;">
            An RFQ can only be floated against a Purchase Requisition that has cleared its full departmental, financial, and executive approval chain.
          </div>
          @if (\App\Support\Roles::canAccessModuleForUser(auth()->user(), 'requisition'))
            <a href="{{ route('requisition') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600; text-decoration:none;">
              <i class="bi bi-file-earmark-text"></i>
              <span>View Requisitions</span>
            </a>
          @else
            <a href="{{ route('rfq') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mt-3" style="font-weight:600;">
              <i class="bi bi-arrow-left"></i>
              <span>Back to RFQs</span>
            </a>
          @endif
        </div>
        @else
        <form method="POST" action="{{ route('rfq.store') }}" enctype="multipart/form-data" class="mt-3">
          @csrf

          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Requisition Anchor &amp; Project Scope</h2>
            </div>

            <div class="mb-3">
              <label class="vendor-form-label">Approved Purchase Requisition <span class="text-danger">*</span></label>
              <select name="purchase_requisition_id" id="prSelect" class="form-select vendor-form-control" required onchange="handlePrChange(this)">
                <option value="">Select cleared PR reference…</option>
                @foreach ($purchaseRequisitions as $pr)
                @php
                  $prProjectName = $pr->projectBudget?->project_name ?: ($pr->event_name ?: '');
                @endphp
                <option value="{{ $pr->id }}" 
                        data-department="{{ $pr->department }}"
                        data-title="{{ $pr->title }}"
                        data-project="{{ $prProjectName }}"
                        data-required-by="{{ optional($pr->required_by)->format('Y-m-d') ?? '' }}"
                        {{ old('purchase_requisition_id') == $pr->id ? 'selected' : '' }}>
                  {{ $pr->pr_number }} @if($prProjectName)— {{ $prProjectName }} @elseif($pr->title)— {{ $pr->title }} @endif— {{ $pr->department }} (Est: ₹{{ number_format($pr->estimatedCostInclGst(), 2) }})
                </option>
                @endforeach
              </select>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-md-4">
                <label class="vendor-form-label">Sector / Domain <span class="text-danger">*</span></label>
                <input type="text" name="sector" id="sectorInput" class="form-control vendor-form-control" placeholder="e.g. IT & Telecom, Infrastructure" value="{{ old('sector') }}" required maxlength="100">
                <div class="text-muted mt-1" style="font-size:12px;">Pre-filled from Requisition's department.</div>
              </div>

              <div class="col-12 col-md-5">
                <label class="vendor-form-label">Unique Project Name <span class="text-danger">*</span></label>
                <input type="text" name="project_name" id="projectNameInput" class="form-control vendor-form-control @error('project_name') is-invalid @enderror" placeholder="e.g. Annual IT Infrastructure Upgrade FY26-27" value="{{ old('project_name') }}" required maxlength="255">
                @error('project_name')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @else
                  <div class="text-muted mt-1" style="font-size:12px;">Must be unique across all RFQs.</div>
                @enderror
              </div>

              <div class="col-12 col-md-3">
                <label class="vendor-form-label">Quotation Due Date</label>
                <input type="date" name="due_date" id="dueDateInput" class="form-control vendor-form-control" value="{{ old('due_date') }}">
                <div class="text-muted mt-1" style="font-size:12px;">Pre-filled from PR required date.</div>
              </div>
            </div>

            <!-- Attachments / Supporting Specs Upload using standard vendor-upload-card -->
            <div class="col-12 mt-3">
              <div class="vendor-upload-card">
                <div class="vendor-upload-title">
                  <i class="bi bi-paperclip text-primary fs-5"></i>
                  <span>Supporting Documents &amp; Specifications (Optional)</span>
                </div>
                <div class="vendor-upload-hint">Selected specification sheets, SOWs, or drawings will be automatically attached to dispatch emails sent to each vendor. Max 10MB each (PDF, DOC, XLS, ZIP, Images).</div>
                <input type="file" name="attachments[]" class="form-control vendor-upload-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
              </div>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
              <div class="vendor-form-icon-box">
                <i class="bi bi-people"></i>
              </div>
              <h2 class="vendor-form-section-title mb-0">Empanelled Vendor Selection</h2>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div>
                <label class="form-label mb-0 fw-semibold text-dark">Dispatch To Empanelled Vendors *</label>
                <div class="text-muted" style="font-size:12px;">Select vendors to float this request for quote</div>
              </div>

              <!-- Minimal Quick Action Suggestion Buttons -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 12px; border-radius: 6px;" onclick="selectTopRatedVendors(4.0)" title="Select all vendors with rating 4.0 or above">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>Select Top Rated (★ 4.0+)</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 12px; border-radius: 6px;" onclick="selectTopNVendors(3)" title="Select the top 3 highest rated vendors">
                  <i class="bi bi-trophy text-primary"></i>
                  <span>Select Top 3 Reviewed</span>
                </button>
                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none fw-semibold" style="font-size: 12px;" onclick="clearVendorSelection()">
                  Clear
                </button>
              </div>
            </div>

            <!-- Minimal Review Suggestion Strip (Compact & Clean) -->
            @if(isset($suggestedVendors) && $suggestedVendors->isNotEmpty())
            <div class="d-flex align-items-center gap-2 mb-2.5 px-3 py-1.5 bg-light rounded-2 border" style="font-size: 12px;">
              <div class="d-flex align-items-center gap-1.5 text-muted fw-semibold text-nowrap" style="font-size: 11.5px;">
                <i class="bi bi-stars text-warning"></i>
                <span>Vendor Suggestions (Review &amp; Rating Wise):</span>
              </div>
              <span class="text-muted small d-none d-lg-inline" style="font-size: 11px;">Top Recommended Partners:</span>
              <div class="d-flex flex-wrap gap-1.5 align-items-center flex-grow-1" id="suggestedChipsContainer">
                @foreach($suggestedVendors as $sv)
                @php
                  $svRating = $sv->average_rating ?? (float)($sv->rating ?? 0);
                @endphp
                <button type="button" 
                        class="btn btn-sm btn-white bg-white border py-0 px-2 d-inline-flex align-items-center gap-1.5 vendor-suggest-chip" 
                        id="suggestChip{{ $sv->id }}"
                        data-vendor-id="{{ $sv->id }}"
                        data-sector="{{ strtolower($sv->category ?: 'general') }}"
                        data-rating="{{ $svRating }}"
                        onclick="toggleVendorSuggestionChip({{ $sv->id }})"
                        style="font-size: 11.5px; height: 26px; border-radius: 4px;">
                  <i class="bi bi-check-circle-fill text-success d-none chip-check-icon"></i>
                  <i class="bi bi-plus text-muted chip-plus-icon"></i>
                  <span class="fw-medium text-dark">{{ $sv->company_name }}</span>
                  <span class="text-warning fw-bold" style="font-size: 11px;">★ {{ number_format($svRating, 1) }}</span>
                </button>
                @endforeach
              </div>
            </div>
            @endif

            <!-- Dynamic PR Sector Suggestion Strip (if sector matches) -->
            <div id="prSectorSuggestionAlert" class="mb-2.5 px-3 py-1.5 rounded-2 bg-light border d-none">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 12px;">
                <div class="d-flex align-items-center gap-1.5 text-dark">
                  <i class="bi bi-info-circle text-primary"></i>
                  <span id="prSectorSuggestionText">Sector match found: <strong><span id="prSectorName"></span></strong>.</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 11px; height: 22px;" onclick="selectTopSectorVendors()">
                  Select Sector Suggestions
                </button>
              </div>
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
            <!-- Minimal Toolbar: Select All Checkbox + Filter Dropdowns in One Row -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 bg-light rounded-top-2 border border-bottom-0">
              <div class="form-check d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input mt-0" type="checkbox" id="selectAllVendors" onchange="toggleSelectAllVendors(this)">
                <label class="form-check-label fw-semibold text-dark" for="selectAllVendors" style="font-size: 13px; cursor: pointer;">
                  Select All Visible Vendors
                </label>
                <span class="text-muted small ms-1" id="selectedVendorCountBadge">
                  (<span id="selectedVendorCount">0</span> of <span id="visibleVendorCount">{{ $vendors->count() }}</span> selected)
                </span>
              </div>

              <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <div class="position-relative" style="min-width: 170px;">
                  <input type="text" id="vendorSearchInput" class="form-control form-control-sm pe-4 bg-white border" placeholder="Search vendor name, code..." style="font-size: 12px; height: 31px;" oninput="applyVendorFilters()">
                  <i class="bi bi-search position-absolute text-muted" style="right: 8px; top: 7px; font-size: 11px;"></i>
                </div>

                @php
                  $sectors = $vendors->pluck('category')->filter()->unique()->sort();
                @endphp
                @if ($sectors->isNotEmpty())
                  <select id="vendorSectorFilter" class="form-select form-select-sm bg-white border" style="width: auto; min-width: 130px; font-size: 12px;" onchange="applyVendorFilters()">
                    <option value="all">All Sectors ({{ $vendors->count() }})</option>
                    @foreach ($sectors as $sector)
                      <option value="{{ strtolower($sector) }}">{{ $sector }} ({{ $vendors->where('category', $sector)->count() }})</option>
                    @endforeach
                  </select>
                @endif

                <select id="vendorRatingFilter" class="form-select form-select-sm bg-white border" style="width: auto; min-width: 120px; font-size: 12px;" onchange="applyVendorFilters()">
                  <option value="all">All Ratings</option>
                  <option value="top">★ 4.5+ (Top Rated)</option>
                  <option value="high">★ 4.0+ (Recommended)</option>
                  <option value="reviewed">Has Reviews</option>
                </select>

                <select id="vendorSortBy" class="form-select form-select-sm bg-white border" style="width: auto; min-width: 120px; font-size: 12px;" onchange="sortVendorList(this.value)">
                  <option value="rating_desc">Highest Rated</option>
                  <option value="orders_desc">Most Orders</option>
                  <option value="alpha">Alphabetical</option>
                </select>
              </div>
            </div>

            <!-- Sleek, Minimal Vendor List (Table-like rows) -->
            <div class="border rounded-bottom-2 bg-white" style="max-height: 380px; overflow-y: auto;">
              <div class="d-flex flex-column" id="vendorListContainer">
                @foreach ($vendors as $vendor)
                @php
                  $vRating = $vendor->average_rating ?? (float)($vendor->rating ?? 0);
                  $vReviewsCount = $vendor->reviews_count ?? ($vendor->relationLoaded('evaluations') ? $vendor->evaluations->count() : 0);
                  $vOrdersCount = $vendor->total_orders_count ?? 0;
                  $latestComment = $vendor->latest_review_comment;
                @endphp
                <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between rfq-vendor-item" 
                     id="vendorRow{{ $vendor->id }}"
                     data-vendor-id="{{ $vendor->id }}"
                     data-sector="{{ strtolower($vendor->category ?: 'general') }}"
                     data-rating="{{ $vRating }}"
                     data-reviews-count="{{ $vReviewsCount }}"
                     data-orders-count="{{ $vOrdersCount }}"
                     data-name="{{ strtolower($vendor->company_name) }}"
                     style="cursor: pointer; transition: background-color 0.15s ease;"
                     onclick="handleRowClick(event, {{ $vendor->id }})">

                  <!-- Left: Checkbox, Name, Code, Badges -->
                  <div class="d-flex align-items-center gap-2.5">
                    <input class="form-check-input mt-0 vendor-checkbox" 
                           type="checkbox" 
                           name="vendor_ids[]" 
                           value="{{ $vendor->id }}" 
                           id="vendor{{ $vendor->id }}"
                           onchange="updateSelectedVendorCount()"
                           onclick="event.stopPropagation()"
                           {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }}>
                    <label class="form-check-label d-flex align-items-center gap-2 mb-0" for="vendor{{ $vendor->id }}" style="cursor: pointer;" onclick="event.stopPropagation()">
                      <span class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $vendor->company_name }}</span>
                      <span class="text-muted" style="font-size: 11.5px;">{{ $vendor->vendor_code }}</span>
                      @if ($vRating >= 4.5)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size: 10px; font-weight: 600;">Top Rated</span>
                      @elseif ($vRating >= 4.0)
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-0.5 px-1.5" style="font-size: 10px; font-weight: 600;">Recommended</span>
                      @endif
                    </label>
                  </div>

                  <!-- Right: Sector, Rating, Reviews, Orders -->
                  <div class="d-flex align-items-center gap-3 ms-auto">
                    @if($latestComment)
                    <span class="text-muted small fst-italic text-truncate d-none d-xl-inline-block" style="max-width: 200px; font-size: 11.5px;" title="{{ $latestComment }}">
                      "{{ Str::limit($latestComment, 30) }}"
                    </span>
                    @endif

                    <span class="badge bg-light text-secondary border fw-normal" style="font-size: 11.5px;">
                      {{ $vendor->category ?: 'General' }}
                    </span>

                    <div class="d-flex align-items-center gap-1.5 text-nowrap" style="min-width: 105px; justify-content: flex-end;">
                      @if ($vRating > 0)
                        <span class="fw-bold text-dark" style="font-size: 12.5px;">
                          <i class="bi bi-star-fill text-warning me-0.5" style="font-size: 11px;"></i>{{ number_format($vRating, 1) }}
                        </span>
                      @else
                        <span class="text-muted small" style="font-size: 11.5px;">Unrated</span>
                      @endif

                      @if ($vOrdersCount > 0)
                        <span class="text-muted small" style="font-size: 11.5px;">&bull; {{ $vOrdersCount }} orders</span>
                      @elseif ($vReviewsCount > 0)
                        <span class="text-muted small" style="font-size: 11.5px;">&bull; {{ $vReviewsCount }} rev</span>
                      @endif
                    </div>
                  </div>
                </div>
                @endforeach
                <div id="noVendorMatchAlert" class="text-center text-muted py-4 d-none">
                  <i class="bi bi-filter-circle fs-4 d-block mb-1 text-secondary"></i>
                  No empanelled vendors match the selected filter criteria.
                </div>
              </div>
            </div>
            @endif
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
            <a href="{{ route('rfq') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;" {{ $vendors->isEmpty() ? 'disabled' : '' }}>
              Dispatch Quotation Request
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
  if (!selectedOption || !selectedOption.value) {
    const prAlert = document.getElementById('prSectorSuggestionAlert');
    if (prAlert) prAlert.classList.add('d-none');
    return;
  }

  const dept = selectedOption.getAttribute('data-department') || '';
  const title = selectedOption.getAttribute('data-title') || '';
  const project = selectedOption.getAttribute('data-project') || '';
  const reqBy = selectedOption.getAttribute('data-required-by') || '';
  const sectorInput = document.getElementById('sectorInput');
  const projectInput = document.getElementById('projectNameInput');
  const dueDateInput = document.getElementById('dueDateInput');

  if (sectorInput && !sectorInput.value) {
    sectorInput.value = dept;
  }
  if (projectInput && !projectInput.value) {
    projectInput.value = project || title;
  }
  if (dueDateInput && !dueDateInput.value && reqBy) {
    dueDateInput.value = reqBy;
  }

  // Update dynamic PR sector suggestion alert
  const prAlert = document.getElementById('prSectorSuggestionAlert');
  const prSectorName = document.getElementById('prSectorName');
  const prText = document.getElementById('prSectorSuggestionText');
  
  if (prAlert && dept) {
    const normalizedDept = dept.toLowerCase().trim();
    const matchingItems = Array.from(document.querySelectorAll('.rfq-vendor-item')).filter(item => {
      const itemSector = (item.getAttribute('data-sector') || '').toLowerCase().trim();
      return itemSector === normalizedDept || itemSector.includes(normalizedDept) || normalizedDept.includes(itemSector);
    });

    if (matchingItems.length > 0) {
      if (prSectorName) prSectorName.textContent = dept;
      if (prText) {
        prText.innerHTML = `Sector match for <strong>${dept}</strong>: Found <strong>${matchingItems.length}</strong> empanelled partner(s) in directory.`;
      }
      prAlert.classList.remove('d-none');
    } else {
      prAlert.classList.add('d-none');
    }
  }
}

function applyVendorFilters() {
  const sectorFilter = (document.getElementById('vendorSectorFilter')?.value || 'all').toLowerCase().trim();
  const ratingFilter = document.getElementById('vendorRatingFilter')?.value || 'all';
  const searchTerm = (document.getElementById('vendorSearchInput')?.value || '').toLowerCase().trim();
  const items = document.querySelectorAll('.rfq-vendor-item');
  let visibleCount = 0;

  items.forEach(item => {
    const itemSector = (item.getAttribute('data-sector') || '').toLowerCase().trim();
    const itemRating = parseFloat(item.getAttribute('data-rating') || '0');
    const itemReviews = parseInt(item.getAttribute('data-reviews-count') || '0', 10);
    const itemName = (item.getAttribute('data-name') || '').toLowerCase();
    const itemText = (item.textContent || '').toLowerCase();

    const matchesSector = (sectorFilter === 'all' || itemSector === sectorFilter);
    const matchesSearch = (!searchTerm || itemName.includes(searchTerm) || itemText.includes(searchTerm));
    let matchesRating = true;

    if (ratingFilter === 'top') {
      matchesRating = (itemRating >= 4.5);
    } else if (ratingFilter === 'high') {
      matchesRating = (itemRating >= 4.0);
    } else if (ratingFilter === 'rated') {
      matchesRating = (itemRating >= 1.0);
    } else if (ratingFilter === 'reviewed') {
      matchesRating = (itemReviews > 0);
    }

    if (matchesSector && matchesRating && matchesSearch) {
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

function filterVendorsBySector(sector) {
  const select = document.getElementById('vendorSectorFilter');
  if (select) {
    select.value = sector || 'all';
  }
  applyVendorFilters();
}

function sortVendorList(criteria) {
  const container = document.getElementById('vendorListContainer');
  if (!container) return;

  const items = Array.from(container.querySelectorAll('.rfq-vendor-item'));
  const noMatchAlert = document.getElementById('noVendorMatchAlert');

  items.sort((a, b) => {
    const ratingA = parseFloat(a.getAttribute('data-rating') || '0');
    const ratingB = parseFloat(b.getAttribute('data-rating') || '0');
    const ordersA = parseInt(a.getAttribute('data-orders-count') || '0', 10);
    const ordersB = parseInt(b.getAttribute('data-orders-count') || '0', 10);
    const nameA = (a.getAttribute('data-name') || '').toLowerCase();
    const nameB = (b.getAttribute('data-name') || '').toLowerCase();

    if (criteria === 'rating_desc') {
      if (ratingB !== ratingA) return ratingB - ratingA;
      if (ordersB !== ordersA) return ordersB - ordersA;
      return nameA.localeCompare(nameB);
    } else if (criteria === 'orders_desc') {
      if (ordersB !== ordersA) return ordersB - ordersA;
      if (ratingB !== ratingA) return ratingB - ratingA;
      return nameA.localeCompare(nameB);
    } else if (criteria === 'alpha') {
      return nameA.localeCompare(nameB);
    }
    return 0;
  });

  items.forEach(item => {
    container.insertBefore(item, noMatchAlert);
  });
}

function selectTopRatedVendors(minRating = 4.0) {
  let visibleItems = Array.from(document.querySelectorAll('.rfq-vendor-item:not(.d-none)'));
  
  // If current filter has no items >= minRating, reset rating filter to show all so user gets the top rated
  const matchingCount = visibleItems.filter(item => parseFloat(item.getAttribute('data-rating') || '0') >= minRating).length;
  if (matchingCount === 0) {
    const ratingFilterEl = document.getElementById('vendorRatingFilter');
    if (ratingFilterEl) ratingFilterEl.value = 'all';
    applyVendorFilters();
    visibleItems = Array.from(document.querySelectorAll('.rfq-vendor-item:not(.d-none)'));
  }

  visibleItems.forEach(item => {
    const rating = parseFloat(item.getAttribute('data-rating') || '0');
    const cb = item.querySelector('.vendor-checkbox');
    if (cb && rating >= minRating) {
      cb.checked = true;
    }
  });

  updateSelectedVendorCount();
}

function selectTopNVendors(n = 3) {
  let visibleItems = Array.from(document.querySelectorAll('.rfq-vendor-item:not(.d-none)'));

  // Sort visible items by rating descending, then orders count descending
  visibleItems.sort((a, b) => {
    const ratingA = parseFloat(a.getAttribute('data-rating') || '0');
    const ratingB = parseFloat(b.getAttribute('data-rating') || '0');
    if (ratingB !== ratingA) return ratingB - ratingA;
    const ordersA = parseInt(a.getAttribute('data-orders-count') || '0', 10);
    const ordersB = parseInt(b.getAttribute('data-orders-count') || '0', 10);
    return ordersB - ordersA;
  });

  visibleItems.slice(0, n).forEach(item => {
    const cb = item.querySelector('.vendor-checkbox');
    if (cb) {
      cb.checked = true;
    }
  });

  updateSelectedVendorCount();
}

function selectTopSectorVendors() {
  const sectorInput = document.getElementById('sectorInput')?.value?.toLowerCase()?.trim() || '';
  const sectorFilterSelect = document.getElementById('vendorSectorFilter');
  
  if (sectorInput && sectorFilterSelect) {
    // Check if sector option exists
    let matchedOption = false;
    for (let opt of sectorFilterSelect.options) {
      if (opt.value === sectorInput || opt.value.includes(sectorInput) || sectorInput.includes(opt.value)) {
        sectorFilterSelect.value = opt.value;
        matchedOption = true;
        break;
      }
    }
    applyVendorFilters();
  }

  // Select the top 3 highest rated in this sector
  selectTopNVendors(3);
}

function clearVendorSelection() {
  document.querySelectorAll('.vendor-checkbox').forEach(cb => {
    cb.checked = false;
  });
  updateSelectedVendorCount();
}

function handleRowClick(event, vendorId) {
  if (event.target.tagName === 'INPUT' || event.target.tagName === 'LABEL') return;
  toggleSingleVendor(vendorId);
}

function toggleSingleVendor(vendorId) {
  const cb = document.getElementById(`vendor${vendorId}`);
  if (cb) {
    cb.checked = !cb.checked;
    updateSelectedVendorCount();
  }
}

function toggleVendorSuggestionChip(vendorId) {
  toggleSingleVendor(vendorId);
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

  // Sync checkboxes and row highlight
  document.querySelectorAll('.rfq-vendor-item').forEach(item => {
    const cb = item.querySelector('.vendor-checkbox');
    if (cb && cb.checked) {
      if (item.classList.contains('d-flex') || !item.classList.contains('d-none')) {
        selectedVisible++;
      }
      item.style.backgroundColor = '#f1f5f9';
    } else {
      item.style.backgroundColor = '';
    }
  });

  // Sync suggestion chips
  document.querySelectorAll('.vendor-suggest-chip').forEach(chip => {
    const vId = chip.getAttribute('data-vendor-id');
    const cb = document.getElementById(`vendor${vId}`);
    const checkIcon = chip.querySelector('.chip-check-icon');
    const plusIcon = chip.querySelector('.chip-plus-icon');

    if (cb && cb.checked) {
      chip.classList.remove('bg-white', 'text-dark');
      chip.classList.add('bg-success-subtle', 'border-success-subtle', 'text-success-emphasis', 'fw-semibold');
      if (checkIcon) checkIcon.classList.remove('d-none');
      if (plusIcon) plusIcon.classList.add('d-none');
    } else {
      chip.classList.remove('bg-success-subtle', 'border-success-subtle', 'text-success-emphasis', 'fw-semibold');
      chip.classList.add('bg-white', 'text-dark');
      if (checkIcon) checkIcon.classList.add('d-none');
      if (plusIcon) plusIcon.classList.remove('d-none');
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
  // Initial sort by highest rating
  sortVendorList('rating_desc');
  updateSelectedVendorCount();
});
@endsection