<?php

use Filament\Facades\Filament;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Stats4sd\FilamentTeamManagement\Events\RegisteredWithData;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Register;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function mountRegister(Invite $invite)
{
    return Livewire::withQueryParams(['token' => $invite->token])->test(Register::class);
}

it('prefills the invited email from the token', function () {
    $invite = Invite::factory()->create(['email' => 'invited@example.test']);

    mountRegister($invite)
        ->assertOk()
        ->assertSet('invite.id', $invite->id)
        ->assertFormSet(['email' => 'invited@example.test']);
});

it('redirects to the login page when the token is missing or unknown', function () {
    Livewire::withQueryParams(['token' => 'does-not-exist'])
        ->test(Register::class)
        ->assertRedirect(Filament::getLoginUrl());
});

it('creates the user, links the invite role + team, confirms it, and fires events', function () {
    Event::fake([Registered::class, RegisteredWithData::class]);

    $role = config('permission.models.role')::findByName('Super Admin', 'web');
    $team = Team::factory()->create();
    $invite = Invite::factory()->forTeam($team)->create([
        'email' => 'joiner@example.test',
        'role_id' => $role->id,
    ]);

    mountRegister($invite)
        ->fillForm([
            'name' => 'Joiner',
            'password' => 'longenoughpw',
            'passwordConfirmation' => 'longenoughpw',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'joiner@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('Super Admin'))->toBeTrue()
        ->and($team->members()->whereKey($user->id)->exists())->toBeTrue()
        ->and(Invite::withoutGlobalScope('onlyUnconfirmed')->find($invite->id)->is_confirmed)->toBeTrue();

    Event::assertDispatched(Registered::class);
    Event::assertDispatched(RegisteredWithData::class);
});

it('enforces a minimum 10-character password', function () {
    $invite = Invite::factory()->create(['email' => 'shortpw@example.test']);

    mountRegister($invite)
        ->fillForm([
            'name' => 'Shorty',
            'password' => 'short',
            'passwordConfirmation' => 'short',
        ])
        ->call('register')
        ->assertHasFormErrors(['password']);

    expect(User::where('email', 'shortpw@example.test')->exists())->toBeFalse();
});
