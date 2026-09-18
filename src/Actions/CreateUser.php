<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class CreateUser
{
    public function handle(Authenticatable $actor, array $data): Model
    {
        return app(MembershipMutation::class)->run($actor, [['create_user', null, null, $data]])[0];
    }
}
