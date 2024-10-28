<?php

namespace Stats4sd\FilamentTeamManagement\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface TeamInterface
{

    public function sendInvites(array $emails): void;

    public function invites(): HasMany;
    public function users(): BelongsToMany;
    public function admins(): BelongsToMany;
    public function members(): BelongsToMany;
    public function programs(): BelongsToMany;


    // add relationship to refer to team model itself, so that app panel > Teams resource can show the selected team for editing
    public function team(): HasOne;

}
