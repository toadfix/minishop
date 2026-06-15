# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`toadfix/minishop` is a **distributable Laravel package** (a library, `type: library`), not an application. It provides a drop-in ecommerce stack: a Filament v5 admin panel, a Livewire/Blade storefront, a Sanctum REST API, Fortify-based customer auth, Stripe/COD payments, and Canadian tax/shipping. It is consumed by a host Laravel 13 app.

Because it's a package, there is no `artisan` here and the suite runs through **Orchestra Testbench** (an in-memory host app). `vendor/` and `composer.lock` are gitignored — run `composer install` after a fresh clone.

## Commands

```bash
composer install            # required after clone (no committed lock/vendor)
composer test               # full suite (= phpunit tests)
composer lint               # Pint in --test mode (CI gate)
composer format             # Pint autofix — run before committing

vendor/bin/phpunit tests/Feature/Admin/PolicyAuthorizationTest.php   # one file
vendor/bin/phpunit --filter test_manager_can_edit_but_not_delete     # one test
```

CI (`.github/workflows/tests.yml`) runs `composer lint` then `composer test` on PHP 8.3/8.4. **`composer lint` must pass** — run `composer format` before committing or CI fails.

### Working against a real app (the sandbox)

`scripts/fresh-host.sh` scaffolds a sibling host app (`../minishop-app` by default), wires this package in via a Composer **path repository** (symlinked, so edits are live), and runs the installer. Use the sandbox to click through the admin/storefront in a browser; use the package dir to run tests. After editing service providers, run `php artisan optimize:clear` in the sandbox.

## Architecture

### Two front ends, one package
- **Admin** = Filament v5 panel registered by `MinishopPanelProvider` at `/dashboard`. Resources live in `src/Filament/Resources/*`; the panel uses Filament's own login (`->login()`), independent of Fortify.
- **Storefront** = controllers + Livewire components rendered through a **renderer abstraction**: `StorefrontRendererContract` (default `BladeRenderer`, selected by `config('minishop.renderer')`). Controllers call `$renderer->render('storefront/Home', $data)`; `BladeRenderer` maps that to `minishop::storefront.home` and prefers a host override at `resources/views/storefront/*` if present. Interactive pieces are Livewire components in `src/Livewire/*`, registered in `MinishopServiceProvider::registerLivewireComponents()`.

### Service provider is the wiring hub
`src/MinishopServiceProvider.php` registers everything: the renderer binding, payment manager, Fortify view/action bindings, Livewire components, observers, policies, the shipping service, and the **super-admin gate** (`Gate::before` returns true for the `super-admin` role — it bypasses all policy checks). `MinishopPanelProvider.php` is the Filament panel. Both are listed under `extra.laravel.providers` in `composer.json`.

### Domain patterns (follow these for consistency)
- **Money is stored as integer cents** everywhere (`price`, `*_amount`, `unit_price`). Divide by 100 only at display.
- **Actions** (`src/Actions/*`) hold discrete business operations (`CreateOrderAction`, `BuildLineItemsAction`, `Inventory/DecrementStockAction`, `GenerateInvoicePdf`). Stock decrements use `lockForUpdate()` inside a transaction.
- **Observers** (`src/Observers/*`) handle side effects (activity logging, image-file cleanup, low-stock checks). Product deletion cascades at the DB level (bypassing model events), so `ProductObserver::deleting` cleans up files manually while `ProductImageObserver` covers individual image deletes.
- **Authorization** = Spatie permissions behind thin policies (`src/Policies/*`), each method delegating to `$user->can('resource.action')`. Roles (`super-admin`, `admin`, `manager`, `customer`) and their permissions are defined in `RoleAndPermissionSeeder`.
- **Payments** = a Laravel `Manager` (`src/Payments/PaymentManager.php`) with `stripe`/`cod`/`null` drivers; extend via `Payment::extend()`. Gateways implement `PaymentGatewayContract`. The active gateway is a `StoreSettings` field; Stripe **secrets live in `.env`**, never in the DB.
- **Webhooks** are idempotent: the Stripe handler records each event in `processed_webhook_events` (unique `gateway,event_id`) inside the fulfilment transaction.

### Auth
Customer auth is **Fortify**; the package registers the login/register/forgot/reset/verify views (`Fortify::loginView()` etc.) and binds `CreatesNewUsers`/`ResetsUserPasswords` actions in the provider. `User` implements `MustVerifyEmail`, so the `/account` routes' `verified` middleware is real; registration sends a verification email.

## Critical gotchas

- **Migrations**: the provider only `loadMigrationsFrom()` under `runningUnitTests()`. Host apps **publish then run** migrations (via `minishop:install`), so don't add unconditional `loadMigrationsFrom` — it double-runs migrations in a host.
- **Host must use `Minishop\Models\User`** as its auth model (carries the roles/Sanctum/Fortify traits). `minishop:install` sets `AUTH_MODEL` in `.env`; the Filament panel breaks on the stock `App\Models\User`.
- **Filament render tests do not work** in this Testbench setup — nested Livewire components hit a `ViewErrorBag` initialization failure. **Test admin behavior at the logic level** (policies, resource config, model/action behavior), not by rendering pages. See `tests/Feature/Admin/*` for the established pattern.
- **Published storefront views shadow the package's.** Once a host runs `vendor:publish --tag=minishop-storefront`, their copies win; package updates to those views require re-publishing with `--force`.
- **Filament assets** (`php artisan filament:assets`) must be published or the admin renders with no JS/CSS. `minishop:install` runs it and registers a `post-autoload-dump` composer hook.
- **Storefront styling** is Tailwind v4 via Vite; until `npm run build` runs, the layout falls back to the Tailwind Play CDN.
