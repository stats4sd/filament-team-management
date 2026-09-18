<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class UnlinkTeamFromProgram
{
    public function handle(Authenticatable $actor, Model $program, Model $team): bool
    {
        return app(MembershipMutation::class)->run($actor, [['unlink_team', $program, $team]])[0];
    }
}
