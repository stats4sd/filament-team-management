<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Tests\CustomPanelTestCase;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\ProgramTestCase;
use Stats4sd\FilamentTeamManagement\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case binding
|--------------------------------------------------------------------------
|
| Every test extends the package TestCase. Directory-scoped groups let us run
| a single tier (Unit, Feature, …) without naming each file.
|
*/

uses(TestCase::class)->in('Unit', 'Smoke', 'Feature');

uses(ProgramTestCase::class)->in('ProgramMode');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a host administrator and act as them. Returns the user.
 */
function actingAsAdmin(): User
{
    return test()->actingAsAdmin();
}

/**
 * Create a host administrator and act as them. Returns the user.
 */
function actingAsProgramAdmin(): User
{
    return test()->actingAsProgramAdmin();
}

/**
 * Enable program mode for the current test (config flag + program tables).
 */
function withPrograms(): TestCase
{
    return test()->withPrograms();
}

/**
 * Mount a Livewire/Filament component for testing. Thin wrapper over the
 * Livewire facade (the pest-plugin-livewire `livewire()` helper isn't installed).
 *
 * @param  array<string, mixed>  $params
 */
function livewire(string $component, array $params = []): Testable
{
    return Livewire::test($component, $params);
}

/**
 * Build the signed registration URL an invite email would contain.
 */
function signedInviteUrl(Invite $invite): string
{
    $routeName = Filament::getDefaultPanel()->generateRouteName('auth.register');

    return URL::signedRoute($routeName, ['token' => $invite->token]);
}

uses(CustomPanelTestCase::class)->in('CustomPanels');
