# Repository architecture and contributor guidance

This repository is an installable Laravel/Filament membership package. It provides teams, optional programs, direct memberships, invitations, transactional actions and Filament management surfaces. Hosts own authorization policies, administrator/owner definitions, panel admission and tenant queries. No permission backend or role schema is required.

Requires PHP ^8.4, Laravel 13, Filament ^5.2, Livewire ^4 and `spatie/laravel-package-tools`. Run `composer test`, `composer analyse` and `vendor/bin/pint --test` after changes. Tests use an explicit host User/policy integration; MySQL concurrency tests are opt-in and create isolated InnoDB databases. See README for the `FTM_TEST_MYSQL_*` settings and required PHP extensions.

## Boundaries

- Resolve configured model classes, tables and foreign keys from `config/filament-team-management.php`. Display words come from `names.*`, and links use host panel objects selected by `panels.*`.
- Models provide structural relations; `users()` and `members()` are unfiltered direct-membership aliases. Base User access methods deny until the host overrides them. Programs never implicitly authorize linked teams.
- Public `Actions` accept an explicit actor and actual target. Direct Laravel Gate denies missing policies; Filament adapters use identical arguments and delegate writes. Do not add unchecked action flags, raw relationship mutation handlers or permissive missing-policy fallbacks.
- `Support\MembershipMutation` coordinates bulk/creation/removal/lifecycle writes with user-first locks; invitation actions validate token capabilities separately. Host participants run before change and after write inside the transaction. Observation events/mail run after successful outer commit.
- `Filament\Support` holds shared member/invitation UI, scoped selectors and navigation. Profile viewing, editing, member viewing and leaving are separate abilities. Retain server-side checks on direct widget/relation-manager requests.
- The installer publishes fresh schemas in ordered default/program tags and optionally host policy stubs. Do not introduce legacy upgrade adapters, role seeders or host permission configuration changes.

Read [docs/membership-contract.md](docs/membership-contract.md) for the public operation, participant, event and account-lifecycle contracts. Update README, SETUP, CHANGELOG and UPGRADE when changing public APIs. Keep Markdown paragraphs unwrapped. Historical plans remain under docs/plans; the implementation decisions and verification log distinguish completed work from deferred release/monorepo scope.
