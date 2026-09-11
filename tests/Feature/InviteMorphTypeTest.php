<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Models\User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\CustomUser;

beforeEach(function () {
    Mail::fake();
});

// Named regression for bug 4.6: when the host subclasses the User model, inviting an
// EXISTING user must attach the role under the host's morph type. Before the fix all
// three sendInvites() paths looked the user up via the package's own User class, so the
// role was written with model_type = package User and the host user never saw it.
it('assigns roles under the configured user class morph type when User is subclassed (guards 4.6)', function () {
    $inviter = actingAsAdmin();

    $existing = User::factory()->create();
    $role = config('permission.models.role')::findByName('Super Admin', 'web');

    // Repoint the package at a host User subclass (shares the users table).
    config()->set('filament-team-management.models.user', CustomUser::class);

    $inviter->sendInvites([
        ['email' => $existing->email, 'role' => $role->id],
    ]);

    $morphType = DB::table(config('permission.table_names.model_has_roles'))
        ->where('role_id', $role->id)
        ->where('model_id', $existing->id)
        ->value('model_type');

    expect($morphType)->toBe(CustomUser::class);

    // And the host user model (matching morph type) actually sees the role.
    expect(CustomUser::find($existing->id)->hasRole('Super Admin'))->toBeTrue();
});
