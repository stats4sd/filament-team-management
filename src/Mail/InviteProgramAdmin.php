<?php

namespace Stats4sd\FilamentTeamManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Stats4sd\FilamentTeamManagement\Models\ProgramInvite;

class InviteProgramAdmin extends Mailable
{
    use Queueable;
    use SerializesModels;

    public ProgramInvite $invite;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ProgramInvite $invite)
    {
        $this->invite = $invite;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address'),
            subject: config('app.name') . ': Invitation To Join Program ' . $this->invite->program->name,
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'vendor.filament-team-management.emails.program_invite',
            with: [
                'acceptUrl' => URL::signedRoute(
                    'filament.app.programregister',
                    [
                        'token' => $this->invite->token,
                    ],
                ),
            ],
        );
    }
}
