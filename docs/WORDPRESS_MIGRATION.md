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
| WPML + WooCommerce Multilingual + WPML String Translation | Multilingual | Need to confirm with owner whether a second language was actually published (open question in `PROJECT_SCOPE.md`) |
| Gravity Forms + multilingual add-on | Forms | Likely powers "online order form" and contact/inquiry forms — form structure + entries live in `wp_gf_*` tables |
| Yoast SEO + Premium | SEO metadata | Per-page/product title, meta description, canonical, redirects (Premium includes a redirect manager) — must be extracted for `docs/SEO_MIGRATION.md` and `docs/migration/url-mapping.csv` |
| iThemes Security, wps-hide-login | Security | No migration-relevant data, just confirms admin URL was hidden on the live site |
| RevSlider | Homepage/page sliders | Master prompt directs against arbitrary autoplay sliders; evaluate slide content for reuse as static hero imagery only |
| codevz-plus | Iranian WooCommerce add-on (likely payment/shipping/variations helper) | Needs direct inspection — unclear yet what it adds |
| Duplicator / Duplicator Pro | Backup/migration tool | How this local copy was produced; not relevant to the new app |

## Content inventory (from local DB, `wp_posts`)

| Type | Status | Count | Migration relevance |
|---|---|---|---|
| product | publish | 120 | Primary migration target |
| page | publish | 48 | Primary migration target (static pages) |
| page | draft | 1 | Review before deciding to migrate |
| post (blog) | publish | 84 | Primary migration target |
| post (blog) | draft | 3,536 | **Not migrated** — stale draft backlog, not live content |
| portfolio | publish | 8 | Review with owner — not in current main nav; confirm if still wanted |
| attachment | inherit/private | 1,202 | Media library — source for all product/content images |
| shop_order | wc-cancelled | 1 | No real order history to migrate |

Note the live sitemap (`product-sitemap.xml`) currently lists 52 product URLs, vs. 120 `publish`-status products in the local DB — the discrepancy needs reconciling during import (could be out-of-stock/hidden products excluded from the sitemap, or the local copy being newer/older than live; must verify against live product listing before treating 120 as the final count).

## Live-site discovery (read-only, via public URLs)

- `robots.txt`: standard WooCommerce disallow rules (`/wp-content/uploads/`, add-to-cart params, `/wp-admin/` except `admin-ajax.php`) + Yoast sitemap reference.
- Sitemap index (`sitemap_index.xml`): 10 sub-sitemaps — post, page, portfolio, product, category, portfolio_cat, portfolio_tags, product_cat, product_tag, author.
- Main navigation: Home, Products (with category submenus), Image gallery, Articles/Blog, Product catalog (download), News, Online order form, About us, Contact us.
- Product categories: tool brushes (برس ابزاری), carpet-washing/cleaning (برس قالیشویی و نظافتی), carrot-washing/sorting (برس هویج‌شویی و سورتینگ), strip/sealing (برس نواری و دزدگیر), food-industry (برس صنایع غذایی).
- Footer: contact (address in Takht-e Jamshid, Marvdasht; phone; email), social links (LinkedIn, Instagram, Telegram, YouTube). No visible privacy/terms/return-policy pages — confirm with owner whether these exist elsewhere or need to be authored fresh.

## Next steps (not yet started)

1. Confirm with owner: WPML real bilingual usage (affects i18n scope) and the `parsbrush`/`parsbrush-new` folders (unrelated or not).
2. Spin up the local WordPress copy itself (Laragon vhost) to inspect via wp-admin/phpMyAdmin rather than raw table reads, to correctly decode Elementor JSON content, Gravity Forms field structures, and Yoast SEO metadata/redirects.
3. Build a read-only Laravel Artisan import command set per the master prompt's migration requirements (dry-run mode, mapping tables, reconciliation report) once the target schema exists (Phase 2).
4. Reconcile the 52 (live sitemap) vs 120 (local DB) product count discrepancy before finalizing the product import list.
5. Produce `docs/migration/url-mapping.csv` once URL structure for the new app is decided.
