<?php

namespace Stats4sd\FilamentTeamManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Stats4sd\FilamentTeamManagement\Models\Invite;

class UpdateUser extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Invite $invite) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address'),
            subject: config('app.name') . ': Update to user account',
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'filament-team-management::emails.update',
        );
    }
}
