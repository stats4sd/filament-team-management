<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Team extends Model implements TeamInterface
{
    use HasFactory;
    use HasModelNameLowerString;

    public function getTable()
    {
        return config('filament-team-management.table_names.teams');
    }

    protected $guarded = ['id'];

    /** @return HasMany<Invite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(
            related: Invite::class,
            foreignKey: config('filament-team-management.column_names.teams_foreign_key'),
            localKey: 'id'
        );
    }

    /** @return BelongsToMany<Model, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.user'),
            table: config('filament-team-management.table_names.team_members'),
            foreignPivotKey: config('filament-team-management.column_names.teams_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.users_foreign_key')
        );
    }

    /** @return BelongsToMany<Model, $this> */
    public function members(): BelongsToMany
    {
        return $this->users();
    }

    /** @return BelongsToMany<Model, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.program'),
            table: config('filament-team-management.table_names.program_team'),
            foreignPivotKey: config('filament-team-management.column_names.teams_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.programs_foreign_key')
        );
    }
}
