<?php

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\UserResource;
use Stats4sd\FilamentTeamManagement\Http\Middleware\CheckIfAdmin;
use Stats4sd\FilamentTeamManagement\Http\Middleware\CheckIfProgramAdmin;
use Stats4sd\FilamentTeamManagement\Http\Middleware\SetLatestTeamMiddleware;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

function passThrough(): Closure
{
    return fn (Request $request): Response => new Response('ok');
}

describe('CheckIfAdmin', function () {
    it('aborts with 403 without the access admin panel permission', function () {
        $this->actingAs(User::factory()->create());

        (new CheckIfAdmin)->handle(new Request, passThrough());
    })->throws(HttpException::class);

    it('passes a user holding the permission through', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('access admin panel');
        $this->actingAs($user);

        $response = (new CheckIfAdmin)->handle(new Request, passThrough());

        expect($response->getStatusCode())->toBe(200);
    });
});

describe('CheckIfProgramAdmin', function () {
    it('aborts with 403 without the access program admin panel permission', function () {
        $this->actingAs(User::factory()->create());

        (new CheckIfProgramAdmin)->handle(new Request, passThrough());
    })->throws(HttpException::class);

    it('passes a user holding the permission through', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('access program admin panel');
        $this->actingAs($user);

        $response = (new CheckIfProgramAdmin)->handle(new Request, passThrough());

        expect($response->getStatusCode())->toBe(200);
    });
});

describe('SetLatestTeamMiddleware', function () {
    it('persists the current tenant as the latest team', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $this->actingAs($user);
        Filament::setTenant($team, isQuiet: true);

        (new SetLatestTeamMiddleware)->handle(new Request, passThrough());

        expect($user->fresh()->latest_team_id)->toBe($team->id);
    });

    it('is a no-op when there is no current tenant', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setTenant(null, isQuiet: true);

        $response = (new SetLatestTeamMiddleware)->handle(new Request, passThrough());

        expect($response->getStatusCode())->toBe(200)
            ->and($user->fresh()->latest_team_id)->toBeNull();
    });
});

it('redirects unauthenticated admin-panel access to the App panel login', function () {
    $response = $this->get(UserResource::getUrl('index', panel: 'admin'));

    $response->assertRedirect(Filament::getPanel('app')->getLoginUrl());
});
