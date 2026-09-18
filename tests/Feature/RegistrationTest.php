<?php

use Filament\Facades\Filament;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Stats4sd\FilamentTeamManagement\Events\RegisteredWithData;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Register;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

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

it('redirects an already-authenticated user away from the register page', function () {
    $invite = Invite::factory()->create(['email' => 'invited@example.test']);
    $this->actingAs(User::factory()->create());

    mountRegister($invite)
        ->assertRedirect(Filament::getUrl());
});

it('creates the user, joins the invited team, confirms it, and fires events', function () {
    Event::fake([Registered::class, RegisteredWithData::class]);

    $team = Team::factory()->create();
    $invite = Invite::factory()->forTeam($team)->create([
        'email' => 'joiner@example.test',
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
        ->and($team->members()->whereKey($user->id)->exists())->toBeTrue()
        ->and(Invite::query()->find($invite->id)->is_confirmed)->toBeTrue();

    Event::assertDispatched(Registered::class);
    Event::assertDispatched(RegisteredWithData::class, fn ($event) => $event->data['original_password'] === 'longenoughpw' && Hash::check('longenoughpw', $event->data['password']));
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

it('surfaces the custom minimum-length validation message', function () {
    $invite = Invite::factory()->create(['email' => 'pwmsg@example.test']);

    mountRegister($invite)
        ->fillForm([
            'name' => 'Shorty',
            'password' => 'short',
            'passwordConfirmation' => 'short',
        ])
        ->call('register')
        ->assertHasFormErrors(['password' => 'Password must be at least 10 characters long.']);
});

it('does not authenticate an invited account before its outer transaction commits', function () {
    $team = Team::factory()->create();
    $invite = Invite::factory()->forTeam($team)->create(['email' => 'rollback@example.test']);
    DB::beginTransaction();
    mountRegister($invite)
        ->fillForm(['name' => 'Rollback', 'password' => 'longenoughpw', 'passwordConfirmation' => 'longenoughpw'])
        ->call('register')
        ->assertHasNoFormErrors();
    expect(Filament::auth()->check())->toBeFalse();
    DB::rollBack();
    expect(Filament::auth()->check())->toBeFalse()->and(User::where('email', $invite->email)->exists())->toBeFalse()->and($invite->fresh()->isPending())->toBeTrue();
});
