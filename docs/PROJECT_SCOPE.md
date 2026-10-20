# Project Scope — Pasargad Brush Laravel Platform

## Brand

- **Company:** Pasargad Brush (شرکت برس صنعتی پاسارگاد) — industrial brush manufacturer.
- **Current live site:** https://pasargadbrush.com/ — WordPress + WooCommerce + Elementor (Xtra theme) + WPML + Gravity Forms + Yoast SEO Premium.
- **Objective:** Replace the WordPress/WooCommerce site with a custom Laravel + MySQL application, preserving content, SEO value, and business functionality while improving performance, maintainability, and administration.

## Confirmed business model (owner-confirmed, 2026-10-20)

The sales model is **hybrid**, not purely inquiry-based and not purely a standard fixed-catalog store:

- **Custom / quote-based products:** some products are made to order or require a quote (استعلامی). These must use a "request a quote" flow, not add-to-cart.
- **Catalog products with variants:** other products come in defined sizes/dimensions with a **fixed price per variant**. These support real add-to-cart / order flow with server-calculated pricing.

→ The product model must support **both** a `requires_quote` product type and a `priced` product type with variants (e.g. by dimension), on the same catalog, decided per-product by the admin. This is a core data-model decision — see [`DECISIONS.md`](DECISIONS.md).

## Access and sources (confirmed)

- The owner has full control of the WordPress installation — no restrictions on what can be pulled from it.
- A **complete local working copy already exists** on this machine (not fetched by us): `C:\laragon\www\pasargadbrush`, backed by a local MySQL database `pasargardbrush`, including:
  - A full cPanel account backup (`cpmove-pasargadbrush`) in the site root.
  - `wp-content/uploads` — 254 MB of real media (product photos, logos, galleries).
  - Database: 120 published products, 48 published pages, 84 published blog posts (plus ~3,536 draft posts, not migration-relevant), 8 portfolio items, 1 cancelled WooCommerce order (no real order history to migrate).
  - Plugins confirming prior behavior: WooCommerce, WPML + WooCommerce Multilingual + WPML String Translation, Gravity Forms + multilingual add-on (powers "online order form" / inquiries), Yoast SEO + Premium, Elementor, iThemes Security, wps-hide-login, RevSlider, Duplicator/Duplicator Pro (how this local copy was made), head-footer-code, codevz-plus (Iranian WooCommerce add-on, likely payment/shipping related — needs inspection).
- **Decision:** this local copy is the migration source of truth. We do not need to connect to the live production server for discovery or export, which keeps production completely untouched during migration work. The `s.txt` cPanel credential found in the parent folder is not needed for this and has not been used.

## Resolved (owner-confirmed, 2026-10-20)

- **Language:** Persian only for now. English may be added later — don't build bilingual UI/WPML-equivalent now, but avoid schema choices that would block adding a locale later.
- **`parsbrush` / `parsbrush-new`:** a different, unrelated site for the same client. Never touched by this project.
- **Payment gateway:** Zarinpal, for priced/variant products. Built behind a small gateway abstraction since more gateways may be added later.
- **Torob:** owner registers the merchant account themselves; we build the feed/integration to be technically ready independent of that.

## Out of scope for now

- Migrating WooCommerce customer accounts/orders (effectively none exist — 1 cancelled order found).
- Any payment gateway other than Zarinpal.
- Torob live submission/activation (owner-side registration step, not blocking our technical build).
- Bilingual (FA/EN) UI.
