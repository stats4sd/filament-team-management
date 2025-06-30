<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

/**
 * @property string $email
 * @property int $inviter_id
 * @property int $role_id
 * @property string $token
 * @property bool $is_confirmed
 * @property ?Team $team
 * @property ?Role $role
 * @property ?Program $program
 */
class Invite extends Model
{
    protected $table = 'invites';

    protected $guarded = ['id'];

    protected $casts = [
        'is_confirmed' => 'boolean',
    ];

    // *********** RELATIONSHIPS ************ //

    /** @return BelongsTo<Model, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.user'), 'inviter_id');
    }

    /** @return BelongsTo<Model, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.role'), 'role_id');
    }

    /** @return BelongsTo<Model, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-team-management.models.program'),
            foreignKey: config('filament-team-management.models.program')::getModelNameLower() . '_id'
        );
    }

    /** @return BelongsTo<Model, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-team-management.models.team'),
            foreignKey: config('filament-team-management.models.team')::getModelNameLower() . '_id'
        );
    }

    public function confirm(): bool
    {
        $this->is_confirmed = true;
        $this->save();

        return $this->is_confirmed;
    }
}
