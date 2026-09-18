# Phase 2 membership package review

**Date:** 2026-09-18. **Status:** accepted after bounded corrections and independent verification; no blocking findings remain. Baseline `9f6a2b5`; extraction commits `6e4b439` and `47dab0f`; copy/config cleanup `08514e2`. Final runtime revision is `6643ebf7ffe37f71ade94b3dc717b5eb4c6baf2b`; navigation correction is `6ebeddd`. Documentation was finalized afterward without runtime changes. This report preserves the distinction between source review, executable reproduction and final acceptance.

## Scope and method

An independent read-only reviewer examined the Phase 2 diff and the entire membership package: explicit actor/target host-policy enforcement, direct and batch mutation, picker privacy, model/schema/config indirection, participants and locks, outer-commit observations, invitation and account lifecycle, Filament pages/resources/tables, registration/navigation/mail, provider/installer and the [finite boundary inventory](../plans/phase-2-boundary-inventory.md). Runtime probes and final checks were executed separately. This is not a review only of the two new entry points.

The review found no authorization or atomicity defect in CreateTeamForProgram or MembershipCandidates. Creator bootstrap precedes dual link authorization, transaction ownership has moved out of the UI, scoped query and selected-ID resolution are shared, and mutation batches still authorize each user. The installed Filament Select validation was inspected: explicit submitted `in` values let the shared resolver and batch execute while required selection and filtered search/labels remain. Mounted tests establish both 403 rejection and transactional denial, rather than relying on hidden actions.

## Architectural findings

These are later design decisions, not reproduced runtime defects:

- **Auth/navigation/mail ownership:** MembershipNavigation mixes access selection, Filament context, resource URLs and escaped HTML; RegisterResponse imports it; InviteUser generates the default-panel registration URL. Phase 3 must choose how host routes/rendering relate to reusable access logic without weakening checks.
- **User integration:** the configured User subclass implements Filament admission/tenant/default-tenant contracts. A trait or alternative integration is not automatically required; decide with a real host after this review.
- **UI and installation ownership:** shared table adapters, page/resource scaffolds, non-email views, provider route/assets/test registration, installer namespace/overwrite behavior and config consumers require explicit decisions. The inventory enumerates them; no scaffold conversion or deletion is implied by this review.
- **Registration integration payload:** RegisteredWithData intentionally retains its separately documented synchronous credential-bearing payload. This requires deliberate host handling and later contract review; it is not newly introduced by Phase 2 and was not presented as credential-free membership observation.

## Code and documentation gotchas

- Filament's implicit selected-label validation previously rejected duplicate valid IDs and intercepted unavailable IDs as field errors. The attach adapter now lets MembershipCandidates enforce discovery and MembershipBatch enforce mutation authorization. It retains required-field validation and uses configured model keys for option labels.
- Picker eligibility is rechecked when resolving submitted IDs but does not establish a locked cross-query eligibility snapshot. Direct action callers are not required to pass through the picker. This is the documented discovery/mutation boundary.
- Initial deletion graph enumeration is advisory. A fresh in-transaction validation query is not necessarily a current read under InnoDB REPEATABLE READ. SQLite and the earlier four race cases did not establish that property.
- The fresh-install contract supersedes historical role/admin/minor-release instructions; old planning and merge/test counts are retained only as dated evidence. A8 release remains separate from Phase 2 and this PR.

## Demonstrated runtime bugs and required corrections

### 1. High priority: target deletion misses a concurrent membership under repeatable read

**Original path:** `Support/MembershipMutation.php::run()` reads target users before locking, then repeats ordinary relationship reads for graph checking and deletion traversal. The first read establishes an InnoDB snapshot. An unrelated actor can add a member and host grant before the deletion obtains its target lock; later ordinary reads can still see the old graph.

**Reproduction:** deterministic MySQL 9.7.1/InnoDB tests pause DeleteTeam or DeleteProgram immediately after the initial member enumeration, commit AddMember plus a same-connection host grant from another worker, and resume deletion. Both tests failed on the original kernel: deletion returned true, target and membership pivot disappeared, the host grant remained, and no MemberRemoved observation was emitted. The focused run reported **2 failed / 12 assertions**; the expanded baseline was **4 passed, 2 failed / 27 assertions**. This is confirmed runtime evidence, not a static-analysis inference.

**Required correction:** authoritative current reads of pivot identifiers after the existing user/target locks; reject graph growth outside the held locks before participants; consume the validated graph for cleanup. Attachment existence/postcondition checks must also use current pivot state. Do not acquire joined User locks after target locks or automatically retry participants. Account deletion inside an already-established caller snapshot and link graphs need focused coverage because they share the same mechanism.

### 2. Medium priority: program-only membership cannot be reached after registration or final-team departure

**Original path:** RegisterResponse calls MembershipNavigation with the default team departure mode; that mode only considered the App panel. A new user with an accepted Program membership and no Team/create permission reached “No memberships available”. A member leaving their final Team while retaining a Program encountered the same result.

**Reproduction:** two custom-panel regression tests failed with the no-memberships URL instead of the authorized Program URL. The registration case actually accepted the invitation, authenticated the user and verified Program membership; it did not merely mock the response.

**Required correction:** retain App-first precedence and add the existing configured Program panel as fallback, preserving program-mode, model, panel-admission, tenant and view-policy checks and restoration of the previous panel. No new host routing contract is necessary.

## Resolution and final verification

Both demonstrated defects are resolved. The independent reviewer re-examined the production corrections and their regressions and found no blocking issue. Current pivot reads occur after existing user/target locks, validate graph membership before participants and drive actual cleanup; no new late User locks or automatic retries were introduced. The navigation fallback preserves existing App-first precedence and every access check.

A separate verifier ran the final runtime tree: **206 passing tests / 924 assertions / 16 expected opt-in skips**, PHPStan clean across 111 files, Pint clean and diff whitespace clean. The expanded real MySQL suite independently passed **16 tests / 96 assertions**. It covers the reproduced race, already-held member cleanup, caller-owned account-deletion snapshots, association graph growth and direct membership/link stale-snapshot behavior. The [closeout implementation log](../change-logs/phase-2-bounded-hardening-closeout.md) records exact commands, commits and limits. No release, merge, scaffold implementation or Phase 3 design selection is part of this review.
