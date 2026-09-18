<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Stats4sd\FilamentTeamManagement\Events\MemberAdded;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages\ListPrograms;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ListTeams;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ViewTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages\ListUsers;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeam;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamInvites;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamMembers;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterTeam;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\ContextualCandidatePicker;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\CandidateMembershipPolicy;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

it('lets a read-only member view members and leave but rejects profile saves', function () {
    $member = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Original']);
    $team->users()->attach($member);
    $this->actingAs($member);
    Filament::setTenant($team);
    livewire(ManageTeamMembers::class)->assertCanSeeTableRecords([$member])->assertActionDoesNotExist(TestAction::make('attach')->table());
    livewire(ManageTeam::class)->assertSuccessful()->assertActionVisible('leave');
    livewire(ManageTeam::class)->set('data.name', 'Tampered')->call('save')->assertForbidden();
    expect($team->fresh()->name)->toBe('Original');
    livewire(ManageTeam::class)->callAction('leave')->assertRedirect(route('filament-team-management.no-memberships'));
    expect($team->users()->count())->toBe(0);
    $this->get(route('filament-team-management.no-memberships'))->assertOk()->assertSee('No memberships available');
});

it('rejects crafted access to invitation widgets and the global user directory', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $this->actingAs($user);
    Filament::setTenant($team);
    livewire(ManageTeamInvites::class)->assertForbidden();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    livewire(ListUsers::class)->assertForbidden();
});

it('denies tenant registration at page and submit boundaries without create permission', function () {
    $this->actingAs(User::factory()->create());
    livewire(RegisterTeam::class)->assertNotFound();
});

it('keeps missing resource policies closed even for a host panel administrator', function () {
    actingAsAdmin();
    Gate::policy(Team::class, (new class {})::class);
    $team = Team::factory()->create();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    livewire(ViewTeam::class, ['record' => $team->getKey()])->assertForbidden();
});

it('returns no picker candidates and hides attachment until a host query is configured', function () {
    actingAsAdmin();
    $team = Team::factory()->create();
    $user = User::factory()->create();
    expect(Access::users($team)->count())->toBe(0);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    livewire(UsersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => ViewTeam::class])
        ->assertActionHidden(TestAction::make('attach')->table());
    expect($team->users()->count())->toBe(0);
});

describe('scoped team candidate attachment', function () {
    beforeEach(function () {
        $this->actor = actingAsAdmin();
        $this->team = Team::factory()->create();
        $this->eligible = User::factory()->create(['email' => 'allowed@eligible.test']);
        $this->excluded = User::factory()->create(['email' => 'excluded@eligible.test']);
        $this->denied = User::factory()->create(['email' => 'denied@eligible.test']);
        $this->picker = new ContextualCandidatePicker;
        $this->picker->allow($this->actor, $this->team, [$this->eligible->getKey(), $this->denied->getKey()]);
        app()->instance(ContextualCandidatePicker::class, $this->picker);
        config()->set('filament-team-management.user_picker', ContextualCandidatePicker::class);
        $this->policy = new CandidateMembershipPolicy;
        app()->instance(CandidateMembershipPolicy::class, $this->policy);
        Gate::policy(Team::class, CandidateMembershipPolicy::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('filters mounted search results and selected labels by both picker eligibility and per-user policy', function () {
        $this->policy->denied = [$this->denied->getKey()];
        expect(Gate::forUser($this->actor)->allows('addMember', [$this->team, $this->excluded]))->toBeTrue();
        $component = livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->mountAction(TestAction::make('attach')->table())
            ->assertActionMounted(TestAction::make('attach')->table());
        $select = $component->instance()->getSchema('mountedActionSchema0')->getComponentByStatePath('recordId');
        expect($select->getSearchResults('eligible.test'))->toBe([$this->eligible->getKey() => $this->eligible->email]);
        $select->state([$this->eligible->getKey(), $this->excluded->getKey(), $this->denied->getKey()]);
        expect($select->getOptionLabels())->toBe([$this->eligible->getKey() => $this->eligible->email]);
    });

    it('rejects forged selections in a mounted visible action without attaching any members', function (string $selection) {
        Event::fake([MemberAdded::class]);
        $ids = match ($selection) {
            'excluded' => [$this->excluded->getKey()],
            'nonexistent' => [999999],
            'mixed' => [$this->eligible->getKey(), $this->excluded->getKey()],
        };
        livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->assertActionVisible(TestAction::make('attach')->table())
            ->mountAction(TestAction::make('attach')->table())
            ->assertActionMounted(TestAction::make('attach')->table())
            ->setActionData(['recordId' => $ids])
            ->callMountedAction()
            ->assertForbidden();
        expect($this->team->users()->count())->toBe(0);
        Event::assertNotDispatched(MemberAdded::class);
    })->with(['excluded', 'nonexistent', 'mixed']);

    it('rechecks eligibility after showing an option while another valid candidate keeps attachment available', function () {
        $component = livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->mountAction(TestAction::make('attach')->table())
            ->assertActionMounted(TestAction::make('attach')->table());
        $select = $component->instance()->getSchema('mountedActionSchema0')->getComponentByStatePath('recordId');
        expect($select->getSearchResults('allowed'))->toHaveKey($this->eligible->getKey());
        $this->eligible->update(['email' => 'changed@excluded.test']);
        $component->setActionData(['recordId' => [$this->eligible->getKey()]])->callMountedAction()->assertForbidden();
        expect($this->team->users()->count())->toBe(0);
    });

    it('retains batch authorization when a picker-visible selected user becomes policy denied', function () {
        Event::fake([MemberAdded::class]);
        $component = livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->mountAction(TestAction::make('attach')->table())
            ->assertActionMounted(TestAction::make('attach')->table());
        $this->policy->denied = [$this->denied->getKey()];
        $this->policy->attempted = [];
        $component->setActionData(['recordId' => [$this->eligible->getKey(), $this->denied->getKey()]])
            ->callMountedAction()->assertForbidden();
        expect($this->policy->attempted)->toContain($this->denied->getKey());
        expect($this->policy->transactionMembershipCounts)->toBe([$this->eligible->getKey() => 0, $this->denied->getKey() => 1]);
        expect($this->team->users()->count())->toBe(0);
        Event::assertNotDispatched(MemberAdded::class);
    });

    it('adds duplicate valid ids once through the team relation manager and emits one membership event', function () {
        Event::fake([MemberAdded::class]);
        livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->callAction(TestAction::make('attach')->table(), ['recordId' => [$this->eligible->getKey(), (string) $this->eligible->getKey()]])
            ->assertHasNoActionErrors();
        expect($this->team->users()->get()->modelKeys())->toBe([$this->eligible->getKey()]);
        Event::assertDispatchedTimes(MemberAdded::class, 1);
    });

    it('keeps member visibility authorization in front of an otherwise eligible picker', function () {
        $this->policy->canViewMembers = false;
        livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])->assertForbidden();
        expect($this->team->users()->count())->toBe(0);
    });

    it('still requires a selection before calling the membership batch', function () {
        livewire(UsersRelationManager::class, ['ownerRecord' => $this->team, 'pageClass' => ViewTeam::class])
            ->callAction(TestAction::make('attach')->table(), ['recordId' => []])
            ->assertHasActionErrors(['recordId' => 'required']);
        expect($this->team->users()->count())->toBe(0);
    });
});

it('uses fresh explicit App tenants after Admin deletion and custom panel paths', function () {
    $actor = actingAsAdmin();
    $deleted = Team::factory()->create();
    $remaining = Team::factory()->create();
    $actor->latestTeam()->associate($deleted)->save();
    $deleted->delete();
    $panel = Filament::getPanel('app');
    $panel->path('workspace');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($deleted, isQuiet: true);
    expect(MembershipNavigation::afterDeparture(true))->toBe($panel->getUrl($remaining));
});

it('rejects a remembered tenant that is no longer in the supplied panels accessible query', function () {
    $user = User::factory()->create();
    $stale = Team::factory()->create();
    $mine = Team::factory()->create();
    $user->teams()->attach($mine);
    $user->latestTeam()->associate($stale)->save();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    expect($user->getDefaultTenant(Filament::getPanel('app'))->is($mine))->toBeTrue();
});

it('requires authentication for the no-memberships destination', function () {
    $this->get(route('filament-team-management.no-memberships'))->assertRedirect(Filament::getPanel('app')->getLoginUrl());
});

it('shows only true pending counts and retains expired invitations for resend', function () {
    actingAsAdmin();
    $team = Team::factory()->create();
    $pending = Invite::factory()->forTeam($team)->create();
    $expired = Invite::factory()->forTeam($team)->create(['expires_at' => now()->subDay()]);
    $accepted = Invite::factory()->forTeam($team)->confirmed()->create();
    Filament::setTenant($team);
    livewire(ManageTeamInvites::class)->assertCanSeeTableRecords([$pending, $expired])->assertCanNotSeeTableRecords([$accepted])
        ->filterTable('show_accepted')->assertCanSeeTableRecords([$pending, $expired, $accepted]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $component = livewire(ListTeams::class);
    expect($component->instance()->getTableRecords()->first()->invites_count)->toBe(1);
});

it('keeps program resource routes closed when programs are disabled even with host allow', function () {
    actingAsAdmin();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    livewire(ListPrograms::class)->assertForbidden();
});
