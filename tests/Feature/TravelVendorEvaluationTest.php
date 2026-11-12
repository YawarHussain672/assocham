<?php

namespace Tests\Feature;

use App\Models\FlightLog;
use App\Models\Trf;
use App\Models\Vendor;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class TravelVendorEvaluationTest extends TestCase
{
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_head_can_rate_travel_vendor_per_completed_trf_and_update_leaderboard(): void
    {
        $traveller = $this->userWithRole(Roles::HOD);
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $vendor = Vendor::create([
            'vendor_code' => 'VEND-EV-01',
            'company_name' => 'Apex Voyages',
            'contact_person' => 'Kiran',
            'contact_email' => 'kiran@apex.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        $completedTrf = Trf::create([
            'trf_number' => 'TRF-EV-101',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Sector Meet',
            'route' => 'New Delhi (DEL) → Kochi (COK)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(8)->toDateString(),
            'travel_end' => now()->subDays(5)->toDateString(),
            'status' => 'approved',
        ]);

        FlightLog::create([
            'ref' => 'FLT-EV-01',
            'trf_id' => $completedTrf->id,
            'type' => 'Flight ticket',
            'vendor_id' => $vendor->id,
            'cost' => 9200,
            'uploaded_by' => $admin->id,
        ]);

        // Pending rating surfaces on /traveleval, /trf/{trf}, and pre-selects on /traveleval/create
        $this->get(route('traveleval'))
            ->assertOk()
            ->assertSee('Pending Vendor Rating')
            ->assertSee('TRF-EV-101')
            ->assertSee('Apex Voyages');

        $this->get(route('trf.show', $completedTrf))
            ->assertOk()
            ->assertSee('Rate Travel Vendor (Apex Voyages)');

        $this->get(route('traveleval.create', ['trf_id' => $completedTrf->id, 'vendor_id' => $vendor->id]))
            ->assertOk()
            ->assertSee('value="' . $completedTrf->id . ':' . $vendor->id . '" selected', false);

        // Submit evaluation on Cost, Timeliness, Service, Responsiveness (+ optional Accuracy omitted -> auto-averaged)
        $this->post(route('traveleval.store'), [
            'trf_id' => $completedTrf->id,
            'vendor_id' => $vendor->id,
            'cost_score' => 5,
            'time_score' => 4,
            'quality_score' => 5,
            'responsiveness_score' => 4,
            'comments' => 'Prompt itinerary issuance and fair corporate fare.',
        ])->assertRedirect(route('traveleval'));

        $eval = VendorEvaluation::where('trf_id', $completedTrf->id)->where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame(5, $eval->cost_score);
        $this->assertSame(4, $eval->time_score);
        $this->assertSame(5, $eval->quality_score);
        $this->assertSame(4, $eval->responsiveness_score);

        // Vendor's overall rating updated to inform future bookings
        $vendor->refresh();
        $this->assertNotNull($vendor->rating);
        $this->assertEquals($eval->averageRating(), $vendor->rating);

        $this->get(route('flight.create'))
            ->assertOk()
            ->assertSee('Apex Voyages (★');

        $this->get(route('traveleval.show', $eval))
            ->assertOk()
            ->assertSee('Apex Voyages')
            ->assertSee('Prompt itinerary issuance and fair corporate fare.');

        // Duplicate rating for same (TRF, vendor) is blocked
        $this->post(route('traveleval.store'), [
            'trf_id' => $completedTrf->id,
            'vendor_id' => $vendor->id,
            'cost_score' => 4,
            'time_score' => 4,
            'quality_score' => 4,
            'responsiveness_score' => 4,
        ])->assertStatus(422);
    }

    public function test_future_trf_unbooked_vendor_and_non_admin_are_blocked(): void
    {
        $traveller = $this->userWithRole(Roles::HOD);
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $bookedVendor = Vendor::create([
            'vendor_code' => 'VEND-EV-02',
            'company_name' => 'Booked Travels',
            'contact_person' => 'Meera',
            'contact_email' => 'meera@booked.test',
            'category' => 'Travel',
            'status' => 'empanelled',
            'submitted_by' => $traveller->id,
        ]);

        $otherVendor = Vendor::create([
            'vendor_code' => 'VEND-EV-03',
            'company_name' => 'Unbooked Travels',
            'contact_person' => 'Raj',
            'contact_email' => 'raj@unbooked.test',
            'category' => 'Travel',
            'status' => 'empanelled',
            'submitted_by' => $traveller->id,
        ]);

        $futureTrf = Trf::create([
            'trf_number' => 'TRF-EV-FUT',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Future Trip',
            'route' => 'New Delhi (DEL) → Goa (GOI)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->addDays(5)->toDateString(),
            'travel_end' => now()->addDays(8)->toDateString(),
            'status' => 'approved',
        ]);

        FlightLog::create([
            'ref' => 'FLT-EV-FUT',
            'trf_id' => $futureTrf->id,
            'type' => 'Flight ticket',
            'vendor_id' => $bookedVendor->id,
            'cost' => 7000,
            'uploaded_by' => $admin->id,
        ]);

        // Future TRF cannot be rated
        $this->post(route('traveleval.store'), [
            'trf_id' => $futureTrf->id,
            'vendor_id' => $bookedVendor->id,
            'cost_score' => 5,
            'time_score' => 5,
            'quality_score' => 5,
            'responsiveness_score' => 5,
        ])->assertStatus(422);

        $pastTrf = Trf::create([
            'trf_number' => 'TRF-EV-PAST',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Past Trip',
            'route' => 'New Delhi (DEL) → Jaipur (JAI)',
            'travel_mode' => 'Train',
            'is_international' => false,
            'travel_start' => now()->subDays(6)->toDateString(),
            'travel_end' => now()->subDays(4)->toDateString(),
            'status' => 'approved',
        ]);

        FlightLog::create([
            'ref' => 'FLT-EV-PAST',
            'trf_id' => $pastTrf->id,
            'type' => 'Flight ticket',
            'vendor_id' => $bookedVendor->id,
            'cost' => 4500,
            'uploaded_by' => $admin->id,
        ]);

        // Unbooked vendor cannot be rated against pastTrf
        $this->post(route('traveleval.store'), [
            'trf_id' => $pastTrf->id,
            'vendor_id' => $otherVendor->id,
            'cost_score' => 5,
            'time_score' => 5,
            'quality_score' => 5,
            'responsiveness_score' => 5,
        ])->assertStatus(422);

        // Non-Admin Head cannot create/submit evaluation
        $this->loginAs(Roles::BUYER);
        $this->get(route('traveleval.create'))->assertForbidden();
        $this->post(route('traveleval.store'), [
            'trf_id' => $pastTrf->id,
            'vendor_id' => $bookedVendor->id,
            'cost_score' => 5,
            'time_score' => 5,
            'quality_score' => 5,
            'responsiveness_score' => 5,
        ])->assertForbidden();
    }

    public function test_vendor_historical_performance_accumulates_average_score_trend_and_trip_count(): void
    {
        $traveller = $this->userWithRole(Roles::HOD);
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $vendor = Vendor::create([
            'vendor_code' => 'VEND-HIST-01',
            'company_name' => 'Meridian Corporate Travel',
            'contact_person' => 'Ananya',
            'contact_email' => 'ananya@meridian.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        // Earlier trip rated 3.0
        $trf1 = Trf::create([
            'trf_number' => 'TRF-HIST-1',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Q1 Review',
            'route' => 'New Delhi (DEL) → Pune (PNQ)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(25)->toDateString(),
            'travel_end' => now()->subDays(22)->toDateString(),
            'status' => 'approved',
        ]);
        FlightLog::create([
            'ref' => 'FLT-HIST-1',
            'trf_id' => $trf1->id,
            'type' => 'Flight ticket',
            'vendor_id' => $vendor->id,
            'cost' => 6500,
            'uploaded_by' => $admin->id,
        ]);
        $this->post(route('traveleval.store'), [
            'trf_id' => $trf1->id,
            'vendor_id' => $vendor->id,
            'cost_score' => 3,
            'time_score' => 3,
            'quality_score' => 3,
            'responsiveness_score' => 3,
            'comments' => 'Initial trip baseline service.',
        ])->assertRedirect(route('traveleval'));

        // Later trip rated 4.8 -> Improving (+1.8), cumulative average 3.9
        $trf2 = Trf::create([
            'trf_number' => 'TRF-HIST-2',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Q2 Summit',
            'route' => 'New Delhi (DEL) → Chennai (MAA)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(10)->toDateString(),
            'travel_end' => now()->subDays(8)->toDateString(),
            'status' => 'approved',
        ]);
        FlightLog::create([
            'ref' => 'FLT-HIST-2',
            'trf_id' => $trf2->id,
            'type' => 'Flight ticket',
            'vendor_id' => $vendor->id,
            'cost' => 8200,
            'uploaded_by' => $admin->id,
        ]);
        $this->post(route('traveleval.store'), [
            'trf_id' => $trf2->id,
            'vendor_id' => $vendor->id,
            'cost_score' => 5,
            'time_score' => 5,
            'quality_score' => 5,
            'responsiveness_score' => 4,
            'accuracy_score' => 5,
            'comments' => 'Much faster turnaround and lower fare.',
        ])->assertRedirect(route('traveleval'));

        // Leaderboard shows trend pill + link to vendor history
        $this->get(route('traveleval'))
            ->assertOk()
            ->assertSee('Improving')
            ->assertSee(route('traveleval.vendor-history', $vendor), false);

        // Historical Performance page shows accumulated metrics, trend, spend, and all rated trips
        $this->get(route('traveleval.vendor-history', $vendor))
            ->assertOk()
            ->assertSee('Meridian Corporate Travel')
            ->assertSee('3.9')
            ->assertSee('Improving')
            ->assertSee('+1.8')
            ->assertSee('14,700.00')
            ->assertSee('TRF-HIST-1')
            ->assertSee('TRF-HIST-2')
            ->assertSee('Initial trip baseline service.')
            ->assertSee('Much faster turnaround and lower fare.');
    }

    public function test_pending_vendor_rating_banner_stays_compact_and_uses_modal_when_many_pending(): void
    {
        $traveller = $this->userWithRole(Roles::HOD);
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $vendor = Vendor::create([
            'vendor_code' => 'VEND-CAP-01',
            'company_name' => 'Capable Travels',
            'contact_person' => 'Riya',
            'contact_email' => 'riya@capable.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        foreach ([1, 2, 3, 4] as $i) {
            $trf = Trf::create([
                'trf_number' => "TRF-CAP-{$i}",
                'employee_id' => $traveller->id,
                'booking_type' => 'self',
                'purpose' => "Trip {$i}",
                'route' => 'New Delhi (DEL) → Mumbai (BOM)',
                'travel_mode' => 'Flight',
                'is_international' => false,
                'travel_start' => now()->subDays(10 + $i)->toDateString(),
                'travel_end' => now()->subDays(8 + $i)->toDateString(),
                'status' => 'approved',
            ]);
            FlightLog::create([
                'ref' => "FLT-CAP-{$i}",
                'trf_id' => $trf->id,
                'type' => 'Flight ticket',
                'vendor_id' => $vendor->id,
                'cost' => 5000,
                'uploaded_by' => $admin->id,
            ]);
        }

        $this->get(route('traveleval'))
            ->assertOk()
            ->assertSee('4 Completed Bookings Pending Vendor Rating')
            ->assertSee('+2 more')
            ->assertSee('View All (4)')
            ->assertSee('id="pendingEvalModal"', false);
    }

    public function test_booking_screen_surfaces_vendor_ratings_orders_preferred_first_and_warns_on_low_performers(): void
    {
        $traveller = $this->userWithRole(Roles::HOD);
        $admin = $this->loginAs(Roles::ADMIN_HEAD);

        $lowVendor = Vendor::create([
            'vendor_code' => 'VEND-BOOK-LOW',
            'company_name' => 'Alpha Low Travels',
            'contact_person' => 'Rohan',
            'contact_email' => 'rohan@low.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        $unratedVendor = Vendor::create([
            'vendor_code' => 'VEND-BOOK-NEW',
            'company_name' => 'Mid New Agency',
            'contact_person' => 'Meera',
            'contact_email' => 'meera@new.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        $topVendor = Vendor::create([
            'vendor_code' => 'VEND-BOOK-TOP',
            'company_name' => 'Zenith Preferred Partners',
            'contact_person' => 'Kavita',
            'contact_email' => 'kavita@zenith.test',
            'category' => 'Travel',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $traveller->id,
        ]);

        $completedTop = Trf::create([
            'trf_number' => 'TRF-BK-TOP',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Executive visit',
            'route' => 'Delhi -> Bengaluru',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(14)->toDateString(),
            'travel_end' => now()->subDays(12)->toDateString(),
            'status' => 'approved',
        ]);
        VendorEvaluation::create([
            'trf_id' => $completedTop->id,
            'vendor_id' => $topVendor->id,
            'cost_score' => 5,
            'time_score' => 5,
            'quality_score' => 5,
            'responsiveness_score' => 4,
            'accuracy_score' => 5,
            'comments' => 'Always fast and fair pricing.',
            'rated_by' => $admin->id,
        ]);

        $completedLow = Trf::create([
            'trf_number' => 'TRF-BK-LOW',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Branch audit',
            'route' => 'Delhi -> Jaipur',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(9)->toDateString(),
            'travel_end' => now()->subDays(7)->toDateString(),
            'status' => 'approved',
        ]);
        VendorEvaluation::create([
            'trf_id' => $completedLow->id,
            'vendor_id' => $lowVendor->id,
            'cost_score' => 2,
            'time_score' => 2,
            'quality_score' => 3,
            'responsiveness_score' => 2,
            'accuracy_score' => 2,
            'comments' => 'Repeated delays and billing mistakes.',
            'rated_by' => $admin->id,
        ]);

        Trf::create([
            'trf_number' => 'TRF-BK-UPCOMING',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Client workshop',
            'route' => 'Delhi -> Pune',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->addDays(5)->toDateString(),
            'travel_end' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->get(route('flight.create'));

        $response->assertOk()
            ->assertSeeInOrder([
                'Zenith Preferred Partners (★ 4.8/5 — Preferred)',
                'Alpha Low Travels (★ 2.2/5 — Low Performing)',
                'Mid New Agency (Unrated)',
            ])
            ->assertSee('rated below 3.0/5.0')
            ->assertSee(route('traveleval.vendor-history', $topVendor), false)
            ->assertSee(route('traveleval.vendor-history', $lowVendor), false)
            ->assertSee('"tier_key":"low"', false)
            ->assertSee('"tier_key":"preferred"', false);
    }
}

