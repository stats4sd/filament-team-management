<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamMembers;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

// Fix 2.3: the App-panel Members tab was bound to Team::members(), which is
// filtered to is_admin = false. Since RegisterTeam now flags the creator as
// admin (4.5), the creator vanished from their own team's member list. The
// table must list every member regardless of the is_admin flag.
it('lists team admins as well as plain members on the Manage Team members tab', function () {
    $admin = actingAsAdmin();
    $member = User::factory()->create(['name' => 'Plain Member']);
    $creator = User::factory()->create(['name' => 'Team Creator']);

    $team = Team::factory()->create();
    $team->users()->attach($admin);
    $team->users()->attach($member, ['is_admin' => false]);
    $team->users()->attach($creator, ['is_admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($team);

    livewire(ManageTeamMembers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$member, $creator]);
});
