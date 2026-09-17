# Test suite plan — filament-team-management

## Status

- **Phase 0 — Foundation: ✅ done.** Harness migrations, roles/permissions seed, test panel, factories, and Pest helpers all landed and verified. See `local-docs/change-summaries/test-harness-phase-0.md`.
- **Phase 1 — Unit tests: ✅ done (6 of 6).** All six unit suites written and green.
- **Phase 2 — Smoke tests: ✅ done.** Panel boot, migrations, resource resolution, and page render (HTTP) covered in both modes.
- **Phase 3 — Feature tests: ✅ done.** Invite flow, registration, role-assignment tracing, multi-tenancy, middleware, and resource CRUD (incl. program-mode tenancy + invites).
- **Phase 4 — Install command: ✅ done.** Migration-set publishing (per mode) and DatabaseSeeder brace-matching injection.

Whole suite green (70 passing), Pint + PHPStan clean.

Carry-over notes / harness facts established while building phases 1–4:

- Runtime `withPrograms()` creates the program tables but cannot add the migration-time columns (`invites.program_id`, `users.latest_program_id`) or register the Program panel at boot. Program-mode tests that need those live under `tests/ProgramMode/` and are bound to `ProgramTestCase` (`$usePrograms = true`).
- The global `uses(TestCase::class)->in(...)` bind cannot overlap a per-file `uses(ProgramTestCase::class)`, so binds are scoped to non-overlapping directories: `TestCase` → `Unit`, `Smoke`, `Feature`; `ProgramTestCase` → `ProgramMode`. Per-directory `group()`s remain dropped — use `--filter`/path selection.
- **Load-bearing harness fix:** Filament's `SupportServiceProvider` rebinds Livewire's `DataStore` with a non-shared `bind()` (to `DataStoreOverride`). In this manually-ordered harness that wins over Livewire's shared `instance()`, so every `app(DataStore)` returned a fresh store and Livewire's per-component state (validation error bag) never persisted — every component render threw on a null error bag. `TestCase::getEnvironmentSetUp()` re-binds it as a shared singleton (after all providers register). Without this, no Livewire/Filament render test can pass.
- `Filament\Schemas\SchemasServiceProvider` must be registered (Filament 5 split schemas into their own package) or auth/schema pages fail with "No hint path defined for [filament-schemas]".
- The test panel registers all three panels in `register()` (the base `PanelProvider` registers only one); the Program panel is gated on `TestPanelProvider::$usePrograms`, a static the test case sets before app creation (config isn't readable yet at register time).
- A `Gate::before` granting Super Admins everything is registered in the harness (the package ships no policies, host apps add this), so authorization-gated Admin actions are reachable.
- `livewire()` helper in `Pest.php` wraps `Livewire::test()` (pest-plugin-livewire isn't installed).
- `ProgramInvite` (table `program_invites`) has no migration and is unused — `Program::sendInvites()` writes regular `Invite` rows — so it's untested.
- Non-program-mode bulk-delete on the Users table eager-loads the (invisible) `programs` relation, which doesn't exist without programs — a Filament bulk-fetch quirk that ignores column visibility. The Users bulk-delete test therefore lives in `tests/ProgramMode/Feature/`.

## Foundational findings

Two blockers, both load-bearing:

1. **Migrations never run in the harness.** `tests/TestCase.php` sets the `testing` connection but the migration include is commented out, and there is no `loadMigrationsFrom()` or `RefreshDatabase`. The package ships `.stub` migrations (not `.php`), so Testbench will not auto-discover them. No DB-backed test can pass until the harness loads the package migrations + Spatie permission migrations and seeds roles/permissions.
2. **No factories exist** — only an empty `database/factories/ModelFactory.php`. Every model (User, Team, Program, Invite, ProgramInvite) needs a factory before relationship/flow tests are writable.

Context:

- The "crud panel test" commits in history are not present on any current branch (`dev`, `tests-submodule`, `main` all carry only `ExampleTest` + `ArchTest`) — they were lost/reverted, so we start from effectively zero.
- The `tests-submodule` branch has `src/Testing/TestsFilamentTeamManagement.php`, a Filament testing mixin worth reviewing/reusing.
- Everything is config-indirected and `use_programs` gates half the package, so the suite must run against both modes (programs on / off).
- The package ships no panel of its own, but most behaviour (tenancy, resources, pages) only exists inside a panel, so tests must register a throwaway test panel.

## Phase 0 — Foundation ✅ done

| Item | Status | Notes |
|---|---|---|
| Harness migrations | ✅ | `defineDatabaseMigrations()` runs Laravel defaults → Spatie permission stub → package `.stub` migrations in dependency order; in-memory sqlite is recreated per test so no `RefreshDatabase` needed. Program migrations run when `$usePrograms = true` (or via the runtime helper, with the column caveat above). Also configures the `web` auth guard so Spatie role assignment resolves a guard. |
| Roles/permissions seed | ✅ | `seedRolesAndPermissions()` seeds `Super Admin`/`Program Admin` and the four permissions. |
| Test panel | ✅ | `tests/Fixtures/TestPanelProvider.php` registers App (Team tenant), Admin, and Program (Program tenant, gated on `use_programs`) panels, discovering package resources/pages. Registered globally, so panel boot is exercised every test. |
| Factories | ✅ | `UserFactory`, `TeamFactory`, `ProgramFactory`, `InviteFactory`, `ProgramInviteFactory`; states `unverified()`, `confirmed()`, `withToken()`, `forTeam()`. Models gained `HasFactory`. All FK/table refs config-resolved. |
| Pest helpers | ✅ | `actingAsAdmin()`, `actingAsProgramAdmin()`, `withPrograms()`, `signedInviteUrl()`. Directory `group()`s dropped (conflict with the global `uses()->in()` bind). |

## Phase 1 — Unit tests (`tests/Unit/`), no Filament, model logic in isolation

- ✅ **HasModelNameLowerString** — `getModelNameLower()` returns snake_case for default and custom model names. (`HasModelNameLowerStringTest`)
- ✅ **Config indirection** — relationships build with configured table/FK names (assert SQL or pivot table on `BelongsToMany`); custom model class via config is honoured. (`ConfigIndirectionTest`)
- ✅ **Invite** — `onlyUnconfirmed` global scope hides confirmed rows; `confirm()` flips the flag. (`InviteTest`)
- ✅ **Team/Program/User relationship wiring** — `admins()`/`members()` filter the `is_admin` pivot; `users()` vs `members()` aliasing on Program. (`RelationshipWiringTest`)
- ✅ **User authorization predicates** — `isAdmin()`, `belongsToTeam()`, `belongsToProgram()`. (`UserAuthorizationTest`)
- ✅ **Mailables** — `InviteUser`/`UpdateUser` subject + signed registration URL carries the token. (`MailableTest`)

## Phase 2 — Smoke tests (`tests/Smoke/`, program-mode in `tests/ProgramMode/Smoke/`), "does it boot" ✅

- ✅ Panels boot in both modes (`PanelBootTest`, `ProgramMode/Smoke/PanelBootTest`); App tenant is the configured Team model.
- ✅ Migrations create the expected tables/columns — default set, and program set + columns when enabled (`MigrationsTest`, `ProgramMode/Smoke/MigrationsTest`).
- ✅ Each Admin resource resolves its configured model; Program nav gated on the flag (`ResourceResolutionTest`).
- ✅ Admin list pages render `200` via real HTTP request (`PageRenderTest`) — chosen over `Livewire::test` so the session/error-bag middleware runs.

## Phase 3 — Feature tests (`tests/Feature/`, program-mode in `tests/ProgramMode/Feature/`) ✅

- ✅ **Invite flow** — `Team::sendInvites()` / `User::sendInvites()` (`InviteFlowTest`) and `Program::sendInvites()` (`ProgramMode/Feature/ProgramInviteFlowTest`): pending Invite + `InviteUser` for unknown email; direct attach + `UpdateUser` for a registered email; no duplicate membership; empty entries skipped; Program path assigns `Program Admin`. `Mail::fake()`.
- ✅ **Registration via token** — `RegistrationTest`: `?token=` prefills+locks email; submit creates user, links role/team, confirms invite, fires `Registered` + `RegisteredWithData`; bad token → `ModelNotFoundException`; 10-char password enforced.
- ✅ **Role-assignment tracing** — `RoleAssignmentTracingTest`: admin-driven assign writes a confirmed tracing Invite + `UpdateUser`; self-registration (no `auth()->id()`) stays silent.
- ✅ **Multi-tenancy** — `MultiTenancyTest` (Team) + `ProgramMode/Feature/ProgramTenancyTest` (Program): `getTenants()`/`canAccessTenant()`/`getDefaultTenant()` branch per tenant; `view all teams`/`view all programs` grant global access; `getAllAccessibleTeams()` unions direct + program-reachable teams.
- ✅ **Middleware** — `MiddlewareTest`: `CheckIfAdmin`/`CheckIfProgramAdmin` 403/pass; `SetLatestTeamMiddleware` persists `latest_team_id` and no-ops on null tenant; `AuthenticateThroughDefaultPanel` redirects to the App-panel login.
- ✅ **Filament resource CRUD (Livewire)** — `ResourceCrudTest` + `ProgramMode/Feature/ResourceCrudTest`: list renders, edit, invite header action (repeater), attach/detach relation-manager actions, bulk delete (program mode — see eager-load note above).

## Phase 4 — Install command (`tests/Feature/Install/`) ✅

- ✅ `InstallCommandTest`: drives the prompts via `->expectsConfirmation()`, asserts the correct migration set is published (program set only when opted in) and that `DatabaseSeeder` brace-matching injection appends both seeders inside `run()` without corrupting the file (brace balance + `token_get_all` parse check). Snapshots/restores every skeleton path it touches. Env-var assertion omitted: the command writes to the relative `app()->environmentFile()` (cwd = package root under Testbench), not `base_path('.env')`.

## Sequencing / definition of done

1. ✅ Phase 0 lands first as one PR — prerequisite and highest-risk piece (harness + factories + test panel).
2. ✅ Phases 1–2 (cheap, fast, catch boot/config regressions). Still TODO: wire into CI `run-tests.yml`.
3. ✅ Phase 3 — the bulk of the value; split by area (invites, registration, tenancy, middleware, resources).
4. ✅ Phase 4 — install command.
5. Program gating is exercised continuously via the `tests/ProgramMode/` subtree (`ProgramTestCase`, `$usePrograms = true`) running alongside the default-mode suite, rather than a separate CI matrix axis.
