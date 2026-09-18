# Phase 1b Track B: membership-only implementation

Date: 2026-09-18. Implements B1–B8 of the [revised Track B plan](../plans/phase-1b-track-b-membership-only.md), with the public APIs specified in the [implementation contract](../plans/phase-1b-track-b-implementation.md). B9 remains deferred. This is an implementation and verification record, not a release announcement.

## Result

The package owns membership structure and lifecycle. Host applications own authorization, panel admission, accessible tenants, administrator definitions and grant lifecycle. Shared actions require an explicit actor and enforce the host's Laravel Gate/policy decisions against the supplied target. Missing abilities deny access. The base User model denies panel/tenant access until the host implements its access rules.

Removed the Spatie permission and Althinect integration dependencies, role/permission resources, permission middleware, role-bearing invitations, role pivot adapter, package administrator flags and implicit program-to-team access. Retained `spatie/laravel-package-tools`, which is unrelated to permissions. Dependency resolution, installation and Testbench application tests run without the removed packages installed.

## Delivered scope

| Area | Implemented behavior |
|---|---|
| B1: authorization | Explicit host abilities; deny-default optional policy stubs; per-record authorization in shared actions and bulk operations; independent read, edit, member-list, invitation-list and leave checks; host-controlled user pickers. |
| B2: invitations | One team/program invitation action and result contract; existing accounts are added immediately with both required checks; new accounts receive real pending invitations; duplicate, expired, already-member and blank outcomes remain distinct. |
| B3: actions and integration | Shared CRUD/membership/link actions, atomic batches, transactional participants, user-first deterministic locking and eight credential-free after-commit membership/invitation events. Registration retains its documented registration-event contract. |
| B4: lifecycle | Expiry, token rotation on resend, cancellation, atomic acceptance and accurate accepted-history filtering; no synthetic accepted invitation rows for direct additions. |
| B5: tenancy | Host-owned access enumeration and direct checks; supplied-panel default-tenant selection; middleware type guards and dirty checks; safe navigation when remembered membership becomes unavailable. |
| B6: UI | Shared membership/invitation presentation; consistent configured labels and panel IDs; explicit tenant URLs; read-only profiles; leave and bulk removal; retained program team descriptions and linking; accurate deletion copy. App direct attach is removed while email invitations remain. |
| B7: accounts and mail | Nullable inviter, immutable queued-mail snapshots and sender fallback; authorized account deletion with membership cleanup/hooks; explicit soft-delete boundaries; current-record email uniqueness. |
| B8: installation | Fresh role-free schema, unique membership/link/token constraints, configured invites table and custom FKs; both migration modes and rollback; optional non-overwriting policies; dotenv-parser-tested values; syntax-aware, idempotent seeder insertion. |

Queue dispatch is the default and occurs after the outermost successful transaction commit. Membership persistence and mail delivery have separate outcomes. Synchronous delivery is an explicit configuration choice. Participants run inside the transaction and are not automatically retried; observation events do not promise durable exactly-once delivery.

## Changes made for clarity and consistency

- Standardized team and program action signatures, tables and invitation feedback so hosts and users learn one membership workflow. `users()` and `members()` both represent all direct members; neither silently means administrators.
- Canonicalized self-removal to `leave`, including bulk and direct API callers. The same departure policy applies regardless of which screen or action initiated it.
- Kept profile viewing independent from editing and membership management. A read-only member can view permitted information and leave without receiving update permission.
- Removed membership relationship editing from the global User form and directed membership changes through the target's membership surface. This gives every entry point the same authorization, transaction, hook and event behavior.
- Made an unconfigured existing-user picker empty and non-actionable. A host explicitly chooses its visible candidate query, and the action still authorizes the selected member.
- Separated persisted invitation results from pending, queued, sent or failed mail dispatch. Feedback no longer claims that creating an invitation proves email delivery.
- Made departure destinations predictable: another accessible tenant of the relevant panel first, then authorized registration or an authenticated non-tenant landing page. Ordinary members are not sent to an inaccessible Admin list.
- Used configured nouns consistently, including program team-link selectors, deletion wording and membership tabs. Program deletion explains that linked teams remain while program associations and invitations are removed.

## Review findings resolved

Independent core and UI reviews were followed by fixes and independent verification. Reviews identified several cases that happy-path tests alone did not establish:

- Eloquent save/delete listeners and custom pivot listeners can veto writes without throwing. Actions now check results/postconditions and roll back instead of publishing a successful transition.
- Host global visibility scopes can hide structural memberships during deletion. Cleanup enumerates the full structural graph, while direct operations still reject ineligible or soft-deleted actors, members and targets.
- Registration authentication could otherwise outlive an enclosing rollback. Login and session regeneration now wait for the outer transaction commit.
- An inactive Filament filter does not run its query callback. Accepted invitations are now excluded by the base table-query modification and included only when the history option is selected.
- Compound program team creation/linking uses the actor's database connection and one transaction. Bulk self-removal refreshes tenant navigation after success.

## Verification

| Check | Result |
|---|---|
| Baseline before implementation | 119 tests passed, 320 assertions. |
| Original default suite: `vendor/bin/pest --compact --colors=never` | 167 passed, 637 assertions; four opt-in concurrency tests intentionally skipped in this invocation. |
| Historical PostgreSQL concurrency suite (before the MySQL replacement) | Four passed, 15 assertions, against an isolated local PostgreSQL 18.3 instance. |
| Replacement MySQL concurrency suite | Four passed, 15 assertions, against a disposable local MySQL 9.7.1 instance using InnoDB and the default `REPEATABLE READ` isolation. |
| Static analysis: `composer analyse -- --no-progress --debug` | Passed with no errors. Serial debug execution avoids the sandbox restriction on PHPStan's parallel worker socket. |
| Formatting: `vendor/bin/pint --test` | Passed after formatting the final regression test. |
| Composer manifest: `composer validate --strict` | Passed. |
| Dependency resolution/install | Removed both permission packages; Composer install completed and the test applications booted without them. |

After the MySQL replacement, `composer test -- --compact --colors=never` passed with 167 tests, 637 assertions and four expected opt-in skips. `composer analyse -- --no-progress --debug`, `vendor/bin/pint --test` and `git diff --check` also passed. Temporary setup and worker-initialization failure probes produced the expected errors and left no disposable databases or worker directories; the test file was restored byte-for-byte after the probes. A separate review found no actionable defects. MySQL 8.x and MariaDB were not exercised in this run.

The concurrency tests now use MySQL with InnoDB, matching the primary database used by consuming applications. They retain the four scenarios: duplicate invitation/member/link writes, resend versus acceptance, account deletion versus addition to a previously unrelated target, and concurrent departures of the last host-defined administrators. The last-administrator fixture includes an ordinary member, so it verifies the host grant invariant rather than merely preserving the final membership. Each test creates and drops a unique database, using the server's normal transaction isolation. The PostgreSQL result above records the earlier run and is not evidence of MySQL verification.

To run these tests, set `FTM_TEST_MYSQL_PORT` and optionally `FTM_TEST_MYSQL_HOST` (default `127.0.0.1`), `FTM_TEST_MYSQL_USER` (default `root`) and `FTM_TEST_MYSQL_PASSWORD`, then run `vendor/bin/pest tests/Concurrency --compact --colors=never`. Use a test server and an account allowed to create and drop databases. PHP requires `pdo_mysql`, `pcntl` and `posix`; the default SQLite suite skips these four tests when the MySQL port is unset.

Regression coverage includes missing/denying/allowing policies and an explicit host global bypass; configured model subclasses; simple host-owned manager flags and separately stored scoped grants; rollback and revocation/rejoin; two-program overlap with explicit host-chosen inherited access; token tampering and stale acceptance; model/pivot vetoes; outer-commit events/mail/authentication; soft-delete and global-scope cleanup; direct crafted Livewire actions; custom panel IDs/paths/slugs; accepted-history visibility; both program modes; full custom model/table/FK migration and workflow tests; installer reruns, policy preservation and actual dotenv parsing. UI verification uses Livewire/rendered-component tests; a browser visual review is not claimed. Database concurrency evidence is limited to the versions recorded here, not every supported database engine.

## Integration and remaining scope

Host setup and working examples are documented in [SETUP](../../SETUP.md) and the [membership contract](../membership-contract.md), with breaking changes in [UPGRADE](../../UPGRADE.md) and [CHANGELOG](../../CHANGELOG.md). Hosts must implement policies and matching panel/tenant access, and supply participants if they need bootstrap grants, revocation, durable auditing or last-administrator rules. A host may continue using Spatie internally; the package does not install or configure it.

B9 program-panel member drill-down, pending acceptance for existing accounts, the core/scaffold split, monorepo migration and A8 release publishing remain outside this implementation. No existing application database was rebuilt, no legacy upgrade migration was introduced, and no release was tagged or published. The implementation log and plan documents are ordinary repository files; Composer's local lock file remains ignored by the existing repository configuration.
