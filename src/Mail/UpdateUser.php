<?php

namespace Stats4sd\FilamentTeamManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UpdateUser extends Mailable
{
    use Queueable;

    public function __construct(public array $snapshot) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name') . ': Membership updated');
    }

    public function content(): Content
    {
        return new Content(markdown: 'filament-team-management::emails.update');
    }
}
