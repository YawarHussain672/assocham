<?php

namespace App\Mail;

use App\Models\FlightLog;
use App\Support\StorageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FlightBookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public FlightLog $flightLog;

    public function __construct(FlightLog $flightLog)
    {
        $this->flightLog = $flightLog->loadMissing(['trf.employee', 'vendor', 'uploader']);
    }

    public function envelope(): Envelope
    {
        $trfNumber = $this->flightLog->trf?->trf_number ?? 'TRF';

        return new Envelope(
            subject: "Travel Booking Confirmation ({$this->flightLog->ref}) — {$this->flightLog->type} for {$trfNumber}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.flight-booking-confirmed',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->flightLog->file_path) {
            return [];
        }

        $disk = StorageHelper::hasS3Credentials() ? 's3' : 'public';
        if (Storage::disk($disk)->exists($this->flightLog->file_path)) {
            return [
                Attachment::fromStorageDisk($disk, $this->flightLog->file_path),
            ];
        }

        return [];
    }
}

