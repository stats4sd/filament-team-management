<?php

use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ListTeams;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ViewTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\InvitesRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages\ListUsers;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\HostUserPicker;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

beforeEach(function () {
    $this->admin = actingAsAdmin();
    config()->set('filament-team-management.user_picker', HostUserPicker::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

describe('Admin Users resource', function () {
    it('lists existing users in the table', function () {
        $users = User::factory()->count(3)->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users->push($this->admin));
    });

    it('edits a user through the row action', function () {
        $user = User::factory()->create(['name' => 'Before']);

        livewire(ListUsers::class)
            ->callAction(TestAction::make(EditAction::getDefaultName())->table($user), data: [
                'name' => 'After',
                'email' => $user->email,
            ])
            ->assertHasNoActionErrors();

        expect($user->fresh()->name)->toBe('After');
    });

    it('has no role-only invitation action', function () {
        livewire(ListUsers::class)->assertActionDoesNotExist('invite users');
    });
});

describe('Admin Teams resource', function () {
    it('lists teams with their user counts', function () {
        $teams = Team::factory()->count(2)->create();

        livewire(ListTeams::class)
            ->assertCanSeeTableRecords($teams)
            ->assertCanRenderTableColumn('users_count');
    });

    it('attaches an existing user via the Users relation manager', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        livewire(UsersRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => ViewTeam::class,
        ])
            ->callAction(TestAction::make(AttachAction::getDefaultName())->table(), data: [
                'recordId' => [$user->id],
            ])
            ->assertHasNoActionErrors();

        expect($team->users()->whereKey($user->id)->exists())->toBeTrue();
    });

    it('detaches a user via the Users relation manager', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user);

        livewire(UsersRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => ViewTeam::class,
        ])
            ->callAction(TestAction::make(DetachAction::getDefaultName())->table($user));

        expect($team->users()->whereKey($user->id)->exists())->toBeFalse();
    });

    it('has no package administrator pivot edit', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user);
        livewire(UsersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => ViewTeam::class])->assertActionDoesNotExist(TestAction::make('edit')->table($user));
    });

    it('exposes no Create action but keeps Delete on the Team Invites relation manager', function () {
        $team = Team::factory()->create();
        $invite = Invite::factory()->forTeam($team)->create();

        livewire(InvitesRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => ViewTeam::class,
        ])
            ->assertActionDoesNotExist(TestAction::make(CreateAction::getDefaultName())->table())
            ->assertActionExists(TestAction::make('cancel')->table($invite));
    });
});
