<?php

use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Support\MembershipCandidates;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\ContextualCandidatePicker;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\StringKeyCandidateUser;

beforeEach(function () {
    $this->candidates = app(MembershipCandidates::class);
    $this->picker = new ContextualCandidatePicker;
    app()->instance(ContextualCandidatePicker::class, $this->picker);
    config()->set('filament-team-management.user_picker', ContextualCandidatePicker::class);
});

it('uses only the explicit actor and target for discovery without UI authentication', function () {
    withPrograms();
    Filament::setCurrentPanel(null);
    Filament::setTenant(null, isQuiet: true);
    $actors = User::factory()->count(2)->create();
    $team = Team::factory()->create();
    $program = Program::factory()->create();
    $first = User::factory()->create(['email' => 'first@eligible.test']);
    $second = User::factory()->create(['email' => 'second@eligible.test']);
    $this->picker->allow($actors[0], $team, [$first->getKey()]);
    $this->picker->allow($actors[1], $team, [$second->getKey()]);
    $this->picker->allow($actors[0], $program, [$second->getKey()]);

    expect(auth()->check())->toBeFalse();
    expect($this->candidates->query($actors[0], $team)->get()->modelKeys())->toBe([$first->getKey()]);
    expect($this->candidates->query($actors[1], $team)->get()->modelKeys())->toBe([$second->getKey()]);
    expect($this->candidates->resolveSelection($actors[0], $program, [$second->getKey()])->modelKeys())->toBe([$second->getKey()]);
    expect($this->picker->contexts)->toBe([[$actors[0], $team], [$actors[1], $team], [$actors[0], $program]]);
});

it('returns an empty configured user query without a picker and rejects unavailable selections', function () {
    config()->set('filament-team-management.user_picker', null);
    $actor = User::factory()->create();
    $team = Team::factory()->create();
    expect($this->candidates->query($actor, $team)->getModel())->toBeInstanceOf(User::class);
    expect($this->candidates->query($actor, $team)->count())->toBe(0);
    expect($this->candidates->resolveSelection($actor, $team, []))->toBeInstanceOf(Collection::class)->toBeEmpty();
    expect(fn () => $this->candidates->resolveSelection($actor, $team, [$actor->getKey()]))->toThrow(AuthorizationException::class);
});

it('fails explicitly for a configured picker that does not implement the contract', function () {
    config()->set('filament-team-management.user_picker', stdClass::class);
    expect(fn () => $this->candidates->query(User::factory()->create(), Team::factory()->create()))
        ->toThrow(LogicException::class, 'user_picker must implement UserPicker.');
});

it('preserves all host query restrictions and resolves selections freshly', function () {
    $actor = User::factory()->create();
    $team = Team::factory()->create();
    $eligible = User::factory()->create(['email' => 'member@eligible.test']);
    $excluded = User::factory()->create(['email' => 'member@excluded.test']);
    $this->picker->allow($actor, $team, [$eligible->getKey(), $excluded->getKey()]);
    expect($this->candidates->query($actor, $team)->get()->modelKeys())->toBe([$eligible->getKey()]);
    expect($this->candidates->resolveSelection($actor, $team, [$eligible->getKey(), (string) $eligible->getKey()])->modelKeys())->toBe([$eligible->getKey()]);
    foreach ([[$excluded->getKey()], [999999], [$eligible->getKey(), $excluded->getKey()], [$eligible->getKey() . 'invalid']] as $ids) {
        expect(fn () => $this->candidates->resolveSelection($actor, $team, $ids))->toThrow(AuthorizationException::class);
    }
    $eligible->update(['email' => 'changed@excluded.test']);
    expect(fn () => $this->candidates->resolveSelection($actor, $team, [$eligible->getKey()]))->toThrow(AuthorizationException::class);
});

it('preserves configured nonstandard string user keys without integer coercion', function () {
    Schema::create('candidate_people', function (Blueprint $table) {
        $table->string('person_key')->primary();
        $table->string('email');
    });
    config()->set('filament-team-management.models.user', StringKeyCandidateUser::class);
    config()->set('filament-team-management.table_names.users', 'candidate_people');
    $actor = StringKeyCandidateUser::forceCreate(['person_key' => 'actor-01', 'email' => 'actor@eligible.test']);
    $user = StringKeyCandidateUser::forceCreate(['person_key' => '001-user', 'email' => 'member@eligible.test']);
    $team = Team::factory()->create();
    $this->picker->allow($actor, $team, [$user->getKey()]);
    $resolved = $this->candidates->resolveSelection($actor, $team, ['001-user', '001-user']);
    expect($resolved->modelKeys())->toBe(['001-user'])->and($resolved->first())->toBeInstanceOf(StringKeyCandidateUser::class);
    expect(fn () => $this->candidates->resolveSelection($actor, $team, ['1-user']))->toThrow(AuthorizationException::class);
});
