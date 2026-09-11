# Changelog

All notable changes to `filament-team-management` will be documented in this file.

## 5.0.0 - Filament 5 / Livewire 4 / Laravel 13 - unreleased

**Requires Laravel 13, Filament ^5.2, Livewire ^4 and PHP ^8.4.** This is a breaking release. Every change a consuming app must act on is listed in [UPGRADE.md](UPGRADE.md); read it before updating.

### Framework

- Upgraded to Filament 5 and Livewire 4 (Laravel 13). `awcodes/shout` is no longer a dependency; the package uses Filament's `Callout` instead.
- `althinect/filament-spatie-roles-permissions` is now required at `^3.0` stable.
- `spatie/laravel-permission` is now a direct dependency (`^7.0`); the package extends its models and pivots, so it no longer relies on the transitive requirement. (3.13)

### Fixes

- **Config keys ignored under 4.x are now read.** The config read `FILAMENT_TEAM_MANAGEMENT_USERS_TABLE` / `USERS_FOREIGN_KEY` while the installer wrote the singular `USER_TABLE` / `USER_FOREIGN_KEY`, so custom users-table settings never took effect. The config now reads the singular keys the installer writes. `programs_foreign_key` read the wrong env key (`PROGRAM_MODEL`) and now reads `PROGRAMS_FOREIGN_KEY`. The installer now also writes `ROLE_MODEL`. A parity test guards installer-written keys against config-read keys in both directions. (Review 4.1, 4.2)
- **Team ↔ Program pivot table name was wrong.** `Team::programs()` and `Program::teams()` queried `team_programs` / `program_teams`; the migration creates `program_team`. Both now use the configured `table_names.program_team`. (4.3)
- **Invites and role tracing now honour the app's User and Role classes.** `sendInvites()` and the role-assignment tracing resolved the package's own `User` / `Role` models, so roles granted to existing users via invite were recorded against the wrong class and never applied. They now resolve through `models.user` / `models.role`. (4.6)
- **Inviting to a program with no `Program Admin` role no longer crashes.** A missing role now surfaces a warning notification and sends nothing. (4.7)
- **UI copy no longer renders a blank where the team/program noun should be.** Added the `names` config block that the invite callouts and program delete modal read. Also fixed the program members table using the team word instead of the program word. (2.2)
- **Invite tables showed an empty "program" column.** The column was bound to a non-existent `project` relationship; it now reads `program.name`. (4.4)
- **Team admin flag was unreachable.** The admin panel Team → Users relation manager gains an "Edit Role" action to toggle `is_admin`, and the user who registers a team is now attached as its admin. The dead name-edit form on Program → Users was removed. (4.5)
- **Manage Team → Members tab hid team admins.** The tab was bound to the non-admin-only relationship, so the team creator vanished from their own members list. It now lists all members. (2.3)
- **Admin Invites relation managers no longer offer Create/Edit.** Invites are system-generated and double as an audit log; hand-authored invites are not supported. Delete remains. (4.8)
- **Wrong `inverseRelationship` names on tenant tables** (`teams` where the inverse was `program`, `programs` or `team`) corrected. (4.13)
- **Invite emails now link to a plain (unsigned) URL.** The token is the secret; Livewire stripped the signature parameter anyway. (4.10)
- **Register page guards.** An already-authenticated user following an invite link is redirected to the panel home; a missing or invalid token redirects to login instead of a 404. (4.10)
- **Password minimum-length message now displays** (the rule was enforced, the custom message was not). (4.12)
- Removed an inert `SendEmailVerificationNotification` bind. (4.11)
- **`CheckIfAdmin` / `CheckIfProgramAdmin` return 403 for guests** instead of a 500 null dereference. (4.14)
- **Seeded admins can reach the admin panels.** `TestUserSeeder` now creates the four permissions (`access admin panel`, `access program admin panel`, `view all teams`, `view all programs`) and attaches them to the seeded roles, idempotently. (4.9)
- **Removed dead code** (breaking only if an app imported it; none of ours do): the empty `FilamentTeamManagement` class, its facade and composer alias; the empty `FilamentTeamManagementPlugin`; the unused `Filament\Auth\RegisterResponse`; the `ProgramInvite` model and factory (program invites live in `invites` since 2.0); the empty `routes/team-management.php` and provider route registration; the placeholder `manage-team` view; and the self-referencing `Team::team()` / `Program::program()` relations and their interface declarations. See UPGRADE.md "Removed classes and files". (3.6, 3.9)
- **Docs:** README navigation examples used an invented `viewAdminPanel` permission; corrected to the real strings and added a canonical Permissions section. `SetLatestTeamMiddleware` / `SetLatestProgramMiddleware` are documented as required tenant middleware. (4.15)

## 4.0.7 - Hide Program Invite Info - 2025-12-08

Small update to hide the 'programs assigned' column in the Team Invites table when not using programs.

## 4.0.6 - Add non-team / non-panel panel tenancy fallback - 2025-11-18

If the tenant Model is not the defined `Team` or `Program` model, it now falls back to trying to guess the tenancy relationship name based on the tenant model of the current panel.

Also fixes a bug where the program relationship was being checked even when `use_progams` was set to false.

## 4.0.5 - Should have been in the previous - 2025-11-17

One hotfix makes you identify another that's needed...

Fixes ordering of the teams() and programs() key variables for the `User` model.

## 4.0.4 - User teams relationship fix - 2025-11-17

Hotfix for a bug encountered in apps that don't use the default "team_membes" table name.

## 4.0.3 - Fixes for non-program apps - 2025-11-14

Bug fixes for apps that don't use programs.

## 4.0.2: More Test Update Attempts - 2025-11-13

More test updates. I wonder if the badge will update?

## 4.0.1 - Fixed Tests - 2025-11-13

### What's Changed

* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/stats4sd/filament-team-management/pull/35
* Add Program Panel Submodule by @dan-tang-ssd in https://github.com/stats4sd/filament-team-management/pull/36
* Add management pages submodule by @dave-mills in https://github.com/stats4sd/filament-team-management/pull/37
* Invite workflow updates by @dave-mills in https://github.com/stats4sd/filament-team-management/pull/39
* Refine Invite System Submodule by @dan-tang-ssd in https://github.com/stats4sd/filament-team-management/pull/40
* Bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/stats4sd/filament-team-management/pull/41
* Bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/stats4sd/filament-team-management/pull/42
* Bump stefanzweifel/git-auto-commit-action from 6 to 7 by @dependabot[bot] in https://github.com/stats4sd/filament-team-management/pull/43
* Filament 4 by @dave-mills in https://github.com/stats4sd/filament-team-management/pull/45

**Full Changelog**: https://github.com/stats4sd/filament-team-management/compare/v2.1...v4.0.1

## 4.0 - "What happened to Version 3?" - 2025-11-13

Sometimes, you just need to accept someone else's numbering system. Here, we skipped version 3 so that our versions match the version of Filament that they are built for.

This version re-writes a lot of the core internals to be easier to manage, (hopefully) easier to use and extend in applications, and to work with Filament 4.

**Requires Filament 4!**

## v2.1 - The quick bug release - 2025-06-16

Fixes bugs found in model references and registering new users.

## v2.0 - Harmonised Invites - 2025-06-16

### Breaking Changes

This update will require updates to apps using this package.

1. Update Database
   For apps without a live implementation, where a full db refresh is possible, remove the team_invites, role_invites and program_invites migrations and swap with the new invites database migration.
   For apps with a live implementation, where a full db refresh is not possible, then:

- add the new invites database migration.
- optionally, remove the old database tables. If keeping the old invites is important, then they could be transferred to the invites table first.

2. Overrides
   If the app doesn't override the default Register pages, routes or Invite classes, then nothing else is needed. If the app does, then there are probably updates needed. See the code changes to see what needs updating.

## Updates Password Requirements - 2025-03-25

The password requirements are now 'min:10', to match ODK Central requirements.

## Hotfix for apps with different Team + User Models - 2025-03-03

This fixes a bug caused by the `canAccessTenant` returning the wrong namespaced team.

## 1.04 - Remove Unused App Panel Dashboard - 2024-12-17

To give app  devs more freedom on what sort of navigation / dashboard pages they want.

## 1.03 - 2024-11-21

Adds support for when a user is redirected immediately after registration by the app itself.

## 1.02 - Another, similar bug fix - 2024-11-19

Fixes another pivot table name clash.

## 1.01 - Bug fix - 2024-11-18

- Fixes a bug where the User -> programs relationship was defining the wrong pivot table name

## 1.0.0 - 202X-XX-XX

- initial release
