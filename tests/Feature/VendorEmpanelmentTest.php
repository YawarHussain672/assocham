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
}
