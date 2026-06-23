<?php

use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\ProjectTeam;

it('returns the snake_case model name for the default models', function () {
    expect(Team::getModelNameLower())->toBe('team')
        ->and(Program::getModelNameLower())->toBe('program')
        ->and(User::getModelNameLower())->toBe('user');
});

it('returns a snake_case name for a multi-word custom model', function () {
    expect(ProjectTeam::getModelNameLower())->toBe('project_team');
});
