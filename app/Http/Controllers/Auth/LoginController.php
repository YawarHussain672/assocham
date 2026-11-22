<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Hand-rolled session auth (no Breeze/Fortify) so the login page can reuse
 * the ERP's existing Bootstrap-based styling instead of pulling in a second
 * CSS framework (Breeze's Blade stack ships Tailwind). See project memory:
 * assocham-portal-spec / erp-laravel-shell-routing.
 */
class LoginController extends Controller
{
    public function create(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials don\'t match our records.',
            ]);
        }

        if (! $request->user()?->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact the Admin Head.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function switchRole(Request $request): RedirectResponse
    {
        abort_unless(
            config('app.enable_admin_role_switch'),
            403,
            'Role switching is disabled in this environment.'
        );

        $currentUser = $request->user();
        $impersonatorAdminId = $request->session()->get('impersonator_admin_id');
        $isRealAdmin = $currentUser && $currentUser->role === \App\Support\Roles::ADMIN_HEAD;
        $impersonatorAdmin = $impersonatorAdminId
            ? \App\Models\User::whereKey($impersonatorAdminId)
                ->where('role', \App\Support\Roles::ADMIN_HEAD)
                ->where('is_active', true)
                ->first()
            : null;

        abort_unless(
            $isRealAdmin || $impersonatorAdmin !== null,
            403,
            'Only the Admin Head is permitted to switch roles.'
        );

        $role = $request->validate([
            'role' => ['required', 'string'],
        ])['role'];

        $originAdmin = $impersonatorAdmin ?? $currentUser;

        if ($role === \App\Support\Roles::ADMIN_HEAD && $originAdmin) {
            $targetUser = $originAdmin;
        } else {
            $targetUser = \App\Models\User::where('role', $role)
                ->where('is_active', true)
                ->first();
        }

        if (! $targetUser || ! $targetUser->isActive()) {
            return back()->with('error', "No active account found for role {$role}.");
        }

        Auth::login($targetUser);
        $request->session()->regenerate();

        if ($targetUser->id === $originAdmin->id && $targetUser->role === \App\Support\Roles::ADMIN_HEAD) {
            $request->session()->forget('impersonator_admin_id');
        } else {
            $request->session()->put('impersonator_admin_id', $originAdmin->id);
        }

        \App\Models\AuditLog::create([
            'user_id' => $targetUser->id,
            'module' => 'System',
            'action' => 'Switched Role',
            'reference' => \App\Support\Roles::label($targetUser->role),
            'detail' => "Admin {$originAdmin->name} switched active session to {$targetUser->name} ({$targetUser->roleLabel()})",
        ]);

        $previousUrl = url()->previous();
        $previousPath = trim((string) parse_url($previousUrl, PHP_URL_PATH), '/');
        $rootModule = explode('/', $previousPath)[0] ?? '';
        if ($rootModule && $rootModule !== 'index' && ! \App\Support\Roles::canAccessModuleForUser($targetUser, $rootModule)) {
            return redirect()->route('dashboard')->with('status', "Active session switched to {$targetUser->name} ({$targetUser->roleLabel()}).");
        }

        return redirect()->back()->with('status', "Active session switched to {$targetUser->name} ({$targetUser->roleLabel()}).");
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
