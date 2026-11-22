<?php

namespace Tests\Feature;

use App\Mail\VendorEmpanelledMail;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_only_authorized_roles_may_submit_a_vendor(): void
    {
        // Unauthorized role (e.g. PR Senior Executive) cannot submit
        $this->loginAs(Roles::PR_SR_EXEC);

        $this->post(route('onboarding.store'), ['company_name' => 'Blocked Vendor'])
            ->assertForbidden();

        // Admin Head and Buyer are authorized
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->get(route('onboarding'))->assertOk();
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

    public function test_can_edit_and_delete_vendor_while_pending_approval(): void
    {
        $vendor = $this->submitVendor();

        $this->loginAs(Roles::BUYER);

        // Edit page loads
        $this->get(route('empanelment.edit', $vendor))->assertOk()
            ->assertSee('Edit Vendor Application')
            ->assertSee($vendor->company_name);

        // Update vendor details
        $this->put(route('empanelment.update', $vendor), [
            'company_name' => 'Updated Traders Pvt Ltd',
            'pan' => 'AABCT1234E',
            'category' => 'Hardware & IT',
            'contact_person' => 'Ramesh Kumar',
        ])->assertRedirect(route('empanelment.show', $vendor));

        $this->assertSame('Updated Traders Pvt Ltd', $vendor->fresh()->company_name);
        $this->assertSame('Hardware & IT', $vendor->fresh()->category);

        // Empanelled list shows Edit and Delete actions in dropdown
        $this->get(route('empanelment'))->assertOk()
            ->assertSee('Edit Application')
            ->assertSee('Delete Vendor');

        // Delete vendor before approval/rejection
        $this->delete(route('empanelment.destroy', $vendor))
            ->assertRedirect(route('empanelment'));

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_can_edit_correct_resubmit_and_delete_rejected_vendor(): void
    {
        $vendor = $this->submitVendor();

        // Advance to rejected
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.reject', $vendor), ['comments' => 'Tax document discrepancy']);

        $this->assertSame('rejected', $vendor->fresh()->status);

        $this->loginAs(Roles::BUYER);

        // Edit page loads for rejected vendor
        $this->get(route('empanelment.edit', $vendor))->assertOk()
            ->assertSee('Correct & Re-submit Vendor Application')
            ->assertSee($vendor->company_name);

        // Empanelment directory shows 'Correct & Resubmit' and 'Delete Vendor'
        $this->get(route('empanelment'))->assertOk()
            ->assertSee('Correct & Resubmit')
            ->assertSee('Delete Vendor');

        // Submitting edit on rejected vendor resets status to pending_admin_head and re-seeds approvals
        $this->put(route('empanelment.update', $vendor), [
            'company_name' => 'Corrected Traders Pvt Ltd',
            'pan' => 'AABCT1234E',
            'category' => 'Office Equipment',
        ])->assertRedirect(route('empanelment.show', $vendor));

        $fresh = $vendor->fresh();
        $this->assertSame('Corrected Traders Pvt Ltd', $fresh->company_name);
        $this->assertSame('pending_admin_head', $fresh->status);
        $this->assertCount(4, $fresh->approvals);

        // Delete rejected vendor
        $vendor2 = $this->submitVendor();
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.reject', $vendor2), ['comments' => 'Invalid GST']);

        $this->loginAs(Roles::BUYER);
        $this->delete(route('empanelment.destroy', $vendor2))
            ->assertRedirect(route('empanelment'));

        $this->assertDatabaseMissing('vendors', ['id' => $vendor2->id]);
    }

    public function test_cannot_edit_or_delete_empanelled_vendor(): void
    {
        $vendor = $this->submitVendor();

        // Full approval walk to approved
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::ASG2);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::CFO);
        $this->post(route('empanelment.approve', $vendor));

        $this->assertSame('approved', $vendor->fresh()->status);

        $this->loginAs(Roles::BUYER);
        $this->get(route('empanelment.edit', $vendor))->assertStatus(422);
        $this->put(route('empanelment.update', $vendor), ['company_name' => 'Cannot Edit'])->assertStatus(422);
        $this->delete(route('empanelment.destroy', $vendor))->assertStatus(422);
    }

    public function test_can_blacklist_vendor_with_mandatory_reason_and_locks_permanently(): void
    {
        $vendor = $this->submitVendor();

        // Non-admin roles (e.g. Buyer) cannot blacklist or unblacklist
        $this->loginAs(Roles::BUYER);
        $this->post(route('empanelment.blacklist', $vendor), ['reason' => 'Some violation'])->assertForbidden();

        // Only Admin Head can blacklist
        $this->loginAs(Roles::ADMIN_HEAD);

        // Validation: reason is mandatory
        $this->post(route('empanelment.blacklist', $vendor), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        // Blacklist with valid reason
        $this->post(route('empanelment.blacklist', $vendor), [
            'reason' => 'Fraudulent GST documentation provided during compliance audit.',
        ])->assertRedirect(route('empanelment.show', $vendor));

        $fresh = $vendor->fresh();
        $this->assertSame('blacklisted', $fresh->compliance_status);
        $this->assertSame('Fraudulent GST documentation provided during compliance audit.', $fresh->blacklist_reason);
        $this->assertNotNull($fresh->blacklisted_at);
        $this->assertTrue($fresh->isBlacklisted());

        // Any pending approvals are cancelled/rejected
        $this->assertSame(0, $fresh->approvals()->where('status', 'pending')->count());

        // Blacklisted vendor is permanently locked: Edit and Delete are blocked (403)
        $this->loginAs(Roles::BUYER);
        $this->get(route('empanelment.edit', $vendor))->assertForbidden();
        $this->put(route('empanelment.update', $vendor), ['company_name' => 'Locked'])->assertForbidden();
        $this->delete(route('empanelment.destroy', $vendor))->assertForbidden();

        // Buyer cannot unblacklist
        $this->post(route('empanelment.unblacklist', $vendor), ['reason' => 'Restored'])->assertForbidden();

        // UI renders Blacklisted badge and locks action options
        $this->get(route('empanelment'))->assertOk()
            ->assertSee('Blacklisted');

        $this->get(route('empanelment.show', $vendor))->assertOk()
            ->assertSee('This Vendor is Blacklisted')
            ->assertSee('Fraudulent GST documentation provided during compliance audit.');

        // Only Admin Head can unblacklist vendor with mandatory reason
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.unblacklist', $vendor), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->post(route('empanelment.unblacklist', $vendor), [
            'reason' => 'Compliance audit cleared and authenticated GST certificate produced.',
        ])->assertRedirect(route('empanelment.show', $vendor));

        $unblacklisted = $vendor->fresh();
        $this->assertSame('active', $unblacklisted->compliance_status);
        $this->assertSame('pending_admin_head', $unblacklisted->status);
        $this->assertNull($unblacklisted->blacklist_reason);
        $this->assertNull($unblacklisted->blacklisted_at);
        $this->assertFalse($unblacklisted->isBlacklisted());

        // Model A verification: Full 4-level chain re-seeded, starting at Admin Head
        $this->assertSame(4, $unblacklisted->approvals()->where('status', 'pending')->count());

        // Verification: Admin Head can approve at any stage of the chain
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->get(route('empanelment.show', $vendor))->assertOk()
            ->assertSee('Approve Application')
            ->assertSee('Decline');

        // Admin Head approves Stage 1 (Admin Head)
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();
        $this->assertSame('pending_l1', $vendor->fresh()->status);

        // Admin Head has universal permission to approve Stage 2 (ASG 1) as well
        $this->get(route('empanelment.show', $vendor))->assertOk()
            ->assertSee('Approve Application');
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();
        $this->assertSame('pending_l2', $vendor->fresh()->status);
    }

    public function test_automatic_email_sent_on_final_empanelment_approval(): void
    {
        Mail::fake();

        $this->loginAs(Roles::BUYER);
        $this->post(route('onboarding.store'), [
            'company_name' => 'Auto Email Traders Ltd',
            'pan' => 'AABCT9999E',
            'contact_email' => 'vendor@autoemail.com',
            'contact_person' => 'Rajesh Sharma',
        ])->assertRedirect(route('empanelment'));

        $vendor = Vendor::where('company_name', 'Auto Email Traders Ltd')->firstOrFail();

        // Level 1: Admin Head
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();
        Mail::assertNothingSent();

        // Level 2: ASG 1
        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();
        Mail::assertNothingSent();

        // Level 3: ASG 2
        $this->loginAs(Roles::ASG2);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();
        Mail::assertNothingSent();

        // Level 4: CFO (Final empanelment)
        $this->loginAs(Roles::CFO);
        $this->post(route('empanelment.approve', $vendor))->assertRedirect();

        $fresh = $vendor->fresh();
        $this->assertTrue($fresh->isEmpanelled());
        $this->assertNotNull($fresh->empanelment_email_sent_at);

        Mail::assertSent(VendorEmpanelledMail::class, function ($mail) use ($vendor) {
            return $mail->hasTo('vendor@autoemail.com') && $mail->vendor->id === $vendor->id;
        });
    }

    public function test_can_resend_empanelment_email_for_empanelled_vendor(): void
    {
        Mail::fake();

        $this->loginAs(Roles::BUYER);
        $this->post(route('onboarding.store'), [
            'company_name' => 'Resend Mail Vendor',
            'contact_email' => 'contact@resendmail.com',
        ])->assertRedirect(route('empanelment'));

        $vendor = Vendor::where('company_name', 'Resend Mail Vendor')->firstOrFail();

        // Unauthorized non-empanelled vendor resend attempt
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.resend-email', $vendor))->assertStatus(422);

        // Approve through to fully empanelled
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::ASG1);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::ASG2);
        $this->post(route('empanelment.approve', $vendor));
        $this->loginAs(Roles::CFO);
        $this->post(route('empanelment.approve', $vendor));

        Mail::assertSent(VendorEmpanelledMail::class, 1);

        // Now test Resend option
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->get(route('empanelment.show', $vendor))->assertOk()
            ->assertSee('Resend Empanelment Email');

        $this->post(route('empanelment.resend-email', $vendor))
            ->assertRedirect()
            ->assertSessionHas('status', "Empanelment email successfully resent to contact@resendmail.com.");

        Mail::assertSent(VendorEmpanelledMail::class, 2);
    }

    public function test_vendor_registration_form_stores_all_excel_fields_and_supporting_documents(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->loginAs(Roles::BUYER);

        // Verify clean professional form renders without unnecessary icon circles
        $res = $this->get(route('onboarding'));
        $res->assertOk()
            ->assertSee('Vendor Registration Form')
            ->assertSee('1. Personal / Firm Details')
            ->assertSee('2. Banking Details')
            ->assertSee('3. Business &amp; References', false)
            ->assertSee('4. Tax Details')
            ->assertSee('Declaration with Signature / Date')
            ->assertSee('I am not involved in any fraudulent or corrupt activities')
            ->assertSee('no child labour engaged in our establishment');

        $idFile = \Illuminate\Http\UploadedFile::fake()->create('govt_id.pdf', 500, 'application/pdf');
        $chequeFile = \Illuminate\Http\UploadedFile::fake()->create('cancelled_cheque.jpg', 300, 'image/jpeg');
        $taxFile = \Illuminate\Http\UploadedFile::fake()->create('gst_certificate.pdf', 400, 'application/pdf');

        $payload = [
            'company_name' => 'Zenith Tech Systems Pvt Ltd',
            'contact_person' => 'Vikram Singhania',
            'id_number' => 'U72200DL2026PTC123456',
            'address' => 'Plot 42, Okhla Industrial Area Phase-III, New Delhi 110020',
            'contact_phone' => '+91 98111 22233',
            'contact_email' => 'contact@zenithtech.com',
            'bank_name' => 'HDFC Bank',
            'bank_branch' => 'Okhla Phase 3, New Delhi',
            'bank_account_holder' => 'Zenith Tech Systems Pvt Ltd',
            'bank_account_no' => '50200012345678',
            'bank_account_type' => 'Current',
            'bank_ifsc' => 'HDFC0001234',
            'category' => 'IT & Software',
            'references' => "1. ABC Corp, Rajesh Sharma, +91 9876543210\n2. XYZ Ltd, Priya Nair, +91 9812345678",
            'pan' => 'AAACZ1234F',
            'gstin' => '07AAACZ1234F1Z5',
            'msme_status' => '1',
            'msme_number' => 'UDYAM-DL-01-0012345',
            'incorporation_certificate_no' => 'ROC-DEL-2026-98765',
            'partnership_deed_no' => 'ROF-DL-4321',
            'payment_terms' => 'Net 30',
            'location' => 'New Delhi',
            'rating' => 4.8,
            'previous_orders' => 10,
            'declaration_accepted' => '1',
            'declaration_signatory_name' => 'Vikram Singhania',
            'declaration_designation' => 'Managing Director',
            'declaration_date' => now()->toDateString(),
            'doc_entity_id' => $idFile,
            'doc_cancelled_cheque' => $chequeFile,
            'doc_tax_registration' => $taxFile,
        ];

        $postRes = $this->post(route('onboarding.store'), $payload);
        $postRes->assertRedirect(route('empanelment'));

        $vendor = Vendor::where('company_name', 'Zenith Tech Systems Pvt Ltd')->firstOrFail();
        $this->assertEquals('U72200DL2026PTC123456', $vendor->id_number);
        $this->assertEquals('HDFC Bank', $vendor->bank_name);
        $this->assertEquals('Okhla Phase 3, New Delhi', $vendor->bank_branch);
        $this->assertEquals('Zenith Tech Systems Pvt Ltd', $vendor->bank_account_holder);
        $this->assertEquals('50200012345678', $vendor->bank_account_no);
        $this->assertEquals('Current', $vendor->bank_account_type);
        $this->assertEquals('HDFC0001234', $vendor->bank_ifsc);
        $this->assertTrue($vendor->msme_status);
        $this->assertEquals('UDYAM-DL-01-0012345', $vendor->msme_number);
        $this->assertEquals('ROC-DEL-2026-98765', $vendor->incorporation_certificate_no);
        $this->assertEquals('ROF-DL-4321', $vendor->partnership_deed_no);
        $this->assertTrue($vendor->declaration_accepted);
        $this->assertEquals('Vikram Singhania', $vendor->declaration_signatory_name);
        $this->assertEquals('Managing Director', $vendor->declaration_designation);

        // Verify attachments
        $this->assertCount(3, $vendor->attachments);

        // Verify dossier view renders these details
        $showRes = $this->get(route('empanelment.show', $vendor));
        $showRes->assertOk()
            ->assertSee('Zenith Tech Systems Pvt Ltd')
            ->assertSee('U72200DL2026PTC123456')
            ->assertSee('HDFC Bank')
            ->assertSee('50200012345678')
            ->assertSee('UDYAM-DL-01-0012345')
            ->assertSee('ROC-DEL-2026-98765')
            ->assertSee('Vikram Singhania')
            ->assertSee('Managing Director')
            ->assertSee('Download Self-Declaration Form')
            ->assertSee('Uploaded Supporting Documents');

        // Test downloading the Vendor Self-Declaration Undertaking document
        $declRes = $this->get(route('empanelment.declaration', $vendor));
        $declRes->assertOk()
            ->assertSee('VENDOR EMPANELMENT SELF-DECLARATION &amp; CERTIFICATION FORM', false)
            ->assertSee('Zenith Tech Systems Pvt Ltd')
            ->assertSee('I am not involved in any fraudulent or corrupt activities')
            ->assertSee('no child labour engaged in our establishment')
            ->assertSee('Vikram Singhania');

        // Test downloading blank template
        $templateRes = $this->get(route('empanelment.declaration.template'));
        $templateRes->assertOk()
            ->assertSee('VENDOR EMPANELMENT SELF-DECLARATION &amp; CERTIFICATION FORM', false)
            ->assertSee('Blank Form Template for Signing')
            ->assertSee('no child labour engaged in our establishment');
    }
}
