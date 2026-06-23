<?php

use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;

it('builds the InviteUser envelope subject from the app name', function () {
    config()->set('app.name', 'Test App');

    $mailable = new InviteUser(Invite::factory()->make(['token' => 'abc123']));

    expect($mailable->envelope()->subject)->toBe('Test App: Invitation to register');
});

it('embeds the signed registration URL carrying the invite token', function () {
    $invite = Invite::factory()->make(['token' => 'tok-xyz']);

    $acceptUrl = (new InviteUser($invite))->content()->with['acceptUrl'];

    expect($acceptUrl)->toContain('token=tok-xyz')
        ->and($acceptUrl)->toContain('signature=');
});

it('builds the UpdateUser envelope subject from the app name', function () {
    config()->set('app.name', 'Test App');

    $mailable = new UpdateUser(Invite::factory()->make());

    expect($mailable->envelope()->subject)->toBe('Test App: Update to user account');
});
