<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class PurchaseOrderPdfTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createTestPo(): PurchaseOrder
    {
        $buyer = User::where('role', Roles::BUYER)->firstOrFail();
        $vendor = Vendor::firstOrCreate(
            ['vendor_code' => 'VEN-TEST'],
            ['company_name' => 'Test Vendor Pvt Ltd', 'status' => 'approved', 'category' => 'IT Services']
        );
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/FY26-27/20261107-001',
            'department' => 'IT',
            'status' => 'approved',
            'created_by' => $buyer->id,
        ]);
        $pr->items()->create([
            'item_name' => 'Dell Server',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 50000,
            'gst_percent' => 18,
        ]);

        return PurchaseOrder::create([
            'po_number' => 'PO-2026-TEST-1',
            'purchase_requisition_id' => $pr->id,
            'vendor_id' => $vendor->id,
            'po_qty' => 2,
            'delivery_date' => now()->addDays(7)->toDateString(),
            'amount_incl_gst' => 118000,
            'status' => 'approved',
            'created_by' => $buyer->id,
        ]);
    }

    public function test_can_download_and_preview_po_pdf(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::BUYER);

        // 1. Download PDF
        $resDownload = $this->get(route('po.pdf', $po));
        $resDownload->assertOk();
        $this->assertStringContainsString('application/pdf', $resDownload->headers->get('content-type'));

        // 2. Preview PDF
        $resPreview = $this->get(route('po.preview-pdf', $po));
        $resPreview->assertOk();
        $this->assertStringContainsString('application/pdf', $resPreview->headers->get('content-type'));
    }

    public function test_buyer_is_forbidden_from_editing_terms(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::BUYER);

        $res = $this->post(route('po.update-terms', $po), [
            'terms_and_conditions' => 'Buyer modified terms',
        ]);

        $res->assertForbidden();
    }

    public function test_admin_head_can_update_editable_terms_and_commercials(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::ADMIN_HEAD);

        $customTerms = "1. Immediate delivery required.\n2. Payment within 15 days.";
        $res = $this->post(route('po.update-terms', $po), [
            'event_name' => 'ASSOCHAM National Summit 2026',
            'payment_terms' => '50% advance, 50% on delivery',
            'delivery_terms' => 'Direct to venue',
            'delivery_address' => 'Vigyan Bhawan, New Delhi',
            'contact_person' => 'Rajesh Sharma',
            'contact_number' => '9876543210',
            'terms_and_conditions' => $customTerms,
        ]);

        $res->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame('ASSOCHAM National Summit 2026', $po->event_name);
        $this->assertSame('50% advance, 50% on delivery', $po->payment_terms);
        $this->assertSame('Direct to venue', $po->delivery_terms);
        $this->assertSame('Vigyan Bhawan, New Delhi', $po->delivery_address);
        $this->assertSame('Rajesh Sharma', $po->contact_person);
        $this->assertSame('9876543210', $po->contact_number);
        $this->assertSame($customTerms, $po->terms_and_conditions);
    }

    public function test_buyer_edit_po_view_does_not_contain_terms_or_commercial_inputs(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::BUYER);

        $res = $this->get(route('po.edit', $po));
        $res->assertOk();
        $res->assertSee('Total Order Quantity');
        $res->assertSee('Target Delivery Date');
        $res->assertDontSee('Terms &amp; Conditions (Locked)', false);
        $res->assertDontSee('Terms & Conditions (Locked)');
        $res->assertDontSee('Commercial Terms &amp; Legal Clauses', false);
        $res->assertDontSee('name="event_name"', false);
        $res->assertDontSee('name="terms_and_conditions"', false);
    }

    public function test_admin_head_edit_po_view_contains_terms_and_commercial_inputs(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::ADMIN_HEAD);

        $res = $this->get(route('po.edit', $po));
        $res->assertOk();
        $res->assertSee('Total Order Quantity');
        $res->assertSee('Commercial Terms &amp; Legal Clauses', false);
        $res->assertSee('name="event_name"', false);
        $res->assertSee('name="terms_and_conditions"', false);
    }

    public function test_buyer_submitting_po_update_cannot_alter_terms_or_commercials(): void
    {
        $po = $this->createTestPo();
        $originalTerms = $po->terms_and_conditions;
        $this->loginAs(Roles::BUYER);

        $res = $this->put(route('po.update', $po), [
            'po_qty' => 5,
            'delivery_date' => now()->addDays(14)->toDateString(),
            'event_name' => 'Buyer Attempted Overwrite',
            'terms_and_conditions' => 'Buyer Malicious Overwrite',
        ]);

        $res->assertRedirect(route('po.show', $po));

        $po->refresh();
        $this->assertSame(5, $po->po_qty);
        $this->assertNotSame('Buyer Attempted Overwrite', $po->event_name);
        $this->assertNotSame('Buyer Malicious Overwrite', $po->terms_and_conditions);
        $this->assertSame($originalTerms, $po->terms_and_conditions);
    }

    public function test_approver_sees_action_required_integrated_with_po_header(): void
    {
        $po = $this->createTestPo();
        $po->update(['status' => 'pending']);
        $po->approvals()->create([
            'level' => 1,
            'role_required' => Roles::ADMIN_HEAD,
            'status' => 'pending',
        ]);

        $this->loginAs(Roles::ADMIN_HEAD);

        $res = $this->get(route('po.show', $po));
        $res->assertOk();
        $res->assertSee($po->po_number);
        $res->assertSee('Your Action Required');
        $res->assertSee('Approve &amp; Issue PO', false);
        $res->assertSee('Decline PO');
        $res->assertSee('declinePoModal');
        $res->assertSee('Reason for Rejection');
    }

    public function test_cannot_cancel_po_with_grns_gracefully_redirects_with_error(): void
    {
        $po = $this->createTestPo();
        $this->loginAs(Roles::ADMIN_HEAD);

        // Create a GRN for this PO
        $po->grns()->create([
            'grn_number' => 'GRN-2026-TEST-1',
            'vendor_id' => $po->vendor_id,
            'po_qty' => $po->po_qty,
            'received_qty' => $po->po_qty,
            'receipt_date' => now()->toDateString(),
            'status' => 'approved',
            'created_by' => $po->created_by,
        ]);

        // Attempting to cancel via POST should redirect with flash error, never 422 crash
        $res = $this->post(route('po.cancel', $po));
        $res->assertRedirect(route('po.show', $po));
        $res->assertSessionHas('error', 'Cannot cancel a Purchase Order that already has Goods Receipt Notes logged against it.');

        // On show page, Cancel button must NOT be rendered
        $resShow = $this->get(route('po.show', $po));
        $resShow->assertOk();
        $resShow->assertDontSee('Cancel PO');
    }
}
