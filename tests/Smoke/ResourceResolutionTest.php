<?php

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\ProgramResource;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\UserResource;

it('resolves each Admin resource to its configured model', function () {
    expect(UserResource::getModel())->toBe(config('filament-team-management.models.user'))
        ->and(TeamResource::getModel())->toBe(config('filament-team-management.models.team'))
        ->and(ProgramResource::getModel())->toBe(config('filament-team-management.models.program'));
});

it('only registers the Program resource navigation when programs are enabled', function () {
    expect(ProgramResource::shouldRegisterNavigation())->toBeFalse();
});
