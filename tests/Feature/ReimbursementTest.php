<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\Trf;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class ReimbursementTest extends TestCase
{
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_employee_can_file_settlement_claim_with_receipts_against_completed_trf(): void
    {
        Storage::fake('public');

        $employee = $this->loginAs(Roles::BUYER);

        $completedTrf = Trf::create([
            'trf_number' => 'TRF-DONE-101',
            'employee_id' => $employee->id,
            'booking_type' => 'self',
            'purpose' => 'Completed Conference',
            'route' => 'New Delhi (DEL) → Chennai (MAA)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(10)->toDateString(),
            'travel_end' => now()->subDays(8)->toDateString(),
            'requested_advance' => 10000,
            'status' => 'approved',
        ]);

        // Index highlights pending completed TRF
        $this->get(route('reimb'))
            ->assertOk()
            ->assertSee('Completed Trip Pending Settlement')
            ->assertSee('TRF-DONE-101');

        // Create page shows completed TRF
        $this->get(route('reimb.create'))
            ->assertOk()
            ->assertSee('TRF-DONE-101');

        // Submit settlement claim with receipt bills
        $bill1 = UploadedFile::fake()->create('hotel-tax-invoice.pdf', 110, 'application/pdf');
        $bill2 = UploadedFile::fake()->image('cab-receipt.png', 200, 120);

        $this->post(route('reimb.store'), [
            'trf_id' => $completedTrf->id,
            'claim_amount' => 14250,
            'advance_amount' => 10000,
            'reason' => 'Hotel stay + airport cab + per-diem',
            'receipts' => [$bill1, $bill2],
        ])->assertRedirect(route('reimb'));

        $claim = Reimbursement::where('trf_id', $completedTrf->id)->firstOrFail();
        $this->assertSame('pending_cfo', $claim->status);
        $this->assertSame('standard', $claim->routing_flag);
        $this->assertEquals(4250.0, (float) $claim->balance_amount);
        $this->assertCount(2, $claim->attachments);

        // Duplicate claim against same active/settled TRF is blocked
        $this->post(route('reimb.store'), [
            'trf_id' => $completedTrf->id,
            'claim_amount' => 5000,
        ])->assertSessionHasErrors('trf_id');

        // Claim show displays receipts & serves files
        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee('hotel-tax-invoice.pdf')
            ->assertSee('cab-receipt.png')
            ->assertSee('Hotel stay + airport cab + per-diem');

        $firstBill = $claim->attachments->first();
        $this->get(route('reimb.attachments.show', [$claim, $firstBill]))->assertOk();

        // CFO sees Action dropdown (Approve & Settle / Reject / Decline) on registry and approves settlement
        $this->loginAs(Roles::CFO);
        $this->get(route('reimb'))
            ->assertOk()
            ->assertSee('Approve &amp; Settle', false)
            ->assertSee('Reject / Decline', false);

        $this->post(route('reimb.approve', $claim))->assertRedirect();
        $this->assertSame('approved', $claim->fresh()->status);

        // Employee no longer sees pending settlement banner
        $this->loginAs(Roles::BUYER);
        $this->get(route('reimb'))
            ->assertOk()
            ->assertDontSee('Pending Settlement');
    }

    public function test_upcoming_trf_cannot_be_claimed_before_completion_and_rejected_claim_can_be_refiled(): void
    {
        $employee = $this->loginAs(Roles::HOD);

        $upcomingTrf = Trf::create([
            'trf_number' => 'TRF-FUT-201',
            'employee_id' => $employee->id,
            'booking_type' => 'self',
            'purpose' => 'Upcoming Summit',
            'route' => 'New Delhi (DEL) → Bengaluru (BLR)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->addDays(5)->toDateString(),
            'travel_end' => now()->addDays(7)->toDateString(),
            'requested_advance' => 5000,
            'status' => 'approved',
        ]);

        // Blocked before trip finishes
        $this->post(route('reimb.store'), [
            'trf_id' => $upcomingTrf->id,
            'claim_amount' => 6000,
            'advance_amount' => 5000,
        ])->assertSessionHasErrors('trf_id');

        $completedTrf = Trf::create([
            'trf_number' => 'TRF-PAST-202',
            'employee_id' => $employee->id,
            'booking_type' => 'self',
            'purpose' => 'Past Visit',
            'route' => 'New Delhi (DEL) → Jaipur (JAI)',
            'travel_mode' => 'Train',
            'is_international' => false,
            'travel_start' => now()->subDays(5)->toDateString(),
            'travel_end' => now()->subDays(3)->toDateString(),
            'requested_advance' => 4000,
            'status' => 'approved',
        ]);

        $this->post(route('reimb.store'), [
            'trf_id' => $completedTrf->id,
            'claim_amount' => 4500,
            'advance_amount' => 4000,
        ])->assertRedirect(route('reimb'));

        $firstClaim = Reimbursement::where('trf_id', $completedTrf->id)->firstOrFail();

        // CFO rejects first claim
        $this->loginAs(Roles::CFO);
        $this->post(route('reimb.reject', $firstClaim), ['comments' => 'Missing hotel receipt'])->assertRedirect();
        $this->assertSame('rejected', $firstClaim->fresh()->status);

        // Employee can re-file a corrected claim
        $this->loginAs(Roles::HOD);
        $this->post(route('reimb.store'), [
            'trf_id' => $completedTrf->id,
            'claim_amount' => 4200,
            'advance_amount' => 4000,
        ])->assertRedirect(route('reimb'));

        $this->assertSame(2, Reimbursement::where('trf_id', $completedTrf->id)->count());
    }

    public function test_claim_supports_itemised_expenses_with_per_item_bill_attachments(): void
    {
        Storage::fake('public');

        $employee = $this->loginAs(Roles::BUYER);

        $trf = Trf::create([
            'trf_number' => 'TRF-ITEM-301',
            'employee_id' => $employee->id,
            'booking_type' => 'self',
            'purpose' => 'Itemised Audit Trip',
            'route' => 'New Delhi (DEL) → Pune (PNQ)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(12)->toDateString(),
            'travel_end' => now()->subDays(9)->toDateString(),
            'requested_advance' => 5000,
            'status' => 'approved',
        ]);

        // Incomplete item row is rejected
        $this->post(route('reimb.store'), [
            'trf_id' => $trf->id,
            'advance_amount' => 5000,
            'items' => [
                ['expense_date' => now()->subDays(10)->toDateString(), 'category' => 'Hotel / Accommodation', 'amount' => ''],
            ],
        ])->assertSessionHasErrors('items.0.amount');

        $hotelBill = UploadedFile::fake()->create('taj-hotel.pdf', 120, 'application/pdf');
        $cabBill = UploadedFile::fake()->image('uber-receipt.jpg', 200, 120);

        $this->post(route('reimb.store'), [
            'trf_id' => $trf->id,
            'advance_amount' => 5000,
            'items' => [
                [
                    'expense_date' => now()->subDays(11)->toDateString(),
                    'category' => 'Hotel / Accommodation',
                    'description' => '2 nights corporate tariff',
                    'amount' => 6400,
                    'bill' => $hotelBill,
                ],
                [
                    'expense_date' => now()->subDays(10)->toDateString(),
                    'category' => 'Local Conveyance / Cab',
                    'description' => 'Airport pickup & drop',
                    'amount' => 850,
                    'bill' => $cabBill,
                ],
            ],
        ])->assertRedirect(route('reimb'));

        $claim = Reimbursement::where('trf_id', $trf->id)->firstOrFail();
        // Auto-summed from itemised rows (6400 + 850 = 7250), net balance 7250 - 5000 = 2250
        $this->assertEquals(7250.0, (float) $claim->claim_amount);
        $this->assertEquals(2250.0, (float) $claim->balance_amount);
        $this->assertCount(2, $claim->items);

        $firstItem = $claim->items->first();
        $this->assertSame('Hotel / Accommodation', $firstItem->category);
        $this->assertSame('taj-hotel.pdf', $firstItem->bill_name);
        $this->assertNotNull($firstItem->bill_path);

        // Claim show displays itemised breakdown and per-item bills; bill endpoint serves file
        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee('Itemised Expense Breakdown')
            ->assertSee('Hotel / Accommodation')
            ->assertSee('Local Conveyance / Cab')
            ->assertSee('2 nights corporate tariff')
            ->assertSee('View Bill');

        $this->get(route('reimb.items.bill', [$claim, $firstItem]))->assertOk();

        // Registry shows item & bill counts cleanly
        $this->get(route('reimb'))
            ->assertOk()
            ->assertSee('2 items')
            ->assertSee('2 bills');
    }

    public function test_advance_adjustment_shows_refundable_balance_prevents_tampering_and_releases_travel_block_when_settled(): void
    {
        $employee = $this->loginAs(Roles::BUYER);

        $overdueTrf = Trf::create([
            'trf_number' => 'TRF-SET-401',
            'employee_id' => $employee->id,
            'booking_type' => 'self',
            'purpose' => 'Trade Delegation',
            'route' => 'New Delhi (DEL) → Hyderabad (HYD)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(45)->toDateString(),
            'travel_end' => now()->subDays(40)->toDateString(),
            'requested_advance' => 15000,
            'status' => 'approved',
        ]);

        // Travel block is active
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionHasErrors('employee_id');

        // Even if client passes advance_amount = 0, server adjusts against TRF advance (15000)
        $this->post(route('reimb.store'), [
            'trf_id' => $overdueTrf->id,
            'advance_amount' => 0,
            'items' => [
                [
                    'expense_date' => now()->subDays(42)->toDateString(),
                    'category' => 'Hotel / Accommodation',
                    'description' => 'Stay bill',
                    'amount' => 11200,
                ],
            ],
        ])->assertRedirect(route('reimb'));

        $claim = Reimbursement::where('trf_id', $overdueTrf->id)->firstOrFail();
        $this->assertEquals(15000.0, (float) $claim->advance_amount);
        $this->assertEquals(-3800.0, (float) $claim->balance_amount);

        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee('Refundable by Traveler')
            ->assertSee('3,800.00');

        $this->get(route('reimb'))
            ->assertOk()
            ->assertSee('Refundable');

        // Pending claim still keeps block active until CFO settles
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionHasErrors('employee_id');

        // CFO settles the claim -> block released
        $this->loginAs(Roles::CFO);
        $this->post(route('reimb.approve', $claim))->assertRedirect();

        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee('Settled &amp; Reconciled', false)
            ->assertSee('travel block cleared');

        // Employee can now create a new TRF cleanly
        $this->loginAs(Roles::BUYER);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'Post-Settlement Trip',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull(Trf::findOverdueSettlementFor($employee->id));
    }
}

