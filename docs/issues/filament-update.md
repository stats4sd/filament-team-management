# Investigation: test-suite foundation blockers

## The headline blocker (not in the test plan): installed Filament is v3, source targets v4

`composer.json` requires `filament/filament: ^4.2` and the package source is written for **Filament v4**, but `composer.lock` and `vendor/` actually have **Filament v3.3.5** installed. The lock was never updated after the manifest was bumped to `^4.2`.

Evidence:

- `vendor/composer/installed.json` → all `filament/*` at `v3.3.5`; `composer.lock` pins `filament/filament v3.3.5`.
- Installed parent signatures are v3: `Filament\Resources\Resource::form(Filament\Forms\Form $form): Filament\Forms\Form` and `Filament\Pages\Page::$navigationIcon` typed `?string`.
- Source uses v4 APIs that don't exist in v3: `Filament\Schemas\Schema`, `Filament\Schemas\Components\*`, `Filament\Auth\Pages\Login`, `Filament\Auth\Pages\Register`, `Filament\Auth\Http\Responses\RegistrationResponse`, and `string | BackedEnum | null` navigation-icon types.

## What this breaks

Every class under `src/Filament/` fails to **class-load** under the installed v3. Confirmed fatals (one subprocess per class, 44 classes scanned):

| Class | Failure |
|---|---|
| `App\Pages\ManageTeam\ManageTeam` | `getHeading()` return type widened to `…|null`, incompatible with v3 `BasePage::getHeading()`; also `$navigationIcon` union vs `?string` |
| `App\Pages\RegisterTeam` | `form(Schema): Schema` vs v3 `RegisterTenant::form(Form): Form`; `Filament\Schemas\Schema` not available |
| `Program\Pages\ManageProgram\ManageProgram` | `form(Schema)` vs `EditTenantProfile::form(Form)` |
| `Program\Pages\RegisterProgram` | `form(Schema)` vs `RegisterTenant::form(Form)` |
| `Auth\Login`, `Auth\Register`, `Auth\RegisterResponse` | parent classes `Filament\Auth\Pages\*` / `Filament\Auth\Http\Responses\*` don't exist in v3 |
| `Admin\Resources\{Users,Teams,Programs}\*Resource` | `form(Schema): Schema` vs `Resource::form(Form): Form` |
| `Admin\Resources\Teams\RelationManagers\UsersRelationManager`, `Programs\RelationManagers\{Users,Teams}RelationManager` | `form(Schema)` vs `RelationManager::form(Form)` |

The package **models** (`User`, `Team`, `Program`, `Invite`, `ProgramInvite`) and their traits load fine under v3 — only the Filament UI layer is broken.

The existing `tests/ArchTest.php` (`expect([...])->each->not->toBeUsed()`) forces every `src/` class to load, so **the suite already fatals today, before any harness work** — `composer test` is currently red at baseline.

## The two blockers the test plan did identify — both confirmed accurate

1. **Migrations never run in the harness.** `tests/TestCase.php:51-59` sets the `testing` connection but the migration include is commented out; no `loadMigrationsFrom`/`RefreshDatabase`. Migrations ship as `.stub` (not `.php`), so Testbench won't auto-discover them. Additional wrinkles the harness must handle:
   - The package ships **no `users`-table migration** (only `9_add_column_to_users_table` adds `latest_team_id`/`latest_program_id`). The harness must create `users` first (Testbench's stock users migration works).
   - **Spatie permission migrations are also `.stub`** (`vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub`) and must run before `invites` (FK `role_id` → `roles`).
   - **`Spatie\Permission\PermissionServiceProvider` is not in `TestCase::getPackageProviders()`** — must be added, or `config('permission.*')` is empty and the permission migration throws.
   - **Migration order is `use_programs`-dependent.** When programs are on, `3_create_invites_table` adds a `program_id` FK and `9_add_column_to_users_table` adds `latest_program_id` — both `->constrained()` the `programs` table, so `5_create_programs_table` must run *before* invites and the users-column migration. A `withPrograms()` helper can't just append program migrations to an already-migrated default DB; it has to rebuild.

2. **No factories.** `database/factories/ModelFactory.php` is an empty commented stub. Need `UserFactory`, `TeamFactory`, `ProgramFactory`, `InviteFactory`, `ProgramInviteFactory`. `TestCase::setUp()` already remaps factory resolution to `…\Database\Factories\{Model}Factory`.

## Config bugs noticed in passing (will bite the Phase 1 "config indirection" tests)

These are in source, not the harness — flagged, not fixed:

- `config/filament-team-management.php:33` — `'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', 'program_id')` reads the **model** env var, not a foreign-key var. Copy-paste error.
- `Team::programs()` (`src/Models/Team.php:166`) uses `table_names.team_programs` and `Program::teams()` (`src/Models/Program.php:142`) uses `table_names.program_teams` — **neither key exists** in config (the real key is `program_team`, and the migration creates `program_team`). Both resolve to `null`. The Phase 1 "relationships build with configured table/FK names" test would catch this.

## Bottom line on the plan

Phase 0 as written assumes the package boots and just needs a harness. It doesn't boot — the v3/v4 dependency mismatch is a prerequisite to Phase 0 itself. Sequencing implied by the findings:

0. **Resolve the Filament version** (`composer update` to v4 — and then expect to fix any remaining v3→v4 source drift the update surfaces). Without this, the existing `ArchTest` and all of Phases 2–4 stay red.
1. Then the plan's Phase 0 (harness migrations + providers + factories + helpers + test panel).
2. The two model-only Phase 1 tests (`HasModelNameLowerString`, config indirection) are the *only* part achievable against the current v3 vendor — but only if `ArchTest` is scoped away from the uncompilable `src/Filament/` classes.
