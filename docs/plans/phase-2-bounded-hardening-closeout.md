# Phase 2 — Bounded closeout of completed hardening

**Date:** 2026-09-18. **Status:** implementation plan; work below has not been implemented by writing this document. **Planning baseline:** `membership-only` at `b40c711`, following replacement Track B B1–B8. Recheck the checkout and preserve existing work when implementation begins.

**Outcome:** finish the small, known shared-behavior extractions, reconcile the operative roadmap, and assemble evidence for a full review of the resulting membership package. Stop after implementation and verification for that review. Write the detailed Phase 3 scaffold plan separately, using the review's findings.

This plan replaces the old meta-plan's Phase 2 implementation requirements. It adopts the bounded closeout recommendation in the [September 18 review](../code-reviews/2026-09-18-meta-plan-membership-only-review.md), with one sequencing change requested afterward: complete Phase 2 and review the package before selecting the detailed Phase 3 architecture or reference-host design. The old meta-plan will be reconciled in P2.1; its incompatible role/admin/minor-release instructions are not an additional work queue.

## 1. Baseline and scope

Phase 1 is implementation-complete. Phase 1b A1–A7 and replacement B1–B8 delivered the shared membership actions, invitation lifecycle and acceptance, explicit host-policy enforcement, transactional participants, after-commit observations, tenant-access hardening, configured schemas and installer work. Do not repeat those implementations or restore removed compatibility APIs. The [membership contract](../membership-contract.md) and [Track B implementation log](../change-logs/phase-1b-track-b-membership-only.md) describe the current behavior.

The remaining implementation consists of two extractions and two small cleanup changes. The present create-and-link flow is already atomic, and picker checks already exist in UI support. These are boundary improvements and test-coverage gaps, not established runtime defects.

| Item | Phase 2 disposition |
|---|---|
| Create a team and link it to a program | Move orchestration from UI support into one shared action; prove rollback and outer-commit behavior. |
| Host user-picker query and selected-ID validation | Move the reusable discovery boundary into explicit-actor support; retain per-user mutation authorization. |
| Missing inviter wording | Replace the package's “A site administrator” fallback with neutral wording. |
| Stale display-name config comment | Remove the abandoned Phase 2 env-expansion instruction; preserve config behavior. |
| Roadmap/status contradictions | Reconcile current instructions and completion records while preserving labeled historical evidence. |
| Remaining UI/core dependencies | Inventory and classify current responsibilities; carry concrete decisions into the later full review and Phase 3 planning. |
| Navigation/auth routing, generated UI, installer redesign, reference app and monorepo | No implementation in this phase. |

### EXACTLY AS-IS

- Package ownership of teams, optional programs, direct memberships, invitations and structural integrity; host ownership of roles, permissions, administrator/owner definitions, panel admission, tenant enumeration and privilege grants/revocation.
- Explicit actor and actual-target policy arguments, direct Laravel Gate enforcement inside shared actions, fail-closed missing policies, and independent authorization on both sides of program/team links. Program membership alone grants no linked-team authority.
- Existing action outcomes, invitation token/expiry/resend/cancel rules, email normalization, immediate addition of existing accounts, and the separate invitation-capability acceptance path.
- User-first locking, same-connection requirements, transactional participant order, creator bootstrap, and observation events/mail/authentication only after successful outer commit. No new retries or side effects in the UI.
- Separate profile/member/invitation/leave abilities, scoped candidate discovery, all-or-nothing membership batches, and direct-request enforcement. Picker eligibility and mutation permission remain separate checks.
- The current supported User subclass integration and deny-default access methods. The package remains one installable, working Laravel/Filament package throughout Phase 2.
- B9 drill-down and pending acceptance for existing accounts remain deferred. A8 integration/tagging/publishing remains separately tracked.

The new action and candidate helper extend the callable API; they do not introduce a new policy ability, participant operation, event, invitation result, privilege backend or configuration key.

## 2. Ordered implementation work

Implement P2.1, then P2.2 and P2.3, then P2.4–P2.6. P2.2 and P2.3 have independent source changes and may be separate commits, but coordinate changes to shared documentation/tests. Keep each extraction and its behavioral coverage together. Do not mark this phase complete from documentation changes alone.

### P2.1 — Reconcile the current roadmap and completion record

**Files:** `docs/plans/meta-plan-monorepo-migration.md`, the current-status/deferral sections of `docs/plans/phase-1-bugfixes-and-config-lockdown.md` and `docs/plans/phase-1b-pre-release-and-review-remainder.md`; current-status annotations in the Track B plans only where they conflict with their September 18 implementation record.

1. Replace the operative meta-plan's Phase 1/1b status and Phase 2 section with a concise completed-work mapping and a link to this plan. Preserve links to the detailed historical plans instead of presenting removed role fixes as future requirements.
2. Mark Phase 1 implementation-complete, A1–A7 historically complete, and B1–B8 implemented in the reviewed checkout. Explicitly distinguish implementation from merge, release and historical verification status; verify remote state with `gh` only if making a new remote-status claim.
3. Record that A5's dependency addition was later intentionally reversed, and that role-bearing invitations, package-admin policies/events, old model wrappers and a backwards-compatible minor refactor are superseded. Update Phase 1's stale deferral ownership accordingly.
4. Revise A8's operative checklist to cover integration of the actual membership-only work, final documentation/verification, and the eventual release. Remove the request to decide whether obsolete admin/role behaviors can be compatible minors and replace obsolete existing-install migration criteria with the accepted fresh-install contract. Preserve the recorded consumer constraints; do not reopen a legacy-consumer migration audit.
5. Reduce Phase 3 to its surviving high-level objective and explicit pending decisions. Remove core-owned privilege policies, invitation role choice, mandatory tenancy-trait work and premature installer/config/release prescriptions. State that detailed planning follows the post-Phase-2 review.
6. Replace mandatory patch/minor/major tags at each phase boundary with separate A8/release tracking. Phase 2 completion requires a verified implementation and review handoff, not a published tag. Retain the broad Phase 4 monorepo direction as pending companion-package validation; do not silently revalidate its package split, tooling or versioning assumptions.

**Acceptance:** there is one current sequence: completed Phase 1/1b implementation → this closeout → full package review → separate Phase 3 plan. Historical documents may retain old decisions only when clearly labeled historical; current instructions must not tell an implementer to restore roles or package administrators. This task does not merge branches, publish releases or rewrite the earlier review report as if it had said something different.

### P2.2 — Extract atomic team creation within a program

**Files:** add `src/Actions/CreateTeamForProgram.php`; change `src/Filament/Support/ProgramTeams.php`; add focused action tests, preferably `tests/Feature/CreateTeamForProgramTest.php`; extend the relevant program-mode UI tests. Update the public contract and user documentation with the new callable action.

**Public API:** container-resolvable `Actions\CreateTeamForProgram::handle(Authenticatable $actor, Model $program, array $data): Model`. Return the newly created configured Team model only when both creation and linking succeed. The supplied program is the actual target; never infer it from the current Filament tenant.

Implementation requirements:

1. Resolve the persisted configured actor through `Membership::actor()`. Require `Membership::type($program) === 'program'`, a persisted program and the same connection before beginning creation; the type check also rejects disabled program mode. Use the existing structural validation helpers and errors; leave authoritative locked-row existence/eligibility checks and mutation authorization to the existing actions. Preflight does not promise that concurrent deletion cannot reach bootstrap participants; their database writes must remain rollback-safe.
2. Use the actor model's connection for a single enclosing transaction with no automatic retry. Inside it call `CreateTeam::handle($actor, $data)`, then `LinkTeamToProgram::handle($actor, $program, $team)`, and return `$team`. Do not copy mutation code or add a second mutation engine.
3. Preserve `create` on the configured Team class, `linkTeam` on the program with the new team, and `linkProgram` on the new team with the program. Link authorization must still happen after creator-bootstrap participants have run, because the host may grant the required team permission during bootstrap. No implicit link permission or program-membership prerequisite is added.
4. Preserve the existing `create_team` and `link_team` participant contexts and the `MemberAdded`/`TeamLinkedToProgram` observations. No additional composite event or participant operation is introduced. Exceptions and model/pivot vetoes must escape and roll back the enclosing transaction.
5. Replace the transaction closure in `ProgramTeams` with one call to the new action. Its form, labels and UI authorization remain adapters. Both the program-panel teams widget and the management program resource's teams relation manager must use that path.

Review the lock trace explicitly: the first action locks the actor before creating a new team; the second reuses the already-held actor lock and obtains existing program/new-team target locks through the shared mutation code. Do not acquire an existing program lock before the actor or expand this into a generic dependent-batch API. Host participants retain the existing same-connection and locking obligations.

**Required behavioral coverage:**

| Scenario | Evidence required |
|---|---|
| Authorized success with host bootstrap | Returned configured team exists; creator membership, program link and same-connection host grant exist. Both existing participant contexts are preserved. |
| `create` denied or policy absent | No team, membership, link, host grant or observation event is created. |
| Either link ability independently denied | Creation and bootstrap have occurred inside the transaction, but all their database writes roll back when linking denies. Exercise each policy separately. |
| Failure after writes | A host participant writes a grant/audit row on the same connection and a later link participant throws; all writes roll back. Also cover a link-pivot veto so a silent failed attach cannot look successful. |
| Caller owns an outer transaction | Neither observation fires before the caller commits; commit emits each once. Caller rollback removes all writes and emits neither. Assert against real outer transactions, not merely mocked action calls. |
| Invalid context | Programs disabled, wrong target type, nonexistent/ineligible program and mismatched connection fail without durable writes. Reuse established validation semantics. |
| Configured models/schema | Prove the new action uses configured classes, tables and keys, reusing custom-schema fixtures where possible. |
| Both UI entry points | Execute successful creation through each surface and at least one denied-link submission through the shared UI path; assert persisted results/rollback, not only action visibility. |

Use `tests/Feature/MembershipActionsTest.php` for existing examples of dual link policies, participant integration, outer transactions and pivot vetoes; `tests/Feature/CustomSchemaTest.php` supplies custom schema patterns. Prefer shared test fixtures over copying the mutation implementation into tests. Characterize the current UI composition where useful; this extraction does not require claiming that the old atomic flow was broken.

**Acceptance:** non-Filament callers can perform this operation with the same atomicity and authorization as the existing UI, and the UI no longer owns its transaction.

### P2.3 — Extract the host candidate-discovery boundary

**Files:** add `src/Support/MembershipCandidates.php`; adapt `src/Filament/Support/Access.php` and `src/Filament/Support/MemberActions.php`; add `tests/Feature/MembershipCandidatesTest.php`; strengthen `tests/Feature/MembershipUiTest.php` and relevant team/program attach tests. Keep `Contracts\UserPicker` unchanged.

Use a small container-resolvable, Filament-independent support class with these instance methods:

| Method | Contract |
|---|---|
| `query(Authenticatable $actor, Model $target): Builder` | Resolve the configured `UserPicker` and call its existing `query($actor, $target)`. Return an empty configured-user query if unconfigured; preserve the explicit configuration error for an invalid implementation. No global auth, current panel or current tenant lookup. |
| `resolveSelection(Authenticatable $actor, Model $target, array $ids): Collection` | Deduplicate submitted IDs, re-query through `query()` and return the selected Eloquent user collection only if every distinct submitted ID is present in that query. Otherwise throw an authorization exception before calling the membership batch; the HTTP adapter renders this as 403. Document the Eloquent collection/model types for analysis. |

Use the existing configured-user key semantics; do not introduce integer-only ID casts or remove host query scopes. An empty selection resolves to an empty collection without mutations; UI required-field validation remains responsible for requiring a selection. Discovery eligibility is checked at resolution time; this extraction does not add a cross-query locking or concurrent picker-policy snapshot guarantee.

`Access::users($target)` may remain as a thin adapter supplying `Access::actor()` to the new helper. `MemberActions` must use the shared query for search and selected labels, and `resolveSelection()` for submitted IDs. Preserve the additional `addMember` filtering on visible options/labels and the `viewMembers` visibility gate. Keep search terms, labels and the 50-result presentation limit in the UI. Do not add another generic authorization-query framework.

After resolution, continue calling `MembershipBatch::handle($actor, 'add_member', $users, $target)`. That action must still authorize each selected user inside its transaction. Direct `AddMember`/`MembershipBatch` callers retain their existing action contract; a UI discovery restriction is not a new universal prerequisite for every membership mutation or email invitation.

**Required behavioral coverage:**

- Direct helper invocation works with an explicit actor and target without a current Filament panel/authenticated UI context. Use different actors/targets to prove the helper passes the supplied context to the host picker.
- Missing picker returns no candidates; an invalid picker implementation fails explicitly. A configured restrictive picker preserves its scopes for query and selected-ID resolution.
- Visible search results and selected labels exclude users outside the picker, including an excluded user whom the host mutation policy would otherwise allow. The same tests prove that picker-visible but policy-denied users are filtered from options/labels.
- Submit an excluded ID, a nonexistent ID, and a mix of eligible and ineligible IDs through a mounted attach action with a restrictive picker and at least one valid candidate. Assert rejection and zero attachments. A hidden action or empty-picker assertion alone is insufficient.
- Change picker eligibility between displaying an option and submitting it; the fresh resolution rejects it. Duplicate valid IDs produce one membership change, not an erroneous count mismatch or duplicate event.
- With IDs inside the picker, make one selected user's `addMember` policy deny at submission; the existing batch rolls back the whole addition. This proves discovery checks did not replace mutation authorization.
- Exercise team and program targets and retain success coverage for the shared attach UI. Use focused helper tests for the full edge-case matrix instead of duplicating every case across every page.

The current test named “returns no picker candidates until a host query is configured and rejects crafted ids” only proves empty candidates and a hidden action. Rename it to match that evidence or extend it with the actual crafted submission above.

**Acceptance:** candidate query resolution and all-submitted-IDs validation are centrally versioned outside `src/Filament`; all existing UI paths delegate to them; mutation authorization remains unchanged.

### P2.4 — Finish the small membership-only cleanup

**Files:** `src/Support/MembershipMail.php`, `tests/Unit/MailableTest.php`, `config/filament-team-management.php`.

- Change the missing-sender fallback from “A site administrator” to **“Someone”**, which works for both team and program mail without inventing a privilege or membership relationship. Update the existing rendered-mail assertion. No new copy framework or sender role lookup is needed.
- Remove the config comment proposing env-backed `names.*` values in Phase 2. Describe the actual shared UI/email display-word use. Leave the config keys, values, env behavior and installer parity unchanged.

**Acceptance:** mail no longer invents an administrator identity when its sender is unavailable, and config does not instruct a later implementer to perform an abandoned expansion. This is copy/comment cleanup; do not rename every `Admin` namespace or `panels.admin` key, which currently identify a UI surface.

### P2.5 — Record the residual boundary inventory and audit

**Deliverable:** create `docs/plans/phase-2-boundary-inventory.md` against the implemented commit. This is evidence and a decision backlog for the full review, not the Phase 3 implementation plan.

For each file or coherent file group, record current responsibility, dependencies on packaged UI/classes/routes/config, retained behavior, existing tests, and disposition: **closed in Phase 2**, **presentation/integration retained for now**, or **specific Phase 3 decision**. Enumerate group members or use precise paths/globs with explicit exceptions so nothing ambiguous disappears under “other support”. Record the exact scanned commit and commands. Distinguish observed dependencies from proposed ownership.

The finite inventory includes:

| Surface | What to record |
|---|---|
| `src/Actions`, `src/Support`, `src/Contracts`, `src/Events`, model classes/interfaces/traits | Retained domain contract, the two new extraction entry points, and any imports of package UI. Keep the existing User subclass contract for this phase. |
| All `src/Filament` pages/resources/widgets/tables/schemas/traits/support | Presentation, delegation, direct-request authorization and remaining composition. Distinguish reusable presentation from shared domain behavior. |
| `MembershipNavigation`, `RegisterResponse` and their callers | Access-aware destination selection, tenant refresh, resource URL and HTML dependencies. Record a future decision on separating host routes/rendering from reusable access handling; leave current navigation working. |
| Auth pages, middleware, `Mail/InviteUser`, `NoMembershipsController` | Default-panel/guard assumptions, acceptance URL generation, post-commit login, registration response and landing behavior. Record coupled decisions without selecting a new routing contract now. |
| Service provider, command, current stubs, `database`, all `resources` and `src/Testing` | Route/view/asset/icon/test-mixin registration, migration/policy publishing and ownership of non-email views. Include files outside `src/Filament`. |
| `config/filament-team-management.php` and consumers | Model/table/FK invariants, runtime settings, mail nouns, panel mapping and candidate/participant contracts. Record UI and non-UI consumers before proposing any deletion. |
| Tests and host fixtures | Which tests exercise core behavior, packaged UI, installer/schema, host policies and transactions; which future generated-app evidence cannot exist yet. |

Perform a bounded scan for raw relationship/model mutations, UI-owned transactions, compound action sequences and server-side checks that would be copied into a scaffold. Inspect matches, including indirect support/HTTP callers. Not every loop, authorization check or UI callback is a domain escape: per-email invitation results, presentation filtering and auth/session wiring have existing contracts.

If another small extraction is necessary to preserve an already-documented invariant, record its evidence, files and focused tests before adding it to the closeout. If it requires a new host-routing contract, tenancy model, installer design, privilege rule or new feature, record it for the review/Phase 3 rather than expanding Phase 2. Any demonstrated authorization, atomicity or data-integrity defect is a blocker for declaring the affected implementation complete; report and fix it with a regression test or explicitly report the phase as blocked on that concrete defect.

**Acceptance:** both known shared-behavior escapes are closed; every remaining mixed component has a specific dependency/decision recorded. Open scaffold design questions are an expected output, not a reason to restructure navigation or build the reference app in this phase. There is no requirement that every Filament callback contain only form layout before this closeout can finish.

### P2.6 — Verify, document and prepare the full-review handoff

**Files:** update `docs/membership-contract.md`, README, SETUP, UPGRADE and CHANGELOG where the added APIs or changed copy affect their statements. Update `CLAUDE.md` to distinguish shared candidate enforcement from the remaining Filament selector presentation. Create `docs/change-logs/phase-2-bounded-hardening-closeout.md` with the exact delivered scope, commits, decisions, test commands/results, skipped checks and remaining work. Update this plan's status only from recorded evidence.

Document the new action signature, existing policy checks and participant/event sequence. Document the candidate helper's discovery contract separately from mutation authorization. Avoid turning extraction-only changes into new install steps or telling hosts to implement another permission system.

Run the focused tests while implementing, then run the following against the completed change:

```sh
composer test -- --compact --colors=never
composer analyse
vendor/bin/pint --test
git diff --check
```

Use the existing suite's team-only/program-mode, custom-schema, host-authorization, invitation, registration, navigation and installer checks as regression coverage. Run Composer validation only if package metadata/dependencies change. Do not require unrelated dependency updates or browser visual QA for this extraction-only phase.

Re-run the existing opt-in MySQL/InnoDB concurrency suite using the documented `FTM_TEST_MYSQL_*` setup when available; it checks the shared locking baseline. It does not, by itself, prove the new composite action's race behavior. If implementation changes the lock algorithm/retry behavior or review identifies a concrete untested race, add a focused database-backed test for that scenario before accepting the change. If the backend is unavailable, record that gap explicitly; never describe SQLite or expected skips as fresh row-lock evidence. The full review must decide whether the gap matters to the actual diff.

Check links, referenced symbols, Markdown formatting and current-status consistency. Keep prose unwrapped. Do not count this document's planned tests as executed evidence.

**Full-review handoff:** implementation and independent verification complete, then review the package as a whole before Phase 3 planning. Review coverage must include the membership-only public contract, host-policy enforcement, candidate privacy, schema/config indirection, participant/locking/commit behavior, invitations/account lifecycle, Filament adapters and the residual inventory—not only the Phase 2 diff. Separate architectural findings, code/documentation gotchas and demonstrated runtime bugs. Save that review under `docs/code-reviews` and prioritize any required fixes explicitly.

## 3. Completion and stopping criteria

- [ ] P2.1: current roadmap/status reconciled; old role/admin/minor-refactor requirements are historical only.
- [ ] P2.2: the shared composite action is implemented, both UI callers delegate, and rollback/authorization/outer-commit coverage passes.
- [ ] P2.3: candidate query and submitted-ID validation are shared; actual crafted-submission and per-user denial coverage passes.
- [ ] P2.4: sender fallback and stale config comment cleaned up without behavior/config expansion.
- [ ] P2.5: the finite boundary inventory and residual audit are complete, with concrete evidence and deferred decisions.
- [ ] P2.6: focused/full checks and independent verification recorded; public docs and implementation log reflect the delivered behavior.
- [ ] The implementation is ready for full review, with no unresolved demonstrated defect hidden as a Phase 3 design question.

At that point mark **“Phase 2 implementation complete; full review pending”** and stop implementation. After the full review and any required fixes, record **“Phase 2 reviewed; ready for separate Phase 3 planning”**. Do not silently treat review readiness as review completion or start scaffold conversion. A8 release is a separate status throughout.

The later review may challenge the proposed one-package, UI-free but Filament-aware core. Phase 2 preserves that direction without committing to detailed navigation/URL contracts, a new User trait, retained UI adapters, scaffold namespaces, overwrite semantics, config shrink, optional panel topology, reference-host layout or release sequencing. Those belong to the separate Phase 3 planning discussion.

## 4. Explicit exclusions

No scaffold generation, UI publishing/removal, new panel topology, navigation/auth rewrite, extra package split, monorepo setup, ODK integration, reference-app build, consumer migration, B9 drill-down, existing-account pending-acceptance feature, package roles/permissions/admin definitions, last-owner rules, generic audit/outbox infrastructure, performance rewrite of visibility queries, or legacy data-upgrade tooling. No mandatory tag, merge or release is part of executing this plan.

## 5. Planning evidence and limits

This plan was prepared from the September 18 review and live inspection of the action/support/UI boundaries and existing tests at `b40c711`. The earlier review ran **167 passing tests, 637 assertions and four expected opt-in concurrency skips**; those are prior baseline results, not verification of the work proposed here. No runtime code was changed and no runtime checks were rerun while drafting this plan. Mechanical document checks and independent plan review should be recorded with delivery of the plan.
