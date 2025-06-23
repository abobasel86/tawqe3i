<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\DocumentParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $participant;

    public function __construct(Document $document, DocumentParticipant $participant)
    {
        $this->document = $document;
        $this->participant = $participant;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'طلب توقيع للمستند: ' . $this->document->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signature-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}