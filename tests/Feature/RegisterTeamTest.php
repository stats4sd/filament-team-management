<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterTeam;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

// Fix 4.5 (behaviour change): the user who registers a team lands as an admin
// (is_admin = true on the team_members pivot). Interaction-level test: drives
// the RegisterTeam tenant-registration Livewire page and asserts the pivot.
it('attaches the creator as a team admin after registering a team', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    livewire(RegisterTeam::class)
        ->fillForm(['name' => 'Founders Team'])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = Team::where('name', 'Founders Team')->firstOrFail();

    expect($team->users()->whereKey($user->id)->wherePivot('is_admin', true)->exists())->toBeTrue();
});
