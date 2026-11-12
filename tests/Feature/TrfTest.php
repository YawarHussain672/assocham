<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\Trf;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_trf_show_renders_travel_approval_timeline_correctly(): void
    {
        $trf = $this->submitDomesticTrf();

        $this->loginAs(Roles::ASG1);
        $response = $this->get(route('trf.show', $trf));

        $response->assertOk();
        $response->assertSee('Travel Approval Timeline');
        $response->assertSee('Requisition Initiated');
        $response->assertSee('ASG 1 or ASG 2');
        $response->assertSee('Flight &amp; Logistics Booking', false);
        $response->assertSee('Reimbursement &amp; Settlement', false);
        $response->assertSee('Endorse Travel Request');
    }

    public function test_trf_index_supports_employee_department_status_date_range_and_destination_filters(): void
    {
        $hodUser = \App\Models\User::where('role', Roles::HOD)->firstOrFail();
        $buyerUser = \App\Models\User::where('role', Roles::BUYER)->firstOrFail();

        $this->loginAs(Roles::HOD);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'route' => 'Delhi -> Mumbai',
            'travel_start' => '2026-11-10',
            'travel_end' => '2026-11-12',
        ]);
        $trf1 = Trf::latest('id')->firstOrFail();

        $this->loginAs(Roles::ASG1);
        $this->post(route('trf.approve', $trf1));

        $this->loginAs(Roles::BUYER);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'is_international' => '1',
            'route' => 'Delhi -> London',
            'travel_start' => '2026-12-05',
            'travel_end' => '2026-12-10',
        ]);
        $trf2 = Trf::latest('id')->firstOrFail();

        // Filter UI controls are rendered
        $this->get(route('trf'))
            ->assertOk()
            ->assertSee('id="trfEmployeeFilter"', false)
            ->assertSee('id="trfDepartmentFilter"', false)
            ->assertSee('id="trfStatusFilter"', false)
            ->assertSee('id="trfDateFrom"', false)
            ->assertSee('id="trfDateTo"', false)
            ->assertSee('id="trfDestinationFilter"', false)
            ->assertSee($trf1->trf_number)
            ->assertSee($trf2->trf_number);

        // Filter by Employee
        $this->get(route('trf', ['employee_id' => $hodUser->id]))
            ->assertOk()
            ->assertSee($trf1->trf_number)
            ->assertDontSee($trf2->trf_number);

        // Filter by Department
        if ($buyerUser->department && $buyerUser->department !== $hodUser->department) {
            $this->get(route('trf', ['department' => $buyerUser->department]))
                ->assertOk()
                ->assertSee($trf2->trf_number)
                ->assertDontSee($trf1->trf_number);
        }

        // Filter by Status
        $this->get(route('trf', ['status' => 'approved']))
            ->assertOk()
            ->assertSee($trf1->trf_number)
            ->assertDontSee($trf2->trf_number);

        // Filter by Date Range
        $this->get(route('trf', ['date_from' => '2026-12-01', 'date_to' => '2026-12-15']))
            ->assertOk()
            ->assertSee($trf2->trf_number)
            ->assertDontSee($trf1->trf_number);

        // Filter by Destination
        $this->get(route('trf', ['destination' => 'Mumbai']))
            ->assertOk()
            ->assertSee($trf1->trf_number)
            ->assertDontSee($trf2->trf_number);
    }

    public function test_trf_create_uses_from_and_to_city_dropdowns_for_route(): void
    {
        $this->loginAs(Roles::HOD);

        $this->get(route('trf.create'))
            ->assertOk()
            ->assertSee('name="from_city"', false)
            ->assertSee('name="to_city"', false)
            ->assertSee('New Delhi (DEL)')
            ->assertSee('Mumbai (BOM)')
            ->assertSee('Singapore (SIN)')
            ->assertDontSee('type="text" name="route"', false);

        // Same from & to city must be rejected
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'Conference',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'New Delhi (DEL)',
            'travel_start' => '2026-11-20',
            'travel_end' => '2026-11-22',
        ])->assertSessionHasErrors('to_city');

        // Valid From / To selection composes itinerary route cleanly
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'Annual Summit',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => '2026-11-20',
            'travel_end' => '2026-11-22',
        ])->assertRedirect(route('trf'));

        $trf = Trf::latest('id')->firstOrFail();
        $this->assertSame('New Delhi (DEL) → Mumbai (BOM)', $trf->route);
    }

    public function test_trf_captures_travel_mode_destinations_accommodation_and_visa_requirement(): void
    {
        $this->loginAs(Roles::HOD);

        $this->get(route('trf.create'))
            ->assertOk()
            ->assertSee('name="travel_mode"', false)
            ->assertSee('name="destinations"', false)
            ->assertSee('name="accommodation"', false)
            ->assertSee('name="accommodation_details"', false)
            ->assertSee('name="visa_required"', false)
            ->assertSee('name="visa_details"', false);

        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'European Trade Delegation',
            'travel_mode' => 'Multi-modal',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Frankfurt (FRA)',
            'destinations' => 'London (LHR), Frankfurt (FRA)',
            'is_international' => '1',
            'travel_start' => '2026-12-01',
            'travel_end' => '2026-12-08',
            'accommodation' => '1',
            'accommodation_details' => 'Hotel near Messe Frankfurt, 7 nights',
            'visa_required' => '1',
            'visa_details' => 'Schengen Business Visa needed',
            'insurance' => '1',
        ])->assertRedirect(route('trf'));

        $trf = Trf::latest('id')->firstOrFail();
        $this->assertSame('Multi-modal', $trf->travel_mode);
        $this->assertSame('New Delhi (DEL) → London (LHR) → Frankfurt (FRA)', $trf->route);
        $this->assertSame('Frankfurt (FRA), London (LHR)', $trf->destinations);
        $this->assertTrue($trf->accommodation);
        $this->assertSame('Hotel near Messe Frankfurt, 7 nights', $trf->accommodation_details);
        $this->assertTrue($trf->visa_required);
        $this->assertSame('Schengen Business Visa needed', $trf->visa_details);

        $this->get(route('trf.show', $trf))
            ->assertOk()
            ->assertSee('Multi-modal')
            ->assertSee('Frankfurt (FRA), London (LHR)')
            ->assertSee('Required / Arranged')
            ->assertSee('Hotel near Messe Frankfurt, 7 nights')
            ->assertSee('Visa Required')
            ->assertSee('Schengen Business Visa needed');

        // Destination filter matches intermediate destination too
        $this->get(route('trf', ['destination' => 'London']))
            ->assertOk()
            ->assertSee($trf->trf_number);
    }

    public function test_trf_enforces_real_world_rules_for_on_behalf_international_cities_and_toggles(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        // On behalf requires selecting a traveling employee
        $this->post(route('trf.store'), [
            'booking_type' => 'on_behalf',
            'purpose' => 'Site Visit',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Mumbai (BOM)',
            'travel_start' => '2026-12-01',
            'travel_end' => '2026-12-03',
        ])->assertSessionHasErrors('employee_id');

        // Selecting an international city auto-routes to SG even without is_international checkbox
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'APAC Forum',
            'travel_mode' => 'Flight',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Singapore (SIN)',
            'travel_start' => '2026-12-10',
            'travel_end' => '2026-12-13',
            'accommodation_details' => 'Should be cleared when toggle off',
            'visa_details' => 'Should be cleared when toggle off',
        ])->assertRedirect(route('trf'));

        $trf = Trf::latest('id')->firstOrFail();
        $this->assertTrue($trf->is_international);
        $this->assertSame('pending_sg', $trf->status);
        $this->assertSame('sg', $trf->approvals()->first()->role_required);
        $this->assertNull($trf->accommodation_details);
        $this->assertNull($trf->visa_details);
    }

    public function test_trf_template_download_supports_xlsx_and_csv(): void
    {
        $this->loginAs(Roles::HOD);

        $xlsxResponse = $this->get(route('trf.template'));
        $xlsxResponse->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string)$xlsxResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('TRF_Bulk_Import_Template.xlsx', (string)$xlsxResponse->headers->get('Content-Disposition'));

        $csvResponse = $this->get(route('trf.template', ['format' => 'csv']));
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string)$csvResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('TRF_Bulk_Import_Template.csv', (string)$csvResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('ROUTE', $csvResponse->getContent());
        $this->assertStringContainsString('New Delhi (DEL) → Mumbai (BOM)', $csvResponse->getContent());
        // Verify only 1 demo row is present (removed rows 2 and 3)
        $this->assertStringNotContainsString('Gulf Industrial & Trade Partnership', $csvResponse->getContent());
        $this->assertStringNotContainsString('Regional Chamber Member Consultation', $csvResponse->getContent());
    }

    public function test_trf_bulk_import_from_file_and_pasted_data(): void
    {
        $user = $this->loginAs(Roles::HOD);

        // 1. Upload CSV file with headers
        $csvContent = "ROUTE,PURPOSE,TRAVEL MODE,START DATE,END DATE,ADVANCE,INTERNATIONAL,ACCOMMODATION\n" .
            "New Delhi → Mumbai,Q4 Review,Flight,2026-12-05,2026-12-07,12000,No,Yes\n" .
            "Delhi → Dubai,Global Trade Meet,Flight,2026-12-15,2026-12-20,40000,Yes,Yes\n";

        $file = UploadedFile::fake()->createWithContent('bulk_trf.csv', $csvContent);

        $response = $this->post(route('trf.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('trf'));
        $response->assertSessionHas('status', '2 Travel Requisitions successfully imported and routed for approval.');
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(2, $summary['entered_count']);
        $this->assertCount(2, $summary['created_trfs']);
        $this->assertStringStartsWith('TRF-', $summary['created_trfs'][0]['trf_number']);

        $domestic = Trf::where('route', 'New Delhi → Mumbai')->firstOrFail();
        $this->assertFalse($domestic->is_international);
        $this->assertSame('pending_asg', $domestic->status);
        $this->assertSame('Flight', $domestic->travel_mode);
        $this->assertEquals(12000.0, (float)$domestic->requested_advance);

        $international = Trf::where('route', 'Delhi → Dubai')->firstOrFail();
        $this->assertTrue($international->is_international);
        $this->assertSame('pending_sg', $international->status);
        $this->assertEquals(40000.0, (float)$international->requested_advance);

        // 2. Import via pasted tabular data with 6-column format
        $pastedData = "Bengaluru - Chennai, Branch Audit, 2026-12-22, 2026-12-24, 6000, no";
        $pastedResp = $this->post(route('trf.import-excel'), [
            'csv_data' => $pastedData,
        ]);

        $pastedResp->assertRedirect(route('trf'));
        $pastedTrf = Trf::where('route', 'Bengaluru - Chennai')->firstOrFail();
        $this->assertFalse($pastedTrf->is_international);
        $this->assertSame('pending_asg', $pastedTrf->status);
        $this->assertEquals(6000.0, (float)$pastedTrf->requested_advance);

        // 3. Import via native Excel .xlsx file generated from ExcelReader
        $xlsxHeaders = ['ROUTE', 'PURPOSE', 'TRAVEL MODE', 'START DATE', 'END DATE', 'ADVANCE', 'INTERNATIONAL', 'ACCOMMODATION'];
        $xlsxRows = [
            ['Ahmedabad → London (LHR)', 'UK Trade Mission', 'Flight', '2026-12-25', '2026-12-29', 65000, 'Yes', 'Yes'],
        ];
        $xlsxRaw = \App\Services\ExcelReader::createXlsx($xlsxHeaders, $xlsxRows);
        $xlsxFile = UploadedFile::fake()->createWithContent('itinerary.xlsx', $xlsxRaw);

        $xlsxResp = $this->post(route('trf.import-excel'), [
            'file' => $xlsxFile,
        ]);

        $xlsxResp->assertRedirect(route('trf'));
        $xlsxTrf = Trf::where('route', 'Ahmedabad → London (LHR)')->firstOrFail();
        $this->assertTrue($xlsxTrf->is_international);
        $this->assertSame('pending_sg', $xlsxTrf->status);
        $this->assertEquals(65000.0, (float)$xlsxTrf->requested_advance);
    }

    public function test_trf_bulk_import_supports_optional_employee_column_and_rejects_unknown_staff(): void
    {
        $admin = $this->loginAs(Roles::ADMIN_HEAD);
        $targetUser = $this->userWithRole(Roles::BUYER);

        $csv = "EMPLOYEE,ROUTE,PURPOSE,TRAVEL MODE,START DATE,END DATE,ADVANCE,INTERNATIONAL,ACCOMMODATION\n" .
            "{$targetUser->email},Delhi → Kolkata,Zonal Review,Flight,2026-12-10,2026-12-12,8000,No,Yes\n" .
            "NonExistentUser12345,Delhi → Jaipur,Meet,Flight,2026-12-14,2026-12-15,3000,No,No\n";

        $file = UploadedFile::fake()->createWithContent('on_behalf_trfs.csv', $csv);
        $resp = $this->post(route('trf.import-excel'), ['file' => $file]);

        $resp->assertRedirect(route('trf'));
        $summary = session('import_summary');
        $this->assertEquals(1, $summary['entered_count']);
        $this->assertEquals(1, $summary['rejected_count']);
        $this->assertStringContainsString('NonExistentUser12345', $summary['rejections'][0]);

        $created = Trf::where('route', 'Delhi → Kolkata')->firstOrFail();
        $this->assertSame($targetUser->id, $created->employee_id);
        $this->assertSame('on_behalf', $created->booking_type);
    }

    public function test_trf_bulk_import_enforces_settlement_block_and_row_rejection(): void
    {
        $user = $this->loginAs(Roles::HOD);

        // Seed an overdue TRF with un-settled advance (> 30 days ago)
        Trf::create([
            'trf_number' => 'TRF-OVERDUE-1',
            'employee_id' => $user->id,
            'booking_type' => 'self',
            'purpose' => 'Old Trip',
            'route' => 'Delhi → Pune',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(60)->toDateString(),
            'travel_end' => now()->subDays(55)->toDateString(),
            'requested_advance' => 15000,
            'status' => 'approved',
        ]);

        // Attempting import should be blocked
        $response = $this->post(route('trf.import-excel'), [
            'csv_data' => "Delhi - Mumbai, Urgent Meeting, 2026-12-01, 2026-12-03, 5000, no",
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertStringContainsString('unsettled travel advance', session('errors')->first('file'));
    }

    public function test_requester_can_edit_and_resubmit_pushed_back_domestic_trf(): void
    {
        $trf = $this->submitDomesticTrf();

        // Approver (ASG 1) pushes back with comments
        $this->loginAs(Roles::ASG1);
        $this->post(route('trf.pushback', $trf), ['comments' => 'Reduce advance to ₹8,000'])
            ->assertRedirect();

        $this->assertSame('pushed_back', $trf->fresh()->status);

        // Unauthorized user cannot edit
        $this->loginAs(Roles::BUYER);
        $this->get(route('trf.edit', $trf))->assertForbidden();

        // Requester (HOD) views TRF detail, sees pushback banner & edit button
        $this->loginAs(Roles::HOD);
        $this->get(route('trf.show', $trf))
            ->assertOk()
            ->assertSee('Returned for Revision (Pushback)')
            ->assertSee('Reduce advance to ₹8,000')
            ->assertSee('Edit &amp; Resubmit TRF', false);

        // Edit page loads with pushback comments
        $this->get(route('trf.edit', $trf))
            ->assertOk()
            ->assertSee('Reduce advance to ₹8,000');

        // Requester updates and resubmits
        $this->put(route('trf.update', $trf), [
            'booking_type' => 'self',
            'purpose' => 'Revised Client Summit',
            'travel_mode' => 'Train',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Jaipur (JAI)',
            'travel_start' => now()->addDays(20)->toDateString(),
            'travel_end' => now()->addDays(22)->toDateString(),
            'requested_advance' => 8000,
            'accommodation' => 1,
            'resubmit_notes' => 'Updated mode to Train and reduced advance to ₹8,000.',
        ])->assertRedirect(route('trf.show', $trf));

        $trf->refresh();
        $this->assertSame('pending_asg', $trf->status);
        $this->assertSame('New Delhi (DEL) → Jaipur (JAI)', $trf->route);
        $this->assertSame('Train', $trf->travel_mode);
        $this->assertEquals(8000.0, (float) $trf->requested_advance);
        $this->assertSame(1, $trf->approvals()->where('status', 'pushed_back')->count());
        $this->assertSame(1, $trf->approvals()->where('status', 'pending')->count());

        // Approver (ASG 2) can now endorse the resubmitted TRF
        $this->loginAs(Roles::ASG2);
        $this->post(route('trf.approve', $trf))->assertRedirect();
        $this->assertSame('approved', $trf->fresh()->status);

        // Approved TRFs cannot be edited
        $this->loginAs(Roles::HOD);
        $this->get(route('trf.edit', $trf))->assertForbidden();
    }

    public function test_sg_can_pushback_international_trf_and_requester_resubmits_to_sg(): void
    {
        $this->loginAs(Roles::HOD);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'purpose' => 'Trade Expo',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Dubai (DXB)',
            'travel_start' => now()->addDays(30)->toDateString(),
            'travel_end' => now()->addDays(34)->toDateString(),
            'is_international' => 1,
        ])->assertRedirect();

        $trf = Trf::latest('id')->firstOrFail();
        $this->assertSame('pending_sg', $trf->status);

        // SG pushes back with review comments
        $this->loginAs(Roles::SG);
        $this->post(route('trf.pushback', $trf), ['comments' => 'Specify visa requirement and stay details'])
            ->assertRedirect();
        $this->assertSame('pushed_back', $trf->fresh()->status);

        // Requester edits & resubmits with visa details
        $this->loginAs(Roles::HOD);
        $this->put(route('trf.update', $trf), [
            'booking_type' => 'self',
            'purpose' => 'Trade Expo',
            'travel_mode' => 'Flight',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Dubai (DXB)',
            'travel_start' => now()->addDays(30)->toDateString(),
            'travel_end' => now()->addDays(34)->toDateString(),
            'is_international' => 1,
            'visa_required' => 1,
            'visa_details' => 'UAE 14-day business visa',
            'accommodation' => 1,
            'accommodation_details' => '4 nights near World Trade Centre',
            'resubmit_notes' => 'Added UAE visa and hotel stay info.',
        ])->assertRedirect(route('trf.show', $trf));

        $trf->refresh();
        $this->assertSame('pending_sg', $trf->status);
        $this->assertTrue($trf->visa_required);
        $this->assertSame('UAE 14-day business visa', $trf->visa_details);

        // SG approves
        $this->loginAs(Roles::SG);
        $this->post(route('trf.approve', $trf))->assertRedirect();
        $this->assertSame('approved', $trf->fresh()->status);
    }

    public function test_overdue_advance_blocks_trf_create_ui_on_behalf_and_bulk_import_until_reconciled(): void
    {
        $blockedUser = $this->loginAs(Roles::BUYER);
        $clearUser = $this->userWithRole(Roles::HOD);

        $overdueTrf = Trf::create([
            'trf_number' => 'TRF-ADV-901',
            'employee_id' => $blockedUser->id,
            'booking_type' => 'self',
            'purpose' => 'Overdue Conference',
            'route' => 'New Delhi (DEL) → Mumbai (BOM)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(50)->toDateString(),
            'travel_end' => now()->subDays(45)->toDateString(),
            'requested_advance' => 18500,
            'status' => 'approved',
        ]);

        // Blocked user sees overdue warning & disabled submit on TRF create + TRF index
        $this->get(route('trf'))
            ->assertOk()
            ->assertSee('New Travel Requests Blocked — Overdue Unsettled Advance', false)
            ->assertSee('TRF-ADV-901')
            ->assertSee('Settle Advance Now');

        $this->get(route('trf.create'))
            ->assertOk()
            ->assertSee('Travel Creation Blocked — Overdue Unsettled Advance', false)
            ->assertSee('Blocked — Reconcile Overdue Advance', false)
            ->assertSee('18,500.00')
            ->assertSee(route('reimb.create', ['trf_id' => $overdueTrf->id]), false);

        // Pending (unapproved) reimbursement claim does NOT lift the block
        $this->post(route('reimb.store'), [
            'trf_id' => $overdueTrf->id,
            'claim_amount' => 18500,
            'advance_amount' => 18500,
        ]);
        $claim = Reimbursement::where('trf_id', $overdueTrf->id)->firstOrFail();
        $this->assertSame('pending_cfo', $claim->status);

        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Goa (GOI)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionHasErrors('employee_id');

        // Admin Head cannot book on_behalf of blocked employee, nor bulk-import their row,
        // while clear employees remain unblocked.
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->get(route('trf.create'))
            ->assertOk()
            ->assertSee('[Blocked: Overdue Advance]');

        $this->post(route('trf.store'), [
            'booking_type' => 'on_behalf',
            'employee_id' => $blockedUser->id,
            'from_city' => 'New Delhi (DEL)',
            'to_city' => 'Goa (GOI)',
            'travel_start' => now()->addDays(10)->toDateString(),
            'travel_end' => now()->addDays(12)->toDateString(),
        ])->assertSessionHasErrors('employee_id');

        $csv = "EMPLOYEE,ROUTE,PURPOSE,TRAVEL MODE,START DATE,END DATE,ADVANCE,INTERNATIONAL,ACCOMMODATION\n" .
            "{$blockedUser->email},New Delhi (DEL) → Pune (PNQ),Summit,Flight,2026-12-10,2026-12-12,5000,No,Yes\n" .
            "{$clearUser->email},New Delhi (DEL) → Jaipur (JAI),Workshop,Train,2026-12-14,2026-12-15,3000,No,No\n";
        $file = UploadedFile::fake()->createWithContent('mixed_trfs.csv', $csv);
        $this->post(route('trf.import-excel'), ['file' => $file])->assertRedirect(route('trf'));

        $summary = session('import_summary');
        $this->assertEquals(1, $summary['entered_count']);
        $this->assertEquals(1, $summary['rejected_count']);
        $this->assertStringContainsString('TRF-ADV-901', $summary['rejections'][0]);

        // Reconcile via CFO approval -> block lifts completely
        $this->loginAs(Roles::CFO);
        $this->post(route('reimb.approve', $claim))->assertRedirect();

        $this->loginAs(Roles::BUYER);
        $this->get(route('trf.create'))
            ->assertOk()
            ->assertDontSee('18,500.00');
        $this->assertNull(Trf::findOverdueSettlementFor($blockedUser->id));
    }
}

