<?php

namespace Tests\Feature;

use App\Models\DepartmentBudget;
use App\Models\Grievance;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Reimbursement;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class FullSystemE2eAuditTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    public function test_non_core_employee_roles_and_vendor_dossier_access_controls(): void
    {
        $membershipOps = User::create([
            'name' => 'Anmol Kumar',
            'email' => 'anmol.ops@assocham.test',
            'password' => 'Secret@123',
            'role' => 'membership_ops',
            'department' => 'Membership Ops',
            'email_verified_at' => now(),
        ]);

        // Fallback roles can see Dashboard, TRF, Reimbursements, Grievances, Notifications
        $this->actingAs($membershipOps)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-module="trf"', false)
            ->assertSee('data-module="reimb"', false)
            ->assertSee('data-module="grievance"', false)
            ->assertSee('data-module="notifications"', false)
            ->assertDontSee('data-module="po"', false)
            ->assertDontSee('data-module="users"', false);

        // Vendor dossier route rejects users without empanelment/rfq/po/traveleval access
        $vendor = Vendor::first() ?? Vendor::create([
            'vendor_code' => 'VEN-TEST-01',
            'company_name' => 'Test Vendor Co',
            'pan' => 'AAACT1234E',
            'gstin' => '07AAACT1234E1Z5',
            'category' => 'IT Hardware',
            'status' => 'approved',
            'compliance_status' => 'active',
            'submitted_by' => $membershipOps->id,
        ]);
        $this->actingAs($membershipOps)
            ->get(route('empanelment.show', $vendor))
            ->assertForbidden();
        $this->actingAs($membershipOps)
            ->get(route('vendor.show', $vendor))
            ->assertForbidden();

        // Buyer (has empanelment/rfq/po) can inspect vendor dossier
        $this->loginAs(Roles::BUYER);
        $this->get(route('empanelment.show', $vendor))->assertOk();
        $this->get(route('vendor.show', $vendor))->assertOk();
    }

    public function test_every_module_index_and_create_page_renders_across_authorized_roles_with_shell_init(): void
    {
        $this->loginAs(Roles::ADMIN_HEAD);

        $pages = [
            route('dashboard'),
            route('onboarding'),
            route('empanelment'),
            route('requisition'),
            route('requisition.create'),
            route('rfq'),
            route('rfq.create'),
            route('po'),
            route('po.create'),
            route('grn'),
            route('grn.create'),
            route('trf'),
            route('trf.create'),
            route('flight'),
            route('flight.create'),
            route('reimb'),
            route('reimb.create'),
            route('traveleval'),
            route('traveleval.create'),
            route('checks-balances'),
            route('exceptions'),
            route('exceptions.create'),
            route('grievance'),
            route('grievance.create'),
            route('audit'),
            route('notifications'),
            route('users'),
            route('users.create'),
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('Shell.init', false);
        }
    }

    public function test_end_to_end_procurement_stage_by_stage_workflow(): void
    {
        $fy = DepartmentBudget::currentFiscalYear();
        $budget = DepartmentBudget::where('department', 'Operations')->where('fiscal_year', $fy)->first()
            ?? DepartmentBudget::create([
                'department' => 'Operations',
                'fiscal_year' => $fy,
                'allocated_amount' => 1000000,
                'utilised_amount' => 0,
                'gst_rate' => 18,
            ]);

        // Stage 1: Buyer onboards 2 vendors & walks empanelment (Admin Head -> ASG -> CFO)
        $this->loginAs(Roles::BUYER);
        $this->post(route('onboarding.store'), [
            'company_name' => 'Apex Industrial Gear Pvt Ltd',
            'pan' => 'AAACA9988K',
            'gstin' => '07AAACA9988K1Z1',
            'category' => 'IT Hardware',
            'email' => 'apex@vendor.test',
            'phone' => '9876543210',
            'location' => 'New Delhi',
        ])->assertRedirect(route('empanelment'));

        $vendor1 = Vendor::where('company_name', 'Apex Industrial Gear Pvt Ltd')->firstOrFail();

        $this->post(route('onboarding.store'), [
            'company_name' => 'Beacon Systems India Ltd',
            'pan' => 'AAACB9977M',
            'gstin' => '07AAACB9977M1Z2',
            'category' => 'IT Hardware',
            'email' => 'beacon@vendor.test',
            'phone' => '9876543211',
            'location' => 'Noida',
        ])->assertRedirect(route('empanelment'));

        $vendor2 = Vendor::where('company_name', 'Beacon Systems India Ltd')->firstOrFail();

        foreach ([$vendor1, $vendor2] as $vendor) {
            foreach ([Roles::ADMIN_HEAD, Roles::ASG1, Roles::ASG2, Roles::CFO] as $approverRole) {
                $v = $vendor->fresh();
                $pending = $v->approvals()->where('status', 'pending')->orderBy('level')->first();
                if ($pending && in_array($approverRole, explode(',', $pending->role_required), true)) {
                    $this->loginAs($approverRole);
                    $this->post(route('empanelment.approve', $v))->assertRedirect();
                }
            }
            $this->assertSame('approved', $vendor->fresh()->status);
        }

        // Stage 2: PR & GRN Creator raises PR -> L1 (DSG) -> L2 (Head of States) -> CFO -> SG
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('requisition.store'), [
            'title' => 'Conference Workstations Batch',
            'department' => 'Operations',
            'category' => 'IT Hardware',
            'purpose' => 'Equip regional conference hall workstations.',
            'justification' => 'Annual operations upgrade',
            'items' => [
                [
                    'item_name' => 'Business Laptop',
                    'description' => '16GB RAM / 512GB NVMe',
                    'quantity' => 5,
                    'unit_cost' => 50000,
                ],
            ],
        ])->assertRedirect(route('requisition'));

        $pr = PurchaseRequisition::where('title', 'Conference Workstations Batch')->firstOrFail();
        $this->assertSame('pending_l1', $pr->status);

        // Unauthorized role cannot approve PR
        $this->loginAs(Roles::BUYER);
        $this->post(route('requisition.approve', $pr))->assertForbidden();

        // Approve through chain
        while (true) {
            $pr->refresh();
            $step = $pr->approvals()->where('status', 'pending')->orderBy('level')->first();
            if (! $step) {
                break;
            }
            $this->loginAs($step->role_required);
            $this->post(route('requisition.approve', $pr))->assertRedirect();
        }
        $this->assertSame('approved', $pr->fresh()->status);

        // Stage 3: Buyer creates RFQ inviting 2 vendors, enters quotes & selects winner
        $this->loginAs(Roles::BUYER);
        $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT Infrastructure',
            'project_name' => 'Regional Upgrade',
            'due_date' => now()->addDays(7)->toDateString(),
            'vendor_ids' => [$vendor1->id, $vendor2->id],
        ])->assertRedirect();

        $rfq = Rfq::where('purchase_requisition_id', $pr->id)->firstOrFail();
        $this->post(route('rfq.quotes-received', $rfq))->assertRedirect();
        $this->post(route('rfq.save-quotations', $rfq), [
            'quotes' => [
                [
                    'vendor_id' => $vendor1->id,
                    'basic_price' => 225000,
                    'gst_amount' => 40500,
                    'transportation_cost' => 1000,
                    'delivery_days' => 5,
                    'warranty' => '24 Months',
                    'payment_terms' => 'Net 30',
                ],
                [
                    'vendor_id' => $vendor2->id,
                    'basic_price' => 240000,
                    'gst_amount' => 43200,
                    'transportation_cost' => 0,
                    'delivery_days' => 10,
                    'warranty' => '12 Months',
                    'payment_terms' => 'Net 15',
                ],
            ],
        ])->assertRedirect();

        $this->post(route('rfq.select-vendor', $rfq), [
            'vendor_id' => $vendor1->id,
            'selection_remarks' => 'L1 lowest evaluated landed cost with 24 months warranty.',
        ])->assertRedirect();

        $ccs = $rfq->fresh()->costComparativeStatement;
        $this->assertNotNull($ccs);
        $this->assertSame($vendor1->id, (int) $ccs->selected_vendor_id);

        // Stage 4: Buyer creates PO -> CFO & SG approve -> PDF preview renders cleanly
        $this->post(route('po.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor1->id,
            'amount_incl_gst' => 265500,
            'delivery_date' => now()->addDays(14)->toDateString(),
            'payment_terms' => 'Net 30',
        ])->assertRedirect();

        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->firstOrFail();
        while (true) {
            $po->refresh();
            $step = $po->approvals()->where('status', 'pending')->orderBy('level')->first();
            if (! $step) {
                break;
            }
            $this->loginAs($step->role_required);
            $this->post(route('po.approve', $po))->assertRedirect();
        }
        $this->assertSame('approved', $po->fresh()->status);

        $this->loginAs(Roles::BUYER);
        $this->get(route('po.preview-pdf', $po))->assertOk();

        // Stage 5: PR & GRN Creator records GRN -> HOD approves
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->post(route('grn.store'), [
            'purchase_order_id' => $po->id,
            'received_qty' => 5,
            'received_date' => now()->toDateString(),
            'remarks' => 'All 5 workstations verified in good condition.',
        ])->assertRedirect(route('grn'));

        $grn = Grn::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame('pending_hod', $grn->status);

        $this->loginAs(Roles::HOD);
        $this->post(route('grn.approve', $grn))->assertRedirect();
        $this->assertSame('approved', $grn->fresh()->status);
    }

    public function test_end_to_end_travel_and_governance_workflows(): void
    {
        // Clean existing overdue seed TRFs for Buyer so fresh flow runs cleanly
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        Trf::where('employee_id', $buyer->id)->delete();

        $this->actingAs($buyer);
        $this->post(route('trf.store'), [
            'booking_type' => 'self',
            'travel_type' => 'domestic',
            'from_city' => 'New Delhi',
            'to_city' => 'Mumbai',
            'travel_mode' => 'flight',
            'purpose' => 'Western Region Conclave',
            'departure_date' => now()->subDays(5)->toDateString(),
            'return_date' => now()->subDays(2)->toDateString(),
            'estimated_cost' => 18000,
            'advance_amount' => 5000,
            'accommodation_required' => '1',
            'hotel_city' => 'Mumbai',
        ])->assertRedirect(route('trf'));

        $trf = Trf::where('employee_id', $buyer->id)->where('purpose', 'Western Region Conclave')->firstOrFail();
        $this->assertSame('pending_asg', $trf->status);

        // ASG2 approves domestic TRF
        $this->loginAs(Roles::ASG2);
        $this->post(route('trf.approve', $trf))->assertRedirect();
        $this->assertSame('approved', $trf->fresh()->status);

        // Employee submits Reimbursement & Advance settlement -> CFO approves
        $this->actingAs($buyer);
        $this->post(route('reimb.store'), [
            'trf_id' => $trf->id,
            'actual_expense' => 16500,
            'advance_taken' => 5000,
            'notes' => 'Conclave airfare & hotel bills.',
            'items' => [
                ['category' => 'Airfare', 'description' => 'DEL-BOM Return', 'amount' => 11500, 'expense_date' => now()->subDays(4)->toDateString()],
                ['category' => 'Lodging', 'description' => 'Hotel 2 nights', 'amount' => 5000, 'expense_date' => now()->subDays(3)->toDateString()],
            ],
        ])->assertRedirect(route('reimb'));

        $reimb = Reimbursement::where('trf_id', $trf->id)->firstOrFail();
        $this->loginAs(Roles::CFO);
        $this->post(route('reimb.approve', $reimb))->assertRedirect();
        $this->assertSame('approved', $reimb->fresh()->status);
        $this->assertFalse($trf->fresh()->isSettlementOverdue());

        // Employee raises Grievance -> HOD responds -> escalates -> Admin Head resolves
        $this->actingAs($buyer);
        $this->post(route('grievance.store'), [
            'subject_category' => 'Reimbursement & Advance Settlement',
            'reimbursement_id' => $reimb->id,
            'subject' => 'Delay in NEFT payout credit',
            'description' => 'Approved 3 days ago, payout ref awaited.',
        ])->assertRedirect(route('grievance'));

        $ticket = Grievance::where('subject', 'Delay in NEFT payout credit')->firstOrFail();

        $this->loginAs(Roles::HOD);
        $this->post(route('grievance.respond', $ticket), [
            'message' => 'Verified with department, escalating for Admin dispatch confirmation.',
        ])->assertRedirect();
        $this->post(route('grievance.escalate', $ticket), [
            'message' => 'Escalating to Admin Head.',
        ])->assertRedirect();
        $this->assertSame(Roles::ADMIN_HEAD, $ticket->fresh()->current_owner_role);

        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('grievance.resolve', $ticket), [
            'resolution_notes' => 'NEFT UTR confirmed and shared with employee.',
        ])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
    }
}
