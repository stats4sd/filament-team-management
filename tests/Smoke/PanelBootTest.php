<?php

use Filament\Facades\Filament;

it('boots the App and Admin panels without programs', function () {
    $ids = array_keys(Filament::getPanels());

    expect($ids)->toContain('app')
        ->and($ids)->toContain('admin')
        ->and($ids)->not->toContain('program');
});

it('registers the configured Team model as the App panel tenant', function () {
    expect(Filament::getPanel('app')->getTenantModel())
        ->toBe(config('filament-team-management.models.team'));
});
