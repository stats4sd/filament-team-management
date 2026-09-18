# Changelog

## 5.0.0 — unreleased

Requires PHP ^8.4, Laravel 13, Filament ^5.2 and Livewire ^4. See [UPGRADE.md](UPGRADE.md) and [SETUP.md](SETUP.md).

### Membership-only boundary

- Removed `spatie/laravel-permission`, Althinect integration, role resources, role-only invitations, `ModelHasRole`, permission seeders/middleware, role config and the package `is_admin` pivot/API. `spatie/laravel-package-tools` remains.
- Shared actor-aware actions enforce host policies against actual configured targets. Team/program/member/invitation management, links, bootstrap, user deletion and bulk operations have one transactional boundary. Missing abilities deny.
- Added synchronous before/after-write host participants for bootstrap grants, revocation, invariants and durable audit; added credential-free after-commit membership/invitation/link events.
- Base User tenant/panel access now denies until the host implements its access contract. Removed implicit program-derived team access and `getAllAccessibleTeams()`.
- Shared member/invitation UI supports independent read, edit and leave permissions. App direct attach is removed; Admin/Program pickers require a host-authorized query. Leaving the last team has an authenticated non-tenant destination.

### Bounded hardening closeout

- Added `CreateTeamForProgram` for atomic team creation and program linking with creator bootstrap, both link policies and existing after-commit observations; both program teams UI surfaces delegate to it.
- Added explicit-actor `MembershipCandidates` queries and complete submitted-ID resolution outside Filament. Selectors retain scoped visibility and batches retain per-user mutation authorization.
- Missing-sender mail uses “Someone”; display-name config comments now describe shared UI/email use without promising new environment settings.

- Target/account deletion validates current membership/link pivots before cleanup, including under caller-owned InnoDB repeatable-read snapshots; unexpected graph growth fails before participants.
- Program-only members can reach an authorized Program panel after registration or final-Team departure.

### Invitations and schema

- New-user acceptance validates persisted identity, target, token and expiry inside one transaction. Existing-user email invitations still add membership immediately, requiring both invite and add abilities, without a synthetic Invite history row.
- Added expiration, token-rotating resend, cancellation, accepted-history filtering, pending-only counts and explicit outcomes. Mail queues after commit by default, with synchronous opt-out, immutable message snapshots and nullable inviter handling.
- Fresh membership/link tables have unique pair constraints. Invites have unique tokens, nullable expiry, a nullable inviter with null-on-delete, configured table/FKs and no role column. Default/program publishing order and nullable program columns remain.
- Installer no longer touches host permission configuration. It offers deny-default host policies, preserves existing files, writes dotenv values correctly and inserts seed calls idempotently with syntax-aware parsing.

### Retained framework and correctness work

- Upgraded Filament/Livewire and removed `awcodes/shout`; corrected config env names and program-team relationship wiring.
- Display labels use `names.team`, `names.program`, `names.user`; configurable panel IDs resolve URLs through host panels. Program links check access and use escaped plain text when unavailable. Deletion redirects refresh the tenant before selecting a destination.
- Remembered tenant selection uses the supplied panel and current accessible set. Tenant middleware validates configured model types and avoids unchanged saves.
- User email edits ignore the current record for uniqueness. Program deletion copy now states that associated teams remain.
- Renamed Program Projects pages/tables to Program Teams; removed unused facade/plugin/model/route remnants from the earlier framework update. Full removal list is in UPGRADE.md.

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
