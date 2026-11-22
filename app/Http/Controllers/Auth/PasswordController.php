<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Show Forgot Password request screen (guest).
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link to the user's registered email.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $targetUser = User::where('email', strtolower(trim((string) $request->input('email'))))->first();
        if ($targetUser && ! $targetUser->isActive()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account has been deactivated. Please contact the Admin Head.']);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'A password reset link has been emailed to your registered address.');
        }

        // Return a clean validation error when user cannot be emailed or is throttled
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /**
     * Show Reset Password screen using emailed token (guest).
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Reset password using the valid reset token.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $targetUser = User::where('email', strtolower(trim((string) $request->input('email'))))->first();
        if ($targetUser && ! $targetUser->isActive()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account has been deactivated. Please contact the Admin Head.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLog::create([
                    'user_id' => $user->id,
                    'module' => 'Auth',
                    'action' => 'password_reset',
                    'reference' => $user->email,
                    'detail' => "{$user->name} reset their account password via email reset link.",
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully. You may now sign in with your new password.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /**
     * Show authenticated Change Password screen.
     */
    public function showChangeForm(): View
    {
        return view('pages.change-password');
    }

    /**
     * Update the currently authenticated user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password you entered is incorrect.',
            'password.different' => 'Your new password must be different from your current password.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'password_changed',
            'reference' => $user->email,
            'detail' => "{$user->name} updated their account password.",
        ]);

        return back()->with('status', 'Your password has been updated successfully.');
    }
}
