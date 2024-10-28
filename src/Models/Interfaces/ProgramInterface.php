<?php

namespace Stats4sd\FilamentTeamManagement\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface ProgramInterface
{
    /**
     * Generate an invitation to join this program for each of the provided email addresses
     */
    public function sendInvites(array $emails): void;

    public function invites(): HasMany;

    public function users(): BelongsToMany;

    public function teams(): BelongsToMany;

    // add relationship to refer to program model itself, so that program admin panel > Programs resource can show the selected program for editing
    public function program(): HasOne;
}
