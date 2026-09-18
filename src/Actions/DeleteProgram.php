<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class DeleteProgram
{
    public function handle(Authenticatable $actor, Model $target): bool
    {
        return app(MembershipMutation::class)->run($actor, [['delete_program', $target]])[0];
    }
}
