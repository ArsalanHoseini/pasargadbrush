# Architectural & Business Decisions Log

Newest first. Each entry: decision, reasoning, alternatives considered, date.

---

## 2026-10-21 — About Us content: real, sourced verbatim, one inferred unit

**Decision:** Seeded a real "About Us" page (`PagesSeeder`) using the owner-provided live URL (`https://pasargadbrush.com/about-us-2/`). The exact Persian wording was pulled from the local WordPress copy's `post_content` for page ID 8 ("درباره ما") — not paraphrased from the live page's rendered text — since WebFetch's quoting limit would have forced paraphrasing. Also copied the two real images referenced in that content (`pasargadbrush-group2-1024x739-1.jpg`, `بق-min-3.png`) into the media library.

**One inferred detail, flagged rather than silently guessed:** the source content includes four stats with no stated unit ("110", "89", "97", "1000"). Two (110 product varieties, 1000 contracts) are clearly counts. The other two ("89 رضایتمندی مشتری", "97 تضمین کیفیت محصولات") read naturally as percentages given their labels (customer satisfaction, quality assurance) — rendered as `89٪` / `97٪`. These are the company's own authored numbers, not invented, but the % unit itself is an inference from context, not something explicitly labeled in the source; worth the owner double-checking.

**Omitted, not fabricated:** the source page also has a customer-logo carousel (a dozen small unlabeled logo images) and animated counters that were broken on the live site itself ("product statistics... display as 0" per the live-site check). The logo carousel was skipped — the images have no real identifying alt text, so reproducing them would be decorative at best — rather than guessing which real companies they represent.

**Contact Us page left unchanged:** also checked the owner-provided `https://pasargadbrush.com/contact-us/` URL against the already-seeded Contact Us page; the real contact widgets on that page are exactly the phone/email (already in `settings`) with no additional body copy, so no changes were needed there.

---

## 2026-10-21 — Lesson: never round-trip Persian text through bash for manual admin testing

**What happened:** While manually curl-testing the new admin "specifications" field against the real dev database, Persian form values were extracted from HTML via `grep`/`sed` under `LC_ALL=C` (needed for other reasons in the same pipeline). That silently corrupted the Persian bytes into literal `?` characters, which then got genuinely submitted and saved — overwriting a real imported product's `name` (and, as a side effect of `PersianSlug::make()` producing an empty string from `"???"`, its `slug`) with garbage, and separately wiped its category and image associations because the test request omitted `category_ids`/`image_ids`/`primary_category_id` (fields a real browser form always sends, even if empty) — the admin controller's "sync" methods correctly treat an omitted field as "clear this," which is correct behavior for a real form submission but dangerous for a hand-rolled partial test request.

**Recovery:** Deleted the corrupted product + its `migration_mappings` row + orphaned pivot rows, then re-ran `php artisan wp:import-products --commit`, which is idempotent and recreated it correctly from the untouched WordPress source. Verified counts (40 products, 40 category links, 102 images) matched exactly before and after.

**Rule going forward:** Automated tests (real PHP strings, no shell round-trip) are the right way to verify behavior involving Persian/non-ASCII data — several already exist and correctly proved this specific feature works before this incident. Manual `curl` against the real dev database is fine for structural checks (HTTP status, presence of elements, ASCII content) but must never be used to round-trip Persian form data, and any manual admin-form POST must include every field a real form would send (or expect fields to be cleared, by design).

---

## 2026-10-21 — Never cache Eloquent models/Collections on the `database` cache driver

**Decision:** `App\Models\Setting::get()` and the header/footer nav-categories composer (`AppServiceProvider`) now cache plain arrays of scalar values, never Eloquent models or Collections.

**Why (real bug, found while verifying the homepage against a live server, not by inspection):** Caching a `Collection` of `ProductCategory` models via `Cache::remember()`/`rememberForever()` on this app's `database` cache store corrupted on read-back — the first bug surfaced as `unserialize(): incomplete object` for a cached `Collection`, the second as `Attempt to read property "name" on string` for a cached `Collection` of models, even though the raw serialized value in the `cache` table's `value` column was well-formed PHP `serialize()` output. Root cause not fully isolated (likely an interaction between this Laravel version's database cache store and PHP object unserialization in this environment), but the fix is correct regardless of root cause: caching plain arrays is cheaper, has no coupling to model internals, and this is standard Laravel advice independent of this bug.

**How to apply:** Any future `Cache::remember()` call must store scalars/arrays, never Eloquent models, Collections, or other objects with non-trivial internal state. If a model is needed after a cache hit, re-fetch it by id or re-hydrate from the cached array.

---

## 2026-10-21 — Real contact info confirmed by owner; one field still open

**Decision:** Seeded (`SettingsSeeder`) and now live in the footer/contact sections: mobile `09174287445`, email `pbc_info@yahoo.com`, address «مرودشت، کیلومتر ۶۰ جاده شیراز-اصفهان، شهرک صنعتی انجیره مرودشت» — all owner-confirmed 2026-10-21, overriding earlier conflicting values found in the WordPress source (a second mobile number and a second address wording ("تخت جمشید") were found during migration and are now known to be wrong/outdated).

**Open:** The landline/office phone number isn't in the local WordPress copy at all (checked DB and theme files — genuinely absent, meaning that local backup predates whatever added it to the live site) and displays with broken/reversed formatting even on the live site itself. No landline is in `settings` yet — asked the owner directly rather than guessing a real business's phone number. Add a `contact_landline` setting once confirmed.

**Why real logo works:** Found and copied the actual brand logo from the local WordPress media library (`wp-content/uploads/2021/10/logo.png`) into `public/images/logo.png` — this is the real asset, not a placeholder.

---

## 2026-10-21 — Self-hosted Vazirmatn (Persian font), not a font CDN

**Decision:** Installed the `vazirmatn` npm package and self-host 4 static weights (400/500/600/700, woff2) via `@font-face` in `app.css`, built by Vite. Removed the default Laravel skeleton's Bunny-Fonts-CDN "Instrument Sans" setup (`laravel-vite-plugin/fonts`) from `vite.config.js` entirely — it's irrelevant now that the site is Persian-first.

**Why:** Reliable loading without a third-party font request at runtime (matches master prompt's "reliable loading behavior" requirement); a variable woff2 wasn't available in the installed package version, so static per-weight files are used instead (still small: ~200KB total for 4 weights).

---

## 2026-10-21 — Brand accent color taken from the real site, not invented

**Decision:** `--color-brand-accent: #ff7700` (orange/gold) and `--color-brand-charcoal: #1d1d1d` in `app.css` `@theme`, matching the master prompt's "white background, charcoal text, restrained gold/yellow accent" direction.

**Why:** These exact hex values were found in the live site's own Elementor widget settings (icon background/hover colors on the real contact page), not guessed — so the new site's accent color matches the existing brand rather than introducing an arbitrary new one.

---

## 2026-10-20 — WordPress import reads the local DB directly, not export files

**Decision:** Owner confirmed (when asked): import products/blog/categories by reading the local WordPress copy's database directly (`App\Services\WordPressImport\*` + a read-only `wordpress` Eloquent connection), not by uploading WXR/CSV export files through the admin.

**Why:** Faster, more complete (real attachment files, exact category relationships), and repeatable/idempotent via `migration_mappings` — matches the master prompt's migration requirements better than one-off file uploads. Never touches the live site.

---

## 2026-10-20 — All migrated products are `type = quote`; no currency conversion needed

**Decision:** Every one of the 40 real WooCommerce products was verified to have zero price, SKU, or variation data. All imports land as `requires_quote`/`type = quote`. The `priced` + `product_variants` machinery exists for products the owner adds going forward, not for anything migrated.

**Why:** Verified directly against the data before writing the importer (`SELECT` across all 40 products for `_price`/`_regular_price`/`_sku`) rather than assuming — avoids guessing a Rial/Toman conversion where none is needed, and avoids fabricating prices that don't exist in the source.

---

## 2026-10-20 — Persian-safe slugs: custom helper, not `Str::slug()`

**Decision:** Added `App\Support\PersianSlug::make()` — keeps Unicode letters/digits and only normalizes whitespace/punctuation to dashes.

**Why:** Laravel's `Str::slug()` transliterates to ASCII, which mangles or empties Persian text. This also matches how the existing WordPress site's real Persian slugs are shaped, which matters for preserving URLs later.

---

## 2026-10-20 — Two real bugs caught by writing tests (not just by inspection)

1. **`Product::categories()` / `ProductCategory::products()` belongsToMany** used the default guessed pivot foreign key (`product_category_id`) instead of the actual migration column name (`category_id`). Caught immediately when running the real product import (`--commit`) against the local WP copy — a live `QueryException`, not a test. Fixed by passing explicit pivot keys to `belongsToMany()`.
2. **`FormRequest::validated()` / `->safe()->except()` omit a key entirely** when it wasn't present in the request at all (as opposed to present-but-empty) — `$data['slug']` then threw `Undefined array key` in every admin create/update controller that auto-generates a slug. Caught by a feature test that (correctly, like a minimal API client would) only submitted the fields it cared about. Fixed everywhere with `($data['slug'] ?? null) ?: ...`. Not a practical problem through the actual Blade forms (every text input is always rendered, so the key is always present, just possibly empty), but the code is now correct regardless of caller.

**How to apply:** When adding a `belongsToMany` on a non-conventionally-named pivot table, always pass explicit foreign/related pivot key names — don't rely on Laravel's naming guess. When reading `validated()`/`safe()` for an optional field, access it with `??`, never direct array access.

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
