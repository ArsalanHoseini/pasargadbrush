# Architectural & Business Decisions Log

Newest first. Each entry: decision, reasoning, alternatives considered, date.

---

## 2026-10-20 — Enabled `pdo_sqlite`/`sqlite3` in the local PHP install

**Decision:** Uncommented `extension=pdo_sqlite` and `extension=sqlite3` in `C:\laragon\bin\php\php-8.4.25-Win32-vs17-x64\php.ini`.

**Why:** `phpunit.xml` (Laravel default) runs the test suite against an in-memory SQLite database, isolated from the real MySQL dev database — this is the standard, fast, safe way to run Laravel tests and avoids any risk of tests touching `pasargadbrush_laravel`. The extension wasn't enabled by default on this machine.

---

## 2026-10-20 — Shared media library instead of per-entity image tables

**Decision:** One `media` table + a polymorphic `mediables` pivot (role + sort_order), used by products, blog posts, pages, categories — instead of separate `product_images`, `blog_post_images`, etc.

**Why:** Matches how the admin "media management" domain is supposed to work per the master prompt (upload once, reuse anywhere) and avoids duplicating the same upload-handling logic per entity.

---

## 2026-10-20 — Admin auth: hand-rolled, not a starter kit

**Decision:** Built a minimal session-based login (`routes/admin.php`, `Admin\Auth\LoginController`, `EnsureUserIsAdminStaff` middleware) directly on Laravel's default `users` table + a `role` enum column (`admin`/`editor`), rather than installing Breeze/Jetstream.

**Why:** The platform is meant to be custom-coded with its own design system; a starter kit's scaffolded Blade/Tailwind would just get replaced. Role check is a single enum for now — a full permissions package is deferred until real multi-role needs appear (not pre-built speculatively).

**Follow-up:** `redirectGuestsTo()` in `bootstrap/app.php` currently always points to `admin.login`. Once Phase 5 adds customer-facing auth (storefront login for `priced` checkout), this needs to become guard-aware (admin guests → `admin.login`, storefront guests → the future customer login route).

---

## 2026-10-20 — Admin media library (manual upload) built in Phase 2, ahead of Phase 6

**Decision:** Built a working media library page (`/admin/media`) with manual upload/delete now, instead of waiting for the full Phase 6 admin dashboard.

**Why:** Owner asked for the ability to upload files manually. It also unblocks Phase 3 (homepage/design) without having to script extraction from the WordPress media library for every asset — real logo/product photos can just be uploaded through the admin UI.

**Security notes:** uploads are restricted to `jpg`/`jpeg`/`png`/`webp` — SVG is deliberately excluded (stored-XSS risk if ever rendered inline); stored filenames are always random (`Str::random(40)`), never the original filename; Laravel's `image` rule verifies real file content via `getimagesize`/finfo, not just the extension (verified with an automated test that renames a PHP file to `.jpg`-style multipart but keeps a non-image mime, which is correctly rejected). Max upload size 5 MB.

**Verified (not just unit-tested):** ran the app for real with `php artisan serve` + a `curl` cookie-jar walkthrough (login → dashboard → media page → upload → confirm thumbnail serves with HTTP 200 → delete → confirm removed from disk and DB). No `chromium-cli`/Playwright was available on this Windows machine, so this was an HTTP-level verification, not a pixel screenshot — actual visual/RTL rendering in a real browser window still needs a human look before Phase 3 design work is called "verified," not just "implemented."

**Gotcha found (environment, not app bug):** `php artisan serve`'s built-in PHP dev server mishandles `Expect: 100-continue`, which `curl` sends automatically once a multipart body exceeds ~1KB — this made multipart uploads appear to fail CSRF validation when tested via curl (`curl -H "Expect:"` fixes it). Confirmed this does not happen through PHPUnit's in-process test client (which is why the automated tests passed before this was even noticed) and does not affect real production servers (Nginx/PHP-FPM, Apache). Worth remembering if a future manual curl test of any file upload route mysteriously 419s.

---

## 2026-10-20 — Owner confirmations: language, unrelated sites, payment, Torob

**Decisions (owner-confirmed):**
- **Language:** site is single-language Persian for now; English may be added later. Build the schema/content model in a way that doesn't block adding a locale later (e.g. don't hardcode Persian strings into structured data that would need a schema change to translate), but **do not build a bilingual UI or WPML-style translation system now** — that's explicitly deferred.
- **`parsbrush` / `parsbrush-new`:** confirmed unrelated to this project (a different site for the same client). Must not be touched, read from, or referenced by this project's migration work.
- **Payment gateway:** Zarinpal is the target for now, with the possibility of adding other gateways later. Payment integration should go behind a small gateway abstraction (even if Zarinpal is the only implementation initially) rather than hardcoding Zarinpal calls throughout checkout code.
- **Torob:** owner will handle merchant registration themselves. We build the feed/integration to be technically ready; no dependency on us for the registration step.

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
