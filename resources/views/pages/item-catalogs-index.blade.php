@extends('layouts.app')

@section('title', 'Item & Fixed Price Master (OPEX / COPEX)')

@section('content')
<div class="view-wrap" id="pageContent">

  {{-- Page Header & Action Bar --}}
  <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-0">Item &amp; Fixed Price Master (OPEX / COPEX)</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      @if ($canManageBudgets)
        <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createCatalogItemModal">
          <i class="bi bi-plus-circle"></i>
          <span>Add Master Item</span>
        </button>
      @else
        <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
          <i class="bi bi-eye me-1"></i>Read-Only View
        </span>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-octagon-fill fs-5"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @php
    $items = $itemCatalogs ?? collect();
    $totalCatItems = $catalogCounts['total'] ?? $items->count();
    $opexCatItems = $catalogCounts['opex'] ?? $items->where('expense_type', 'opex')->count();
    $copexCatItems = $catalogCounts['copex'] ?? $items->where('expense_type', 'copex')->count();
    $activeCatItems = $catalogCounts['active'] ?? $items->where('is_active', true)->count();
  @endphp

  <!-- Enseur Status Switcher -->
  <div class="erp-switch-bar" id="catalogSwitchBar">
    <button type="button" class="erp-switch-tab active" data-type="" onclick="selectCatalogTab('')">
      All Items <span class="erp-switch-count">{{ $totalCatItems }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-type="opex" onclick="selectCatalogTab('opex')">
      OPEX <span class="erp-switch-count">{{ $opexCatItems }}</span>
    </button>
    <button type="button" class="erp-switch-tab" data-type="copex" onclick="selectCatalogTab('copex')">
      COPEX (SG) <span class="erp-switch-count">{{ $copexCatItems }}</span>
    </button>
  </div>

  {{-- Unified ERP Table Container --}}
  <div class="erp-table-container">
    {{-- Integrated Toolbar --}}
    <div class="erp-table-toolbar">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-0 w-100">
        <div class="erp-search-wrap flex-grow-1" style="min-width: 220px;">
          <i class="bi bi-search erp-search-icon"></i>
          <input type="text" id="catalogSearchInput" class="erp-search-input" placeholder="Search by name, code, category, or specs...">
        </div>
        <div style="width: 170px;">
          <select id="catalogCatFilter" class="erp-select">
            <option value="">All Categories</option>
            @foreach ($catalogCategories as $cat)
              <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
            @endforeach
          </select>
        </div>
        <div style="width: 130px;">
          <select id="catalogActiveFilter" class="erp-select">
            <option value="">All Status</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive Only</option>
          </select>
        </div>
        <button type="button" id="catalogResetBtn" class="erp-btn-reset">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </div>

    {{-- Catalog Table --}}
    <table class="erp-table" id="catalogItemsTable">
      <thead>
        <tr>
          @if ($canManageBudgets)
            <th class="ps-3" style="width: 10%;">ITEM CODE</th>
            <th style="width: 32%; white-space: normal !important; line-height: 1.25;">ITEM &amp;<br>SPECIFICATIONS</th>
            <th style="width: 15%;">CATEGORY</th>
            <th style="width: 8%;" class="text-center">TYPE</th>
            <th style="width: 14%;" class="text-end">PRICE</th>
            <th style="width: 9%;" class="text-center">STATUS</th>
            <th style="width: 12%;" class="text-end pe-3">ACTION</th>
          @else
            <th class="ps-3" style="width: 11%;">ITEM CODE</th>
            <th style="width: 38%; white-space: normal !important; line-height: 1.25;">ITEM &amp;<br>SPECIFICATIONS</th>
            <th style="width: 18%;">CATEGORY</th>
            <th style="width: 9%;" class="text-center">TYPE</th>
            <th style="width: 14%;" class="text-end pe-3">PRICE</th>
            <th style="width: 10%;" class="text-center">STATUS</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @forelse ($itemCatalogs as $item)
          @php
            $gstAmt = ($item->fixed_price * $item->gst_percent) / 100;
            $totWithGst = $item->fixed_price + $gstAmt;
          @endphp
          <tr class="catalog-item-row"
              data-name="{{ strtolower($item->name) }}"
              data-code="{{ strtolower($item->item_code) }}"
              data-type="{{ strtolower($item->expense_type) }}"
              data-category="{{ strtolower($item->category ?? '') }}"
              data-desc="{{ strtolower($item->description ?? '') }}"
              data-active="{{ $item->is_active ? 'active' : 'inactive' }}">
            
            <td class="ps-3 text-nowrap">
              <span class="font-monospace fw-semibold text-secondary" style="font-size: 12px;">
                {{ $item->item_code }}
              </span>
            </td>

            <td>
              <div class="fw-semibold text-dark" style="font-size: 13px; line-height: 1.35;">{{ $item->name }}</div>
              @if ($item->description)
                <div class="text-secondary small mt-0.5" style="font-size: 11.5px; line-height: 1.4; word-break: normal; white-space: normal;">
                  {{ $item->description }}
                </div>
              @endif
            </td>

            <td>
              <span class="text-secondary" style="font-size: 12px;">
                {{ $item->category ?: 'General' }}
              </span>
            </td>

            <td class="text-center text-nowrap">
              @if ($item->isCopex())
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5 fw-semibold" style="font-size: 10.5px;">
                  COPEX
                </span>
              @else
                <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5 fw-semibold" style="font-size: 10.5px;">
                  OPEX
                </span>
              @endif
            </td>

            <td class="text-end text-nowrap">
              <div class="fw-bold font-monospace text-dark" style="font-size: 13px;">
                ₹{{ number_format((float)$item->fixed_price, 2) }}
              </div>
              <div class="text-secondary small" style="font-size: 10.5px;">
                {{ $item->unit ?: 'Unit' }} &bull; {{ (float)$item->gst_percent }}% GST
              </div>
            </td>

            <td class="text-center text-nowrap">
              @if ($item->is_active)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 11px;">Active</span>
              @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 11px;">Inactive</span>
              @endif
            </td>

            @if ($canManageBudgets)
              <td class="text-end pe-3 text-nowrap">
                <div class="erp-row-actions">
                  <button type="button" class="erp-btn-action-edit" data-bs-toggle="modal" data-bs-target="#editCatalogModal-{{ $item->id }}" title="Edit Predefined Item">
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                  <div class="dropdown">
                    <button class="erp-action-dropdown-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Actions">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end erp-action-menu shadow-sm">
                      <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCatalogModal-{{ $item->id }}">
                          <i class="bi bi-pencil text-secondary"></i> Edit Item
                        </button>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="POST" action="{{ route('item-catalogs.destroy', $item) }}" class="m-0" onsubmit="return confirm('Are you sure you want to delete catalog item {{ $item->name }} ({{ $item->item_code }})?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-trash3 text-danger"></i> Delete Item
                          </button>
                        </form>
                      </li>
                    </ul>
                  </div>
                </div>
              </td>
            @endif
          </tr>
        @empty
          <tr id="emptyCatalogRow">
            <td colspan="{{ $canManageBudgets ? 7 : 6 }}" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
              <div>No predefined items found in the master catalog.</div>
              @if ($canManageBudgets)
                <div class="mt-2">
                  <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCatalogItemModal">
                    <i class="bi bi-plus-lg me-1"></i> Add First Predefined Item
                  </button>
                </div>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Modal: Add Predefined Item --}}
  @if ($canManageBudgets)
    <div class="modal fade" id="createCatalogItemModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <form method="POST" action="{{ route('item-catalogs.store') }}">
            @csrf
            <div class="modal-header bg-white border-bottom px-4 py-3">
              <div>
                <h6 class="modal-title fw-bold text-dark mb-0">Add Predefined Item &amp; Fixed Pricing</h6>
                <div class="text-muted" style="font-size: 12px;">Configure standard master item with fixed pricing and OPEX/COPEX governance.</div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-dark mb-1">Item Code <span class="text-muted small fw-normal">(Optional)</span></label>
                  <input type="text" name="item_code" class="form-control font-monospace" placeholder="e.g. OPEX-007 or COPEX-009">
                </div>

                <div class="col-12 col-sm-8">
                  <label class="form-label fw-semibold text-dark mb-1">Item Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Dell Latitude 5540 Laptop" required>
                </div>

                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-dark mb-1">Classification / Expense Type <span class="text-danger">*</span></label>
                  <select name="expense_type" class="form-select" required>
                    <option value="opex">OPEX — Operating Expense (&lt; ₹5L clears at CFO)</option>
                    <option value="copex">COPEX — Capital Asset (Mandatory SG Approval)</option>
                  </select>
                </div>

                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-dark mb-1">Category</label>
                  <input type="text" name="category" class="form-control" placeholder="e.g. IT &amp; Hardware, Logistics, Facilities">
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-dark mb-1">Fixed Unit Cost (₹) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" name="fixed_price" class="form-control font-monospace fw-bold" step="0.01" min="0" placeholder="0.00" required>
                  </div>
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-dark mb-1">Unit of Measurement</label>
                  <input type="text" name="unit" class="form-control" value="Units" placeholder="e.g. Units, Sets, Months, Years">
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-dark mb-1">Applicable GST %</label>
                  <div class="input-group">
                    <input type="number" name="gst_percent" class="form-control" value="18.00" step="0.01" min="0" max="100">
                    <span class="input-group-text bg-light">%</span>
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-dark mb-1">Catalog Status</label>
                  <select name="is_active" class="form-select">
                    <option value="1" selected>Active (Available for PR Line Item Selection)</option>
                    <option value="0">Inactive / Archived</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold text-dark mb-1">Technical Specifications / Scope of Work</label>
                  <textarea name="description" class="form-control" rows="2" placeholder="Model, warranty, configuration, or service agreement scope..."></textarea>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top px-4 py-3">
              <button type="button" class="btn btn-outline-secondary fw-semibold px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary fw-semibold px-4">Save Master Item</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Edit Predefined Item Modals --}}
    @foreach ($itemCatalogs as $item)
      <div class="modal fade" id="editCatalogModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('item-catalogs.update', $item) }}">
              @csrf
              @method('PUT')
              <div class="modal-header bg-white border-bottom px-4 py-3">
                <div>
                  <h6 class="modal-title fw-bold text-dark mb-0">Edit Predefined Item — {{ $item->item_code }}</h6>
                  <div class="text-muted" style="font-size: 12px;">Modify pricing, specifications, or classification governance.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body p-4">
                <div class="row g-3">
                  <div class="col-12 col-sm-4">
                    <label class="form-label fw-semibold text-dark mb-1">Item Code</label>
                    <input type="text" name="item_code" class="form-control font-monospace" value="{{ old('item_code', $item->item_code) }}" required>
                  </div>

                  <div class="col-12 col-sm-8">
                    <label class="form-label fw-semibold text-dark mb-1">Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                  </div>

                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold text-dark mb-1">Classification / Expense Type <span class="text-danger">*</span></label>
                    <select name="expense_type" class="form-select" required>
                      <option value="opex" {{ old('expense_type', $item->expense_type) === 'opex' ? 'selected' : '' }}>OPEX — Operating Expense (&lt; ₹5L clears at CFO)</option>
                      <option value="copex" {{ old('expense_type', $item->expense_type) === 'copex' ? 'selected' : '' }}>COPEX — Capital Asset (Mandatory SG Approval)</option>
                    </select>
                  </div>

                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold text-dark mb-1">Category</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $item->category) }}" placeholder="e.g. IT &amp; Hardware, Logistics">
                  </div>

                  <div class="col-12 col-sm-4">
                    <label class="form-label fw-semibold text-dark mb-1">Fixed Unit Cost (₹) <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text bg-light">₹</span>
                      <input type="number" name="fixed_price" class="form-control font-monospace fw-bold" value="{{ old('fixed_price', $item->fixed_price) }}" step="0.01" min="0" required>
                    </div>
                  </div>

                  <div class="col-12 col-sm-4">
                    <label class="form-label fw-semibold text-dark mb-1">Unit of Measurement</label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $item->unit ?: 'Units') }}">
                  </div>

                  <div class="col-12 col-sm-4">
                    <label class="form-label fw-semibold text-dark mb-1">Applicable GST %</label>
                    <div class="input-group">
                      <input type="number" name="gst_percent" class="form-control" value="{{ old('gst_percent', $item->gst_percent) }}" step="0.01" min="0" max="100">
                      <span class="input-group-text bg-light">%</span>
                    </div>
                  </div>

                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold text-dark mb-1">Catalog Status</label>
                    <select name="is_active" class="form-select">
                      <option value="1" {{ old('is_active', $item->is_active) ? 'selected' : '' }}>Active</option>
                      <option value="0" {{ !old('is_active', $item->is_active) ? 'selected' : '' }}>Inactive</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold text-dark mb-1">Technical Specifications / Scope of Work</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $item->description) }}</textarea>
                  </div>
                </div>
              </div>

              <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary fw-semibold px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold px-4">Update Master Item</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endforeach
  @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('catalogSearchInput');
  const catFilter = document.getElementById('catalogCatFilter');
  const activeFilter = document.getElementById('catalogActiveFilter');
  const resetBtn = document.getElementById('catalogResetBtn');
  const rows = document.querySelectorAll('.catalog-item-row');
  let currentCatalogType = '';

  window.selectCatalogTab = function(type) {
    currentCatalogType = type;
    applyFilters();
  };

  function applyFilters() {
    const query = (searchInput.value || '').trim().toLowerCase();
    const typeVal = currentCatalogType.toLowerCase();
    const catVal = (catFilter.value || '').toLowerCase();
    const activeVal = (activeFilter.value || '').toLowerCase();

    // Sync Enseur Switch Bar
    document.querySelectorAll('#catalogSwitchBar .erp-switch-tab').forEach(tab => {
      tab.classList.toggle('active', tab.dataset.type === typeVal);
    });

    let visible = 0;
    rows.forEach(tr => {
      const name = tr.dataset.name || '';
      const code = tr.dataset.code || '';
      const type = tr.dataset.type || '';
      const cat = tr.dataset.category || '';
      const desc = tr.dataset.desc || '';
      const active = tr.dataset.active || '';

      const matchesQuery = !query || name.includes(query) || code.includes(query) || cat.includes(query) || desc.includes(query);
      const matchesType = !typeVal || type === typeVal;
      const matchesCat = !catVal || cat === catVal;
      const matchesActive = !activeVal || active === activeVal;

      if (matchesQuery && matchesType && matchesCat && matchesActive) {
        tr.style.display = '';
        visible++;
      } else {
        tr.style.display = 'none';
      }
    });

    const counter = document.getElementById('catalogCounterCount');
    if (counter) counter.textContent = visible;
  }

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  if (catFilter) catFilter.addEventListener('change', applyFilters);
  if (activeFilter) activeFilter.addEventListener('change', applyFilters);

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      currentCatalogType = '';
      if (catFilter) catFilter.value = '';
      if (activeFilter) activeFilter.value = '';
      applyFilters();
    });
  }
});
</script>
@endsection

@section('init')
Shell.init({ activeNav: 'item-catalogs', crumb: 'Governance & Controls', title: 'Item & Fixed Price Master' });
@endsection
