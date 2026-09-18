# Phase 1b — Pre-release hardening + remaining review items

> **Track B superseded (2026-09-17):** use [Replacement Track B — membership core with app-owned authorization](phase-1b-track-b-membership-only.md) for the accepted boundary, unchanged-item mapping and historical implementation requirements. The original Track B and decisions below are retained as historical context. Track A completion records remain valid; the replacement implemented the new breaking boundary before A8. Older-app compatibility and incremental upgrade migrations are not requirements under the user's latest consumer constraints.

**Date:** 2026-09-11 · **Updated:** 2026-09-18 · **Status:** A1–A7 historically complete; replacement B1–B8 implemented at `b40c711`, with [verification recorded](../change-logs/phase-1b-track-b-membership-only.md). A8 integration/release remains separate. Historical remote/verification records below have not been refreshed by this status edit.
**Inputs:** [2026-09-11-pr75-stack-re-review.md](../code-reviews/2026-09-11-pr75-stack-re-review.md) §3–§4, [2026-07-06-package-review.md](../code-reviews/2026-07-06-package-review.md), [meta-plan-monorepo-migration.md](meta-plan-monorepo-migration.md) Phase 2.

The current sequence is completed Phase 1/1b implementation → completed [Phase 2 closeout and full review](phase-2-bounded-hardening-closeout.md) → separate Phase 3 plan. Track A records historical preparation; the original Track B is historical only. Package roles/admins, compatibility wrappers and legacy migrations are superseded by the fresh-install membership contract.

**Historical preparation rules:** one PR per group, red→green test per fix, `composer test / analyse / format` green, README/SETUP in the same PR as any public change.

**Historical release rule: every breaking change we already know about ships in 5.0, not later.** If a fix touches a file that has another known-breaking defect in it (e.g. a migration stub), fix that too in the same PR rather than deferring to 6.0.

---

## Consuming apps (all in the `stats4sd` GitHub org)

Historical constraints, preserved without a new consumer audit. Used by A4/A7 for the "grep before deleting/renaming" step, and by UPGRADE.md to know who has to act.

| App                               | Constraint      | Status             | Action for 5.0                                                                                      |
| --------------------------------- | --------------- | ------------------ | --------------------------------------------------------------------------------------------------- |
| `apni-research`                   | `*` (path repo) | not yet published  | grep; will update soon, breaking changes fine                                                       |
| `groundswell_platform`            | `*` (path repo) | not yet published  | grep; will update soon, breaking changes fine                                                       |
| `ae-policy-tracking-tool`         | `^4.0`          | not yet published  | grep; wants to move to 5 soon                                                                       |
| `holpa-platform` | `*` | published, unused | User-recorded 2026-09-17: package revision pinned through submodules, so release tags do not update it. No additional Composer pin required on that basis. |
| `ccrp-soil-health`                | `^1.05`         | published, locked  | no action; will update later                                                                        |
| `wcd-track`                       | `^1.0`          | published, locked  | no action; will update later                                                                        |
| `team-management-dev-no-programs` | `*` (path repo) | throwaway test app | ignore; to be deleted                                                                               |

The historical "grep the consuming apps" instruction meant: for each class/name being deleted or renamed, search the applicable consuming repos above and record which refs were checked (clone or `gh search code --owner stats4sd`). Anything found gets an explicit line in UPGRADE.md. If nothing is found the deletion/rename is still listed in UPGRADE.md, just without an app-specific note.

---

## Track A — required before tagging 5.0

### Merge and verification record (2026-09-17)

| Item | Status | Evidence |
|---|---|---|
| A1 — members include admins | Merged 2026-09-11 | [#78](https://github.com/stats4sd/filament-team-management/pull/78), `ffcbf14`. |
| A2 — CHANGELOG / UPGRADE | Merged 2026-09-17 | [#79](https://github.com/stats4sd/filament-team-management/pull/79). |
| A3 — env reference | Merged 2026-09-17 | [#80](https://github.com/stats4sd/filament-team-management/pull/80). |
| A4 — dead code | Merged 2026-09-17 | [#81](https://github.com/stats4sd/filament-team-management/pull/81). |
| A5 — direct Spatie dependency | Merged 2026-09-17 | [#82](https://github.com/stats4sd/filament-team-management/pull/82). |
| A6 — program columns / FKs | Merged 2026-09-17 | [#83](https://github.com/stats4sd/filament-team-management/pull/83). |
| A7 — Program-panel Teams rename | Merged 2026-09-17 | [#84](https://github.com/stats4sd/filament-team-management/pull/84). |
| A8 — release | Pending | Latest published GitHub release is still `v4.0.7`; `v5.0.0` changelog remains unreleased. |

GitHub records A2–A7 as merged through the stacked merge `e5e98df`. Verified current baseline: `composer test` **119 passed / 320 assertions**; `composer analyse` **no errors**; `vendor/bin/pint --test` **passed**. The scopes below are retained as the implementation record, with corrections where the merged implementation improved on the original plan.

### A1. Fix 2.3 — Members tab hides admins — merged #78

`TeamMembersTable` binds to `users()` instead of `members()`; render test `tests/Feature/TeamMembersTableTest.php`. Merged and covered by the current green suite.

### A2. CHANGELOG 5.0 + UPGRADE.md — merged #79

`UPGRADE.md` now covers dependencies, honoured env vars, published config, panel wiring, behavior changes and post-upgrade verification. CHANGELOG’s unreleased 5.0.0 entry describes the user-visible fixes in words; README links the guide. A4/A6/A7 appended their own changes. A8 still owns the final read-through and release date.

### A3. README env-var reference — merged #80

README now lists every `FILAMENT_TEAM_MANAGEMENT_*` key read by config, its default and purpose, including the plural `USERS_*` → singular `USER_*` upgrade instructions. `ConfigParityTest` checks config/installer key-set parity. Future env-backed settings must update the installer and reference together.

### A4. Dead-code removal (3.6, 3.9) — merged #81, ships in 5.0

Removed the unused `Filament\Auth\RegisterResponse`, empty package class/facade/alias/plugin, `ProgramInvite` and factory, route file and provider route registration, placeholder ManageTeam blade/commented wiring, unused ProgramForm local, duplicate invite save, and `Team::team()` / `Program::program()` self-relations with their interface declarations. UPGRADE.md lists the removals and consumer-search results. Full suite and analysis pass. Reintroducing a plugin object remains a Phase 3 design choice, not leftover A4 work.

### A5. Declare `spatie/laravel-permission` (3.13) — merged #82

A5 added `spatie/laravel-permission: ^7.0` at that time. Replacement B1 intentionally removed it and Althinect; hosts now own privilege storage. Do not restore the dependency.

> **Historical A6 schema record:** role FKs and existing-install migration guidance below were superseded by replacement Track B’s accepted fresh-install contract.

### A6. Program columns always created (3.12) + explicit constraint targets — merged #83, ships in 5.0

- Default stubs 3/9 always create nullable `invites.program_id` (configured FK name) and `users.latest_program_id` without program FKs. Stub 9 drops both columns on rollback. Stub 3 explicitly targets configured teams and Spatie roles tables instead of inferring them from column names.
- Program-tag stub `10_add_program_foreign_keys` adds the invite program FK with cascade deletion and the latest-program FK with null-on-delete. Its `up()` skips existing constraints; `down()` removes the two keys only. Existing program-enabled installs can run it without duplicate-constraint errors.
- Provider publishing uses one shared mutable clock and explicit lists: default 1/2/3/9, then program 5/6/7/10. Generated timestamps enforce the order. Do not rely on lexical sorting of raw stub names (`10_` does not sort numerically after `9_`).
- `tests/TestCase.php` runs default stubs unconditionally and shares `runProgramMigrations()` with `withPrograms()`. The columns now exist before runtime program enablement; the dedicated program-mode test case remains necessary for Program-panel registration at application boot.
- Tests cover default columns without program FKs, custom constraint targets, program-mode FK targets/deletion semantics, stub 10 idempotence/rollback, and published migration order/tag membership. The original SQLite rollback prohibition was incorrect for the installed Laravel version: table rebuilding supports these FK changes, and the rollback test passes.
- README/SETUP/UPGRADE/CHANGELOG describe the new schema and enabling-programs paths. Existing 5.0 installs publish the program tag; pre-5.0 installs that never enabled programs need an add-column migration before stub 10. Existing program-enabled installs already have the columns/constraints.

### A7. Rename Program-panel “Projects” classes (2.5) + config-driven labels — merged #84, ships in 5.0

- `ManageProgramProjects` → `ManageProgramTeams`, `ProgramProjectsTable` → `ProgramTeamsTable`; Livewire key `manage-program-projects` → `manage-program-teams`.
- The table’s attach action is now named `attach` (previously `Add Existing Projects`) and its label follows `names.team`. ManageProgram’s Teams tab also uses `names.team`; its page/name-field labels use `names.program`, with capitalization/pluralization as appropriate.
- UPGRADE.md records the class, action-name and Livewire-key changes and the affected `groundswell_platform` subclass/view wiring. The repo-wide remaining label sweep stays in B6.
- Program-mode render tests cover the renamed widget listing only its program’s teams, and `DisplayNamesTest` covers configurable tab/page/name-field labels.

### A8. Release — pending

- [x] A1–A7 completion and historical verification recorded; A5 intentionally reversed by membership-only B1.
- [x] Consuming-app constraints retained below; no legacy-consumer migration audit is required.
- [ ] Integrate the actual replacement B1–B8 and Phase 2 work through the normal PR process; verify the integrated revision.
- [ ] Finish full package review and required fixes, then reconcile README, SETUP, UPGRADE, CHANGELOG and the membership contract with the accepted fresh schema, host policies, removed APIs and retained class/action names.
- [ ] Record final tests, analysis and formatting evidence; choose eventual release scope/version and date separately from Phase 2 completion.
- [ ] When release is explicitly authorized, merge the release branch through a green PR, tag and publish using the final CHANGELOG. This closeout does not authorize release publishing.

User-recorded update 2026-09-17: `holpa-platform` still declares `*`, but is pinned through submodules and is not affected by release tags. Other consuming apps are pinned or ready to update. This refresh preserves that statement; it did not independently re-audit the consuming repos.

---

## Track B — remaining review items

> **Historical version:** this section is superseded by [the replacement Track B](phase-1b-track-b-membership-only.md). Do not implement its fixed admin rules, Spatie coupling or compatibility requirements as the current plan.

### Readiness review (2026-09-17)

**Not ready as one implementation queue.** B5 can start independently. B6’s panel-ID and App-action decisions are now settled; its scoped UI work and B8’s corrected installer/config scope can be prepared independently. B1–B4 need shared authorization, invite and event contracts; B7 needs an upgrade-safe migration specification. “Ready” below means ready to begin the stated work, not already implemented or validated.

| Component | Readiness | Requirement before implementing the full group |
|---|---|---|
| B1 — authorization | Design required | Define scoped program administration, member page access versus writes, last-admin protection, and configured-model/host policy precedence. |
| B2 — invite service | Contract required | Define actor, duplicate identity, role/admin intent, shared action and exactly-once tracing/mail ownership with B3; classify queue-default compatibility. |
| B3 — events / actions | Depends on B2 contract | Extract invite acceptance and explicit role assignment; define payloads, transaction timing and legacy pivot adapter behavior. |
| B4 — lifecycle | Depends on B2/B3 | Add upgrade migration and submit-time validity checks; settle expired/accepted invite behavior and apply B1 authorization. |
| B5 — tenancy / middleware | Ready now | Include configured FK writes in `Team::sendInvites()` as well as `invites()` relations; coordinate if B2 moves the code first. |
| B6 — UI | Main decisions settled; implementation details below | Use `panels.app/program/admin`; remove only the App attach action. Define safe link/redirect fallbacks, retain the copy-only 2.9 recommendation, and schedule the removal under the release rule. Existing-user acceptance is future feasibility work. |
| B7 — account lifecycle | Migration design required | Make inviter nullable, repair existing orphan references before FK creation, and verify existing deletion cleanup before adding hooks. |
| B8 — installer / config | Ready after scope corrections below | Cover A6 stub 10 and any newer invite migrations; specify robust insertion and actual dotenv round-trip tests. |
| B9 — drill-down | Deferred until a consumer requests it | Depends on B1 authorization and B2’s shared invite action. |

**Release classification still open:** queueing by default changes delivery for hosts without workers; enforcing `is_admin` restricts existing member operations; changing automatic Program Admin grants and removing App attach actions change existing behavior. These cannot be called compatible minors merely because UPGRADE.md explains them. Preserve the historical decisions below, but resolve whether to include those defaults before A8 or introduce compatible opt-in transitions for later minors. The blanket post-5.0 sequence is superseded.

**Relationship to the meta-plan:** Track B is the detailed work breakdown for much of Phase 2 and selected UI follow-ups. A4/A5/A6 already close its dead-code, direct-dependency and program-column work. Core acceptance/role/membership actions must remain explicit so Phase 3 can publish thin UI scaffolds; sending events directly from pages alone does not satisfy that boundary.

### B1. `is_admin` authorization model (1.1, 1.4, 1.3, 1.8) — the headline decision

Decided permission set: team admins may rename the team, invite, attach/detach members and toggle `is_admin`. Team members are read-only plus "Leave team"; **members may not invite**.

- Encode the team rules as `TeamPolicy::update` (rename) and `TeamPolicy::manageMembers` (invite, attach/detach, toggle). **Program design remains open:** `program_members` has no `is_admin`, and `RegisterProgram` attaches ordinary membership. Define whether administration uses a scoped pivot flag or membership plus a role, how global Program Admin / `view all programs` interact, and how creators/existing members are migrated before specifying `ProgramPolicy`; do not simply mirror the team flag.
- Register default policies against configured model classes and define/test host-policy precedence, including custom model subclasses. Do not rely only on assumed provider order. The test harness’s Super Admin `Gate::before` is not a package-shipped authorization guarantee.
- Separate page viewing from rename/membership writes. Installed Filament `EditTenantProfile::canView()` authorizes `update`, so simply denying member updates also hides the page containing the read-only members list and Leave team. Implement/test a read-only view path with independent server-side save and action authorization; apply `manageMembers` in admin `UsersRelationManager::isReadOnly()` and all relevant individual/bulk actions.
- Add "Leave team" action on the App Members tab (1.3): detach self, then redirect to tenant selection; refuse if last admin. Protect the same invariant on detach, bulk detach and demotion, including concurrent mutations; define no-admin legacy-team recovery.
- Tenant registration gate (1.8): config `allow_team_registration` / `allow_program_registration` (bool, default true for BC) checked by overriding `canView()` on `RegisterTeam` / `RegisterProgram` (Filament's `RegisterTenant::canView()` is what the tenant routes `abort_unless` on).
- Tests: policy unit tests (note `tests/TestCase.php` installs a `Gate::before` Super Admin bypass — test policies with non-super-admin users); member vs admin render tests on `ManageTeam*`; leave-team flow; registration gate.
- Docs: README "Team admins" section; UPGRADE note (default policies now shipped; if the app already has a `TeamPolicy` it takes precedence).

### B2. `InviteService` + move UI feedback out of models (3.1, 3.2, 2.1, 1.2 duplicate guard, 3.3 role name)

**Contract work required first:** define an explicit actor/inviter for HTTP, console and job callers; configured Spatie role/model compatibility (do not require a package-only Role subclass); duplicate identity for role-only, team and program invitations; whether a changed role/admin intent on an existing member is applied or rejected; and results for blank input, missing roles and failure. If `asAdmin` supports new-user invites, persist that intent and consume it in the acceptance action; the current invites schema has no such field. Set transaction boundaries and give service/B3 listener a single owner for tracing and mail, because `User::sendInvites()` currently relies on the `ModelHasRole` hook to perform both. Test concurrent duplicate attempts and rollback/no-op behavior.

- Proposed API, subject to the contract above: `Services/InviteService` (or `Actions/SendInvite`): `send(string $email, ?Role $role, ?Team $team, ?Program $program, bool $asAdmin = false)`. Handles: skip blank, duplicate-pending guard (same email + same target → skip with reason), existing-user attach vs new `Invite` + mail, tracing row, returns a small result object (`sent|attached|duplicate|already_member`).
- `User/Team/Program::sendInvites()` become thin delegators (keep for BC; deprecate).
- Filament `Notification`s move to a shared Filament invite action used across the existing entry points, driven by the result object and B1 authorization. Mail stays in the core service/listener boundary chosen with B3; dispatch only after a successful commit. Explicitly deliver this shared action here, since B9 depends on it.
- Decided: **queue by default.** Config `queue_mail` (env `FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL`, default `true`); the service calls `Mail::to()->queue()` when true, `->send()` when false. Mailables already use `Queueable`. The installer must write the key (the parity test `tests/Feature/Install/ConfigParityTest.php` requires every env key the config reads to be written by the installer); add it to the A3 env-var table. The release/default classification must be resolved before A8; an UPGRADE note alone does not make this a compatible minor: hosts with a non-`sync` queue driver and no worker will see invite mail silently stop, so either run a worker or set the env to `false`.
- Program invites: role name from config `roles.program_admin` (default `Program Admin`); the invite form gets an explicit "make program admin" toggle (fixes the silent grant in 2.1). `roles.*` is display/lookup-only and **not env-backed** (same rule as `names.*`), so it needs no installer write and the parity test is unaffected. Add default + override cases for `roles.program_admin` to the `names.*` block in `tests/Unit/ConfigIndirectionTest.php`. (Earlier drafts mentioned a `names.role` key; nothing would read it — dropped.)
- Tests: service tests for each branch plus configured models/morph aliases, explicit actor context, exactly-once mail/tracing and persisted admin intent. Preserve existing InviteFlow/ProgramInviteFlow/MorphType behavior deliberately; where queueing is enabled use `Mail::assertQueued`, and separately retain `Mail::assertSent` coverage for the synchronous opt-out. Existing synchronous-mail assertions cannot remain unchanged under a queued default.

### B3. Membership events + replace `ModelHasRole::created` heuristic (1.6, 3.7)

- Events: `InviteSent`, `InviteAccepted`, `MemberAdded`, `MemberRemoved`, `TeamAdminToggled`, `UserRoleAssigned`. Specify payloads (actor, target, affected user/invite and changed state) and emit from shared actions/services after successful commits, once per actual change, never for duplicates/no-ops or rolled-back writes. Cover individual and bulk UI mutations. Define whether `InviteSent` means mail queued or delivered.
- Restore the meta-plan’s `RegisterInvitedUserAction` / `AcceptInviteAction` extraction: account creation, role/membership attachment and invite confirmation belong in the core transaction; `Register::register()` delegates. Preserve `Registered` / `RegisteredWithData` compatibility explicitly. Shared membership actions must also own writes currently performed by Filament attach/detach/edit actions.
- Introduce explicit role-assignment action/event with actor/context and one tracing/mail owner shared with B2. If `ModelHasRole::created` remains for host/third-party compatibility, document it as an adapter and suppress duplicate service-originated events. Merely wrapping the current auth/request heuristic in an event does not replace it; define request, registration, console/job and seeder behavior explicitly. Remove Filament notifications from the pivot/listener. Use `booted()` if a model hook remains.
- Document events in README (extensibility section, next to `RegisteredWithData`).

### B4. Invite lifecycle (1.2, 2.4)

- Fresh-install schema plus a separately published incremental migration for existing installs, registered in provider/harness: `invites.expires_at` nullable; config `invite_expiry_days` (default null = never, for BC). `Register::mount()` treats expired as invalid → login redirect with a notification. The B3 acceptance action must also reload and validate the submitted token, pending status, expiry and cancellation inside its transaction before creating an account; mount-only validation leaves an already-open form stale after expiry, resend or cancellation.
- Specify whether expired pending invites block a new invite and whether accepted invites may be resent (recommend pending-only); align duplicate handling with B2.
- Actions on App/Program invite tabs and admin RMs: **Resend** (new token + mail, resets `expires_at`), **Cancel** (delete). Shared action classes so all surfaces match, with server-side B1 authorization.
- 2.4 cleanup: rename the internal filter key from `only_unconfirmed` to `show_accepted`; the visible label already says "Show accepted invites"; drop the always-current `team.name` column on the team tab (and `program.name` on the program tab); label "# Invites" as "# Pending invites".
- Tests: expiry rejects, resend rotates token and invalidates old links, cancel removes, toggle reveals confirmed, and expiry/resend/cancel after opening the registration form still prevent acceptance. Cover existing-install migration and rollback as well as fresh schema.

### B5. Tenancy + middleware gotchas (3.5, 3.10, 3.4)

- `SetLatest*Middleware`: dirty-check before `save()`; bail unless tenant is an instance of the configured Team/Program model.
- `User::getDefaultTenant()`: use the `$panel` argument; validate `latestTeam`/`latestProgram` is still in `getTenants($panel)` before returning, else first accessible.
- `Team::invites()` / `Program::invites()`: use `column_names.*_foreign_key` instead of `getModelNameLower().'_id'` (Team) / Laravel's guessed key (Program); extend `ConfigIndirectionTest` (currently does not cover `invites()` FK). Also replace/remove the explicit model-name-derived FK payload in `Team::sendInvites()` for existing users; fixing only the relationship leaves that write incorrect. Exercise new and existing invitees with custom table/FK names.
- `canAccessTenant()`: `exists()` queries instead of loading collections (optional).

### B6. UI copy, hardcoded links, and config-driven labels (2.5, 2.6, 2.8, 2.9)

**User decisions recorded after the readiness review:** remove “Add Existing Users” from the App panel only (2.8), and use the consistent `panels.app|program|admin` config contract (2.6). The removal can be implemented independently of B1; it does not require a new permission model. The details labelled recommendations below are implementation proposals, not additional user decisions.

- **Labels from config, repo-wide** (follow-up to A7): every UI label currently derived from `getModelNameLower()` or `table_names.*` (`HasTeamManagementNavigationGroup`, `ProgramTable`, `ProgramForm`, `TeamsRelationManager`, `UsersRelationManager`, `UserTable`, `TeamInvitesTable` column labels, …) switches to `names.team` / `names.program` (+ `Str::plural` / `Str::ucfirst` as needed). Add `names.user` (default `user`) for user-facing labels. Keep these as plain published-config values. `tests/Feature/DisplayNamesTest.php` and `tests/ProgramMode/Feature/DisplayNamesTest.php` grow cases per surface.
- **2.6 — decided panel-ID contract:** add `panels.app`, `panels.program` and `panels.admin`, defaulting to `app`, `program` and `admin`. These identify host-registered panels; they do not set URL paths or register panels. Recommend plain published-config values, with no new env variables or installer prompts. Document matching host panel IDs and build URLs through those panel objects, including README navigation examples currently using literal panel paths. Preserve the existing default-panel authentication behavior in `InviteUser` and `AuthenticateThroughDefaultPanel`; document the expectation that the configured App panel is also the host’s default authentication panel. These new keys do not independently change login or registration routing.
- **2.6 — links and redirects:** use the configured Program panel for `ManageTeam::getSubheading()` links, checking panel permission/access and access to each linked program. Recommendation: display escaped program names as plain text when the Program panel is unavailable or access is denied, rather than producing broken links; preserve the existing absence of the entire program section when `use_programs` is false. For `ViewTeam`, evaluate the redirect after successful deletion, refresh tenant state and pass a still-accessible tenant explicitly to the configured App panel’s `getUrl($tenant)`. Bare `getUrl()` can infer a default through the globally current Admin panel, so it is not sufficient. Do not reuse the deleted tenant or a stale cached default. If no tenant remains, recommend App tenant registration only when available and permitted; otherwise fall back to the current admin Teams list. The same fallback applies when the App panel is missing or inaccessible. Test custom panel IDs/paths, tenant slugs, missing Program panels, denied tenant access and deletion of the latest/last accessible team, and unavailable or disallowed tenant registration.
- **2.5:** class and copy rename shipped in A7. Nothing left here except any stray “project” wording found after A7.
- **2.8 — decided scope:** remove `AttachAction::make('Add Existing Users')` from App `TeamMembersTable`, including its unused import. Keep Admin and Program attach actions. This is the only behavior change for 2.8 in B6: keep the current email-invite flow, immediate attachment of existing users and notification behavior. Test that the App attach action is absent and cannot be invoked, while the retained invite and Admin/Program attach actions remain available under their existing authorization. Record the removal in CHANGELOG/UPGRADE; under the current release rule it belongs before the 5.0 tag, rather than being assumed compatible in a later minor.
- **2.9 — recommendation still to confirm:** remove the survey-data warning and correct the Program deletion text without changing deletion behavior. Say that deleting a program removes the program and its membership/team associations, while associated teams remain; do not promise that every related row is preserved (program-linked invites also cascade). Keep a clear irreversible-deletion warning appropriate to the package's models. No new cascade into teams or host-owned survey data.

**Future feasibility note — existing-user invitation acceptance (requested, outside B6):** investigate sending an existing user a pending email invitation to join the team, with the email link leading to acceptance using their existing account rather than registration. Membership would be added on acceptance instead of immediately when the invitation is sent. The current `Team::sendInvites()` existing-user branch attaches immediately and sends `UpdateUser`; this future behavior needs a separate pending-invite/acceptance flow, not just different email text or a link to the current registration page. Review with B2's invite service, B3's acceptance action and B4's lifecycle: account/email matching, logged-out login-and-return, wrong-account handling, repeat acceptance, expiry/cancellation and a confirmation step that avoids email scanners accepting invitations automatically. Record feasibility and UX choices before authorizing implementation; do not add this behavior to B6.

**Completion checks:** configured display-name overrides render; custom panel IDs and paths work without hardcoded links; absent/unauthorized panels and post-delete tenant selection have safe fallbacks; the App attach action alone is removed; deletion copy matches actual package behavior; README/config documentation and CHANGELOG/UPGRADE match the chosen release. This scope does not wait for B1/B2 unless implementation introduces additional authorization or invitation behavior beyond the decisions above.

### B7. Account lifecycle (1.9)

- `invites.inviter_id` is currently **NOT NULL**: make it nullable before adding the configured users-table FK with `nullOnDelete`. Update fresh-install schema and provide an incremental migration for existing installs that handles orphan inviter IDs before adding the constraint. Specify publishing order, rollback/data-loss limits and custom-table coverage; honor B8’s invites-table setting if it lands first.
- Mail rendering must tolerate a deleted/missing inviter: both `resources/views/emails/invite.blade.php` and `update.blade.php` currently dereference `inviter->name/email`. Choose a null-safe sender fallback or retained sender snapshot; test deleting the inviter between queueing and delivery, and B4 resend of an orphaned invite. Include this contract in B2 queue integration.
- Admin `UserForm`: `->unique(ignoreRecord: true)` on email.
- User delete: verify current behavior before adding hooks. Installed Spatie `HasRoles::bootHasRoles()` detaches roles on hard delete, and team/program member FKs already cascade. Test hard/soft deletion where supported, custom models/tables, inviter nulling and whether B3 removal events are required (database cascades do not emit application membership events); add only missing behavior.
- Document that password reset / email verification are the host app's responsibility (or wire Filament's `->passwordReset()` in the README App-panel example).

### B8. Installer robustness (3.11) + `table_names.invites` (3.3) + `Stringable` (3.8)

- Round-trip env values containing backslashes through the installed dotenv parser before choosing escaping/quoting; quoting alone is not proof of correctness. Resolve the actual environment-file path, match whole keys including the first line, filter `.env.example` against its own contents, and test repeated runs and independently populated files. Indent injected `$this->call(...)` and make seeder insertion idempotent. Replace raw brace counting with syntax-aware or demonstrably whitespace-tolerant insertion and a clear no-write failure for unsupported input; do not regress to a regex requiring one exact newline/brace layout. Cover braces in strings/comments and supported `run()` formatting.
- Add `table_names.invites` (env `FILAMENT_TEAM_MANAGEMENT_INVITES_TABLE`), written by installer, resolved by `Invite::getTable()`, stub 3 `up()`/`down()`, and A6 stub 10’s FK discovery / `up()` / `down()`; include B4/B7 incremental migrations if they land first; add to `ConfigParityTest` (automatic — it parses both files) and `ConfigIndirectionTest`.
- `HasModelNameLowerString::getModelNameLower()` explicitly casts to `(string)` as contract cleanup, not a confirmed runtime defect (the current method already declares `: string`). (The earlier `getModelNameHeadline()` idea is dropped: labels come from `names.*` config per A7/B6, not from class names.)

### B9. Program panel drill-down (1.7)

Program → Teams tab gains a "Manage members" row action opening a modal table (or a sub-page) of that team's users with invite/detach, reusing the B2 shared invite action. Target A7’s renamed `ProgramTeamsTable`; require B1’s team-level authorization, including which program admins may manage a linked team. Lowest priority; only if a consuming app asks.

---

## Suggested order

> **Superseded:** use [the replacement implementation order](phase-1b-track-b-membership-only.md#5-implementation-order-and-review-gates).

1. **Completed:** Phase 1 #70–#75 and Track A A1–A7; do not recreate their branches or PRs.
2. **Before A8:** classify B1/B2/B6 behavior changes against the existing “known breaking changes in 5.0” rule. If the queued default or authorization/action changes remain breaking, implement those selected items before tagging; otherwise document compatible opt-in transitions and retain the rest for later minors. This is a release decision still to make, not authorization to expand the current release silently.
3. **Independent work ready to start:** B5; B6 with its settled panel-ID/App-action decisions and the remaining implementation recommendations above; B8 with the corrected config/installer coverage. Sequence invite-schema changes consistently across B2/B4/B7/B8.
4. **Core sequence after contract decisions:** settle B1’s authorization model and B2/B3’s actor/role/admin-intent/event ownership together; implement B2 service/shared action → B3 core acceptance/membership/role actions and events → B1 policy/UI enforcement → B4 lifecycle. B7 follows its migration/data-cleanup specification and the B3 event decision. B6’s current UI-only scope can proceed independently; any expanded authorization or existing-user acceptance behavior belongs to its own follow-up.
5. **B9:** only on a consuming-app request, after B1/B2. Append UPGRADE.md per release; assign version bumps based on compatibility rather than automatically calling each group a minor.

## Decisions (settled 2026-09-11)

> **Historical decisions:** the replacement plan explicitly supersedes the package-owned admin model, permission dependency, role intent and compatibility requirements. Retained decisions and exact unchanged requirements are listed in [the new Track B](phase-1b-track-b-membership-only.md).

The following records the decisions made on 2026-09-11. The 2026-09-17 readiness review above identifies additional unresolved contracts and release implications; it does not silently change these historical decisions.

1. **`FilamentTeamManagementPlugin` (A4):** delete. Rebuild in Phase 3 if the scaffold design needs it.
2. **Program columns (A6):** columns always created and nullable in stubs 3 and 9, with no constraint there. FK constraints live in a new stub in the program tag, so they exist only when programs are enabled and never depend on `use_programs` at migration time. Requires the shared-`$now` ordering fix in the provider.
3. **Team admin permissions (B1):** admins rename, invite, attach/detach and toggle `is_admin`. Members are read-only plus "Leave team". Members may not invite.
4. **Invite mail (B2):** queued by default behind `queue_mail` (default `true`), with an UPGRADE note about queue workers.
5. **Program-panel "Projects" classes (B6 → A7):** rename the classes in 5.0. It is a breaking change, so it belongs in the major rather than deferred until the names have confused someone.

Added after the 2026-09-11 code check of the plan:

6. **`spatie/laravel-permission` constraint (A5):** `^7.0`, matching what the lock resolves. `^6.0` (earlier draft) would not install.
7. **FK stub number (A6):** `10_add_program_foreign_keys`, not `8_`. Correction after merge: execution order is established by explicit migration lists and shared-clock publishing timestamps, not raw stub-name lexical order.
8. **Bare `constrained()` in stub 3 (A6):** fix `team_id` and `role_id` to name their tables from config in the same PR. General rule: all known breaking changes ship in 5.0.
9. **UI labels (A7, B6):** come from `names.*` config so each app can override them; never from `getModelNameLower()`. A7 covers `ManageProgram` and its Teams attach label; B6 sweeps the rest and adds `names.user`. `getModelNameHeadline()` (B8) is dropped.
10. **Consuming apps (A4, A7):** listed in the table at the top; the merged A4/A7 upgrade notes record consumer findings and every deletion/rename. The 2026-09-17 user note supersedes the extra `holpa-platform` pin requirement because its package revision is pinned through submodules.
11. **CHANGELOG (A2):** describes each fix in words; internal review numbers are optional cross-references, never the sole description.
12. **`names.role` (B2):** dropped; nothing reads it. `roles.program_admin` stays.

Confirmed by the user after the readiness review:

13. **App attach action (B6 / 2.8):** remove “Add Existing Users” from the App panel only. No change to existing-user email-invite behavior in this item. Investigate acceptance by email link as separate future feasibility work.
14. **Panel IDs (B6 / 2.6):** use `panels.app|program|admin` consistently, with the existing panel IDs as defaults.

## Evidence and handoff notes (2026-09-17)

- B1: `database/migrations/6_create_program_members_table.php.stub`, `src/Filament/Program/Pages/RegisterProgram.php`, and installed `vendor/filament/filament/src/Pages/Tenancy/EditTenantProfile.php::canView()` establish the missing program flag and update/view coupling.
- B2/B3/B4: `src/Models/User.php::sendInvites()`, `src/Models/ModelHasRole.php`, `src/Filament/Auth/Register.php::register()` and `database/migrations/3_create_invites_table.php.stub` establish current tracing ownership, inline acceptance and absent admin-intent/expiry fields.
- B5/B7/B8: `src/Models/Team.php::sendInvites()/invites()`, `src/Models/User.php::getDefaultTenant()`, migration stubs 2/3/6/10 and `src/Commands/InstallFilamentTeamManagement.php` establish the remaining FK, tenancy, nullable-inviter and installer scope.
- `docs/plans/test-plan.md` is an older harness snapshot: its 70-test count, ProgramInvite factory, signed-URL and missing-program-column statements are superseded by the current suite and A4/A6. Program-panel boot still requires the dedicated program-mode test case. Use current tests as the implementation baseline.
- Both phase plans live under git-ignored `docs/`; these updates are local planning deliverables, not part of the tracked package diff.
