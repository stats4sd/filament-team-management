<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramInvite extends Model
{
    protected $table = 'program_invites';

    protected $fillable = [
        'email',
        'inviter_id',
        'program_id',
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
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function confirm(): bool
    {
        $this->is_confirmed = 1;
        $this->save();

        return $this->is_confirmed;
    }
}
