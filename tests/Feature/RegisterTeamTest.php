<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterTeam;
use Stats4sd\FilamentTeamManagement\Models\Team;

it('attaches the creator with an ordinary membership after registering a team', function () {
    $user = actingAsAdmin();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    livewire(RegisterTeam::class)
        ->fillForm(['name' => 'Founders Team'])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = Team::where('name', 'Founders Team')->firstOrFail();

    expect($team->users()->whereKey($user->id)->exists())->toBeTrue();
});
