<?php

namespace Stats4sd\FilamentTeamManagement\Mail;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Stats4sd\FilamentTeamManagement\Models\Invite;

class InviteUser extends Mailable
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
            subject: config('app.name') . ': Invitation to register',
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {

        $routeName = Filament::getDefaultPanel()->generateRouteName('auth.register');

        return new Content(
            markdown: 'filament-team-management::emails.invite',
            with: [
                'acceptUrl' => URL::signedRoute(
                    $routeName,
                    [
                        'token' => $this->invite->token,
                    ],
                ),
            ],
        );
    }
}
