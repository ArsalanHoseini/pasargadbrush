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

`php artisan test` — 41/41 passing (2 default + 6 admin-auth + 5 media-upload + 11 WordPress-import + 5 category-management + 5 blog-management + 5 product-management + 2 upload-related). Two real bugs were caught by these tests while writing them (a wrong pivot foreign key, and `validated()` omitting absent keys) — see `docs/DECISIONS.md`.

**Also manually verified against a real running server** (not just PHPUnit's in-process client): started `php artisan serve`, walked the full HTTP flow with `curl` (cookie jar, real CSRF tokens, a real generated JPEG) — login, dashboard, media library page, upload (confirmed thumbnail served back with HTTP 200, correct width/height extracted from the real file), delete (confirmed removed from both disk and DB). See `docs/DECISIONS.md` for a `php artisan serve` + multipart/`Expect: 100-continue` gotcha hit during this (environment quirk, not an app bug). No actual browser/screenshot was taken (no `chromium-cli`/Playwright available on this machine) — the RTL/Persian layout has not been visually eyeballed yet, only confirmed present in the rendered HTML.

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

**Media library (admin, manual upload) — added ahead of schedule at owner's request**
- `/admin/media`: upload (image only: jpg/jpeg/png/webp, 5MB max, SVG deliberately excluded for XSS safety, random generated filenames, real content-type verified not just extension) + grid listing + delete (removes DB row and the file from disk).
- `App\Actions\Media\StoreUploadedMedia` — reusable action, will be reused by product/blog image upload in later phases instead of duplicating upload handling.
- 5 feature tests (guest blocked, successful upload with correct metadata, non-image rejected, oversized rejected, delete removes DB+disk) — all passing.
- Enabled the `public` storage symlink (`php artisan storage:link`).

**WordPress import (owner-requested, pulled forward from Phase 9) — completed and run for real**
- Read-only `wordpress` DB connection (`config/database.php`) pointed at the local WP copy (`pasargardbrush`) — never the live site.
- Discovered via direct inspection (not assumed): WPML actually has full fa/ar/en product translations (not just installed); all 40 real products have **no price, SKU, or variations at all** in the source — confirms the entire migrated catalog is `type = quote`; `codevz-plus` is a WooCommerce page-layout plugin, not a pricing/inquiry mechanism. Full findings in `docs/WORDPRESS_MIGRATION.md`.
- Built `app/Services/WordPressImport/{ProductCategoryImporter,ProductImporter,BlogCategoryImporter,BlogImporter,MediaCopier}.php` + 4 Artisan commands (`wp:import-*`, default dry-run, `--commit` to write, idempotent via `migration_mappings`).
- **Actually run against the real local WP copy and committed**: 8 product categories, 40 products (with real categories + 102 real copied images), 4 blog categories, 42 published blog posts — all verified in the dev database, not just dry-run text.
- 11 automated tests against a fixture SQLite "wordpress" connection (don't depend on the real local copy being present): dry-run writes nothing, commit persists correctly, idempotent re-run, parent-category resolution, image copying, language/status filtering.
- Exposed in the admin dashboard at `/admin/import` (dry-run / real-run buttons per step + results log), per the owner's request to drive this from the UI.

**Admin CRUD ("complete the dashboard" — owner-requested, pulled forward from Phase 6)**
- Product categories (`/admin/categories`): list, create, edit, delete (blocked if it has products or child categories), parent hierarchy, SEO fields, Persian-safe slug auto-generation (`App\Support\PersianSlug` — `Str::slug()` mangles Persian to nothing, so this keeps Unicode letters and only normalizes punctuation/whitespace to dashes).
- Products (`/admin/products`): list, create, edit, delete; quote-vs-priced type toggle (Alpine.js shows/hides the variants section); dynamic variant rows (add/remove, price entered in Toman and stored as Rial ×10); category (primary + secondary) and image-gallery pickers from the media library.
- Blog categories and posts (`/admin/blog-categories`, `/admin/blog-posts`): list, create, edit, delete; featured-image picker from the media library; publish/draft toggle that sets/clears `published_at` correctly (doesn't reset the date on every re-save of an already-published post — caught and fixed via a test).
- 15 feature tests covering all of the above (auth gating, slug generation, delete guards, variant sync including deletion of removed rows, image/category pivot sync, publish-date handling, uniqueness validation).
- **Real bug caught and fixed during testing** (not just by inspection): `FormRequest::validated()`/`safe()` omits a key entirely when it's absent from the request, so `$data['slug']` threw `Undefined array key` whenever the slug field wasn't submitted — fixed everywhere with `($data['slug'] ?? null) ?: ...`.
- Wired Alpine.js into `resources/js/app.js` (previously an empty placeholder) since the product form needed it for the dynamic variant rows.

**Phase 3 — Public design system + homepage (completed)**
- Real brand assets: logo copied from the WordPress media library (`public/images/logo.png`); accent color (`#ff7700`) and charcoal (`#1d1d1d`) taken from the live site's own styling, not invented.
- Self-hosted Vazirmatn Persian font (4 weights, woff2) via Vite; removed the unused default "Instrument Sans" CDN font setup.
- `layouts/public.blade.php` + `partials/header.blade.php` + `partials/footer.blade.php`: RTL, real category nav (cached, invalidated on category save/delete), real contact info from `settings` (owner-confirmed 2026-10-21: mobile, email, address — landline still pending, intentionally omitted rather than guessed).
- Homepage (`home.blade.php`): hero, real category grid (8), real featured products (real images where present), articles section (real imported posts), a capabilities section with only true, non-fabricated claims (no invented stats/certifications), Organization JSON-LD using only real settings data.
- Category show page, product show page (image gallery, quote CTA via real `tel:`/`mailto:` links — no fake "add to cart" since all 40 real products are `type=quote`; `priced` products would show real variant prices), blog index/show (related posts, BlogPosting JSON-LD).
- `App\Support\Money::toman()` — the one place Rial→Toman display conversion happens, per the currency convention in `docs/DATABASE_DESIGN.md`.
- **Two real bugs found and fixed while manually verifying against a real running server** (not caught by the automated test suite beforehand, since tests use the `array` cache driver): caching Eloquent models/Collections via `Cache::remember()` on the `database` cache driver corrupted on read-back. Fixed by caching plain arrays everywhere; added a regression test (`SettingCacheTest`) that explicitly forces the `database` cache driver so this class of bug is actually caught in CI, not just by luck of which driver the test environment defaults to.
- 11 new feature/unit tests (`PublicSiteTest`, `SettingCacheTest`) covering: homepage shows active categories/products only, category/product pages 404 when inactive, quote CTA renders, blog shows only published posts, draft posts 404, related-posts logic, real contact info present on every page, and the cache round-trip itself.
- Manually verified end-to-end against a real running server with real imported data (not just synthetic test fixtures): homepage, a real category page, a real product page, blog index, and a real blog post all returned HTTP 200 with correct real Persian content, correct image URLs (HTTP 200), and correct structured data.

**Phase 4 — Catalog polish (completed)**
- Category pages: search within category + sort (newest / name A–Z), query-string preserved across pagination, canonical always points to the clean category URL (not the filtered variant) to avoid duplicate-content signals.
- Site-wide product search (`/search`, header search box): matches name/short description/description, active products only.
- Technical specifications: admin product form now has a dynamic key/value editor (Alpine, same pattern as variants); displayed as a definition list on the public product page. Not populated from the WordPress import (the source genuinely has none — see `docs/WORDPRESS_MIGRATION.md`), but now usable going forward.
- 7 new tests (search with/without results, inactive products excluded, category search/sort, specifications saved correctly with blank rows filtered out).

**Lesson recorded (`docs/DECISIONS.md`):** a manual curl-based admin test corrupted a real imported product's name/slug/category/images by round-tripping Persian text through a `LC_ALL=C` shell pipeline and omitting fields a real form always sends. Fully recovered via `wp:import-products --commit` (idempotent re-import). Going forward, Persian-data correctness is verified through the automated test suite, not manual shell round-trips.

**Phase 5 (partial) — Real quote-request workflow (completed)**
- Replaced the `tel:`/`mailto:` CTA on product pages with a real, functional quote-request form (`<x-quote-request-form>` Blade component) that stores into `quote_requests` — the `tel:` link is kept as a secondary option, not removed.
- Validation (name/phone/message required), a honeypot field (hidden `website` input — must stay empty, `prohibited` rule) for spam protection without any paid CAPTCHA service, and rate limiting (`throttle:5,1`) on the public endpoint.
- Admin notification email (`App\Mail\NewQuoteRequestReceived`) sent to the real business email (`setting('contact_email')`) on every submission; wrapped in try/catch so a mail-transport failure never loses the actual lead — it's already saved before the email is attempted. `MAIL_MAILER=log` in this dev environment (logs the email instead of sending); **real SMTP credentials are a genuine external dependency needed before production** — documented here rather than invented.
- Admin: `/admin/quote-requests` (filterable by status: new/contacted/quoted/closed), a detail/edit page (change status, add internal notes, delete). Dashboard now shows real counts (active/total products, categories, new quote requests) and the 5 most recent requests — replacing the earlier placeholder welcome text.
- 11 new tests (public submission with/without a product, required-field validation, honeypot rejection, rate limiting, mail-failure resilience, admin filtering/update/delete, dashboard count). Full suite 67/67 passing.
- Manually verified end-to-end against a real running server using only ASCII test data (per the lesson above): submission saves correctly, notification email is logged with correctly MIME-encoded Persian subject text, nav badge and dashboard both reflect the new request, honeypot/rate-limiting logic is proven by the automated tests (a manual re-check of the honeypot specifically hit an unrelated session-token-reuse snag in the curl script, not a real app issue — the automated test is the authoritative proof here).

**Settings UI + static pages (completed)**
- `/admin/settings`: single form, grouped (general/contact/social), editing the real `settings` table — general + contact fields pre-filled with the real owner-confirmed values; social links (Instagram/Telegram/LinkedIn/YouTube) and the landline are intentionally empty, not fabricated, ready for the owner to fill in themselves. Email/URL format validated server-side.
- `/admin/pages`: full CRUD (title/slug/body/template/status/SEO fields), Persian-safe slug auto-generation, publish/draft toggle with correct `published_at` handling (same pattern as blog posts/products).
- Public rendering at `/page/{slug}` (deliberately not a bare `/{slug}` catch-all — would have risked shadowing `/admin`, `/search`, etc. depending on route-registration order; verified via `route:list` that nothing conflicts). Page `template` field drives rendering: `contact` additionally shows real settings-driven contact info + the same quote-request form component used on product pages; `about`/`default` just render the body.
- Seeded exactly one real page: "تماس با ما" (`contact-us`, template `contact`) — its only written content is a neutral, true one-line instruction; the actual contact details come live from `settings`. **"About Us" was deliberately NOT seeded** — that needs real company history/narrative only the owner can write truthfully; the admin tool to create it now exists (`/admin/pages`).
- Header/footer "تماس با ما" links now point to the real page instead of a same-page anchor; footer conditionally shows social links only for whichever platforms have a real URL set.
- 15 new tests (settings validation/update/cache-invalidation, page CRUD/slug/publish-state, public rendering of both templates, draft/unknown-slug 404s). Full suite 82/82 passing.
- Manually verified end-to-end on a real running server (ASCII-only data per the established lesson): confirmed `/admin` itself still resolves correctly after adding the new page route (the exact risk called out above), settings update round-trips and invalidates the cache, contact page renders live settings data. The real settings I temporarily overwrote with ASCII test values during this verification were restored immediately afterward (re-ran `SettingsSeeder`, deleted the two fabricated test-only keys) — verified via a direct DB query that all values matched the pre-test state exactly.

**About Us page populated with real content (completed)**
- Owner provided the live URLs (`/about-us-2/`, `/contact-us/`) to source from. Pulled the exact Persian wording from the local WordPress copy's `post_content` (not a paraphrase) and copied the two real images it references into the media library. Full sourcing/inference notes in `docs/DECISIONS.md` (one unit — % — was inferred from context for two stats that had no explicit unit in the source; everything else is verbatim or a straight copy).
- Deliberately omitted the source page's customer-logo carousel (unlabeled images, decorative value only) rather than guessing which real companies they represent.
- Checked the real Contact Us page too — no body copy beyond the phone/email widgets already represented in `settings`, so nothing needed changing there.
- Header/footer now link to the real About Us page.
- `PagesSeeder` is idempotent (verified by running it twice — no duplicate media rows created).
- Manually verified on a real running server: both real images serve with HTTP 200, page renders the real history/capabilities/industries/testimonial content.

## Next concrete step

Remaining gaps, none blocking what's been built so far: landline phone number and social media URLs (owner can now add these themselves via `/admin/settings`, or tell me and I will), real SMTP credentials for production email delivery, cart/checkout/Zarinpal for `priced` products (none exist yet since all real products are quote-type — the rest of Phase 5), orders/customers admin, SEO redirect management.
