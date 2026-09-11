# Upgrade guide

This file lists every change a consuming app must act on when moving between major versions of `stats4sd/filament-team-management`. The [CHANGELOG](CHANGELOG.md) describes what changed; this file describes what you have to do about it.

## 4.x → 5.0

5.0 moves the package to Laravel 13, Filament 5 and Livewire 4, and ships the Phase 1 defect fixes from the 2026 package review. Work through each section in order.

### Dependencies

- [ ] App is on **Laravel 13, Filament ^5.2, Livewire ^4** before requiring this version. The package no longer resolves on Filament 4 / Livewire 3. The app's own panels, resources, and Livewire components must already be upgraded (see the Filament and Livewire upgrade guides).
- [ ] PHP ^8.4 (unchanged from 4.0.7).
- [ ] **`awcodes/shout` is no longer a dependency.** If the app used `Shout::make()` anywhere without requiring `awcodes/shout` itself, either require it directly or migrate to Filament's `Callout` (`->type('info')` → `->info()`, `->content()` → `->description()`).
- [ ] `althinect/filament-spatie-roles-permissions` is now `^3.0` stable (was `^3.x-dev`). Remove any `minimum-stability` / `prefer-stable` workarounds added for it.

### Environment variables now honoured

Three env vars the installer has always written were previously ignored by the config. They are now read, so values that silently did nothing before now take effect. The full list of keys is in the README ([Environment variables](README.md#environment-variables)).

- [ ] `FILAMENT_TEAM_MANAGEMENT_USER_TABLE` and `FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY`: if the app hand-set the old plural names `USERS_TABLE` / `USERS_FOREIGN_KEY`, rename them. If the installer wrote non-default values (custom users table or model), confirm they match the real table and pivot column names, because they were ignored under 4.x. (`ae-policy-tracking-tool` has the plural names in `.env.example`.)
- [ ] `FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY`: under 4.x `programs_foreign_key` read `PROGRAM_MODEL`, so any program app that worked had either no `PROGRAM_MODEL` set or a patched config. Confirm the value (default `program_id`) matches the column in `program_members`, `program_team`, `invites`, and `users.latest_program_id`.
- [ ] `FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE`: `Team::programs()` / `Program::teams()` now use this key. If it is set to a non-default value, confirm the pivot table actually has that name (4.x created it with the configured name but queried `program_team`).
- [ ] `FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL`: newly written by the installer; only relevant if the app uses a custom Spatie role model and never set it.

### Published config

- [ ] If the app has published `config/filament-team-management.php`, add the new `names` block (`'names' => ['team' => 'team', 'program' => 'program']`). Without it the invite callouts and program delete modal render a blank where the noun should be.

### Panel wiring

These were always required; 5.0 makes them explicit in the README.

- [ ] App panel `->tenantMiddleware([SetLatestTeamMiddleware::class], isPersistent: true)`; Program panel the same with `SetLatestProgramMiddleware`. `getDefaultTenant()` depends on this. It was a README TODO in 4.x.
- [ ] Navigation items copied from the 4.x README that check `can('viewAdminPanel')` must change to `can('access admin panel')` (admin links) or `can('access program admin panel')` (program link). If the app created a `viewAdminPanel` permission to make the old example work, it can be removed.
- [ ] The four permissions `access admin panel`, `access program admin panel`, `view all teams`, `view all programs` must exist and be assigned to the intended roles. The package seeder now does this; apps with their own seeders should mirror it. A `Gate::before` super-admin bypass still works but is no longer needed for the seeded admin.

### Behaviour changes

- [ ] **Team creators are now team admins.** `RegisterTeam` attaches the creating user with `is_admin = true`. Existing teams have no admin flagged; decide whether to backfill (e.g. set `is_admin` for the oldest member of each team). `is_admin` is still not enforced, so this changes data, not access.
- [ ] **App panel → Manage Team → Members now lists every member, including admins.** Under 4.x (and briefly under the 5.0 development branch) the tab was bound to the non-admin-only relationship, so the team creator vanished from their own members list.
- [ ] **Admin panel → Team → Users** gains an "Edit Role" action that toggles `is_admin`. **Admin panel → Program → Users** loses the (dead) name-edit form. **Admin panel Invites relation managers** lose Create and Edit; Delete remains. Update any app tests or overrides that referenced those actions.
- [ ] **Program invites with no `Program Admin` role** now show a warning and send nothing instead of throwing. Make sure the role exists in every environment (the package seeder creates it).
- [ ] **Invite emails link to an unsigned URL.** `InviteUser` now uses `route()` instead of `URL::signedRoute()`. If the app added `signed` middleware to the register route, or its tests assert a `signature=` parameter, remove them. Apps overriding the `InviteUser` mailable or `emails.invite` view should check their copies.
- [ ] **Register page:** an authenticated user following an invite link is redirected to the panel home; a missing or invalid token redirects to login (4.x returned 404). Apps overriding `Register::mount()` should port both guards.
- [ ] **Register password rule:** the custom "at least 10 characters" message now actually displays. No action, unless tests asserted the default Laravel message.
- [ ] **`CheckIfAdmin` / `CheckIfProgramAdmin`** return 403 when unauthenticated (was a 500). No action.
- [ ] **Inviting an existing user now records the role under the app's User class** in `model_has_roles.model_type`. Under 4.x it wrote the package's `Stats4sd\FilamentTeamManagement\Models\User`, so roles granted via invite to existing users never applied. Check `model_has_roles` for rows with the package class as `model_type` and rewrite them to the app's class (or re-assign the roles).
- [ ] **`TestUserSeeder`** is now idempotent (`findOrCreate`) and attaches permissions. Apps that run it alongside their own seeders that `Role::create` the same names will still collide on the app side, as before.

### Removed classes and files

Dead code that nothing in the package used has been deleted. None of the five active consuming apps reference any of it in application code (checked 2026-09-11); notes on the two incidental hits are inline.

- [ ] `Stats4sd\FilamentTeamManagement\FilamentTeamManagement` and its facade `Stats4sd\FilamentTeamManagement\Facades\FilamentTeamManagement` (both were empty), plus the `FilamentTeamManagement` alias in `composer.json` `extra.laravel.aliases`. `apni-research` has a generated reference in `_ide_helper.php`; regenerate it with `php artisan ide-helper:generate` after updating.
- [ ] `Stats4sd\FilamentTeamManagement\FilamentTeamManagementPlugin` (an empty Filament plugin). If a panel called `->plugin(FilamentTeamManagementPlugin::make())`, remove that line; the package registers nothing through it.
- [ ] `Stats4sd\FilamentTeamManagement\Filament\Auth\RegisterResponse`. `Register` uses `Http\Responses\RegisterResponse`, which stays.
- [ ] `Stats4sd\FilamentTeamManagement\Models\ProgramInvite` and `ProgramInviteFactory`. Program invites have been rows in `invites` with a `program_id` since 2.0; this model pointed at the long-gone `program_invites` table.
- [ ] `routes/team-management.php` (contained only commented-out routes) and the provider's `hasRoute()` / `getRoutes()`. The package registers no routes of its own; Filament panels own the auth routes.
- [ ] The view `filament-team-management::filament.app.pages.manage-team` (a placeholder never rendered). `ManageTeam` uses Filament's default tenant-profile view.
- [ ] `Team::team()` and `Program::program()`, self-referencing `hasOne` relations declared on `TeamInterface` / `ProgramInterface`. If a custom Team/Program model implements the interface and declared these methods, they can be deleted; if anything eager-loaded `->with('team')` on a Team, use the model itself.

### Verify after upgrade

- [ ] Log in as a seeded admin and reach the Admin panel (permission wiring).
- [ ] Register a new team, then open Manage Team → Members and confirm the creator is listed.
- [ ] Invite a new address and an existing user from each entry point (App Members, Admin Team Users, Admin Users, Admin Program Users, Program Members); confirm role/team/program land on the correct user class.
- [ ] Follow an invite link while logged in, then logged out, then with a bad token.
- [ ] Program panel: attach/detach a team, invite a member, confirm they can enter the panel.
