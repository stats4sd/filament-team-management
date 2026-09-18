# Phase 2 bounded hardening closeout

**Date:** 2026-09-18. **Status:** Phase 2 reviewed; ready for separate Phase 3 planning. Independent full-package source review and independent execution of final checks are complete. Continued the existing `membership-only` branch from `9f6a2b5`. This is an implementation/evidence record, not a release announcement. A8 release, scaffold conversion and separate Phase 3 planning remain outside the delivered implementation.

## Delivered scope and commits

| Commit | Scope |
|---|---|
| `e609c65` | P2.1: reconciled the operative roadmap and historical completion/deferral records. Phase 1/A1–A7 and B1–B8 implementation are complete; old package roles/admins, wrappers and minor-refactor requirements are superseded. Consumer constraints retained; A8 tracks integration/release separately. |
| `6e4b439` | P2.2: CreateTeamForProgram, ProgramTeams delegation, direct atomicity/authorization/participant/outer-commit tests, configured schema and actual creation through both UI surfaces. |
| `47dab0f` | P2.3: MembershipCandidates, thin Access adapter, shared selected-ID resolution and mounted candidate privacy/authorization coverage. |
| `08514e2` | P2.4: neutral “Someone” sender fallback and actual shared UI/email display-word config comment; no config behavior change. |

Review corrections were committed as `6ebeddd` (authorized Program fallback and navigation regressions) and `6643ebf` (current deletion graphs and the expanded MySQL regressions). The final documentation-only commit carries this log, the completed plan, public docs, inventory and full-review report; it does not change the verified runtime tree. The inventory pins the complete source revision `6643ebf7ffe37f71ade94b3dc717b5eb4c6baf2b`. The finite [boundary inventory](../plans/phase-2-boundary-inventory.md), [public contract](../membership-contract.md), README, SETUP, UPGRADE, CHANGELOG and contributor guidance describe the final API. The [full-package review](../code-reviews/2026-09-18-phase-2-membership-package-review.md) separates architecture, gotchas and reproduced runtime bugs.

## Implementation decisions

- CreateTeamForProgram composes the existing actions in one single-attempt actor-connection transaction. Program preflight does not claim to prevent concurrent deletion before bootstrap; same-connection participant writes remain rollback-safe. Creator bootstrap precedes dual link authorization. No new action kernel, ability, participant operation or event was added.
- MembershipCandidates receives explicit actor/target context, preserves picker scopes and configured string/integer keys, resolves every distinct submitted ID and raises AuthorizationException for unavailable IDs. Empty selection is valid in the helper; required selection is a UI rule. MembershipBatch still authorizes each selected user's mutation inside its transaction.
- Mounted tests exposed Filament's automatic selected-label validation: it intercepted excluded IDs with HTTP 200 field errors and rejected duplicate valid IDs before shared authorization ran. The Select now supplies submitted `in` values so the shared resolver and batch perform the planned checks. Required selection, filtered labels/search, result limit and viewMembers remain. Tests prove a first attachment exists inside a transaction before a later policy denial rolls the entire batch back.
- Both extractions were boundary improvements to existing implementations, not claims that the previous UI create/link transaction lacked atomicity.
- Full-package review reproduced a pre-existing InnoDB target-deletion integrity defect and a program-only landing bug. P2.5 explicitly requires demonstrated defects to be corrected, so these were added as bounded regression fixes and recorded in the plan. They do not select new routing, tenancy, scaffold or privilege contracts.

## Recorded checks before final verification

| Command / evidence | Result |
|---|---|
| `vendor/bin/pest tests/Feature/CreateTeamForProgramTest.php tests/Feature/CustomSchemaTest.php tests/ProgramMode/Feature/ManageTablesRenderTest.php --compact --colors=never` | 27 passed, 211 assertions. |
| `vendor/bin/pest tests/Feature/MembershipCandidatesTest.php tests/Feature/MembershipUiTest.php tests/ProgramMode/Feature/MembershipCandidatesUiTest.php --compact --colors=never` | 27 passed, 143 assertions. |
| `vendor/bin/pest tests/Unit/MailableTest.php tests/Feature/MembershipActionsTest.php --compact --colors=never` | 41 passed, 233 assertions. |
| Initial `composer analyse` and `vendor/bin/pint --test` after extractions | Passed; final locking changes require fresh verification. |
| Original opt-in MySQL suite on isolated port 33317 | 4 passed, 16 assertions. This did not prove the newly identified deletion race. |
| Deterministic target-deletion race probe on original kernel | 2 failed, 12 assertions (team and program). Expanded suite: 4 passed, 2 failed, 27 assertions. Deletion cascaded the added member but left its host grant and emitted no removal event. |
| Program-only navigation regressions before fix | 2 failed, 5 passed, 23 assertions; actual Program URL expected, no-memberships URL received. |
| `vendor/bin/pest tests/CustomPanels/NavigationTest.php tests/Feature/RegistrationTest.php tests/Feature/MembershipUiTest.php --compact --colors=never` after fallback fix | 34 passed, 161 assertions. |
| Intermediate full suite before navigation correction | 203 passed, 2 known navigation regression failures, 6 opt-in concurrency skips, 921 assertions. Not final acceptance evidence. |

## Final verification and limits

The independent reviewer found no blocking issue after re-reviewing both demonstrated fixes. A separate verifier executed the completed runtime tree at `6643ebf`:

| Command | Final independent result |
|---|---|
| `composer test -- --compact --colors=never` | **206 passed, 924 assertions, 16 expected opt-in concurrency skips**, 23.99 seconds, seed `1789738083`. |
| `composer analyse` | **Passed**, no errors across 111 files. |
| `vendor/bin/pint --test` | **Passed**. |
| `git diff --check` | **Passed**. |
| `FTM_TEST_MYSQL_HOST=127.0.0.1 FTM_TEST_MYSQL_PORT=33317 FTM_TEST_MYSQL_USER=root vendor/bin/pest tests/Concurrency --compact --colors=never` | **16 passed, 96 assertions**, 8.27 seconds, seed `1789738102`. The implementation run also passed all 16 cases with 95 assertions; the existing race-dependent outcome assertion explains this count variation. |
| Focused kernel/composite/schema regression after integrity fix | **56 passed, 390 assertions**. |
| Mechanical docs and inventory | Local link targets resolve; public symbols exist; prose remains unwrapped; status/deferral instructions reconciled. Final inventory scan: 182 files, 93 mutation/check matches, 482 dependency matches and 49 extraction/navigation caller matches. |

The expanded MySQL cases establish safe rejection for newly discovered unlocked graph members/links, actual grant cleanup and removal observations when a newly visible member was already locked, account deletion under an older caller snapshot, and current membership/link idempotence/removal. This is new database evidence for the changed locking reads, not reuse of the original four-case baseline.

MySQL testing uses a disposable local MySQL 9.7.1/InnoDB instance at 127.0.0.1:33317 and creates/drops isolated databases via the existing harness. Sandbox initialization initially crashed; unsandboxed initialization succeeded. Final server metadata confirmed MySQL 9.7.1, REPEATABLE-READ and InnoDB; no `ftm_test_*` databases remained, and the disposable server was shut down. No existing application database was used. SQLite is not fresh row-lock evidence. MySQL 8.x/MariaDB and browser visual QA are not claimed. Composer validation is omitted because package metadata/dependencies are unchanged. No dependency updates, release tag or publishing is required.

## Remaining work

Implementation, independent verification and full-package review are complete; both demonstrated defects are resolved. The later separate Phase 3 plan must decide the concrete navigation/auth/mail URL boundary, retained UI adapters versus scaffolds, User integration, installer/config behavior, panel topology, reference-host design and eventual monorepo coordination. B9 drill-down, existing-account pending acceptance and A8 integration/release remain separately tracked. No consumer migration audit has been reopened.
