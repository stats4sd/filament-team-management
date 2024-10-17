<?php

namespace Stats4sd\FilamentTeamManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Stats4sd\FilamentTeamManagement\FilamentTeamManagement
 */
class FilamentTeamManagement extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Stats4sd\FilamentTeamManagement\FilamentTeamManagement::class;
    }
}
