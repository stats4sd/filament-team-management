<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Events\MemberAdded;
use Stats4sd\FilamentTeamManagement\Events\MemberRemoved;
use Stats4sd\FilamentTeamManagement\Events\TeamLinkedToProgram;
use Stats4sd\FilamentTeamManagement\Events\TeamUnlinkedFromProgram;

/** Internal transaction orchestration. Every public entry still performs authorization. */
final class MembershipMutation
{
    public function run(Authenticatable $actor, array $operations): array
    {
        $actorModel = Membership::actor($actor);

        return $actorModel->getConnection()->transaction(function () use ($actor, $actorModel, $operations): array {
            $users = [$actorModel];
            $targets = [];
            $eligibleUsers = [$actorModel];
            $eligibleTargets = [];
            $deletingTargets = [];
            foreach ($operations as $operation) {
                [$name, $target, $record] = array_pad($operation, 3, null);
                if ($target) {
                    Membership::sameConnection($actorModel, $target);
                }
                if ($record instanceof Model) {
                    Membership::sameConnection($actorModel, $record);
                }
                if (preg_match('/^(update|delete)_(team|program)$/', $name, $matches) && Membership::type($target) !== $matches[2]) {
                    Membership::invalid('The target does not match this operation.');
                }
                if (in_array($name, ['create_team', 'create_program', 'create_user'], true)) {
                    continue;
                }
                if (in_array($name, ['delete_user', 'update_user'], true)) {
                    Membership::user($target);
                    $users[] = $target;
                    $eligibleUsers[] = $target;
                } else {
                    Membership::type($target);
                    $targets[] = $target;
                    $eligibleTargets[] = $target;
                }
                if (in_array($name, ['add_member', 'remove_member'], true)) {
                    Membership::user($record);
                    $users[] = $record;
                    $eligibleUsers[] = $record;
                }
                if ($name === 'leave') {
                    $users[] = $actorModel;
                }
                if (in_array($name, ['link_team', 'unlink_team'], true)) {
                    if (Membership::type($target) !== 'program' || Membership::type($record) !== 'team') {
                        Membership::invalid('A program and a team are required.');
                    }
                    $targets[] = $record;
                    $eligibleTargets[] = $record;
                }
                if (in_array($name, ['delete_team', 'delete_program'], true)) {
                    $deletingTargets[] = $target;
                    array_push($users, ...$target->users()->withoutGlobalScopes()->get()->all());
                }
            }
            $lockedUsers = Membership::lock($users, true);
            foreach ($eligibleUsers as $user) {
                Membership::eligible($lockedUsers[Membership::key($user)]);
            }
            $actor = $lockedUsers[Membership::key($actorModel)];
            // Account deletion enumerates only after its user lock prevents concurrent adds.
            foreach ($operations as $operation) {
                if ($operation[0] === 'delete_user') {
                    $user = $lockedUsers[Membership::key($operation[1])];
                    array_push($targets, ...$user->teams()->withoutGlobalScopes()->get()->all());
                    if (config('filament-team-management.use_programs')) {
                        array_push($targets, ...$user->programs()->withoutGlobalScopes()->get()->all());
                    }
                }
            }
            foreach ($deletingTargets as $target) {
                if (Membership::type($target) === 'program') {
                    array_push($targets, ...$target->teams()->withoutGlobalScopes()->get()->all());
                } elseif (config('filament-team-management.use_programs')) {
                    array_push($targets, ...$target->programs()->withoutGlobalScopes()->get()->all());
                }
            }
            $lockedTargets = Membership::lock($targets, true);
            foreach ($eligibleTargets as $target) {
                Membership::eligible($lockedTargets[Membership::key($target)]);
            }
            // A graph changed before locks were acquired: abort before invoking any participant.
            foreach ($deletingTargets as $original) {
                $target = $lockedTargets[Membership::key($original)];
                foreach ($target->users()->withoutGlobalScopes()->get() as $user) {
                    if (! isset($lockedUsers[Membership::key($user)])) {
                        Membership::invalid('Membership changed while this operation was starting. Please try again.');
                    }
                }
                $links = Membership::type($target) === 'program' ? $target->teams()->withoutGlobalScopes()->get() : (config('filament-team-management.use_programs') ? $target->programs()->withoutGlobalScopes()->get() : collect());
                foreach ($links as $link) {
                    if (! isset($lockedTargets[Membership::key($link)])) {
                        Membership::invalid('Associations changed while this operation was starting. Please try again.');
                    }
                }
            }
            $results = [];
            foreach ($operations as $operation) {
                [$name, $target, $record, $data] = array_pad($operation, 4, []);
                $target = $target instanceof Model ? ($lockedTargets[Membership::key($target)] ?? $lockedUsers[Membership::key($target)]) : $target;
                $record = $record instanceof Model ? ($lockedTargets[Membership::key($record)] ?? $lockedUsers[Membership::key($record)]) : null;
                $results[] = $this->apply($actor, $name, $target, $record, $data);
            }

            return $results;
        }, 1);
    }

    private function apply(Authenticatable $actor, string $operation, ?Model $target, ?Model $record, array $data): mixed
    {
        if (str_starts_with($operation, 'create_')) {
            $type = substr($operation, 7);
            $class = config("filament-team-management.models.{$type}");
            $target = new $class;
            if ($type !== 'user') {
                Membership::type($target);
            }
            Membership::sameConnection(Membership::actor($actor), $target);
            Membership::authorize($actor, 'create', $class);
            $context = new MembershipContext($operation, $actor, $target, $type === 'user' ? $target : Membership::actor($actor), origin: 'bootstrap');
            Membership::participant('before', $context);
            $target->fill($data);
            if (! $target->save()) {
                Membership::invalid('The model creation was cancelled. No membership changes were saved.');
            }
            if ($type !== 'user') {
                $target->users()->attach($actor->getAuthIdentifier());
                Membership::assertAttachment($target->users(), Membership::actor($actor), true);
            }
            $context->changed = true;
            Membership::participant('after', $context);
            if ($type !== 'user') {
                Membership::observe($context, MemberAdded::class);
            }

            return $target;
        }
        if (str_starts_with($operation, 'update_')) {
            Membership::authorize($actor, 'update', $target);
            $target->fill($data);
            if (! $target->isDirty()) {
                return $target;
            }
            $context = new MembershipContext($operation, $actor, $target);
            Membership::participant('before', $context);
            if (! $target->save()) {
                Membership::invalid('The model update was cancelled. No membership changes were saved.');
            }
            $context->changed = true;
            Membership::participant('after', $context);

            return $target;
        }
        if (in_array($operation, ['add_member', 'remove_member', 'leave'], true)) {
            if ($operation === 'remove_member' && Membership::key($record) === Membership::key(Membership::actor($actor))) {
                $operation = 'leave';
            }
            $user = $operation === 'leave' ? Membership::actor($actor) : $record;
            Membership::authorize($actor, match ($operation) {
                'add_member' => 'addMember', 'leave' => 'leave', default => 'removeMember'
            }, $target, $operation === 'leave' ? null : $user);

            return $this->membership($actor, $target, $user, $operation === 'add_member', $operation, $data['origin'] ?? ($operation === 'add_member' ? 'direct_add' : 'direct_remove'));
        }
        if (in_array($operation, ['link_team', 'unlink_team'], true)) {
            Membership::authorize($actor, $operation === 'link_team' ? 'linkTeam' : 'unlinkTeam', $target, $record);
            Membership::authorize($actor, $operation === 'link_team' ? 'linkProgram' : 'unlinkProgram', $record, $target);

            return $this->link($actor, $target, $record, $operation === 'link_team');
        }
        if (str_starts_with($operation, 'delete_')) {
            Membership::authorize($actor, 'delete', $target);
            $context = new MembershipContext($operation, $actor, $target, $operation === 'delete_user' ? $target : null);
            Membership::participant('before', $context);
            if ($operation === 'delete_user') {
                foreach ($target->teams()->withoutGlobalScopes()->get() as $team) {
                    $this->membership($actor, $team, $target, false, 'remove_member', 'account_deleted');
                }
                if (config('filament-team-management.use_programs')) {
                    foreach ($target->programs()->withoutGlobalScopes()->get() as $program) {
                        $this->membership($actor, $program, $target, false, 'remove_member', 'account_deleted');
                    }
                }
            } else {
                foreach ($target->users()->withoutGlobalScopes()->get() as $user) {
                    $this->membership($actor, $target, $user, false, 'remove_member', 'target_deleted');
                }
                if (Membership::type($target) === 'program') {
                    foreach ($target->teams()->withoutGlobalScopes()->get() as $team) {
                        $this->link($actor, $target, $team, false, 'target_deleted');
                    }
                } elseif (config('filament-team-management.use_programs')) {
                    foreach ($target->programs()->withoutGlobalScopes()->get() as $program) {
                        $this->link($actor, $program, $target, false, 'target_deleted');
                    }
                }
            }
            if (! $target->delete()) {
                Membership::invalid('The model deletion was cancelled. No membership changes were saved.');
            }
            $context->changed = true;
            Membership::participant('after', $context);

            return true;
        }

        throw new \InvalidArgumentException('Unsupported membership operation.');
    }

    private function membership(Authenticatable $actor, Model $target, Model $user, bool $add, string $operation, string $origin = 'direct_add'): bool
    {
        $exists = $target->users()->withoutGlobalScopes()->whereKey($user->getKey())->exists();
        if ($exists === $add) {
            return false;
        }
        $context = new MembershipContext($operation, $actor, $target, $user, origin: $origin);
        Membership::participant('before', $context);
        if ($add) {
            $target->users()->attach($user->getKey());
        } else {
            $target->users()->detach($user->getKey());
        }
        Membership::assertAttachment($target->users(), $user, $add);
        $context->changed = true;
        Membership::participant('after', $context);
        Membership::observe($context, $add ? MemberAdded::class : MemberRemoved::class);

        return true;
    }

    private function link(Authenticatable $actor, Model $program, Model $team, bool $add, string $origin = 'direct'): bool
    {
        if ($program->teams()->withoutGlobalScopes()->whereKey($team->getKey())->exists() === $add) {
            return false;
        }
        $context = new MembershipContext($add ? 'link_team' : 'unlink_team', $actor, $program, team: $team, origin: $origin);
        Membership::participant('before', $context);
        if ($add) {
            $program->teams()->attach($team->getKey());
        } else {
            $program->teams()->detach($team->getKey());
        }
        Membership::assertAttachment($program->teams(), $team, $add);
        $context->changed = true;
        Membership::participant('after', $context);
        Membership::observe($context, $add ? TeamLinkedToProgram::class : TeamUnlinkedFromProgram::class);

        return true;
    }
}
