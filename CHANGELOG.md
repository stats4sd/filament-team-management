# Changelog

All notable changes to `filament-team-management` will be documented in this file.

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
