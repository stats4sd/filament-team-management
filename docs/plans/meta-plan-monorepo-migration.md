# Meta-plan: from current package to core + scaffold inside the monorepo

> **Planning update (2026-09-17):** [Replacement Track B — membership core with app-owned authorization](phase-1b-track-b-membership-only.md) supersedes Phase 2's fixed admin model, Spatie dependency, role-bearing invitations/events and backwards-compatible/minor-release assumptions below. It also supersedes Phase 3's proposed core-owned policies and generic invitation role choice: policies and privilege assignment belong to the host. The historical phase outline is preserved; the new plan is authoritative for this boundary. Full UI scaffolding and the monorepo move remain later work.

**Date**: 2026-07-06
**Status**: high-level sequencing only. Each phase below is to be fleshed out into its own detailed plan (by a fresh session) and then implemented, in order. A phase's detailed plan should be written only when the previous phase is complete, so it can react to what actually happened.

**Required reading for anyone fleshing out a phase:**
- [2026-07-06-package-review.md](../code-reviews/2026-07-06-package-review.md) — the findings this plan keeps referring to (sections 1–4).
- [2026-07-06-architecture-package-vs-app.md](../code-reviews/2026-07-06-architecture-package-vs-app.md) — the target architecture (UI-free core + published scaffold, Fortify/Jetstream model) and the "what goes where" table.
- The odk-link companion: `/Users/dave/Projects/PhpPackages/filament-odk-link/docs/code-reviews/2026-07-06-architecture-package-vs-app.md` — the monorepo rationale shared by both packages.

**End state**: a `stats4sd` monorepo containing `packages/team-management` (UI-free, Filament-aware core), `packages/odk-link-core`, `packages/odk-link-filament`, and a reference app; team-management's Filament UI converted to publishable scaffolding owned by consuming apps; three packages distributed via read-only split mirrors to Packagist with lockstep versioning.

**Ground rules for all phases:**
- Each phase lands as its own PR sequence on `dev`; the package stays releasable after every phase (tag a release at each phase boundary: patch for Phase 1, minor for Phase 2, major for Phases 3–4).
- `composer test`, `composer analyse`, `composer format` green at every merge.
- README/SETUP updated in the same PR as any public-API or install-step change (per CLAUDE.md).
- Behaviour changes beyond the documented bug fixes need a decision note in the phase plan, not silent scope creep.

---

## Phase 1 — Fix the known bugs in place; lock down config with tests

**Goal**: current architecture, zero known defects. Nothing here depends on the packaging decision; all of it survives into the core.

Scope, from the package review section 4 (fix in place, minimal diffs):

1. Config/installer key mismatches — 4.1 (`programs_foreign_key` reads `PROGRAM_MODEL` env), 4.2 (`USER_TABLE` vs `USERS_TABLE`, `USER_FOREIGN_KEY` vs `USERS_FOREIGN_KEY`), 4.3 (`team_programs`/`program_teams` vs the real `program_team` key).
2. Broken UI wiring — 4.4 (`project.name` → `program.name`), 4.5 (unreachable `is_admin` form: add the missing `EditAction`; make `RegisterTeam` attach the creator with `is_admin => true`), 4.8 (remove or properly implement the schema-less Create/Edit invite actions), 4.13 (wrong `inverseRelationship` declarations).
3. Invite-logic defects — 4.6 (resolve users/roles via configured model classes so morph types are right), 4.7 (guard the missing `Program Admin` role), plus the nonexistent `names.*` config keys (review 2.2): add a real `names` block to the config with sensible defaults.
4. Auth-flow defects — 4.10 (call parent `mount()` guards; decide whether to drop or enforce the signed-URL), 4.11 (delete the no-op `app()->bind`), 4.12 (fix the `->rule()` misuse), 4.14 (null-safe middleware).
5. Seeder/permission wiring — 4.9 (create all four permissions, attach them to roles).
6. README corrections — 4.15 (404 vs redirect claim, `viewAdminPanel` vs `access admin panel`, document the tenant-middleware requirement).

**Test hardening (the second half of this phase's mandate):**
- Extend `ConfigIndirectionTest` so **every** key in `config/filament-team-management.php` is exercised: each `models.*`, `table_names.*`, `column_names.*`, and `names.*` key overridden and asserted against the relationship/label that consumes it (4.3 slipped through precisely because only `team_members` was covered).
- Add an installer↔config parity test: every env var the install command writes must be read by the config file, and vice versa (catches the whole 4.1/4.2 class permanently).
- Regression tests for each bug fixed (e.g. invite-existing-user asserts the `model_has_roles.model_type` is the configured user class).

**Explicitly out of scope**: refactoring `sendInvites`, events, policies, `is_admin` *enforcement* (Phase 2). Where a Phase 1 fix would be thrown away by Phase 2, prefer the minimal correct fix and leave a `// Phase 2:` marker.

**Exit criteria**: all section-4 findings closed or explicitly deferred with a reason recorded; new tests fail on the pre-fix code; suite + phpstan green; patch/minor release tagged.

---

## Phase 2 — Core hardening (refactor to the behavioural core, still one package)

**Goal**: all shared behaviour lives in services/actions/events/policies with the models thin — the shape the future core needs, delivered as a backwards-compatible refactor. This phase pays off even if Phases 3–4 never happen.

Scope, from review section 3 and gaps 1.1–1.6:

1. **`InviteService`** (review 3.1): one pipeline replacing the three near-identical `sendInvites()` implementations, taking `{email, role?, team?, program?}`; handles duplicate-pending detection, existing-user attach vs new-invite creation, tracing rows, and mail. Models keep thin delegating methods for BC.
2. **Move UI feedback out of models** (3.2): Filament `Notification`s move to the calling actions; mailables dispatched via the service (decide: queue by default?).
3. **`RegisterInvitedUserAction` / `AcceptInviteAction`**: extract the logic currently inside `Register::register()` so the page becomes a thin shell (prerequisite for Phase 3's thin-scaffold rule). Replace the `ModelHasRole::created` side-effect heuristic (3.7) with explicit events.
4. **Membership events** (gap 1.6): `InviteSent`, `InviteAccepted`, `MemberAdded`, `MemberRemoved`, `TeamAdminToggled` — fired from the service layer.
5. **Decide and implement the `is_admin` authorization model** (gap 1.1 — the headline decision of this phase): what team admins can do vs members, enforced via shipped default policies (gap 1.4) and applied in `ManageTeam`/relation managers. Includes a "leave team" pathway decision (gap 1.3) and whether tenant registration stays open (gap 1.8).
6. **Invite lifecycle completion** (gap 1.2): expiry (`expires_at` + migration), resend, cancel actions in the panels, duplicate guard (in the service from item 1).
7. **Dead-code removal** (3.6): unused `RegisterResponse`, empty routes file, empty facade/`FilamentTeamManagement` class, empty plugin (or give it a real job registering middleware/pages — decision point), `ProgramInvite` + factory, unused blades, self-referencing `team()`/`program()` relations, unused variables.
8. Smaller gotchas as they're touched: `latest*` middleware dirty-check + tenant-type guard (3.5), `getDefaultTenant` panel bug and stale-tenant validation (3.10), FK derivation unification (3.4), `Stringable` return type (3.8), explicit `spatie/laravel-permission` dependency (3.13).

**Exit criteria**: no behavioural logic left in Filament page/table classes beyond form layout and delegation; `sendInvites` exists once; events observable in tests; policies shipped and documented; hosts on the previous release upgrade with at most config/seeder changes (write an UPGRADE note); minor release tagged.

---

## Phase 3 — Scaffold conversion (Breeze-style publishable UI)

**Goal**: the package's Filament UI becomes stubs published into the consuming app by the install command; the package itself becomes UI-free (models, migrations, services, actions, events, policies, middleware, tenancy traits, mailables with publishable views).

Scope, from the architecture review's "what goes where" table:

1. **Classify and move**: every class under `src/Filament/**` either (a) moves to `stubs/` as a publishable, namespace-rewritten template (resources, pages, tables, schemas, relation managers, auth pages), or (b) is demoted into the core as a non-UI component (anything that turned out to be behaviour). The thin-file rule from Phase 2 is the acceptance test: a stub that contains logic is a Phase 2 escape, fix it there.
2. **Installer rework**: `filament-team-management:install` gains scaffold publishing — writes the stubs into `app/Filament/...` (+ panel providers), with namespace substitution and `use_programs` conditionality. Decide the flag design (`--panels=app,admin,program`?) and idempotency/overwrite behaviour.
3. **Config shrink**: collapse the env-var indirection to a plain published config file (Spatie pattern); delete label-indirection machinery from stubs (published code just says "Team" — hosts edit the file); keep model/table/FK config in the core for relationships and migrations.
4. **Consolidate the invite UX while converting** (review 2.1): the five invite entry points become one shared action component (calling `InviteService`) used by every published surface, with an explicit role choice. Fix remaining usability findings that live in UI (2.3–2.9) in the stub versions, not the legacy classes.
5. **BC/deprecation path**: decide whether `src/Filament/**` ships one more major as deprecated alongside the stubs, or is removed outright in this major. Write the migration guide for existing apps (publish scaffold → port their subclass overrides into the published files → delete overrides).
6. **Reference verification**: stand up a throwaway host app (this becomes Phase 4's reference app — build it to keep); run every flow end-to-end: install, invite new user, register via token, invite existing user, team admin management, program panel, admin panel.

**Exit criteria**: a fresh Laravel app can `composer require` + install + publish and pass the full manual flow checklist; the package autoloads no Filament UI (or only deprecated shims per the decision in item 5); README/SETUP rewritten around the scaffold model; major release tagged.

---

## Phase 4 — Monorepo move and tidying

**Goal**: development happens in one repo; Packagist distribution unchanged for consumers. Coordinate this phase with the odk-link split — ideally the monorepo is created once, and both packages move in together.

Scope:

1. **Create the monorepo** (`stats4sd/platform-packages` or similar): `packages/team-management/`, `packages/odk-link-core/`, `packages/odk-link-filament/`, `apps/reference-app/`. Import this package with history (`git subtree add` / `git filter-repo`) — don't flatten to a single commit.
2. **Wiring**: root `composer.json` with `path` repositories and merged deps (`symplify/monorepo-builder`); reference app consumes all packages by symlink; per-package Testbench suites unchanged.
3. **Split + release pipeline**: read-only mirror repos (repurpose the existing `stats4sd/filament-team-management` GitHub repo as its own mirror to keep the Packagist registration); `symplify/monorepo-split-github-action` on push + tag; deploy keys/PAT; lockstep version tags; monorepo-builder constraint sync + validation in CI.
4. **CI matrix**: per-package test/analyse jobs + reference-app integration job; releases only from green main.
5. **Repo hygiene**: mirrors get issues/PRs disabled with a pointer to the monorepo; CONTRIBUTING and release-process doc in the monorepo root; move `docs/` content (these reviews and plans) into the monorepo package dir; archive stale branches.
6. **Cross-package follow-ups** (first monorepo-native work, proving the workflow): odk-link consumes team-management's `Team`/membership events via its contracts; decide the dependency direction and constraints between the three packages explicitly.

**Exit criteria**: one tag on the monorepo publishes all three packages to Packagist via the mirrors; a consuming app's `composer update` sees a normal release; a deliberately cross-package change lands as a single PR and releases atomically.

---

## Sequencing notes for the implementing sessions

- **Don't reorder phases 1→2→3.** Phase 2's service extraction assumes the bugs are fixed (refactoring on known-broken behaviour bakes the bugs into the new seams); Phase 3's thin stubs assume Phase 2's actions exist.
- **Phase 4 is independent of 3** and could technically run earlier if the odk-link timeline forces it — the package can enter the monorepo pre-scaffold-conversion. If so, swap 3 and 4 and do the scaffold conversion inside the monorepo with the reference app available; that ordering is acceptable, the reverse (delaying bug fixes) is not.
- Each detailed plan should end with its phase's release tag and an explicit "decisions made" list, so the next phase's session inherits the record.
