# Fresh application setup

The package manages membership; your app supplies access rules. These instructions target a fresh Laravel 13 / Filament 5 application. No permission package or role table is required.

## 1. Install and configure models

```bash
composer require stats4sd/filament-team-management
php artisan filament:install --panels
php artisan filament-team-management:install
```

Extend the package `Models\User`, `Models\Team` and optionally `Models\Program`. Point `models.*` at those classes; also set your auth provider's user model. Configure all custom tables and foreign keys before publishing/migrating. The installer offers default migrations, optional program migrations, example membership seeders and deny-default host policy stubs. Example seeders are development data, grant no privileges, and bypass action events.

The default tag always creates nullable program columns on invites and users. The program tag adds its tables and then their constraints; generated timestamps preserve ordering. For a fresh 5.0 schema, programs can later be enabled by publishing `filament-team-management-migrations-program` and migrating. Reverse program migrations before the default set. Existing older schemas require a deliberate host rebuild or separately designed migration; the installer does not delete a database.

## 2. Register policies

Register policies for your configured classes, for example in your `AppServiceProvider::boot()`:

```php
Gate::policy(config('filament-team-management.models.team'), \App\Policies\TeamPolicy::class);
Gate::policy(config('filament-team-management.models.program'), \App\Policies\ProgramPolicy::class);
Gate::policy(config('filament-team-management.models.user'), \App\Policies\UserPolicy::class);
```

Only register ProgramPolicy when programs are enabled. Published Team/Program policy methods return false until you implement them. UserPolicy governs the global User resource. Configure a `UserPicker` before enabling existing-user selection; the picker is empty by default. See the [policy examples and operation signatures](docs/membership-contract.md).

## 3. Implement panel and tenant access

The base User denies panel and tenant access and returns no tenants. Override `canAccessPanel(Panel $panel)`, `getTenants(Panel $panel)` and `canAccessTenant(Model $tenant)` in your User. Use the same query for enumeration and individual checks; do not grant a record solely because the current tenant is administrable.

This direct-membership example explicitly admits the App panel. Add your own rule for Admin and optional Program panels:

```php
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

public function canAccessPanel(Panel $panel): bool
{
    return $panel->getId() === config('filament-team-management.panels.app');
}

protected function accessibleTenants(Panel $panel): ?Builder
{
    $team = config('filament-team-management.models.team');
    $program = config('filament-team-management.models.program');
    if ($panel->getTenantModel() !== $team && ! (
        config('filament-team-management.use_programs') && $panel->getTenantModel() === $program
    )) {
        return null; // Explicitly implement other host tenant models here.
    }
    $model = $panel->getTenantModel();
    return $model::query()
        ->whereHas('users', fn (Builder $query) => $query->whereKey($this->getKey()));
}

public function getTenants(Panel $panel): array | Collection
{
    return $this->canAccessPanel($panel) ? ($this->accessibleTenants($panel)?->get() ?? collect()) : collect();
}

public function canAccessTenant(Model $tenant): bool
{
    $panel = Filament::getCurrentPanel();
    return $panel && $panel->getTenantModel() && $tenant instanceof ($panel->getTenantModel())
        && $this->canAccessPanel($panel)
        && ($this->accessibleTenants($panel)?->whereKey($tenant->getKey())->exists() ?? false);
}
```

Make policy `view` agree with this rule. For deliberate global access, have your host query return all eligible records only when your host administrator rule allows it, and use that same rule in `view`. For deliberate program-derived team access, extend the team query with an explicit `orWhereHas('programs.users', ...)` branch, then use the same accessible query in `view` and direct tenant checks. A shared team does not inherit management permissions from every linked program unless your host explicitly grants that behavior.

## 4. Wire Filament panels

Keep the middleware installed by Filament. Add these settings to your App panel; model/page classes below are fully qualified to make discovery explicit:

```php
$panel
    ->default()
    ->id(config('filament-team-management.panels.app'))
    ->path('app')
    ->login(\Stats4sd\FilamentTeamManagement\Filament\Auth\Login::class)
    ->registration(\Stats4sd\FilamentTeamManagement\Filament\Auth\Register::class)
    ->passwordReset()
    ->tenant(config('filament-team-management.models.team'))
    ->tenantProfile(\Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeam::class)
    ->tenantRegistration(\Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterTeam::class)
    ->tenantMiddleware([\Stats4sd\FilamentTeamManagement\Http\Middleware\SetLatestTeamMiddleware::class], isPersistent: true);
```

Tenant registration checks `TeamPolicy::create` at access and submission. Remove the registration page or deny `create` to disallow creation. The invitation Register page is invite-only; it is separate from tenant registration. Password reset and email verification policy/delivery remain host responsibilities.

The Admin panel has no tenant. Discover `Stats4sd\FilamentTeamManagement\Filament\Admin\Resources` from `vendor/stats4sd/filament-team-management/src/Filament/Admin/Resources`. Use `AuthenticateThroughDefaultPanel` as its authentication middleware and implement host `canAccessPanel()` admission. There are no package `CheckIfAdmin` or `CheckIfProgramAdmin` middleware classes.

For programs, register a panel with `panels.program`, the configured Program tenant, `ManageProgram` as tenant profile, optional `RegisterProgram`, and `SetLatestProgramMiddleware` as persistent tenant middleware. Its authentication middleware can also use `AuthenticateThroughDefaultPanel`. Register it only when `use_programs` is true.

Build navigation from panel objects, checking the host's admission and accessible tenants:

```php
$panel = Filament::getPanels()[config('filament-team-management.panels.admin')] ?? null;
$url = $panel && auth()->user()->canAccessPanel($panel) ? $panel->getUrl() : null;
```

Program links additionally check each tenant and policy `view`; unavailable/denied panels render escaped plain names. After leaving the final team, the package uses a permitted App registration page or its authenticated `/membership/no-memberships` route. If you configure `no_memberships_route`, that named route must be accessible to the departing user without a tenant and enforce authentication itself.

## 5. Configure notification delivery and host lifecycle rules

Run `php artisan queue:work` for the default queued mail behavior. Configure mail transport, retries and failed-job monitoring in the application. Participants provide same-transaction bootstrap, grant revocation and durable audit writes; observation events and transport run after commit. See [membership-contract.md](docs/membership-contract.md) before using these extension points.
