# Package review — functionality, usability, code quality, bugs

**Date:** 2026-07-06 · **Branch:** `dev` · **HEAD:** `2dc82ac`

**Scope:** full read of `src/`, `config/`, `database/`, `resources/`, `routes/`, README/SETUP, against the package's stated purpose: user/team/program management for Filament apps with an Admin panel, a Team-tenanted App panel, and an optional Program-tenanted panel.

---

## 1. Functionality gaps

Things a user + team management package of this shape is expected to provide but doesn't.

### 1.1 `is_admin` exists in the schema but does nothing

The `team_members.is_admin` column, `Team::admins()`/`Team::members()`, and the "Team Admins have full access…" helper text ([UsersRelationManager.php:52-54](src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L52-L54)) all promise a member/admin distinction. But:

- There is **no UI path that sets `is_admin`**. The only form containing the checkbox (admin panel → Team → Users relation manager) is unreachable because the table defines no `EditAction` ([UsersRelationManager.php:96-100](src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L96-L100)) — see also bug 4.5.
- Nothing **enforces** it. `ManageTeam` (rename team, invite, attach any user, detach any member) is available to every team member equally. `canAccessTenant()` never consults `is_admin`.
- The team creator is attached as a **non-admin** ([RegisterTeam.php:33](src/Filament/App/Pages/RegisterTeam.php#L33)), so even the DB-level flag is never true unless set manually.

This is the single biggest gap: the package currently has no intra-team authorization model at all.

### 1.2 Invite lifecycle is incomplete

- **No expiry.** Tokens are valid forever ([3_create_invites_table.php.stub](database/migrations/3_create_invites_table.php.stub) has no `expires_at`; `Register::mount()` checks only existence).
- **No resend** action anywhere.
- **No cancel/revoke** in the App or Program panels — the Invites tabs ([TeamInvitesTable.php](src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php), [ProgramInvitesTable.php](src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php)) have empty `recordActions`. Only the admin-panel relation managers can delete an invite.
- **No duplicate guard.** Inviting the same email twice creates two pending invites, each with its own valid token ([Team.php:53-58](src/Models/Team.php#L53-L58), [User.php:93-99](src/Models/User.php#L93-L99)).
- **Invite shapes are lopsided.** The Invite model supports team + role + program simultaneously, but each send path sets exactly one: team invites carry no role, admin "invite users" carries a role but no team, program invites hardcode the Program Admin role. There is no way to invite someone to a team *with* a role.

### 1.3 No "leave team" / self-service membership

A member can detach *other* members via the Members tab but there is no first-class "leave this team" action, and nothing prevents a user detaching themselves from their last team and stranding their session (stale `latest_team_id`, see gotcha 3.10).

### 1.4 No shipped policies, but code depends on them

`UsersRelationManager::isReadOnly()` calls `auth()->user()->can('update', $team)` ([UsersRelationManager.php:39](src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L39)). If the host app defines no `TeamPolicy`, the Gate denies, and the invite/attach buttons silently disappear in the admin panel. Neither README nor SETUP mentions that a policy is required. Either ship a sensible default policy or document the requirement.

### 1.5 Permission set is incomplete and never wired up

The package's gates rely on four permissions: `access admin panel`, `access program admin panel`, `view all teams`, `view all programs`. `TestUserSeeder` creates only the first and third, never creates the program pair, and **never assigns any permission to any role** ([TestUserSeeder.php:17-24](database/seeders/TestUserSeeder.php#L17-L24)). A freshly seeded "Test Admin" with the Super Admin role cannot pass `CheckIfAdmin` unless the host app also adds a `Gate::before` super-admin bypass — which is not documented anywhere.

### 1.6 No extensibility events for membership changes

Only `RegisteredWithData` exists. There are no events for invite sent, invite accepted, member added/removed, or admin toggled, so host apps (e.g. the ODK Central integration this package is designed to feed) cannot react to membership changes without overriding models.

### 1.7 Program panel can't manage teams' members

A program admin can create/attach/detach teams ("Projects" tab) but cannot see or manage the members *of* those teams — there's no drill-down. For a "program manages groups of teams" model, this is a likely early feature request.

### 1.8 Tenant registration is open to everyone

`RegisterTeam` / `RegisterProgram` are unrestricted: any authenticated user gets a "Register New Team" item in the tenant menu, and any user who can enter the program panel can create programs. There is no config flag or permission gate for "who may create teams/programs". For an invite-only product this is inconsistent.

### 1.9 Account-lifecycle loose ends

- Deleting a user (admin panel bulk delete) leaves orphaned `invites.inviter_id` rows (no FK) and orphaned `model_has_roles` rows.
- No password reset, email verification, or profile page is configured or documented (the `Registered` event is fired, and the dead `app()->bind(SendEmailVerificationNotification::class)` call in [Register.php:98-100](src/Filament/Auth/Register.php#L98-L100) suggests verification behaviour was intended but never finished).
- Email uniqueness is not validated when an admin edits a user ([UserForm.php:22-26](src/Filament/Admin/Resources/Users/Schemas/UserForm.php#L22-L26)); duplicate emails then break the email-based invite matching (`User::where('email', …)->first()` picks one arbitrarily).

---

## 2. Usability gaps

### 2.1 The same "invite" action exists in five places with three shapes and different side effects

| Location | Form | Side effect for existing users |
|---|---|---|
| App panel → Manage Team → Members tab | emails only | added to team |
| Admin → Team → Users RM | emails only | added to team |
| Admin → Users list | email + role picker | role assigned |
| Admin → Program → Users RM | emails only | added to program **+ silently made Program Admin** |
| Program panel → Manage Program → Members tab | emails only | added to program **+ silently made Program Admin** |

The program paths hardcode the `Program Admin` role for every invitee ([Program.php:43,61,89](src/Models/Program.php#L43)) with no indication in the UI. An admin inviting a plain member to a program is actually granting panel-level admin rights. The five entry points should share one action component with an explicit role choice (or at least a consistent, visible default).

### 2.2 Display names are built four different ways, one of which is a nonexistent config key

- `config('filament-team-management.names.team')` and `names.program` **do not exist in the config file**, so users see literal gaps: *"…you would like to invite to this . An invitation will be sent…"* ([TeamMembersTable.php:37](src/Filament/App/Pages/ManageTeam/TeamMembersTable.php#L37), [ProgramMembersTable.php:37](src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php#L37)) and *"…permanently delete this  and all associated teams…"* ([ViewProgram.php:23](src/Filament/Admin/Resources/Programs/Pages/ViewProgram.php#L23)).
- Elsewhere labels are built from **table names**: "Add Existing User to teams" ([UsersRelationManager.php:94](src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L94)), relation manager titles from `table_names.users`, column label "teams assigned" from `table_names.teams` ([TeamInvitesTable.php:23](src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L23)).
- Elsewhere from the **model class name** (`getModelNameLower()`), and elsewhere **hardcoded** ("Register New Team", "Remove User from Program").

Pick one mechanism — a real `names.*` config block with sensible defaults would fix both the blanks and the inconsistency, and is what the two `names.*` call sites already assume exists.

### 2.3 The App panel "Members" tab hides team admins

[TeamMembersTable.php:22](src/Filament/App/Pages/ManageTeam/TeamMembersTable.php#L22) binds to `members()`, which is filtered to `is_admin = 0` ([Team.php:151-161](src/Models/Team.php#L151-L161)). Any user flagged as admin (via seeder or DB) silently vanishes from the team's own member list, while the admin panel's Users relation manager (bound to `users()`) shows everyone. Same information, two different answers depending on where you look.

### 2.4 Invites tables are confusing

- Confirmed invites are hidden by the global scope, and the toggle that reveals them is labelled "Show accepted invites" but named `only_unconfirmed` and implemented as "remove the scope" — three mental models for one switch ([TeamInvitesTable.php:30-35](src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L30-L35)).
- The `is_confirmed` icon column is therefore always ✗ unless the toggle is on.
- The `team.name` column always shows the current tenant (every invite in the table belongs to it) and the `project.name` column is always empty (wrong relation name — bug 4.4).
- "# Invites" on the admin Teams table counts only *pending* invites (the global scope applies to `withCount`) but nothing says so ([TeamTable.php:26-29](src/Filament/Admin/Resources/Teams/Tables/TeamTable.php#L26-L29)).

### 2.5 "Projects" vs "Teams" terminology split

The Program panel calls teams "Projects" (`ManageProgramProjects`, "Add Existing Projects", tab title) while every other surface — including the *same page's* member/invite emails — says team ([ProgramProjectsTable.php:36](src/Filament/Program/Pages/ManageProgram/ProgramProjectsTable.php#L36)). If "project" is the intended Program-panel vocabulary it should come from config; if not, rename to Teams.

### 2.6 Cross-panel links are hardcoded and shown to the wrong people

- `ManageTeam::getSubheading()` renders links to `/program/{id}` for **every** team member ([ManageTeam.php:38](src/Filament/App/Pages/ManageTeam/ManageTeam.php#L38)); non-program-admins click through to a 403. The panel path is also hardcoded (host apps may mount the panel elsewhere).
- `ViewTeam` delete redirects to hardcoded `/app` ([ViewTeam.php:30](src/Filament/Admin/Resources/Teams/Pages/ViewTeam.php#L30)).

### 2.7 An invalid or already-used invite link is a bare 404

`Register::mount()` `firstOrFail()`s ([Register.php:33](src/Filament/Auth/Register.php#L33)). A user who clicks an old link (already registered, or invite deleted) gets a framework 404 with no explanation and no route to login. README claims they are "redirected to the login page", which is not what the code does.

### 2.8 Attach actions expose the full user directory to every team member

"Add Existing Users" in the App panel Members tab lets any team member search all registered users by email ([TeamMembersTable.php:50-52](src/Filament/App/Pages/ManageTeam/TeamMembersTable.php#L50-L52)) — both a privacy leak and a way to bypass the invite flow entirely. In the admin panel that's appropriate; in the end-user panel it likely isn't.

### 2.9 Stats4SD-specific copy leaks into a generic package

"WARNING: Please do not delete when there is actual survey data collected…" ([ViewTeam.php:26](src/Filament/Admin/Resources/Teams/Pages/ViewTeam.php#L26)). Also the Program delete modal claims it "will permanently delete this … and all associated teams and data", but no cascade deletes teams — only the pivot rows go ([7_create_program_team_table.php.stub](database/migrations/7_create_program_team_table.php.stub)). The warning is both alarming and wrong.

---

## 3. Code gotchas (non-default practice, refactor candidates, over-engineering)

### 3.1 `sendInvites()` is triplicated

`User::sendInvites`, `Team::sendInvites`, `Program::sendInvites` are ~90% identical ([User.php:81-129](src/Models/User.php#L81-L129), [Team.php:40-113](src/Models/Team.php#L40-L113), [Program.php:41-118](src/Models/Program.php#L41-L118)) and all four bugs in that logic (morph class, null role, duplicates, hardcoded `User::`) exist in each copy. Extract one `InviteService` (or trait) taking `{email, role?, team?, program?}`.

### 3.2 Filament `Notification`s and `Mail::send` inside Eloquent models

The models fire Filament session notifications and send mail directly. That couples domain models to a panel request context — calling `sendInvites()` from a command, job, or test sends notifications into the void, and mail failures abort mid-loop leaving half the invites created. Move UI feedback to the calling action; consider queueing the mailables (they already `use Queueable`).

### 3.3 Config indirection is claimed but not consistently applied

The package's core convention (CLAUDE.md: "never hardcode model classes") is violated in the invite paths: `User::where(...)` ([User.php:90](src/Models/User.php#L90), [Team.php:50](src/Models/Team.php#L50), [Program.php:52](src/Models/Program.php#L52)), `Role::find`/`Role::where` ([User.php:112](src/Models/User.php#L112), [Program.php:43](src/Models/Program.php#L43)), `User::find` and `Role::find` in [ModelHasRole.php:29-32](src/Models/ModelHasRole.php#L29-L32), `Invite::class` relations, and `PermissionResource` hardcodes Spatie's `Permission` ([PermissionResource.php:11](src/Filament/Admin/Resources/Permissions/PermissionResource.php#L11)). The `User::` ones are actual bugs when the host subclasses User (see 4.6). Note also there is no `table_names.invites` key — the `invites` table name is hardcoded in both model and migration, unlike every other table.

### 3.4 Two derivations of the same foreign key

`Team::invites()` derives its FK from the class name (`static::getModelNameLower() . '_id'`, [Team.php:120](src/Models/Team.php#L120)) while the invites migration derives the column from `column_names.teams_foreign_key`. Rename the Team model (e.g. `Squad`) without renaming the column and the relation queries `squad_id` against a table whose column is `team_id`. Use the config key in the relation.

### 3.5 The `latest*` middleware writes on every request

`SetLatestTeamMiddleware` saves the user row on **every** tenant request even when the tenant hasn't changed ([SetLatestTeamMiddleware.php:31](src/Http/Middleware/SetLatestTeamMiddleware.php#L31)). Guard with a dirty check (`latest_team_id !== $tenant->getKey()`). Neither middleware verifies the tenant type, so adding the team middleware to the program panel (an easy wiring mistake, since both must be added manually) would write a program id into `latest_team_id`.

### 3.6 Dead and vestigial code

- [src/Filament/Auth/RegisterResponse.php](src/Filament/Auth/RegisterResponse.php) — unused duplicate of `Http\Responses\RegisterResponse` (only the latter is referenced).
- [routes/team-management.php](routes/team-management.php) — registered via `hasRoute()` but entirely commented out.
- [src/FilamentTeamManagement.php](src/FilamentTeamManagement.php) + its Facade — empty class.
- [src/FilamentTeamManagementPlugin.php](src/FilamentTeamManagementPlugin.php) — empty `register()`/`boot()`; the README never mentions it. Ironically a real plugin could replace most of the manual panel wiring the README asks hosts to do.
- `ProgramInvite` model + factory — no `program_invites` migration exists (flagged in the 2026-06-23 review); the Program invite UI actually uses `Invite`. Delete the model, factory, and its commented-out global scope.
- [resources/views/filament/app/pages/manage-team.blade.php](resources/views/filament/app/pages/manage-team.blade.php) and the commented `HasRelationManagers`/`$view` lines in [ManageTeam.php:20-24](src/Filament/App/Pages/ManageTeam/ManageTeam.php#L20-L24).
- `$programTypeName` unused in [ProgramForm.php:15](src/Filament/Admin/Resources/Programs/Schemas/ProgramForm.php#L15); `$roleModel` unused in [InstallFilamentTeamManagement.php:175](src/Commands/InstallFilamentTeamManagement.php#L175); duplicate `$invite->save()` after `Invite::create()` in [ModelHasRole.php:46](src/Models/ModelHasRole.php#L46).

### 3.7 `ModelHasRole::created` is a fragile place for side effects

- Uses legacy `boot()` + closure instead of `booted()`.
- `auth()->id() === null` as the "is this self-registration?" discriminator also matches every console/job/seeder context, so programmatic role assignments silently skip the tracing invite — and conversely, any role attached to a *non-user* model while an admin is logged in would `User::find($item->model_id)` the wrong table's id ([ModelHasRole.php:29](src/Models/ModelHasRole.php#L29)). Checking `$item->model_type` against the configured user model would make it safe.
- Firing mail + Filament notifications from a pivot-model event is action-at-a-distance; a `UserRoleAssigned` event with a listener would be conventional.

### 3.8 `HasModelNameLowerString` returns a `Stringable` declared as `string`

[HasModelNameLowerString.php:11](src/Models/Traits/HasModelNameLowerString.php#L11) returns `Str::of(...)->snake()` (a `Stringable`) from a `: string` method — works only via implicit coercion. Also, snake-casing means a host model `FieldTeam` yields headings like "Manage Field_team" (`ucfirst('field_team')`). `->snake(' ')` or `Str::headline()` handled once here would fix every label downstream.

### 3.9 Self-referencing `HasOne` hack

`Team::team()` / `Program::program()` (`hasOne(self, 'id')`, [Team.php:177-180](src/Models/Team.php#L177-L180)) exist so a resource can "show the selected team for editing". This is non-standard and confusing; Filament's `EditTenantProfile` (already used) makes it unnecessary — nothing in the package references either relation. Also `Team::admins()`/`members()` re-declare the whole `belongsToMany` instead of `return $this->users()->wherePivot('is_admin', …)`.

### 3.10 Tenancy edge cases in `User`

- `getDefaultTenant()` passes `Filament::getCurrentPanel()` into `getTenants()` instead of the `$panel` it was given ([User.php:305](src/Models/User.php#L305)).
- `latestTeam` is returned as default tenant without checking it's still accessible — a user removed from their last-used team gets a 404 loop until the stale `latest_team_id` is cleared.
- `canAccessTenant()`/`getAllAccessibleTeams()` load full `teams` and `programs->pluck('teams')` collections per request — fine at Stats4SD scale, but an `exists()` query would be conventional.

### 3.11 Installer robustness

- `.env` values are written unquoted; class names with backslashes (`App\Models\User`) are exactly the values dotenv recommends quoting.
- The brace-matcher for `DatabaseSeeder::run()` counts braces inside strings/comments and would mangle such a file; it also appends `$this->call(...)` without indentation.
- `.env.example` gets the variable list filtered against `.env`'s contents rather than its own ([InstallFilamentTeamManagement.php:200-219](src/Commands/InstallFilamentTeamManagement.php#L200-L219)).
- The install writes several env var names the config never reads — see bugs 4.1/4.2, which belong in the next section because they break things outright.

### 3.12 Conditional migrations freeze config at migrate time

`3_create_invites_table` and `9_add_column_to_users_table` create program columns only `if (config('use_programs'))`. An app that enables programs later has migrated tables missing `program_id`/`latest_program_id` and no migration to add them. Prefer always creating the nullable columns, or ship an idempotent "enable programs" migration.

### 3.13 Composer dependency hygiene

The package `use`s `Spatie\Permission\*` classes throughout but does not require `spatie/laravel-permission` directly — it arrives transitively via `althinect/filament-spatie-roles-permissions`. Declare it explicitly.

---

## 4. Actual bugs

Ordered by severity.

### 4.1 `programs_foreign_key` reads the wrong env var — installer-configured program apps get a class name as a column name

[config/filament-team-management.php:33](config/filament-team-management.php#L33):

```php
'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', 'program_id'),
```

The install command sets `FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL=Stats4sd\FilamentTeamManagement\Models\Program` whenever programs are enabled ([InstallFilamentTeamManagement.php:193](src/Commands/InstallFilamentTeamManagement.php#L193)). From then on every program relationship, the program migrations, and `Invite::program()` use the **class name as the FK column**, producing SQL errors across the whole programs feature. The env key should be `FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY` (which is what the installer writes, and which the config never reads).

### 4.2 Installer and config disagree on the users-table env var names

Config reads `FILAMENT_TEAM_MANAGEMENT_USERS_TABLE` / `FILAMENT_TEAM_MANAGEMENT_USERS_FOREIGN_KEY` ([config:21,31](config/filament-team-management.php#L21)); the installer writes `FILAMENT_TEAM_MANAGEMENT_USER_TABLE` / `FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY` ([InstallFilamentTeamManagement.php:181-182](src/Commands/InstallFilamentTeamManagement.php#L181-L182)). Custom users tables/FKs configured by the installer are silently ignored. (Also: the installer never writes `FILAMENT_TEAM_MANAGEMENT_USER_MODEL`'s companion `ROLE` var despite computing `$roleModel`.)

### 4.3 `Team::programs()` and `Program::teams()` use nonexistent config keys

`table_names.team_programs` ([Team.php:168](src/Models/Team.php#L168)) and `table_names.program_teams` ([Program.php:144](src/Models/Program.php#L144)) don't exist — the real key is `program_team` ([config:25](config/filament-team-management.php#L25)). `belongsToMany(table: null)` falls back to Laravel's alphabetical convention `program_team`, so it *happens* to work with defaults, but any host that renames the pivot via `FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE` migrates one table and queries another → "table not found" on every program↔team operation. `ConfigIndirectionTest` covers `team_members` but not this pivot, which is why it slipped through.

### 4.4 `project.name` column — the Invite relation is `program`

[TeamInvitesTable.php:24](src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L24) and [ProgramInvitesTable.php:25](src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php#L25) render `TextColumn::make('project.name')`; `Invite` has no `project` relation ([Invite.php](src/Models/Invite.php)), so the "programs assigned" column is permanently blank.

### 4.5 The team-admin checkbox form is unreachable

[Teams/RelationManagers/UsersRelationManager.php:46-56](src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php#L46-L56) defines the `is_admin` pivot form, but the table's `recordActions` contain only `DetachAction` — no `EditAction`, so the form never opens. Combined with `RegisterTeam` attaching creators as non-admins (bug-adjacent, [RegisterTeam.php:33](src/Filament/App/Pages/RegisterTeam.php#L33)), `is_admin` can never become true through the UI. (The Programs Users RM has the same dead form, and its copy would edit the user's *name* — [Programs/RelationManagers/UsersRelationManager.php:39-47](src/Filament/Admin/Resources/Programs/RelationManagers/UsersRelationManager.php#L39-L47).)

### 4.6 Inviting an existing user assigns roles under the wrong morph type when User is subclassed

All three `sendInvites()` implementations look the user up via the package's own `User` class (e.g. [User.php:90](src/Models/User.php#L90), [Program.php:52](src/Models/Program.php#L52)) instead of `config('filament-team-management.models.user')`. When the host app uses `App\Models\User` (the documented setup), `$user->roles()->attach($role)` writes `model_type = Stats4sd\FilamentTeamManagement\Models\User` into `model_has_roles`. The host's user model then never sees the role — `hasRole()`, `can()`, and the panel gates all fail for that user, with a confirmed invite row claiming success. Team/program pivot attaches survive (integer FKs), which makes the role half-failure extra confusing.

### 4.7 `Program::sendInvites()` fatals if the `Program Admin` role doesn't exist

[Program.php:43,61](src/Models/Program.php#L43): `Role::where('name', 'Program Admin')->first()->id` → null-pointer on any install that didn't run the package seeders (which are optional). Hardcodes both the Spatie `Role` class and the role name.

### 4.8 Admin Invites relation managers offer Create/Edit with no form

[Teams/.../InvitesRelationManager.php:44-48](src/Filament/Admin/Resources/Teams/RelationManagers/InvitesRelationManager.php#L44-L48) and [Programs/.../InvitesRelationManager.php:42-46](src/Filament/Admin/Resources/Programs/RelationManagers/InvitesRelationManager.php#L42-L46) expose `CreateAction`/`EditAction`, but neither relation manager defines a form. Create opens an empty modal and the insert fails (`email` is NOT NULL, no token, no mail sent). These actions should be removed or replaced with a proper "send invite" action like the Users RMs have.

### 4.9 Seeded permissions are never attached to roles

[TestUserSeeder.php](database/seeders/TestUserSeeder.php) creates `Super Admin` and permissions `access admin panel` / `view all teams` but never links them; `access program admin panel` and `view all programs` are never created at all. Out of the box, the seeded admin is blocked by `CheckIfAdmin`, and program members are blocked by `CheckIfProgramAdmin` even though `TestProgramSeeder` sets them up as program-team admins.

### 4.10 Registration's signed URL is security theater, and `mount()` skips the base checks

- `InviteUser` mail builds the accept link with `URL::signedRoute(...)` ([InviteUser.php content()](src/Mail/InviteUser.php)), but the Filament register route has no `signed` middleware and `Register::mount()` never validates the signature — only the raw `?token=` matters. Harmless today, but it implies tamper protection that doesn't exist, and the signature breaks if a host *does* add `signed` middleware while Livewire strips the query param.
- The overridden `mount()` ([Register.php:30-42](src/Filament/Auth/Register.php#L30-L42)) doesn't call the parent, dropping Filament's "already logged in → redirect away" guard: a logged-in user following an invite link lands on a working second-registration form.

### 4.11 `app()->bind(SendEmailVerificationNotification::class)` is a no-op

[Register.php:98-100](src/Filament/Auth/Register.php#L98-L100) binds the listener class to itself and changes nothing. Whatever it was meant to do (probably suppress the verification email for invited users), it doesn't do it.

### 4.12 `->rule('min:10', 'Password must be…')` — the second argument is a *condition*, not a message

[Register.php:133](src/Filament/Auth/Register.php#L133): Filament's `rule($rule, $condition)` treats the message string as a truthy condition. The rule applies, the custom message is silently discarded, and the user sees the default "must be at least 10 characters" — currently cosmetic, but the call doesn't do what it says.

### 4.13 `ProgramMembersTable` declares its inverse relationship as `teams`

[ProgramMembersTable.php:23](src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php#L23): the table is `Program::members()` (User models); the inverse on User is `programs`, not `teams`. Likewise both invite tables declare `inverseRelationship('teams')` for a `BelongsTo` whose inverse is `team`/`program` ([TeamInvitesTable.php:19](src/Filament/App/Pages/ManageTeam/TeamInvitesTable.php#L19), [ProgramInvitesTable.php:20](src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php#L20)). Wrong inverse names surface as broken attach scoping/eager loads in Filament; at minimum they're incorrect declarations copy-pasted from the team table.

### 4.14 `CheckIfAdmin` / `CheckIfProgramAdmin` null-deref when unauthenticated

Both call `auth()->user()->can(...)` ([CheckIfAdmin.php:19](src/Http/Middleware/CheckIfAdmin.php#L19)). Correct ordering behind `Authenticate` is the host app's responsibility per README, so any wiring mistake becomes a 500 instead of a 403/redirect. One `?->` plus an `abort_unless` would make them safe.

### 4.15 README behaviour claims that don't match the code

- Nav examples gate on `can('viewAdminPanel')` but the middleware permission is `access admin panel` — copying the README example gives a nav item that never appears (or appears for the wrong people if hosts create a `viewAdminPanel` permission).
- The `SetLatestTeamMiddleware`-in-`tenantMiddleware` requirement is still a TODO footnote despite `getDefaultTenant()` depending on it.

---

## Suggested priorities

1. **4.1 + 4.2 + 4.3** — config/installer key mismatches: small diffs, they break the package's headline "everything is config-indirected" promise, and a `ConfigIndirectionTest` extension can lock them down.
2. **4.6 + 4.7** — invite-an-existing-user paths (morph type, null role): silent data corruption in the core flow.
3. **1.1 + 4.5** — decide what `is_admin` means, expose it, enforce it; everything else about team management UX hangs off this.
4. **2.1 + 3.1** — consolidate the five invite entry points onto one service + one action component; fixes the Program Admin surprise and the blank-label callouts (2.2) in the same pass.
