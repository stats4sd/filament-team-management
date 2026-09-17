# Change log: Upgrade to Filament 5 / Livewire 4 / Laravel 13

Plan: [docs/completed-plans/finish-update-to-filament-5.md](../completed-plans/finish-update-to-filament-5.md)

The `src/Filament/` UI layer had already been rewritten to the Filament 4/5 API by PR #45, but the manifest was left pinned to Filament `^3.0` and the lock/vendor still held v3. This change completes the upgrade: bumps the dependency constraints, resolves the toolchain, removes the `awcodes/shout` dependency, clears out orphaned v3 dead code, and migrates the asset build to Tailwind 4. Scope was **deps + compile-green** — the factories/`.stub`-migration test harness remains a separate effort (see [docs/issues/filament-update.md](../issues/filament-update.md)).

## What changed

### Dependencies (`composer.json`)
Resolved and locked to:
- `filament/filament` ^3.0 → **^5.2** (installed v5.6.7). Floor is `^5.2` because the first-party `Callout` component used below landed in v5.2.0.
- `livewire/livewire` ^3.6.4 → **^4.0** (v4.3.1)
- `laravel/framework` → **v13.16.1** (pulled via `orchestra/testbench` ^10.0 → **^11.0**)
- `althinect/filament-spatie-roles-permissions` ^2.2 → **^3.0** (v3.3.2)
- `larastan/larastan` was pinned to exactly `v3.8.0` (which blocked Laravel 13) → **^3.10**
- **`awcodes/shout` removed entirely**

### Shout → first-party Callout
Dropped the `awcodes/shout` plugin and replaced all 6 `Shout::make()` usages with `Filament\Schemas\Components\Callout` across 5 files. API mapping: `->type('info')` → `->info()`, body text `->content()` → `->description()`. Affected files:
- `src/Filament/App/Pages/ManageTeam/TeamMembersTable.php`
- `src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php`
- `src/Filament/Admin/Resources/Users/Pages/ListUsers.php`
- `src/Filament/Admin/Resources/Programs/RelationManagers/UsersRelationManager.php`
- `src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php` (2 usages)

### Orphaned v3 dead code deleted
Two leftover pre-PR#45 files that broke under v5 (both unreferenced):
- `src/Filament/Program/Resources/ProgramResource.php` — used the v3 `Form` API and referenced non-existent namespaces (the Program panel moved to `ManageProgram` pages).
- `src/Filament/Admin/Resources/UserResource/` — referenced the old `UserResource` class (now `Users\UserResource`); surfaced via PHPStan.

### Tailwind 3 → 4
- Deleted `tailwind.config.js` and `postcss.config.cjs` (Tailwind 4 is CSS-first, no JS preset).
- Rewrote `resources/css/index.css` to the CSS-first config (`@import` Filament's theme + `@source` globs).
- `package.json`: switched to `@tailwindcss/cli`, bumped `tailwindcss` → ^4.0, and dropped the now-obsolete `@awcodes/filament-plugin-purge`, `autoprefixer`, and `postcss-import` deps.

### CI & docs
- `.github/workflows/run-tests.yml`: matrix Laravel 12 / testbench 10 → **Laravel 13 / testbench 11**.
- Synced version strings in `CLAUDE.md` and `SETUP.md`.

## Verification
- `composer update -W` resolves cleanly; `composer validate` valid
- `composer analyse` (PHPStan level 4) — no errors, 74 classes
- `vendor/bin/pest` — ArchTest (forces every `src/` class to load under Filament 5) + suite pass
- Tailwind 4 build produces CSS with no errors; `composer format` (Pint) clean

## Notes / follow-ups
- The Tailwind pipeline is currently **vestigial** — `getAssets()` in the service provider returns `[]`, so the compiled CSS is not registered. Migrated anyway so it isn't left broken.
- The new modal-based user-create path (`Users/Schemas/UserForm.php`) has **no password hashing or `team_id` stripping** — logic that lived in the deleted orphaned `CreateUser`. That orphan was already unwired, so this is a pre-existing PR #45 gap rather than a regression introduced here. It belongs to the test-harness/functional effort.
- The factories / `.stub`-migration **test harness is untouched** by this change (tracked in [docs/issues/filament-update.md](../issues/filament-update.md)). The current suite is green only because functional tests do not exist yet.
