@php
  $isEdit = isset($member);
  $selectedRole = old('role', $isEdit ? $member->role : ($defaultRole ?? \App\Support\Roles::PR_SR_EXEC));
  $selectedDept = old('department', $isEdit ? ($member->department ?: 'General') : 'Administration');
  $selectedModules = old('allowed_modules', $isEdit ? $effectiveModules : ($roleDefaults[$selectedRole] ?? []));
  if (! is_array($selectedModules)) {
      $selectedModules = [];
  }
  $selectedModules = array_values(array_unique($selectedModules));
@endphp

<div class="card card-panel shadow-sm border-0 p-4" id="userAccessFormRoot" data-role-defaults='@json($roleDefaults)' data-is-edit="{{ $isEdit ? '1' : '0' }}">
  <!-- Header Strip -->
  <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 pb-3 mb-4 border-bottom">
    <div>
      <h1 class="h5 mb-1 fw-bold text-dark">{{ $isEdit ? "Edit Member · {$member->name}" : 'Add New Member' }}</h1>
      <div class="text-muted" style="font-size: 13px;">
        {{ $isEdit ? 'Update account details, department placement, or module visibility.' : 'Create portal login credentials, assign role & department, and pick visible ERP modules.' }}
      </div>
    </div>
    <span class="text-muted fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px;">
      {{ $isEdit ? 'Access Profile' : 'Member Onboarding' }}
    </span>
  </div>

  <!-- Row 1: Identity & Credentials -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <label for="memberName" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Full Name <span class="text-danger">*</span>
      </label>
      <input type="text" id="memberName" name="name" value="{{ old('name', $isEdit ? $member->name : '') }}" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Ananya Sharma" required>
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
      <label for="memberEmail" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Official Email <span class="text-danger">*</span>
      </label>
      <input type="email" id="memberEmail" name="email" value="{{ old('email', $isEdit ? $member->email : '') }}" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="name@assocham.test" required>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-2">
      <label for="memberPassword" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Password @unless($isEdit)<span class="text-danger">*</span>@endunless
      </label>
      <input type="password" id="memberPassword" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror" placeholder="{{ $isEdit ? 'Optional' : 'Min 8 chars' }}" @unless($isEdit) required @endunless>
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-2">
      <label for="memberPasswordConfirm" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Confirm @unless($isEdit)<span class="text-danger">*</span>@endunless
      </label>
      <input type="password" id="memberPasswordConfirm" name="password_confirmation" class="form-control form-control-sm" placeholder="{{ $isEdit ? 'Optional' : 'Repeat' }}" @unless($isEdit) required @endunless>
    </div>
  </div>

  <!-- Row 2: Role, Designation & Department -->
  <div class="row g-3 pb-4 mb-4 border-bottom">
    <div class="col-12 col-md-4">
      <label for="memberRole" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Workflow Role <span class="text-danger">*</span>
      </label>
      <select id="memberRole" name="role" class="form-select form-select-sm @error('role') is-invalid @enderror" required>
        @foreach ($roles as $slug => $label)
          <option value="{{ $slug }}" @selected($selectedRole === $slug)>{{ $label }}</option>
        @endforeach
      </select>
      @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
      <label for="memberDesignation" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Designation <span class="text-danger">*</span>
      </label>
      <input type="text" id="memberDesignation" name="designation" list="designationPresets" value="{{ old('designation', $isEdit ? ($member->designation ?: $member->roleLabel()) : '') }}" class="form-control form-control-sm @error('designation') is-invalid @enderror" placeholder="e.g. Executive - Procurement" required>
      <datalist id="designationPresets">
        @foreach ($roles as $label)
          <option value="{{ $label }}"></option>
        @endforeach
        <option value="Senior Executive"></option>
        <option value="Procurement Associate"></option>
        <option value="Accounts Officer"></option>
        <option value="Travel Coordinator"></option>
      </datalist>
      @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
      <label for="memberDepartment" class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
        Department <span class="text-danger">*</span>
      </label>
      <select id="memberDepartment" name="department" class="form-select form-select-sm @error('department') is-invalid @enderror" required>
        @foreach ($departments as $dept)
          <option value="{{ $dept }}" @selected($selectedDept === $dept)>{{ $dept }}</option>
        @endforeach
        <option value="__custom__" @selected(old('department') === '__custom__')>+ Custom Department…</option>
      </select>
      @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror

      <div class="mt-2 @if(old('department') !== '__custom__') d-none @endif" id="customDepartmentWrap">
        <input type="text" id="customDepartmentInput" name="custom_department" value="{{ old('custom_department') }}" class="form-control form-control-sm @error('custom_department') is-invalid @enderror" placeholder="Enter custom department name…">
        @error('custom_department')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  <!-- Section: Module Visibility Matrix -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h2 class="h6 fw-bold text-dark mb-0">Visible Sidebar Modules</h2>
      <span class="badge fw-medium px-2 py-1" style="font-size: 11px; background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
        Dashboard Always On
      </span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-sm btn-light border text-primary fw-semibold px-2 py-1" style="font-size: 12px;" id="btnApplyRoleDefaults">
        Apply Role Defaults
      </button>
      <button type="button" class="btn btn-sm btn-light border text-secondary px-2 py-1" style="font-size: 12px;" id="btnSelectAllModules">
        Select All
      </button>
      <button type="button" class="btn btn-sm btn-light border text-secondary px-2 py-1" style="font-size: 12px;" id="btnClearModules">
        Clear
      </button>
    </div>
  </div>

  <div class="row g-3">
    @foreach ($moduleCatalog as $sectionName => $items)
      <div class="col-12 col-md-6 col-xl-3">
        <div class="border rounded-3 p-3 h-100" style="background: #F8FAFC;">
          <div class="text-uppercase fw-bold text-muted mb-2" style="font-size: 10.5px; letter-spacing: 0.5px;">
            {{ $sectionName }}
          </div>
          <div class="d-flex flex-column gap-2">
            @foreach ($items as $item)
              @php
                $isChecked = in_array($item['id'], $selectedModules, true);
              @endphp
              <label class="d-flex align-items-center gap-2 px-2 py-1 rounded-2 bg-white border mb-0" style="cursor: pointer; font-size: 12.5px;" title="{{ $item['hint'] }}">
                <input
                  type="checkbox"
                  name="allowed_modules[]"
                  value="{{ $item['id'] }}"
                  class="form-check-input mt-0 flex-shrink-0 module-access-checkbox"
                  @checked($isChecked)
                >
                <span class="fw-medium text-dark">{{ $item['label'] }}</span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <!-- Footer Action Bar -->
  <div class="pt-3 mt-4 border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="text-muted" style="font-size: 12px;">
      Selected modules control both sidebar visibility and direct route access.
    </div>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="{{ route('users') }}" class="btn btn-light border px-3 fw-semibold text-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 fw-semibold">
        <i class="bi bi-check2-circle"></i>
        <span>{{ $isEdit ? 'Save Changes' : 'Create Member' }}</span>
      </button>
    </div>
  </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('userAccessFormRoot');
  if (!root) return;

  var roleDefaults = JSON.parse(root.dataset.roleDefaults || '{}');
  var isEdit = root.dataset.isEdit === '1';
  var roleSelect = document.getElementById('memberRole');
  var designationInput = document.getElementById('memberDesignation');
  var deptSelect = document.getElementById('memberDepartment');
  var customDeptWrap = document.getElementById('customDepartmentWrap');
  var customDeptInput = document.getElementById('customDepartmentInput');
  var checkboxes = Array.from(document.querySelectorAll('.module-access-checkbox'));
  var manualModulesTouched = isEdit;
  var manualDesignationTouched = isEdit || (designationInput && designationInput.value.trim() !== '');

  function applyDefaultsForRole(roleSlug) {
    var list = roleDefaults[roleSlug] || [];
    checkboxes.forEach(function (cb) {
      cb.checked = list.indexOf(cb.value) !== -1;
    });
  }

  function syncDesignationFromRole() {
    if (!roleSelect || !designationInput || manualDesignationTouched) return;
    var opt = roleSelect.options[roleSelect.selectedIndex];
    if (opt) {
      designationInput.value = opt.textContent.trim();
    }
  }

  if (designationInput) {
    designationInput.addEventListener('input', function () {
      manualDesignationTouched = designationInput.value.trim() !== '';
    });
  }

  checkboxes.forEach(function (cb) {
    cb.addEventListener('change', function () {
      manualModulesTouched = true;
    });
  });

  if (roleSelect) {
    if (!isEdit && !manualDesignationTouched) {
      syncDesignationFromRole();
    }
    roleSelect.addEventListener('change', function () {
      syncDesignationFromRole();
      if (!manualModulesTouched) {
        applyDefaultsForRole(roleSelect.value);
      }
    });
  }

  var btnDefaults = document.getElementById('btnApplyRoleDefaults');
  if (btnDefaults && roleSelect) {
    btnDefaults.addEventListener('click', function () {
      applyDefaultsForRole(roleSelect.value);
      manualModulesTouched = true;
    });
  }

  var btnSelectAll = document.getElementById('btnSelectAllModules');
  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function () {
      checkboxes.forEach(function (cb) { cb.checked = true; });
      manualModulesTouched = true;
    });
  }

  var btnClear = document.getElementById('btnClearModules');
  if (btnClear) {
    btnClear.addEventListener('click', function () {
      checkboxes.forEach(function (cb) { cb.checked = false; });
      manualModulesTouched = true;
    });
  }

  if (deptSelect && customDeptWrap) {
    deptSelect.addEventListener('change', function () {
      var showCustom = deptSelect.value === '__custom__';
      customDeptWrap.classList.toggle('d-none', !showCustom);
      if (customDeptInput) {
        customDeptInput.required = showCustom;
      }
    });
  }
});
</script>
@endsection
