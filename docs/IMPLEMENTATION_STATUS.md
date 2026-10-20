# Implementation Status

Last updated: 2026-10-20

## Completed

**Phase 0 — Discovery**
- Inspected local working directory (`D:\pasargadbrush\laravel`) — was empty, no prior app.
- Audited live site (pasargadbrush.com) via public pages, `robots.txt`, and sitemap XML (no admin access used, no production contact).
- Discovered a complete local WordPress copy at `C:\laragon\www\pasargadbrush` with matching local database `pasargardbrush` — now the migration source of truth (see `docs/WORDPRESS_MIGRATION.md`).
- Confirmed business model with owner: hybrid quote-based + priced/variant catalog (see `docs/PROJECT_SCOPE.md`, `docs/DECISIONS.md`).
- Confirmed: full WordPress access available; Torob merchant not yet registered.

**Phase 1 — Application foundation (in progress)**
- Scaffolded Laravel 13.38 on PHP 8.4.25 (`composer create-project laravel/laravel .`).
- Git repository initialized (`main` branch), initial commits made.
- Configured `.env` / `.env.example`: MySQL connection (`pasargadbrush_laravel` DB, separate from the WP mirror DB), `APP_LOCALE=fa`, app name "Pasargad Brush".
- Created local MySQL database `pasargadbrush_laravel` (utf8mb4_unicode_ci) via Laragon MySQL 8.0.30.
- Verified full toolchain: `npm run build` succeeds (Tailwind v4 + laravel-vite-plugin + Vite 8); `php artisan migrate` succeeds against MySQL.
- Installed Alpine.js and GSAP as frontend dependencies.
- Installed `laravel/boost` (dev) per the Laravel skeleton's own agent-setup instructions — installation in progress/being verified.
- Fixed environment issues: removed `@laravel/multiplex` optional dependency (triggered a known npm/Vite 8 arborist crash), upgraded global npm to 10.9.2, used `--legacy-peer-deps` for base install.

**Phase 2 — Database and core architecture (in progress)**
- Designed full schema in `docs/DATABASE_DESIGN.md` (catalog, hybrid quote/priced products, variants, attributes, shared media library, customers/addresses, cart/orders/payments, quote requests, blog/pages, SEO redirects, migration mappings, settings, audit logs). Currency convention fixed: integer Rial everywhere, Toman is display-only.
- Created and ran 28 migrations against MySQL (`pasargadbrush_laravel`) — all applied cleanly.
- Created Eloquent models for every table with real relationships (no bare stubs).
- Added `role` (`admin`/`editor`) to `users`; admin session auth implemented by hand (not a starter kit): `routes/admin.php`, `Admin\Auth\LoginController`, `EnsureUserIsAdminStaff` middleware, minimal placeholder login/dashboard Blade views (RTL, real design comes in Phase 3/6).
- `AdminUserSeeder` creates a local-only default admin (`admin@pasargadbrush.local` / `password` — **must be changed before any shared/staging deploy**).
- 6 feature tests for admin auth (login page, successful login, failed login, guest blocked from dashboard, staff can access dashboard, logout) — all passing. Full suite: 8/8 passing.
- Enabled `pdo_sqlite`/`sqlite3` PHP extensions (were disabled) so the test suite's in-memory SQLite database works, isolated from the real MySQL dev database.

## Commands executed (this session)

```
composer create-project laravel/laravel . --no-interaction
git init -b main && git add -A && git commit -m "Initial Laravel 13 scaffold"
npm install (failed — npm/arborist bug) → removed @laravel/multiplex → npm install -g npm@10.9.2 → npm install --legacy-peer-deps (succeeded)
npm install alpinejs gsap --legacy-peer-deps
npm run build (succeeded)
mysql -u root -e "CREATE DATABASE pasargadbrush_laravel ..."
php artisan migrate --force (succeeded against MySQL)
composer require laravel/boost --dev && php artisan boost:install
```

## Tests executed

`php artisan test` — 8/8 passing (2 default + 6 new admin-auth feature tests).

## Known issues

- Local npm environment needed workarounds (documented in `docs/DECISIONS.md`) to get past an upstream npm 10.8.x + Vite 8 dependency-resolution bug. Resolved for this machine; worth re-verifying on any other dev machine/CI runner.
- Discrepancy between live sitemap (52 products) and local DB (120 published products) not yet reconciled.
- `concurrently@10.0.6` warns it wants Node ≥22 (we're on Node 20.20) — non-fatal (EBADENGINE warning only), works for `npm run dev`/`build`, but worth revisiting if it ever causes real failures.

## Remaining work (not started)

Everything from Phase 2 onward: database schema, auth/authorization, design system + homepage, catalog, cart/checkout, admin dashboard, blog/about/contact, Torob feed, WordPress data import commands, SEO implementation, testing, deployment docs.

## Resolved owner input (2026-10-20)

1. Persian-only for now (English maybe later — not built now).
2. `parsbrush` / `parsbrush-new` confirmed unrelated — never touched.
3. Payment gateway: Zarinpal now, extensible for more later.
4. Torob: owner registers the merchant account themselves.

## Next concrete step

Finish Phase 2 (factories/seeders for catalog data, `docs/ARCHITECTURE.md`), then move to Phase 3 (public design system + homepage) — which will need real logo/product images pulled from the local WordPress media library (`C:\laragon\www\pasargadbrush\wp-content\uploads`). Will check in before using specific brand assets.
