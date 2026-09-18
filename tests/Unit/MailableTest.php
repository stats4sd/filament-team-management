<?php

use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Support\MembershipMail;

it('builds the InviteUser envelope subject from the app name', function () {
    config()->set('app.name', 'Test App');
    $team = Team::factory()->create();
    $mailable = new InviteUser(Invite::factory()->forTeam($team)->create(['token' => 'abc123']));
    expect($mailable->envelope()->subject)->toBe('Test App: Invitation to register');
});

it('embeds a plain registration URL carrying the snapshotted invite token', function () {
    $team = Team::factory()->create();
    $invite = Invite::factory()->forTeam($team)->create(['token' => 'tok-xyz']);
    $mail = new InviteUser($invite);
    $invite->forceFill(['token' => 'rotated'])->save();
    expect($mail->acceptUrl)->toContain('token=tok-xyz')->and($mail->acceptUrl)->not->toContain('signature=');
});

it('builds the UpdateUser envelope subject and body from a membership snapshot', function () {
    config()->set('app.name', 'Test App');
    $team = Team::factory()->create(['name' => 'Research']);
    $mailable = new UpdateUser(MembershipMail::snapshot($team, null));
    expect($mailable->envelope()->subject)->toBe('Test App: Membership updated')->and($mailable->render())->toContain('Research')->and($mailable->render())->toContain('A site administrator');
});
