# Changelog

All notable changes to `filament-team-management` will be documented in this file.

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
