<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies the auth flow that was manually curl-verified when Phase 0 was
 * built (see project memory: erp-laravel-phase0-foundation) — unauthenticated
 * redirect, bad credentials rejected, good credentials succeed, logout works.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_hitting_a_protected_page_is_redirected_to_login(): void
    {
        $this->get('/index')->assertRedirect(route('login'));
    }

    public function test_login_page_is_reachable_by_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = $this->userWithRole(\App\Support\Roles::CFO);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_valid_credentials_log_the_user_in_and_redirect_to_dashboard(): void
    {
        $user = $this->userWithRole(\App\Support\Roles::CFO);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_ends_the_session_and_protected_pages_redirect_again(): void
    {
        $this->loginAs(\App\Support\Roles::CFO);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get('/index')->assertRedirect(route('login'));
    }
}
