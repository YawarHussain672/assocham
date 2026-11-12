<?php

namespace App\Mail;

use App\Models\Rfq;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment as MailAttachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RfqDispatchedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Rfq $rfq;
    public Vendor $vendor;

    /**
     * Create a new message instance.
     */
    public function __construct(Rfq $rfq, Vendor $vendor)
    {
        $this->rfq = $rfq;
        $this->vendor = $vendor;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $project = $this->rfq->project_name ?: ($this->rfq->purchaseRequisition?->title ?: 'Procurement Requirement');

        return new Envelope(
            subject: "Request for Quotation: {$this->rfq->rfq_number} — {$project}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rfq-dispatched',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $mailAttachments = [];

        // Attach RFQ attachments if any
        foreach ($this->rfq->attachments as $att) {
            $disk = $att->disk ?: 'public';
            if ($att->file_path && Storage::disk($disk)->exists($att->file_path)) {
                $mailAttachments[] = MailAttachment::fromStorageDisk($disk, $att->file_path)
                    ->as($att->original_name ?: basename($att->file_path));
            }
        }

        // Also check if PR has attachments
        if ($this->rfq->purchaseRequisition) {
            foreach ($this->rfq->purchaseRequisition->attachments as $att) {
                $disk = $att->disk ?: 'public';
                if ($att->file_path && Storage::disk($disk)->exists($att->file_path)) {
                    $mailAttachments[] = MailAttachment::fromStorageDisk($disk, $att->file_path)
                        ->as($att->original_name ?: basename($att->file_path));
                }
            }
        }

        return $mailAttachments;
    }
}

