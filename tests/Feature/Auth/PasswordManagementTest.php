<?php

namespace Tests\Feature\Auth;

use App\Models\ExceptionRequest;
use App\Models\Trf;
use App\Support\Roles;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authenticated_user_can_change_password_with_current_password_verification(): void
    {
        $buyer = $this->loginAs(Roles::BUYER);

        $this->get(route('password.change'))
            ->assertOk()
            ->assertSee('Change Account Password');

        // Wrong current password is rejected
        $this->from(route('password.change'))->put(route('password.change.update'), [
            'current_password' => 'wrong-pass',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertRedirect(route('password.change'))
            ->assertSessionHasErrors('current_password');

        // Valid password change succeeds and writes audit trail
        $this->from(route('password.change'))->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertRedirect(route('password.change'))
            ->assertSessionHas('status');

        $buyer->refresh();
        $this->assertTrue(Hash::check('NewSecurePass123', $buyer->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $buyer->id,
            'module' => 'Auth',
            'action' => 'password_changed',
            'reference' => $buyer->email,
        ]);
    }

    public function test_guest_can_request_forgot_password_link_and_reset_password(): void
    {
        Notification::fake();

        $user = $this->userWithRole(Roles::HOD);

        // Login page links to real Forgot Password route
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot Password');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        // Create real token via Password broker and submit reset form
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Set New Password');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ResetStrongPass99',
            'password_confirmation' => 'ResetStrongPass99',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('ResetStrongPass99', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'password_reset',
            'reference' => $user->email,
        ]);
    }

    public function test_asg_cannot_self_approve_own_domestic_trf_while_peer_asg_can(): void
    {
        $asg1 = $this->loginAs(Roles::ASG1);

        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'ASG 1 Domestic Review Visit',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertRedirect(route('trf'));

        $trf = Trf::where('purpose', 'ASG 1 Domestic Review Visit')->firstOrFail();

        // ASG 1 is blocked from approving their own TRF and has 0 pending items in their queue
        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('"pendingCount":0', false)
            ->assertSee('Nothing waiting on you right now');
        $this->post(route('trf.approve', $trf))->assertForbidden();

        // Peer ASG 2 CAN see and approve ASG 1's domestic TRF
        $this->loginAs(Roles::ASG2);
        $this->get(route('notifications'))->assertSee($trf->trf_number);
        $this->post(route('trf.approve', $trf))->assertRedirect();
        $this->assertSame('approved', $trf->fresh()->status);
    }

    public function test_production_sod_toggle_blocks_admin_head_from_overriding_cfo_exception(): void
    {
        config(['app.allow_admin_approval_override' => false]);

        $admin = $this->loginAs(Roles::ADMIN_HEAD);
        $exception = ExceptionRequest::create([
            'exception_type' => 'budget_exceed',
            'justification' => 'Emergency council conference hall deposit',
            'requested_by' => $admin->id,
            'approver_queue' => Roles::CFO,
            'status' => 'pending',
        ]);

        // With strict production SoD enabled, Admin Head cannot approve CFO's exception
        $this->post(route('exceptions.approve', $exception))->assertForbidden();

        // Designated CFO can approve it cleanly
        $this->loginAs(Roles::CFO);
        $this->post(route('exceptions.approve', $exception))->assertRedirect();
        $this->assertSame('approved', $exception->fresh()->status);
    }
}
