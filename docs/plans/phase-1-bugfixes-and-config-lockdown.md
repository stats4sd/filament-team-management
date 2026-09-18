# Phase 1 — Fix the known bugs in place; lock down config with tests

**Date**: 2026-07-06
**Status**: implementation complete; PRs #70–#75 merged into `dev` on 2026-09-11. Updated 2026-09-17 against `dev` at `e5e98df`. Release is carried by [Phase 1b A8](phase-1b-pre-release-and-review-remainder.md#a8-release--pending), targeting `v5.0.0`; the original patch-release requirement is superseded.
**Branch base**: `dev`
**Required reading already done**: [2026-07-06-package-review.md](../code-reviews/2026-07-06-package-review.md) (sections 1–4), [2026-07-06-architecture-package-vs-app.md](../code-reviews/2026-07-06-architecture-package-vs-app.md).

## Goal

Current architecture, all original section-4 findings closed or explicitly deferred; the config surface locked down by tests so the 4.1/4.2/4.3 class can never regress. Nothing here depends on the packaging decision — all of it survives into the future core.

## Completion record (updated 2026-09-17)

| Original group | Merged PR | Outcome |
|---|---|---|
| PR 1 — config lockdown | [#70](https://github.com/stats4sd/filament-team-management/pull/70) | Config/env/pivot keys aligned; installer writes `ROLE_MODEL`; config and parity tests added. |
| PR 2 — invite models and labels | [#71](https://github.com/stats4sd/filament-team-management/pull/71) | Configured model/morph handling, missing-role guard, and `names.*` shipped. |
| PR 3 — UI wiring | [#72](https://github.com/stats4sd/filament-team-management/pull/72) | Creator admin flag, edit action, invite columns/actions and inverse relationships fixed. |
| PR 4 — auth flow | [#73](https://github.com/stats4sd/filament-team-management/pull/73) | Unsigned URLs, registration guards/password message and guest middleware handling fixed. |
| PR 5 — seeder permissions | [#74](https://github.com/stats4sd/filament-team-management/pull/74) | Idempotent role/permission wiring and seeder variable fix shipped. |
| PR 6 — docs | [#75](https://github.com/stats4sd/filament-team-management/pull/75) | Permissions, tenant middleware and stale registration notes corrected. |

The stack landed through merge `58dab86`. Phase 1b A1 ([#78](https://github.com/stats4sd/filament-team-management/pull/78)) subsequently fixed the members tab hiding admins. A2–A7 ([#79](https://github.com/stats4sd/filament-team-management/pull/79)–[#84](https://github.com/stats4sd/filament-team-management/pull/84)) are now merged through `e5e98df`, including upgrade/release documentation, dead-code removal, the direct Spatie dependency, stable program-column migrations and the Program-panel Teams rename.

Verification on 2026-09-17: `composer test` **119 passed, 320 assertions**; `composer analyse` **no errors**; `vendor/bin/pint --test` **passed**. Local and GitHub `dev` both resolve to `e5e98df`. Latest published GitHub release remains `v4.0.7`; no 5.0 release is claimed here. These checks establish the merged baseline, not closure of the remaining Track B findings.

> **Current scope (2026-09-18):** Phase 1 implementation is complete. Replacement Track B B1–B8 is implemented at `b40c711`; its [log](../change-logs/phase-1b-track-b-membership-only.md) supersedes role/admin behavior below. Historical verification and merge statements above are dated evidence, not refreshed remote claims. The constraints, decisions and original PR sequence below describe the historical implementation only. [Phase 2 closeout and full review](phase-2-bounded-hardening-closeout.md) are now complete; separate Phase 3 planning follows. A8/release remains separate.

## Historical starting baseline (before PRs #70–#75, ahead of the review's `2dc82ac`)

The review was written against `2dc82ac`. The one `src/` change since is [Register.php](../../src/Filament/Auth/Register.php) (redirect-to-login when the register page is hit without a valid invite token), and `tests/Feature/RegistrationTest.php` has been updated to match. Every section-4 finding was re-verified against live code and **all remain present**, except:

- Finding **2.7 is closed**: the redirect-to-login behaviour is now real and tested, matching the README claim. Only the doc/CLAUDE.md cleanup around it (4.15b, stale `firstOrFail()` note) remains.
- **Suite green**: tests, PHPStan (level 4), and Pint all pass at the starting point.

## Constraints (from the meta-plan ground rules)

- Lands as a PR sequence on `dev`; the package stays releasable after every PR.
- `composer test`, `composer analyse`, `composer format` green at every merge.
- README/SETUP updated in the same PR as any public-API or install-step change.
- Every regression test must be written **red on pre-fix code, green after** — demonstrate this in the PR.
- Where a Phase 1 fix would be thrown away by Phase 2, prefer the minimal correct fix and leave a `// Phase 2:` marker rather than over-building.

## Decisions (confirmed 2026-07-06)

The genuine judgement calls in Phase 1, all confirmed:

1. **Signed invite URL (4.10a) — DROP.** The invite token is already an unguessable secret, Livewire strips the `signature` query param (so enforcing would break the flow), and the register route has no `signed` middleware. Replace `URL::signedRoute(...)` in [InviteUser.php](../../src/Mail/InviteUser.php) with plain `route(...)`, and update `tests/Unit/MailableTest.php` (which currently asserts `signature=` is present).
2. **Admin Invites RelationManager Create/Edit (4.8) — REMOVE the actions.** Invites are system-generated (via `sendInvites()` and role-assignment tracing) and double as an audit log; a hand-authored invite form belongs to the Phase 3 consolidated invite UX, not here. Remove `CreateAction`/`EditAction`; keep `DeleteAction`.
3. **Program "admin" concept (4.5, program half) — REMOVE the dead form, do NOT add `is_admin`.** `program_members` has no `is_admin` column and `is_admin` *enforcement* is explicitly Phase 2. So the fix for the Programs `UsersRelationManager` is to delete its dead name-editing `form()`, not to mirror the Teams `EditAction`. Leave a `// Phase 2:` marker where an admin concept would land.
4. **`names.*` defaults — lowercase singular** (`'team' => 'team'`, `'program' => 'program'`) so the existing sentence copy reads naturally ("…invite to this team."). Hosts override these literals in published config; `names.*` is not env-backed.
5. **`table_names.invites` (review 3.3) — DEFER to Phase 2.** It's a consistency gap, not a section-4 bug, and adding it touches the model + migration. Note it with a `// Phase 2:` marker; Phase 1's config-completeness test covers only keys that exist.
6. **Config env-var naming direction (4.2) — align CONFIG to the installer's singular `USER_*`**, matching the already-singular `USER_MODEL` the installer/config agree on. Fewer churned strings than renaming the installer, and consistent with Laravel's own `USER_MODEL` convention.

## Original PR sequence — all six groups merged

The specifications below preserve the original fix/test scope; pre-fix descriptions and line references are historical, not outstanding instructions. The completion record above and Phase 1b govern current work.

Ordered so the suite is green after every merge and the highest-value/lowest-risk config lockdown lands first (review priority #1). Each PR: fix + red-green regression test(s) + `composer format` + green analyse.

### PR 1 — Config/installer key alignment + config lockdown (review priority #1: 4.1, 4.2, 4.3)

Fixes:
- **4.1** — [config:33](../../config/filament-team-management.php#L33): `'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', ...)` → `env('FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY', 'program_id')` (the key the installer already writes at [InstallFilamentTeamManagement.php:195](../../src/Commands/InstallFilamentTeamManagement.php#L195)).
- **4.2** — align config to installer's singular names: [config:21](../../config/filament-team-management.php#L21) `USERS_TABLE`→`USER_TABLE`, [config:31](../../config/filament-team-management.php#L31) `USERS_FOREIGN_KEY`→`USER_FOREIGN_KEY`. Also resolve the dead `$roleModel` at [InstallFilamentTeamManagement.php:175](../../src/Commands/InstallFilamentTeamManagement.php#L175): either write `FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL` or delete the unused line (recommend: write it, since config reads it at [config:16](../../config/filament-team-management.php#L16)).
- **4.3** — [Team.php:168](../../src/Models/Team.php#L168) `team_programs`→`program_team`; [Program.php:144](../../src/Models/Program.php#L144) `program_teams`→`program_team`.

Tests (the second-half mandate; both must fail on pre-fix code):
- **Extend `tests/Unit/ConfigIndirectionTest.php` to exercise EVERY config key.** Currently it covers only `Team::users()` / `team_members`. Add a case per key, overriding it and asserting against the relationship or label that consumes it:
  - `models.user|team|program|role` — related-model class + role morph type on an invite/attach.
  - `table_names.team_members` (existing), `program_members` (Program pivot), `program_team` (**catches 4.3** via `Team::programs()`/`Program::teams()`), plus `users`/`teams`/`programs` where used as relation table or label.
  - `column_names.teams_foreign_key`, `users_foreign_key` (pivot FKs), `programs_foreign_key` (**catches 4.1** via program relations / `Invite::program()`).
  - `names.team|program` — deferred to PR 2 where the block is added; add those assertions there.
- **Add `tests/Feature/Install/` installer↔config parity test (catches the whole 4.1/4.2 class permanently).** Assert the set of env keys the installer writes equals the set the config file reads: run/inspect `updateEnv` output for the written keys, parse `env(...)` calls from the config file for the read keys, assert equality (both directions). This is the durable guard the review asked for.

Docs: none required (internal keys), but note the env-var rename in the PR description for any host that hand-set `USERS_*`.

### PR 2 — Invite-logic morph/role fixes + `names` config block (4.6, 4.7, 2.2)

Fixes (minimal, in place — the `InviteService` extraction is explicitly Phase 2; leave `// Phase 2:` markers where the triplication will be collapsed):
- **4.6** — resolve user/role via configured classes, not the package's own `User`/`Role`:
  - [User.php:90](../../src/Models/User.php#L90), [Team.php:50](../../src/Models/Team.php#L50), [Program.php:52](../../src/Models/Program.php#L52): `User::where('email', ...)` → `config('filament-team-management.models.user')::where(...)`.
  - [User.php:112](../../src/Models/User.php#L112): `Role::find(...)` → `config('filament-team-management.models.role')::find(...)`.
  - [ModelHasRole.php:29,32](https://github.com/stats4sd/filament-team-management/blob/e5e98dfac31fc1ecb4ea15c2220a41c224981e69/src/Models/ModelHasRole.php#L29): `User::find` / `Role::find` → configured classes. Also guard the morph discriminator: only treat rows whose `model_type` is the configured user model (the review's 3.7 note) — minimal guard now, full event extraction is Phase 2, so mark it.
- **4.7** — [Program.php:43](../../src/Models/Program.php#L43): resolve `Role` via config and null-guard the `Program Admin` lookup so a missing role surfaces a Notification/early-return instead of a fatal at [Program.php:61,89](../../src/Models/Program.php#L61). (Role-name-from-config is Phase 2/3; guard the crash now.)
- **2.2** — add a `names` block to [config](../../config/filament-team-management.php) with defaults `['team' => 'team', 'program' => 'program']`. Fixes the blank-gap copy at [TeamMembersTable.php:37](../../src/Filament/App/Pages/ManageTeam/TeamMembersTable.php#L37), [ProgramMembersTable.php:37](../../src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php#L37), [ViewProgram.php:23](../../src/Filament/Admin/Resources/Programs/Pages/ViewProgram.php#L23). Also fix the copy/paste bug at `ProgramMembersTable.php:37` which reads `names.team` but should read `names.program`. Add the `names.*` cases to `ConfigIndirectionTest` now that the keys exist.

Tests (red-green):
- Invite-existing-user asserts `model_has_roles.model_type` equals the **configured** user class (the review's named regression for 4.6). Use the `CustomUser` fixture / a repointed `models.user`.
- `Program::sendInvites()` with no `Program Admin` role asserts a graceful outcome, not an exception.
- Assert the three `names.*` call sites render the configured word (no blank).

### PR 3 — Broken UI wiring (4.4, 4.5, 4.8, 4.13)

Fixes:
- **4.4** — `TextColumn::make('project.name')` → `'program.name'` in [TeamInvitesTable.php:24](../../src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L24) and [ProgramInvitesTable.php:25](../../src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php#L25).
- **4.5** — add `EditAction::make()` to the Teams `UsersRelationManager` `recordActions` ([UsersRelationManager.php:96-100](../../src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L96)) so the existing `is_admin` pivot form is reachable; make [RegisterTeam.php:33](../../src/Filament/App/Pages/RegisterTeam.php#L33) attach the creator with `['is_admin' => true]`. Per Decision 3, **remove** the dead name-editing `form()` in the Programs `UsersRelationManager` rather than adding `is_admin` there. (Exposing/being-able-to-set `is_admin` is in scope; *enforcing* it is Phase 2 — mark it.)
- **4.8** — per Decision 2, remove `CreateAction`/`EditAction` from both admin Invites relation managers ([Teams .../InvitesRelationManager.php:44-48](../../src/Filament/Admin/Resources/Teams/RelationManagers/InvitesRelationManager.php#L44), [Programs .../InvitesRelationManager.php:42-46](../../src/Filament/Admin/Resources/Programs/RelationManagers/InvitesRelationManager.php#L42)); keep `DeleteAction`.
- **4.13** — fix `inverseRelationship` names: [ProgramMembersTable.php:23](../../src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php#L23) `teams`→`programs`; [TeamInvitesTable.php:19](../../src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L19) `teams`→`team`; [ProgramInvitesTable.php:20](../../src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php#L20) `teams`→`program`.

Tests (red-green): render/interaction tests via the existing smoke/Livewire harness — the creator lands as `is_admin=true` after `RegisterTeam`; the `is_admin` `EditAction` opens and toggles the pivot; the invite tables' `program.name` column resolves; the invite relation managers expose no Create action. These live alongside `tests/Smoke/PageRenderTest.php` / a new feature test.

Docs: none (behaviour matches existing README intent). Note the creator-is-admin change in the PR body as a documented behaviour change (it *is* one — record it).

### PR 4 — Auth-flow defects (4.10, 4.11, 4.12, 4.14)

Fixes in [Register.php](../../src/Filament/Auth/Register.php) unless noted:
- **4.10a** — per Decision 1, drop `URL::signedRoute` for `route(...)` in [InviteUser.php](../../src/Mail/InviteUser.php); update `tests/Unit/MailableTest.php`.
- **4.10b** — add Filament's "already authenticated → redirect away" guard at the top of the overridden `mount()` (`if (Filament::auth()->check()) { $this->redirect(Filament::getUrl()); return; }`).
- **4.11** — delete the no-op `app()->bind(SendEmailVerificationNotification::class)` at [Register.php:103-105](../../src/Filament/Auth/Register.php#L103). (User model doesn't implement `MustVerifyEmail`; the listener is already inert.)
- **4.12** — replace `->rule('min:10', '…message…')` at [Register.php:135-138](../../src/Filament/Auth/Register.php#L135) with `->minLength(10)` (or `->rule('min:10')->validationMessages([...])`) so the message actually applies.
- **4.14** — null-safe [CheckIfAdmin.php:19](https://github.com/stats4sd/filament-team-management/blob/e5e98dfac31fc1ecb4ea15c2220a41c224981e69/src/Http/Middleware/CheckIfAdmin.php#L19) and [CheckIfProgramAdmin.php:19](https://github.com/stats4sd/filament-team-management/blob/e5e98dfac31fc1ecb4ea15c2220a41c224981e69/src/Http/Middleware/CheckIfProgramAdmin.php#L19): `abort_unless(auth()->check() && auth()->user()->can(...), 403)` (or `Gate::denies(...)`, which tolerates a null user).

Tests (red-green): unauthenticated request through each middleware returns 403 (not 500); an authenticated user hitting `/register?token=` is redirected away; the invite mail link is a plain (unsigned) route; password `min:10` still enforced. These fill the gaps `MiddlewareTest`/`RegistrationTest` currently leave (no unauthenticated-middleware case exists today).

### PR 5 — Seeder/permission wiring (4.9)

Fixes in [TestUserSeeder.php](../../database/seeders/TestUserSeeder.php):
- Create all four permissions (`access admin panel`, `access program admin panel`, `view all teams`, `view all programs`); gate the program pair on `config('filament-team-management.use_programs')`.
- Attach them to roles: `Super Admin` gets all four (or all applicable); `Program Admin` gets the program pair. So a freshly seeded admin passes `CheckIfAdmin` without the host needing an undocumented `Gate::before`.
- Fix the latent `$user`/`$admin` `method_exists` mismatch at [TestUserSeeder.php:41-45](../../database/seeders/TestUserSeeder.php#L41) noted during verification.

Tests (red-green): seed, then assert the seeded admin `->can('access admin panel')` and passes `CheckIfAdmin`; program admin passes `CheckIfProgramAdmin`. (These are example seeders, but the package's own tests seed through them, so this is testable.)

### PR 6 — Docs corrections (4.15) + stale-note cleanup

Fixes in [README.md](../../README.md):
- **4.15a** — replace `can('viewAdminPanel')` in the three nav examples: lines 110 & 160 → `can('access admin panel')`; line 197 (link to the *program* panel) → `can('access program admin panel')`. Add a canonical list of the four permission strings the package expects (its absence is why `viewAdminPanel` was invented).
- **4.15b** — promote the `SetLatestTeamMiddleware` requirement out of the trailing TODO into the App-panel config block, and add the `SetLatestProgramMiddleware` equivalent to the Program-panel block; document that they belong in `tenantMiddleware`.
- Fix the noted typos in SETUP.md (".env.exmaple") and README ("add _replace_").
- Update the stale [CLAUDE.md](../../CLAUDE.md) sentence describing `Register::mount()` as `firstOrFail()` — it now `->first()`s and redirects (post-`d786d45`).

## Original deferrals — current ownership

The former Phase 2 deferrals are closed or superseded by replacement Track B B1–B8, implemented at `b40c711`:

- Invitation orchestration, feedback separation, membership events and acceptance: **B2/B3 implemented**; role-assignment heuristics removed.
- `is_admin` enforcement and shipped privilege rules: **superseded by B1 host-owned policies**, not future package-admin work.
- Invitation lifecycle and shared presentation: **B2/B4 implemented**.
- FK indirection, latest-tenant guards and host-owned tenant access: **B5 implemented**.
- Configured invites table, installer and display conversion: **B8 implemented**.
- Dead code/self-relations: **A4 complete**; program schema: **A6 followed by the fresh membership schema**; A5's direct permission dependency: **intentionally reversed by B1**.

The only current closeout queue is [Phase 2](phase-2-bounded-hardening-closeout.md). B9 and existing-account pending acceptance remain deferred. Historical criteria below preserve evidence gaps and release tracking; they do not reopen removed privilege APIs.

## Exit criteria (updated 2026-09-17)

- [x] All original section-4 findings closed by #70–#75; no section-4 deferral remains.
- [ ] Every regression test demonstrated red on pre-fix code, green after: #71–#74 describe red/green verification in their PR bodies; #70 describes regression coverage but does not demonstrate the historical red run. Current green tests do not prove this universal historical criterion; retained as an evidence gap, not a newly reproduced defect.
- [x] `ConfigIndirectionTest` exercises each existing `models.*`, `table_names.*`, `column_names.*`, and `names.*` key. This is key coverage, not coverage of every consumer; the `invites()` FK consumers were subsequently covered by replacement B5.
- [x] Installer↔config parity test present and green.
- [x] Historical September 17 merged baseline: 119 tests / 320 assertions, PHPStan clean, Pint check clean.
- [x] README/SETUP/CLAUDE.md corrected; four permission strings documented. Phase 1b A2/A3 add the upgrade guide, changelog and env reference.
- [ ] **Release published**: superseded patch boundary now belongs to Phase 1b A8 (`v5.0.0`); `dev` → `main`, tag and GitHub release remain pending.

## Historical decisions made (confirmed 2026-07-06; closure updated 2026-09-17)

- Signed invite URL: **DROP** — plain `route()`, token is the secret.
- Admin Invites Create/Edit actions: **REMOVED** (keep Delete) — hand-authored invites are Phase 3.
- Program admin concept in Phase 1: **dead form removed, no `is_admin`** — enforcement is Phase 2.
- `names.*` defaults: **lowercase singular** (`team`, `program`).
- Config env-var naming direction (4.2): **config → singular `USER_*`**.
- `$roleModel` in installer: **written** as `FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL` in #70.
- Section-4 deferrals: **none**. Architectural and remaining review work stays in Phase 1b Track B; the original minimal-fix boundary remains intact.
- Release tag: **`v5.0.0`, pending Phase 1b A8**, reflecting the Laravel 13 / Filament 5 / Livewire 4 major upgrade and merged breaking changes. This supersedes the meta-plan’s original Phase 1 patch assumption.
