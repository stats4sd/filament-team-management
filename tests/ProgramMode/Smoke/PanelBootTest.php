<?php

use Filament\Facades\Filament;

it('boots the Program panel when programs are enabled', function () {
    $ids = array_keys(Filament::getPanels());

    expect($ids)->toContain('program')
        ->and(Filament::getPanel('program')->getTenantModel())
        ->toBe(config('filament-team-management.models.program'));
});
