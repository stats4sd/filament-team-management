# Meta-plan review after the membership-only implementation

Date: 2026-09-18. Reviewed checkout: `membership-only` at `b40c711`. Scope: completion accounting and readiness to plan Phase 3, not a new authorization implementation or release approval. The existing meta-plan is left unchanged; the revised sequence below is a recommendation for its replacement.

Phase 1 can be considered implementation-complete. Phase 1b A1–A7 and replacement Track B B1–B8 have also delivered most of the old Phase 2. Replace the old Phase 2 with a bounded core/scaffold readiness closeout. There is enough implemented behavior to start detailed Phase 3 planning now; resolve the remaining boundary decisions and assign the small extraction work before treating that plan as ready to execute. A8 release publishing is separate and need not block planning.

## 1. What is complete

| Work | Current disposition | Evidence |
|---|---|---|
| Phase 1 bug fixes and config lockdown | Implementation complete; do not reopen removed role behavior | [Phase 1 completion record](../plans/phase-1-bugfixes-and-config-lockdown.md#completion-record-updated-2026-09-17) records #70–#75 and closure of the original section-4 findings. |
| Phase 1b A1–A7 | Completed historical preparation; A5's permission dependency was subsequently removed deliberately | [Track A record](../plans/phase-1b-pre-release-and-review-remainder.md#merge-and-verification-record-2026-09-17). |
| Replacement B1–B8 | Implemented in the reviewed checkout | [Implementation log](../change-logs/phase-1b-track-b-membership-only.md#delivered-scope), [public contract](../membership-contract.md), source and regression tests. |
| A8 integration/release | Outstanding; no release completion claimed | [Implementation log](../change-logs/phase-1b-track-b-membership-only.md#integration-and-remaining-scope) and `CHANGELOG.md` still describe unreleased work. |
| B9 program-team member drill-down | Deliberately deferred unless a consumer requests it | [Revised B9](../plans/phase-1b-track-b-membership-only.md#b9-program-panel-team-member-drill-down--still-deferred). |
| Pending acceptance for existing accounts | Deliberately deferred; existing accounts are still added immediately | [Invitation contract](../membership-contract.md#actions). |
| Scaffold conversion and monorepo move | Not implemented | Current package still ships Filament pages/resources/tables; no completed scaffold install/reference-app workflow is claimed. |

“Complete” here does not mean every historical checkbox has been proved. Phase 1 still records incomplete evidence that every regression test was run red on the pre-fix code, and its release requirement moved to A8. Neither is evidence of unfinished bug-fix implementation. Likewise, this review establishes the current checkout, not that the membership-only commit has merged into `dev` or `main`; remote integration/release status was not checked.

### Old Phase 2 mapped to the actual implementation

| Old requirement | Current result | Treatment in the revised meta-plan |
|---|---|---|
| One `InviteService`, thin model wrappers | `SendMembershipInvitation` owns the common invitation workflow; obsolete model `sendInvites()` APIs were removed | Complete by a deliberately different API. Do not create another service or restore compatibility wrappers. |
| Move notifications/mail out of models | Filament feedback lives in `Filament/Support/MemberActions`; dispatch belongs to shared actions and `Support/MembershipMail` | Complete. Distinguish presentation from persistence and delivery status. |
| Extract invitation registration/acceptance | `AcceptMembershipInvitation` owns validation, account creation, membership and consumption; `Register` delegates | Complete for domain behavior. Auth/session adapter ownership still needs classification for scaffolding. |
| Membership events and removal of role-pivot heuristics | Eight membership/invitation/link observation events; transactional participants; role adapter removed | Complete. Keep actual event names and timing from the public contract, not obsolete `TeamAdminToggled`/role events. |
| Define/enforce `is_admin`, default privilege policies | Replaced by explicit actor/target Gate checks, host-owned policies/grants, deny-default examples and host-owned access enumeration | Superseded and implemented through the new boundary. No package admin model remains to design. |
| Leave behavior and tenant creation rules | Shared leave/create actions, separate abilities and safe navigation are implemented | Complete behavior; host chooses who may invoke it. |
| Expiry/resend/cancel/duplicate guards | Implemented invitation lifecycle, transactional acceptance and token rotation | Complete. Existing-user pending acceptance is a different feature. |
| Dead-code removal | A4 removed the obsolete facade/plugin/class, unused resources and self-relations | Complete for that inventory; do not delete current components merely because an earlier component had the same generic name. |
| Middleware, remembered tenants, FK derivation, string return, invites table and installer | B5/B8 implemented; permission dependency addition superseded by removal | Complete for the reviewed requirements. |
| “No behavior in Filament files” exit criterion | Mostly achieved for writes, but not fully closed | Keep as a focused boundary audit, with the concrete remainder below. |
| Backwards-compatible minor release, shipped privilege policies | Incompatible with the accepted breaking membership-only boundary | Delete these exit criteria. Use A8 for the 5.0 release and decide scaffold release sequencing explicitly. |

## 2. Architectural findings

### A. The meta-plan is no longer an executable description of Phase 2

The warning at the top correctly supersedes the old authorization direction, but the body still instructs future work to add role-bearing invitations, administrator events, package privilege policies and a Spatie dependency. It also asks for service/lifecycle work already implemented. The same conflict persists in the Phase 3 policy/role-choice requirements and the phase-by-phase patch/minor/major release assumptions. See [the meta-plan](../plans/meta-plan-monorepo-migration.md), especially its ground rules and Phases 2–3.

Replace those sections, rather than relying on another warning above contradictory instructions. Update the historical Phase 1b status that still says “Track B not implemented,” the A8 checklist that still asks for old compatibility classification, and stale Phase 1 deferral ownership. Preserve historical decisions as historical; use the September 18 contract/log for current behavior. The July architecture review remains useful motivation, but its administrator/policy/role and tenant-authority ownership table is no longer authoritative.

### B. One concrete compound operation still belongs to the UI

`src/Filament/Support/ProgramTeams.php:44` starts a transaction around `CreateTeam` followed by `LinkTeamToProgram`. The current operation delegates both writes to authorized actions and is atomic; this review does not identify it as a runtime bug. The architectural issue is that copying this helper into a scaffold also copies responsibility for the compound transaction.

Move this orchestration into a shared action, for example `CreateTeamForProgram::handle(actor, program, data)`, before publishing that surface. Preserve current policy checks, creator membership/participants, connection and locking discipline, and after-commit behavior. The UI should make one call. Verify that denied linking or a participant failure leaves no new team, creator membership, host grant or observation event. This is a small remainder of Phase 2, not a second mutation framework.

### C. “Everything under src/Filament becomes stubs” is too coarse

There are now reusable support classes beneath that directory, and classes outside it depend on them. A class inventory must classify behavior, integration adapters and presentation by responsibility rather than namespace.

| Current surface | Issue to settle before conversion | Recommended direction |
|---|---|---|
| `Filament/Support/Access.php` | Actor resolution, Gate adaptation and host user-picker query invocation accompany UI visibility/query helpers | Retain the non-presentational enforcement/integration contract centrally; keep screen-specific presentation in the scaffold. Preserve both picker eligibility and per-target action authorization. |
| `Filament/Support/MembershipNavigation.php` | Departure selection, panel access and tenant refresh are mixed with `TeamResource` URLs and HTML program links | Separate reusable destination/access handling from host resource routes and link rendering. |
| `Http/Responses/RegisterResponse.php:9` | A response outside the UI tree imports navigation inside it | Assign both ends explicitly; the retained core must not require a removed or app-generated resource class. |
| `Mail/InviteUser.php:24` | Invitation URL construction assumes the default Filament registration panel | Retain this as an explicit supported Filament adapter or supply a host URL contract if the scaffold permits other routing. Full framework independence is not required. |
| `Filament/Auth/Register.php:75` | Domain acceptance is extracted; rate limiting, authentication, session regeneration and response handling remain in the page | Decide what auth adapter is retained and what thin page wiring is published; preserve outer-commit login behavior. |
| `FilamentTeamManagementServiceProvider.php:47`, `Http/Controllers/NoMembershipsController.php`, `resources/views/no-memberships.blade.php` | The package still registers an authenticated landing route and renders a non-email view | Include these in the boundary inventory. An audit of `src/Filament/**` alone would miss them. |
| `Filament/Support/MemberActions.php`, `MembershipTables.php`, pages/resources/widgets | Shared table/action construction is still UI even when reusable | Publish it as app-owned UI under the existing UI-free target, or explicitly revise the target to retain a UI adapter. Do not silently call Tables/Schemas part of a UI-free core. |
| `Models/User.php`, Team/Program interfaces | The documented integration uses subclassing; access defaults deny. The old architecture proposal mentioned tenancy traits | Decide whether to keep the current supported inheritance contract or introduce composable traits. A trait rewrite is not an automatic Phase 2 requirement. |

The recommended default is the existing one-package, UI-free but Filament-aware core with app-owned scaffolding. Keeping Filament contracts or middleware does not imply keeping table builders. A separately retained UI adapter is an alternative architecture decision, not a prerequisite or an already-approved extra package.

Give candidate privacy an explicit disposition in this inventory. `MemberActions.php:55` constrains search and selected labels through the host picker, and lines 58–61 reject submitted IDs outside that query before invoking the batch action. Preserve that behavior centrally through a small explicit-actor query/selection helper if these UI files are published. The core batch still owns per-member mutation authorization; it does not replace the host's discovery restrictions. This is a focused extraction candidate, not a new generic permission-query framework.

## 3. Code/documentation gotchas, separate from architectural findings

- Removing package administrator identities does not remove authorization. Keep host policy calls, fail-closed behavior, explicit targets, participant hooks and the distinction between membership and access. Permission/admin examples in host fixtures are evidence of host freedom, not a remaining package permission backend.
- The remaining `Admin` namespace and `panels.admin` describe a management surface, not an administrator flag. Phase 3 should make panel topology a host choice; do not infer that every app must install an “admin panel.” Renaming all of those classes is not necessary to prove the membership boundary.
- `Support/MembershipMail.php:42` still uses “A site administrator” when the inviter is missing. This is a small copy assumption worth replacing with neutral sender wording; it is not a remaining role system or a Phase 3 planning blocker.
- Do not delete all `names.*` configuration merely because labels will be editable in published pages. `MembershipMail::snapshot()` currently uses team/program nouns for email too. Classify each config consumer; retain or deliberately replace non-scaffold uses.
- `config/filament-team-management.php:45` still has a Phase 2 comment about making display names env-overridable. That is obsolete planning debt, not a requirement to expand config before later shrinking it.
- Reuse the existing common invitation presentation. Phase 3 should convert/customize it, not invent another shared invitation pipeline. Generic invitations must have no role choice.

## 4. Actual bugs

No new runtime defect is established by this review. The concrete source findings above are boundary gaps, stale planning requirements and copy issues. The passing suite supports current behavior; it does not prove that a future published scaffold will preserve it.

## 5. Proposed replacement Phase 2: core/scaffold readiness

This is a bounded closeout of completed hardening, not a new feature phase. Its deliverable is a small set of explicit decisions, any required shared-action extraction, and a clear handoff to the detailed scaffold plan.

**EXACTLY AS-IS:** the implemented actor/target authorization contract, host privilege ownership, invitation outcomes, transaction participants, locking, after-commit events/mail/authentication, immediate existing-account addition and B9 deferral remain the baseline. Scaffolding does not authorize redesigning those behaviors.

1. **Reconcile the plan and completion record.** Mark Phase 1 implemented; mark A1–A7 and B1–B8 complete in the current implementation; separate branch integration/A8 release. Replace the old Phase 2 checklist with the completed-work mapping and residual readiness work. Remove role/administrator defaults and minor-release promises from the future phases.
2. **Record the target boundary and classify files.** Confirm the one-package UI-free/Filament-aware target; classify all Filament support, HTTP, mail, views, provider wiring, models and test helpers. Record the supported User integration, default-panel/URL assumptions, policy-stub ownership and optional management/program surfaces. Every ambiguous component needs an owner or a bounded decision task in the Phase 3 plan.
3. **Close the known behavior escape.** Extract the create-team-and-link-program transaction to one shared action and add focused rollback/authorization coverage. Audit the other surfaces for equivalent composite writes or action-independent enforcement. Preserve the existing public action/participant/event contract unless a specific reviewed gap requires a change. This extraction must land before converting its UI; it may be a Phase 2 closeout commit or an explicit first prerequisite in the Phase 3 implementation plan.
4. **Prepare the verification handoff.** Map current tests to retained core behavior versus published UI integration. Select a small reference-host shape and acceptance matrix; do not require the entire reference application to exist before writing the plan. Any uncertain User/route/panel contract can be resolved with a focused host spike. Record what the eventual generated-app checks must prove.

The Phase 3 plan is ready for implementation when the boundary decisions are recorded, every retained-to-published dependency has a disposition, the known extraction is complete or explicitly ordered before conversion, and installation/host verification has concrete acceptance criteria. Writing the plan can begin immediately using this inventory. Tagging a release, completing B9 or building the monorepo adds no missing architectural information to that planning exercise.

### Reference-host evidence to require during Phase 3

Testbench already covers simple/scoped host grants, both program modes, custom schemas, host access, transactions and Livewire surfaces. Reuse that evidence; do not describe host-owned authorization as still unproved in every form. The missing evidence is an actual installed/generated host running the scaffold and its selected integration contract.

Use a minimal host that will later become the reference app. Exercise programs off/on, creation/bootstrap, invited registration, immediate existing-account addition, resend/cancel, read-only membership views, leave-last-membership navigation, bulk denial and an overlapping-program authorization case. Verify install/reinstall/no-overwrite behavior, actual panel routes and queued mail processing. Keep the simple/scoped authorization fixtures and action-only tests independent of published pages. A second real consumer is useful evidence of portability, but migrating multiple production apps is not a prerequisite to writing Phase 3.

### Explicitly outside this closeout

B9 drill-down, pending acceptance for existing accounts, a package role engine, ownership/last-admin rules, legacy data upgrade tooling, generic audit/outbox infrastructure, cross-package ODK implementation, full scaffolding and monorepo setup remain outside it. None should be brought back under a vague “core hardening” heading.

## 6. What the revised Phase 3 must plan

- Published ownership of pages, resources, schemas, tables, widgets, shared UI helpers, optional panel providers and any chosen auth/landing UI. Use the classification from Phase 2; do not move files solely by directory.
- Installer choices: target namespaces, optional program/management surfaces, conflict detection, repeat runs, explicit overwrite behavior, and treatment of host-edited files. The installer must not create privilege roles or overwrite host authorization policies.
- Which configuration survives in core, which is scaffold input, and which can disappear after its consumers move. Coordinate mail nouns and routing with this decision.
- Conversion of the existing shared member/invitation UI without adding role choices or changing immediate existing-user membership behavior.
- A clear removal/transition decision for packaged UI and documentation of the resulting public calls. The accepted consumer constraints allow a clean removal; there is no requirement to ship a deprecated UI major or legacy role adapters.
- A reference app that actually consumes the core plus generated UI, with install and end-to-end checks separate from core tests. Keep domain fixes centrally updatable through the package.
- Release sequencing: decide whether A8 publishes the current membership-only package first or whether scaffold work is deliberately included before that first release. Do not silently expand A8 or force an extra major merely to preserve the old phase numbering.

Phase 4's monorepo objective remains largely intact. It is not a prerequisite for Phase 3 planning, and this review did not revalidate the companion ODK package's split status or release infrastructure. If coordination later favors creating the monorepo/reference app first, the old allowance to reorder Phases 3 and 4 remains reasonable. Host privileges must remain host-owned when defining cross-package contracts.

## 7. Verification and limits of this review

- Fresh run in this checkout: `composer test -- --compact --colors=never` passed with **167 tests, 637 assertions and four expected opt-in concurrency skips**.
- Source inspection confirmed the shared action boundary, extracted invitation acceptance, deny-default User access, role-free dependencies and schemas, retained Filament surfaces, and the specific residual coupling described above.
- The implementation log records clean PHPStan/Pint/Composer validation and **four MySQL concurrency tests with 15 assertions on MySQL 9.7.1/InnoDB**. These were not rerun for this review; MySQL 8/MariaDB and browser visual checks are not claimed.
- This deliverable changes documentation only. No runtime code, dependency, database, tag or external application was changed. The report is not git-ignored. The meta-plan remains unchanged pending adoption of the recommendations.
