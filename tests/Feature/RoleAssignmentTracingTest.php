<?php

use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    Mail::fake();
});

it('writes a tracing invite and mails the user when an admin assigns a role', function () {
    $admin = actingAsAdmin();
    $target = User::factory()->create();

    $target->assignRole('Program Admin');

    $trace = Invite::withoutGlobalScope('onlyUnconfirmed')
        ->where('email', $target->email)
        ->where('is_confirmed', true)
        ->first();

    expect($trace)->not->toBeNull()
        ->and($trace->inviter_id)->toBe($admin->id);

    Mail::assertSent(UpdateUser::class);
});

it('stays silent during self-registration (no authenticated user)', function () {
    // auth()->id() is null -> the ModelHasRole created hook suppresses tracing/mail
    $target = User::factory()->create();

    $target->assignRole('Program Admin');

    expect(Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', $target->email)->exists())->toBeFalse();

    Mail::assertNothingSent();
});
