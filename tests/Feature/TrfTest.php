<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\Trf;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies Travel Requisition Form's two novel patterns (see project
 * memory: erp-laravel-phase3-travel): "any authenticated role can create"
 * and interchangeable ASG1/ASG2 approval via a comma-joined role_required.
 * Originally verified by hand via curl.
 */
class TrfTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function submitDomesticTrf(): Trf
    {
        $this->loginAs(Roles::HOD); // HOD has no dedicated 'trf' entry in MODULE_ACCESS — proves it's open to all
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Mumbai',
            'travel_start' => now()->addDays(30)->toDateString(),
            'travel_end' => now()->addDays(32)->toDateString(),
        ]);

        return Trf::latest('id')->firstOrFail();
    }

    public function test_domestic_trf_routes_to_either_asg(): void
    {
        $trf = $this->submitDomesticTrf();

        $this->assertSame('asg1,asg2', $trf->approvals()->first()->role_required);
    }

    public function test_either_asg_may_approve_a_domestic_trf(): void
    {
        $trf = $this->submitDomesticTrf();

        // ASG2 approves even though ASG1 is listed first — proves genuine
        // interchangeability, not a hardcoded "first role wins".
        $this->loginAs(Roles::ASG2);
        $this->post(route('trf.approve', $trf))->assertRedirect();

        $this->assertSame('approved', $trf->fresh()->status);
    }

    public function test_international_trf_routes_to_sg_only_and_asg_is_blocked(): void
    {
        $this->loginAs(Roles::CFO);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'is_international' => '1',
            'route' => 'Delhi -> Singapore',
            'travel_start' => now()->addDays(30)->toDateString(),
            'travel_end' => now()->addDays(32)->toDateString(),
        ]);
        $trf = Trf::latest('id')->firstOrFail();

        $this->assertSame('sg', $trf->approvals()->first()->role_required);

        $this->loginAs(Roles::ASG1);
        $this->post(route('trf.approve', $trf))->assertForbidden();

        $this->loginAs(Roles::SG);
        $this->post(route('trf.approve', $trf))->assertRedirect();
        $this->assertSame('approved', $trf->fresh()->status);
    }

    public function test_pushback_is_distinct_from_rejection(): void
    {
        $trf = $this->submitDomesticTrf();

        $this->loginAs(Roles::ASG1);
        $this->post(route('trf.pushback', $trf), ['comments' => 'Need updated cost estimate'])
            ->assertRedirect();

        $this->assertSame('pushed_back', $trf->fresh()->status);
        $this->assertSame('pushed_back', $trf->approvals()->first()->fresh()->status);
    }

    public function test_overdue_settlement_blocks_a_new_trf_until_reimbursement_is_approved(): void
    {
        $this->loginAs(Roles::BUYER);

        // A trip that ended well over 30 days ago, approved, never settled.
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Chennai',
            'travel_start' => now()->subDays(70)->toDateString(),
            'travel_end' => now()->subDays(68)->toDateString(),
        ]);
        $oldTrf = Trf::latest('id')->firstOrFail();

        $this->loginAs(Roles::ASG1);
        $this->post(route('trf.approve', $oldTrf));

        // Blocked.
        $this->loginAs(Roles::BUYER);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Goa',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionHasErrors('employee_id');
        $this->assertSame(1, Trf::count());

        // Settle it.
        $this->loginAs(Roles::BUYER);
        $this->post(route('reimb.store'), [
            'trf_id' => $oldTrf->id,
            'claim_amount' => 3000,
            'advance_amount' => 0,
        ]);
        $reimbursement = Reimbursement::where('trf_id', $oldTrf->id)->latest('id')->firstOrFail();

        $this->loginAs(Roles::CFO);
        $this->post(route('reimb.approve', $reimbursement));

        // Unblocked.
        $this->loginAs(Roles::BUYER);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Goa',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(2, Trf::count());
    }
}
