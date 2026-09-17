# Code review — `testing-with-filament-5`

**Date:** 2026-06-23 · **Branch:** `testing-with-filament-5` · **HEAD:** `f4d754c`

**Scope:** the `f4d754c` "update to Filament 5" commit (production) plus the uncommitted test-harness work (factories, `TestCase`, fixtures, new tests).

## Summary

Ran the suite — **70 pass, fully green**. Verified the Filament 5 `Callout` API, the file deletions, and migration ordering against the vendored source.

**The production change is clean.** The `Shout` → `Filament\Schemas\Components\Callout` migration uses a valid API (`make($heading)`, `->info()`, `->description()` accept Closures — confirmed in `vendor/filament/schemas`), the two deleted classes (`Program/Resources/ProgramResource`, `UserResource/Pages/CreateUser`) have zero remaining references, and the `@source` CSS paths resolve. No in-scope production correctness bug found. The findings below are in the test harness plus a couple of real out-of-scope bugs the new code brushes up against.

## Findings (ranked)

### 1. `ProgramInviteFactory` cannot run — no `program_invites` table is ever migrated

`database/factories/ProgramInviteFactory.php:16`

`ProgramInvite` has `protected $table = 'program_invites'`, but `database/migrations/` contains no stub that creates it (only `invites`, `programs`, `program_members`, `program_team`). `ProgramInvite::factory()->create()` throws `no such table: program_invites`. Latent today (no test calls it), but it's a shipped factory that is dead on arrival. Note: `ProgramInvitesTable` (`src/Filament/Program/Pages/ManageProgram/ProgramInvitesTable.php`) uses this model in the Program panel UI — so the missing migration is a real pre-existing gap the new factory exposes.

### 2. `withPrograms()` runtime helper has no idempotency/column guard

`tests/TestCase.php:198`

It unconditionally re-runs the program `CREATE TABLE` migrations. Calling the global `withPrograms()` helper from a `ProgramMode` test (already migrated at boot via `$usePrograms=true`) throws `table "programs" already exists`. And on a normal `TestCase` it creates the program tables but *not* `invites.program_id` / `users.latest_program_id` (those ran at boot without programs) — so any code touching those columns fails with `no such column` deep in unrelated code. The docblock warns about the latter but the helper itself doesn't guard with `Schema::hasTable()`. It's a global helper a future ProgramMode-test author will naturally reach for.

### 3. `ResourceResolutionTest` model assertion is tautological

`tests/Smoke/ResourceResolutionTest.php:8-10`

`expect(UserResource::getModel())->toBe(config('...models.user'))` compares the resource's `getModel()` (which *returns* that same config value) against the config. If a resource hardcoded the wrong model class — exactly the config-indirection violation this package forbids — the test stays green. To bite, it should repoint config to a custom subclass and assert `getModel()` follows.

### 4. Factory `$model` hardcodes the package model, defeating custom-model resolution

`database/factories/UserFactory.php:15`

`guessFactoryNamesUsing` maps by `class_basename`, so a host's `App\Models\User` resolves to `UserFactory` — whose `protected $model = Stats4sd\…\User::class` then instantiates the *package* model, not the host subclass. Seeded/test data silently uses the wrong class for custom-model apps. (Same in `TeamFactory`/`ProgramFactory`.) Low — host apps normally ship their own factories — but it undercuts the package's config-indirection story.

### 5. `MailableTest` signed-URL check is half-vacuous

`tests/Unit/MailableTest.php:21`

`->toContain('signature=')` holds for *any* `URL::signedRoute` output, so it verifies nothing about correctness; if the mailable signed the wrong route/panel, the test still passes. The `token=tok-xyz` half is meaningful, so this is a weak assertion rather than a dead one.

### 6. `InviteTest` global-scope test doesn't assert *which* row is hidden

`tests/Unit/InviteTest.php`

Asserting `count() === 1` (one unconfirmed + one confirmed) passes even if the `onlyUnconfirmed` predicate were inverted (hiding the unconfirmed row instead). Assert the surviving row is the unconfirmed one.

### 7. Detach test omits `assertHasNoActionErrors()`

`tests/Feature/ResourceCrudTest.php:90-102`

Unlike its sibling attach test, the detach action isn't checked for action errors, so a halted/erroring detach is swallowed into the error bag and the test stays green.

### 8. Cosmetic: `<br/>` dropped in the team-member edit Callout

`src/Filament/Admin/Resources/Teams/RelationManagers/UsersRelationManager.php:50-51`

The old `Shout` rendered `"Edit user's role…<br/>$name ($email)"` as one block; the split into heading + description drops the explicit line break. Layout-only.

## Out-of-scope but real (predates this branch)

### Config copy-paste bug

`config/filament-team-management.php:33`

```php
'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', 'program_id'),
```

Reads the **model** env var, not a `*_FOREIGN_KEY` var. If a host sets `FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL`, `programs_foreign_key` resolves to the class string and the invites migration creates a column named after the class. Not in the reviewed diff, but the new program factory/migration code sits directly on top of it.

## Verified and cleared

- `CustomUser`/`ProjectTeam` fixtures *are* used (`ConfigIndirectionTest`, `HasModelNameLowerStringTest`).
- The `Gate::before` returns `null` (not `false`) correctly, so other gates still evaluate.
- The invites-before-programs migration order is fine — SQLite permits forward-referencing FKs at table creation, enforced only at insert; confirmed by the green ProgramMode suite.
- No remaining references to `Awcodes\Shout` or the deleted resource/page classes anywhere in `src/`.
