<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamMembers;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

it('lists team admins as well as plain members on the Manage Team members tab', function () {
    $admin = actingAsAdmin();
    $member = User::factory()->create(['name' => 'Plain Member']);
    $creator = User::factory()->create(['name' => 'Team Creator']);

    $team = Team::factory()->create();
    $team->users()->attach($admin);
    $team->users()->attach($member);
    $team->users()->attach($creator);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($team);

    livewire(ManageTeamMembers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$member, $creator]);
});
