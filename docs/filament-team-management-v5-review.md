# filament-team-management: v5.0.0 Review Starting Point

Drafted 2026-09-10 from the AE Policy Tracking Tool side. Intended to be copied into the `stats4sd/filament-team-management` repo (its `docs/` is gitignored, so it can live there untracked alongside the original audit doc) and used to drive the re-review of the PR stack before tagging v5.0.0. Facts below were gathered read-only via the GitHub API on 2026-09-10; nothing was commented, reviewed or merged.

## 1. Why this matters now

The AE Policy Tracking Tool is moving to Laravel 13 / Filament 5 / Livewire 4 (see [upgrade-plan-laravel13-filament5-livewire4.md](upgrade-plan-laravel13-filament5-livewire4.md)). Filament 5 requires a team-management release that allows `filament/filament ^5`. Latest tag is **v4.0.7** (Filament 4). The Filament 5 port lives on `dev` (commit `f4d754c5`, 2026-06-23) plus a six-PR stack that has never been reviewed or, for five of the six, run through the test suite in CI.

Interim option if the review takes time: consume `dev-dev as 5.0.0` from the app. Not recommended past a spike, because the stack changes consumer-visible behaviour and the app would then be pinned to a moving branch.

## 2. Repo state snapshot

| Fact | Value |
|---|---|
| Default branch | `dev` |
| Latest tag | v4.0.7 (2025-12-08), points at a `main`-only pint commit |
| `main...dev` | dev ahead 27 / behind 19, merge base `340e76ae` |
| Content on `main` missing from `dev` | Only `CHANGELOG.md` (+52 lines, the 4.0 to 4.0.7 entries written by the release workflow). All 19 commits are merges of dev into main, changelog-bot commits, or a whitespace-only pint commit whose result dev already has. |
| `dev` composer require | `php ^8.4`, `filament/filament ^5.2`, `livewire/livewire ^4.0`, `althinect/filament-spatie-roles-permissions ^3.0`, `spatie/laravel-package-tools ^1.15` |
| v4.0.7 → dev, `src/` | 14 files, +54/−26: `awcodes/shout` dropped for `Filament\Schemas\Components\Callout` (5 files), `HasFactory` added to models, `Register` redirects to login when token missing (#69), small table/RM tweaks |
| v4.0.7 → dev, migrations | **No changes** |
| v4.0.7 → dev, config | Import refactor only; **no key changes** (the stack adds some, see §4) |
| Test suite on dev | Pest 4, Testbench 11, 30 test files, two test cases (`TestCase`, `ProgramTestCase`) |
| CI evidence for the Filament 5 port | Only PR #70's run (dev@`3d160c9a` + PR1): 83 passed on ubuntu and windows with Filament 5.6.8, Livewire 4.3.3, Laravel 13.18.1. `run-tests` has never executed on a push to `dev` itself. |
| Tracked noise on dev | `.phpunit.cache/test-results`, `.claude/settings.json` |
| CHANGELOG on dev | Stops at v2.1 (2025-06-16) |

## 3. Fix before re-reviewing anything

These make the review possible and are independent of the PR content.

1. **CI trigger gap.** `run-tests` fires on `push: [main]` and `pull_request: [main, dev]`. PRs #71–#75 target other `pr*` branches, so they have phpstan and pint only and zero test executions. #75 (docs) has no checks at all, so its `CLEAN` merge state is vacuous. Fix: add `dev` to `push.branches` and either add `pr*`/`**` to `pull_request.branches` or retarget each PR to `dev` after its predecessor merges and wait for green. Do this first, on `dev`, then rebase the stack so every PR shows a real test run. ✅ Done 2026-09-11 on `dev` (`run-tests` now fires on `push: [main, dev]` and `pull_request: [main, dev, 'pr*']`); stack rebased and force-pushed, see item 4.
2. **Merge `main` into `dev`** to recover the 4.x CHANGELOG entries. Expected conflict surface: `.phpunit.cache/test-results` only. Then untrack `.phpunit.cache/` (add to `.gitignore`; only `.phpunit.result.cache` is ignored today). ✅ Done 2026-09-11: merged with no conflicts (only `CHANGELOG.md` changed; the pint-spacing diff on `TeamInvitesTable.php` was already present on `dev`), `.phpunit.cache/` untracked and ignored.
3. **Locate the audit doc.** ✅ Done 2026-09-11. The items cited in PR bodies (2.2, 2.3, 3.7, 4.1–4.15) are sections of the local, untracked [code-reviews/2026-07-06-package-review.md](code-reviews/2026-07-06-package-review.md). The PR-by-PR mapping of those items, and the "Phase 1/2/3" and "Decision" language, come from [plans/phase-1-bugfixes-and-config-lockdown.md](plans/phase-1-bugfixes-and-config-lockdown.md) and its parent [plans/meta-plan-monorepo-migration.md](plans/meta-plan-monorepo-migration.md). The companion [code-reviews/2026-07-06-architecture-package-vs-app.md](code-reviews/2026-07-06-architecture-package-vs-app.md) is the architecture half of the same review. All four already sit in this `docs/` folder.
4. **Refresh stack bases.** Heads are 2 commits behind `dev` after dependabot #76 merged today (workflow files only, plus whatever step 1 adds). ✅ Done 2026-09-11: each `pr*` branch rebased onto its predecessor (#70 onto `dev`), all clean, 108 tests / pint / phpstan green on the stack head locally; force-pushed with lease.

## 4. Consumer surface this app depends on

Use this as the "does it still work for a real consumer" checklist for every PR. Verified against the app on 2026-09-10.

| App usage | Package surface | Affected by |
|---|---|---|
| `App\Models\User extends FilamentTeamManagement\Models\User`, `App\Models\Assessment extends Team`, `App\Models\Invite extends Invite` | Model base classes; `sendInvites`, `users()`, `members()`, `invites()` are **overridden** in `Assessment` | #71 (config-driven user/role resolution; morph-class check in `ModelHasRole`) |
| Panel `->login(Login::class)->registration(Register::class)` from the package | `Filament\Auth\Login`, `Filament\Auth\Register` | #73 (`Register::mount()` redirect for authenticated users) |
| Panel `->discoverPages(vendor/.../Filament/App/Pages)` | ManageTeam page, `TeamInvitesTable`, `TeamMembersTable` and friends | #72 (column `project.name` → `program.name`, `inverseRelationship` fixes) |
| Tenant registration is the app's own `RegisterAssessment extends Filament RegisterTenant` | **Not** the package's `RegisterTeam` | #72's `is_admin=true` creator change does **not** apply here, but does to any consumer using `RegisterTeam` |
| `AssessmentResource` uses `Teams\RelationManagers\InvitesRelationManager` | Admin invites RM | #72 **removes Create and Edit actions** from this RM. Confirm the app does not rely on admins creating invites from the Assessment resource; if it does, this is a regression for us |
| `App\Filament\Admin\Resources\Users\UserResource extends` package `UserResource` | Admin UserResource | #71/#72 indirectly; also open issue #67 (invite from UserResource cannot target a team) |
| `AdminPanelProvider` uses `Http\Middleware\AuthenticateThroughDefaultPanel` | Middleware | none in stack |
| `App\Mail\InviteUserToAssessment` uses `Models\Invite` | Invite model / token | #73 drops URL signing in the package mailable; the app's own mailable is separate, check which one is actually sent |
| No published `config/filament-team-management.php`; table/column names come from **env** (`assessment_user` pivot with `is_admin`, `teams` table = `assessments`, `latest_team_id` on users) | Config env keys | #70 **renames three env keys** with no fallback. Check every server `.env` for `FILAMENT_TEAM_MANAGEMENT_USERS_TABLE`, `_USERS_FOREIGN_KEY`, `_PROGRAM_MODEL` before upgrading |
| Installer-provided migrations already applied (`1_create_teams`, `2_team_members`, `5/6/7_programs`, `update_to_team_management_4`) | Migrations | none (no migration changes v4.0.7 → stack head) |
| Programs feature | `use_programs` | Not set anywhere in the app's code (package default `false`; only a server `.env` could enable it). Assume off: #71/#74 program paths are **not** exercised by this app's smoke tests |
| `SetLatestTeamMiddleware` | Tenant middleware recommended by #75's docs | **Not registered** in the app's `AppPanelProvider`. Either the app has a gap in maintaining `latest_team_id`, or the package handles it elsewhere. Resolve while reviewing #75 |

## 5. Per-PR review checklist

Common to all: author dave-mills, 2026-07-06, no reviews or comments, `MERGEABLE`/`CLEAN`, Claude-co-authored commits. Each PR body claims tests were added except #75. Verify each claim by reading the test, not the body.

### #70 PR1 — Config key alignment (4.1, 4.2, 4.3)

Files: config, installer, `Program`, `Team`, 4 tests. +203/−7. CI: run-tests ✅ both OSes, phpstan ✅, pint ✅ (the only PR with test evidence).

Changes: env `…USERS_TABLE` → `…USER_TABLE`, `…USERS_FOREIGN_KEY` → `…USER_FOREIGN_KEY`, `…PROGRAM_MODEL` → `…PROGRAMS_FOREIGN_KEY` (config *keys* unchanged); installer writes `ROLE_MODEL`; `Team::programs()`/`Program::teams()` now read `table_names.program_team` (previously read non-existent keys, so Laravel guessed `program_team`).

Questions:
- [ ] Is silently ignoring the old env names acceptable for a major release? Options: read old name as fallback with a deprecation log line for one major; or document loudly in CHANGELOG + UPGRADE. Currently neither.
- [ ] `PROGRAM_TEAM_TABLE` becomes honoured. Any host that set it to a non-default value while the code ignored it will now point at a table that may not exist. Confirm this is documented.
- [ ] `ConfigParityTest`: does it actually assert installer output against config env names, or just that keys exist?
- [ ] Does #59 (leading backslash in model class env values) belong here?

### #71 PR2 — Invite user/role via config, Program Admin guard, `names` block (4.6, 4.7, 2.2)

Files: 11, +196/−11. CI: phpstan ✅, pint ✅, **no test run**.

Changes: `sendInvites` on User/Team/Program and `ModelHasRole::created` resolve classes via `config('…models.user'/'…models.role')`; `ModelHasRole::created` early-returns unless `model_type` matches the configured user's morph class; `Program::sendInvites` null-guards the "Program Admin" role with a Filament notification; new `names => ['team' => 'team', 'program' => 'program']` config block; `names.team` → `names.program` copy-paste fix.

Questions:
- [ ] Morph-class early return: a host whose real user class differs from `config('…models.user')` silently loses role tracing and emails. Should this log a warning (once) rather than return silently? Is there a test for the mismatch case?
- [ ] `names` block: hosts with an already-published config get `null` → blank UI copy, which is the very bug 2.2 describes. Either read with a default (`config('…names.team', 'team')`) at every call site or document re-publishing. Which?
- [ ] "Phase 2 InviteService" TODO comments: is Phase 2 in scope for v5.0.0 or a later minor? Decide and remove or keep comments accordingly.
- [ ] Removing `use Spatie\Permission\Models\Role` while #74's seeder still hardcodes it: inconsistent; see #74.
- [ ] Rerun tests in CI after the trigger fix; confirm `InviteMorphTypeTest` and `DisplayNamesTest` pass on both OSes.

### #72 PR3 — UI wiring in invite/member tables and relation managers (4.4, 4.5, 4.8, 4.13)

Files: 11, +201/−24. CI: phpstan ✅, pint ✅, **no test run**.

Changes: `project.name` → `program.name` in invite tables; `inverseRelationship` names fixed; `EditAction` ("Edit Role") added to Teams `UsersRelationManager`; `RegisterTeam` attaches creator with `is_admin => true`; public `form()` **removed** from Programs `UsersRelationManager`; `CreateAction` and `EditAction` **removed** from both admin `InvitesRelationManager`s.

Questions:
- [ ] Three consumer-visible removals bundled as "fix wiring": removed `form()`, removed invite Create/Edit in admin. Each needs a CHANGELOG "Removed" entry. Is removing admin invite creation intended product direction (invites only from the app panel / UserResource)? For this app, confirm nobody creates invites from the Assessment admin resource.
- [ ] `RegisterTeam` creator becomes `is_admin=1` and therefore disappears from `Team::members()` (`wherePivot('is_admin', 0)`). The PR documents this. Is `members()` excluding admins the intended semantics, or should `members()` be all users and a separate `nonAdminMembers()` exist? Decide once; it affects every consumer's Members tab.
- [ ] `ManageTablesRenderTest` ×4: do they render with a real tenant and a real record, or only assert the component mounts?

### #73 PR4 — Auth-flow defects (4.10, 4.11, 4.12, 4.14)

Files: 7, +60/−16. CI: phpstan ✅, pint ✅, **no test run**.

Changes: `Mail/InviteUser` uses `route()` instead of `URL::signedRoute()`; `Register::mount()` redirects authenticated users to `Filament::getUrl()`; inert `app()->bind(SendEmailVerificationNotification)` removed; password min-length message moved to `validationMessages()`; `CheckIfAdmin`/`CheckIfProgramAdmin` return 403 instead of 500 for guests.

Questions:
- [ ] Dropping URL signing: the route file is effectively empty on dev, so nothing enforced the signature and the token was already the only secret. Still a conscious security decision: record it (token entropy, expiry, single-use?) in the PR or CHANGELOG. Is `signedInviteUrl()` in `tests/Pest.php` now dead?
- [ ] `Register::mount()` redirect via `Filament::getUrl()` for an authenticated user with **zero tenants** on a tenant-required panel: does it loop or 404? The added test uses a user with no team but may not exercise a tenant panel. Add that case.
- [ ] Consumers extending `Register` and calling `parent::mount()` inherit the redirect. Fine, but note it.

### #74 PR5 — Wire the four permissions to seeded roles (4.9)

Files: 3, +128/−10 (`TestUserSeeder` + tests). CI: phpstan ✅, pint ✅, **no test run**.

Questions:
- [ ] Seeder still imports `Spatie\Permission\Models\Role`/`Permission` directly, while #71 moved everything else to `config('…models.role')`. The installer injects this seeder into host `DatabaseSeeder`, so hosts with a custom Role model get the wrong class. Fix here or open a follow-up?
- [ ] Guard is now explicit (`config('auth.defaults.guard', 'web')`). Check hosts using a non-web guard for panels.
- [ ] Idempotency (`findOrCreate`) verified by a test that runs the seeder twice?

### #75 PR6 — Docs corrections (4.15)

Files: `CLAUDE.md`, `README.md`, `SETUP.md`. CI: **none**.

Questions:
- [ ] README still does not state the Filament 5 / Livewire 4 / Laravel 13 / PHP 8.4 requirement, and there are no v4 → v5 upgrade notes (env renames from #70, removals from #72, `names` block from #71, `awcodes/shout` dropped). Add an `UPGRADE.md` or a README section before tagging.
- [ ] README badges point at `main`; fine if releases are cut from main, but confirm the release flow (§7).
- [ ] `->tenantMiddleware([SetLatestTeamMiddleware::class], isPersistent: true)` is added to the panel examples. This app's `AppPanelProvider` does **not** register it. Determine whether `latest_team_id` is maintained some other way, and whether the docs should present the middleware as required rather than an example.

## 6. Cross-cutting decisions to make once

- **Breaking-change policy for v5.0.0.** It is a major, so removals and renames are allowed, but each must appear under "Removed"/"Changed" in the CHANGELOG with the migration step. Currently none do.
- **`members()` semantics** (admins excluded or not). Decide before #72 merges.
- **Config access pattern.** Either every `config('filament-team-management.*')` read has a default, or the package requires re-publishing config on major upgrades. Pick one and apply consistently (#71 `names`, #70 pivot key).
- **Model resolution.** All model references go through `config('…models.*')`, including the seeder (#74) and any remaining `Spatie\Permission\Models\*` usages.
- **Scope of "Phase 2" (InviteService, #67).** In or out of v5.0.0.
- **Program mode coverage.** `ProgramTestCase` exists; confirm each PR that touches program paths has a program-mode test.

## 7. Release checklist for v5.0.0

1. CI trigger fix merged to `dev`; `main` merged into `dev`; `.phpunit.cache/` untracked.
2. Stack rebased on `dev`; each PR shows green `run-tests` on ubuntu and windows.
3. Merge bottom-up (#70 → #75), each retargeted to `dev` after the previous merges (GitHub does this automatically only if the merged head branch is deleted).
4. CHANGELOG "Unreleased" section written by hand on `dev` covering: Filament 5 / Livewire 4 / Laravel 13 / PHP 8.4 requirement; `awcodes/shout` removed; env key renames; `names` block; removed RM actions and `form()`; `RegisterTeam` admin change; invite URL no longer signed; middleware 403s; seeder permissions.
5. `UPGRADE.md` (or README section) with the consumer steps.
6. Smoke-test against the AE Policy Tracking Tool on its Filament 5 branch with `dev-dev as 5.0.0`: login, register via invite token, register directly while logged in (#73), ManageTeam page (members and invites tables), admin Assessment resource invites RM, UserResource, role assignment tracing emails (#71 morph check with `App\Models\User`), env keys on staging `.env`.
7. Open PR `dev → main`, wait for `run-tests`, merge, tag **v5.0.0** on `main`. The `Update Changelog` workflow will then append to `main`; because `main` has just been merged into `dev`, the next `main → dev` sync is conflict-free.
8. Update the app's `composer.json` to `stats4sd/filament-team-management: ^5.0`.

## 8. Open items and loose ends

- Issue #67: invite from `UserResource` cannot specify team/program for non-global roles. Related to #71/#72, deferred to "Phase 2".
- Issue #59: leading backslash in `.env` model class instructions. Adjacent to #70, not addressed.
- `routes/team-management.php` is registered via `hasRoute()` but contains only commented-out code. Remove the file or the registration.
- Dependabot label `dependencies` does not exist in the repo; every dependabot PR gets a noise comment.
- `.claude/settings.json` committed on dev: intended?
