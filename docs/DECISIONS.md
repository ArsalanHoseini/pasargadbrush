# Architectural & Business Decisions Log

Newest first. Each entry: decision, reasoning, alternatives considered, date.

---

## 2026-10-20 — Use the local WordPress copy as migration source, not live production

**Decision:** Treat `C:\laragon\www\pasargadbrush` (local files) + local MySQL database `pasargardbrush` as the authoritative migration source, instead of connecting to the live cPanel account.

**Why:** A full local copy already exists (made via Duplicator, includes a cPanel account backup), so every discovery/migration task can be done read-only and locally with zero risk to the live site. This also makes the migration repeatable/testable without re-touching production.

**Alternative considered:** Logging into the live cPanel with the credentials found in `D:\pasargadbrush\s.txt`. Rejected as unnecessary risk when an equivalent local copy already exists.

---

## 2026-10-20 — Hybrid product model: quote-based + priced/variant products

**Decision:** The `products` table/domain must support two product behaviors selected per product: `requires_quote` (no price shown, inquiry/quote-request flow) and `priced` (fixed price per variant, e.g. by dimension, supports cart/checkout).

**Why:** Owner confirmed some products are custom/made-to-order (quote-based) while others have defined sizes with fixed prices. This is a real business rule, not a simplification — a single "always inquiry" or "always priced" model would misrepresent the catalog.

**How to apply:** Product variants (size/dimension) carry price; a product with no variants and `requires_quote = true` renders a quote CTA instead of add-to-cart. See `docs/DATABASE_DESIGN.md` (to be written in Phase 2) for schema.

---

## 2026-10-20 — Database engine and naming

**Decision:** MySQL 8.0 (via Laragon locally), InnoDB, `utf8mb4_unicode_ci`. Local dev database name: `pasargadbrush_laravel` (distinct from the WordPress mirror database `pasargardbrush`, to avoid any accidental cross-contamination).

**Why:** Matches the required stack (MySQL + utf8mb4) and keeps the new app's database completely separate from the WordPress migration source.

---

## 2026-10-20 — Laravel 13 (latest stable), PHP 8.4, locale `fa`

**Decision:** Scaffolded with `composer create-project laravel/laravel` (resolved to Laravel 13.38 on PHP 8.4.25), `APP_LOCALE=fa` / `APP_FALLBACK_LOCALE=fa`.

**Why:** Matches "latest stable Laravel compatible with the PHP runtime" requirement; locale set to Persian since the site is Persian-first.

---

## 2026-10-20 — Removed `@laravel/multiplex` optional dependency from `package.json`

**Decision:** Removed the default `optionalDependencies: { "@laravel/multiplex" }` entry that ships with the Laravel 13 skeleton.

**Why:** It triggers a known `npm` 10.8.x/Vite 8 `@npmcli/arborist` crash (`Cannot read properties of null (reading 'edgesOut')`) when resolving its large optional peer tree (vitest, webdriverio, puppeteer-core, canvas, jsdom). Multiplex is unrelated to this project's scope (Laravel's real-time/AI websocket helper). Also upgraded global `npm` to 10.9.2 and used `--legacy-peer-deps` for the base install as a second line of defense against the same bug class.

---

## 2026-10-20 — Installed Laravel Boost (dev dependency)

**Decision:** Added `laravel/boost` as a dev dependency and ran `php artisan boost:install`, per the instructions the Laravel 13 skeleton itself ships in `AGENTS.md`.

**Why:** Official Laravel tooling for AI-assisted development (generates app-aware agent guidelines/tools); dev-only, no production impact.
