<?php

namespace Stats4sd\FilamentTeamManagement\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface TeamInterface
{
    public function invites(): HasMany;

    public function users(): BelongsToMany;

    public function members(): BelongsToMany;

    public function programs(): BelongsToMany;
}
