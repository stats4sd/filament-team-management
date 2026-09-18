<?php

use Stats4sd\FilamentTeamManagement\Models\Invite;

it('keeps accepted history visible and exposes explicit pending and accepted scopes', function () {
    Invite::factory()->create();
    Invite::factory()->confirmed()->create();
    Invite::factory()->create(['expires_at' => now()->subSecond()]);

    expect(Invite::count())->toBe(3)
        ->and(Invite::accepted()->count())->toBe(1)
        ->and(Invite::unaccepted()->count())->toBe(2)
        ->and(Invite::pending()->count())->toBe(1);
});

it('uses consistent accepted pending and expired predicates', function () {
    $invite = Invite::factory()->create();
    expect($invite->isPending())->toBeTrue()->and($invite->isAccepted())->toBeFalse()->and($invite->isExpired())->toBeFalse();
    $invite->forceFill(['expires_at' => now()->subSecond()])->save();
    expect($invite->isPending())->toBeFalse()->and($invite->isExpired())->toBeTrue();
    $invite->forceFill(['is_confirmed' => true])->save();
    expect($invite->isPending())->toBeFalse()->and($invite->isExpired())->toBeFalse()->and($invite->isAccepted())->toBeTrue()->and(Invite::find($invite->id))->not->toBeNull();
});
