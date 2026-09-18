# Filament Team Management

Teams, optional programs, direct memberships and membership invitations for Laravel 13, Filament 5, Livewire 4 and PHP 8.4+. The package owns membership data and workflows; your application owns authorization, panel admission and tenant access. It does not install a permission backend or define administrator roles.

```bash
composer require stats4sd/filament-team-management
php artisan filament-team-management:install
```

Follow [SETUP.md](SETUP.md) to register your models, policies and panels. Version 5 changes the authorization boundary; see [UPGRADE.md](UPGRADE.md) before adopting it. The installer publishes fresh-install migrations and optionally deny-default host policy stubs. It preserves existing policies and never registers a competing package policy.

## Membership and authorization

`Team::users()` and `Program::users()` return all direct members. `members()` is an unfiltered alias. Programs and teams are many-to-many; program membership does not automatically grant access to linked teams. No `admins()`, `isAdmin()`, `is_admin` pivot, role resources or role-only invitations are provided.

Use the shared actions from UI, jobs and application code. Every management action requires an identified actor and authorizes against the actual configured target model, including when an Admin screen is managing a different team from the selected tenant.

```php
use Stats4sd\FilamentTeamManagement\Actions\AddMember;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;

app(AddMember::class)->handle($actor, $team, $member);
$result = app(SendMembershipInvitation::class)->handle($actor, $team, 'member@example.org');
```

A missing policy or ability denies the operation. Filament uses the same ability arguments for presentation; shared actions reauthorize after locking. Read-only members can view permitted member lists and leave without permission to edit the tenant. App member management uses email invitations; Admin and Program screens also support a host-scoped existing-user picker.

The [authorization and workflow guide](docs/membership-contract.md) contains the full abilities table, simple administrator/member policy, scoped-permission recipe, transactional participants, events and lifecycle rules.

## Invitations and mail

An email invitation to a new user creates a pending Invite and queues mail after commit. An email invitation to an existing user requires both `inviteMember` and `addMember`, immediately adds membership and sends an update notification. It does not create a synthetic accepted Invite. Existing-user pending acceptance is not implemented.

`InvitationResult::status` is `invitation_created`, `member_added`, `duplicate_pending`, `expired_pending`, `already_member` or `skipped_blank`. Invalid input and denied actions throw. Expired pending invitations can be renewed with Resend; it rotates the token. Cancel deletes only pending invitations. Accepted invitations remain history. Pending counts exclude accepted and expired invitations.

Mail is queued by default; run your application's queue worker. Set `queue_mail` to false for synchronous transport, still after commit. A saved invitation is not proof of delivery. Results expose `mailStatus` (`pending_commit`, `queued`, `sent`, `failed`) and an error on dispatch failure. A failed resend dispatch raises `InvitationDeliveryFailed` after the new token is saved. Queue retries and provider delivery are host operational responsibilities. Message snapshots retain their original token/link and sender text; a later resend does not rewrite an older queued message. Rotated/cancelled links remain invalid even if old mail arrives later.

## Configuration

Publish config with `php artisan vendor:publish --tag=filament-team-management-config`. Display names, panel IDs, participant classes, picker class, expiry and fallback route are plain config values. Panel IDs identify existing host panels and do not set their URL paths or register panels. The configured App panel must also be the default authentication panel.

| Config | Default | Purpose |
|---|---|---|
| `names.team`, `names.program`, `names.user` | `team`, `program`, `user` | Singular display words; independent of class/table names |
| `panels.app`, `panels.program`, `panels.admin` | `app`, `program`, `admin` | Host panel IDs used for links and redirects |
| `invite_expiry_days` | `null` | No expiry by default; integer days applies to new/resend invitations |
| `participants` | `[]` | Ordered synchronous `MembershipParticipant` classes |
| `user_picker` | `null` | `UserPicker` class; no candidates until configured |
| `no_memberships_route` | `null` | Named authenticated, tenant-independent host landing route; otherwise package no-memberships page |

### Environment variables

The installer reads your configured values and adds missing keys independently to `.env` and `.env.example`. It preserves existing whole-key assignments, including the first line. Namespace values use dotenv-compatible quoting.

| Variable suffix after `FILAMENT_TEAM_MANAGEMENT_` | Default |
|---|---|
| `USE_PROGRAMS` | `false` |
| `QUEUE_MAIL` | `true` |
| `USER_MODEL` | Package `Models\User`; installer uses your auth provider model |
| `TEAM_MODEL`, `PROGRAM_MODEL` | Package `Models\Team`, `Models\Program` |
| `USER_TABLE`, `TEAMS_TABLE`, `PROGRAMS_TABLE` | `users`, `teams`, `programs` |
| `INVITES_TABLE` | `invites` |
| `TEAM_MEMBERS_TABLE`, `PROGRAM_MEMBERS_TABLE`, `PROGRAM_TEAM_TABLE` | `team_members`, `program_members`, `program_team` |
| `USER_FOREIGN_KEY`, `TEAMS_FOREIGN_KEY`, `PROGRAMS_FOREIGN_KEY` | `user_id`, `team_id`, `program_id` |

## Development

```bash
composer test
composer analyse
vendor/bin/pint --test
```

The test host explicitly implements policies and tenant access without a permission package. Production row-lock tests are opt-in and use MySQL with InnoDB, matching the primary database used by consuming applications. Each test creates and drops its own uniquely named database; use a test server and credentials with permission to create and drop databases. PHP needs the `pdo_mysql`, `pcntl` and `posix` extensions. SQLite tests do not establish row-lock behavior.

```sh
FTM_TEST_MYSQL_HOST=127.0.0.1 FTM_TEST_MYSQL_PORT=3306 FTM_TEST_MYSQL_USER=root vendor/bin/pest tests/Concurrency --compact --colors=never
```

Set `FTM_TEST_MYSQL_PASSWORD` if the test account requires a password. The host defaults to `127.0.0.1` and the user to `root`; setting `FTM_TEST_MYSQL_PORT` enables the tests. Without it, the four concurrency tests are skipped. See `tests/Concurrency/MembershipConcurrencyTest.php` and the [implementation log](docs/change-logs/phase-1b-track-b-membership-only.md) for coverage and verification results.
