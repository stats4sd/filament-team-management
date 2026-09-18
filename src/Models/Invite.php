<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stats4sd\FilamentTeamManagement\Support\Membership;

/**
 * @property string $email
 * @property string $token
 * @property bool $is_confirmed
 * @property ?Carbon $expires_at
 * @property ?Model $inviter
 */
class Invite extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['is_confirmed' => 'boolean', 'expires_at' => 'datetime'];

    public function getTable()
    {
        return config('filament-team-management.table_names.invites', 'invites');
    }

    public function setEmailAttribute(string $email): void
    {
        $this->attributes['email'] = Membership::email($email);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.user'), 'inviter_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.program'), config('filament-team-management.column_names.programs_foreign_key'));
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.team'), config('filament-team-management.column_names.teams_foreign_key'));
    }

    public function target(): Model
    {
        $team = $this->getAttribute(config('filament-team-management.column_names.teams_foreign_key'));
        $program = $this->getAttribute(config('filament-team-management.column_names.programs_foreign_key'));
        if (($team === null) === ($program === null)) {
            Membership::invalid('This invitation has no valid membership target.');
        }
        $target = $team !== null ? $this->team()->withoutGlobalScopes()->first() : $this->program()->withoutGlobalScopes()->first();
        if (! $target) {
            Membership::invalid('The invitation target is no longer available.');
        }
        Membership::type($target);
        Membership::eligible($target);

        return $target;
    }

    public function isAccepted(): bool
    {
        return (bool) $this->is_confirmed;
    }

    public function isExpired(): bool
    {
        return ! $this->isAccepted() && $this->expires_at !== null && $this->expires_at->lte(now());
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public function scopeUnaccepted(Builder $query): Builder
    {
        return $query->where('is_confirmed', false);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('is_confirmed', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_confirmed', false)->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
