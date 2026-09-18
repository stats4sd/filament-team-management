<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Models\Invite;

final class MembershipContext
{
    public function __construct(
        public readonly string $operation,
        public readonly ?Authenticatable $actor,
        public readonly Model $target,
        public readonly ?Model $user = null,
        public readonly ?Invite $invite = null,
        public readonly ?Model $team = null,
        public bool $changed = false,
        public readonly string $origin = 'direct',
        public readonly bool $acceptance = false,
    ) {}

    /** Only identifiers and non-secret operation metadata leave the transaction. */
    public function payload(): array
    {
        return [
            'operation' => $this->operation,
            'actor_id' => $this->actor?->getAuthIdentifier(),
            'target_type' => $this->target::class,
            'target_id' => $this->target->getKey(),
            'user_id' => $this->user?->getKey(),
            'invite_id' => $this->invite?->getKey(),
            'team_id' => $this->team?->getKey(),
            'origin' => $this->origin,
            'changed' => $this->changed,
            'acceptance' => $this->acceptance,
        ];
    }
}
