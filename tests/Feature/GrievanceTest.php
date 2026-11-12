<?php

namespace Tests\Feature;

use App\Http\Controllers\GrievanceController;
use App\Mail\GrievanceStatusUpdatedMail;
use App\Models\Grievance;
use App\Models\Reimbursement;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class GrievanceTest extends TestCase
{
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_authenticated_role_can_access_dashboard_shortcut_and_raise_grievance(): void
    {
        foreach (Roles::all() as $index => $role) {
            $user = $this->loginAs($role);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee('data-module="grievance"', false)
                ->assertSee(route('grievance.create'), false);

            $this->get(route('grievance.create'))
                ->assertOk()
                ->assertSee(GrievanceController::CATEGORY_SETTLEMENT);

            $this->post(route('grievance.store'), [
                'subject_category' => 'Operational Delay',
                'subject' => "Role {$role} grievance #{$index}",
                'description' => "Submitted by {$role} to verify universal grievance filing.",
            ])->assertRedirect(route('grievance'));

            $this->assertDatabaseHas('grievances', [
                'subject' => "Role {$role} grievance #{$index}",
                'raised_by' => $user->id,
                'current_owner_role' => Roles::HOD,
                'status' => 'under_review',
            ]);
        }
    }

    public function test_user_can_raise_grievance_for_reimbursement_and_advance_settlement_with_attachment(): void
    {
        Storage::fake('public');

        $buyer = $this->loginAs(Roles::BUYER);

        $claim = Reimbursement::create([
            'claim_number' => 'CLM-GRV-101',
            'employee_id' => $buyer->id,
            'claim_amount' => 18500,
            'advance_amount' => 10000,
            'balance_amount' => 8500,
            'routing_flag' => 'exception',
            'reason' => 'Urgent client travel settlement',
            'status' => 'pending_sg',
            'submitted_at' => now()->subDays(5),
        ]);

        // Claim detail and reimb index expose the Grievance shortcut
        $this->get(route('reimb'))
            ->assertOk()
            ->assertSee('Settlement Grievance');

        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee('Raise Grievance')
            ->assertSee(route('grievance.create', [
                'reimbursement_id' => $claim->id,
                'category' => GrievanceController::CATEGORY_SETTLEMENT,
            ]));

        // Prefilled grievance create screen lists the user's claim
        $this->get(route('grievance.create', [
            'reimbursement_id' => $claim->id,
            'category' => GrievanceController::CATEGORY_SETTLEMENT,
        ]))
            ->assertOk()
            ->assertSee('CLM-GRV-101');

        // Submit settlement grievance with screenshot evidence
        $file = UploadedFile::fake()->create('settlement-slip.pdf', 120, 'application/pdf');
        $this->post(route('grievance.store'), [
            'subject_category' => GrievanceController::CATEGORY_SETTLEMENT,
            'reimbursement_id' => $claim->id,
            'subject' => 'Advance adjustment mismatch on CLM-GRV-101',
            'description' => 'Only ₹6,000 credited instead of ₹8,500 net balance after advance adjustment.',
            'proof' => $file,
        ])->assertRedirect(route('grievance'));

        $grievance = Grievance::where('reimbursement_id', $claim->id)->firstOrFail();
        $this->assertSame(GrievanceController::CATEGORY_SETTLEMENT, $grievance->subject_category);
        $this->assertCount(1, $grievance->attachments);

        // Linked claim and attachment show cleanly on grievance detail & claim detail
        $this->get(route('grievance.show', $grievance))
            ->assertOk()
            ->assertSee('CLM-GRV-101')
            ->assertSee('settlement-slip.pdf');

        $attachment = $grievance->attachments->first();
        $this->get(route('grievance.attachments.show', [$grievance, $attachment]))
            ->assertOk();

        $this->get(route('reimb.show', $claim))
            ->assertOk()
            ->assertSee($grievance->ticket_number)
            ->assertSee('Advance adjustment mismatch on CLM-GRV-101');
    }

    public function test_non_governance_user_cannot_link_grievance_to_another_users_claim_and_cannot_resolve(): void
    {
        $hod = $this->userWithRole(Roles::HOD);
        $otherClaim = Reimbursement::create([
            'claim_number' => 'CLM-OTHER-99',
            'employee_id' => $hod->id,
            'claim_amount' => 5000,
            'advance_amount' => 0,
            'balance_amount' => 5000,
            'routing_flag' => 'standard',
            'status' => 'pending_cfo',
            'submitted_at' => now(),
        ]);

        // Buyer cannot hijack HOD's claim in a grievance
        $this->loginAs(Roles::BUYER);
        $this->post(route('grievance.store'), [
            'subject_category' => GrievanceController::CATEGORY_SETTLEMENT,
            'reimbursement_id' => $otherClaim->id,
            'subject' => 'Unauthorized claim reference',
            'description' => 'Should be forbidden for non-owner.',
        ])->assertForbidden();

        // HOD files legitimate grievance -> escalates HOD -> Admin Head -> CFO -> resolves with notes
        $this->loginAs(Roles::HOD);
        $this->followingRedirects()->post(route('grievance.store'), [
            'subject_category' => GrievanceController::CATEGORY_SETTLEMENT,
            'reimbursement_id' => $otherClaim->id,
            'subject' => 'Delayed payout on CLM-OTHER-99',
            'description' => 'Pending beyond SLA.',
        ])->assertOk();

        $grievance = Grievance::latest('id')->firstOrFail();

        // Buyer cannot view, resolve, or escalate HOD's private grievance
        $this->loginAs(Roles::BUYER);
        $this->get(route('grievance'))
            ->assertOk()
            ->assertSee('Private View · Your Raised Tickets Only')
            ->assertDontSee($grievance->ticket_number)
            ->assertDontSee('Delayed payout on CLM-OTHER-99');
        $this->get(route('grievance.show', $grievance))->assertForbidden();
        $this->post(route('grievance.escalate', $grievance))->assertForbidden();
        $this->post(route('grievance.resolve', $grievance))->assertForbidden();

        // PR Sr Exec also cannot view HOD's ticket
        $this->loginAs(Roles::PR_SR_EXEC);
        $this->get(route('grievance.show', $grievance))->assertForbidden();

        // HOD sees handler view & escalates to Admin Head, CFO resolves with resolution_notes
        $this->loginAs(Roles::HOD);
        $this->get(route('grievance'))
            ->assertOk()
            ->assertSee('Handler View · All Employee Tickets')
            ->assertSee($grievance->ticket_number);
        $this->post(route('grievance.escalate', $grievance))->assertRedirect();
        $this->assertSame(Roles::ADMIN_HEAD, $grievance->fresh()->current_owner_role);

        $this->loginAs(Roles::CFO);
        $this->post(route('grievance.resolve', $grievance), [
            'resolution_notes' => 'Settlement voucher released and credited via NEFT.',
        ])->assertRedirect();

        $grievance->refresh();
        $this->assertSame('resolved', $grievance->status);
        $this->assertSame('Settlement voucher released and credited via NEFT.', $grievance->resolution_notes);

        $this->get(route('grievance.show', $grievance))
            ->assertOk()
            ->assertSee('Settlement voucher released and credited via NEFT.');
    }

    public function test_assigned_owner_can_respond_escalate_and_close_while_notifying_user_at_each_step(): void
    {
        Mail::fake();

        $buyer = $this->loginAs(Roles::BUYER);
        $hod = $this->userWithRole(Roles::HOD);
        $adminHead = $this->userWithRole(Roles::ADMIN_HEAD);

        // Step 1: Buyer files grievance -> assigned to HOD; emails Buyer + HOD
        $this->followingRedirects()->post(route('grievance.store'), [
            'subject_category' => GrievanceController::CATEGORY_SETTLEMENT,
            'subject' => 'Trackable reimbursement grievance',
            'description' => 'Testing step-by-step tracking and notifications.',
        ])->assertOk();

        $grievance = Grievance::latest('id')->firstOrFail();

        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($buyer->email) && $m->update->action === 'filed');
        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($hod->email) && $m->recipientAudience === 'handler');

        // Unassigned role (CFO while assigned to HOD) and Buyer cannot post owner response
        $this->loginAs(Roles::CFO);
        $this->post(route('grievance.respond', $grievance), ['response_message' => 'Not my tier yet.'])->assertForbidden();
        $this->loginAs(Roles::BUYER);
        $this->post(route('grievance.respond', $grievance), ['response_message' => 'Self-response blocked.'])->assertForbidden();

        // Step 2: Assigned owner (HOD) responds -> Buyer notified via Mail + Action Center + /grievance
        $this->loginAs(Roles::HOD);
        $this->post(route('grievance.respond', $grievance), [
            'response_message' => 'Verified travel claim ledger; waiting on finance batch reference.',
        ])->assertRedirect();

        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($buyer->email) && $m->update->action === 'responded');

        // Step 3: Assigned owner (HOD) escalates with handoff note to Admin Head -> Buyer & Admin Head emailed
        $this->post(route('grievance.escalate', $grievance), [
            'escalation_notes' => 'Needs inter-department sign-off from Admin Head.',
        ])->assertRedirect();

        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($buyer->email) && $m->update->action === 'escalated');
        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($adminHead->email) && $m->recipientAudience === 'handler');

        // Step 4: Admin Head resolves and closes -> Buyer emailed
        $this->loginAs(Roles::ADMIN_HEAD);
        $this->post(route('grievance.resolve', $grievance), [
            'resolution_notes' => 'Reimbursement adjustment processed in full.',
        ])->assertRedirect();

        Mail::assertSent(GrievanceStatusUpdatedMail::class, fn ($m) => $m->hasTo($buyer->email) && $m->update->action === 'resolved');

        // Buyer sees unread alerts on /notifications, sidebar grievance badge, and badge on /grievance before viewing detail
        $this->loginAs(Roles::BUYER);
        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('Grievance Status Updates')
            ->assertSee('3 Unread Updates')
            ->assertSee('Verified travel claim ledger; waiting on finance batch reference.')
            ->assertSee('Reimbursement adjustment processed in full.');

        $this->get(route('grievance'))
            ->assertOk()
            ->assertSee('"grievance":3', false)
            ->assertSee('3 New Updates')
            ->assertSee('Resolved');

        // Opening the ticket displays complete step timeline and marks updates read
        $this->get(route('grievance.show', $grievance))
            ->assertOk()
            ->assertSee('Response &amp; Tracking History', false)
            ->assertSee('Ticket Filed')
            ->assertSee('Owner Response')
            ->assertSee('Escalated')
            ->assertSee('Resolved &amp; Closed', false)
            ->assertSee('Verified travel claim ledger; waiting on finance batch reference.')
            ->assertSee('Needs inter-department sign-off from Admin Head.')
            ->assertSee('Reimbursement adjustment processed in full.');

        $this->get(route('notifications'))
            ->assertOk()
            ->assertDontSee('Grievance Status Updates');

        // Legacy resolved grievance without update rows renders synthesized filed+resolved history cleanly
        $legacy = Grievance::create([
            'ticket_number' => 'GRV-LEG-01',
            'subject_category' => 'Technical Issue',
            'subject' => 'Legacy resolved ticket',
            'description' => 'Closed prior to grievance_updates rollout.',
            'raised_by' => $buyer->id,
            'current_owner_role' => Roles::HOD,
            'status' => 'resolved',
            'resolved_by' => $hod->id,
            'resolved_at' => now(),
        ]);

        $this->get(route('grievance.show', $legacy))
            ->assertOk()
            ->assertSee('2 Updates')
            ->assertSee('Ticket Filed')
            ->assertSee('Resolved &amp; Closed', false)
            ->assertDontSee('Awaiting response from');
    }
}

