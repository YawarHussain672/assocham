<?php

namespace Tests\Feature;

use App\Models\CostComparativeStatement;
use App\Models\DepartmentBudget;
use App\Models\ExceptionRequest;
use App\Models\FlightLog;
use App\Models\Grievance;
use App\Models\Grn;
use App\Models\ProjectBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Reimbursement;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullRoleUiAndBackendAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_designation_every_sidebar_link_form_and_detail_page_renders_cleanly(): void
    {
        $this->seed();

        $usersByRole = [];
        foreach (Roles::all() as $role) {
            $usersByRole[$role] = User::where('role', $role)->firstOrFail();
        }

        $admin = $usersByRole[Roles::ADMIN_HEAD];
        $buyer = $usersByRole[Roles::BUYER];
        $prExec = $usersByRole[Roles::PR_SR_EXEC];
        $hod = $usersByRole[Roles::HOD];
        $cfo = $usersByRole[Roles::CFO];

        // Seed rich records across EVERY module so every list, show, edit, and create page exercises real data
        $fy = DepartmentBudget::currentFiscalYear();
        $deptBudget = DepartmentBudget::firstOrCreate(
            ['department' => 'IT', 'fiscal_year' => $fy],
            ['allocated_amount' => 5000000, 'utilised_amount' => 500000, 'gst_rate' => 18]
        );
        $projectBudget = ProjectBudget::firstOrCreate(
            ['project_name' => 'Annual Cyber Summit', 'fiscal_year' => $fy],
            [
                'department' => 'IT',
                'council' => 'Digital Council',
                'allocated_amount' => 2000000,
                'utilised_amount' => 200000,
            ]
        );

        $pendingVendor = Vendor::create([
            'vendor_code' => 'VEND-AUD-P1',
            'company_name' => 'Pending Audit Vendor Pvt Ltd',
            'contact_person' => 'Rohan Das',
            'email' => 'pending@vendor.test',
            'category' => 'IT Hardware',
            'status' => 'pending_l1',
            'compliance_status' => 'active',
            'submitted_by' => $buyer->id,
        ]);
        $pendingVendor->approvals()->create(['level' => 1, 'role_required' => Roles::ADMIN_HEAD, 'status' => 'pending']);

        $empanelledVendor = Vendor::create([
            'vendor_code' => 'VEND-AUD-E1',
            'company_name' => 'Empanelled Travel & Tech Partner',
            'contact_person' => 'Meera Sen',
            'email' => 'partner@vendor.test',
            'category' => 'Travel & IT',
            'status' => 'empanelled',
            'compliance_status' => 'active',
            'rating' => 4.5,
            'submitted_by' => $buyer->id,
        ]);

        $pendingPr = PurchaseRequisition::create([
            'pr_number' => 'PR-AUD-PEND-1',
            'title' => 'Cloud Security Firewall',
            'department' => 'IT',
            'project_budget_id' => $projectBudget->id,
            'category' => 'IT Hardware',
            'estimated_value' => 118000,
            'status' => 'pending_l1',
            'current_level' => 1,
            'created_by' => $prExec->id,
        ]);
        $pendingPr->items()->create([
            'item_name' => 'Enterprise Firewall',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => 100000,
            'gst_percent' => 18,
            'status' => 'pending',
        ]);
        $pendingPr->approvals()->create(['level' => 1, 'role_required' => Roles::PR_APPROVER_L1, 'status' => 'pending']);

        $approvedPr = PurchaseRequisition::create([
            'pr_number' => 'PR-AUD-APP-1',
            'title' => 'Conference AV Setup',
            'department' => 'IT',
            'project_budget_id' => $projectBudget->id,
            'category' => 'IT Hardware',
            'estimated_value' => 236000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prExec->id,
        ]);
        $approvedPr->items()->create([
            'item_name' => 'LED Wall System',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 100000,
            'gst_percent' => 18,
            'status' => 'approved',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-AUD-01',
            'sector' => 'IT',
            'project_name' => 'Annual Cyber Summit AV',
            'purchase_requisition_id' => $approvedPr->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'quotes_received',
            'email_dispatched_at' => now(),
        ]);
        $rfq->rfqVendors()->create([
            'vendor_id' => $empanelledVendor->id,
            'response_status' => 'quoted',
            'email_sent_at' => now(),
        ]);
        $ccs = CostComparativeStatement::create([
            'ccs_number' => 'CCS-AUD-01',
            'rfq_id' => $rfq->id,
            'selected_vendor_id' => $empanelledVendor->id,
            'selected_by' => $buyer->id,
            'selection_remarks' => 'Best SLA and delivery',
            'status' => 'approved',
            'approved_by' => $buyer->id,
        ]);
        $ccs->quotes()->create([
            'vendor_id' => $empanelledVendor->id,
            'basic_price' => 200000,
            'gst_amount' => 36000,
            'transportation_cost' => 0,
            'quoted_cost' => 236000,
            'delivery_days' => 5,
            'is_selected' => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-AUD-01',
            'purchase_requisition_id' => $approvedPr->id,
            'cost_comparative_statement_id' => $ccs->id,
            'vendor_id' => $empanelledVendor->id,
            'po_qty' => 2,
            'delivery_date' => now()->addDays(10)->toDateString(),
            'amount_incl_gst' => 236000,
            'status' => 'approved',
            'created_by' => $buyer->id,
            'event_name' => $projectBudget->project_name,
        ]);

        $grn = Grn::create([
            'grn_number' => 'GRN-AUD-01',
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $approvedPr->id,
            'item_description' => 'LED Wall System',
            'po_qty' => 2,
            'received_qty' => 1,
            'invoice_number' => 'INV-AUD-101',
            'receipt_date' => now()->toDateString(),
            'status' => 'pending_hod',
            'created_by' => $prExec->id,
        ]);
        $grn->approvals()->create(['level' => 1, 'role_required' => Roles::HOD, 'status' => 'pending']);

        $trfCompleted = Trf::create([
            'trf_number' => 'TRF-AUD-01',
            'employee_id' => $prExec->id,
            'travel_type' => 'domestic',
            'travel_mode' => 'Air',
            'from_city' => 'New Delhi',
            'to_city' => 'Mumbai',
            'destination' => 'Mumbai',
            'travel_start' => now()->subDays(10)->toDateString(),
            'travel_end' => now()->subDays(7)->toDateString(),
            'purpose' => 'Industry Conference',
            'requested_advance' => 10000,
            'status' => 'approved',
        ]);

        $trfPushedBack = Trf::create([
            'trf_number' => 'TRF-AUD-PB',
            'employee_id' => $prExec->id,
            'travel_type' => 'domestic',
            'travel_mode' => 'Rail',
            'from_city' => 'New Delhi',
            'to_city' => 'Jaipur',
            'destination' => 'Jaipur',
            'travel_start' => now()->addDays(5)->toDateString(),
            'travel_end' => now()->addDays(7)->toDateString(),
            'purpose' => 'Regional Workshop',
            'requested_advance' => 5000,
            'status' => 'pushed_back',
        ]);

        $flightLog = FlightLog::create([
            'ref' => 'FLT-AUD-01',
            'trf_id' => $trfCompleted->id,
            'vendor_id' => $empanelledVendor->id,
            'type' => 'Flight ticket',
            'cost' => 12500,
            'uploaded_by' => $admin->id,
        ]);

        $reimb = Reimbursement::create([
            'claim_number' => 'CLM-AUD-01',
            'trf_id' => $trfCompleted->id,
            'employee_id' => $prExec->id,
            'routing_flag' => 'standard',
            'claim_amount' => 14000,
            'advance_amount' => 10000,
            'balance_amount' => 4000,
            'status' => 'pending_cfo',
        ]);
        $reimb->approvals()->create(['level' => 1, 'role_required' => Roles::CFO, 'status' => 'pending']);

        $evaluation = VendorEvaluation::create([
            'trf_id' => $trfCompleted->id,
            'vendor_id' => $empanelledVendor->id,
            'cost_score' => 5,
            'time_score' => 4,
            'quality_score' => 5,
            'responsiveness_score' => 4,
            'accuracy_score' => 5,
            'comments' => 'Prompt booking and accurate ticketing.',
            'rated_by' => $admin->id,
        ]);

        $exception = ExceptionRequest::create([
            'exception_type' => 'budget_exceed',
            'reference_type' => 'purchase_requisitions',
            'reference_id' => $pendingPr->id,
            'justification' => 'Urgent compliance upgrade',
            'requested_by' => $admin->id,
            'approver_queue' => Roles::CFO,
            'status' => 'pending',
        ]);

        $grievance = Grievance::create([
            'ticket_number' => 'GRV-AUD-01',
            'raised_by' => $prExec->id,
            'subject_category' => 'Technical Issue',
            'subject' => 'Portal invoice preview check',
            'description' => 'Verifying grievance screen across roles.',
            'current_owner_role' => Roles::HOD,
            'status' => 'under_review',
        ]);

        // 1. Check all guest routes
        foreach (['/login', '/forgot-password', '/reset-password/test-token?email=test@assocham.test'] as $guestUrl) {
            $res = $this->get($guestUrl);
            $res->assertOk();
            $this->assertCleanHtml($res->getContent(), "GUEST $guestUrl");
        }

        // 2. Check EVERY SINGLE DESIGNATION (all 10 roles)
        $routeByModule = [
            'dashboard' => route('dashboard'),
            'onboarding' => route('onboarding'),
            'empanelment' => route('empanelment'),
            'requisition' => route('requisition'),
            'rfq' => route('rfq'),
            'po' => route('po'),
            'grn' => route('grn'),
            'events' => route('events.index'),
            'trf' => route('trf'),
            'flight' => route('flight'),
            'reimb' => route('reimb'),
            'traveleval' => route('traveleval'),
            'checks-balances' => route('checks-balances'),
            'annual-budget' => route('annual-budget'),
            'item-catalogs' => route('item-catalogs.index'),
            'exceptions' => route('exceptions'),
            'grievance' => route('grievance'),
            'notifications' => route('notifications'),
            'audit' => route('audit'),
            'users' => route('users'),
        ];

        $failures = [];

        foreach ($usersByRole as $role => $user) {
            // Every sidebar link MUST return 200 OK with clean HTML and Shell.init
            $sidebarModules = Roles::sidebarModulesForUser($user);
            foreach ($sidebarModules as $mod) {
                $url = $routeByModule[$mod];
                $res = $this->actingAs($user)->get($url);
                if ($res->getStatusCode() !== 200) {
                    $failures[] = "[$role] Sidebar link '$mod' ($url) returned HTTP {$res->getStatusCode()} (expected 200)";
                    continue;
                }
                $html = $res->getContent();
                $this->collectHtmlIssues($html, "[$role] $mod ($url)", $failures);

                // Non-admin must NEVER get canSwitchRole:true
                if ($role !== Roles::ADMIN_HEAD && str_contains($html, '"canSwitchRole":true')) {
                    $failures[] = "[$role] $mod leaked canSwitchRole:true";
                }
            }

            // Change password page MUST work for all 10 roles
            $cpRes = $this->actingAs($user)->get(route('password.change'));
            if ($cpRes->getStatusCode() !== 200) {
                $failures[] = "[$role] Change Password returned HTTP {$cpRes->getStatusCode()}";
            } else {
                $this->collectHtmlIssues($cpRes->getContent(), "[$role] /account/password", $failures);
            }

            // Every UNAUTHORIZED module MUST return 403
            foreach ($routeByModule as $mod => $url) {
                if (! Roles::canAccessModuleForUser($user, $mod)) {
                    $res = $this->actingAs($user)->get($url);
                    if ($res->getStatusCode() !== 403) {
                        $failures[] = "[$role] Unauthorized module '$mod' ($url) returned HTTP {$res->getStatusCode()} (expected 403)";
                    }
                }
            }

            // Test detail/create/edit pages for every module this role CAN access
            $detailChecks = [
                'empanelment' => [
                    route('empanelment.show', $pendingVendor),
                    route('empanelment.show', $empanelledVendor),
                ],
                'requisition' => [
                    route('requisition.show', $pendingPr),
                    route('requisition.show', $approvedPr),
                ],
                'rfq' => [
                    route('rfq.show', $rfq),
                ],
                'po' => [
                    route('po.show', $po),
                ],
                'grn' => [
                    route('grn.show', $grn),
                ],
                'trf' => [
                    route('trf.create'),
                    route('trf.show', $trfCompleted),
                    route('trf.show', $trfPushedBack),
                ],
                'flight' => [
                    route('flight.create'),
                    route('flight.show', $flightLog),
                ],
                'reimb' => [
                    route('reimb.create'),
                    route('reimb.show', $reimb),
                ],
                'traveleval' => [
                    route('traveleval.create'),
                    route('traveleval.show', $evaluation),
                    route('traveleval.vendor-history', $empanelledVendor),
                ],
                'checks-balances' => [
                    route('checks-balances', ['view' => 'project']),
                ],
                'exceptions' => [
                    route('exceptions.show', $exception),
                ],
                'grievance' => [
                    route('grievance.create'),
                ],
                'users' => [
                    route('users.create'),
                    route('users.edit', $buyer),
                ],
            ];

            foreach ($detailChecks as $mod => $urls) {
                if (! Roles::canAccessModuleForUser($user, $mod)) {
                    continue;
                }
                foreach ($urls as $url) {
                    $res = $this->actingAs($user)->get($url);
                    if ($res->getStatusCode() !== 200) {
                        $failures[] = "[$role] Detail/form page $url returned HTTP {$res->getStatusCode()} (expected 200)";
                    } else {
                        $this->collectHtmlIssues($res->getContent(), "[$role] $url", $failures);
                    }
                }
            }
        }

        // 3. Check role-specific Create & Edit forms for authorized roles
        $roleFormChecks = [
            [$buyer, route('onboarding')],
            [$buyer, route('empanelment.edit', $pendingVendor)],
            [$buyer, route('rfq.create')],
            [$buyer, route('po.create')],
            [$buyer, route('po.edit', $po)],
            [$prExec, route('requisition.create')],
            [$prExec, route('requisition.edit', $pendingPr)],
            [$prExec, route('grn.create')],
            [$prExec, route('grn.edit', $grn)],
            [$prExec, route('trf.edit', $trfPushedBack)],
            [$prExec, route('grievance.show', $grievance)],
            [$hod, route('grievance.show', $grievance)],
            [$cfo, route('grievance.show', $grievance)],
            [$admin, route('exceptions.create')],
            [$admin, route('empanelment.edit', $pendingVendor)],
            [$admin, route('requisition.create')],
            [$admin, route('requisition.edit', $pendingPr)],
            [$admin, route('rfq.create')],
            [$admin, route('po.create')],
            [$admin, route('po.edit', $po)],
            [$admin, route('grn.create')],
            [$admin, route('grn.edit', $grn)],
            [$admin, route('grievance.show', $grievance)],
        ];

        foreach ($roleFormChecks as [$actor, $url]) {
            $res = $this->actingAs($actor)->get($url);
            if ($res->getStatusCode() !== 200) {
                $failures[] = "[{$actor->role}] Form/Show page $url returned HTTP {$res->getStatusCode()} (expected 200)";
            } else {
                $this->collectHtmlIssues($res->getContent(), "[{$actor->role}] $url", $failures);
            }
        }

        // 4. Verify UI Action-Button & Budget Privacy Isolation across roles
        $cfoChecksHtml = $this->actingAs($cfo)->get(route('checks-balances'))->assertOk()->getContent();
        if (str_contains($cfoChecksHtml, route('checks-balances.budgets.store'))) {
            $failures[] = '[cfo] /checks-balances leaked Admin Head budget creation form';
        }
        $sgChecksHtml = $this->actingAs($usersByRole[Roles::SG])->get(route('checks-balances'))->assertOk()->getContent();
        if (str_contains($sgChecksHtml, route('checks-balances.budgets.store'))) {
            $failures[] = '[sg] /checks-balances leaked Admin Head budget creation form';
        }
        $cfoExcHtml = $this->actingAs($cfo)->get(route('exceptions'))->assertOk()->getContent();
        if (str_contains($cfoExcHtml, route('exceptions.create'))) {
            $failures[] = '[cfo] /exceptions leaked Admin Head File Exception button';
        }
        $hodGrnHtml = $this->actingAs($hod)->get(route('grn'))->assertOk()->getContent();
        if (str_contains($hodGrnHtml, route('grn.create'))) {
            $failures[] = '[hod] /grn leaked New GRN button';
        }
        $cfoPoHtml = $this->actingAs($cfo)->get(route('po'))->assertOk()->getContent();
        if (str_contains($cfoPoHtml, route('po.create'))) {
            $failures[] = '[cfo] /po leaked New Purchase Order button';
        }
        foreach ([Roles::PR_SR_EXEC, Roles::BUYER, Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::ASG1, Roles::ASG2, Roles::HOD] as $nonBudgetRole) {
            $dashHtml = $this->actingAs($usersByRole[$nonBudgetRole])->get(route('dashboard'))->assertOk()->getContent();
            if (str_contains($dashHtml, 'Department Budgets')) {
                $failures[] = "[$nonBudgetRole] /index dashboard leaked Department Budgets panel";
            }
        }

        // 5. Verify template downloads and PO PDF preview/download return 200
        $downloadChecks = [
            [$buyer, route('empanelment.declaration.template')],
            [$prExec, route('requisition.template')],
            [$prExec, route('trf.template')],
            [$buyer, route('po.pdf', $po)],
            [$buyer, route('po.preview-pdf', $po)],
        ];
        foreach ($downloadChecks as [$actor, $url]) {
            $res = $this->actingAs($actor)->get($url);
            if ($res->getStatusCode() !== 200) {
                $failures[] = "[{$actor->role}] Download/PDF $url returned HTTP {$res->getStatusCode()} (expected 200)";
            }
        }

        $this->assertEmpty($failures, "Found UI/Backend audit issues:\n- ".implode("\n- ", $failures));
    }

    public function test_no_designation_sees_dead_403_links_and_rfq_grn_po_actions_are_complete(): void
    {
        $this->seed();

        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $prExec = User::where('role', Roles::PR_SR_EXEC)->firstOrFail();
        $hod = User::where('role', Roles::HOD)->firstOrFail();
        $cfo = User::where('role', Roles::CFO)->firstOrFail();
        $admin = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();

        $fy = DepartmentBudget::currentFiscalYear();
        $projectBudget = ProjectBudget::firstOrCreate(
            ['project_name' => 'Digital Transformation Conclave', 'fiscal_year' => $fy],
            ['department' => 'IT', 'allocated_amount' => 1000000, 'utilised_amount' => 100000]
        );
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-UX-1',
            'company_name' => 'UX Audit Vendor',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR-UX-001',
            'title' => 'Conclave Networking',
            'department' => 'IT',
            'project_budget_id' => $projectBudget->id,
            'estimated_value' => 100000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prExec->id,
        ]);
        $pr->items()->create([
            'item_name' => 'Core Switch',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 50000,
            'gst_percent' => 0,
            'status' => 'approved',
        ]);

        // Awaiting RFQ whose project_name matches ProjectBudget::project_name
        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-UX-001',
            'sector' => 'IT',
            'project_name' => 'Digital Transformation Conclave',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'dispatched',
        ]);
        $rfq->rfqVendors()->create(['vendor_id' => $vendor->id, 'response_status' => 'awaiting']);

        // 1) Buyer on /rfq sees clean View button in Action section, no duplicated project name, and no 403 link to /requisition/{id}
        $buyerRfqHtml = $this->actingAs($buyer)->get(route('rfq'))->assertOk()->getContent();
        $this->assertStringContainsString('View', $buyerRfqHtml);
        $this->assertStringContainsString(route('rfq.show', $rfq), $buyerRfqHtml);
        $this->assertStringNotContainsString(route('requisition.show', $pr), $buyerRfqHtml);
        $buyerRfqShowHtml = $this->actingAs($buyer)->get(route('rfq.show', $rfq))->assertOk()->getContent();
        $this->assertStringContainsString('quotationMatrixSection', $buyerRfqShowHtml);
        $this->assertSame(
            1,
            substr_count($buyerRfqHtml, '<span class="rfq-title-text" title="Digital Transformation Conclave">'),
            'Project name title should render once without duplicating the identical badge text.'
        );
        $this->assertStringNotContainsString('<i class="bi bi-folder2-open me-1"></i>Digital Transformation Conclave', $buyerRfqHtml);

        // 2) PO & GRN: Buyer on /po & /po/{po} gets no 403 PR/GRN links and sees Cancel PO + Cancelled filter
        $po = PurchaseOrder::create([
            'po_number' => 'PO-UX-001',
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'po_qty' => 2,
            'amount_incl_gst' => 100000,
            'status' => 'approved',
            'created_by' => $buyer->id,
        ]);

        $buyerPoHtml = $this->actingAs($buyer)->get(route('po'))->assertOk()->getContent();
        $this->assertStringContainsString('Cancel PO', $buyerPoHtml);
        $this->assertStringContainsString('<option value="cancelled">Cancelled</option>', $buyerPoHtml);
        $this->assertStringNotContainsString(route('requisition.show', $pr), $buyerPoHtml);

        $grn = Grn::create([
            'grn_number' => 'GRN-UX-001',
            'purchase_order_id' => $po->id,
            'purchase_requisition_id' => $pr->id,
            'item_description' => 'Core Switch',
            'po_qty' => 2,
            'received_qty' => 1,
            'receipt_date' => now()->toDateString(),
            'status' => 'pending_hod',
            'created_by' => $prExec->id,
        ]);
        $grn->approvals()->create(['level' => 1, 'role_required' => Roles::HOD, 'status' => 'pending']);

        // Buyer on /po/{po} must not see dead 403 link to /grn/{grn}
        $buyerPoShowHtml = $this->actingAs($buyer)->get(route('po.show', $po))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('grn.show', $grn), $buyerPoShowHtml);

        // 3) HOD on /grn sees View in Action section, and on /grn/{grn} can review and approve without dead 403 link to /po/{po}
        $hodGrnHtml = $this->actingAs($hod)->get(route('grn'))->assertOk()->getContent();
        $this->assertStringContainsString('View', $hodGrnHtml);
        $this->assertStringContainsString(route('grn.show', $grn), $hodGrnHtml);
        $this->assertStringNotContainsString(route('po.show', $po), $hodGrnHtml);

        $hodGrnShowHtml = $this->actingAs($hod)->get(route('grn.show', $grn))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('po.show', $po), $hodGrnShowHtml);

        // 4) PR Creator on /grn must not see dead 403 link to /po/{po}
        $prExecGrnHtml = $this->actingAs($prExec)->get(route('grn'))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('po.show', $po), $prExecGrnHtml);

        // 5) CFO on /exceptions sees Review & Decide for pending budget_exceed exception
        $exc = ExceptionRequest::create([
            'exception_type' => 'budget_exceed',
            'justification' => 'Urgent summit spend',
            'requested_by' => $admin->id,
            'approver_queue' => Roles::CFO,
            'status' => 'pending',
        ]);
        $cfoExcHtml = $this->actingAs($cfo)->get(route('exceptions'))->assertOk()->getContent();
        $this->assertStringContainsString('Review &amp; Decide', $cfoExcHtml);
    }

    public function test_all_form_creation_and_approval_chain_edge_cases_are_strictly_enforced(): void
    {
        $this->seed();

        $admin = User::where('role', Roles::ADMIN_HEAD)->firstOrFail();
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $prExec = User::where('role', Roles::PR_SR_EXEC)->firstOrFail();
        $cfo = User::where('role', Roles::CFO)->firstOrFail();
        $sg = User::where('role', Roles::SG)->firstOrFail();

        // 1) CFO's own TRF reimbursement routes to SG (no self-approval)
        $cfoTrf = Trf::create([
            'trf_number' => 'TRF-CFO-SELF',
            'employee_id' => $cfo->id,
            'booking_type' => 'self',
            'purpose' => 'Budget Board Meet',
            'route' => 'New Delhi (DEL) → Mumbai (BOM)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->subDays(5)->toDateString(),
            'travel_end' => now()->subDays(3)->toDateString(),
            'requested_advance' => 5000,
            'status' => 'approved',
        ]);

        $this->actingAs($cfo)
            ->post(route('reimb.store'), [
                'trf_id' => $cfoTrf->id,
                'claim_amount' => 7500,
                'advance_amount' => 5000,
            ])
            ->assertRedirect(route('reimb'));

        $cfoClaim = Reimbursement::where('trf_id', $cfoTrf->id)->firstOrFail();
        $this->assertSame('pending_sg', $cfoClaim->status);
        $this->actingAs($cfo)->post(route('reimb.approve', $cfoClaim))->assertForbidden();
        $this->actingAs($sg)->post(route('reimb.approve', $cfoClaim))->assertRedirect();
        $this->assertSame('approved', $cfoClaim->fresh()->status);

        // 2) SG cannot self-approve their own off-TRF claim
        $this->actingAs($sg)
            ->post(route('reimb.store'), [
                'claim_amount' => 3000,
                'reason' => 'Emergency diplomatic transport',
            ])
            ->assertRedirect(route('reimb'));
        $sgClaim = Reimbursement::where('employee_id', $sg->id)->latest('id')->firstOrFail();
        $this->assertFalse(\App\Support\PendingItems::for($sg)->contains('number', $sgClaim->claim_number));
        $this->actingAs($sg)->post(route('reimb.approve', $sgClaim))->assertForbidden();

        // 3) ALLOW_ADMIN_APPROVAL_OVERRIDE=false blocks Admin Head on Vendor L2, PO CFO, and Reimbursement
        config(['app.allow_admin_approval_override' => false]);
        $vendorL2 = Vendor::create([
            'vendor_code' => 'VEND-SOD-L2',
            'company_name' => 'SoD Vendor',
            'status' => 'pending_l2',
            'compliance_status' => 'active',
        ]);
        $vendorL2->approvals()->create(['level' => 2, 'role_required' => Roles::ASG1, 'status' => 'pending']);
        $this->actingAs($admin)->post(route('empanelment.approve', $vendorL2))->assertForbidden();

        $approvedPr = PurchaseRequisition::create([
            'pr_number' => 'PR-SOD-PO-1',
            'title' => 'IT Core Storage',
            'department' => 'IT',
            'estimated_value' => 1200000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prExec->id,
        ]);
        $empanelledVendor = Vendor::create([
            'vendor_code' => 'VEND-SOD-EMP',
            'company_name' => 'Empanelled Storage Corp',
            'status' => 'empanelled',
            'compliance_status' => 'active',
        ]);
        $poCfo = PurchaseOrder::create([
            'po_number' => 'PO-SOD-CFO-1',
            'purchase_requisition_id' => $approvedPr->id,
            'vendor_id' => $empanelledVendor->id,
            'po_qty' => 1,
            'amount_incl_gst' => 1200000,
            'status' => 'pending',
            'created_by' => $buyer->id,
        ]);
        $poCfo->approvals()->create(['level' => 1, 'role_required' => Roles::CFO, 'status' => 'pending']);
        $this->actingAs($admin)->post(route('po.approve', $poCfo))->assertForbidden();
        $this->actingAs($admin)->post(route('reimb.approve', $sgClaim))->assertForbidden();

        // 4) Blacklisted / non-empanelled vendors blocked on RFQ, PO, and FlightLog
        $blacklisted = Vendor::create([
            'vendor_code' => 'VEND-BLK-1',
            'company_name' => 'Blacklisted Agency',
            'status' => 'empanelled',
            'compliance_status' => 'blacklisted',
        ]);
        $approvedPr2 = PurchaseRequisition::create([
            'pr_number' => 'PR-BLK-2',
            'title' => 'Office Supplies',
            'department' => 'IT',
            'estimated_value' => 10000,
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $prExec->id,
        ]);
        $approvedPr2->items()->create([
            'item_name' => 'Paper Box',
            'quantity' => 5,
            'unit' => 'Boxes',
            'unit_cost' => 2000,
            'gst_percent' => 0,
            'status' => 'approved',
        ]);

        $this->actingAs($buyer)
            ->from(route('rfq.create'))
            ->post(route('rfq.store'), [
                'purchase_requisition_id' => $approvedPr2->id,
                'sector' => 'IT',
                'project_name' => 'Blacklisted Vendor RFQ Attempt',
                'vendor_ids' => [$blacklisted->id],
            ])
            ->assertRedirect(route('rfq.create'))
            ->assertSessionHasErrors('vendor_ids');

        $this->actingAs($buyer)
            ->post(route('po.store'), [
                'purchase_requisition_id' => $approvedPr2->id,
                'vendor_id' => $blacklisted->id,
                'amount_incl_gst' => 10000,
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->post(route('flight.store'), [
                'trf_id' => $cfoTrf->id,
                'type' => 'Flight ticket',
                'vendor_id' => $blacklisted->id,
                'cost' => 6500,
            ])
            ->assertStatus(422);

        // 5) Exception po_force_close and grn_reversal require valid target records
        $this->actingAs($admin)
            ->from(route('exceptions.create'))
            ->post(route('exceptions.store'), [
                'exception_type' => 'po_force_close',
                'justification' => 'Missing PO selection',
            ])
            ->assertRedirect(route('exceptions.create'))
            ->assertSessionHasErrors('reference_id');

        $this->actingAs($admin)
            ->from(route('exceptions.create'))
            ->post(route('exceptions.store'), [
                'exception_type' => 'grn_reversal',
                'justification' => 'Missing GRN selection',
            ])
            ->assertRedirect(route('exceptions.create'))
            ->assertSessionHasErrors('reference_id');

        // 6) On-behalf TRF targeting a deactivated employee is blocked
        $inactiveStaff = User::factory()->create([
            'name' => 'Deactivated Staff',
            'role' => Roles::PR_SR_EXEC,
            'department' => 'IT',
            'is_active' => false,
        ]);
        $this->actingAs($admin)
            ->from(route('trf.create'))
            ->post(route('trf.store'), [
                'booking_type' => 'on_behalf',
                'employee_id' => $inactiveStaff->id,
                'from_city' => 'New Delhi (DEL)',
                'to_city' => 'Mumbai (BOM)',
                'purpose' => 'Conference',
                'travel_mode' => 'Flight',
                'travel_start' => now()->addDays(5)->toDateString(),
                'travel_end' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect(route('trf.create'))
            ->assertSessionHasErrors('employee_id');

        // 7) Cancelled PO cannot be edited or resurrected without budget reservation
        $poCfo->update(['status' => 'cancelled']);
        $this->actingAs($buyer)->get(route('po.edit', $poCfo))->assertStatus(422);
        $this->actingAs($buyer)->put(route('po.update', $poCfo), ['po_qty' => 2])->assertStatus(422);
        $this->actingAs($admin)->post(route('po.update-terms', $poCfo), ['event_name' => 'Hack'])->assertStatus(422);
        $this->assertSame('cancelled', $poCfo->fresh()->status);
    }

    private function assertCleanHtml(string $html, string $context): void
    {
        $issues = [];
        $this->collectHtmlIssues($html, $context, $issues, requireShell: false);
        $this->assertEmpty($issues, implode("\n", $issues));
    }

    /**
     * @param  list<string>  $issues
     */
    private function collectHtmlIssues(string $html, string $context, array &$issues, bool $requireShell = true): void
    {
        if (trim($html) === '') {
            $issues[] = "$context rendered empty HTML";

            return;
        }
        if (str_contains($html, 'Undefined variable') || str_contains($html, 'Undefined array key')) {
            $issues[] = "$context contains PHP undefined warning";
        }
        if (preg_match('/@(if|foreach|forelse|endif|endforeach|endforelse|endsection|extends)\b/', $html)) {
            $issues[] = "$context leaked raw Blade directive in rendered HTML";
        }
        if (str_contains($html, 'FatalError') || str_contains($html, 'SQLSTATE[')) {
            $issues[] = "$context contains server/SQL error text";
        }
        if ($requireShell) {
            if (! str_contains($html, 'Shell.init(')) {
                $issues[] = "$context missing Shell.init()";
            }
            if (! str_contains($html, 'window.AUTH_USER')) {
                $issues[] = "$context missing window.AUTH_USER payload";
            }
        }
    }
}
