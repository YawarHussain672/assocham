<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        $search = trim((string) $request->query('q', ''));
        $roleFilter = (string) $request->query('role', '');
        $departmentFilter = (string) $request->query('department', '');

        $query = User::query()->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '' && in_array($roleFilter, Roles::all(), true)) {
            $query->where('role', $roleFilter);
        }

        if ($departmentFilter !== '') {
            $query->where('department', $departmentFilter);
        }

        $users = $query->get();

        $allUsers = User::all(['id', 'role', 'department', 'allowed_modules']);
        $stats = [
            'total' => $allUsers->count(),
            'custom_access' => $allUsers->filter(fn (User $u) => is_array($u->allowed_modules))->count(),
            'departments' => $allUsers->pluck('department')->filter()->unique()->count(),
            'roles' => $allUsers->pluck('role')->filter()->unique()->count(),
        ];

        $departments = $this->availableDepartments();

        return view('pages.users', [
            'users' => $users,
            'stats' => $stats,
            'roles' => Roles::labels(),
            'departments' => $departments,
            'filters' => [
                'q' => $search,
                'role' => $roleFilter,
                'department' => $departmentFilter,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        return view('pages.users-create', [
            'roles' => Roles::labels(),
            'departments' => $this->availableDepartments(),
            'moduleCatalog' => Roles::moduleCatalog(),
            'roleDefaults' => Roles::roleDefaultsMap(),
            'defaultRole' => Roles::PR_SR_EXEC,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(Roles::all())],
            'designation' => ['required', 'string', 'max:120'],
            'department' => ['required', 'string', 'max:100'],
            'custom_department' => ['nullable', 'string', 'max:100'],
            'use_role_defaults' => ['nullable', 'boolean'],
            'allowed_modules' => ['nullable', 'array'],
            'allowed_modules.*' => ['string', Rule::in(Roles::selectableModuleIds())],
        ]);

        $department = $validated['department'] === '__custom__'
            ? trim((string) ($validated['custom_department'] ?? ''))
            : $validated['department'];

        if ($department === '') {
            return back()
                ->withInput()
                ->withErrors(['custom_department' => 'Please enter a department name when choosing Custom Department.']);
        }

        $allowedModules = $this->resolveAllowedModules($request);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'designation' => trim($validated['designation']),
            'department' => $department,
            'allowed_modules' => $allowedModules,
            'email_verified_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'module' => 'UserManagement',
            'action' => 'created',
            'reference' => $user->email,
            'detail' => sprintf(
                'Added %s (%s · %s) with %s module profile.',
                $user->name,
                $user->designationLabel(),
                $user->department,
                is_array($user->allowed_modules) ? count($user->allowed_modules).' custom' : 'role default'
            ),
        ]);

        return redirect()
            ->route('users')
            ->with('status', "Member {$user->name} added successfully with portal login and module permissions.");
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        $effectiveModules = is_array($user->allowed_modules)
            ? $user->allowed_modules
            : (Roles::roleDefaultsMap()[$user->role] ?? []);

        return view('pages.users-edit', [
            'member' => $user,
            'roles' => Roles::labels(),
            'departments' => $this->availableDepartments($user->department),
            'moduleCatalog' => Roles::moduleCatalog(),
            'roleDefaults' => Roles::roleDefaultsMap(),
            'effectiveModules' => $effectiveModules,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(Roles::all())],
            'designation' => ['required', 'string', 'max:120'],
            'department' => ['required', 'string', 'max:100'],
            'custom_department' => ['nullable', 'string', 'max:100'],
            'use_role_defaults' => ['nullable', 'boolean'],
            'allowed_modules' => ['nullable', 'array'],
            'allowed_modules.*' => ['string', Rule::in(Roles::selectableModuleIds())],
        ]);

        $department = $validated['department'] === '__custom__'
            ? trim((string) ($validated['custom_department'] ?? ''))
            : $validated['department'];

        if ($department === '') {
            return back()
                ->withInput()
                ->withErrors(['custom_department' => 'Please enter a department name when choosing Custom Department.']);
        }

        $allowedModules = $this->resolveAllowedModules($request);

        // Prevent active Head Admin from removing their own User Management access accidentally.
        if ($user->id === $request->user()?->id && is_array($allowedModules) && ! in_array('users', $allowedModules, true)) {
            $allowedModules[] = 'users';
        }

        $payload = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'designation' => trim($validated['designation']),
            'department' => $department,
            'allowed_modules' => $allowedModules,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'module' => 'UserManagement',
            'action' => 'updated',
            'reference' => $user->email,
            'detail' => sprintf(
                'Updated access profile for %s (%s · %s)%s.',
                $user->name,
                $user->designationLabel(),
                $user->department,
                ! empty($validated['password']) ? ' including password reset' : ''
            ),
        ]);

        return redirect()
            ->route('users')
            ->with('status', "Updated {$user->name}'s profile and module access.");
    }

    private function canManageUsers(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && Roles::canAccessModuleForUser($user, 'users');
    }

    /**
     * Returns explicit module IDs selected in the form (unless role defaults are explicitly requested).
     *
     * @return list<string>|null
     */
    private function resolveAllowedModules(Request $request): ?array
    {
        if ($request->boolean('use_role_defaults')) {
            return null;
        }

        return array_values(array_unique(array_intersect(
            Roles::selectableModuleIds(),
            (array) $request->input('allowed_modules', [])
        )));
    }

    /**
     * @return list<string>
     */
    private function availableDepartments(?string $include = null): array
    {
        $budgetDepartments = DepartmentBudget::query()
            ->distinct()
            ->pluck('department')
            ->filter()
            ->all();

        $userDepartments = User::query()
            ->distinct()
            ->pluck('department')
            ->filter()
            ->all();

        $defaults = [
            'Administration',
            'Finance',
            'Procurement',
            'Operations',
            'Projects',
            'IT',
            'Marketing',
            'HR',
            'General',
        ];

        $merged = array_merge($defaults, $budgetDepartments, $userDepartments);
        if ($include) {
            $merged[] = $include;
        }

        $unique = array_values(array_unique(array_filter(array_map('trim', $merged))));
        sort($unique);

        return $unique;
    }
}
