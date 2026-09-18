# Track B implementation contract

Date: 2026-09-18. Implements the revised [Track B plan](phase-1b-track-b-membership-only.md). B9, existing-account pending acceptance, release publishing and the monorepo move remain deferred.

## Clarity and consistency decisions

- Team and program membership use the same action signatures, member/invitation table builders and invitation result presenter. A host learns one contract for both target types.
- `users()` is the canonical direct membership relation; `members()` remains an unfiltered alias. Invitations use explicit pending/accepted/expired predicates instead of a hidden global scope.
- Public actions accept an explicit actor first, then the actual target and affected record. Token acceptance is the sole capability-based exception. No action derives authority from the selected tenant or request state.
- Optional `user_picker` supplies a host-authorized query through `Contracts\UserPicker::query(actor, target)`. Its default is an empty query. Candidate selection never substitutes for the action's `addMember` authorization.
- `MembershipBatch` prelocks all affected users and targets before applying a bulk operation. Looping single-record transactions is not sufficient for atomic bulk denial or consistent lock ordering.
- Shared invitation feedback describes persisted outcomes and queued notification delivery separately. An invitation being created does not claim mail has been delivered.
- Membership profile access checks `view`; editing, member lists, invitation lists and leaving each check their own ability. Leaving a final membership has an authenticated non-tenant destination.

These refinements make the original plan's requirements concrete; they do not introduce a package-owned authorization backend.

## Public action contract

Actions live in `Stats4sd\FilamentTeamManagement\Actions` and expose instance `handle()` methods resolved through the container.

| Action | Arguments | Result |
|---|---|---|
| `SendMembershipInvitation` | actor, target, nullable email | `InvitationResult` with status, email, invite, user |
| `AddMember`, `RemoveMember` | actor, target, member | changed boolean |
| `LeaveMembership` | actor, target | changed boolean |
| `CreateTeam`, `CreateProgram`, `CreateUser` | actor, data | created model |
| `UpdateTeam`, `UpdateProgram`, `UpdateUser` | actor, target, data | updated model |
| `DeleteTeam`, `DeleteProgram`, `DeleteUser` | actor, target | changed boolean |
| `LinkTeamToProgram`, `UnlinkTeamFromProgram` | actor, program, team | changed boolean |
| `ResendMembershipInvitation` | actor, target, invite | refreshed invite |
| `CancelMembershipInvitation` | actor, target, invite | changed boolean |
| `AcceptMembershipInvitation` | token, registration data | created user |
| `MembershipBatch` | actor, operation, records, optional target | results array |

Batch operations are `add_member`, `remove_member`, `delete_team`, `delete_program`, `delete_user`, `link_team`, `unlink_team`. Invitation submission reports each email separately. Participants implement `before(MembershipContext)` and `after(MembershipContext)`; both run in the transaction, and `after` means after persistence but before commit.

## File ownership and execution

1. Core: models except User, interfaces, new actions/contracts/events/support, mail templates/mail classes and invitation registration page.
2. UI and access: remaining Filament surfaces, User, middleware, response/navigation helpers and no-memberships destination.
3. Installation: configuration, migrations, factories, seeders, installer, provider and published policy stubs.
4. Integration: Composer dependencies, host test fixtures/regressions, docs, static analysis and cross-layer repairs.
5. Independent review followed by independent test verification. Validate both program modes, custom classes/tables/FKs, policy deny/allow, rollback/events/mail, Livewire calls and actual production-database concurrency. Run the full Pest suite, PHPStan and Pint after integration.

The baseline before implementation passes 119 tests with 320 assertions. Implementation and verification results will be recorded in the [implementation log](../change-logs/phase-1b-track-b-membership-only.md).

## Review refinements implemented

- Self-removal through direct and bulk APIs canonicalizes to `leave`; a host cannot accidentally allow departure merely by granting `removeMember`.
- Program departure prefers another accessible program; team departure prefers another accessible App team. Both use authorized registration/fallback paths, and bulk self-removal refreshes navigation after success.
- Host visibility scopes cannot hide structural records from deletion cleanup/hooks. Direct actors, members and targets still require eligibility; soft-deleted direct records are rejected.
- Cancelled Eloquent model saves/deletes and custom pivot attach/detach vetoes fail atomically. Pivot postconditions are checked before participant after-hooks and observation events.
- Registration authentication/session regeneration waits for the outer transaction commit. Invitation feedback distinguishes awaiting commit, queued, sent and failed dispatch.
- Profile labels, resource nouns, user/team summaries and member/invitation tables remain consistent. User-form membership editing is directed to the target membership surface so the same policy/action contract applies everywhere.
- The accepted-invitations checkbox controls the table query explicitly; an inactive Filament filter cannot accidentally reveal accepted history by default.
