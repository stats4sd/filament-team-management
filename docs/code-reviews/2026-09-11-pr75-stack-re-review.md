# Re-review of PR #75 and the Phase 1 stack against the 2026-07-06 package review

**Date:** 2026-09-11 · **Branch:** `pr6-docs-corrections` · **HEAD:** `1a8a5a3` · **Baseline:** [2026-07-06-package-review.md](2026-07-06-package-review.md), [phase-1-bugfixes-and-config-lockdown.md](../plans/phase-1-bugfixes-and-config-lockdown.md)

**Goal being assessed:** publish the next major release so consuming apps can move to Laravel 13 / Filament 5 / Livewire 4, with breaking changes listed and an upgrade checklist for those apps.

**Suite at HEAD:** 108 tests pass (282 assertions), PHPStan level 4 clean, CI green on all six stacked PRs (#70–#75).

---

## 1. PR #75 itself

Docs-only: README, SETUP, CLAUDE.md. Every claim it adds was checked against the code and is accurate.

- The four permission strings match `CheckIfAdmin`, `CheckIfProgramAdmin`, and the `view all teams` / `view all programs` checks in `User`.
- `->tenantMiddleware([...], isPersistent: true)` matches the Filament 5 signature (`HasMiddleware::tenantMiddleware(array $middleware, bool $isPersistent = false)`).
- The Permissions section's description of `TestUserSeeder` (creates the four, program pair only when programs enabled, attaches to Super Admin and Program Admin) matches the PR5 seeder.
- The CLAUDE.md `Register::mount()` sentence matches the current code.

Gaps in PR #75 (small, could land in it or in the release PR):

- README still documents only one env var (`FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL`, line 57). The PR1 env-var rename (`USERS_TABLE`→`USER_TABLE`, `USERS_FOREIGN_KEY`→`USER_FOREIGN_KEY`) and the newly honoured `PROGRAMS_FOREIGN_KEY` have no user-facing home. The plan said "note in the PR description", which is not something an upgrading app will read.
- The README nav example for the Program panel link now checks `access program admin panel`, but the same block still hardcodes `/program` and `/admin` URLs (review 2.6, deferred). Acceptable for now.
- No CHANGELOG entry anywhere in the stack. CHANGELOG.md stops at 4.0.7.

**Verdict on #75:** approve as-is. It closes 4.15 and the stale-note cleanup exactly as planned.

---

## 2. Coverage of the 2026-07-06 review by the stack (#70–#75)

### Closed

| Item | Where | Notes |
|---|---|---|
| 4.1, 4.2, 4.3 | PR1 | Config now reads `USER_TABLE`, `USER_FOREIGN_KEY`, `PROGRAMS_FOREIGN_KEY`; `program_team` key used in both pivots; installer writes `ROLE_MODEL`. Locked by `ConfigIndirectionTest` (every key) and `ConfigParityTest` (installer↔config). |
| 4.6, 4.7, 2.2 | PR2 | Invite paths resolve user/role via config; `Program::sendInvites()` warns and returns on a missing role; `names` block added. |
| 4.4, 4.5, 4.8, 4.13 | PR3 | `program.name` columns; `EditAction` exposes `is_admin`; `RegisterTeam` attaches creator as admin; dead Create/Edit removed from admin invite RMs; inverse relationship names fixed. |
| 4.10, 4.11, 4.12, 4.14 | PR4 | Signed URL dropped (Decision 1); auth guard in `mount()`; no-op bind removed; password message via `validationMessages`; null-safe middleware. |
| 4.9 / 1.5 | PR5 | Four permissions created, attached to roles, idempotent seeder. |
| 4.15 | PR6 | This PR. |
| 2.7 | pre-stack (`d786d45`) | Invalid token redirects to login. |

### Partially addressed (minimal fix, `// Phase 2:` marker left)

- **1.1 / 4.5** — `is_admin` is now settable (admin panel) and set for team creators, but nothing enforces it. `canAccessTenant()` and `ManageTeam` still treat every member identically.
- **2.1** — the crash is guarded, but `Program::sendInvites()` still hardcodes the role name `Program Admin` and silently grants it to every program invitee.
- **3.3** — `User::`/`Role::` literals are gone from the invite paths and `ModelHasRole`. Still hardcoded: `Invite::$table = 'invites'` (no `table_names.invites` key), `PermissionResource` uses Spatie `Permission` directly, and the `Program Admin` role name.
- **3.7** — `ModelHasRole::created` now checks `model_type` against the configured user model's morph class. Still legacy `boot()`, still uses `auth()->id() != null` to distinguish registration from admin assignment (so console/job/seeder role assignments write no tracing invite), still fires mail and Filament notifications from a pivot event.

### Not addressed (all explicitly deferred by the Phase 1 plan; confirmed still present at HEAD)

Functionality: 1.2 (no expiry/resend/cancel/duplicate guard), 1.3 (no leave-team), 1.4 (`can('update', $team)` with no shipped policy and no doc), 1.6 (only `RegisteredWithData` event), 1.7, 1.8 (open tenant registration), 1.9 (orphan `inviter_id`, no email-uniqueness on admin edit).

Usability: 2.3 (App Members tab binds to `members()` and hides admins, now more visible because creators are admins), 2.4, 2.5 (Projects vs Teams), 2.6 (hardcoded `/program/{id}`, `/app`), 2.8, 2.9 (survey-data copy, false cascade claim).

Code: 3.1, 3.2, 3.4, 3.5, 3.6 (all dead code still present, including the duplicate `$invite->save()` at `ModelHasRole.php:54`), 3.8, 3.9, 3.10, 3.11, 3.12, 3.13.

Note on 2.3: PR3 made this worse in practice. Before, `is_admin` was never true so `members()` and `users()` returned the same rows. Now every team created through `RegisterTeam` has its creator flagged admin, and that creator disappears from their own team's Members tab in the App panel. Recommend fixing before release (one-line change to `users()`, plus a render test).

---

## 3. What is required before publishing the major release

### Must do

1. **Merge the stack in order** (#70 → #75). They are stacked (each targets the previous branch); merge #70 into `dev`, retarget #71 to `dev`, repeat. Or squash the six into one `dev` merge if review of each is already done.
2. **Fix 2.3** (`TeamMembersTable` → `users()`), since PR3 makes admins vanish from the App panel member list. Small; add a `ManageTablesRenderTest` case that an admin member is listed.
3. **Write the 5.0 CHANGELOG entry and an UPGRADE section** (see §4). Nothing in the stack or on `dev` documents the breaking changes; the last CHANGELOG entry is 4.0.7.
4. **Document the env vars in README** (all the `FILAMENT_TEAM_MANAGEMENT_*` keys the config reads, with defaults), so the rename in item 3 of the checklist has a canonical reference.
5. **Choose the version.** 4.0 was numbered to match Filament 4; the same logic gives **5.0.0**.
6. **Tag from a green `dev` → `main` merge** after the stack lands (existing release pattern: PRs #56, #58, #61).

### Strongly recommended for this major, because they are breaking and cheap, and the next chance is 6.0

7. **Dead-code removal (3.6)** — deleting `Filament\Auth\RegisterResponse`, the empty `FilamentTeamManagement` class + Facade (and its `composer.json` alias), the empty `FilamentTeamManagementPlugin`, `ProgramInvite` + factory, the commented-out routes file, and the unused blade is only safe in a major. Any host referencing them would break; grep the consuming apps first.
8. **Declare `spatie/laravel-permission` explicitly (3.13)** — a one-line `composer.json` change that belongs with the dependency bump.
9. **Conditional program columns (3.12)** — decide now whether 5.0 migrations always create nullable `program_id` / `latest_program_id`. Only affects fresh installs, but changing published migration stubs later is awkward.

### Not required for the release (Phase 2 / Phase 3 scope, no BC impact)

Everything else in §2 "Not addressed": invite lifecycle, policies and `is_admin` enforcement, events, `InviteService`, UX consolidation, installer robustness.

---

## 4. Breaking changes and upgrade checklist for consuming apps (v4.0.7 → 5.0)

No class was renamed or moved and no public method signature changed between v4.0.7 and HEAD (`git diff -M --name-status` on `src/` is all modifications; the Filament 5 layout change happened in 4.0). The breaks are dependency, config, and behaviour changes.

### Dependencies

- [ ] App is on **Laravel 13, Filament ^5.2, Livewire ^4** before requiring this version. The package no longer resolves on Filament 4 / Livewire 3. The app's own panels, resources, and Livewire components must already be upgraded (Filament and Livewire upgrade guides).
- [ ] PHP ^8.4 (unchanged from 4.0.7).
- [ ] **`awcodes/shout` is no longer a dependency.** If the app used `Shout::make()` anywhere without requiring `awcodes/shout` itself, either require it directly or migrate to Filament's `Callout` (`->type('info')` → `->info()`, `->content()` → `->description()`).
- [ ] `althinect/filament-spatie-roles-permissions` is now `^3.0` stable (was `^3.x-dev`). Remove any `minimum-stability`/`prefer-stable` workarounds added for it.

### Environment variables now honoured (PR1)

Three env vars the installer has always written were previously ignored by the config. They are now read, so values that silently did nothing before now take effect.

- [ ] `FILAMENT_TEAM_MANAGEMENT_USER_TABLE` and `FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY`: if the app hand-set the old plural names `USERS_TABLE` / `USERS_FOREIGN_KEY`, rename them. If the installer wrote non-default values (custom users table or model), confirm they match the real table and pivot column names, because they were ignored under 4.x.
- [ ] `FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY`: under 4.x `programs_foreign_key` read `PROGRAM_MODEL`, so any program app that worked had either no `PROGRAM_MODEL` set or a patched config. Confirm the value (default `program_id`) matches the column in `program_members`, `program_team`, `invites`, and `users.latest_program_id`.
- [ ] `FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE`: `Team::programs()` / `Program::teams()` now use this key. If it is set to a non-default value, confirm the pivot table actually has that name (4.x created it with the configured name but queried `program_team`).
- [ ] `FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL`: newly written by the installer; only relevant if the app uses a custom Spatie role model and never set it.

### Published config (PR2)

- [ ] If the app has published `config/filament-team-management.php`, add the new `names` block (`'names' => ['team' => 'team', 'program' => 'program']`). Without it the invite callouts and program delete modal render a blank where the noun should be.

### Panel wiring (PR6 makes existing requirements explicit)

- [ ] App panel `->tenantMiddleware([SetLatestTeamMiddleware::class], isPersistent: true)`; Program panel the same with `SetLatestProgramMiddleware`. `getDefaultTenant()` depends on this. It was a README TODO in 4.x.
- [ ] Navigation items copied from the 4.x README that check `can('viewAdminPanel')` must change to `can('access admin panel')` (admin links) or `can('access program admin panel')` (program link). If the app created a `viewAdminPanel` permission to make the old example work, it can be removed.
- [ ] The four permissions `access admin panel`, `access program admin panel`, `view all teams`, `view all programs` must exist and be assigned to the intended roles. The package seeder now does this; apps with their own seeders should mirror it. A `Gate::before` super-admin bypass still works but is no longer needed for the seeded admin.

### Behaviour changes

- [ ] **Team creators are now team admins.** `RegisterTeam` attaches the creating user with `is_admin = true`. Existing teams have no admin flagged; decide whether to backfill (e.g. set `is_admin` for the oldest member of each team). `is_admin` is still not enforced, so this changes data, not access.
- [ ] **Admin panel → Team → Users** gains an "Edit Role" action that toggles `is_admin`. **Admin panel → Program → Users** loses the (dead) name-edit form. **Admin panel Invites relation managers** lose Create and Edit; Delete remains. Update any app tests or overrides that referenced those actions.
- [ ] **Program invites with no `Program Admin` role** now show a warning and send nothing instead of throwing. Make sure the role exists in every environment (the package seeder creates it).
- [ ] **Invite emails link to an unsigned URL.** `InviteUser` now uses `route()` instead of `URL::signedRoute()`. If the app added `signed` middleware to the register route, or its tests assert a `signature=` parameter, remove them. Apps overriding the `InviteUser` mailable or `emails.invite` view should check their copies.
- [ ] **Register page:** an authenticated user following an invite link is redirected to the panel home; a missing or invalid token redirects to login (4.x returned 404). Apps overriding `Register::mount()` should port both guards.
- [ ] **Register password rule:** the custom "at least 10 characters" message now actually displays. No action, unless tests asserted the default Laravel message.
- [ ] **`CheckIfAdmin` / `CheckIfProgramAdmin`** return 403 when unauthenticated (was a 500). No action.
- [ ] **Inviting an existing user now records the role under the app's User class** in `model_has_roles.model_type`. Under 4.x it wrote the package's `Stats4sd\FilamentTeamManagement\Models\User`, so roles granted via invite to existing users never applied. Check `model_has_roles` for rows with the package class as `model_type` and rewrite them to the app's class (or re-assign the roles).
- [ ] **`TestUserSeeder`** is now idempotent (`findOrCreate`) and attaches permissions. Apps that run it alongside their own seeders that `Role::create` the same names will still collide on the app side, as before.

### Verify after upgrade

- [ ] Log in as a seeded admin and reach the Admin panel (permission wiring).
- [ ] Register a new team, then open Manage Team → Members and confirm the creator is listed (this is finding 2.3; it fails at HEAD until fixed).
- [ ] Invite a new address and an existing user from each entry point (App Members, Admin Team Users, Admin Users, Admin Program Users, Program Members); confirm role/team/program land on the correct user class.
- [ ] Follow an invite link while logged in, then logged out, then with a bad token.
- [ ] Program panel: attach/detach a team, invite a member, confirm they can enter the panel.
