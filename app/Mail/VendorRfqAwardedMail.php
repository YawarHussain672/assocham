<?php

namespace App\Mail;

use App\Models\CostComparativeStatement;
use App\Models\Rfq;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorRfqAwardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Rfq $rfq;
    public Vendor $vendor;
    public CostComparativeStatement $ccs;

    /**
     * Create a new message instance.
     */
    public function __construct(Rfq $rfq, Vendor $vendor, CostComparativeStatement $ccs)
    {
        $this->rfq = $rfq;
        $this->vendor = $vendor;
        $this->ccs = $ccs;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $project = $this->rfq->project_name ?: ($this->rfq->purchaseRequisition?->title ?: 'Procurement Requirement');

        return new Envelope(
            subject: "Quotation Accepted / Vendor Award Notification: {$this->rfq->rfq_number} — {$project}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-rfq-awarded',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

