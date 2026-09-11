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
    /**
     * Generate an invitation to join this program for each of the provided email addresses
     */
    public function sendInvites(array $emails): void;

    public function invites(): HasMany;

    public function users(): BelongsToMany;

    public function teams(): BelongsToMany;
}
