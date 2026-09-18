<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Support\InvitationMutation;

final class ResendMembershipInvitation
{
    public function handle(Authenticatable $actor, Model $target, Invite $invite): Invite
    {
        return app(InvitationMutation::class)->run($actor, $target, $invite, true);
    }
}
