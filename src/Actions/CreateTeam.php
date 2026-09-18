<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class CreateTeam
{
    public function handle(Authenticatable $actor, array $data): Model
    {
        return app(MembershipMutation::class)->run($actor, [['create_team', null, null, $data]])[0];
    }
}
