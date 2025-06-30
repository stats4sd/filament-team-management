<?php

namespace Stats4sd\FilamentTeamManagement\Models\Traits;

use Illuminate\Support\Str;

trait HasModelNameLowerString
{
    public static function getModelNameLower(): string
    {
        return Str::of(static::class)->afterLast('\\')->snake();
    }
}
