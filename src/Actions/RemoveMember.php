<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class RemoveMember
{
    public function handle(Authenticatable $actor, Model $target, Model $member): bool
    {
        return app(MembershipMutation::class)->run($actor, [['remove_member', $target, $member]])[0];
    }
}
