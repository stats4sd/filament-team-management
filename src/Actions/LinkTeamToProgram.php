<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class LinkTeamToProgram
{
    public function handle(Authenticatable $actor, Model $program, Model $team): bool
    {
        return app(MembershipMutation::class)->run($actor, [['link_team', $program, $team]])[0];
    }
}
