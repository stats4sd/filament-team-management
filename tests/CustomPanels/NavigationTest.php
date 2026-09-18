<?php

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Stats4sd\FilamentTeamManagement\Actions\LeaveMembership;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages\ViewTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Register;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;
use Stats4sd\FilamentTeamManagement\Http\Responses\RegisterResponse;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\MembershipPolicy;

it('links authorized programs using custom panel ids paths and slugs', function () {
    actingAsAdmin();
    $team = Team::factory()->create(['slug' => 'team-alpha']);
    $program = Program::factory()->create(['name' => '<script>alert(1)</script>', 'slug' => 'program-alpha']);
    $team->programs()->attach($program);
    Filament::setCurrentPanel(Filament::getPanel('workspace'));
    $html = MembershipNavigation::programLinks($team);
    expect($html)->toContain('/program-office/program-alpha')->toContain('&lt;script&gt;')->not->toContain('<script>')
        ->and(Filament::getCurrentPanel()->getId())->toBe('workspace');
});

it('renders plain escaped program names for missing or denied panels and policies', function () {
    $member = User::factory()->create();
    $this->actingAs($member);
    $team = Team::factory()->create(['slug' => 'team-alpha']);
    $program = Program::factory()->create(['name' => '<Denied>', 'slug' => 'program-alpha']);
    $team->programs()->attach($program);
    Filament::setCurrentPanel(Filament::getPanel('workspace'));
    expect(MembershipNavigation::programLinks($team))->toBe('&lt;Denied&gt;');
    config()->set('filament-team-management.panels.program', 'missing');
    expect(MembershipNavigation::programLinks($team))->toBe('&lt;Denied&gt;');
});

it('redirects after successful deletion to an explicit remaining App tenant slug', function () {
    $actor = actingAsAdmin();
    $deleted = Team::factory()->create(['slug' => 'deleted-team']);
    $remaining = Team::factory()->create(['slug' => 'remaining-team']);
    $actor->latestTeam()->associate($deleted)->save();
    Filament::setCurrentPanel(Filament::getPanel('control'));
    livewire(ViewTeam::class, ['record' => $deleted->getKey()])->callAction('delete')
        ->assertRedirect(Filament::getPanel('workspace')->getUrl($remaining));
    expect(Team::find($deleted->getKey()))->toBeNull();
});

it('offers permitted tenant registration after the last tenant is deleted', function () {
    actingAsAdmin();
    Filament::setCurrentPanel(Filament::getPanel('control'));
    expect(MembershipNavigation::afterDeparture(true))->toBe(Filament::getPanel('workspace')->getTenantRegistrationUrl());
});

it('uses the Admin list only in Admin context when creation is denied or App panel absent', function () {
    actingAsAdmin();
    Gate::policy(Team::class, (new class extends MembershipPolicy
    {
        public function create(User $actor): bool
        {
            return false;
        }
    })::class);
    Filament::setCurrentPanel(Filament::getPanel('control'));
    $list = TeamResource::getUrl('index', panel: 'control');
    expect(MembershipNavigation::afterDeparture(true))->toBe($list)
        ->and(MembershipNavigation::afterDeparture(false))->toBe(route('filament-team-management.no-memberships'));
    config()->set('filament-team-management.panels.app', 'missing');
    expect(MembershipNavigation::afterDeparture(true))->toBe($list);
});

it('routes a newly registered program-only member to the accessible program panel', function () {
    $program = Program::factory()->create(['slug' => 'invited-program']);
    $invite = Invite::factory()->create(['email' => 'program-only@example.test', 'program_id' => $program->getKey()]);
    Filament::setCurrentPanel(Filament::getPanel('workspace'));
    Livewire::withQueryParams(['token' => $invite->token])
        ->test(Register::class)
        ->fillForm(['name' => 'Program member', 'password' => 'longenoughpw', 'passwordConfirmation' => 'longenoughpw'])
        ->call('register')->assertHasNoFormErrors();
    $actor = Filament::auth()->user();
    expect($actor)->not->toBeNull()
        ->and($actor->teams()->count())->toBe(0)
        ->and($actor->programs()->whereKey($program->getKey())->exists())->toBeTrue();
    $response = app(RegisterResponse::class)->toResponse(request());
    expect($response->getTargetUrl())->toBe(Filament::getPanel('initiatives')->getUrl($program));
});

it('finds an accessible program after leaving the final team', function () {
    $actor = User::factory()->create();
    $team = Team::factory()->create(['slug' => 'departed-team']);
    $program = Program::factory()->create(['slug' => 'remaining-program']);
    $actor->teams()->attach($team);
    $actor->programs()->attach($program);
    $this->actingAs($actor);
    Filament::setCurrentPanel(Filament::getPanel('workspace'));
    Filament::setTenant($team);
    app(LeaveMembership::class)->handle($actor, $team);
    expect(MembershipNavigation::afterDeparture())->toBe(Filament::getPanel('initiatives')->getUrl($program))
        ->and(Filament::getCurrentPanel()->getId())->toBe('workspace');
});

it('does not select a fallback program when its policy denies access or program mode is disabled', function () {
    $actor = User::factory()->create();
    $program = Program::factory()->create(['slug' => 'denied-fallback']);
    $actor->programs()->attach($program);
    $this->actingAs($actor);
    Filament::setCurrentPanel(Filament::getPanel('workspace'));
    Gate::policy(Program::class, (new class extends MembershipPolicy
    {
        public function view(User $actor, Model $target): bool
        {
            return false;
        }
    })::class);
    expect(MembershipNavigation::afterDeparture())->toBe(route('filament-team-management.no-memberships'));
    Gate::policy(Program::class, MembershipPolicy::class);
    config()->set('filament-team-management.use_programs', false);
    expect(MembershipNavigation::afterDeparture())->toBe(route('filament-team-management.no-memberships'));
});
