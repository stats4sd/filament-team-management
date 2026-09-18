# Adopting 5.0

Version 5 is a breaking membership-only boundary, alongside PHP ^8.4, Laravel 13, Filament ^5.2 and Livewire ^4. Development consumers may rebuild deliberately; consumers pinned to older versions need not adopt this release. There is no automatic old-schema upgrade or database deletion. For a fresh installation follow [SETUP.md](SETUP.md).

## Removed contracts

- Remove package role resources, permission middleware (`CheckIfAdmin`, `CheckIfProgramAdmin`), `ModelHasRole`, role-only User invitations, `models.role` / `FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL`, `admins()`, `isAdmin()` and package `is_admin` assumptions.
- `spatie/laravel-permission` and Althinect integration are no longer dependencies. If your host still needs them, declare and configure them in the host. Package installation does not create/drop your permission tables or change their configuration.
- Replace model `sendInvites()` with `SendMembershipInvitation::handle(actor, target, email)`. There are no compatibility wrappers or role parameters.
- Replace implicit global permission strings and `getAllAccessibleTeams()` with host User panel/tenant access methods. Base integration denies until configured. Program membership alone grants no team access.
- App direct attach and Admin role-only invitation actions are removed. Manage memberships on the target Team/Program. Global User editing no longer silently synchronizes membership through a relationship form; target membership actions enforce the relevant policies.

## Required integration

Register policies for the configured Team, Program and User classes. Implement the exact [abilities and signatures](docs/membership-contract.md), including read-only member access, leave and both sides of program/team linking. Implement `canAccessPanel`, `getTenants` and `canAccessTenant` consistently. Configure a `UserPicker` for existing-user selection, optional transaction participants for privileges/invariants, and queued mail workers.

The configured App panel must be the default authentication panel. `panels.app|program|admin` identify host panel IDs, not paths. Keep persistent `SetLatestTeamMiddleware` / `SetLatestProgramMiddleware`. Register tenant creation pages only where appropriate; their `create` ability is the single authorization source. Configure an authenticated non-tenant landing route if the package no-memberships page does not suit your host.

`names.team`, `names.program` and `names.user` are plain display config. New config includes `queue_mail`, `invite_expiry_days`, `participants`, `user_picker`, `no_memberships_route` and `table_names.invites`. `FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL` and `FILAMENT_TEAM_MANAGEMENT_INVITES_TABLE` are new environment keys. Existing users keys are singular `USER_TABLE` / `USER_FOREIGN_KEY`; programs use `PROGRAMS_FOREIGN_KEY`. Review your published config rather than retaining obsolete role keys.

## Additional shared APIs

`Actions\CreateTeamForProgram::handle($actor, $program, $data)` exposes the existing atomic UI create-and-link operation to other callers. `Support\MembershipCandidates` centralizes explicit-actor candidate queries and selected-ID resolution; `UserPicker`, policies, participants and events keep their existing contracts. These extractions add no schema, permission, config or installation requirement. Missing-sender mail now uses “Someone”.

## Fresh schema and lifecycle

Fresh membership pivots have no administrator flag. Fresh invites have exactly one application-validated team/program target, unique bearer tokens, nullable expiry, nullable inviter with null-on-delete and no role FK. The optional program columns remain present in the default schema; constraints belong to the program publish tag. Custom model/table/FK names are supported. The installer does not implement a legacy migration program.

Only real invitations are retained in Invite history. Direct additions no longer write synthetic accepted rows with token `na`. Store durable direct-add history through a participant if needed. Existing-account invitations still add membership immediately; new-user acceptance requires a valid pending token. Accepted rows cannot be resent or cancelled.

Use shared actions for lifecycle events and grant revocation. Raw SQL, relationship writes and cascades bypass the action contract. `DeleteUser` removes memberships even for a soft-deleting host User; hosts wanting a different retention policy must define and test that path. Mail and observation events run after commit; synchronous participants must stay on the same database connection. The separate synchronous `RegisteredWithData` integration retains its existing credential-bearing payload.

## Other 5.0 removals and renames

The earlier framework update renamed `ManageProgramProjects` to `ManageProgramTeams` and `ProgramProjectsTable` to `ProgramTeamsTable`. It removed the empty `FilamentTeamManagement` service/facade and alias, empty `FilamentTeamManagementPlugin`, unused `Filament\Auth\RegisterResponse`, obsolete `ProgramInvite` model/factory, empty `routes/team-management.php`, placeholder `manage-team` view, and self-referencing Team/Program relationships. Do not import those classes in the host. Invitation mail uses an unsigned URL whose token is the capability; do not add a signed-URL requirement.

Release tagging/publishing, the core/scaffold monorepo split, existing-user pending acceptance and Program-panel team-member drill-down remain separate work.
