<?php

namespace Stats4sd\FilamentTeamManagement\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface TeamInterface
{
    public function sendInvites(array $emails): void;

    public function invites(): HasMany;

    public function users(): BelongsToMany;

    public function admins(): BelongsToMany;

    public function members(): BelongsToMany;

    public function programs(): BelongsToMany;
}
