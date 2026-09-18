<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class AddMember
{
    public function handle(Authenticatable $actor, Model $target, Model $member): bool
    {
        return app(MembershipMutation::class)->run($actor, [['add_member', $target, $member]])[0];
    }
}
