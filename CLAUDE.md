# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`stats4sd/filament-team-management` is an installable Laravel/Filament **package** (not an app). It provides an opinionated teams setup — Teams, optional Programs (groups of teams), invite-based registration, and Spatie roles/permissions integration — that consuming apps wire into their own Filament panels. There is no panel of its own; the package ships resources/pages that the host app discovers into 2–3 panels (App, Program, Admin).

Requires PHP ^8.4, Filament ^3.0, Livewire ^3.6.4. Built on `spatie/laravel-package-tools ^1.15`, `althinect/filament-spatie-roles-permissions ^2.2`, and `awcodes/shout ^2.0`. Dev tooling: Pest ^4.1, Orchestra Testbench ^10.0, Larastan v3.8, Laravel Pint ^1.0.

## Commands

```bash
composer test                      # run Pest test suite
vendor/bin/pest --filter="name"    # run a single test by name/description
composer test-coverage             # tests with coverage
composer analyse                   # PHPStan (larastan), level 4, scans src/ config/ database/
composer format                    # Laravel Pint (code style)
```

Tests run against an in-memory `testing` sqlite connection via Orchestra Testbench — there is no app to boot. `tests/TestCase.php` manually registers every Filament service provider plus `FilamentTeamManagementServiceProvider`; new test dependencies on Filament sub-packages may need adding there. Factory resolution is remapped to `Database\Factories\{Model}Factory` in `setUp()`.

## Architecture

### Everything is config-indirected — never hardcode model classes, table names, or FKs

Models, table names, and foreign-key column names all resolve through `config/filament-team-management.php`, which reads `FILAMENT_TEAM_MANAGEMENT_*` env vars. Consuming apps are expected to subclass `Team`/`Program`/`User` and repoint the config. So throughout the package:

- Relationships are defined with `config('filament-team-management.models.team')`, `config('...table_names.team_members')`, `config('...column_names.users_foreign_key')` rather than literals. Follow this pattern in any new relationship or query.
- `config('filament-team-management.use_programs')` (bool) gates all Program behaviour. Program migrations, panels, and the `getAllAccessibleTeams()` flattening only apply when true.
- `getModelNameLower()` (from `HasModelNameLowerString` trait) derives snake-case names (e.g. `team_id`, `program_id`) dynamically so custom model names still work.

### Models (`src/Models/`)

- **User** extends Laravel `Authenticatable` and implements Filament's `FilamentUser`, `HasTenants`, `HasDefaultTenant`. This is the heart of multi-tenancy: `getTenants()`, `canAccessTenant()`, and `getDefaultTenant()` branch on the panel's tenant model (Team vs Program). `view all teams` / `view all programs` permissions grant global access. `getAllAccessibleTeams()` unions a user's direct teams with teams reachable via their programs. `latestTeam`/`latestProgram` track the last-used tenant.
- **Team** / **Program** — `users()`/`members()`/`admins()` are the same `team_members` pivot filtered by the `is_admin` pivot column. Both expose `sendInvites()`.
- **Invite** — has a global `onlyUnconfirmed` scope (`is_confirmed = false`), so confirmed invites are invisible to normal queries. Invites double as an audit log: role assignments and direct team adds write confirmed `is_confirmed=true` invite rows for tracing.
- **ModelHasRole** (custom Spatie pivot) — `User::roles()` is aliased to use this pivot so its `created` event fires. On role assignment by a logged-in admin it writes a tracing Invite and emails the user. `auth()->id() === null` is used to distinguish self-registration from admin-driven assignment (suppresses the email/notification during registration).

### Invite + registration flow

The package allows registration **only via invite**. `Filament\Auth\Register` reads a `?token=` URL param, `firstOrFail()`s the matching Invite, prefills+readonly-locks the email, and on submit creates the user then links role/team/program from the invite and marks it confirmed. `sendInvites()` on User/Team/Program either creates a pending Invite + emails `InviteUser`, or — if the email already belongs to a registered user — attaches them directly and emails `UpdateUser`. Password hashing is overridden in the register form (`dehydrateStateUsing` returns plain state) so the plaintext can be forwarded to external systems (e.g. ODK Central) via the `RegisteredWithData` event; hashing happens explicitly in `register()`.

### Filament resources/pages (`src/Filament/`)

Namespaced by intended panel — the host app discovers each into the matching panel:
- `Filament\Admin\*` — site-wide admin (Users, Teams, Programs, Roles, Permissions resources).
- `Filament\Program\*` — program-manager panel (manage program members/invites/teams/projects).
- `Filament\App\*` — end-user panel; `ManageTeam` pages back Filament's `tenantProfile`.
- `Filament\Auth\*` — Login/Register shared by the default (App) panel.

Resources follow Filament 4 layout: `Resources/{Name}/{Name}Resource.php` with `Schemas/` (forms+infolists), `Tables/`, `Pages/`, `RelationManagers/`.

### Middleware (`src/Http/Middleware/`)

- `SetLatestTeamMiddleware` / `SetLatestProgramMiddleware` — persist the current tenant onto the user. **Must be added to the panel's tenant middleware** to populate `latestTeam`/`latestProgram`.
- `AuthenticateThroughDefaultPanel` — non-App panels (Admin, Program) have no login of their own; replace Filament's `Authenticate` in their `authMiddleware` with this so logins route through the default App panel.
- `CheckIfAdmin` / `CheckIfProgramAdmin` — access gates.

### Service provider & migrations

`FilamentTeamManagementServiceProvider` (Spatie `PackageServiceProvider`) splits migrations into two publish tags via `handleMigrations()`: `...-migrations-default` (teams, team_members, invites, users column) and `...-migrations-program` (programs, program_members, program_team). Migrations are `.stub` files in `database/migrations/` published with generated timestamps. The install command publishes the program set only when the user opts into programs.

The `filament-team-management:install` artisan command (`src/Commands/`) is the host-app entry point: prompts for programs, writes `FILAMENT_TEAM_MANAGEMENT_*` env vars (deriving table/FK names from the configured models via `Str` helpers), publishes Spatie + package migrations, optionally migrates and injects package seeders into the app's `DatabaseSeeder` (by brace-matching the `run()` method).

## Conventions

- Follow `.editorconfig` / Pint (`pint.json`) — run `composer format` before committing.
- PHPStan runs at level 4 with model-property checking; some `env()`-outside-config and dynamic-model-property errors are deliberately ignored in `phpstan.neon.dist`.
- README.md and SETUP.md document host-app integration (panel wiring, model extension) — keep them in sync when changing public APIs, install steps, or required panel configuration.
