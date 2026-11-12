<?php

namespace App\Mail;

use App\Models\Attachment as StoredAttachment;
use App\Models\Trf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BoardingPassSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Trf $trf;

    /** @var Collection<int, StoredAttachment> */
    public Collection $boardingPasses;

    /**
     * @param  Collection<int, StoredAttachment>  $boardingPasses
     */
    public function __construct(Trf $trf, Collection $boardingPasses)
    {
        $this->trf = $trf->loadMissing('employee');
        $this->boardingPasses = $boardingPasses;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Boarding Pass & Travel Documents — {$this->trf->trf_number} ({$this->trf->route})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.boarding-pass-shared',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $files = [];
        foreach ($this->boardingPasses as $pass) {
            $disk = $pass->disk ?: 'public';
            if (Storage::disk($disk)->exists($pass->file_path)) {
                $mailAttachment = Attachment::fromStorageDisk($disk, $pass->file_path);
                if (! empty($pass->original_name)) {
                    $mailAttachment = $mailAttachment->as($pass->original_name);
                }
                $files[] = $mailAttachment;
            }
        }

        return $files;
    }
}

