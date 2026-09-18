<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class DeleteUser
{
    public function handle(Authenticatable $actor, Model $target): bool
    {
        return app(MembershipMutation::class)->run($actor, [['delete_user', $target]])[0];
    }
}
