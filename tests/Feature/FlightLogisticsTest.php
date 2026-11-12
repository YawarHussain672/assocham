<?php

namespace Tests\Feature;

use App\Mail\BoardingPassSharedMail;
use App\Mail\FlightBookingConfirmedMail;
use App\Models\Attachment;
use App\Models\BookingMailTransaction;
use App\Models\FlightLog;
use App\Models\Trf;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class FlightLogisticsTest extends TestCase
{
    use InteractsWithDemoUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_logging_booking_sends_confirmation_mail_to_traveller_and_stores_transaction_against_trf(): void
    {
        Mail::fake();
        Storage::fake('public');

        $traveller = $this->userWithRole(Roles::HOD);
        $trf = Trf::create([
            'trf_number' => 'TRF-MAIL-101',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Industry Forum',
            'route' => 'New Delhi (DEL) → Mumbai (BOM)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->addDays(5)->toDateString(),
            'travel_end' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $vendor = Vendor::create([
            'vendor_code' => 'VEND-TRV-01',
            'company_name' => 'SkyWays Travel',
            'contact_person' => 'Rohan',
            'contact_email' => 'rohan@skyways.test',
            'category' => 'Travel',
            'status' => 'empanelled',
            'submitted_by' => $traveller->id,
        ]);

        $this->loginAs(Roles::ADMIN_HEAD);
        $proofFile = UploadedFile::fake()->create('boarding-pass.pdf', 120, 'application/pdf');

        $response = $this->post(route('flight.store'), [
            'trf_id' => $trf->id,
            'type' => 'Flight ticket',
            'vendor_id' => $vendor->id,
            'cost' => 8450.50,
            'proof' => $proofFile,
        ]);

        $response->assertRedirect(route('flight'));

        $log = FlightLog::latest('id')->firstOrFail();
        $this->assertNotNull($log->file_path);

        // Email dispatched to traveller with Mailable
        Mail::assertSent(FlightBookingConfirmedMail::class, function (FlightBookingConfirmedMail $mail) use ($traveller, $log) {
            return $mail->hasTo($traveller->email)
                && $mail->flightLog->id === $log->id;
        });

        // Mail transaction persisted against both TRF and FlightLog
        $tx = BookingMailTransaction::where('trf_id', $trf->id)->firstOrFail();
        $this->assertSame($log->id, $tx->flight_log_id);
        $this->assertSame($traveller->email, $tx->recipient_email);
        $this->assertSame('sent', $tx->status);
        $this->assertStringContainsString($log->ref, $tx->subject);
        $this->assertStringContainsString($trf->trf_number, $tx->body_html);

        // Visible on TRF Show and Flight Show
        $this->get(route('trf.show', $trf))
            ->assertOk()
            ->assertSee('Booking Confirmation Mail Transactions')
            ->assertSee($traveller->email)
            ->assertSee($log->ref);

        $this->get(route('flight.show', $log))
            ->assertOk()
            ->assertSee('Traveller Confirmation Email')
            ->assertSee($traveller->email);

        $this->get(route('flight'))
            ->assertOk()
            ->assertSee('Mailed')
            ->assertSee('Resend');

        // Proof endpoint serves file cleanly
        $this->get(route('flight.proof', $log))->assertOk();

        // Resend email adds a second transaction stored on TRF
        $this->post(route('flight.resend-email', $log))
            ->assertRedirect();

        $this->assertSame(2, $trf->fresh()->mailTransactions()->count());
        Mail::assertSentCount(2);

        // Non-Admin Head cannot resend
        $this->loginAs(Roles::HOD);
        $this->post(route('flight.resend-email', $log))->assertForbidden();
    }

    public function test_boarding_passes_can_be_uploaded_stored_on_trf_viewed_shared_by_email_and_removed(): void
    {
        Mail::fake();
        Storage::fake('public');

        $traveller = $this->userWithRole(Roles::HOD);
        $otherUser = $this->userWithRole(Roles::BUYER);

        $approvedTrf = Trf::create([
            'trf_number' => 'TRF-BP-201',
            'employee_id' => $traveller->id,
            'booking_type' => 'self',
            'purpose' => 'Summit Travel',
            'route' => 'New Delhi (DEL) → Bengaluru (BLR)',
            'travel_mode' => 'Flight',
            'is_international' => false,
            'travel_start' => now()->addDays(4)->toDateString(),
            'travel_end' => now()->addDays(6)->toDateString(),
            'status' => 'approved',
        ]);

        // Unauthorized third-party user cannot upload or share
        $this->loginAs(Roles::BUYER);
        $this->post(route('trf.boarding-passes.store', $approvedTrf), [
            'boarding_passes' => [UploadedFile::fake()->create('leg1.pdf', 80, 'application/pdf')],
        ])->assertForbidden();

        // Admin Head uploads two boarding passes and shares immediately with traveller
        $this->loginAs(Roles::ADMIN_HEAD);
        $pass1 = UploadedFile::fake()->create('DEL-BLR-pass.pdf', 90, 'application/pdf');
        $pass2 = UploadedFile::fake()->image('BLR-DEL-pass.png', 200, 120);

        $this->post(route('trf.boarding-passes.store', $approvedTrf), [
            'boarding_passes' => [$pass1, $pass2],
            'share_with_traveller' => 1,
        ])->assertRedirect();

        $passes = $approvedTrf->fresh()->boardingPasses;
        $this->assertCount(2, $passes);

        Mail::assertSent(BoardingPassSharedMail::class, function (BoardingPassSharedMail $mail) use ($traveller, $approvedTrf) {
            return $mail->hasTo($traveller->email)
                && $mail->trf->id === $approvedTrf->id
                && $mail->boardingPasses->count() === 2;
        });

        $mailTx = $approvedTrf->fresh()->mailTransactions()->where('mail_type', 'boarding_pass')->firstOrFail();
        $this->assertSame($traveller->email, $mailTx->recipient_email);
        $this->assertSame('sent', $mailTx->status);

        // Stored boarding passes & mail transactions visible on TRF show page
        $this->get(route('trf.show', $approvedTrf))
            ->assertOk()
            ->assertSee('Boarding Passes &amp; Travel Documents', false)
            ->assertSee('DEL-BLR-pass.pdf')
            ->assertSee('BLR-DEL-pass.png')
            ->assertSee('Boarding Pass');

        // Can view/download each stored boarding pass
        $firstPass = $passes->first();
        $this->get(route('trf.boarding-passes.show', [$approvedTrf, $firstPass]))->assertOk();

        // Traveller can also share a single stored pass again
        $this->loginAs(Roles::HOD);
        $this->post(route('trf.boarding-passes.share', $approvedTrf), [
            'attachment_id' => $firstPass->id,
        ])->assertRedirect();

        Mail::assertSentCount(2);
        $this->assertSame(2, $approvedTrf->fresh()->mailTransactions()->where('mail_type', 'boarding_pass')->count());

        // Traveller can delete a stored boarding pass
        $this->delete(route('trf.boarding-passes.destroy', [$approvedTrf, $firstPass]))
            ->assertRedirect();
        $this->assertCount(1, $approvedTrf->fresh()->boardingPasses);
    }
}
