<?php

use Stats4sd\FilamentTeamManagement\Models\Invite;

it('hides confirmed invites behind the onlyUnconfirmed global scope', function () {
    Invite::factory()->create();
    Invite::factory()->confirmed()->create();

    expect(Invite::count())->toBe(1)
        ->and(Invite::withoutGlobalScope('onlyUnconfirmed')->count())->toBe(2);
});

it('flips the is_confirmed flag and persists it via confirm()', function () {
    $invite = Invite::factory()->create();

    expect($invite->confirm())->toBeTrue()
        ->and($invite->is_confirmed)->toBeTrue()
        // the global scope now hides it from a normal query
        ->and(Invite::find($invite->id))->toBeNull();

    $reloaded = Invite::withoutGlobalScope('onlyUnconfirmed')->find($invite->id);

    expect($reloaded->is_confirmed)->toBeTrue();
});
