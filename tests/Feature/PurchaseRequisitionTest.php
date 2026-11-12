<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

/**
 * Codifies Purchase Requisition's budget reserve/release + L1-L4 chain
 * (see project memory: erp-laravel-phase2-procurement). Originally
 * verified by hand via curl: within-budget PR reserved the right amount,
 * over-budget PR was blocked pre-persistence, full chain walked in order,
 * rejection released the reserved budget.
 */
class PurchaseRequisitionTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function submitPr(float $unitCost = 10000, int $quantity = 1): \Illuminate\Testing\TestResponse
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        return $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'items' => [
                ['item_name' => 'Laptops', 'quantity' => $quantity, 'unit_cost' => $unitCost, 'gst_percent' => 18],
            ],
        ]);
    }

    public function test_within_budget_pr_reserves_the_gst_inclusive_amount(): void
    {
        $budgetBefore = DepartmentBudget::where('department', 'Finance')
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->first();
        $utilisedBefore = (float) $budgetBefore->utilised_amount;

        $this->submitPr(unitCost: 10000)->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('department', 'Finance')->firstOrFail();
        $this->assertEqualsWithDelta(11800.0, $pr->estimatedCostInclGst(), 0.01); // 10000 * 1.18

        $budgetAfter = $budgetBefore->fresh();
        $this->assertEqualsWithDelta($utilisedBefore + 11800, (float) $budgetAfter->utilised_amount, 0.01);
    }

    public function test_over_budget_pr_is_blocked_and_nothing_is_persisted(): void
    {
        // Finance's seeded allocation (900,000) is far below this.
        $this->submitPr(unitCost: 10_000_000)->assertSessionHasErrors('budget');

        $this->assertSame(0, PurchaseRequisition::count());
    }

    public function test_full_l1_l4_chain_approves_in_order(): void
    {
        $this->submitPr();
        $pr = PurchaseRequisition::firstOrFail();

        $this->loginAs(Roles::PR_APPROVER_L1); // Basu
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::PR_APPROVER_L2); // Ambika
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::SG);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->assertSame('approved', $pr->fresh()->status);
    }

    public function test_pr_stores_and_displays_optional_descriptions(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        $response = $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'Finance Workstation Modernization',
            'description' => 'General office equipment upgrade for Q4',
            'items' => [
                [
                    'item_name' => 'Dell Latitude 5540',
                    'description' => '16GB RAM, 512GB SSD, Intel Core i7 with 3Y on-site warranty',
                    'quantity' => 2,
                    'unit_cost' => 5000,
                    'gst_percent' => 18,
                ],
                [
                    'item_name' => 'USB-C Docking Station',
                    'description' => null,
                    'quantity' => 2,
                    'unit_cost' => 1000,
                    'gst_percent' => 18,
                ],
            ],
        ]);

        $response->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('department', 'Finance')->with('items')->firstOrFail();
        $this->assertSame('Finance Workstation Modernization', $pr->title);
        $this->assertSame('General office equipment upgrade for Q4', $pr->description);
        $this->assertCount(2, $pr->items);
        $this->assertSame('16GB RAM, 512GB SSD, Intel Core i7 with 3Y on-site warranty', $pr->items[0]->description);
        $this->assertNull($pr->items[1]->description);

        $indexResponse = $this->get(route('requisition'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Finance Workstation Modernization');

        $showResponse = $this->get(route('requisition.show', $pr));
        $showResponse->assertOk();
        $showResponse->assertSee('Finance Workstation Modernization');
        $showResponse->assertSee('General office equipment upgrade for Q4');
        $showResponse->assertSee('16GB RAM, 512GB SSD, Intel Core i7 with 3Y on-site warranty');
    }

    public function test_rejection_releases_the_reserved_budget(): void
    {
        $this->submitPr(unitCost: 5000);
        $pr = PurchaseRequisition::firstOrFail();

        $budget = DepartmentBudget::where('department', 'Finance')
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())->firstOrFail();
        $utilisedAfterSubmit = (float) $budget->fresh()->utilised_amount;

        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.reject', $pr), ['comments' => 'Not needed this quarter'])
            ->assertRedirect();

        $this->assertSame('rejected', $pr->fresh()->status);
        $released = $utilisedAfterSubmit - (float) $budget->fresh()->utilised_amount;
        $this->assertEqualsWithDelta(5900.0, $released, 0.01); // 5000 * 1.18
    }

    public function test_pr_show_renders_timeline_without_static_progress_bar(): void
    {
        $this->submitPr();
        $pr = PurchaseRequisition::firstOrFail();

        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get(route('requisition.show', $pr));
        $res->assertOk();
        $res->assertSee('Approval Chain Timeline');
        $res->assertSee('Endorsed');
        $res->assertDontSee('travel-lifecycle-bar');
        $res->assertDontSee('bar-segment');
    }

    public function test_pr_show_never_renders_duplicate_approval_steps(): void
    {
        $this->submitPr();
        $pr = PurchaseRequisition::firstOrFail();

        // Simulate old ghost approvals for the same PR ID
        $pr->approvals()->create(['level' => 1, 'role_required' => Roles::PR_APPROVER_L1, 'status' => 'approved', 'comments' => 'Ghost']);
        $pr->approvals()->create(['level' => 2, 'role_required' => Roles::PR_APPROVER_L2, 'status' => 'approved', 'comments' => 'Ghost']);

        $this->loginAs(Roles::PR_SR_EXEC);
        $res = $this->get(route('requisition.show', $pr));
        $res->assertOk();
        // Badge should count exactly 4 total levels, not 6
        $res->assertSee('/4 Endorsed');
        $res->assertDontSee('/6 Endorsed');
    }

    public function test_complete_version_history_tracks_creation_approval_rejection_and_resubmission(): void
    {
        $this->submitPr(unitCost: 2000, quantity: 2);
        $pr = PurchaseRequisition::firstOrFail();

        // Check Version 1 created
        $this->assertCount(1, $pr->versions);
        $v1 = $pr->versions->first();
        $this->assertSame(1, $v1->version_number);
        $this->assertSame('created', $v1->event);

        // Approve at L1 -> creates Version 2
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr), ['comments' => 'L1 looks fine'])->assertRedirect();
        $pr->refresh();
        $this->assertCount(2, $pr->versions);
        $v2 = $pr->versions->first();
        $this->assertSame(2, $v2->version_number);
        $this->assertSame('approved', $v2->event);
        $this->assertSame('L1 looks fine', $v2->comments);

        // Reject at L2 -> creates Version 3
        $this->loginAs(Roles::PR_APPROVER_L2);
        $this->post(route('requisition.reject', $pr), ['comments' => 'Price is high, please negotiate'])->assertRedirect();
        $pr->refresh();
        $this->assertSame('rejected', $pr->status);
        $this->assertCount(3, $pr->versions);
        $v3 = $pr->versions->first();
        $this->assertSame(3, $v3->version_number);
        $this->assertSame('rejected', $v3->event);
        $this->assertSame('Price is high, please negotiate', $v3->comments);

        // Edit and Re-submit -> creates Version 4 and restarts approval at L1
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->put(route('requisition.update', $pr), [
            'department' => 'Finance',
            'title' => 'Renegotiated Laptops',
            'resubmit_notes' => 'Reduced unit cost to 1800 per unit',
            'items' => [
                [
                    'item_name' => 'Laptops',
                    'quantity' => 2,
                    'unit_cost' => 1800,
                    'gst_percent' => 18,
                ],
            ],
        ])->assertRedirect(route('requisition.show', $pr));

        $pr->refresh();
        $this->assertSame('pending_l1', $pr->status);
        $this->assertSame('Renegotiated Laptops', $pr->title);
        $this->assertCount(4, $pr->versions);
        $v4 = $pr->versions->first();
        $this->assertSame(4, $v4->version_number);
        $this->assertSame('resubmitted', $v4->event);
        $this->assertSame('Reduced unit cost to 1800 per unit', $v4->comments);

        // Show page renders complete version history and action details
        $showRes = $this->get(route('requisition.show', $pr));
        $showRes->assertOk();
        $showRes->assertSee('Complete Version &amp; Revision History', false);
        $showRes->assertSee('v4');
        $showRes->assertSee('v3');
        $showRes->assertSee('v2');
        $showRes->assertSee('v1');
        $showRes->assertSee('Reduced unit cost to 1800 per unit');
        $showRes->assertSee('Price is high, please negotiate');
    }

    public function test_pr_directory_renders_role_wise_actions_and_modals(): void
    {
        $this->submitPr(5000, 1)->assertRedirect();
        $pr = PurchaseRequisition::latest('id')->firstOrFail();

        // When logged in as L1 approver: directory must show Action dropdown with Approve L1 & Decline modals
        $this->loginAs(Roles::PR_APPROVER_L1);
        $res = $this->get(route('requisition'));
        $res->assertOk();
        $res->assertSee('Action');
        $res->assertSee('Approve (L1)');
        $res->assertSee('Reject / Decline');
        $res->assertSee('tableApproveModal-' . $pr->id);
        $res->assertSee('tableRejectModal-' . $pr->id);

        // When logged in as non-approver user who didn't create it: clean 'View' button
        $otherUser = \App\Models\User::factory()->create(['role' => Roles::HOD]);
        $this->actingAs($otherUser);
        $resOther = $this->get(route('requisition'));
        $resOther->assertOk();
        $resOther->assertDontSee('tableApproveModal-' . $pr->id);
    }

    public function test_line_item_partial_approval_allows_selective_approval_and_releases_rejected_budget(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'Finance',
            'title' => 'Multi-item Equipment Order',
            'items' => [
                ['item_name' => 'Item A (Approved)', 'quantity' => 1, 'unit_cost' => 10000, 'gst_percent' => 18],
                ['item_name' => 'Item B (Rejected)', 'quantity' => 1, 'unit_cost' => 20000, 'gst_percent' => 18],
            ],
        ])->assertRedirect();

        $pr = PurchaseRequisition::where('title', 'Multi-item Equipment Order')->firstOrFail();
        $this->assertCount(2, $pr->items);

        $itemA = $pr->items->where('item_name', 'Item A (Approved)')->first();
        $itemB = $pr->items->where('item_name', 'Item B (Rejected)')->first();

        $budgetBeforeL1 = DepartmentBudget::where('department', 'Finance')
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->first();

        // L1 Approver partially approves Item A and rejects Item B
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr), [
            'comments' => 'Item A cleared, Item B rejected due to specifications.',
            'line_decisions' => [
                $itemA->id => 'approved',
                $itemB->id => 'rejected',
            ],
        ])->assertRedirect();

        $pr->refresh();
        $itemA->refresh();
        $itemB->refresh();

        $this->assertSame('approved', $itemA->status);
        $this->assertSame('rejected', $itemB->status);

        // Item B's budget (20,000 + 18% = 23,600) is released back to Finance
        $budgetAfterL1 = $budgetBeforeL1->fresh();
        $this->assertEqualsWithDelta(
            (float) $budgetBeforeL1->utilised_amount - 23600.0,
            (float) $budgetAfterL1->utilised_amount,
            0.01
        );

        // Complete the remaining levels (L2, CFO, SG)
        $this->loginAs(Roles::PR_APPROVER_L2);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::CFO);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $this->loginAs(Roles::SG);
        $this->post(route('requisition.approve', $pr))->assertRedirect();

        $pr->refresh();
        $this->assertSame('partially_approved', $pr->status);

        // RFQ was created automatically
        $this->assertNotNull($pr->rfq);

        // RFQ view displays only approved lines (Item A), not rejected lines (Item B)
        $this->loginAs(Roles::BUYER);
        $rfqRes = $this->get(route('rfq.show', $pr->rfq));
        $rfqRes->assertOk();
        $rfqRes->assertSee('Item A (Approved)');
        $rfqRes->assertDontSee('Item B (Rejected)');
    }

    public function test_pr_excel_import_supports_template_download_and_file_upload_with_row_validation(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // 1. Download XLSX template
        $xlsxTemplateRes = $this->get(route('requisition.template', ['format' => 'xlsx']));
        $xlsxTemplateRes->assertOk();
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $xlsxTemplateRes->headers->get('content-type'));
        $this->assertStringContainsString('PR_Bulk_Import_Template.xlsx', (string) $xlsxTemplateRes->headers->get('content-disposition'));

        // 2. Download CSV template
        $csvTemplateRes = $this->get(route('requisition.template', ['format' => 'csv']));
        $csvTemplateRes->assertOk();
        $this->assertStringContainsString('ITEM NAME(S)', $csvTemplateRes->getContent());
        $this->assertStringContainsString('GST%', $csvTemplateRes->getContent());
        $this->assertStringContainsString('Dell Latitude 5540 Laptop', $csvTemplateRes->getContent());
        $this->assertStringContainsString('DESCRIPTION / SPECS', $csvTemplateRes->getContent());
        $this->assertStringContainsString('PURPOSE', $csvTemplateRes->getContent());
        $this->assertStringContainsString('SOLE SOURCE', $csvTemplateRes->getContent());

        // 3. Row-level validation errors report
        $invalidCsv = "Item Name, Quantity, Unit, Unit Cost, GST%\n, -5, Units, -100, 150";
        $invalidFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv);

        $badRes = $this->post(route('requisition.import-excel'), [
            'department' => 'Operations',
            'file' => $invalidFile,
        ]);
        $badRes->assertSessionHasErrors(['import_errors', 'file']);

        // 4. Valid XLSX file import with multiple rows
        $xlsxBytes = \App\Services\ExcelReader::createXlsx(
            ['Item Name', 'Quantity', 'Unit', 'Unit Cost', 'GST%'],
            [
                ['Industrial Printer Model X', 2, 'Units', 30000, 18],
                ['A4 Paper Bundles', 50, 'Packets', 250, 12],
            ]
        );
        $validXlsx = \Illuminate\Http\UploadedFile::fake()->createWithContent('bulk_items.xlsx', $xlsxBytes);

        $validRes = $this->post(route('requisition.import-excel'), [
            'department' => 'Operations',
            'required_by' => now()->addDays(10)->toDateString(),
            'title' => 'Office Equipment Refresh',
            'file' => $validXlsx,
        ]);

        $validRes->assertRedirect();

        $pr = PurchaseRequisition::where('title', 'Office Equipment Refresh')->first();
        $this->assertNotNull($pr);
        $this->assertCount(2, $pr->items);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Industrial Printer Model X',
            'quantity' => 2,
            'unit_cost' => 30000,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'A4 Paper Bundles',
            'quantity' => 50,
            'unit_cost' => 250,
        ]);
    }

    public function test_pr_parse_excel_returns_row_counts_and_rejection_reasons_for_live_page_population(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Multi-line CSV with department, title, required date, valid lines and an invalid line
        $csvContent = "Department,Item Name,Quantity,Unit,Unit Cost,GST%,Required By,Title\n" .
            "IT,Dell Latitude 5540 Laptop,5,Units,85000,18,2026-11-30,Q4 IT Procurement\n" .
            "IT,Dell 27-inch 4K Monitor,3,Units,22000,18,2026-11-30,Q4 IT Procurement\n" .
            "IT,, -2,Units,-500,150,2026-11-30,Q4 IT Procurement"; // Row 4 is invalid

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('items.csv', $csvContent);

        $response = $this->postJson(route('requisition.parse-excel'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total_rows' => 3,
            'entered_count' => 2,
            'rejected_count' => 1,
            'department' => 'IT',
            'title' => 'Q4 IT Procurement',
            'required_by' => '2026-11-30',
        ]);

        $json = $response->json();
        $this->assertCount(2, $json['entered']);
        $this->assertEquals('Dell Latitude 5540 Laptop', $json['entered'][0]['item_name']);
        $this->assertEquals(5, $json['entered'][0]['quantity']);
        $this->assertEquals(85000, $json['entered'][0]['unit_cost']);

        $this->assertCount(1, $json['rejections']);
        $this->assertEquals(4, $json['rejections'][0]['row']);
        $this->assertStringContainsString('ITEM NAME(S)', $json['rejections'][0]['reason']);
    }

    public function test_pr_excel_import_supports_exact_user_columns_and_creates_prs_with_popup_summary(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Exact 8-column schema: PR NO., PR TITLE, ITEM NAME(S), DEPARTMENT, CATEGORY, EST. COST, REQUIRED BY, STATUS
        $xlsxBytes = \App\Services\ExcelReader::createXlsx(
            ['PR NO.', 'PR TITLE', 'ITEM NAME(S)', 'DEPARTMENT', 'CATEGORY', 'EST. COST', 'REQUIRED BY', 'STATUS'],
            [
                ['PR/FY26-27/20261109-9901', 'Smart Board Setup', 'Interactive Digital Whiteboard 75"', 'Operations', 'Electronics', 65000, '2026-11-28', 'Pending'],
                ['PR/FY26-27/20261109-9901', 'Smart Board Setup', 'Ceiling Mount Stylus Kit', 'Operations', 'Electronics', 15000, '2026-11-28', 'Pending'],
            ]
        );

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_import.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(2, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);
        $this->assertEquals('PR/FY26-27/20261109-9901', $summary['pr_number']);

        // Verify database persistence
        $this->assertDatabaseHas('purchase_requisitions', [
            'pr_number' => 'PR/FY26-27/20261109-9901',
            'department' => 'Operations',
            'title' => 'Smart Board Setup',
        ]);

        $this->assertDatabaseHas('purchase_requisition_items', [
            'item_name' => 'Interactive Digital Whiteboard 75"',
            'unit_cost' => 65000,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'item_name' => 'Ceiling Mount Stylus Kit',
            'unit_cost' => 15000,
        ]);
    }

    public function test_pr_excel_import_updates_existing_pr_status_and_rejects_empty_columns_with_reasons(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // 1. Create an existing pending PR
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/FY26-27/20261109-7777',
            'title' => 'Initial Title',
            'department' => 'IT',
            'required_by' => now()->addDays(7),
            'sole_source' => false,
            'status' => 'pending_l1',
            'current_level' => 1,
            'created_by' => auth()->id(),
        ]);

        $pr->items()->create([
            'item_name' => 'Existing Keyboard',
            'quantity' => 1,
            'unit' => 'Units',
            'unit_cost' => 1000,
            'gst_percent' => 18,
            'status' => 'pending',
        ]);

        // 2. Prepare Excel that:
        // - Updates existing PR 7777 and sets its status to 'Approved'
        // - Row 2 has empty PR TITLE -> rejected
        // - Row 3 has empty DEPARTMENT -> rejected
        // - Row 4 has invalid EST. COST -> rejected
        // - Row 5 has invalid STATUS -> rejected
        $headers = ['PR NO.', 'PR TITLE', 'ITEM NAME(S)', 'DEPARTMENT', 'CATEGORY', 'EST. COST', 'REQUIRED BY', 'STATUS'];
        $rows = [
            // Row 1 (valid update to approved):
            ['PR/FY26-27/20261109-7777', 'Updated IT Hardware', 'Mechanical Keyboards Pack', 'IT', 'IT & Electronics', 15000, '2026-11-25', 'Approved'],
            // Row 2 (missing PR TITLE):
            ['PR/FY26-27/20261109-8881', '', 'Monitor 4K', 'IT', 'IT & Electronics', 25000, '2026-11-25', 'Pending'],
            // Row 3 (missing DEPARTMENT):
            ['PR/FY26-27/20261109-8882', 'Lab Equipment', 'Microscope Pro', '', 'Lab', 40000, '2026-11-25', 'Pending'],
            // Row 4 (invalid EST. COST):
            ['PR/FY26-27/20261109-8883', 'Office Supplies', 'Desk Organizers', 'Operations', 'Supplies', -500, '2026-11-25', 'Pending'],
            // Row 5 (invalid STATUS):
            ['PR/FY26-27/20261109-8884', 'Conference Refresh', 'Projector Screen', 'Marketing', 'Media', 12000, '2026-11-25', 'InvalidStatusXYZ'],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_status_update.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(1, $summary['entered_count']);
        $this->assertEquals(4, $summary['rejected_count']);
        $this->assertCount(4, $summary['rejections']);

        // Check rejection details and PR unique numbers
        $rejectionReasons = collect($summary['rejections'])->pluck('reason')->implode(' | ');
        $this->assertStringContainsString('PR TITLE is missing or empty', $rejectionReasons);
        $this->assertStringContainsString('DEPARTMENT is missing or empty', $rejectionReasons);
        $this->assertStringContainsString('EST. COST must be a positive number', $rejectionReasons);
        $this->assertStringContainsString('STATUS \'InvalidStatusXYZ\' is invalid', $rejectionReasons);

        // Check that existing PR was fetched and its status updated to approved
        $pr->refresh();
        $this->assertEquals('approved', $pr->status);
        $this->assertEquals('Updated IT Hardware', $pr->title);
        $this->assertCount(2, $pr->items);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Mechanical Keyboards Pack',
            'status' => 'approved',
        ]);
    }

    public function test_pr_excel_import_supports_multiple_items_inside_single_items_column_cell(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        // Single row in Excel where "ITEM NAME(S)" column has multiple items separated by comma
        $headers = ['PR NO.', 'PR TITLE', 'ITEM NAME(S)', 'DEPARTMENT', 'CATEGORY', 'EST. COST', 'REQUIRED BY', 'STATUS'];
        $rows = [
            ['PR/FY26-27/20261109-5555', 'Workstation Bundle', 'Dell Latitude Laptop, 27-inch Monitor, Ergonomic Mouse', 'IT', 'IT & Electronics', 90000, '2026-11-30', 'Pending'],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_multi_items.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(3, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        $pr = PurchaseRequisition::where('pr_number', 'PR/FY26-27/20261109-5555')->first();
        $this->assertNotNull($pr);
        $this->assertCount(3, $pr->items);

        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Dell Latitude Laptop',
            'unit_cost' => 30000,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => '27-inch Monitor',
            'unit_cost' => 30000,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Ergonomic Mouse',
            'unit_cost' => 30000,
        ]);
    }

    public function test_pr_excel_import_supports_custom_gst_rates_per_row_and_calculates_budget(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 500000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        // 9-column format with custom GST% per line: 5%, 12%, 28%
        $headers = ['PR NO.', 'PR TITLE', 'ITEM NAME(S)', 'DEPARTMENT', 'CATEGORY', 'EST. COST', 'GST%', 'REQUIRED BY', 'STATUS'];
        $rows = [
            ['PR/FY26-27/20261109-1234', 'Custom GST Hardware', 'Print Paper Reams', 'IT', 'Supplies', 10000, '5%', '2026-11-25', 'Pending'],
            ['PR/FY26-27/20261109-1234', 'Custom GST Hardware', 'Network Switches', 'IT', 'Networking', 20000, '12%', '2026-11-25', 'Pending'],
            ['PR/FY26-27/20261109-1234', 'Custom GST Hardware', 'High-End GPU Workstation', 'IT', 'Hardware', 30000, '28%', '2026-11-25', 'Pending'],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_custom_gst.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(3, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        $pr = PurchaseRequisition::where('pr_number', 'PR/FY26-27/20261109-1234')->first();
        $this->assertNotNull($pr);
        $this->assertCount(3, $pr->items);

        // Verify each line item saved its exact custom GST%
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Print Paper Reams',
            'unit_cost' => 10000,
            'gst_percent' => 5,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'Network Switches',
            'unit_cost' => 20000,
            'gst_percent' => 12,
        ]);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $pr->id,
            'item_name' => 'High-End GPU Workstation',
            'unit_cost' => 30000,
            'gst_percent' => 28,
        ]);

        // Combined total:
        // 10000 * 1.05 = 10,500
        // 20000 * 1.12 = 22,400
        // 30000 * 1.28 = 38,400
        // Sum = 71,300
        $budget = DepartmentBudget::where('department', 'IT')->first();
        $this->assertEquals(71300, (float)$budget->utilised_amount);
    }

    public function test_pr_excel_import_supports_all_requisition_form_fields_purpose_sole_source_specs(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'Operations')->delete();
        DepartmentBudget::create([
            'department' => 'Operations',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 500000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        // Complete 13-column schema matching every single field on the Requisition Create form
        $headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT', 'UNIT COST', 'GST%',
            'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE', 'SOLE SOURCE', 'STATUS',
        ];

        $rows = [
            [
                'PR/FY26-27/20261109-9001',
                'Proprietary Laser Cutter Procurement',
                'Operations',
                'Industrial Fiber Laser Cutter 2000W',
                2,
                'Sets',
                60000,
                18,
                'High-precision 2000W Raycus source, water chiller, safety enclosure, 2-yr warranty',
                '2026-12-15',
                'Specialized fabrication machinery for central workshop expansion',
                'Yes',
                'Pending',
            ],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_all_fields.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(1, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        // Verify the requisition was persisted with all header-level fields
        $pr = PurchaseRequisition::where('pr_number', 'PR/FY26-27/20261109-9001')->first();
        $this->assertNotNull($pr);
        $this->assertEquals('Proprietary Laser Cutter Procurement', $pr->title);
        $this->assertEquals('Operations', $pr->department);
        $this->assertEquals('Specialized fabrication machinery for central workshop expansion', $pr->description);
        $this->assertTrue($pr->sole_source);
        $this->assertEquals('2026-12-15', $pr->required_by->format('Y-m-d'));
        $this->assertEquals('pending_l1', $pr->status);

        // Verify the line item was persisted with all item-level fields
        $this->assertCount(1, $pr->items);
        $item = $pr->items->first();
        $this->assertEquals('Industrial Fiber Laser Cutter 2000W', $item->item_name);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals('Sets', $item->unit);
        $this->assertEquals(60000, (float)$item->unit_cost);
        $this->assertEquals(18, (float)$item->gst_percent);
        $this->assertEquals('High-precision 2000W Raycus source, water chiller, safety enclosure, 2-yr warranty', $item->description);

        // Verify budget calculation:
        // Base = 2 * 60000 = 120,000
        // GST (18%) = 21,600
        // Grand Total = 141,600
        $this->assertEquals(141600, $pr->estimatedCostInclGst());

        $budget = DepartmentBudget::where('department', 'Operations')->first();
        $this->assertEquals(141600, (float)$budget->utilised_amount);
    }

    public function test_pr_excel_import_supports_multiple_rows_same_pr_number_with_distinct_item_specs_and_purpose(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 1000000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT', 'UNIT COST', 'GST%',
            'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE', 'SOLE SOURCE', 'STATUS',
        ];

        $prNumber = 'PR/FY26-27/20261109-3899';
        $rows = [
            [
                $prNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Dell Latitude 5540 Laptop',
                2,
                'Units',
                85000,
                18,
                'Intel i7 13th Gen, 16GB RAM, 512GB SSD, 3-Year ProSupport Warranty',
                '2026-11-23',
                'Q4 engineering workstation refresh under departmental IT budget',
                'No',
                'Pending',
            ],
            [
                $prNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Dell UltraSharp 27 4K Monitor',
                2,
                'Units',
                42000,
                18,
                'U2723QE 4K IPS Black, USB-C Hub 90W PD, Height Adjustable Stand',
                '2026-11-23',
                'Q4 engineering workstation refresh under departmental IT budget',
                'No',
                'Pending',
            ],
            [
                $prNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Logitech MX Master 3S Mouse',
                2,
                'Units',
                8500,
                18,
                'Ergonomic Wireless Performance Mouse with Quiet Clicks',
                '2026-11-23',
                'Q4 engineering workstation refresh under departmental IT budget',
                'No',
                'Pending',
            ],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_multi_items.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $summary = session('import_summary');
        $this->assertEquals(3, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        $pr = PurchaseRequisition::where('pr_number', $prNumber)->first();
        $this->assertNotNull($pr);
        $this->assertEquals('IT Infrastructure Refresh', $pr->title);
        $this->assertEquals('IT', $pr->department);
        $this->assertEquals('Q4 engineering workstation refresh under departmental IT budget', $pr->description);
        $this->assertCount(3, $pr->items);

        $laptop = $pr->items->firstWhere('item_name', 'Dell Latitude 5540 Laptop');
        $this->assertNotNull($laptop);
        $this->assertEquals('Intel i7 13th Gen, 16GB RAM, 512GB SSD, 3-Year ProSupport Warranty', $laptop->description);
        $this->assertEquals(2, $laptop->quantity);
        $this->assertEquals(85000, (float)$laptop->unit_cost);

        $monitor = $pr->items->firstWhere('item_name', 'Dell UltraSharp 27 4K Monitor');
        $this->assertNotNull($monitor);
        $this->assertEquals('U2723QE 4K IPS Black, USB-C Hub 90W PD, Height Adjustable Stand', $monitor->description);
        $this->assertEquals(2, $monitor->quantity);
        $this->assertEquals(42000, (float)$monitor->unit_cost);

        $mouse = $pr->items->firstWhere('item_name', 'Logitech MX Master 3S Mouse');
        $this->assertNotNull($mouse);
        $this->assertEquals('Ergonomic Wireless Performance Mouse with Quiet Clicks', $mouse->description);

        // Financial Calculation:
        // Laptop: 2 * 85,000 = 170,000 + 18% GST (30,600) = 200,600
        // Monitor: 2 * 42,000 = 84,000 + 18% GST (15,120) = 99,120
        // Mouse: 2 * 8,500 = 17,000 + 18% GST (3,060) = 20,060
        // Subtotal = 271,000
        // Total GST = 48,780
        // Grand Total = 319,780
        $this->assertEquals(319780, $pr->estimatedCostInclGst());

        $budget = DepartmentBudget::where('department', 'IT')->first();
        $this->assertEquals(319780, (float)$budget->utilised_amount);
    }

    public function test_pr_excel_import_pairs_multiple_items_and_multiple_specs_in_single_cell(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 500000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT', 'UNIT COST', 'GST%',
            'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE', 'SOLE SOURCE', 'STATUS',
        ];

        $prNumber = 'PR/FY26-27/20261109-7711';
        $rows = [
            [
                $prNumber,
                'Engineering Peripherals',
                'IT',
                "Dell Latitude 5540;\nDell UltraSharp 27 4K Monitor",
                1,
                'Units',
                127000,
                18,
                "Intel i7 16GB RAM 512GB SSD;\n4K IPS Black 90W PD USB-C",
                '2026-11-23',
                'Dual display & developer workstation upgrade',
                'No',
                'Pending',
            ],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('pr_paired_items.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $pr = PurchaseRequisition::where('pr_number', $prNumber)->first();
        $this->assertNotNull($pr);
        $this->assertEquals('Dual display & developer workstation upgrade', $pr->description);
        $this->assertCount(2, $pr->items);

        $item1 = $pr->items->firstWhere('item_name', 'Dell Latitude 5540');
        $this->assertNotNull($item1);
        $this->assertEquals('Intel i7 16GB RAM 512GB SSD', $item1->description);
        $this->assertEquals(63500, (float)$item1->unit_cost);

        $item2 = $pr->items->firstWhere('item_name', 'Dell UltraSharp 27 4K Monitor');
        $this->assertNotNull($item2);
        $this->assertEquals('4K IPS Black 90W PD USB-C', $item2->description);
        $this->assertEquals(63500, (float)$item2->unit_cost);
    }

    public function test_pr_excel_import_directly_imports_downloaded_multi_item_template(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 1000000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        // 1. Fetch template directly from controller endpoint
        $templateResponse = $this->get(route('requisition.template', ['format' => 'xlsx']));
        $templateResponse->assertOk();
        $xlsxContent = $templateResponse->getContent();

        // 2. Upload the exact template right back into the system
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('downloaded_template.xlsx', $xlsxContent);
        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $summary = session('import_summary');
        $this->assertEquals(3, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        // 3. Verify PR was created with all 3 items and exact specs
        $pr = PurchaseRequisition::where('pr_number', $summary['pr_number'])->first();
        $this->assertNotNull($pr);
        $this->assertEquals('IT Infrastructure Refresh', $pr->title);
        $this->assertEquals('IT', $pr->department);
        $this->assertCount(3, $pr->items);

        $laptop = $pr->items->firstWhere('item_name', 'Dell Latitude 5540 Laptop');
        $this->assertNotNull($laptop);
        $this->assertEquals(2, $laptop->quantity);
        $this->assertEquals('Units', $laptop->unit);
        $this->assertEquals(85000, (float)$laptop->unit_cost);
        $this->assertEquals(18, (float)$laptop->gst_percent);
        $this->assertStringContainsString('Intel Core i7', $laptop->description);

        $monitor = $pr->items->firstWhere('item_name', 'Dell UltraSharp 27 4K Monitor');
        $this->assertNotNull($monitor);
        $this->assertEquals(2, $monitor->quantity);
        $this->assertEquals('Units', $monitor->unit);
        $this->assertEquals(42000, (float)$monitor->unit_cost);
        $this->assertStringContainsString('Dell UltraSharp U2723QE', $monitor->description);

        $mouse = $pr->items->firstWhere('item_name', 'Logitech MX Master 3S Mouse');
        $this->assertNotNull($mouse);
        $this->assertEquals(2, $mouse->quantity);
        $this->assertEquals('Units', $mouse->unit);
        $this->assertEquals(8500, (float)$mouse->unit_cost);
        $this->assertStringContainsString('Logitech MX Master 3S', $mouse->description);

        // Subtotal = (2*85000) + (2*42000) + (2*8500) = 170000 + 84000 + 17000 = 271000
        // GST (18%) = 48780
        // Grand Total = 319780
        $this->assertEquals(319780, $pr->estimatedCostInclGst());
    }

    public function test_pr_excel_import_supports_form_layout_with_blank_pr_number_and_custom_department(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'HR')->delete();
        DepartmentBudget::create([
            'department' => 'HR',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 500000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        // Form layout generated with EMPTY pr_number and DEPARTMENT = 'HR'
        $xlsxBytes = \App\Services\ExcelReader::createFormXlsx(
            prNumber: '', // Empty PR number -> triggers auto-generation
            sampleItems: [
                ['Ergonomic Executive Chair', 5, 'Pieces', 12000, 18, 'High back mesh ergonomic chair with lumbar support'],
                ['Height Adjustable Standing Desk', 2, 'Sets', 25000, 18, 'Dual-motor electric standing desk 1400x700mm'],
            ],
            prTitle: 'HR Floor Ergonomic Upgrade',
            department: 'HR',
            requiredBy: '2026-12-01',
            purpose: 'Workplace ergonomics improvement for talent acquisition team',
            soleSource: 'No',
            status: 'Pending'
        );

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('hr_ergonomics.xlsx', $xlsxBytes);
        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $summary = session('import_summary');
        $this->assertEquals(2, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        // Check that a unique PR number was automatically generated
        $this->assertNotEmpty($summary['pr_number']);
        $this->assertStringStartsWith('PR/FY', $summary['pr_number']);

        $pr = PurchaseRequisition::where('pr_number', $summary['pr_number'])->first();
        $this->assertNotNull($pr);
        $this->assertEquals('HR Floor Ergonomic Upgrade', $pr->title);
        $this->assertEquals('HR', $pr->department);
        $this->assertEquals('2026-12-01', $pr->required_by->format('Y-m-d'));
        $this->assertEquals('Workplace ergonomics improvement for talent acquisition team', $pr->description);
        $this->assertCount(2, $pr->items);

        // Verify budget calculation for HR:
        // Item 1: 5 * 12,000 = 60,000 + 18% GST (10,800) = 70,800
        // Item 2: 2 * 25,000 = 50,000 + 18% GST (9,000) = 59,000
        // Total = 129,800
        $this->assertEquals(129800, $pr->estimatedCostInclGst());

        $hrBudget = DepartmentBudget::where('department', 'HR')->first();
        $this->assertEquals(129800, (float)$hrBudget->utilised_amount);
    }

    public function test_single_department_can_create_multiple_prs_with_multiple_items_and_costs_in_one_excel(): void
    {
        $this->loginAs(Roles::PR_SR_EXEC);

        DepartmentBudget::where('department', 'IT')->delete();
        DepartmentBudget::create([
            'department' => 'IT',
            'fiscal_year' => DepartmentBudget::currentFiscalYear(),
            'allocated_amount' => 1500000,
            'utilised_amount' => 0,
            'gst_rate' => 18,
        ]);

        $headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT', 'UNIT COST', 'GST%',
            'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE', 'SOLE SOURCE', 'STATUS',
        ];

        // 2 distinct PRs for the SAME department (IT), each having multiple items with their own quantities, costs, GST, and specs:
        $pr1 = 'PR/FY26-27/20261110-1001';
        $pr2 = 'PR/FY26-27/20261110-1002';

        $rows = [
            // PR 1: Workstation Refresh (2 items)
            [$pr1, 'Workstation Refresh', 'IT', 'Dell Latitude 5540 Laptop', 3, 'Units', 85000, 18, 'Core i7 32GB RAM', '2026-11-30', 'Developer upgrades', 'No', 'Pending'],
            [$pr1, 'Workstation Refresh', 'IT', 'Dell 27 4K Monitor', 3, 'Units', 42000, 18, 'U2723QE IPS Black', '2026-11-30', 'Developer upgrades', 'No', 'Pending'],

            // PR 2: Network Infrastructure (2 items)
            [$pr2, 'Network Infrastructure', 'IT', 'Cisco Catalyst 9300 Switch', 1, 'Units', 180000, 18, '48-Port PoE+ Gigabit', '2026-12-05', 'Core server rack upgrade', 'No', 'Pending'],
            [$pr2, 'Network Infrastructure', 'IT', 'Cat6A Patch Cables 50-Pack', 4, 'Packs', 4500, 18, 'Shielded RJ45 cables', '2026-12-05', 'Core server rack upgrade', 'No', 'Pending'],
        ];

        $xlsxBytes = \App\Services\ExcelReader::createXlsx($headers, $rows);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('it_multiple_prs.xlsx', $xlsxBytes);

        $response = $this->post(route('requisition.import-excel'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('requisition'));
        $summary = session('import_summary');
        $this->assertEquals(4, $summary['entered_count']);
        $this->assertEquals(0, $summary['rejected_count']);

        // Verify PR 1 created with 2 items
        $createdPr1 = PurchaseRequisition::where('pr_number', $pr1)->first();
        $this->assertNotNull($createdPr1);
        $this->assertEquals('Workstation Refresh', $createdPr1->title);
        $this->assertEquals('IT', $createdPr1->department);
        $this->assertCount(2, $createdPr1->items);
        // PR 1 total: (3*85000 + 3*42000) * 1.18 = (255000 + 126000) * 1.18 = 381000 * 1.18 = 449,580
        $this->assertEquals(449580, $createdPr1->estimatedCostInclGst());

        // Verify PR 2 created with 2 items
        $createdPr2 = PurchaseRequisition::where('pr_number', $pr2)->first();
        $this->assertNotNull($createdPr2);
        $this->assertEquals('Network Infrastructure', $createdPr2->title);
        $this->assertEquals('IT', $createdPr2->department);
        $this->assertCount(2, $createdPr2->items);
        // PR 2 total: (1*180000 + 4*4500) * 1.18 = (180000 + 18000) * 1.18 = 198000 * 1.18 = 233,640
        $this->assertEquals(233640, $createdPr2->estimatedCostInclGst());

        // Total IT budget utilized across both PRs: 449,580 + 233,640 = 683,220
        $itBudget = DepartmentBudget::where('department', 'IT')->first();
        $this->assertEquals(683220, (float)$itBudget->utilised_amount);
    }

    public function test_upper_hierarchy_approver_can_edit_and_resend_pr(): void
    {
        // 1. Submit PR by PR Sr. Executive with Event, Region, Special Instructions
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'department' => 'Marketing',
            'title' => 'Annual Industry Gala Requisition',
            'event_name' => 'National Innovation Conclave 2026',
            'region' => 'Delhi NCR',
            'description' => 'Event staging, AV equipment and promotional displays',
            'special_instructions' => 'Set up by 6:00 AM on event day at Vigyan Bhawan',
            'items' => [
                ['item_name' => 'LED Display Wall', 'quantity' => 1, 'unit_cost' => 50000, 'gst_percent' => 18],
            ],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('department', 'Marketing')->firstOrFail();
        $this->assertSame('National Innovation Conclave 2026', $pr->event_name);
        $this->assertSame('Delhi NCR', $pr->region);
        $this->assertSame('Set up by 6:00 AM on event day at Vigyan Bhawan', $pr->special_instructions);
        $this->assertSame('pending_l1', $pr->status);

        // 2. Advance to Level 2 (Ambika)
        $this->loginAs(Roles::PR_APPROVER_L1);
        $this->post(route('requisition.approve', $pr))->assertRedirect();
        $this->assertSame('pending_l2', $pr->fresh()->status);

        // 3. Upper hierarchy approver (Level 2: Head of States / Ambika or Level 3: CFO) needs to edit items/details and re-send
        $this->loginAs(Roles::PR_APPROVER_L2);

        // Ambika can view edit form
        $this->get(route('requisition.edit', $pr))->assertOk()
            ->assertSee('National Innovation Conclave 2026')
            ->assertSee('Delhi NCR')
            ->assertSee('LED Display Wall');

        // Ambika modifies the line items (adjusts rate/adds items) and re-sends
        $this->put(route('requisition.update', $pr), [
            'department' => 'Marketing',
            'title' => 'Annual Industry Gala Requisition (Revised by Ambika)',
            'event_name' => 'National Innovation Conclave 2026',
            'region' => 'Delhi NCR (Vigyan Bhawan)',
            'description' => 'Revised staging and PA audio support',
            'special_instructions' => 'Sound check mandatory evening prior',
            'resubmit_notes' => 'Negotiated LED wall rental down to ₹42,000 and added wireless mic system.',
            'items' => [
                ['item_name' => 'LED Display Wall', 'quantity' => 1, 'unit_cost' => 42000, 'gst_percent' => 18, 'description' => 'High resolution P2.5 LED'],
                ['item_name' => 'Wireless Collar Mics', 'quantity' => 2, 'unit_cost' => 4000, 'gst_percent' => 18, 'description' => 'Sennheiser G4 sets'],
            ],
        ])->assertRedirect(route('requisition.show', $pr));

        $fresh = $pr->fresh();
        // Approval chain was re-routed back to Level 1
        $this->assertSame('pending_l1', $fresh->status);
        $this->assertSame('Delhi NCR (Vigyan Bhawan)', $fresh->region);
        $this->assertSame('Sound check mandatory evening prior', $fresh->special_instructions);
        $this->assertCount(2, $fresh->items);

        // Verify version history captures approver edit
        $latestVersion = $fresh->versions()->latest('version_number')->first();
        $this->assertNotNull($latestVersion);
        $this->assertSame('Modified by Approver & Re-sent', $latestVersion->action_title);
        $this->assertStringContainsString('Negotiated LED wall rental', $latestVersion->comments);
    }
}
