<?php

namespace Stats4sd\FilamentTeamManagement\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasPermissions;

trait HasRoles
{
    use HasPermissions;

    private ?string $roleClass = null;

    // ****** TEAM MANAGEMENT STUFF ******

    public static function boot()
    {
        parent::boot();

        logger('HasRoles.boot()...');

        // ModelHasRole model event "created" is not triggered when a new model_has_roles record is created.
        // TODO: try to create a new class trait to extends Spatie\Permission\Traits\HasRoles
        static::created(function ($item) {
            logger('HasRoles.created()...');

            // TODO: check if there is invites record for this role
            // if invites record found, email invite should have been sent before, no need to send email notification
            // if no invites record found, send email notification to user (this role should be added by Super Admin in admin panel > Users resource > edit page)

            logger('role_id: ' . $item->role_id);
            logger('model_id: ' . $item->model_id);

        });

    }

    public static function bootHasRoles()
    {
        logger('HasRoles.bootHasRoles()...');

        static::deleting(function ($model) {
            logger('HasRoles.bootHasRoles.deleting()...');

            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $teams = app(PermissionRegistrar::class)->teams;
            app(PermissionRegistrar::class)->teams = false;
            $model->roles()->detach();
            if (is_a($model, Permission::class)) {
                $model->users()->detach();
            }
            app(PermissionRegistrar::class)->teams = $teams;
        });

        static::creating(function ($model) {
            logger('HasRoles.bootHasRoles.creating()...');

        });
    }

    public function getRoleClass(): string
    {
        logger('HasRoles.getRoleClass()...');

        if (! $this->roleClass) {
            $this->roleClass = app(PermissionRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): BelongsToMany
    {
        logger('HasRoles.roles()...');

        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        );

        if (! app(PermissionRegistrar::class)->teams) {
            return $relation;
        }

        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $relation->withPivot($teamsKey);
        $teamField = config('permission.table_names.roles') . '.' . $teamsKey;

        return $relation->wherePivot($teamsKey, getPermissionsTeamId())
            ->where(fn ($q) => $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId()));
    }

    /**
     * Scope the model query to certain roles only.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     * @param  string  $guard
     * @param  bool  $without
     */
    public function scopeRole(Builder $query, $roles, $guard = null, $without = false): Builder
    {
        logger('HasRoles.scopeRole()...');

        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        $roles = array_map(function ($role) use ($guard) {
            if ($role instanceof Role) {
                return $role;
            }

            if ($role instanceof \BackedEnum) {
                $role = $role->value;
            }

            $method = is_int($role) || PermissionRegistrar::isUid($role) ? 'findById' : 'findByName';

            return $this->getRoleClass()::{$method}($role, $guard ?: $this->getDefaultGuardName());
        }, Arr::wrap($roles));

        $key = (new ($this->getRoleClass())())->getKeyName();

        return $query->{! $without ? 'whereHas' : 'whereDoesntHave'}(
            'roles',
            fn (Builder $subQuery) => $subQuery
                ->whereIn(config('permission.table_names.roles') . ".$key", \array_column($roles, $key))
        );
    }

    /**
     * Scope the model query to only those without certain roles.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     * @param  string  $guard
     */
    public function scopeWithoutRole(Builder $query, $roles, $guard = null): Builder
    {
        logger('HasRoles.scopeWithoutRole()...');

        return $this->scopeRole($query, $roles, $guard, true);
    }

    /**
     * Returns array of role ids
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    private function collectRoles(...$roles): array
    {
        logger('HasRoles.collectRoles()...');

        return collect($roles)
            ->flatten()
            ->reduce(function ($array, $role) {
                if (empty($role)) {
                    return $array;
                }

                $role = $this->getStoredRole($role);

                if (! in_array($role->getKey(), $array)) {
                    $this->ensureModelSharesGuard($role);
                    $array[] = $role->getKey();
                }

                return $array;
            }, []);
    }

    /**
     * Assign the given role to the model.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        logger('HasRoles.assignRole()...');

        $roles = $this->collectRoles($roles);

        $model = $this->getModel();
        $teamPivot = app(PermissionRegistrar::class)->teams && ! is_a($this, Permission::class) ?
            [app(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId()] : [];

        if ($model->exists) {
            if (app(PermissionRegistrar::class)->teams) {
                // explicit reload in case team has been changed since last load
                $this->load('roles');
            }

            $currentRoles = $this->roles->map(fn ($role) => $role->getKey())->toArray();

            $this->roles()->attach(array_diff($roles, $currentRoles), $teamPivot);
            $model->unsetRelation('roles');
        } else {
            $class = \get_class($model);
            $saved = false;

            $class::saved(
                function ($object) use ($roles, $model, $teamPivot, &$saved) {
                    if ($saved || $model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->roles()->attach($roles, $teamPivot);
                    $model->unsetRelation('roles');
                    $saved = true;
                }
            );
        }

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        if (config('permission.events_enabled')) {
            event(new RoleAttached($this->getModel(), $roles));
        }

        return $this;
    }

    /**
     * Revoke the given role from the model.
     *
     * @param  string|int|Role|\BackedEnum  $role
     */
    public function removeRole($role)
    {
        logger('HasRoles.removeRole()...');

        $storedRole = $this->getStoredRole($role);

        $this->roles()->detach($storedRole);

        $this->unsetRelation('roles');

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        if (config('permission.events_enabled')) {
            event(new RoleDetached($this->getModel(), $storedRole));
        }

        return $this;
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$roles
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        logger('HasRoles.syncRole()...');

        if ($this->getModel()->exists) {
            $this->collectRoles($roles);
            $this->roles()->detach();
            $this->setRelation('roles', collect());
        }

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        logger('HasRoles.hasRole()...');

        $this->loadMissing('roles');

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;

            return $this->roles
                ->when($guard, fn ($q) => $q->where('guard_name', $guard))
                ->pluck('name')
                ->contains(function ($name) use ($roles) {
                    /** @var string|\BackedEnum $name */
                    if ($name instanceof \BackedEnum) {
                        return $name->value == $roles;
                    }

                    return $name == $roles;
                });
        }

        if (is_int($roles) || PermissionRegistrar::isUid($roles)) {
            $key = (new ($this->getRoleClass())())->getKeyName();

            return $guard
                ? $this->roles->where('guard_name', $guard)->contains($key, $roles)
                : $this->roles->contains($key, $roles);
        }

        if (is_string($roles)) {
            return $guard
                ? $this->roles->where('guard_name', $guard)->contains('name', $roles)
                : $this->roles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof Collection) {
            return $roles->intersect($guard ? $this->roles->where('guard_name', $guard) : $this->roles)->isNotEmpty();
        }

        throw new \TypeError('Unsupported type for $roles parameter to hasRole().');
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * Alias to hasRole() but without Guard controls
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasAnyRole(...$roles): bool
    {
        logger('HasRoles.hasAnyRole()...');

        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param  string|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        logger('HasRoles.hasAllRoles()...');

        $this->loadMissing('roles');

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->hasRole($roles, $guard);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        $roles = collect()->make($roles)->map(function ($role) {
            if ($role instanceof \BackedEnum) {
                return $role->value;
            }

            return $role instanceof Role ? $role->name : $role;
        });

        $roleNames = $guard
            ? $this->roles->where('guard_name', $guard)->pluck('name')
            : $this->getRoleNames();

        $roleNames = $roleNames->transform(function ($roleName) {
            if ($roleName instanceof \BackedEnum) {
                return $roleName->value;
            }

            return $roleName;
        });

        return $roles->intersect($roleNames) == $roles;
    }

    /**
     * Determine if the model has exactly all of the given role(s).
     *
     * @param  string|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        logger('HasRoles.hasExactRoles()...');

        $this->loadMissing('roles');

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if ($roles instanceof Role) {
            $roles = [$roles->name];
        }

        $roles = collect()->make($roles)->map(
            fn ($role) => $role instanceof Role ? $role->name : $role
        );

        return $this->roles->count() == $roles->count() && $this->hasAllRoles($roles, $guard);
    }

    /**
     * Return all permissions directly coupled to the model.
     */
    public function getDirectPermissions(): Collection
    {
        logger('HasRoles.getDirectPermissions()...');

        return $this->permissions;
    }

    public function getRoleNames(): Collection
    {
        logger('HasRoles.getRoleNames()...');

        $this->loadMissing('roles');

        return $this->roles->pluck('name');
    }

    protected function getStoredRole($role): Role
    {
        logger('HasRoles.getStoredRole()...');

        if ($role instanceof \BackedEnum) {
            $role = $role->value;
        }

        if (is_int($role) || PermissionRegistrar::isUid($role)) {
            return $this->getRoleClass()::findById($role, $this->getDefaultGuardName());
        }

        if (is_string($role)) {
            return $this->getRoleClass()::findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        logger('HasRoles.convertPipeToArray()...');

        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return [str_replace('|', '', $pipeString)];
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}
