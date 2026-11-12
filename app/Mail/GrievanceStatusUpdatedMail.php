<?php

namespace App\Mail;

use App\Models\Grievance;
use App\Models\GrievanceUpdate;
use App\Support\Roles;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GrievanceStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Grievance $grievance;

    public GrievanceUpdate $update;

    public string $recipientAudience;

    public function __construct(Grievance $grievance, GrievanceUpdate $update, string $recipientAudience = 'raiser')
    {
        $this->grievance = $grievance->loadMissing(['raisedBy', 'resolvedBy', 'reimbursement']);
        $this->update = $update->loadMissing('actor');
        $this->recipientAudience = $recipientAudience;
    }

    public function envelope(): Envelope
    {
        $step = $this->update->actionLabel();

        return new Envelope(
            subject: "[{$this->grievance->ticket_number}] {$step} — {$this->grievance->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.grievance-status-updated',
            with: [
                'ownerLabel' => Roles::label($this->grievance->current_owner_role),
                'actorName' => $this->update->actor?->name ?? 'System',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

