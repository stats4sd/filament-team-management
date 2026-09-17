# Plan: Upgrade dependencies to Filament 5 / Livewire 4 / Laravel 13

## Context

`stats4sd/filament-team-management` is a consumable Laravel/Filament package. PR #45 ("Update to Filament 4", merged `448258f`, Nov 2025) already rewrote the entire `src/Filament/` UI layer to the Filament **4** API (`Filament\Schemas\Schema`, `Filament\Auth\Pages\*`, `Resources/{Name}/{Schemas,Tables,Pages}` layout, union nav-icon types). However, on this `testing-with-filament-3` branch the **manifest was reverted back to Filament `^3.0`** (commit `5965a97`), and `composer.lock`/`vendor/` still hold Filament v3.3.54, Livewire 3.8.1, Laravel 12.62. The result, documented in `docs/issues/filament-update.md`: source targets v4 but vendor is v3, so every `src/Filament/` class fatals on load and the suite is red at baseline.

Filament **v5** (released 2026-01-16) is **API-identical to v4** — the only difference is Livewire v4 support (Laravel 11.28+, Tailwind 4). So the completed PR #45 source migration *is* the Filament-5 migration. This work is therefore a **manifest/build/tooling upgrade**, not a source rewrite: point composer at Filament `^5.0` + Livewire `^4.0` + Laravel 13, fix the one orphaned v3 file, migrate the Tailwind build to v4, and update docs/CI.

**Scope (confirmed):** Filament `^5.0` only (not dual `^4||^5`). Deps + compile-green — get `composer update` to resolve and all `src/` classes to load (ArchTest green). The factories/`.stub`-migration test-harness work stays in the separate `docs/test-plan.md` / `docs/issues/filament-update.md` effort.

## Changes

### 1. `composer.json` — version constraints
- `filament/filament`: `^3.0` → `^5.2` (the native `Callout` component landed in v5.2.0 — see §2; `^5.2` guarantees it)
- `livewire/livewire`: `^3.6.4` → `^4.0`
- **Remove** `awcodes/shout` entirely (replaced by first-party `Callout` — see §2)
- `althinect/filament-spatie-roles-permissions`: `^2.2` → version that allows Filament 5 (likely `^3.0`; resolve via composer)
- Add an explicit Laravel constraint if appropriate: `laravel/framework` is currently pulled transitively; add/confirm `illuminate/*` or `laravel/framework: ^13.0` compatibility. Filament 5 requires Laravel 11.28+, so 13 is in range.
- `require-dev`: `orchestra/testbench` `^10.0` → `^11.0` (the Laravel 13 line — verify exact major on Packagist during install); confirm `pestphp/pest ^4`, `larastan/larastan v3.8`, `phpstan/*` all support Laravel 13 / PHP 8.4 (bump if composer complains).
- Keep `php: ^8.4` (Filament 5 floor is 8.2; 8.4 is fine).

Then run `composer update -W` and resolve any satellite-version conflicts interactively (pin shout / roles-permissions to whatever minor actually declares `filament/filament: ^5.0`).

### 2. Drop `awcodes/shout` → first-party `Filament\Schemas\Components\Callout`
6 `Shout::make('info')` usages across 5 files. Replace each, removing `use Awcodes\Shout\Components\Shout;` and adding `use Filament\Schemas\Components\Callout;`.

**API mapping** (Callout's `make()` arg is the *heading*; Shout had no heading, only body):
- `Shout::make('info')->type('info')->content($body)->columnSpanFull()`
  → `Callout::make('Invitation')->info()->description($body)->columnSpanFull()` (use a short, meaningful heading per call site, e.g. "Invite users"; `->type('info')` → `->info()`; body text moves from `->content()` to `->description()`; `columnSpanFull()` is unchanged).
- Dynamic/HtmlString content is preserved: `->content(fn (User $record) => new HtmlString(...))` → `->description(fn (User $record) => new HtmlString(...))`.

Files / lines:
- `src/Filament/App/Pages/ManageTeam/TeamMembersTable.php:35`
- `src/Filament/Program/Pages/ManageProgram/ProgramMembersTable.php:35`
- `src/Filament/Admin/Resources/Users/Pages/ListUsers.php:21`
- `src/Filament/Admin/Resources/Programs/RelationManagers/UsersRelationManager.php:68`
- `src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php:50` and `:78`

Also: confirm no Shout reference survives anywhere (`grep -rn -i shout .`), including `package.json`/config/blade (current grep shows none outside the 5 src files + the composer line).

### 3. Orphaned v3-API file — `src/Filament/Program/Resources/ProgramResource.php`
This is the **only** remaining v3 API in `src/` (`use Filament\Forms\Form; public static function form(Form $form): Form`). PR #45 deleted the Program-panel `ProgramResource` in favour of the `ManageProgram` pages (`src/Filament/Program/Pages/ManageProgram/*`); this copy survived on this branch as dead code.
- **First check** whether anything still registers/references it (grep `Program\\Resources\\ProgramResource`, panel `discoverResources`/`resources([...])`, the host-panel docs). If unreferenced → **delete it** (matches the PR #45 intent).
- If it *is* still wired into a panel → migrate it to the `Schema` API like its sibling `src/Filament/Admin/Resources/Programs/ProgramResource.php` (`use Filament\Schemas\Schema; form(Schema $schema): Schema`), or better, move its form into a `Programs/Schemas/ProgramForm.php` to match the established layout.

### 4. Tailwind 4 build migration
Filament 5 uses Tailwind 4 (CSS-first, no JS preset).
- `package.json`: `tailwindcss` `^3.3.3` → `^4.0`; review `@tailwindcss/forms`/`@tailwindcss/typography`/`autoprefixer`/`postcss` for Tailwind-4 compatibility (Tailwind 4 folds autoprefixer/postcss-import in; likely drop several). Update the `purge` script's `-v 3.x` → `-v 5.x` (or remove the `@awcodes/filament-plugin-purge` step if no longer needed under v5).
- `tailwind.config.js`: the `vendor/filament/filament/tailwind.config.preset` JS preset no longer exists in v5 — remove the file and move to the CSS-first config.
- `resources/css/index.css`: replace the bare `@import '.../theme.css'` with the Filament-5 theme import convention (`@import 'tailwindcss'; @import '../../vendor/filament/filament/resources/css/theme.css'; @source` globs for `src/Filament/**` and `resources/views/**`). Verify the exact recipe against the Filament 5 "custom theme" docs at build time.
- Build scripts (`dev:styles`/`build:styles`): adjust to the Tailwind 4 CLI invocation (drop `--postcss` if unused).

### 5. CI — `.github/workflows/run-tests.yml`
- Matrix `laravel: [12.*]` → `13.*`; `testbench: 10.*` → matching major (e.g. `11.*`); keep `carbon: 3.*` (confirm).
- `php: [8.4]` is fine. `phpstan.yml` `php-version: '8.4'` unchanged.
- No Livewire pin needed (transitive via Filament 5), but optionally add it to the `composer require` line for clarity.

### 6. Docs sync
- `CLAUDE.md` line 9: `Filament ^3.0` → `^5.2`, `Livewire ^3.6.4` → `^4.0`, **drop the `awcodes/shout` mention**, and update roles-permissions/testbench numbers to the resolved values. Also the "Filament 4 layout" wording → "Filament 5 layout".
- `SETUP.md` line 4: "fresh Laravel 11 Installation" → Laravel 13; update any Tailwind/theme setup instructions to the v4 recipe.
- `README.md`: update any host-app install snippets that name Filament/Tailwind versions or the old `tailwind.config.js` preset.

## Out of scope (separate effort)
Test-harness boot — uncommenting/loading the `.stub` migrations in `tests/TestCase.php:55-58`, adding `Spatie\Permission\PermissionServiceProvider` to `getPackageProviders()`, and writing the 5 empty factories (`database/factories/`). Tracked by `docs/test-plan.md` + `docs/issues/filament-update.md`. Resolving the Filament version (this plan) is its prerequisite.

## Verification
1. `composer update -W` resolves cleanly with `filament/filament v5.*`, `livewire/livewire v4.*`, `laravel/framework v13.*` (confirm with `composer show -D | grep -E 'filament|livewire|framework|testbench'`).
2. `composer analyse` (PHPStan level 4) passes — this loads `src/` against the real v5 vendor and will catch any residual v3 API drift beyond the one known file.
3. `vendor/bin/pest tests/ArchTest.php` — the arch test forces every `src/` class to load; green here proves the whole UI layer compiles under Filament 5 (the headline blocker from `docs/issues/filament-update.md`). Full suite may stay red pending the out-of-scope harness work.
4. `npm install && npm run build:styles` produces `resources/dist/filament-team-management.css` without Tailwind errors.
5. `composer format` (Pint) clean before commit.
