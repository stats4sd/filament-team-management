# Membership authorization and workflow contract

The package stores membership facts. The host defines privileges, who may enter each panel, accessible tenant queries, administrator/owner grants and any last-administrator invariant. Policies resolve through Laravel Gate against the actual configured model class. A missing method denies; there is no package global administrator bypass.

## Abilities

Policy methods receive the actor first. The table lists subsequent arguments. For class abilities, pass the configured class to Gate; Laravel resolves its policy and invokes the method with only the actor.

| Ability | Arguments after actor | Meaning |
|---|---|---|
| `viewAny`, `create` | None; Gate receives configured model class | List/create Team, Program or User |
| `view`, `update`, `delete` | Target model | Record access and mutation |
| `viewMembers`, `viewInvitations` | Team or Program | Read the corresponding membership tab |
| `inviteMember` | Team or Program | Use the email invitation workflow |
| `addMember`, `removeMember` | Team or Program, affected User | Directly change another membership |
| `leave` | Team or Program | Remove the actor's own membership |
| `resendInvitation`, `cancelInvitation` | Team or Program, Invite | Change a matching unaccepted invitation |
| `viewTeams` | Program | Read linked teams, each also subject to `view` |
| `linkTeam`, `unlinkTeam` | Program, Team | Program side of an association |
| `linkProgram`, `unlinkProgram` | Team, Program | Team side of an association |
| `deleteAny` | None; Gate receives configured User class | Show global User bulk delete; each User still checks `delete` |

Example: `Gate::forUser($actor)->authorize('removeMember', [$team, $member])` invokes `TeamPolicy::removeMember($actor, $team, $member)`. Both sides authorize link/unlink. A program that shares a team with another program gains no automatic authority over that team.

## A simple host administrator/member policy

A small app can add its own `is_manager` column to its own membership schema and use it consistently. This column is not supplied or interpreted by the package. In your host Team model, extend `users()` with `parent::users()->withPivot('is_manager')`. Register this policy for the configured Team class and adapt the same pattern for programs if wanted:

```php
class TeamPolicy
{
    private function manages(User $actor, Team $team): bool
    {
        return $team->users()->whereKey($actor->getKey())->wherePivot('is_manager', true)->exists();
    }

    public function view(User $actor, Team $team): bool
    {
        return $team->users()->whereKey($actor->getKey())->exists();
    }

    public function viewMembers(User $actor, Team $team): bool { return $this->view($actor, $team); }
    public function viewInvitations(User $actor, Team $team): bool { return $this->manages($actor, $team); }
    public function update(User $actor, Team $team): bool { return $this->manages($actor, $team); }
    public function inviteMember(User $actor, Team $team): bool { return $this->manages($actor, $team); }
    public function addMember(User $actor, Team $team, User $member): bool { return $this->manages($actor, $team); }
    public function removeMember(User $actor, Team $team, User $member): bool { return $this->manages($actor, $team); }
    public function leave(User $actor, Team $team): bool { return $this->view($actor, $team); }
    public function resendInvitation(User $actor, Team $team, Invite $invite): bool { return $this->manages($actor, $team); }
    public function cancelInvitation(User $actor, Team $team, Invite $invite): bool { return $this->manages($actor, $team); }
    public function create(User $actor): bool { return $actor->may_create_teams; }
}
```

`User`, `Team` and `Invite` here denote your configured User/Team classes and the package Invite. Methods omitted from this example deny. Add deliberate `viewAny`, `delete` and link policies if the app exposes those operations. Use a participant to bootstrap the creator's manager flag after `create_team`, and validate last-manager departure before removal. Ordinary members retain `viewMembers` and `leave` independently of `update`.

A richer host can replace `manages()` with a scoped grant query keyed by the supplied target. If it uses Spatie, keep the dependency and role configuration in the host. Within the policy, set the permission scope from `$team->getKey()`, clear cached role/permission relations when switching scope, evaluate the host's permission, and restore the previous scope in `finally`. Never derive this decision solely from `Filament::getTenant()`: an Admin or Program screen may act on another team. Cross-program inheritance and grantability are host decisions.

## Existing-user selectors

Implement `Contracts\UserPicker::query(Authenticatable $actor, Model $target): Builder` and set `user_picker` to the class name. Return an Eloquent query of configured users the actor may discover for this target. The package returns zero candidates without it. Search results and selected labels use this query; submitted IDs are revalidated, and `addMember` is still checked on every selected user inside the transaction. Do not return a global user directory merely because the actor can invite by email.

## Actions

All actions are container-resolvable classes in `Stats4sd\FilamentTeamManagement\Actions`, with an instance `handle()` method. Management actor parameters implement `Authenticatable` and must be persisted configured User models; targets are actual configured Eloquent models.

| Action | Arguments | Result |
|---|---|---|
| `CreateTeam`, `CreateProgram`, `CreateUser` | actor, data | Model |
| `UpdateTeam`, `UpdateProgram`, `UpdateUser` | actor, target, data | Model |
| `DeleteTeam`, `DeleteProgram`, `DeleteUser` | actor, target | boolean |
| `AddMember`, `RemoveMember` | actor, target, member | changed boolean |
| `LeaveMembership` | actor, target | changed boolean |
| `LinkTeamToProgram`, `UnlinkTeamFromProgram` | actor, program, team | changed boolean |
| `SendMembershipInvitation` | actor, target, email | `InvitationResult` |
| `ResendMembershipInvitation` | actor, target, invite | Invite |
| `CancelMembershipInvitation` | actor, target, invite | changed boolean |
| `AcceptMembershipInvitation` | token, registration data | Created configured User |
| `MembershipBatch` | actor, operation, records, optional owner target | Results array |

Batch operations are `add_member`, `remove_member`, `delete_team`, `delete_program`, `delete_user`, `link_team` and `unlink_team`. Authorization or participant failure rolls back the entire batch. UI email lists are processed per address and report each result; they are not an atomic batch promise.

Email normalization is trim plus lowercase; no provider-specific equivalence is inferred. Use that same normalization for every host account write and enforce a unique normalized email in the host users table. The package base User normalizes its email attribute. A host replacing that behavior must preserve the identity contract.

Invitation acceptance is a dedicated capability workflow. It reloads the persisted invitation under locks, validates token, pending state, expiry, target and email, then creates the account/membership and consumes the invitation atomically. It does not require the invitee to have `addMember`. If the account appeared since the page opened, acceptance stops and asks the user to sign in/contact the inviter; it never silently attaches an arbitrary logged-in account. Cancelled/rotated/expired tokens and changed email fail even if a host policy would allow management. If an older pending invitation remains after the email later becomes an existing account, managers can cancel it; existing-user email additions do not manufacture acceptance history.

## Transaction participants and locking

Set `participants` to an ordered list of classes implementing `Contracts\MembershipParticipant`. Each exposes `before(Support\MembershipContext $context): void` and `after(MembershipContext $context): void`. The second method runs after the package write and before commit, not after commit. Exceptions roll back membership writes, invitation consumption, host grant writes and audit rows on the same database connection.

Context contains `operation`, `actor` (null for capability acceptance), `target`, optional `user`, `invite` and `team`, `origin`, `acceptance`, and `changed`. Before a change, `changed` is false; after persistence it is true. Models expose their original/dirty attributes for host update validation. During creation, the target is unsaved in `before` and has its key in `after`. No-op membership/link changes invoke no participants or observation events. Operation names include `create_team`, `create_program`, `update_team`, `delete_user`, `add_member`, `remove_member`, `leave`, `link_team`, `unlink_team` and invitation transitions; inspect the public context in your integration tests.

Typical host uses are creator grant bootstrap, durable audit rows, grant revocation and a last-manager invariant. Validate in `before`; write transactional integration records in `after`. Do not perform network I/O or cross-database writes there. Package operations do not automatically retry participant execution. If a deletion graph changes before it is locked, the operation fails before participants and asks the caller to retry.

All existing affected User rows are locked before targets; rows are sorted deterministically within each lock group. Invite/membership mutations follow target locks. Batch operations gather the complete user/target set before applying changes. Account deletion takes the affected User lock before enumerating membership targets, so an addition to a previously unrelated team cannot bypass cleanup. Host privilege-demotion paths enforcing the same last-manager invariant must follow this user-first discipline too. Models participating in an operation must share a connection. Database unique constraints protect duplicate memberships, associations and invitation tokens.

`DeleteUser` removes memberships and invokes removal hooks even for a soft-deleting host User. The host may reject that action and implement a different soft-delete choice explicitly; raw model deletion is outside the action event contract. Target deletion removes its memberships, invitations and links; deleting a program leaves its linked teams intact. Raw SQL, FK cascades, seeders and relationship writes outside shared actions promise no package events or authorization.

## Events and registration

`InvitationCreated`, `InvitationResent`, `InvitationCancelled`, `InvitationAccepted`, `MemberAdded`, `MemberRemoved`, `TeamLinkedToProgram` and `TeamUnlinkedFromProgram` dispatch after the outermost successful commit, once per actual transition in normal execution. Their `payload` contains operation, actor ID, target type/key, affected user/invite/team IDs, origin, changed and acceptance flags. Identifiers remain usable after deletion. Payloads contain no bearer tokens or credentials. They are observations, not durable audit storage; use participants for atomic audit and privilege changes.

A rollback, denial or no-op does not dispatch these events or mail. This is not an exactly-once delivery guarantee across crashes or queue retries; host listeners must be idempotent when appropriate. Mail has one dispatch owner in the action layer; do not send duplicate invitation emails from observation listeners.

Successful invited registration dispatches Laravel `Registered`, then the existing synchronous `RegisteredWithData` event, after commit. `RegisteredWithData` deliberately retains the existing separate integration contract: hashed `password` plus plaintext `original_password` in its data array. Keep handlers synchronous and protect that payload; do not copy it into queued membership events, logs or audit records. Login and session regeneration follow successful commit. Password reset and email verification remain host responsibilities.
