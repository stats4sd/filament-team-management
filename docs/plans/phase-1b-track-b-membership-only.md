# Phase 1b — replacement Track B: membership core with app-owned authorization

**Date:** 2026-09-17 · **Status:** B1–B8 implemented on 2026-09-18; B9 remains deferred. See the [implementation and verification record](../change-logs/phase-1b-track-b-membership-only.md). · **Replaces:** Track B, its readiness assessment and its suggested implementation order in [the original Phase 1b plan](phase-1b-pre-release-and-review-remainder.md#track-b--remaining-review-items). Track A's completed work remains historical fact.

## 1. Direction and scope

The package owns teams, optional programs, their membership and associations, membership invitations, and the actions that change those records. The host application owns roles, permissions, administration rules, panel access and accessible tenants. Package entry points enforce the host's decisions through Laravel Gate and policies; removing the permission backend does not remove authorization checks.

Remove `spatie/laravel-permission`, its Althinect Filament integration and the package's `is_admin` concept. Retain `spatie/laravel-package-tools`, which is unrelated to authorization. A host may use an admin flag, Spatie's scoped permissions or another implementation. The package does not prescribe role names, create permission records or silently grant privileges.

**User constraints:** existing consumers are development-only or pinned to older versions that will not be updated. This plan does not require backwards-compatible APIs, legacy adapters, incremental migrations for old releases, orphan repair for old schemas, consumer upgrade audits or old-app compatibility testing. Development databases may be rebuilt deliberately; this document does not authorize deleting any database. Fresh installation, migration ordering, rollback and custom model/table/FK support still require verification.

**Planning status:** the membership-only direction and app-owned authorization boundary consolidate the preceding discussion. Concrete API names, duplicate handling, audit representation and hook behavior below are implementation proposals made explicit for review. Previously settled choices remain: queue mail by default; remove direct attach only from the App panel; use `panels.app|program|admin`; keep existing-user email invitations as immediate attachment for now. Do not implement existing-user pending acceptance as part of this revision.

**Release proposal:** deliver the coherent new boundary in the pending 5.0 release before A8. Do not split known breaking defaults into later purportedly compatible minors. B9 remains deferred. This is a proposed release scope, not authorization to tag or publish a release. Record breaking removals and the fresh-install setup in CHANGELOG/UPGRADE without manufacturing a legacy migration program.

The future core/scaffold split and monorepo remain separate phases. This work keeps the existing Filament surfaces, makes them delegate to shared actions and removes their permission-backend assumptions. Host policy stubs are a small integration aid, not the full Phase 3 scaffold conversion.

## 2. What changes in the old Track B

**No complete B group can remain exactly as written.** The table distinguishes unchanged individual requirements from retained behavior whose implementation or dependency changes. Sections labelled **EXACTLY AS-IS** reproduce the corresponding old requirement verbatim; their dependency on the new authorization contract is stated separately.

| Item | New disposition | Exactly unchanged parts |
|---|---|---|
| B1 — authorization | Replace fixed admin semantics with a documented, enforced host-policy contract; remove permission dependencies and admin state. | None of the old group as a whole. Read-only access and Leave team remain requirements with new rules. |
| B2 — invitations | Rewrite as membership-only orchestration; retain immediate addition of existing users and queued-mail default. | No complete old bullet: role intent, tracing and compatibility assumptions change. |
| B3 — actions/events | Keep extraction, add transactional host integration; remove role actions/events/pivot adapter. | Event documentation remains required, but the event contract changes. |
| B4 — lifecycle | Keep expiry, resend, cancellation and transactional validation; use fresh schema. | The 2.4 filter/column/count cleanup. |
| B5 — tenancy | Expand to host-owned access; retained correctness fixes operate against that contract. | Middleware dirty-check/type guard; default-tenant selection requirements. |
| B6 — UI | Keep labels, panel IDs and navigation behavior; apply new abilities to retained actions. | Label sweep; panel-ID contract; link/redirect requirements; A7 wording remainder; deletion-copy recommendation. |
| B7 — accounts | Keep nullable inviter and deletion behavior work; remove Spatie cleanup and old-schema migration requirements. | Missing-inviter mail handling; unique email; password reset/email verification ownership. |
| B8 — installer | Remove permission setup and add host policy guidance; preserve installer correctness. | Dotenv/seeder insertion robustness; explicit string cast. |
| B9 — drill-down | Keep deferred; authorize against the actual team through the host policy. | Its deferral and intended UX survive, but the authorization wording changes. |

## 3. Shared contract for B1–B5

### Responsibility boundary

| Package responsibility | Host responsibility |
|---|---|
| Membership and program–team relationships; structural integrity | Role definitions, scoped grants and their lifecycle |
| Invitation identity, validity, expiry and consumption | Additional privileges promised by a host-specific invitation |
| Explicit actor and target on management actions; mandatory Gate checks | Policy implementations and deliberate global bypasses |
| Transactions and synchronous extension points | Bootstrap, ownership, last-admin and revocation invariants |
| After-commit membership/invitation events | Durable application audit storage and external integrations |
| Direct membership queries and remembered-tenant mechanics | Panel admission, accessible tenant queries and any inherited program access |

Programs and teams remain many-to-many. Being a member or administrator of a program does not inherently grant access to, or administration of, every linked team. Linking a team can affect access in a host that chooses inheritance; both sides of that link need authorization. Host rules must explicitly cover teams shared by multiple programs.

### Public policy abilities

Use standard Laravel policy resolution against the actual configured model class. Document this table as a public contract. No package policy auto-registration may override a host policy; optional published stubs live in the host and initially deny management operations. An app can map several abilities to one `isAdminOf($team)` check or distinct scoped permissions.

| Operation | Policy method | Arguments after the actor |
|---|---|---|
| List/view team or program records | `viewAny`, `view` | Configured model class for listing; target model for viewing |
| Create a team or program | `create` | Configured model class |
| Rename/edit; delete | `update`, `delete` | Target model |
| View membership; view invitations | `viewMembers`, `viewInvitations` | Team or Program |
| Send a membership invitation | `inviteMember` | Team or Program |
| Directly add an existing user | `addMember` | Team or Program, target User |
| Remove another member | `removeMember` | Team or Program, target User |
| Leave your own membership | `leave` | Team or Program |
| Resend/cancel an invitation | `resendInvitation`, `cancelInvitation` | Team or Program, Invite |
| List teams associated with a program | `viewTeams` | Program; visible linked records must also satisfy host access rules |
| Link a team to a program | `ProgramPolicy::linkTeam` AND `TeamPolicy::linkProgram` | Program, Team for the former; Team, Program for the latter |
| Unlink a team from a program | `ProgramPolicy::unlinkTeam` AND `TeamPolicy::unlinkProgram` | Program, Team for the former; Team, Program for the latter |

For model-class checks, Laravel uses the class to resolve the policy; the policy method itself receives the actor without a model instance. For record checks, the first model resolves the policy and the remaining arguments provide context. For example, `Gate::forUser($actor)->authorize('removeMember', [$team, $member])` calls the host's `TeamPolicy::removeMember($actor, $team, $member)`.

Bulk operations authorize every affected record inside the shared operation. A Filament `deleteAny` or similar coarse check may control button availability but cannot replace per-record checks. Retained site-wide User resource reads/writes also require host `UserPolicy` abilities; removal of roles must not accidentally expose the global user directory, edit form or deletion action. User pickers must use host-authorized queries, not expose every user merely because someone can invite to one team.

Policy abilities describe business operations; they are not Spatie permission names. Host permission adapters must evaluate the supplied target, including on cross-team Admin/Program screens, rather than trust whichever tenant happens to be selected. A host's global Gate callback is an explicit host decision, never a package-shipped Super Admin rule.

### Enforcement and transaction rules

1. Interactive management and application-facing mutation actions require an explicit authenticated actor, including console/job callers using an identified service actor. No inference from `auth() === null` and no public `skipAuthorization` switch. Trusted seeders/migrations may use low-level relationships; those writes do not promise action events or authorization.
2. Use direct Laravel Gate authorization at the shared action boundary. Missing abilities deny unless the host explicitly grants through Gate. UI checks use the same ability/arguments but are only presentation checks. Do not rely on Filament's permissive fallback for a missing policy/method.
3. Distinguish record/page viewing from editing, member management and leaving. A read-only member must not lose their members list or Leave action merely because the tenant profile base page checks `update`.
4. A new user's valid invitation authorizes its own membership acceptance through a dedicated token-validated workflow. The invitee does not need `addMember`; the original inviter is attribution, not the authenticated actor for acceptance. Token ownership, persisted email, target, pending status and validity checks cannot be bypassed by an allowing host policy. No new public unchecked add-member method is exposed to make this work.
5. Authorized creation atomically creates the team/program and its creator membership. The host's synchronous participant may establish its chosen administrator/owner grant. The creator cannot be required to administer a record that does not exist yet. No core `is_admin` write or mandatory last-admin rule.
6. Optional synchronous host participants run in the same database transaction for bootstrap grants, grant revocation, durable audit rows and business invariants. Their exceptions roll back the package mutation and invite consumption. Core membership works without these participants. Permission decisions still require policies; optional hooks are not a permissive authorization fallback.
7. Publish a precise participant context: operation, actor or invitation-acceptance context, target, affected user/invite/link and changed state. Provide a before-change validation point and an after-write/before-commit integration point. Do not perform external I/O there or automatically retry a transaction containing a participant unless its retry behavior is explicitly supported. Cross-database/external side effects are not atomic under this contract.
8. Establish one lock order: existing affected User rows, then affected program/team target rows, then invite/membership rows, with deterministic ordering within each class. Add/remove/leave and account deletion coordinate on the affected User row; deletion acquires that lock before enumerating memberships, so a concurrent addition to a previously unrelated target cannot escape cleanup/hooks/events. Revalidate that the user still exists and is eligible after obtaining the lock. Recheck target state and authorization after obtaining the relevant locks. Lock the membership owner for mutation and add unique membership/link constraints so duplicate writes and concurrent last-member checks cannot race. New-user acceptance/bootstrap may create rows that are invisible outside its transaction; a concurrently appearing existing account must be rejected or the operation restarted before participants, never handled by taking existing-user locks late in a conflicting order. Host role-demotion/removal paths that bypass package actions must use the same locking discipline if they enforce the same ownership invariant.
9. Publish observation events and dispatch mail after the outermost successful commit. Emit once per actual committed transition during normal execution, never for denied operations, no-ops or rollbacks. This is not an exactly-once delivery guarantee across queue retries, mail transport or process crashes; document failure/retry behavior and require idempotent listeners where needed.

### Unified schema and invitation identity

Keep the configured nullable team and program FK columns, with exactly one populated for a real invitation. Drop role-only invitations and `role_id`; do not introduce a polymorphic target migration merely for this refactor. Program-disabled mode still has the nullable program columns from A6, but rejects program operations. Preserve the default/program migration tags and A6's explicit timestamp ordering.

Design the fresh invites schema once across B1/B2/B4/B7/B8: configured invites table, nullable inviter with configured users FK and `nullOnDelete`, nullable expiry, unique real tokens and the existing completion state or an explicitly documented replacement. Define a single email normalization function consistent with host account lookup and email uniqueness; do not invent provider-specific address equivalence. Duplicate identity is normalized email plus target type and key. Serialize duplicate decisions by locking the target and enforce membership/link uniqueness at the database level. Verify production-database concurrency, not just SQLite behavior.

**Proposed audit simplification:** an Invite represents a real invitation, retained when accepted. Stop creating synthetic confirmed Invite rows with `token = 'na'` for role assignment or immediate membership additions. Immediate additions emit `MemberAdded` with an origin such as `email_invite` or `direct_add`; a host requiring durable history stores it through the synchronous participant. This changes the package's historical tracing behavior deliberately: it will no longer provide durable direct-add history in the invites table. The accepted-invites filter then describes actual accepted invitations. Do not add a generic audit subsystem to replace Spatie coupling.

## 4. Replacement Track B

### B1. Remove the permission backend and implement the authorization contract

**Disposition: replaces old B1 and adds the removal work made necessary by the new boundary.**

- Remove the two permission Composer dependencies; keep package-tools. Remove `HasRoles`, the custom `roles()` override, package Role/Permission models/resources, `ModelHasRole`, role-related User form/table actions, role seeders, role-only invite entry points, role config/env keys, role mail branches and role schema references. Remove hardcoded Super Admin/Program Admin checks and package permission-based panel middleware; B5 supplies the replacement integration.
- Remove `is_admin` from fresh membership schema, factories, interfaces, relation filters, creator attachment and all toggles. Keep `users()` and `members()` as explicitly documented unfiltered aliases for all direct memberships; remove `admins()` and role-based `User::isAdmin()`. The host can extend its own pivot/model if it wants an admin flag.
- Deliver the policy contract above, configured-model registration instructions and deny-default host policy stubs/examples. Do not require a new role resolver framework or a Spatie adapter in the package. Keep a simple example host policy demonstrating admin-versus-member behavior and a separate optional recipe for scoped Spatie checks.
- Apply the contract across shared actions and retained Filament surfaces, including custom actions, bulk operations, user selectors and User resource operations. Keep application policy registration authoritative; tests must include configured subclasses.
- Implement read-only tenant/member pages independently from writes. Add Leave team through the shared removal operation with `leave` authorization and host invariant checks. Clear stale tenant state and select another accessible App tenant if one exists, otherwise offer App registration only when available and permitted. If neither is available, redirect to a host-configured, authorized non-tenant landing route or a package-provided no-memberships page that requires authentication but no tenant. Do not redirect an ordinary member to the Admin Teams list. B6's unchanged Admin deletion fallback remains specific to that Admin context.
- Gate tenant registration with the configured model's `create` policy, both on route/page access and submission. Replace the old proposed `allow_team_registration` / `allow_program_registration` booleans with this single authorization source. A host disables registration by denying `create`; do not leave an alternate permissive path.
- Update test fixtures to use explicit host policies. Remove the harness's blanket Super Admin role bypass from ordinary authorization tests; exercise an explicit host global bypass separately.

**Primary surfaces:** `composer.json`/lock resolution, `src/Models/**`, `src/Filament/**`, `src/Http/Middleware/CheckIf*`, config, migrations 2/3, factories/seeders, provider and authorization fixtures. Coordinate the removal with B2/B3/B5/B8 rather than merging a state that cannot boot or leaves mutations unguarded.

**Acceptance:** the package installs and boots without either permission dependency; missing/denying policies prevent action execution and direct crafted requests; a host admin can manage Team A but an ordinary member cannot; that admin's grant does not leak into Team B or unrelated programs. Read access and leaving work independently of editing. There is no role/admin schema or behavior left in the core.

### B2. Membership-only invitation orchestration and shared UI feedback

**Disposition: rewrite the contract; retain the invitation UX and delivery choices specified below.**

- Proposed API: `SendMembershipInvitation::handle(actor, target, email)` with a validated Team-or-Program target. No role parameter, `asAdmin`, role-only target or automatic Program Admin grant. Retire the duplicated model `sendInvites()` implementations without mandatory compatibility wrappers.
- Return explicit outcomes such as `invitation_created`, `member_added`, `duplicate_pending`, `expired_pending`, `already_member` and `skipped_blank`. Invalid email/target is validation failure; denied operations and participant failures are not success results. A created invitation is not proof of email delivery. `expired_pending` directs the authorized actor to Resend rather than leaving re-invitation indefinitely blocked.
- New user: authorize `inviteMember`, lock and check duplicate state, persist a pending invitation, then dispatch mail after commit. Already-pending valid invitations are a no-op; use B4's resend operation to rotate/extend an expired pending invitation rather than creating parallel pending rows. Accepted/cancelled history does not block a new invitation when no membership exists.
- Existing user: preserve immediate attachment and update notification. Authorize both `inviteMember` and `addMember` before changing membership, then use B3's shared add operation. A host that wants this email workflow must grant the relevant add ability; denying `addMember` must not silently fall back to another behavior. Existing membership is a no-op. Do not create a fake accepted Invite for this branch.
- Share one Filament invitation action across App/Program and Admin membership surfaces, driven by the result. Remove role-only invitation from the Admin Users screen rather than retaining an invitation with no membership target. Filament notifications remain in the UI adapter, not models or domain actions.
- Retain queued mail by default: `queue_mail` / `FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL`, default `true`, with synchronous `send()` opt-out. Both modes dispatch after commit. Give B2 one mail-dispatch owner so B3 observation listeners cannot send the same email again. Document workers, delivery failures and retry behavior; no exactly-once transport claim.
- Snapshot the intended invitation token/link, target display data and null-safe sender data when dispatching each mail. Do not let delayed model rehydration silently replace an earlier message's token with a later resend token. Old queued links may become invalid after rotation or cancellation; actual delivery must not revive them. Tokens belong in the intentional invitation mail payload, not observation events or logs.
- Hosts offering “invite as administrator” persist and validate their own scoped privilege intent in a host-owned record linked to the invitation, then consume it via the synchronous acceptance participant. Role grantability, inviter privilege revocation and changes to that intent are host rules. The package neither parses generic role metadata nor queues required privilege assignment after acceptance.

**Dependencies:** B1 contract; B3 action/transaction kernel; B4 lifecycle predicates agreed before implementation; B7 mail fallback; B8 env/config wiring. The existing-user acceptance investigation remains deferred under B6.

**Acceptance:** cover new/existing users, duplicate/no-op, blank/invalid input, configured target/user models and custom FKs, explicit actors, denied immediate additions, concurrent duplicate attempts, participant rollback and mail queued/sent modes. No role table or role fixture is required. Preserve email notification behavior while accurately describing outcomes.

### B3. Transactional membership actions, acceptance and host integration

**Disposition: replaces role events/pivot side effects with explicit membership operations. Implement the action kernel before B2's orchestration.**

- Shared actor-aware actions own add, remove, leave, program–team link/unlink, creation/bootstrap and retained team/program update/delete mutations. Filament handlers call these actions instead of writing pivots directly. Keep trusted persistence primitives internal to the workflows; seeders may still use ordinary Eloquent under the documented event limitation.
- Extract invited account creation/acceptance from `Register::register()`. Inside one transaction, reload and lock the invitation/target, validate its persisted token/status/expiry/target/email, create the configured user, attach membership, run synchronous participants and consume the invitation. Acceptance is authorized by the valid invitation capability, not by giving the invitee member-management powers.
- Treat persisted invite email and target as authoritative. A modified read-only form field must not redirect acceptance to another email or team. If an account with that email has appeared since the form opened, stop without consuming the invitation or attaching it to an arbitrary logged-in user; give an actionable sign-in/contact response. A general existing-account acceptance flow remains deferred.
- Implement the synchronous participant contract and lock discipline in section 3. Exercise bootstrap, removal/revocation and optional last-admin validation through host test fixtures. The core stores no administrator definition and emits no `TeamAdminToggled` or `UserRoleAssigned` event.
- Proposed observation events: `InvitationCreated`, `InvitationResent`, `InvitationCancelled`, `InvitationAccepted`, `MemberAdded`, `MemberRemoved`, `TeamLinkedToProgram`, `TeamUnlinkedFromProgram`. Payloads identify operation, target type/key, affected user/invite/link, actor or acceptance context, origin and changed state; cancellation/removal payloads remain usable after deletion. Do not publish credentials or invitation bearer tokens in general membership events. “Created” means persisted, never email delivered.
- Account registration remains an explicit integration: document `Registered` and `RegisteredWithData` ordering and dispatch only after successful commit, with login/session regeneration after commit. Preserve their existing documented registration payload intentionally in this extraction, including the separate credential-bearing `RegisteredWithData` contract; keep that event synchronous and do not copy its payload into queueable membership events, audit records or logs. Any redesign of credential transfer is a separately scoped decision.
- Direct-add audit history uses the synchronous host participant if required; after-commit events provide observation, not durable audit or atomic privilege guarantees. Cover deleted inviter attribution and no-current-request callers without an auth/request heuristic.

**Acceptance:** denied mutations, failed participants and invalid/stale tokens leave no membership/account/invite-consumption changes or queued mail. Repeat acceptance cannot create a second account/membership. Direct and bulk actions follow the same rules; bulk denial rolls back the complete batch. Events represent actual committed changes. A host admin-flag fixture and a fixture using separate scoped grants both integrate without core role assumptions.

### B4. Invitation expiry, resend and cancellation

**Disposition: core behavior retained; fresh schema and new authorization/actions replace old migration and role assumptions.**

- Add nullable `expires_at` to the coordinated fresh schema. Retain `invite_expiry_days = null` as the simple no-expiry default, without treating it as a compatibility requirement. Publish/register the schema in provider and harness; no incremental old-release migration.
- Keep mount-time expiry feedback and login redirect. Always revalidate at acceptance submission under B3's transaction, including token rotation, cancellation and target deletion after the page opened.
- Only pending invitations can be resent or cancelled. Resend may renew an expired pending invitation, rotates its token and resets expiry according to current config. Cancel deletes the pending invitation. Accepted invitations are history and cannot be resent/cancelled by these actions. Invalid/stale operations cannot send mail.
- Define pending counts as unaccepted, unexpired invitations; expired unaccepted rows remain visible with an expired status and a Resend action for an authorized actor. Reuse the same lifecycle predicates in duplicate handling, counts, UI status and acceptance validation.
- Use shared resend/cancel actions from every invitation surface; authorize the owner target plus invitation and verify that they match. Resending requires a current actor and records that actor in the operation/event; it need not rewrite historical inviter attribution.

**EXACTLY AS-IS — original B4's 2.4 cleanup:**

- 2.4 cleanup: rename the internal filter key from `only_unconfirmed` to `show_accepted`; the visible label already says "Show accepted invites"; drop the always-current `team.name` column on the team tab (and `program.name` on the program tab); label "# Invites" as "# Pending invites".

**Acceptance:** expiration, renewal, cancellation, repeated/stale submissions and resend-versus-accept races follow the transaction contract. Old tokens fail after rotation. Accepted-history filtering and pending counts reflect real invitation states. Test fresh migration up/down and optional programs; omit old-schema upgrades.

### B5. Host-owned tenant access and retained tenancy correctness fixes

**Disposition: expanded; full B5 is no longer independent of B1.**

- Remove the package's `view all teams`, `view all programs`, automatic program-member-to-team access and Super Admin assumptions. Replace permission-based panel middleware with documented host `canAccessPanel()`/panel middleware integration. Panel authentication through the default App panel remains.
- Keep direct `teams()`/`programs()` relations as membership facts. Retire or redefine the misleading `getAllAccessibleTeams()` API so a relationship union is not silently an access grant. An explicit relationship-navigation helper may return program-linked teams, but it cannot be the default authorization answer.
- Host users implement the Filament access contract: `getTenants($panel)`, `canAccessTenant($tenant)` and `canAccessPanel($panel)`. Ship documented examples that derive enumeration and individual checks from the same host-owned accessible query/rule. The base integration returns no tenants/denies access until configured; no `canAccessPanel(): true` default. No separate package role-resolution framework is required.
- Host examples cover direct members, deliberate global access and optional program-derived access. Policy `view` and accessible-tenant decisions must agree for the relevant panel. Validate the actual configured tenant class, current panel and disabled-program mode; preserve host handling for any other tenant models without granting access by guessing a relation.

**EXACTLY AS-IS — original B5 middleware requirement:**

- `SetLatest*Middleware`: dirty-check before `save()`; bail unless tenant is an instance of the configured Team/Program model.

**EXACTLY AS-IS — original B5 default-tenant requirement:**

- `User::getDefaultTenant()`: use the `$panel` argument; validate `latestTeam`/`latestProgram` is still in `getTenants($panel)` before returning, else first accessible.

These two unchanged requirements operate against the host's new access implementation. Empty tenant lists return no default tenant and use B1's permitted selection/registration/non-tenant fallback, never a stale inaccessible record. The host-configured landing destination must itself be accessible without a tenant; provide a usable authenticated no-memberships page when the host supplies no destination.

- Preserve the original configured-FK fix: `Team::invites()` and `Program::invites()` resolve `column_names.*_foreign_key`. Remove model-name-derived writes from the old `Team::sendInvites()` along with that implementation; B2/B3 writes use the configured target relationship/FK. Exercise both new-user invitations and existing-user membership additions with custom tables/FKs.
- Efficient existence queries remain appropriate in host access examples, but the old optional `canAccessTenant()` optimization cannot preserve the old authorization model.

**Acceptance:** no program membership alone broadens team access; explicit host inheritance works when configured; tenant lists and direct URL access agree; changing panel or removing membership invalidates remembered tenants. Wrong-model middleware inputs cause no writes. Models/FKs work with programs enabled and disabled.

### B6. Labels, navigation and retained UI behavior

**Disposition: mostly retained; new ability checks replace the old authorization assumptions.**

The following requirements are **EXACTLY AS-IS**. References to panel/tenant permission checks now resolve through B1/B5. The deletion wording remains a proposal, not a previously confirmed user choice.

- **Labels from config, repo-wide** (follow-up to A7): every UI label currently derived from `getModelNameLower()` or `table_names.*` (`HasTeamManagementNavigationGroup`, `ProgramTable`, `ProgramForm`, `TeamsRelationManager`, `UsersRelationManager`, `UserTable`, `TeamInvitesTable` column labels, …) switches to `names.team` / `names.program` (+ `Str::plural` / `Str::ucfirst` as needed). Add `names.user` (default `user`) for user-facing labels. Keep these as plain published-config values. `tests/Feature/DisplayNamesTest.php` and `tests/ProgramMode/Feature/DisplayNamesTest.php` grow cases per surface.
- **2.6 — decided panel-ID contract:** add `panels.app`, `panels.program` and `panels.admin`, defaulting to `app`, `program` and `admin`. These identify host-registered panels; they do not set URL paths or register panels. Recommend plain published-config values, with no new env variables or installer prompts. Document matching host panel IDs and build URLs through those panel objects, including README navigation examples currently using literal panel paths. Preserve the existing default-panel authentication behavior in `InviteUser` and `AuthenticateThroughDefaultPanel`; document the expectation that the configured App panel is also the host’s default authentication panel. These new keys do not independently change login or registration routing.
- **2.6 — links and redirects:** use the configured Program panel for `ManageTeam::getSubheading()` links, checking panel permission/access and access to each linked program. Recommendation: display escaped program names as plain text when the Program panel is unavailable or access is denied, rather than producing broken links; preserve the existing absence of the entire program section when `use_programs` is false. For `ViewTeam`, evaluate the redirect after successful deletion, refresh tenant state and pass a still-accessible tenant explicitly to the configured App panel’s `getUrl($tenant)`. Bare `getUrl()` can infer a default through the globally current Admin panel, so it is not sufficient. Do not reuse the deleted tenant or a stale cached default. If no tenant remains, recommend App tenant registration only when available and permitted; otherwise fall back to the current admin Teams list. The same fallback applies when the App panel is missing or inaccessible. Test custom panel IDs/paths, tenant slugs, missing Program panels, denied tenant access and deletion of the latest/last accessible team, and unavailable or disallowed tenant registration.
- **2.5:** class and copy rename shipped in A7. Nothing left here except any stray “project” wording found after A7.
- **2.9 — recommendation still to confirm:** remove the survey-data warning and correct the Program deletion text without changing deletion behavior. Say that deleting a program removes the program and its membership/team associations, while associated teams remain; do not promise that every related row is preserved (program-linked invites also cascade). Keep a clear irreversible-deletion warning appropriate to the package's models. No new cascade into teams or host-owned survey data.

**Retained decision, revised authorization wording:** remove `AttachAction::make('Add Existing Users')` from App `TeamMembersTable`, including its unused import. Keep Admin and Program attach actions, now gated by `addMember` and routed through B3. Preserve the App email invitation route and immediate addition of existing users, with both checks described in B2. Test that the removed action cannot be invoked and that retained actions deny or allow according to host policies. Include the removal in the proposed 5.0 release.

**Existing-user pending acceptance remains deferred feasibility work.** Investigate later: login-and-return, correct-account/email matching, repeat acceptance, expiry/cancellation, confirmation that email scanners cannot trigger and atomic host privilege integration. Do not turn B2's existing-user branch into pending acceptance during this refactor.

**Acceptance:** retain the original display-name, custom panel ID/path, escaped link fallback and post-delete navigation checks. Also test a host policy denying linked-program access, denied tenant creation fallback, missing panels and newly denied membership actions. UI-only labels/copy can proceed independently; authorization and navigation completion depend on B1/B5.

### B7. Inviter and account lifecycle

**Disposition: retained robustness with a fresh-schema and host-authorization boundary.**

- Make `inviter_id` nullable in the coordinated fresh schema before adding the configured users-table FK with `nullOnDelete`. Verify custom tables and publish/rollback order. Drop the old orphan-repair and incremental migration requirements. Invitations may survive inviter deletion; membership-only acceptance remains governed by invitation validity unless a host participant applies a stricter rule for its privilege intent.

**EXACTLY AS-IS — mail robustness, email uniqueness and host account responsibilities:**

- Mail rendering must tolerate a deleted/missing inviter: both `resources/views/emails/invite.blade.php` and `update.blade.php` currently dereference `inviter->name/email`. Choose a null-safe sender fallback or retained sender snapshot; test deleting the inviter between queueing and delivery, and B4 resend of an orphaned invite. Include this contract in B2 queue integration.
- Admin `UserForm`: `->unique(ignoreRecord: true)` on email.
- Document that password reset / email verification are the host app's responsibility (or wire Filament's `->passwordReset()` in the README App-panel example).

- Verify membership/link FK cleanup and optional host soft-delete behavior. Spatie role cleanup is no longer a package concern. Host-owned grants must be revoked or rendered unusable according to the host's account and membership lifecycle rules, including the case where a user later rejoins.
- Define a shared, authorized account-deletion orchestration path for the retained User resource: first lock the affected User row using the common add/remove/delete protocol, then enumerate/snapshot affected memberships, lock their targets consistently, run host invariants/revocation participants and delete/remove within one transaction; emit appropriate removal events after commit. Concurrent additions must serialize on the same user lock and revalidate the user's existence/eligibility, so they cannot create an undiscovered membership that is silently cascade-deleted. Raw SQL/FK cascades and host deletions outside that path do not emit package application events. Document that boundary instead of promising universal cascade notifications. Soft-delete hosts explicitly choose whether memberships remain, are removed or are only inaccessible and must test that choice.

**Acceptance:** missing/deleted inviter mail is safe, including delayed queue delivery and resend; current-user email edits work; deleting accounts through the documented action cleans up memberships and gives the promised events/hooks. No residual Spatie import is needed for account lifecycle tests.

### B8. Membership-only installer, schema publishing and config correctness

**Disposition: preserve robustness work and add removal/integration setup.**

- Remove Spatie permission migration publishing, Althinect setup, role-model env prompts/config, permission seeders and their insertion into `DatabaseSeeder`. Keep optional example membership seeders only after rewriting them without roles, so any retained seeder insertion has the robustness contract below. Never create/drop the host's role tables or alter host-owned permission configuration.
- Offer opt-in publishing of deny-default Team/Program policy stubs plus documented User/panel/tenant integration examples. Do not overwrite existing host policies or silently auto-register competing package policies. Generated policies follow configured model classes and program enablement. A fresh install makes missing authorization obvious and does not expose management operations by default.
- Add `table_names.invites` / `FILAMENT_TEAM_MANAGEMENT_INVITES_TABLE`, written by the installer and used by `Invite::getTable()`, migration 3 up/down and A6 stub 10 discovery/up/down. The schema from B1/B4/B7 must use it consistently. Cover fresh publish order/tags and rollback; omit incremental legacy migrations.
- Add B2's queue setting to the installer/config parity and README env reference. Keep `names.*` and `panels.*` plain published-config values. Remove obsolete role keys from parity expectations and examples; preserve configured models/tables/FKs and the default/program migration split.

**EXACTLY AS-IS — installer robustness:**

- Round-trip env values containing backslashes through the installed dotenv parser before choosing escaping/quoting; quoting alone is not proof of correctness. Resolve the actual environment-file path, match whole keys including the first line, filter `.env.example` against its own contents, and test repeated runs and independently populated files. Indent injected `$this->call(...)` and make seeder insertion idempotent. Replace raw brace counting with syntax-aware or demonstrably whitespace-tolerant insertion and a clear no-write failure for unsupported input; do not regress to a regex requiring one exact newline/brace layout. Cover braces in strings/comments and supported `run()` formatting.

**EXACTLY AS-IS — string return cleanup:**

- `HasModelNameLowerString::getModelNameLower()` explicitly casts to `(string)` as contract cleanup, not a confirmed runtime defect (the current method already declares `: string`). (The earlier `getModelNameHeadline()` idea is dropped: labels come from `names.*` config per A7/B6, not from class names.)

**Acceptance:** a fresh host without permission packages/tables can install, publish, migrate and boot in both program modes; policies initially deny, and a configured example host can complete membership workflows. Re-running installation does not duplicate settings, seed calls or policy files. Actual dotenv parsing, custom invites table, shared migration timestamps and rollback pass.

### B9. Program-panel team-member drill-down — still deferred

**Disposition: retain the optional UX and deferral; revise the authority source.**

Only when requested by a consuming app, add Manage members to A7's `ProgramTeamsTable`, using the shared members UI and B2 invitation action. Viewing the program is insufficient: check `viewMembers` and the relevant invite/add/remove abilities against the specific linked team. The host decides whether program membership conveys any such authority. A team shared by two programs must not gain unintended cross-program administrative access. No independent role model or duplicate mutation implementation.

## 5. Implementation order and review gates

Do not execute B1–B9 in numeric order. B2 needs B3's transaction kernel and B4's lifecycle predicates. The removal spans boot-time dependencies, migrations, installer and UI; split reviewable commits/PRs without releasing an intermediate unguarded state. Each merged unit must boot and pass its checks. Where a safe split would require a temporary compatibility layer, combine the coupled change instead.

1. **Contract and schema specification:** freeze ability names/arguments, dedicated token acceptance, participant timing, locking, audit representation, duplicate/expired states and the unified fresh schema. Translate this plan into a bounded file-level implementation plan. Record any changes to the proposed choices here before coding.
2. **Foundational implementation:** implement B1 removal/authorization, B3 mutation and acceptance kernel, B5 host access integration and the minimum B8 installer/schema changes as a coordinated change. Update existing callers/fixtures so dependency removal cannot leave broken boot paths or authorization gaps. Creation/bootstrap and read-only member views belong in this foundation.
3. **Invitation and lifecycle completion:** implement B2 shared orchestration/UI, B4 lifecycle and B7 inviter-safe mail together with the foundation's hooks. Complete B7 account-deletion semantics and its host integration tests. No queued effects before commit.
4. **Remaining independent fixes and UI:** finish B6, retained B5 correctness work, B7 email uniqueness/docs and B8 robustness. Label/copy and dotenv work can start earlier if file ownership does not conflict, but navigation and new action availability wait for the new contracts.
5. **Integration review:** review the full removal and host-boundary diff; then independently verify the acceptance matrix below. Update README/SETUP/CHANGELOG/UPGRADE and repository architecture guidance to describe the implemented boundary. Phase 3 inherits host-owned authorization and no role choices in generic scaffold invitations.
6. **A8 handoff:** only after accepted required work and verification, resume final release preparation from the original Track A. B9 and existing-user pending acceptance remain deferred. Do not reopen completed A1–A7 or a consumer compatibility audit; A5 is intentionally superseded by dependency removal and A6's role FK disappears from the new schema.

## 6. Verification matrix

Implementation work uses focused regression/acceptance tests plus `composer test`, `composer analyse` and `vendor/bin/pint --test` before merge; apply formatting when implementation changes need it. This documentation-only planning revision does not claim those checks were run against a new implementation.

| Area | Required evidence |
|---|---|
| Dependency independence | Fresh dependency resolution/host boot without permission packages or tables; scan executable package/config/schema/installer code for obsolete imports, role IDs and admin flags. Documentation examples may mention optional host Spatie use. |
| Authorization | Missing policy, explicit deny, host allow and deliberate host global bypass; configured subclasses; UI and direct action calls; per-record bulk checks; no unscoped user-picker leak. |
| Simple host | App-owned admin flag allows invite/add/remove in one team; ordinary member reads/leaves; no privilege leakage to another team. The flag exists in the fixture/host only. |
| Scoped-grant host | A host fixture with separately stored per-target grants and an optional real Spatie reference integration prove target context, revocation/rejoin and optional program authority. Core tests do not require Spatie to boot. |
| Program overlap | One team linked to two programs; view/link/unlink/member operations honor each target and host rule; direct membership is distinguishable from inherited access. |
| Acceptance/bootstrap | Valid token workflow without invitee add permission; tampered email/target and stale token rejected; account-created-since-mount race; creator membership plus host grant commit/rollback together. |
| Transactions/concurrency | Duplicate sends/adds/links, resend versus accept, account deletion versus addition to a previously unrelated team, concurrent last-admin departures in a host fixture, and batch denial; use a supported production database for row-lock behavior rather than claiming SQLite proves it. |
| Events/mail | Correct actor or acceptance attribution, usable deletion payloads, no events/mail for rollbacks/no-ops, one dispatch owner, queued default and synchronous opt-out, transport failure accurately reported. |
| Lifecycle | Inviter deletion, account deletion/soft-delete host choice, role cleanup through host hooks, accepted-history truthfulness, no implied events from raw cascades. |
| Tenancy/UI | Enumeration equals direct access, correct panel argument, inaccessible remembered tenant rejected, read-only page versus writes, absent/denied panels and post-delete redirects; leaving the last team with creation/Admin access denied reaches an authorized non-tenant destination. |
| Installation/config | Both program modes, custom user/team/program/invites tables and FKs, fresh migration order/up/down, policy non-overwrite, dotenv round-trip and repeated install. |

## 7. Historical decisions superseded and work deliberately excluded

- Old B1's universal admin/member rights, `is_admin` toggle and mandatory last-admin rule are replaced by host policies/invariants. Program admin scope is a host decision rather than an unresolved package role schema.
- Old B2's role/admin intent, `roles.program_admin`, role-model compatibility and mandatory legacy method wrappers are removed. Host-specific privilege invitations use host-owned records/participants.
- Old B3's role assignment action/event and `ModelHasRole` adapter are removed. Membership events remain; external/durable audit delivery is not promised by an in-memory event.
- Old B5's implicit program inheritance and global permission strings are removed. The host explicitly chooses accessible tenants and panel admission.
- Old existing-install migration requirements and post-5.0 compatibility classification are removed under the user's consumer constraints. Track A's historical merge record is not rewritten.
- Existing-user pending invitation acceptance, a generic permission engine, a generic audit/outbox system, full Filament scaffold conversion, the monorepo move and B9 drill-down are outside this implementation scope.

## 8. Original planning evidence and handoff (2026-09-17)

This plan follows source inspection on 2026-09-17, not runtime confirmation of the proposed implementation. Relevant current code: `src/Models/User.php` (Spatie trait, role pivot, global permission shortcuts and program-derived teams); `src/Models/Team.php` (admin/member filters and immediate-add tracing); `src/Models/Program.php` (automatic Program Admin invite intent and many-to-many teams); `src/Models/ModelHasRole.php`; `src/Models/Invite.php`; `src/Filament/Auth/Register.php`; membership/invites migration stubs 2/3/6 and program FK stub 10; Admin User form/table/actions; installer/config; `tests/TestCase.php` (Super Admin bypass).

Direct Laravel Gate denies an unresolved ability in the installed `vendor/laravel/framework/src/Illuminate/Auth/Access/Gate.php`; Filament's helper in `vendor/filament/filament/src/helpers.php` can permit a missing policy unless strict authorization or a rejecting callback applies. The plan deliberately uses shared direct Gate checks.

The comparison also inspected `stats4sd/aec_portfolio` at `a666dc66c2dfe7c5156082ddf4c701e33c0269f7`: its Spatie context is organisation-scoped; membership and scoped role assignment are separate; portfolios do not introduce a second role scope. That is evidence for a host-owned permission adapter, not a ready-made program/team hierarchy to import.

This plan and the linked historical plans are local planning deliverables under `docs/`. This planning revision does not stage, commit or publish them. No runtime code, dependency or migration is changed by this planning revision.

## 9. Implementation decisions (2026-09-18)

The [bounded implementation contract](phase-1b-track-b-implementation.md) and [implementation log](../change-logs/phase-1b-track-b-membership-only.md) record the executed work, the clarity/consistency refinements and runtime evidence. The sections above preserve the original specification and its historical planning evidence; they are not the current verification report.

Self-removal now consistently uses `leave`, including direct/bulk removal callers. Team and program screens share actions/tables/results; program departure selects another accessible program before App fallback. Unconfigured existing-user pickers expose no candidates or actionable picker. Host model/pivot vetoes roll back the entire operation, and structural cleanup bypasses visibility scopes while direct operations still reject soft-deleted actors/members/targets. The chosen deletion wording states what the package actually removes. These refinements follow “keep it clear; keep it consistent” and do not add a package authorization backend.
