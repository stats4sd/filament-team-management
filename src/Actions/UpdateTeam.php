<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class UpdateTeam
{
    public function handle(Authenticatable $actor, Model $target, array $data): Model
    {
        return app(MembershipMutation::class)->run($actor, [['update_team', $target, null, $data]])[0];
    }
}
