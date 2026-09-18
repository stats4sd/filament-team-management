<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class UpdateUser
{
    public function handle(Authenticatable $actor, Model $target, array $data): Model
    {
        return app(MembershipMutation::class)->run($actor, [['update_user', $target, null, $data]])[0];
    }
}
