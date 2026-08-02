# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

ClickERP / Platinum ERP — a Laravel 8 (PHP 8.1/8.2) ERP for a Saudi domestic-worker recruitment
business: recruitment contracts, rental contracts, shelter/housing, sponsorship transfers,
deportation & travel, complaints, finance, HR, and a public customer-facing site.

UI and business documentation are Arabic-first (RTL). `docs/` and `tests/README.md` are written in
Arabic — read them before touching permissions, finance, cron jobs, or tests.

## Commands

```bash
# Dependencies / autoload (the Helpers files are autoloaded via composer "files")
composer install
composer dump-autoload -o

# Assets (Laravel Mix / webpack)
npm run dev            # or: npm run watch, npm run prod

# Local server
php artisan serve      # app assumed at http://127.0.0.1:8000

# After changing routes, config, views, or permission definitions
php artisan optimize:clear
```

### Tests

```bash
php artisan test:db-provision   # once, and again after any new migration
vendor/bin/phpunit
vendor/bin/phpunit tests/Feature/Branch/                       # a directory
vendor/bin/phpunit --filter=BranchModeTest                     # a single test class
BASELINE_UPDATE=1 vendor/bin/phpunit                           # shrink tests/baselines/*.json
```

Read [tests/README.md](tests/README.md) first. Key constraints:

- Tests run against a **real MySQL database** (`rafdain_testing`, set in [phpunit.xml](phpunit.xml)),
  separate from the dev DB. Not sqlite.
- `php artisan migrate` **from scratch is broken** — `app/` and `packages/hr/` both define a
  `CreateComplaintsTable` class. That is why `test:db-provision` clones the dev schema
  (`CREATE TABLE ... LIKE`) instead of migrating fresh.
- Tests use `DatabaseTransactions`, not `RefreshDatabase`. Never rely on pre-existing rows —
  build what the test needs with `Branch::factory()` / `Admin::factory()`.
- `RouteIntegrityTest` and `ModelSchemaIntegrityTest` are project-wide guards that fail only on
  **new** problems, measured against `tests/baselines/*.json`.

### Permissions after any deploy or permission change

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

Verify via `/dash/perms/{roleId}`; the command's output must end with `Missing selected: 0`.
See [docs/PERMISSIONS.md](docs/PERMISSIONS.md) — do **not** run per-section permission commands.

## Architecture

### Multiple auth guards, one app

| Guard | Provider model | Area |
|---|---|---|
| `admin` | `App\Models\auth\Admin` | back office (`/dashboard/...`) |
| `agent` | `App\Models\auth\Admin` | external recruitment-office agents |
| `web` | `App\Models\ingaz\User` | public site customers |
| `api` | `App\Models\ingaz\User` (JWT) | mobile app |

Middleware aliases live in [app/Http/Kernel.php](app/Http/Kernel.php): `admin`, `agent`, `site_auth`,
`auth_web`.

### Routing

Eight route files, all registered in [app/Providers/RouteServiceProvider.php](app/Providers/RouteServiceProvider.php):
`web.php` + `admin.php` (back office), `site.php` (public site), `agent.php`, `office.php`,
`ingaz.php`, `api.php`, `commands.php`.

`web.php`, `admin.php`, `api.php` and `commands.php` are grouped under the
`App\Http\Controllers` namespace and use **string** controller references
(`'admin\\DashboardController@index'`); the other files import controller classes. Match the
surrounding style in whichever file you edit. Back-office routes are wrapped in a locale prefix
group (`LaravelLocalization::setLocale()`) plus `['admin', 'admin.route.permission']`.

### Permission system — the central piece

Permission names are constants in `App\PermKeys` and `App\NewPermKeys` (files live in
[app/Helpers/](app/Helpers/) but the namespace is `App\`, autoloaded through composer's `files`).
Never invent literal permission strings; add a constant.

[app/Support/AdminRoutePermissionMatrix.php](app/Support/AdminRoutePermissionMatrix.php) maps
**route-name patterns → required permissions** (an admin needs *any* one of them). The
`admin.route.permission` middleware
([EnsureAdminRoutePermission](app/Http/Middleware/EnsureAdminRoutePermission.php)) resolves the
matrix per request. A route with **no matrix entry is completely ungated** — the matrix, not the
route file, is what protects a screen. `tests/Feature/Admin/UngatedAdminRoutesTest.php` and
`AdminRoutePermissionMatrixTest.php` enforce this, so adding a back-office route means adding a
matrix entry in the same change.

The matrix also drives the redirect target when an admin lacks permission
(`firstAccessibleUrlForAdmin` walks `primaryRouteNames()`), so a new landing screen belongs in that
list too.

Package routes use `package.route.permission`; developer-only screens use `dev.superadmin`.

### Super Admin module toggles

[app/Support/SystemModuleManager.php](app/Support/SystemModuleManager.php) reads
[config/superadmin_modules.php](config/superadmin_modules.php) merged over the
`system_module_settings` table. A disabled module makes every matching route redirect to
`admin.no-permissions` (or 403 for JSON) *before* the permission check.

Values are **cached for 5 minutes** — any code or test that flips a module must clear the cache
(tests do this through `tests/Concerns/InteractsWithBranches.php`).

### Branches (multi-branch mode)

The whole feature hangs on `SystemModuleManager::isEnabled('branches')`, **default off**.

- [app/Models/Concerns/AssignsBranch.php](app/Models/Concerns/AssignsBranch.php) — a `saving` hook
  that stamps `branch_id` from the acting admin, with per-entity fallbacks
  (`resolveBranchIdFromOrder`, `...FromContract`, …). Every guard is `Schema::hasTable`/`hasColumn`
  wrapped so the trait is safe on partially-migrated databases.
- [app/Services/BranchAccessService.php](app/Services/BranchAccessService.php) — read filtering.
  An admin with no `branch_id` is "global management" and sees everything; rows with
  `branch_id = null` stay visible to everyone (intentional).

Both on and off modes are pinned by `tests/Feature/Branch/BranchModeTest.php` — change either side
and that file must be updated.

### Nami packages

`packages/{finance,hr,crm,access-control}` are first-party packages (`Nami\Finance\`, `Nami\Hr\`,
`Nami\Crm\`, `Nami\AccessControl\`), PSR-4 mapped from the **root** [composer.json](composer.json).
Each `*ServiceProvider` loads its own routes, migrations, views (`finance::`, `hr::`, …) and
translations — so finance migrations run from `packages/finance/database/migrations`, not
`database/migrations`.

In the production/`main` workflow these are **git submodules** (see [README.md](README.md)); on this
branch they are tracked as ordinary files. The `.githooks/pre-push` hook runs
[scripts/check-package-repos.sh](scripts/check-package-repos.sh) and blocks a push if a nested
package repo has uncommitted changes.

### Settings pattern

Runtime configuration lives in the database, wrapped by static classes in
[app/Support/](app/Support/) — `AutomationSettings`, `CommunicationSettings`, `R2StorageSettings`,
`RentalContractPricing`, `NotificationAlertSettings`, etc. They read a settings table and cache the
result. Reach for the existing `*Settings` class instead of querying the settings table directly.

[app/Console/Kernel.php](app/Console/Kernel.php) builds its cron expressions from
`AutomationSettings`, so scheduled-task timing is admin-configurable rather than hard-coded. See
[docs/CRON_JOBS.md](docs/CRON_JOBS.md).

### Controllers → Actions

Controllers delegate persistence to action classes under [app/Http/Actions/](app/Http/Actions/),
which extend [MainAction](app/Http/Actions/MainAction.php) (generic `store`/`update`/`getWhere`/… over
a `$model` property, with `clearRequesrt()` stripping empty values). Scaffold with
`php artisan make:action {model} {action}`.

### Localization

`mcamara/laravel-localization` with `ar` (default) and `en`; locale is a URL prefix on web/admin
groups. Content models use `astrotomic/laravel-translatable` with sibling `*Translation` models.
The `TranslateHardcodedOutput` middleware post-processes untranslated literals in responses.

## Conventions

- Sub-namespaces are **lowercase**: `App\Models\ingaz\...`, `App\Models\auth\Admin`,
  `App\Http\Controllers\admin\...`. Case matters on the production Linux host — keep it exact.
- Arabic is the primary UI language; user-facing strings and comments are frequently Arabic.
- Migration/permission/finance/CV-extraction deploy procedures are documented per-topic in `docs/`;
  update the relevant doc when you change one of those flows.

## Do not run against production data

`migrate:fresh`, `migrate:refresh`, and the `db:seed` variants (`LaratrustSeeder`,
`Nami\Finance\Database\Seeders\*`) reset or overwrite core data. Take a `mysqldump` first — see the
end of [README.md](README.md).
