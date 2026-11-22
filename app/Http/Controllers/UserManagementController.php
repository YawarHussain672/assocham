<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        $statusFilter = (string) $request->query('status', '');

        $query = User::query()
            ->orderByDesc('is_active')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '') {
            $query->where('role', $roleFilter);
        }

        if ($departmentFilter !== '') {
            $query->where('department', $departmentFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'deactivated') {
            $query->where('is_active', false);
        } elseif ($statusFilter === 'deleted') {
            $query->onlyTrashed();
        }

        $users = $query->get();

        $allUsers = User::withTrashed()->get(['id', 'role', 'department', 'allowed_modules', 'is_active', 'deleted_at']);
        $stats = [
            'total' => $allUsers->whereNull('deleted_at')->count(),
            'active' => $allUsers->filter(fn (User $u) => $u->isActive() && ! $u->trashed())->count(),
            'deactivated' => $allUsers->filter(fn (User $u) => ! $u->isActive() && ! $u->trashed())->count(),
            'deleted' => $allUsers->filter(fn (User $u) => $u->trashed())->count(),
            'custom_access' => $allUsers->whereNull('deleted_at')->filter(fn (User $u) => is_array($u->allowed_modules))->count(),
            'departments' => $allUsers->whereNull('deleted_at')->pluck('department')->filter()->unique()->count(),
            'roles' => $allUsers->whereNull('deleted_at')->pluck('role')->filter()->unique()->count(),
        ];

        $departments = $this->availableDepartments();
        $usersWithRecords = User::operationalUserIdSet();

        return view('pages.users', [
            'users' => $users,
            'stats' => $stats,
            'roles' => Roles::labels(),
            'departments' => $departments,
            'usersWithRecords' => $usersWithRecords,
            'filters' => [
                'q' => $search,
                'role' => $roleFilter,
                'department' => $departmentFilter,
                'status' => $statusFilter,
            ],
            'hasFilters' => ($search !== '' || $roleFilter !== '' || $departmentFilter !== '' || $statusFilter !== ''),
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
            'operationalRecords' => $user->operationalRecordCounts(),
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

        $passwordChanged = ! empty($validated['password']);
        if ($passwordChanged) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        if ($passwordChanged) {
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            if ($user->id !== $request->user()?->id) {
                $this->invalidateUserSessions($user->id);
            }
        }

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
                $passwordChanged ? ' including password reset' : ''
            ),
        ]);

        return redirect()
            ->route('users')
            ->with('status', $passwordChanged
                ? "Updated {$user->name}'s profile, module access, and password."
                : "Updated {$user->name}'s profile and module access.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        if ($user->id === $request->user()?->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $activating = ! $user->isActive();

        $user->forceFill([
            'is_active' => $activating,
            'deactivated_at' => $activating ? null : now(),
            'remember_token' => $activating ? $user->remember_token : Str::random(60),
        ])->save();

        if (! $activating) {
            $this->invalidateUserSessions($user->id);
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'module' => 'UserManagement',
            'action' => $activating ? 'reactivated' : 'deactivated',
            'reference' => $user->email,
            'detail' => $activating
                ? "Reactivated portal access for {$user->name} ({$user->designationLabel()} · {$user->department})."
                : "Deactivated portal access for {$user->name} ({$user->designationLabel()} · {$user->department}); active sessions revoked.",
        ]);

        $message = $activating
            ? "Account for {$user->name} has been reactivated."
            : "Account for {$user->name} has been deactivated and signed out.";

        return redirect()->route('users')->with('status', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->canManageUsers($request), 403, 'Only authorized administrators can manage employees.');

        if ($user->id === $request->user()?->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $recordCounts = $user->operationalRecordCounts();
        $hasRecords = count($recordCounts) > 0;
        $name = $user->name;
        $email = $user->email;
        $designation = $user->designationLabel();
        $department = $user->department ?: 'General';

        $this->invalidateUserSessions($user->id);

        if ($hasRecords) {
            $summary = collect($recordCounts)
                ->map(fn (int $count, string $label) => "{$count} {$label}")
                ->take(3)
                ->implode(', ');

            $user->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            // Soft-deletes user record to prevent foreign-key cascade deletions
            // and retain 100% of historical transactions, documents, and audit logs.
            $user->delete();

            AuditLog::create([
                'user_id' => $request->user()?->id,
                'module' => 'UserManagement',
                'action' => 'deleted',
                'reference' => $email,
                'detail' => "Deleted user account {$name} ({$designation} · {$department}) with data retention. Preserved {$summary} in operational history.",
            ]);

            return redirect()
                ->route('users')
                ->with('status', "Member {$name} has been deleted. All completed work, historical records ({$summary}), and audit data have been safely retained.");
        }

        $user->forceDelete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'module' => 'UserManagement',
            'action' => 'deleted',
            'reference' => $email,
            'detail' => "Permanently deleted user account {$name} ({$designation} · {$department}) with zero linked operational records.",
        ]);

        return redirect()
            ->route('users')
            ->with('status', "Member {$name} has been permanently deleted.");
    }

    private function invalidateUserSessions(int $userId): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
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
