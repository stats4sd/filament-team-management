<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Stats4sd\FilamentTeamManagement\Contracts\MembershipParticipant;
use Stats4sd\FilamentTeamManagement\Models\Invite;

/** Shared structural rules; this class does not expose an unchecked mutation API. */
final class Membership
{
    public static function invalid(string $message): never
    {
        throw ValidationException::withMessages(['membership' => $message]);
    }

    public static function email(?string $email): string
    {
        return mb_strtolower(trim($email ?? ''));
    }

    public static function type(Model $target): string
    {
        foreach (['team', 'program'] as $type) {
            $class = config("filament-team-management.models.{$type}");
            if ($target instanceof $class) {
                if ($type === 'program' && ! config('filament-team-management.use_programs')) {
                    self::invalid('Programs are disabled.');
                }

                return $type;
            }
        }
        self::invalid('A configured team or program is required.');
    }

    public static function actor(Authenticatable $actor): Model
    {
        $class = config('filament-team-management.models.user');
        if (! $actor instanceof $class || ! $actor instanceof Model || ! $actor->exists) {
            self::invalid('An existing authenticated actor is required.');
        }

        return $actor;
    }

    public static function user(Model $user): void
    {
        $class = config('filament-team-management.models.user');
        if (! $user instanceof $class || ! $user->exists) {
            self::invalid('An existing configured user is required.');
        }
    }

    public static function authorize(Authenticatable $actor, string $ability, Model | string $target, ?Model $related = null): void
    {
        Gate::forUser($actor)->authorize($ability, $related ? [$target, $related] : $target);
    }

    public static function key(Model $model): string
    {
        return $model->getTable() . ':' . $model->getKey();
    }

    /** All affected users precede every target; callers must precompute the complete set. */
    public static function lock(array $models, bool $includeTrashed = false): array
    {
        usort($models, fn (Model $a, Model $b) => strcmp(self::key($a), self::key($b)));
        $locked = [];
        foreach ($models as $model) {
            $key = self::key($model);
            if (! isset($locked[$key])) {
                $query = $model->newQueryWithoutScopes()->whereKey($model->getKey());
                if (! $includeTrashed && method_exists($model, 'getQualifiedDeletedAtColumn')) {
                    $query->whereNull($model->getQualifiedDeletedAtColumn());
                }
                $locked[$key] = $query->lockForUpdate()->firstOrFail();
            }
        }

        return $locked;
    }

    public static function eligible(Model $model): void
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            throw (new ModelNotFoundException)->setModel($model::class, [$model->getKey()]);
        }
    }

    /** Read the current pivot row without taking related-model locks after target locks. */
    public static function attached(BelongsToMany $relation, Model $related): bool
    {
        return $relation->newPivotQuery()
            ->where($relation->getRelatedPivotKeyName(), $related->getAttribute($relation->getRelatedKeyName()))
            ->lockForUpdate()->first([$relation->getRelatedPivotKeyName()]) !== null;
    }

    public static function assertAttachment(BelongsToMany $relation, Model $related, bool $attached): void
    {
        if (self::attached($relation, $related) !== $attached) {
            self::invalid('The membership or association change was cancelled. No changes were saved.');
        }
    }

    public static function sameConnection(Model $owner, Model ...$models): void
    {
        foreach ($models as $model) {
            if ($model->getConnection()->getName() !== $owner->getConnection()->getName()) {
                self::invalid('Membership operations require all models to use the same database connection.');
            }
        }
    }

    public static function participant(string $phase, MembershipContext $context): void
    {
        foreach (config('filament-team-management.participants', []) as $participant) {
            $instance = is_string($participant) ? app($participant) : $participant;
            if (! $instance instanceof MembershipParticipant) {
                throw new \LogicException('Membership participants must implement MembershipParticipant.');
            }
            $instance->{$phase}($context);
        }
    }

    public static function observe(MembershipContext $context, string $event): void
    {
        if ($context->changed) {
            $payload = $context->payload();
            $context->target->getConnection()->afterCommit(fn () => event(new $event($payload)));
        }
    }

    public static function expiry(): ?Carbon
    {
        $days = config('filament-team-management.invite_expiry_days');

        return $days === null ? null : now()->addDays((int) $days);
    }

    public static function assertInviteTarget(Invite $invite, Model $target): void
    {
        $actual = $invite->target();
        if (self::key($actual) !== self::key($target)) {
            self::invalid('This invitation does not belong to this membership target.');
        }
    }
}
