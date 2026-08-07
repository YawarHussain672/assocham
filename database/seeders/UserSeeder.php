<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

/**
 * One demo login per role from the spec (see project memory:
 * assocham-portal-spec). All demo accounts share the password "password" —
 * fine for local dev, must be rotated before any real deployment.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['role' => Roles::PR_SR_EXEC, 'name' => 'Priya Nair', 'email' => 'priya.nair@assocham.test', 'department' => 'Projects'],
            ['role' => Roles::BUYER, 'name' => 'Rohit Verma', 'email' => 'rohit.verma@assocham.test', 'department' => 'IT'],
            ['role' => Roles::ADMIN_HEAD, 'name' => 'Rahul Mehta', 'email' => 'rahul.mehta@assocham.test', 'department' => null],
            ['role' => Roles::PR_APPROVER_L1, 'name' => 'Basu', 'email' => 'basu@assocham.test', 'department' => null],
            ['role' => Roles::PR_APPROVER_L2, 'name' => 'Ambika', 'email' => 'ambika@assocham.test', 'department' => null],
            ['role' => Roles::ASG1, 'name' => 'Anil Kulkarni', 'email' => 'asg1@assocham.test', 'department' => null],
            ['role' => Roles::ASG2, 'name' => 'Deepak Nair', 'email' => 'asg2@assocham.test', 'department' => null],
            ['role' => Roles::CFO, 'name' => 'Sanjay Menon', 'email' => 'sanjay.menon@assocham.test', 'department' => null],
            ['role' => Roles::SG, 'name' => 'Meenakshi Rao', 'email' => 'meenakshi.rao@assocham.test', 'department' => null],
            ['role' => Roles::HOD, 'name' => 'Vikram Shah', 'email' => 'hod@assocham.test', 'department' => 'Operations'],
        ];

        foreach ($users as $attrs) {
            User::updateOrCreate(
                ['email' => $attrs['email']],
                [
                    'name' => $attrs['name'],
                    'role' => $attrs['role'],
                    'department' => $attrs['department'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
