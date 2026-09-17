# Package vs. scaffold — should `filament-team-management` follow the odk-link split?

**Date**: 2026-07-06
**Author**: Claude (architecture appraisal, companion to [2026-07-06-package-review.md](2026-07-06-package-review.md))
**Question**: The companion review of `filament-odk-link` ([architecture doc](../../../filament-odk-link/docs/code-reviews/2026-07-06-architecture-package-vs-app.md)) recommends splitting that package into a headless versioned core plus a Breeze-style published UI scaffold that the consuming app owns. Does the same strategy make sense long-term for `filament-team-management`?

---

## TL;DR

**Yes — the split fits this package even better than it fits odk-link, with two adjustments to the recipe:**

1. **The core here cannot be fully headless.** Team-management's server side *is partly Filament plumbing by nature* — `HasTenants`/`HasDefaultTenant` on User, tenancy middleware, the invite-registration flow hooked into Filament auth. Aim for a **UI-free but Filament-aware** core (contracts, traits, actions, middleware — no Schemas, Tables, Pages, or Blade), not a framework-agnostic one.
2. **One composer package is enough.** The core left after extracting UI is roughly spatie/laravel-permission-sized. Follow the Breeze packaging model (one package: core in `src/`, UI as publishable stubs via the install command) rather than odk-link's two-package split — but put it in the **same monorepo** so cross-package features (odk-link consumes Teams) are one PR.

The strongest argument is that this package already demonstrates the failure mode the scaffold model solves: roughly a third of the findings in the companion review are the package *fighting to make packaged UI generic* — and losing.

---

## Why the evidence points the same way

The odk-link diagnosis was "the boundary is fictional" (package reaches into `App\Models`). This package has the **opposite** disease with the **same** symptom. Credit where due: the boundary here is real — there is no `App\` coupling anywhere; everything resolves through config. But look at what maintaining that genericity in *UI* code costs:

- **Label-generation gymnastics.** Four competing mechanisms for one job — `getModelNameLower()` string-building, `table_names.*` as display text ("Add Existing User to teams"), hardcoded strings, and a `names.*` config block that *doesn't exist*, rendering literal blank gaps in user-facing copy (review 2.2). This is what it looks like when package UI tries to parameterise every noun. An app-owned page just types the word.
- **Five invite entry points with three shapes and divergent side effects** (review 2.1). Packaged UI can't decide per-app which surfaces should exist, so it ships all of them, and they drift.
- **Hardcoded panel paths** (`/app`, `/program/{id}` — review 2.6) because the package can't know the host's panel layout. The host's own scaffold would.
- **Stats4SD-specific copy in generic UI** ("survey data collected" — review 2.9) — app content trapped in a shared dependency.
- **The README already outsources the hard part.** Panel wiring, middleware ordering, auth middleware replacement — the host assembles the front-end anyway. The package ships UI *parts* but not the UI *architecture*, which is the worst of both: apps own the layout but not the pieces in it.

Meanwhile the genuinely shared, security-relevant logic — invite tokens, tenancy access rules (`canAccessTenant`), role assignment, registration — is exactly the code the odk-link doc argues must stay single-source. The companion review found real bugs in it (wrong morph class on role assignment, config-key mismatches, forever-valid tokens). You want to fix those once, not per app.

So the same conclusion drops out: **shared behavioural core as a versioned dependency; presentation published into the app.**

## The precedent is exact: Fortify + Jetstream

Laravel already split *this precise domain* this way. **Fortify** is headless auth: actions, contracts, no views — the package equivalent of the proposed core. **Jetstream** (which includes teams, invitations, and team roles) is scaffolding: it publishes views and `app/Actions/Jetstream/*` (`AddTeamMember`, `InviteTeamMember`, `DeleteTeam`…) into the app, which owns them thereafter. Filament's own recommendation for customising beyond hooks is the same: publish and own. The proposed target state for this package is "Fortify-with-teams core + a Filament-flavoured Jetstream scaffold", which is about as well-trodden as architecture decisions get.

## What goes where

| Core (versioned dependency, `src/`) | Published scaffold (app-owned after install) |
|---|---|
| Models, interfaces, migrations, config | Filament Resources, Schemas, Tables, RelationManagers |
| **`InviteService`** — the single invite pipeline (review 3.1 wants this extracted anyway) | `ManageTeam` / `ManageProgram` pages and their tab widgets |
| `AcceptInviteAction` / `RegisterInvitedUserAction` (token validation, user creation, role/team/program linking) | `Register` / `Login` page classes (thin: form layout + a call into the core action) |
| Tenancy: `HasTenants` implementation as a **trait**, `SetLatest*` middleware, `AuthenticateThroughDefaultPanel`, `CheckIf*Admin` | Panel providers / wiring examples (currently README prose — make them published code) |
| Events: `InviteSent`, `InviteAccepted`, `MemberAdded`, `MemberRemoved`, `RegisteredWithData` (fills review gap 1.6) | All display text, nav items, panel-path links, branding blades |
| Default policies (`TeamPolicy` etc. — fills review gap 1.4) + permission seeder that actually wires roles (gap 1.5) | Admin resources (Users/Teams/Programs/Roles/Permissions) |
| Mailables with publishable views (standard Laravel) | Choice of which invite surfaces exist, and their copy |

The load-bearing rule: **published files must be thin.** Every scaffolded page/action delegates to a core action or service, so a security fix in the invite flow is a `composer update`, not a five-app find-and-replace. Jetstream gets away with publishing actions because they're ~20-line wrappers over Fortify contracts; copy that discipline. The current code fails this rule — `sendInvites()` (mail, token generation, role attachment, UI notifications, all in the models) would today be published wholesale. Extracting `InviteService` is therefore the *prerequisite* step, and it's already the top refactor recommendation in the companion review regardless of the packaging decision.

## Where this package differs from odk-link (and why it changes the recipe, not the conclusion)

- **Smaller core.** Odk-link's headless core (ODK client, jobs, imports) is the majority of that package. Here the split is inverted: UI is ~60–70% of the code by volume. That's an argument *for* the scaffold model, not against a package — what remains (schema, invite lifecycle, tenancy, policies, events) is precisely the hard-to-get-right shared part, and it's a perfectly respectable package on its own.
- **Filament coupling in the core is legitimate.** Don't chase full headlessness: `getTenants(Panel $panel)` exists to serve Filament tenancy, and that *is* this package's job. UI-free is the boundary; framework-free is over-engineering.
- **The config surface should shrink, not grow.** Most of the env-var indirection (`FILAMENT_TEAM_MANAGEMENT_*` for tables/FKs/labels) exists so packaged UI and migrations can adapt to host naming. Once apps own the UI, label indirection disappears entirely, and table/FK config can collapse to a plain published config file (the Spatie pattern) instead of ten env vars — which incidentally would have prevented bugs 4.1/4.2 (env-name mismatches between installer and config).
- **The two packages compose.** Odk-link's host apps get Teams from this package. If both live in one monorepo (`packages/team-management`, `packages/odk-link-core`, `packages/odk-link-filament`, plus a reference app on `path` repositories), the recurring cross-package feature ("form ownership follows team membership") becomes one PR. Doing the split for one package but not the other forfeits most of the workflow win.

## Honest costs

- **Published UI drifts.** A UI improvement in the scaffold (say, a better invites table) reaches existing apps only by manual re-adoption. Acceptable *if* the thin-file rule holds, because behaviour fixes ship through the core; UI drift is then cosmetic and opt-in. This is the deal Jetstream users accept knowingly.
- **Upgrade discipline.** Core must maintain BC on its action/service signatures, since app-owned files call them. Versioned releases + a small contracts namespace make that tractable.
- **One-off migration effort.** Existing consuming apps need a guided move: install the scaffold, delete their subclass-and-override workarounds, re-point panels. Budget it like a framework upgrade.
- **The bugs don't wait.** As with odk-link: the companion review's 4.1–4.9 (config-key mismatches, morph-type role assignment, unreachable `is_admin`, seeder wiring) should be fixed on the current structure first. They're orthogonal to packaging, several live in what will become the core, and refactoring on top of known-broken behaviour bakes bugs into the new architecture.

## Recommended sequence

1. **Now:** fix the review's section-4 bugs in place; extend `ConfigIndirectionTest` to cover every config key.
2. **Core hardening (still one package):** extract `InviteService` + `RegisterInvitedUserAction`, add the membership events, ship default policies and a correct permission seeder. This step pays off even if the scaffold idea is later dropped.
3. **Scaffold conversion:** move `src/Filament/**` to publishable stubs; extend `filament-team-management:install` to publish them (and the panel providers) into the app; make each published class a thin shell over core actions. Delete the label-indirection machinery as you go.
4. **Monorepo:** co-locate with the odk-link packages and a reference app via `path` repositories; tag releases for external consumers deliberately.

## One-paragraph version for a stakeholder

Yes — the same strategy fits, arguably better. The valuable, risky part of this package (invite tokens, tenancy rules, role assignment, the database schema) is a compact core that should remain one shared, versioned dependency so a security fix lands everywhere at once. The bulk of the package by volume is Filament UI that is already straining to be generic — inventing four different ways to render the word "team", shipping five inconsistent invite buttons, and hardcoding panel URLs it can't actually know. Publish that UI into each app Breeze/Jetstream-style and let the app own it, keeping the published files as thin wrappers over core actions. Laravel's own Fortify/Jetstream pair is this exact architecture for this exact domain. Do it in the same monorepo as the odk-link split so features spanning both packages become a single pull request — and fix the already-catalogued bugs first, on the current structure, so the new architecture isn't built on known-broken behaviour.
