# Database Design

MySQL 8, InnoDB, `utf8mb4_unicode_ci` on every table.

## Currency convention (read this before touching any money field)

**All monetary values are stored as integer Iranian Rial (IRR), in `unsignedBigInteger` columns — never decimals, never Toman.**

- Iranian Rial has no practical subunit, so integers are exact (no float/decimal rounding risk).
- Zarinpal's API operates in Rial. Storing in Rial avoids a conversion step at the one place (payment) where a mistake is costly.
- **Toman is a display-only concept**: the UI divides by 10 and formats with Persian digits when showing a price to a human. No stored value is ever "in Toman." A `money()`/Blade helper will own this conversion in one place so it's never done ad hoc in a view.
- Columns holding money: always suffixed `_rial` (e.g. `price_rial`, `total_rial`) so it's unambiguous at the call site.

## Core catalog

**`product_categories`**
- `id`, `parent_id` (nullable FK self, for hierarchy), `name`, `slug` (unique), `description` (text, nullable), `sort_order` (int, default 0), `is_active` (bool, default true)
- SEO: `meta_title`, `meta_description`, `canonical_url` (all nullable strings/text)
- Timestamps

**`products`**
- `id`, `sku` (unique), `slug` (unique), `name`
- `primary_category_id` (FK → product_categories, nullable — a product could theoretically be unfiled during import, not allowed to publish without one)
- `type` (enum: `quote`, `priced`) — **the hybrid model decision**: `quote` products never show a price and always render a quote-request CTA; `priced` products are sold through variants (see below) and never render a quote CTA.
- `short_description` (text, nullable), `description` (longtext, nullable)
- `specifications` (JSON, nullable) — free-form key/value technical specs (material, bristle type, dimensions range, etc.) since WooCommerce's actual attribute set is unknown until migration import runs; never fabricated ahead of real data.
- `stock_status` (enum: `in_stock`, `out_of_stock`, `made_to_order`, nullable) — only set when we have a real answer; nullable rather than defaulting to a fabricated status.
- `is_featured` (bool, default false), `is_active` (bool, default false — unpublished until explicitly published)
- SEO: `meta_title`, `meta_description`, `canonical_url`, `og_image_media_id` (nullable FK → media)
- `sort_order` (int, default 0)
- Timestamps + `published_at` (nullable)

**`category_product`** (pivot, many-to-many — a product can be tagged into secondary categories beyond its `primary_category_id`)
- `product_id`, `category_id`

**`product_variants`** (only meaningful for `type = priced` products)
- `id`, `product_id` (FK), `sku` (unique), `label` (e.g. "۴۰×۶۰ سانتی‌متر")
- `attributes` (JSON — e.g. `{"width_cm": 40, "length_cm": 60}`)
- `price_rial` (unsignedBigInteger, **not nullable** — a priced product's variant must have a real price)
- `stock_quantity` (unsignedInteger, nullable — nullable means "not tracked", not "zero")
- `is_default` (bool, default false), `sort_order` (int, default 0)
- Timestamps

**`attributes`** / **`attribute_values`** / **`attribute_value_product`**
- For catalog filtering (e.g. material, application). Populated from real migrated data — not pre-seeded with guessed values, per the master prompt's instruction not to invent filters for attributes that don't exist.
- `attributes`: `id`, `name`, `slug` (unique)
- `attribute_values`: `id`, `attribute_id` (FK), `value`, `slug`
- `attribute_value_product`: `product_id`, `attribute_value_id`

## Media (shared library, used by products/categories/blog/pages)

**`media`**
- `id`, `disk` (string, default `public`), `path`, `mime_type`, `size` (bytes), `width`/`height` (nullable), `alt_text` (nullable), `uploaded_by` (nullable FK → users), timestamps

**`mediables`** (polymorphic pivot)
- `media_id` (FK), `mediable_type`, `mediable_id`, `role` (string, e.g. `gallery`, `featured`, `logo`), `sort_order`

This replaces separate `product_images` / per-entity image tables — one upload can be reused as, say, a product gallery image and a blog illustration without duplicating the file.

## Customers & addresses

**`customers`**
- `id`, `name`, `phone` (unique, since Iranian storefronts generally key on phone), `email` (nullable), `password` (nullable — guest checkout allowed for `priced` orders), `email_verified_at`, timestamps

**`addresses`**
- `id`, `customer_id` (FK), `province`, `city`, `address_line`, `postal_code` (nullable), `recipient_name`, `recipient_phone`, `is_default` (bool), timestamps

## Cart, orders, payments (for `priced` products only — `quote` products go through `quote_requests`)

**`carts`**
- `id`, `customer_id` (nullable FK), `session_token` (nullable, for guest carts), timestamps

**`cart_items`**
- `id`, `cart_id` (FK), `product_variant_id` (FK), `quantity` (unsignedInteger), timestamps
- Price is **never** stored here — always read live from `product_variants.price_rial` at render/checkout time so stale cart rows can't under/overcharge.

**`orders`**
- `id`, `order_number` (unique, human-readable, e.g. `PB-20261020-0001`), `customer_id` (nullable FK, nullable = guest order)
- `status` (enum: `pending`, `confirmed`, `processing`, `shipped`, `completed`, `cancelled`)
- `payment_status` (enum: `unpaid`, `paid`, `failed`, `refunded`)
- `shipping_address_id` (nullable FK → addresses)
- `subtotal_rial`, `shipping_cost_rial`, `total_rial` (all unsignedBigInteger)
- `notes` (nullable text), `placed_at`, timestamps

**`order_items`**
- `id`, `order_id` (FK), `product_id` (FK), `product_variant_id` (nullable FK)
- `name_snapshot`, `sku_snapshot` (string — captured at order time so later product renames/deletes don't corrupt historical orders)
- `unit_price_rial` (unsignedBigInteger, captured at order time — **never recalculated from the live product**), `quantity`, `line_total_rial`

**`payments`**
- `id`, `order_id` (FK), `gateway` (string, e.g. `zarinpal` — kept as a string, not an enum, so adding a gateway later is a data change, not a migration), `authority` (gateway tracking token, nullable), `ref_id` (nullable), `amount_rial`, `status` (enum: `pending`, `success`, `failed`), `raw_response` (JSON, nullable — for debugging gateway callbacks; never store card data, Zarinpal never sends any), `paid_at` (nullable), timestamps

## Quote requests (for `quote` products and general custom-manufacturing inquiries)

**`quote_requests`**
- `id`, `product_id` (nullable FK — nullable for a general "custom manufacturing" inquiry not tied to one catalog product)
- `name`, `phone`, `email` (nullable), `company` (nullable), `message` (text), `requested_quantity` (nullable string — quantity requests here are often descriptive, e.g. "پالت کامل", not a clean integer)
- `status` (enum: `new`, `contacted`, `quoted`, `closed`)
- `admin_notes` (nullable text), timestamps

## Content (blog, pages)

**`blog_categories`**: `id`, `name`, `slug` (unique), `description` (nullable), timestamps

**`blog_posts`**
- `id`, `blog_category_id` (nullable FK), `title`, `slug` (unique), `excerpt` (nullable), `body` (longtext)
- `author_id` (nullable FK → users), `status` (enum: `draft`, `published`), `published_at` (nullable)
- SEO: `meta_title`, `meta_description`, `canonical_url`
- Timestamps

**`tags`** / **`blog_post_tag`**: standard tagging pivot.

**`pages`**
- `id`, `title`, `slug` (unique), `body` (longtext), `template` (string, e.g. `default`, `about`, `contact`, `faq`)
- `status` (enum: `draft`, `published`), `published_at` (nullable)
- SEO: `meta_title`, `meta_description`, `canonical_url`
- Timestamps

## SEO / migration support

**`seo_redirects`**
- `id`, `from_path` (unique, string — path only, not full URL, so domain changes don't break it), `to_path`, `status_code` (smallint, 301 or 302), `hits_count` (unsignedInteger, default 0), `is_active` (bool, default true), timestamps

**`migration_mappings`**
- `id`, `source_system` (string, e.g. `wordpress`), `source_type` (e.g. `product`, `page`, `post`), `source_id` (the WP post ID), `target_type`, `target_id` (nullable until created), `source_url` (nullable), `target_url` (nullable), `status` (enum: `pending`, `imported`, `skipped`, `failed`), `notes` (nullable text), timestamps
- Enables idempotent re-running of import commands (look up by `source_system` + `source_type` + `source_id` before inserting) and the reconciliation report the master prompt requires.

## Site operations

**`contact_inquiries`**
- `id`, `name`, `phone` (nullable), `email` (nullable), `subject` (nullable), `message` (text), `source` (string, e.g. `contact_form`), `status` (enum: `new`, `read`, `replied`), `ip_address` (nullable), `user_agent` (nullable), timestamps

**`settings`**
- `id`, `key` (unique), `value` (text, nullable), `group` (string, nullable — e.g. `contact`, `social`, `seo`), timestamps
- Simple key-value store for site name, contact details, social links, default meta description, etc.

**`audit_logs`**
- `id`, `user_id` (nullable FK → users — the admin who acted), `action` (string, e.g. `product.updated`), `auditable_type`, `auditable_id`, `changes` (JSON, nullable), `ip_address` (nullable), timestamps

## Users (admin/staff, not storefront customers)

Laravel's default `users` table, extended with:
- `role` (enum: `admin`, `editor`, default `editor`) — minimum viable distinction between full administration and content-only access, per the master prompt. Expand to a full permissions package only if real multi-role needs emerge later — not pre-built speculatively.

## Deferred (not created in Phase 2, revisited in their own phase)

- **Torob feed logging** — Phase 8, once the feed itself exists.
- Anything bilingual/WPML-equivalent — explicitly out of scope per owner decision (2026-10-20).
