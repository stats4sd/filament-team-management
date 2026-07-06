<?php

use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ListTeams;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ViewTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\InvitesRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages\ListUsers;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    $this->admin = actingAsAdmin();
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

    it('sends invites through the invite-users header action', function () {
        Mail::fake();

        $roleId = config('permission.models.role')::findByName('Super Admin', 'web')->id;

        // The Repeater mounts with one empty default item; fill that item by its
        // generated key rather than appending (which would leave the empty one
        // and fail validation).
        $component = livewire(ListUsers::class)->mountAction('invite users');
        $key = array_key_first(data_get($component->instance()->mountedActions, '0.data.users'));

        $component
            ->setActionData(['users' => [$key => ['email' => 'inviteviaaction@example.test', 'role' => $roleId]]])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        expect(Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', 'inviteviaaction@example.test')->exists())->toBeTrue();
        Mail::assertSent(InviteUser::class);
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
                'recordId' => $user->id,
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

    // Fix 4.5: the is_admin pivot form is now reachable via an EditAction on the
    // Users relation manager. Interaction-level test: opens the row EditAction
    // and toggles is_admin, asserting it persists to the pivot. Pre-fix (no
    // EditAction in recordActions) callAction fails because the action is absent.
    it('toggles the is_admin pivot via the Users relation manager EditAction', function () {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['is_admin' => false]);

        livewire(UsersRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => ViewTeam::class,
        ])
            ->callAction(TestAction::make(EditAction::getDefaultName())->table($user), data: [
                'is_admin' => true,
            ])
            ->assertHasNoActionErrors();

        expect($team->users()->whereKey($user->id)->wherePivot('is_admin', true)->exists())->toBeTrue();
    });

    // Fix 4.8: invites are system-generated + double as an audit log, so the
    // admin Invites relation manager exposes no hand-authored Create action, but
    // keeps Delete. Interaction-level assertions on the action registry.
    it('exposes no Create action but keeps Delete on the Team Invites relation manager', function () {
        $team = Team::factory()->create();
        $invite = Invite::factory()->forTeam($team)->create();

        livewire(InvitesRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => ViewTeam::class,
        ])
            ->assertActionDoesNotExist(TestAction::make(CreateAction::getDefaultName())->table())
            ->assertActionExists(TestAction::make(DeleteAction::getDefaultName())->table($invite));
    });
});
