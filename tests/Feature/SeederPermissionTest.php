<?php

use Illuminate\Http\Request;
use Stats4sd\FilamentTeamManagement\Database\Seeders\TestUserSeeder;
use Stats4sd\FilamentTeamManagement\Http\Middleware\CheckIfAdmin;
use Stats4sd\FilamentTeamManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('attaches the admin permissions to the seeded Super Admin', function () {
    $this->seed(TestUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    // hasPermissionTo checks the Spatie permission directly, bypassing the
    // Gate::before Super-Admin shortcut in the test harness, so it proves the
    // permission is actually attached to the role.
    expect($admin->hasRole('Super Admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('access admin panel'))->toBeTrue()
        ->and($admin->hasPermissionTo('view all teams'))->toBeTrue();
});

it('lets a holder of the seeded "access admin panel" permission through CheckIfAdmin', function () {
    $this->seed(TestUserSeeder::class);

    // A plain user (NOT Super Admin) so the harness Gate::before Super-Admin
    // shortcut does not mask the result — this genuinely exercises the
    // permission the seeder creates flowing through the middleware.
    $user = User::factory()->create();
    $user->givePermissionTo('access admin panel');

    $this->actingAs($user);
    $response = (new CheckIfAdmin)->handle(new Request, fn (Request $request): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
});

it('blocks a user without the "access admin panel" permission in CheckIfAdmin', function () {
    $this->seed(TestUserSeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => (new CheckIfAdmin)->handle(new Request, fn (Request $request): Response => new Response('ok')))
        ->toThrow(HttpException::class);
});

it('does not grant the program permissions when programs are disabled', function () {
    $this->seed(TestUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->hasPermissionTo('access program admin panel'))->toBeFalse()
        ->and($admin->hasPermissionTo('view all programs'))->toBeFalse();
});
