<?php

use Illuminate\Http\Request;
use Stats4sd\FilamentTeamManagement\Database\Seeders\TestUserSeeder;
use Stats4sd\FilamentTeamManagement\Http\Middleware\CheckIfProgramAdmin;
use Stats4sd\FilamentTeamManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

it('grants the seeded Super Admin all four permissions in program mode', function () {
    $this->seed(TestUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->hasPermissionTo('access admin panel'))->toBeTrue()
        ->and($admin->hasPermissionTo('view all teams'))->toBeTrue()
        ->and($admin->hasPermissionTo('access program admin panel'))->toBeTrue()
        ->and($admin->hasPermissionTo('view all programs'))->toBeTrue();
});

it('grants the Program Admin role the program permissions so it passes CheckIfProgramAdmin', function () {
    $this->seed(TestUserSeeder::class);

    // Gate::before in the harness only shortcuts Super Admins, so a Program
    // Admin's ->can() genuinely resolves through the attached permission.
    $user = User::factory()->create();
    $user->assignRole('Program Admin');

    expect($user->hasPermissionTo('access program admin panel'))->toBeTrue()
        ->and($user->hasPermissionTo('view all programs'))->toBeTrue();

    $this->actingAs($user);

    $response = (new CheckIfProgramAdmin)->handle(new Request, fn (Request $request): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
