<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * Every feature test authenticates as one of the ten seeded demo users
 * (UserSeeder) rather than building an ad-hoc User — matches how every
 * module was actually verified during development (curl + these exact
 * demo logins), and exercises the real Roles::MODULE_ACCESS/approval-role
 * matching against a real role slug, not a hand-picked fake one.
 */
trait InteractsWithDemoUsers
{
    protected function userWithRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    protected function loginAs(string $role): User
    {
        $user = $this->userWithRole($role);
        $this->actingAs($user);

        return $user;
    }
}
