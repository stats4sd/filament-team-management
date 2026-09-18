<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Traits;

use Illuminate\Support\Str;

trait HasTeamManagementNavigationGroup
{
    public static function getNavigationGroup(): string
    {
        $programTypeName = Str::of(config('filament-team-management.names.program'))->plural()->ucFirst();
        $teamTypeName = Str::of(config('filament-team-management.names.team'))->plural()->ucFirst();
        $userTypeName = Str::of(config('filament-team-management.names.user'))->plural()->ucFirst();

        if (config('filament-team-management.use_programs')) {
            return "$programTypeName, $teamTypeName and $userTypeName";
        } else {
            return "$teamTypeName and $userTypeName";
        }
    }
}
