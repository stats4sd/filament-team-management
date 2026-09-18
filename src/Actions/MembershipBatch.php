<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Support\Membership;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class MembershipBatch
{
    public function handle(Authenticatable $actor, string $operation, iterable $records, ?Model $target = null): array
    {
        if (! in_array($operation, ['add_member', 'remove_member', 'delete_team', 'delete_program', 'delete_user', 'link_team', 'unlink_team'], true)) {
            throw new \InvalidArgumentException('Unsupported bulk membership operation.');
        }
        $operations = [];
        $seen = [];
        foreach ($records as $record) {
            $key = Membership::key($record);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $operations[] = str_starts_with($operation, 'delete_') ? [$operation, $record] : [$operation, $target, $record];
        }

        return app(MembershipMutation::class)->run($actor, $operations);
    }
}
