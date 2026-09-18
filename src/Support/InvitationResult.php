<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Models\Invite;

final class InvitationResult
{
    public ?string $mailStatus = null;

    public ?string $error = null;

    public function __construct(
        public readonly string $status,
        public readonly ?string $email,
        public readonly ?Invite $invite = null,
        public readonly ?Model $user = null,
    ) {}
}
