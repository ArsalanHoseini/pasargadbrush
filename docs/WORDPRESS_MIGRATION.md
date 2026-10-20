# WordPress Migration — Source Audit & Plan

## Source of truth

**Local copy, not live production:**
- Files: `C:\laragon\www\pasargadbrush`
- Database: local MySQL `pasargardbrush` (root / no password, local only)
- This is a Duplicator-generated copy that includes a full cPanel account backup (`cpmove-pasargadbrush` in the site root). It mirrors the live site closely enough for migration purposes; exact sync freshness has not yet been diffed against the live site and should be spot-checked before final cutover.

Live site (`pasargadbrush.com`) was only read via public pages/sitemaps (no admin login), to avoid touching production. Confirmed from `robots.txt` + sitemap index: **WordPress + WooCommerce + Yoast SEO**, sitemap at `/sitemap_index.xml`.

## Confirmed plugin stack (from local copy)

| Plugin | Purpose | Migration implication |
|---|---|---|
| WooCommerce | Product catalog, cart/checkout, orders | Source of products, categories, (minimal) order history |
| Elementor + "Xtra" theme/child theme | Page building | Page content is stored as Elementor JSON in postmeta — needs parsing, not just `post_content` HTML |
| WPML + WooCommerce Multilingual + WPML String Translation | Multilingual | **Confirmed real, not just installed**: every one of the 40 products has full fa/ar/en translations (120 `wp_posts` rows = 40 × 3 languages — this is what originally looked like "120 products"). Pages are partially translated (24 fa / 13 ar / 13 en), blog posts mostly fa-only (3,389 fa, 90 ar, 157 en). Owner decided (2026-10-20): new site is Persian-only for now; ar/en rows are left untouched in the source and can be imported later if the site goes multilingual. |
| Gravity Forms + multilingual add-on | Forms | Likely powers "online order form" and contact/inquiry forms — form structure + entries live in `wp_gf_*` tables |
| Yoast SEO + Premium | SEO metadata | Per-page/product title, meta description, canonical, redirects (Premium includes a redirect manager) — must be extracted for `docs/SEO_MIGRATION.md` and `docs/migration/url-mapping.csv` |
| iThemes Security, wps-hide-login | Security | No migration-relevant data, just confirms admin URL was hidden on the live site |
| RevSlider | Homepage/page sliders | Master prompt directs against arbitrary autoplay sliders; evaluate slide content for reuse as static hero imagery only |
| codevz-plus | Iranian WooCommerce add-on (likely payment/shipping/variations helper) | Needs direct inspection — unclear yet what it adds |
| Duplicator / Duplicator Pro | Backup/migration tool | How this local copy was produced; not relevant to the new app |

## Content inventory — corrected to Persian (`fa`) originals only

The first pass at this inventory (before the WPML discovery below) double- and triple-counted rows across languages. These are the real, fa-only numbers, each one now actually imported and verified:

| Type | Status | fa count | Migration relevance |
|---|---|---|---|
| product | publish | **40** | **Imported** — see "Import results" below |
| product category (`product_cat`) | — | **8** | **Imported** |
| page | publish | 24 | Not yet imported (not requested yet) |
| blog category (`category`) | — | **4** | **Imported** |
| post (blog) | publish | **42** | **Imported** |
| post (blog) | draft | ~3,500 (all languages) | **Not migrated** — stale draft backlog, not live content |
| portfolio | publish | 8 | Not yet imported — not in current main nav; confirm with owner if still wanted |
| attachment | — | 1,202 (all languages/uses) | Media library — copied per-product/post as referenced, not bulk-imported wholesale |
| shop_order | wc-cancelled | 1 | No real order history to migrate |

The 52-vs-120 discrepancy noted in the first pass of this document is now explained: 120 was 40 products × 3 languages (fa/ar/en), not 120 distinct products. The live sitemap's 52 could still differ slightly from today's local 40 (e.g. products added/removed between when this local copy was made and now) — worth a final spot-check against the live site before cutover, but not blocking since the local copy is already confirmed as the working source.

**Critical pricing finding (verified against every one of the 40 fa products):** `woocommerce_currency` is actually set to `USD` in `wp_options` (a leftover default, not real) and **none of the 40 products have `_price`, `_regular_price`, or `_sku` set at all** — no WooCommerce attributes, no product variations either. The entire current catalog is 100% inquiry-based in the real data. This means **all 40 imported products are `type = quote`**; the `priced` + `product_variants` capability in the new schema is for products the owner will add going forward through the new admin, not something migrated from WordPress. No currency conversion was needed or risked.

## Import results (committed to the dev database, 2026-10-20)

Built as Laravel service classes (`app/Services/WordPressImport/*`) + Artisan commands (`wp:import-product-categories`, `wp:import-products`, `wp:import-blog-categories`, `wp:import-blog-posts`), each defaulting to a dry run (`--commit` to actually write) and idempotent via the `migration_mappings` table. Also exposed in the admin UI at `/admin/import` (dry-run and commit buttons per step, with a results log) per the owner's request to drive this from the dashboard rather than the command line.

- **Product categories**: 8/8 imported, including correct parent/child resolution (all are currently flat/no hierarchy in the source).
- **Products**: 40/40 imported as `type = quote`, each with its real category attached, SKU generated as `WP-{wp_post_id}` (source has none), slug preserved exactly as the old Persian WordPress slug (good for later URL/SEO preservation), description populated from `post_excerpt` (where the real content actually lives — `post_content` is empty for every product).
- **Media**: 102 real image files copied from `wp-content/uploads` into the new app's media library (featured + gallery images), verified as valid, correctly-sized images on disk.
- **Blog categories**: 4/4 imported.
- **Blog posts**: 42/42 imported (published fa posts only — the ~3,500 draft posts are correctly excluded), body copied from `post_content` (this is where blog content lives, unlike products).
- All four importers have automated tests (`tests/Feature/WordPressImport/`) against a fixture SQLite "wordpress" connection, so they don't depend on the real local WP copy being present to be verified — 11 tests covering dry-run (no writes), commit, idempotent re-run, category/parent mapping, image copying, and language/status filtering.

## Live-site discovery (read-only, via public URLs)

- `robots.txt`: standard WooCommerce disallow rules (`/wp-content/uploads/`, add-to-cart params, `/wp-admin/` except `admin-ajax.php`) + Yoast sitemap reference.
- Sitemap index (`sitemap_index.xml`): 10 sub-sitemaps — post, page, portfolio, product, category, portfolio_cat, portfolio_tags, product_cat, product_tag, author.
- Main navigation: Home, Products (with category submenus), Image gallery, Articles/Blog, Product catalog (download), News, Online order form, About us, Contact us.
- Product categories: tool brushes (برس ابزاری), carpet-washing/cleaning (برس قالیشویی و نظافتی), carrot-washing/sorting (برس هویج‌شویی و سورتینگ), strip/sealing (برس نواری و دزدگیر), food-industry (برس صنایع غذایی).
- Footer: contact (address in Takht-e Jamshid, Marvdasht; phone; email), social links (LinkedIn, Instagram, Telegram, YouTube). No visible privacy/terms/return-policy pages — confirm with owner whether these exist elsewhere or need to be authored fresh.

## Next steps (not yet started)

1. Static pages (24 fa) and portfolio items (8) — not requested yet, confirm with owner whether/when to import.
2. Yoast SEO metadata (title/meta description/canonical) and the Premium redirect manager's existing redirects — not yet extracted; needed for `docs/SEO_MIGRATION.md` and `docs/migration/url-mapping.csv`.
3. Gravity Forms structure/entries (powers the live site's "online order form") — not yet inspected; relevant once the new quote-request flow is built (Phase 5).
4. `codevz-plus` plugin — confirmed to be a page-layout/styling helper for WooCommerce single product pages (`codevz_page_meta`, `codevz_single_page_css` postmeta), not a pricing or inquiry mechanism. No migration action needed.
5. Final spot-check of the local copy's 40 products against the live site's current count before any production cutover.
