<?php

namespace Tests\Feature;

use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies the Vendor Empanelment chain — the first Phase 2 module, and
 * the template every other approval-chain module followed (see project
 * memory: erp-laravel-phase2-procurement). Originally verified by hand via
 * curl walking Buyer -> Admin Head -> ASG1 -> ASG2 -> CFO.
 */
class VendorEmpanelmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function submitVendor(): Vendor
    {
        $this->loginAs(Roles::BUYER);

        $this->post(route('onboarding.store'), [
            'company_name' => 'Test Traders Pvt Ltd',
            'pan' => 'AABCT1234E',
            'category' => 'Stationery',
        ])->assertRedirect(route('empanelment'));

        return Vendor::where('company_name', 'Test Traders Pvt Ltd')->firstOrFail();
    }

    public function test_only_buyer_may_submit_a_vendor(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $this->post(route('onboarding.store'), ['company_name' => 'Blocked Vendor'])
            ->assertForbidden();
    }

    public function test_submitting_a_vendor_seeds_the_four_level_chain(): void
    {
        $vendor = $this->submitVendor();

        $this->assertSame('pending_admin_head', $vendor->status);
        $this->assertCount(4, $vendor->approvals);
        $this->assertSame(
            [Roles::ADMIN_HEAD, Roles::ASG1, Roles::ASG2, Roles::CFO],
            $vendor->approvals()->orderBy('level')->pluck('role_required')->all()
        );
    }

    public function test_full_chain_walks_the_vendor_to_approved(): void
    {
        $vendor = $this->submitVendor();

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();

        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();

        $this->loginAs(Roles::ASG2);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();

        $this->loginAs(Roles::CFO);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();

        $this->assertSame('approved', $vendor->fresh()->status);
        $this->assertTrue($vendor->fresh()->isEmpanelled());
    }

    public function test_a_role_out_of_turn_cannot_approve(): void
    {
        $vendor = $this->submitVendor();

        // ASG1 is level 2; level 1 (Admin Head) hasn't acted yet.
        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor))->assertForbidden();

        $this->assertSame('pending_admin_head', $vendor->fresh()->status);
    }

    public function test_rejection_at_any_level_is_terminal(): void
    {
        $vendor = $this->submitVendor();

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.reject', $vendor), ['comments' => 'PAN mismatch'])
            ->assertRedirect();

        $this->assertSame('rejected', $vendor->fresh()->status);

        // Rejected is terminal — even the "next" role can no longer act.
        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor))->assertForbidden();
    }
}
