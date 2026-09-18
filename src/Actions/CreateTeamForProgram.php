<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\Membership;

final class CreateTeamForProgram
{
    public function handle(Authenticatable $actor, Model $program, array $data): Model
    {
        $actorModel = Membership::actor($actor);
        if (Membership::type($program) !== 'program' || ! $program->exists) {
            Membership::invalid('An existing configured program is required.');
        }
        Membership::sameConnection($actorModel, $program);

        return $actorModel->getConnection()->transaction(function () use ($actor, $program, $data): Model {
            $team = app(CreateTeam::class)->handle($actor, $data);
            app(LinkTeamToProgram::class)->handle($actor, $program, $team);

            return $team;
        }, 1);
    }
}
