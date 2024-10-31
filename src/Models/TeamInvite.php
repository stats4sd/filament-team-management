<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $email
 * @property int $inviter_id
 * @property string $token
 * @property bool $is_confirmed
 * @property User $inviter
 * @property Team $team
 */
class TeamInvite extends Model
{
    protected $table = 'team_invites';

    protected $fillable = [
        'email',
        'inviter_id',
        'token',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
    ];

    // do not use global scope, to show all invitation emails sent
    //
    // protected static function booted(): void
    // {
    //     static::addGlobalScope('unconfirmed', static function (Builder $builder) {
    //         $builder->where('is_confirmed', false);
    //     });
    // }

    // *********** RELATIONSHIPS ************ //
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.user'), 'inviter_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.team'), 'team_id');
    }

    // ************ METHODS ************ //

    public function confirm(): bool
    {
        $this->is_confirmed = true;
        $this->save();

        return $this->is_confirmed;
    }
}
