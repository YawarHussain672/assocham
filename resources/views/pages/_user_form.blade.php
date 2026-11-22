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

<div class="card-panel" id="userAccessFormRoot" data-role-defaults='@json($roleDefaults)' data-is-edit="{{ $isEdit ? '1' : '0' }}">
  <!-- Header Strip -->
  <div class="form-icon-header d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
    <div class="d-flex align-items-center gap-3">
      <div class="vendor-form-icon-box">
        <i class="bi bi-person-gear"></i>
      </div>
      <div>
        <h1 class="h5 mb-0 fw-bold text-dark">{{ $isEdit ? "Edit Member · {$member->name}" : 'Add New Member' }}</h1>
      </div>
    </div>
    <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="font-size:11px; font-weight:600; letter-spacing:0.5px; border-color:#e2e8f0 !important;">
      {{ $isEdit ? 'ACCESS PROFILE' : 'MEMBER ONBOARDING' }}
    </span>
  </div>

  <!-- Section 1: Identity & Credentials -->
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
      <div class="vendor-form-icon-box">
        <i class="bi bi-person-vcard"></i>
      </div>
      <h2 class="vendor-form-section-title mb-0">Identity &amp; Credentials</h2>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <label for="memberName" class="vendor-form-label">
          Full Name <span class="text-danger">*</span>
        </label>
        <input type="text" id="memberName" name="name" value="{{ old('name', $isEdit ? $member->name : '') }}" class="form-control vendor-form-control @error('name') is-invalid @enderror" placeholder="e.g. Ananya Sharma" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-12 col-md-4">
        <label for="memberEmail" class="vendor-form-label">
          Official Email <span class="text-danger">*</span>
        </label>
        <input type="email" id="memberEmail" name="email" value="{{ old('email', $isEdit ? $member->email : '') }}" class="form-control vendor-form-control @error('email') is-invalid @enderror" placeholder="name@assocham.test" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-6 col-md-2">
        <label for="memberPassword" class="vendor-form-label">
          {{ $isEdit ? 'Reset Password' : 'Password' }} @unless($isEdit)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" id="memberPassword" name="password" class="form-control vendor-form-control @error('password') is-invalid @enderror" placeholder="{{ $isEdit ? 'Leave blank to keep' : 'Min 8 chars' }}" @unless($isEdit) required @endunless>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-6 col-md-2">
        <label for="memberPasswordConfirm" class="vendor-form-label">
          Confirm @unless($isEdit)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" id="memberPasswordConfirm" name="password_confirmation" class="form-control vendor-form-control" placeholder="{{ $isEdit ? 'Optional' : 'Repeat' }}" @unless($isEdit) required @endunless>
      </div>
    </div>
  </div>

  <!-- Section 2: Role, Designation & Department -->
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
      <div class="vendor-form-icon-box">
        <i class="bi bi-briefcase"></i>
      </div>
      <h2 class="vendor-form-section-title mb-0">Role &amp; Departmental Assignment</h2>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-4">
        <label for="memberRole" class="vendor-form-label">
          Workflow Role <span class="text-danger">*</span>
        </label>
        <select id="memberRole" name="role" class="form-select vendor-form-control @error('role') is-invalid @enderror" required>
          @foreach ($roles as $slug => $label)
            <option value="{{ $slug }}" @selected($selectedRole === $slug)>{{ $label }}</option>
          @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-12 col-md-4">
        <label for="memberDesignation" class="vendor-form-label">
          Designation <span class="text-danger">*</span>
        </label>
        <input type="text" id="memberDesignation" name="designation" list="designationPresets" value="{{ old('designation', $isEdit ? ($member->designation ?: $member->roleLabel()) : '') }}" class="form-control vendor-form-control @error('designation') is-invalid @enderror" placeholder="e.g. Executive - Procurement" required>
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
        <label for="memberDepartment" class="vendor-form-label">
          Department <span class="text-danger">*</span>
        </label>
        <select id="memberDepartment" name="department" class="form-select vendor-form-control @error('department') is-invalid @enderror" required>
          @foreach ($departments as $dept)
            <option value="{{ $dept }}" @selected($selectedDept === $dept)>{{ $dept }}</option>
          @endforeach
          <option value="__custom__" @selected(old('department') === '__custom__')>+ Custom Department…</option>
        </select>
        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="mt-2 @if(old('department') !== '__custom__') d-none @endif" id="customDepartmentWrap">
          <input type="text" id="customDepartmentInput" name="custom_department" value="{{ old('custom_department') }}" class="form-control vendor-form-control @error('custom_department') is-invalid @enderror" placeholder="Enter custom department name…">
          @error('custom_department')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Section 3: Module Visibility Matrix -->
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
      <div class="vendor-form-icon-box">
        <i class="bi bi-grid"></i>
      </div>
      <h2 class="vendor-form-section-title mb-0">Sidebar Module Visibility</h2>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge fw-medium px-2 py-1" style="font-size: 11px; background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
          Dashboard Always On
        </span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-light border text-primary fw-semibold px-2.5 py-1" style="font-size: 12px; border-color: #e2e8f0 !important;" id="btnApplyRoleDefaults">
          Apply Role Defaults
        </button>
        <button type="button" class="btn btn-sm btn-light border text-secondary px-2.5 py-1" style="font-size: 12px; border-color: #e2e8f0 !important;" id="btnSelectAllModules">
          Select All
        </button>
        <button type="button" class="btn btn-sm btn-light border text-secondary px-2.5 py-1" style="font-size: 12px; border-color: #e2e8f0 !important;" id="btnClearModules">
          Clear
        </button>
      </div>
    </div>
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
  <div class="d-flex justify-content-between align-items-center pt-3 border-top erp-form-footer">
    <a href="{{ route('users') }}" class="btn btn-light border px-4 py-2.5 fw-semibold text-secondary" style="font-size: 14px;">Cancel</a>
    <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm" style="font-size: 14.5px;">
      {{ $isEdit ? 'Save Changes' : 'Create Member' }}
    </button>
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
