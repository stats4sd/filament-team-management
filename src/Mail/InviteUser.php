<?php

namespace Stats4sd\FilamentTeamManagement\Mail;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Support\MembershipMail;

class InviteUser extends Mailable
{
    use Queueable;

    public array $snapshot;

    public string $acceptUrl;

    public function __construct(Invite $invite)
    {
        $this->snapshot = MembershipMail::snapshot($invite->target(), $invite->inviter);
        $this->acceptUrl = route(Filament::getDefaultPanel()->generateRouteName('auth.register'), ['token' => $invite->token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name') . ': Invitation to register');
    }

    public function content(): Content
    {
        return new Content(markdown: 'filament-team-management::emails.invite');
    }
}
