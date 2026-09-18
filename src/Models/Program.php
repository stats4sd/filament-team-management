<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;

/**
 * @property string $name
 * @property string $description
 * @property string $note
 */
class Program extends Model implements ProgramInterface
{
    use HasFactory;
    use HasModelNameLowerString;

    public function getTable()
    {
        return config('filament-team-management.table_names.programs');
    }

    protected $guarded = ['id'];

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class, config('filament-team-management.column_names.programs_foreign_key'));
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.user'),
            table: config('filament-team-management.table_names.program_members'),
            foreignPivotKey: config('filament-team-management.column_names.programs_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.users_foreign_key'),
        )->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.team'),
            table: config('filament-team-management.table_names.program_team'),
            foreignPivotKey: config('filament-team-management.column_names.programs_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.teams_foreign_key'),
        )->withTimestamps();
    }
}
