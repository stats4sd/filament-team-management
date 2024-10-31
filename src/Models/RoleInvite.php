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
 */
class RoleInvite extends Model
{
    protected $table = 'role_invites';

    protected $fillable = [
        'email',
        'inviter_id',
        'role_id',
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function confirm(): bool
    {
        $this->is_confirmed = true;
        $this->save();

        return $this->is_confirmed;
    }
}
