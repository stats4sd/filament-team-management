<?php

namespace Stats4sd\FilamentTeamManagement\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $description
 */
interface ProgramInterface
{
    public function invites(): HasMany;

    public function users(): BelongsToMany;

    public function members(): BelongsToMany;

    public function teams(): BelongsToMany;
}
