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

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function switchRole(Request $request): RedirectResponse
    {
        $role = $request->validate([
            'role' => ['required', 'string'],
        ])['role'];

        $user = \App\Models\User::where('role', $role)->first();
        if (! $user) {
            return back()->with('error', "No demo account found for role {$role}.");
        }

        Auth::login($user);
        $request->session()->regenerate();

        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'module' => 'System',
            'action' => 'Switched Role',
            'reference' => \App\Support\Roles::label($user->role),
            'detail' => "Active user set to {$user->name} ({$user->roleLabel()})",
        ]);

        $previousUrl = url()->previous();
        $previousPath = trim((string) parse_url($previousUrl, PHP_URL_PATH), '/');
        $rootModule = explode('/', $previousPath)[0] ?? '';
        if ($rootModule && $rootModule !== 'index' && ! \App\Support\Roles::canAccessModuleForUser($user, $rootModule)) {
            return redirect()->route('dashboard')->with('status', "Active session switched to {$user->name} ({$user->roleLabel()}).");
        }

        return redirect()->back()->with('status', "Active session switched to {$user->name} ({$user->roleLabel()}).");
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
