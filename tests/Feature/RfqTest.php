<?php

namespace Tests\Feature;

use App\Models\PurchaseRequisition;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqTest extends TestCase
{
    use RefreshDatabase;

    private function createBuyer(): User
    {
        return User::factory()->create([
            'role' => Roles::BUYER,
            'department' => 'Procurement',
        ]);
    }

    private function createApprovedPr(): PurchaseRequisition
    {
        $creator = User::factory()->create(['role' => Roles::PR_SR_EXEC, 'department' => 'IT']);
        $pr = PurchaseRequisition::create([
            'pr_number' => 'PR/FY26-27/20261110-8801',
            'title' => 'Server Hardware Upgrade',
            'department' => 'IT',
            'status' => 'approved',
            'current_level' => 4,
            'created_by' => $creator->id,
        ]);

        $pr->items()->create([
            'item_name' => 'Rackmount Server 2U',
            'quantity' => 2,
            'unit' => 'Units',
            'unit_cost' => 150000,
            'gst_percent' => 18,
            'status' => 'approved',
        ]);

        return $pr;
    }

    private function createEmpanelledVendor(string $name, string $category = 'IT & Electronics'): Vendor
    {
        return Vendor::create([
            'vendor_code' => 'VEND-' . random_int(1000, 9999),
            'company_name' => $name,
            'category' => $category,
            'status' => 'empanelled',
        ]);
    }

    public function test_rfq_requires_sector_and_unique_project_name(): void
    {
        $buyer = $this->createBuyer();
        $pr = $this->createApprovedPr();
        $vendor = $this->createEmpanelledVendor('Alpha Technologies');

        $this->actingAs($buyer);

        // Missing sector and project_name
        $res = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'vendor_ids' => [$vendor->id],
        ]);
        $res->assertSessionHasErrors(['sector', 'project_name']);

        // Create successfully with sector and unique project_name
        $res = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT & Systems',
            'project_name' => 'Server Upgrade FY26-27',
            'vendor_ids' => [$vendor->id],
        ]);
        $res->assertRedirect(route('rfq'));

        $this->assertDatabaseHas('rfqs', [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT & Systems',
            'project_name' => 'Server Upgrade FY26-27',
        ]);
    }

    public function test_rfq_blocks_duplicate_project_name(): void
    {
        $buyer = $this->createBuyer();
        $pr = $this->createApprovedPr();
        $vendor = $this->createEmpanelledVendor('Beta Networks');

        Rfq::create([
            'rfq_number' => 'RFQ-1001',
            'sector' => 'IT',
            'project_name' => 'Existing Unique Project',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'dispatched',
        ]);

        $this->actingAs($buyer);

        // Try creating with duplicate project name
        $res = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT',
            'project_name' => 'Existing Unique Project',
            'vendor_ids' => [$vendor->id],
        ]);

        $res->assertSessionHasErrors(['project_name']);
        $this->assertStringContainsString(
            'The project name has already been taken',
            session('errors')->first('project_name')
        );
    }

    public function test_email_sent_to_vendors_on_rfq_dispatch(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $buyer = $this->createBuyer();
        $pr = $this->createApprovedPr();
        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-5001',
            'company_name' => 'Tech Solutions Ltd',
            'email' => 'sales@techsolutions.com',
            'category' => 'IT & Electronics',
            'status' => 'empanelled',
        ]);
        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-5002',
            'company_name' => 'Network World',
            'email' => 'rfq@networkworld.com',
            'category' => 'IT & Electronics',
            'status' => 'empanelled',
        ]);

        $this->actingAs($buyer);

        $res = $this->post(route('rfq.store'), [
            'purchase_requisition_id' => $pr->id,
            'sector' => 'IT',
            'project_name' => 'Data Center Refresh 2026',
            'due_date' => now()->addDays(14)->toDateString(),
            'vendor_ids' => [$vendorA->id, $vendorB->id],
        ]);

        $res->assertRedirect(route('rfq'));

        $rfq = Rfq::where('project_name', 'Data Center Refresh 2026')->firstOrFail();
        $this->assertNotNull($rfq->email_dispatched_at);
        $this->assertEquals(now()->addDays(14)->toDateString(), $rfq->due_date->toDateString());

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\RfqDispatchedMail::class, 2);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\RfqDispatchedMail::class, function ($mail) use ($vendorA) {
            return $mail->hasTo('sales@techsolutions.com') && $mail->vendor->id === $vendorA->id;
        });
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\RfqDispatchedMail::class, function ($mail) use ($vendorB) {
            return $mail->hasTo('rfq@networkworld.com') && $mail->vendor->id === $vendorB->id;
        });

        // Verify rfq_vendors recorded email_sent_at timestamp
        $this->assertDatabaseHas('rfq_vendors', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorA->id,
        ]);
        $this->assertNotNull($rfq->rfqVendors()->where('vendor_id', $vendorA->id)->first()->email_sent_at);
    }

    public function test_can_resend_rfq_details_email_to_vendor(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $buyer = $this->createBuyer();
        $pr = $this->createApprovedPr();
        $vendor = Vendor::create([
            'vendor_code' => 'VEND-5003',
            'company_name' => 'Prime Hardware',
            'email' => 'bids@primehardware.com',
            'category' => 'IT & Electronics',
            'status' => 'empanelled',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-7788',
            'sector' => 'IT',
            'project_name' => 'Cloud Cluster Hardware',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'dispatched',
        ]);
        $rfq->rfqVendors()->create([
            'vendor_id' => $vendor->id,
            'response_status' => 'awaiting',
        ]);

        $this->actingAs($buyer);

        $res = $this->post(route('rfq.vendor.resend-email', ['rfq' => $rfq, 'vendor' => $vendor]));
        $res->assertSessionHas('status');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\RfqDispatchedMail::class, function ($mail) use ($vendor, $rfq) {
            return $mail->hasTo('bids@primehardware.com') && $mail->rfq->id === $rfq->id;
        });

        $this->assertNotNull($rfq->fresh()->email_dispatched_at);
        $this->assertNotNull($rfq->rfqVendors()->where('vendor_id', $vendor->id)->first()->email_sent_at);
    }

    public function test_can_manually_choose_best_vendor_with_remarks(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $buyer = $this->createBuyer();
        $pr = $this->createApprovedPr();
        $vendorA = Vendor::create([
            'vendor_code' => 'VEND-7001',
            'company_name' => 'Cheaper Co',
            'category' => 'IT',
            'status' => 'empanelled',
        ]);
        $vendorB = Vendor::create([
            'vendor_code' => 'VEND-7002',
            'company_name' => 'High Quality Tech',
            'email' => 'sales@hqtech.com',
            'category' => 'IT',
            'status' => 'empanelled',
        ]);

        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-9922',
            'sector' => 'IT',
            'project_name' => 'Datacenter Server Purchase',
            'purchase_requisition_id' => $pr->id,
            'requisition_date' => now()->toDateString(),
            'poc_user_id' => $buyer->id,
            'status' => 'dispatched',
        ]);

        $rfq->rfqVendors()->create(['vendor_id' => $vendorA->id, 'response_status' => 'awaiting']);
        $rfq->rfqVendors()->create(['vendor_id' => $vendorB->id, 'response_status' => 'awaiting']);

        $this->actingAs($buyer);

        // Save quotes: Vendor A is L1 (cheaper 10,000), Vendor B is L2 (more expensive 12,000)
        $this->post(route('rfq.save-quotations', $rfq), [
            'quotes' => [
                [
                    'vendor_id' => $vendorA->id,
                    'basic_price' => 10000,
                    'gst_amount' => 1800,
                    'transportation_cost' => 0,
                    'delivery_days' => 15,
                ],
                [
                    'vendor_id' => $vendorB->id,
                    'basic_price' => 12000,
                    'gst_amount' => 2160,
                    'transportation_cost' => 0,
                    'delivery_days' => 5,
                ],
            ],
        ])->assertSessionHas('status');

        $ccs = $rfq->fresh()->costComparativeStatement;
        $this->assertNotNull($ccs);
        // By default, lowestQuote is Vendor A
        $this->assertEquals($vendorA->id, $ccs->lowestQuote()->vendor_id);
        $this->assertEquals($vendorA->id, $ccs->winningQuote()->vendor_id);

        // Manually select Vendor B as best vendor with remarks (e.g. faster 5-day delivery and better warranty)
        $res = $this->post(route('rfq.select-vendor', $rfq), [
            'vendor_id' => $vendorB->id,
            'selection_remarks' => 'Selected due to critical 5-day expedited delivery timeline and enterprise onsite warranty.',
        ]);
        $res->assertSessionHas('status');

        $ccs->refresh();
        $this->assertEquals($vendorB->id, $ccs->selected_vendor_id);
        $this->assertStringContainsString('Selected due to critical 5-day expedited delivery', $ccs->selection_remarks);
        $this->assertEquals($buyer->id, $ccs->selected_by);

        // winningQuote now returns the manually selected vendor (Vendor B) regardless of L1 price ranking
        $this->assertEquals($vendorB->id, $ccs->winningQuote()->vendor_id);
        $this->assertTrue($ccs->quotes()->where('vendor_id', $vendorB->id)->first()->is_selected);
        $this->assertFalse($ccs->quotes()->where('vendor_id', $vendorA->id)->first()->is_selected);

        // Verify award email sent to Vendor B
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\VendorRfqAwardedMail::class, function ($mail) use ($vendorB, $rfq) {
            return $mail->hasTo('sales@hqtech.com') && $mail->vendor->id === $vendorB->id && $mail->rfq->id === $rfq->id;
        });

        // Test resending award email
        $resendRes = $this->post(route('rfq.resend-award-email', $rfq));
        $resendRes->assertSessionHas('status');
        $this->assertNotNull($ccs->fresh()->award_email_sent_at);

        // Test RFQ table view renders "Generate PO" button for awarded vendor
        $tableRes = $this->get(route('rfq'));
        $tableRes->assertOk();
        $tableRes->assertSee('Generate PO');
        $tableRes->assertSee(route('po.create', ['pr_id' => $pr->id, 'vendor_id' => $vendorB->id]));
    }
}

