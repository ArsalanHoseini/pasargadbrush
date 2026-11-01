# Architectural & Business Decisions Log

Newest first. Each entry: decision, reasoning, alternatives considered, date.

---

## 2026-11-01 — Persian (Shamsi) dates site-wide; app timezone Asia/Tehran

- Every human-facing date (blog list/article/comments, all admin tables, the quote/order/review/conversation pages, footer year) now goes through one helper, `persian_date($date, PersianDate::DATE|DATETIME|COMPACT|COMPACT_DATE)`, built on intl's Persian calendar. Machine-facing dates stay Gregorian/ISO on purpose: RSS `pubDate`, sitemap `lastmod`, `<time datetime>` attributes, order numbers.
- **Timezone bug found and fixed:** `config/app.timezone` was UTC, but WordPress's imported `post_date` values (blog posts, products) are Tehran local time, and staff type the scheduled-publish time in local time — so once dates were shown in Tehran time, those would have read 3½ h late (and sometimes on the wrong day). The app now runs on `APP_TIMEZONE=Asia/Tehran` (default; env-overridable). Safe to switch now because production starts from fresh data; only local dev rows created earlier shift by 3½ h, which is harmless.
- The article form's publish-time picker is the browser's (Gregorian, can't be changed); a live «به تاریخ شمسی» line under it shows the same wall-clock time in the Persian calendar.

---

## 2026-11-01 — Customer notifications on order status change and final-price confirmation

- `NotifyOrderUpdated`, called from the admin order save: announces a status the customer cares about (confirmed / processing / shipped — with the parcel tracking code — / completed / cancelled; never "pending") and/or the final price being confirmed (grand total, or the items subtotal + "shipping announced separately" while shipping is unknown). Both in one save → **one** combined SMS (+ one email if the order has an address), linking to the tracking page. Uses the order's contact snapshot.
- An «اطلاع‌رسانی به مشتری» checkbox on the order page (ticked by default) lets staff skip it for minor corrections; a request without the field sends nothing, so other callers/tests aren't affected. When something was sent, the audit entry records `customer_notified`.
- Failure-isolated like every other notification: a broken SMS/mail channel is logged and never blocks saving.

---

## 2026-11-01 — SEO preservation: 301s for every old WordPress URL, slug-change redirects, sitemap.xml, robots.txt

**Why:** the owner flagged this as critical before go-live — the new site's URLs differ from WordPress's, so without redirects every indexed page and backlink would 404.

**Old URL shapes** (read from the local WP copy, never production): permalinks `/%postname%/`, WooCommerce `/product/{slug}/` and `/product-category/{slug}/`, WPML language as a query parameter (ar/en items have their own slugs on the same paths), images under `/wp-content/uploads/`.

**Design:**
- `seo_redirects` (existing table) + `source` (`wordpress` | `auto` | `manual`) + `notes`; `to_path` nullable for 410.
- `RedirectLegacyUrls` is a **global** middleware that only acts on a would-be 404 for GET/HEAD outside /admin — a real page always wins, normal traffic does no lookup. The map is cached as a plain array (lesson from `SettingCacheTest`); hits are counted with a plain query so the cache isn't flushed per hit. Matching normalizes percent-encoding, trailing slash, case and query string (so `?lang=ar` works), and falls back to the slug without a `/YYYY/MM/DD/` prefix and to the original of WordPress's resized images (`-300x200`).
- `php artisan wp:import-redirects [--commit] [--csv=…]` (`LegacyRedirectBuilder`): products via SKU `WP-{id}`, posts/categories/images via `migration_mappings`, ar/en items via their WPML Persian original, pages via an explicit table, Yoast Premium's own redirects (13, as 410) last. Fallbacks for content that isn't on the new site: a product → one of its categories → **a site search for its name** (generic «برس/فرچه/های» stripped); a newer category → its single product, else the category most of its products are in, else a search. Idempotent; never touches `manual`/`auto` rows; removes stale generated rows. Run locally: 573 redirects (14 × 410). Full list for review: `docs/migration/url-mapping.csv`.
- **Content gap found** (reported to the owner): 5 products and ~14 product categories exist on the live site but not on the new one (added to WordPress after the import) — they currently redirect to a search.
- `RecordsSlugRedirects` trait on Product, ProductCategory, BlogPost and Page: a slug change records old → new (source `auto`) and repoints existing redirects to the new path (no chains).
- `/admin/redirects`: list (search, filter by source, hit counts), add/edit/delete (301/302/410); an edited row becomes `manual`.
- `/sitemap.xml` (home, blog, active categories/products with gallery+schematic images, published posts/pages, tags with posts) and a dynamic `/robots.txt` (static `public/robots.txt` removed — it would have shadowed the route) disallowing admin/account/login/cart/checkout/order-tracking. Utility pages (search, cart, checkout, login, account, tracking) carry `noindex`.
- Persian 404/410 pages (standalone — no session/route context for unmatched URLs).
- Search improved, because redirects now land on it: per-word AND (falls back to OR), stop words ignored, Arabic «ي/ك» ≡ Persian «ی/ک».
- **Production:** `APP_URL` must be `https://pasargadbrush.com` (sitemap/robots use it), and `wp:import-redirects --commit` must be run on the server against the final WordPress data.

---

## 2026-11-01 — Product page redesign stage 2: key features, applications, tabs + reviews

- **Key features** (`product_highlights`): up to 4 per product, title + optional image (uploaded in the product form, shown as circles beside the main image; below it on mobile).
- **Applications** (`applications` + `application_product`): a shared, reusable list with icons managed at `/admin/applications` (icons: jpg/png/webp only — SVG refused, same XSS rule as the media library), picked per product. Chosen over per-product free text so «شستشوی فرش» etc. stay consistent across products.
- **Tabs**: توضیحات / مشخصات فنی / نظرات مشتریان (n) — accessible tablist (Alpine), every panel stays in the HTML for SEO.
- **Reviews** (`product_reviews`): optional 1–5 rating + text, honeypot + `throttle:5,1`, saved `pending` and shown only after approval at `/admin/product-reviews` (nav badge for pending) — same moderation flow as blog comments, but a separate table (different fields: rating, customer link). A review written while logged in links `customer_id`; if that customer has a non-cancelled order containing the product, it shows «خریدار این محصول» (computed in one query per page, not per review).
- Real layout bug found and fixed while checking in a browser: on mobile the page grid had no column template, so its single column grew to its content (137px horizontal scroll) — fixed with `grid-cols-1`/`min-w-0`, and the field rows were tightened for small screens.

---

## 2026-11-01 — Product page redesign: inline order card, several schematics, choice fields, quote products orderable (stage 1)

**Owner's request (with two mockups):** the order form («مشخصات و سفارش») on the product page itself, schematics shown beside the product image. Owner's answers: quote-only products **can** be added to the cart and ordered, priced afterwards by an expert; build choice fields (e.g. «جنس الیاف»), key-feature callouts, applications with icons, and tabs + customer reviews (the last three = stage 2); **don't** change the site header.

**Design (stage 1):**
- Product page: order column (title, short description, order card with variant chips / dimension rows «label · letter · input · unit» / choice chips / quantity / price or «قیمت پس از بررسی کارشناس اعلام می‌شود») and a media column (gallery + «نمای شماتیک (بدون مقیاس)» grid). One form, two buttons: «افزودن به سبد و ثبت سفارش» posts to the cart; «مشاوره و استعلام قیمت» reveals contact fields in the same card and posts the same dimensions/choices to `quote-requests.store` via `formaction` (the card's quantity becomes the requested quantity). The separate `/products/{slug}/customize` step is gone — the route now just redirects to the product page, so old links keep working.
- **Quote-only products are orderable:** `cart_items.product_id` added (always set) and `product_variant_id` made nullable. A variant-less line has no price; `PlaceOrder` stores it at 0 and flags the order `requires_price_confirmation`; it shows as «در انتظار تعیین قیمت» (`OrderItem::isAwaitingPrice()`) until staff set the price in the existing price-confirmation form. Availability for such lines = `Product::quoteOrderUnavailableReason()` (active, still quote-only, not out of stock). Reorder re-adds them (without the old agreed price).
- **Choice fields:** `product_dimension_fields.type` (`number`|`choice`) + `options` (json). Validated with `Rule::in(options)`; never digit-normalized; stored in the same snapshot (unit empty). Admin enters options one per line or comma-separated; the letter is optional for choices.
- **Several schematics:** moved from `products.schematic_media_id` to the `mediables` pivot with role `schematic` (existing data migrated, column dropped); admin uploads several at once (appended) and removes individually.

---

## 2026-11-01 — Reorder (stage C of the customer portal)

**Decision:** «سفارش مجدد» on a customer's order page puts its lines back in the cart **at today's prices** (the cart never stores prices, so this is automatic) through the same checks as a normal add-to-cart (`App\Services\Cart\Reorder`): the variant must still be orderable online and in stock (per-variant totals), and a custom-dimension line's old dimensions are mapped by letter onto the product's *current* form and re-validated with `DimensionForm` — if the form changed so they no longer fit, the line is skipped and the customer is asked to re-enter them on the product page. Lines without a variant (quote-only products from staff-entered orders), deactivated or out-of-stock products are skipped too; every skipped line is listed with its reason on the cart page. Plain lines merge with what's already in the cart; customized lines stay separate.

---

## 2026-11-01 — Staff ↔ customer messaging with attachments (stage B of the customer portal)

**Design:**
- One system for both kinds the owner asked for: `conversations` linked to an order **or** a quote request (unique — at most one thread each, reused if it exists) **or** neither (a general ticket). `conversation_messages` (author `customer` | `staff` + `user_id`) and `message_attachments`.
- Status = whose turn it is: a customer message → `open` («در انتظار پاسخ» for staff, badge in the admin nav); a staff message → `answered`; staff can close/reopen; a customer writing in a closed thread reopens it. Per-side unread flags (`customer_unread` / `staff_unread`) drive the dashboard badges and are cleared on view.
- **Attachments:** images (jpg/png/webp) and PDF only (SVG/others refused), ≤5 per message, ≤10 MB each, content-type checked by Laravel's `mimes` rule. Stored on the **private** `local` disk under random names (original name kept as metadata only) and served only through authorizing controllers (`account.conversations.attachment` checks the customer owns the thread; the admin route is behind staff auth) with `X-Content-Type-Options: nosniff`. Files are deleted if the DB write fails.
- **Notifications** (`ConversationNotifier`, each channel failure-isolated): customer message → email to the business; staff message → SMS to the customer with a direct link (+ email if they have one). The business is not SMSed per message (noise) — the admin nav badge and email cover it; easy to add.
- Staff can start a thread from an order, a quote request, or a customer page. For a guest quote request with a valid mobile, the customer record is created only when the message is actually sent (not when the form is opened), so the customer sees it after their first SMS login; a landline-only request can't be messaged (redirect with an explanation).

**Not yet:** realtime updates (pages are plain request/response; customers are notified by SMS/email instead).

---

## 2026-11-01 — Customer accounts & dashboard (stage A of the customer-portal work)

**Owner's answers (2026-11-01):** login with **both** SMS code and (optional) password; staff↔customer communication as **per-order/quote conversations + general tickets** (stage B, not built yet); dashboard sections: orders, quote requests, profile & addresses, **file attachments in messages** (stage B), **reorder** (stage C).

**Design (stage A):**
- Separate `customer` auth guard (session, `customers` provider); staff keep the `web` guard. Customer logout only logs out that guard, so staff signed in in the same browser stay signed in. Guests are redirected to `account.login` (or `admin.login` under `/admin`).
- Identity = the mobile number, exactly as checkout already uses. SMS-code login (`LoginCodeManager`) creates the account on first use and sets `phone_verified_at`; because guest orders were always attached to the customer with that phone, **past guest orders appear in the account automatically**, and earlier guest quote requests with the same (now canonicalized) mobile are listed too. The code is 5 digits, stored hashed, valid 2 min, 5 guesses, only the newest code valid, resend ≥60 s apart, ≤5 codes/hour per number (SMS costs money and can be used to harass a stranger's phone), plus route throttles.
- Password is optional, set from the dashboard without asking for the old one — the SMS login *is* the reset path, so no email/SMTP dependency. Until an SMS provider is bought, SMS login only works locally (the code is written to the log); password login works everywhere but needs a first SMS login to set the password.
- Address book vs order addresses: `addresses.is_saved` — orders always point at their own frozen copy (`is_saved = false`), address-book rows are freely editable/deletable. Checkout for a logged-in customer forces the account's phone (so the order lands in their account), offers saved addresses, and can save a newly typed address to the book.
- Logged-in quote requests are attached (`quote_requests.customer_id`) and pre-filled; guest quote phones that are valid mobiles are now stored canonically.
- The order-details block was extracted from the tracking page into `orders/partials/details` and is reused by the dashboard's order page.

**Not yet:** changing the account's mobile number (needs an SMS code to the new number); stage B (messaging) and C (reorder).

---

## 2026-11-01 — Staff-entered (manual) orders, incl. from a quote request — customization stage 2

**Why:** owner's flow for quote-only products — the customer sends a quote request (with dimensions); an expert agrees the price, then registers the order on the site by hand. Also covers phone orders.

**Design:**
- `/admin/orders/create` («+ ثبت سفارش دستی» on the orders list), and `?quote_request={id}` from the quote request page («ثبت سفارش از این درخواست»), which pre-fills customer, product, the request's dimensions (matched to the product's current fields **by letter** — `DimensionForm::valuesFromSnapshot()`), the first number found in the free-text requested quantity, and an internal note referencing the request.
- Lines: any product (quote-only and inactive included — staff decide), optional variant, quantity, unit price set by staff, optional line title, and the product's dimension fields, validated with the same `DimensionForm` rules customers face (now prefix-aware, errors read «ردیف ۱ — قطر خارجی (E) ...»).
- `CreateManualOrder`: same no-overwrite customer rule and contact snapshot as checkout; prices are final (`requires_price_confirmation = false`); **tracked stock is enforced and deducted** (under a row lock) because cancelling later returns it via `OrderStock` — skipping it would inflate stock; product availability is *not* enforced. Shipping method/cost chosen per order (COD never charges). The quote request gets `order_id` + status `closed`; a request can only become one order. Audit-logged as `order.created_manually`.
- `orders.source` (`website`/`manual`) + `created_by_user_id`; «ثبت دستی» badge in the list, «ثبت دستی توسط …» on the order page.
- Notifications: the business isn't notified of its own entry; the customer gets the normal confirmation email/SMS (with the order number for tracking) only if staff tick the box (default on). `NotifyOrderPlaced` split into `notifyBusiness()` / `notifyCustomer()`.
- Order number generation moved to `Order::generateOrderNumber()` (shared by both paths).

---

## 2026-11-01 — Per-product dimensions form with a schematic (customization), stage 1

**Owner's request:** every product can have its own schematic drawing and a set of dimensions (A, B, O.H., …) the customer must give exactly; defined per product in the dashboard; for purchasable products the form appears on adding to the cart.

**Owner's answers (2026-11-01):**
- Dimensions **don't** change the price automatically: the line is priced at the variant's base price, the order is flagged `requires_price_confirmation`, and staff confirm/adjust the final unit prices in the admin order page.
- Quote-only products get the same form inside the quote request; staff then register the order on the site themselves → **stage 2** (manual order entry, incl. from a quote request) — not built yet.
- Each product is defined **fully on its own** (no shared shape templates — the alternative was offered and declined).

**Design:**
- `product_dimension_fields` (code = the letter on the drawing, Persian label, unit mm/cm/m/inch, required, optional min/max, order) + `products.schematic_media_id`, uploaded straight from the product form via `StoreUploadedMedia` (SVG excluded, as in the media library). No fields = no form; the product behaves exactly as before.
- `App\Services\Customization\DimensionForm` is the single place for the form's normalization (Persian digits, «٫»), validation (required/numeric/>0/min/max, Persian field names like «قطر خارجی (D)»), and the **snapshot** `{dimensions: [{code,label,unit,value}], notes}` stored on `cart_items`, `order_items` and `quote_requests`. Snapshots freeze labels/units, so later edits to a product's form never alter what a customer submitted.
- Flow: «افزودن به سبد» on such a product goes to `/products/{slug}/customize?variant=&quantity=` (schematic + fields + notes) and only that form adds to the cart — the add-to-cart endpoint itself re-validates, so it can't be bypassed. A customized line is always its own cart line (same variant, two sizes = two lines), so the `(cart_id, product_variant_id)` unique index was dropped (cart_id indexed first for the MySQL FK). Stock is now checked per variant across all its lines.
- Every customer-facing total shows a "base price, final price confirmed by an expert" note when customized lines are present. Admin order page: per-line unit price inputs + «قیمت نهایی تأیید شد» while flagged (line totals, subtotal and total follow; audited as `item_prices_rial`); not editable once confirmed. Order list shows a «نیاز به تأیید قیمت» badge.
- **Not yet:** notifying the customer when the final price is confirmed (no status-change notifications exist yet).

---

## 2026-11-01 — Shipping is configured by staff in the dashboard (/admin/shipping)

**Owner's decision:** rather than choosing a shipping rule now, make it configurable from the dashboard. Built a dedicated page (the generic `/admin/settings` page only does free-text fields) with three methods (`App\Services\Checkout\ShippingMethod`):
- **Announced** (default, = the previous behavior): no amount at checkout; «پس از هماهنگی اعلام می‌شود». Staff then enter the agreed amount on the admin order page («هزینه ارسال (تومان)») and the order total is recalculated.
- **CashOnDelivery** (پس‌کرایه): the shop charges 0, shown as «پس‌کرایه — هنگام تحویل به باربری پرداخت می‌شود»; the per-order shipping field is ignored for these orders.
- **Flat**: a fixed amount, optionally free at/above a subtotal threshold.

Amounts entered in Toman, stored in Rial in `settings` (`shipping_method`, `shipping_flat_rate_rial`, `shipping_free_threshold_rial`). `ShippingCalculator` applies them; `PlaceOrder` stores the method on the order (`orders.shipping_method`) along with the cost, and `Order::shippingQuote()` renders every order from its own stored method — so changing the settings never changes how an existing order reads. One partial (`orders/partials/totals`) renders cart, checkout, confirmation and tracking totals; it only shows «جمع کل» when the shipping cost is actually known.

**Not built (can be added on request):** per-province rates (would also need the province field to become a fixed list), factory pickup, carrier-calculated rates. Under Announced, an order explicitly agreed as free shipping (0) can't be distinguished from "not entered yet"; acceptable for now.

---

## 2026-10-31 — Shipping rule still undecided; customer-facing totals no longer imply shipping is included

**Owner asked (2026-10-31) for the shipping rule:** cash-on-delivery (پس‌کرایه), flat rate, per-province, or announced by phone — plus optional factory pickup / free-over-threshold. **Answer: not decided yet.** The old WooCommerce site had no shipping zones/methods configured either, so there's nothing to carry over. Still no rule invented; `shipping_cost_rial` stays 0 at checkout.

**What changed instead (wording only):** the cart, checkout page, confirmation page, order emails and tracking page labeled the items sum «جمع کل» (grand total) with no mention of shipping — misleading when delivery is charged separately. They now show «جمع کالاها» plus «هزینه ارسال: پس از هماهنگی اعلام می‌شود». Order-based views (`orders/partials/totals`, the email summary) automatically switch to subtotal + shipping + «جمع کل» once an order's `shipping_cost_rial` is > 0, so they stay correct whenever a shipping rule — or a staff-entered shipping amount — is added.

---

## 2026-10-31 — Guest order tracking by order number + mobile; Persian (Shamsi) dates via intl

**Decision:** customers have no accounts (guest checkout), so tracking is a lookup at `/order-tracking`: order number + the mobile the order was placed with, both must match. Inputs are normalized (Persian digits, lowercase, spaces; any mobile spelling via `IranianMobile`). The lookup is throttled (`throttle:10,1`) and a miss gives one generic message that doesn't reveal which field was wrong, so order numbers can't be enumerated. The found order id goes into the session and the status page (`/order-tracking/status`) reads it from there — same pattern as the checkout confirmation page — so order details never sit in a URL.

**Alternatives considered:** customer accounts / OTP login — needs the SMS provider (not chosen yet) and is much more scope; a secret per-order link — would put a bearer token in SMS/email/browser history. Either can be added later on top of this.

**Also added:** `orders.shipment_tracking_code` (staff enter the carrier's parcel code in the admin order page; shown to the customer once set). Order status/payment labels moved to `Order::STATUS_LABELS` / `PAYMENT_STATUS_LABELS` (they were duplicated across admin views).

**Persian dates:** `App\Support\PersianDate` formats via PHP's built-in `intl` (`fa_IR@calendar=persian`, Asia/Tehran) — no package added. Used on the tracking page only so far; the rest of the site still shows Gregorian `Y-m-d` and can be switched over separately. **Production hosting must have the `intl` PHP extension enabled** (standard on cPanel PHP builds, but verify).

---

## 2026-10-31 — Products are re-checked at the moment of ordering; stock is actually enforced

**Problem:** availability was only checked when adding to the cart. A product deactivated or switched to quote-only afterwards was still ordered; `stock_quantity` and the product-level `stock_status` existed but nothing ever read them; a priced variant could be saved at price 0; repeated "add to cart" could push a line past the 1000 cap.

**Decisions:**
- One rule, `ProductVariant::unavailableReason(int $quantity)`, returns a Persian customer-facing reason (inactive / not `priced` / `stock_status=out_of_stock` / stock 0 / price ≤ 0 / quantity > stock) or null. The product page, cart, checkout page and `PlaceOrder` all call it, so they can't disagree. `stock_quantity = null` still means "stock not tracked" — all 10 current variants are null, so nothing changes for them until the owner enters stock.
- `PlaceOrder` re-reads the cart's variants with `lockForUpdate()` inside the order transaction and re-checks them before writing anything; two customers buying the last units simultaneously can't both succeed. On failure it throws `CartNotOrderable` (nothing written, cart kept) and the customer is sent back to the cart, where each affected line shows its reason and the checkout button is replaced by an instruction until they fix it. Tracked stock is deducted in the same transaction.
- **Cart keeps unavailable lines rather than silently deleting them** — the customer sees what changed. Lowering/removing a line is always allowed; only increases are checked.
- **Admin status changes:** cancelling an order returns its tracked stock; un-cancelling takes it again and is refused (with the item names) if the stock is no longer there (`OrderStock`). Moves between non-cancelled statuses don't touch stock.
- Admin product form: a `priced` product's variant price must be ≥ 1 Toman; `quote` products may still carry 0 (their variant prices are never shown).

---

## 2026-10-31 — One canonical mobile format; checkout never overwrites an existing customer

**Problem (found in the gap review, owner-approved fix):** checkout matched customers by the raw phone string and then `updateOrCreate`d them. With no phone verification (no OTP — no SMS provider yet), anyone typing someone else's number silently rewrote that customer's name/email and had their order filed (and texted) under them. And the same person typing `09121234567`, `۰۹۱۲۱۲۳۴۵۶۷`, or `+98 912 123 4567` became three different customers.

**Decisions:**
- `App\Support\IranianMobile::normalize()` reduces Persian/Arabic digits, spaces/dashes/parentheses/zero-width chars, and `+98`/`0098`/`98`/missing-leading-zero to the one stored form `09XXXXXXXXX`. Applied in `StoreCheckoutRequest::prepareForValidation()`, then validated against `IranianMobile::PATTERN` — checkout now requires a valid Iranian mobile (SMS needs one anyway). Landlines are rejected there.
- `PlaceOrder` uses `firstOrCreate`: an existing customer is matched by phone, never updated.
- What was actually typed is snapshotted onto the order itself (new nullable `orders.customer_name/customer_phone/customer_email`), the same way prices are snapshotted onto `order_items`. Admin order screens, order emails, and the customer confirmation email/SMS all read the snapshot (with fallback to the customer record for any order without one).
- The admin order page shows a warning when the snapshot name differs from the customer record for that phone, so staff can confirm by phone before shipping.

**Deliberately not changed:** the quote-request form's phone field — B2B leads often give a landline, so requiring a mobile there would lose real leads. Real ownership verification (OTP) waits for the SMS provider.

---

## 2026-10-31 — Persian validation messages; order notifications by email + SMS (SMS provider still open)

**Bug found during a full-project gap review:** the app had no `lang/fa` files at all. Since both `APP_LOCALE` and `APP_FALLBACK_LOCALE` are `fa`, every validation error on every public and admin form rendered as its raw key (e.g. `validation.required`). Fixed by adding `lang/fa/{validation,auth,pagination,passwords}.php` + `lang/fa.json` (pagination view strings), with Persian labels for every field name the app validates. Hand-written rather than adding a translation package (no dependency change without approval).

**Order notifications (owner-confirmed 2026-10-31: email + SMS):** `App\Services\Checkout\NotifyOrderPlaced`, called right after `PlaceOrder` commits. Sends:
- email to the business (`setting('contact_email')`) — `NewOrderReceived`, with a link to the admin order page;
- SMS to the business (`setting('contact_mobile')`);
- email to the customer, only if they entered one — `OrderPlacedConfirmation`;
- SMS to the customer's phone.

Each channel has its own try/catch + `Log::warning` (same resilience rule as quote-request emails): a broken mail transport or SMS provider must never lose an order that is already saved. Sent synchronously, not queued — matches the existing quote-request mail and avoids a hard dependency on a queue worker on the target hosting.

**SMS provider deliberately left open:** owner hasn't chosen/purchased one yet. All SMS goes through the `App\Services\Sms\SmsSender` interface; the only implementation today is `LogSmsSender` (writes to the log, sends nothing), selected by `SMS_DRIVER=log` (`config/services.php` → `services.sms.driver`, bound in `AppServiceProvider`). Adding the real provider = one new `SmsSender` class + one `match` arm in the binding + its credentials in `.env`. Nothing else changes.

**Still not built (separate decisions):** notifications on order *status* changes (e.g. "shipped"), and real SMTP credentials for production (`MAIL_MAILER=log` locally).

---

## 2026-10-24 — Homepage products are now an owner-curated list, not "latest 8"

**Decision:** Owner gave a specific ordered list of 18 product names to show on the homepage. Rather than hardcoding a list of IDs in the view/controller, used the `is_featured` + `sort_order` columns that already existed on `products` (migrated in Phase 2, model-fillable, even already half-wired into the admin form's `is_featured` checkbox) but were never actually used anywhere — `HomeController` used to just show the 8 most-recently-published active products. Now it queries `is_active && is_featured`, ordered by `sort_order`, capped at 24 as a sane safety limit (today: exactly 16).

**Added the missing piece:** a `sort_order` number input in the admin product form (`sort_order` was already a real column/fillable field with zero UI to edit it) — so the owner can reorder or swap which products are featured later without needing a code change or a request to me. Relabeled the existing "محصول ویژه" checkbox to "محصول ویژه (نمایش در صفحه اصلی)" since it now actually does something.

**Matching the owner's 18 names to real SKUs — 3 were ambiguous, asked rather than guessed (see `App\Console\Commands\SetHomepageFeaturedProducts`):**
- "دستگاه برس چمن مصنوعی" has no matching product; owner confirmed it means the existing "برس چمن مصنوعی" (no new/duplicate entry).
- "فرچه سورتینگ" was ambiguous between two real products ("برس سورتینگ خرما" and "برس سورتینگ مرکبات"); owner picked صرفاً مرکبات.
- "فرچه قالیشویی" is not a separate product at all — per the 2026-10-22 price-list decision, it's already modeled as a second *variant* on "برس قالیشویی", not its own row; owner confirmed that's sufficient.
- Net result: 18 requested names → 16 distinct real products (two names map to products already covered by another name on the list).

**Idempotent, like `ApplyOwnerPriceList`:** `php artisan wp:set-homepage-featured-products --commit` sets `is_featured=true`/`sort_order` for exactly these 16 SKUs (by SKU, not name, so re-running is safe) and un-features anything previously featured but no longer on the list. Dry-run by default.

---

## 2026-10-24 — Removed the "دسته‌بندی محصولات" (categories) grid section from the homepage

**Decision:** Owner asked to remove this section entirely. The hero's and SEO block's primary CTAs, which used to scroll to `#categories`, now scroll to `#products` (the Featured Products grid, given an `id` for this) instead. `HomeController` no longer queries `ProductCategory` at all (it was only used by this section) — one less query per homepage load.

**Noticed but not acted on without asking:** the removed section's data (visible in the owner's own screenshot) included two junk category rows with 0 products each — "بدون دسته‌بندی" (uncategorized) and "فرشاة الصناعی" (Arabic text, likely a WordPress/WPML import artifact). These are a real data-quality issue independent of this removal; worth asking the owner whether to clean up or deactivate, since they'd still show up anywhere else categories are listed (e.g. the header's products dropdown).

---

## 2026-10-24 — Homepage polish pass: cleaner hero collage, SEO copy for "برس پاسارگاد" / "خرید برس" / "خرید فرچه"

**Why:** Owner's first pass feedback — the hero still didn't feel professional/responsive enough, and asked for SEO-targeted copy around the brand name and the two core purchase-intent phrases.

**Visual changes:** Replaced the uneven 4-tile mosaic (irregular negative margins, risk of odd clipping on narrow screens) with a simpler, genuinely responsive 2-column flex collage (one large + two stacked images, percentage-based, degrades to a single image if fewer than 3 real product photos exist, hides entirely if none do). Added a soft accent-colored radial glow behind the hero text, a two-line headline treatment (brand name on its own accent-colored line), an arrow micro-interaction on the primary CTA, and made the diagonal section divider responsive (`h-8 sm:h-12`) instead of a fixed height that was proportionally too heavy on small screens.

**SEO copy — all built from facts already sourced for the About Us page, nothing new invented:** `<title>`/meta description rewritten to include "خرید برس" / "خرید فرچه" / "برس پاسارگاد" naturally; the H1 now leads with "برس پاسارگاد"; the hero paragraph and primary CTA button both use "خرید برس" / "خرید فرچه" as real, clickable anchor text (not just invisible meta stuffing); added one short, genuine on-page paragraph block (with its own CTA, not just filler) repeating the founding year/company name naturally. Verified by curl that these phrases appear a handful of times each in the rendered body — natural density, not keyword-stuffed.

---

## 2026-10-24 — Homepage redesign: "industrial-premium" direction, GSAP wired in for the first time

**Scope (owner-confirmed):** homepage only for this pass — category/product/blog listing pages, cart/checkout, and the admin panel are untouched and still use the original plain styling. Visual direction chosen by the owner from three concrete options: **industrial-premium** (dark charcoal hero, bold typography, real product photography) over "editorial-minimal" and "technical/spec-sheet". Animation level: "moderate and professional" — subtle scroll-reveals and a counter animation, not showy/heavy motion.

**What changed:**
- New dark hero (`--color-brand-charcoal-deep`, a faint dot-grid texture via `.bg-grid-industrial`, a diagonal `clip-path` divider into the next section) with a real-product-photo mosaic (reuses images already loaded by `HomeController` — no new asset pipeline).
- A stats strip using **real, already-sourced facts** from the owner-provided About Us content (`database/seeders/PagesSeeder.php`): founded ۱۳۷۴, ۱۱۰+ product varieties, ۱۰۰۰+ successful contracts, ۹۷٪ quality guarantee. Nothing here is invented — same numbers already live on `/page/about-us`.
- Category/product/blog cards upgraded (lift-on-hover, accent top-border reveal, consistent shadow) without changing the underlying grid/data.
- **GSAP is now actually used** (it was an installed-but-dead dependency since Phase 1): a small lazy-loaded scroll-reveal (`[data-animate]`) and count-up (`[data-counter]`) system in `resources/js/app.js`, imported dynamically so it doesn't block first paint and is skipped entirely under `prefers-reduced-motion: reduce`. Confirmed via `npm run build` that `gsap`/`ScrollTrigger` land in their own chunks, same lazy pattern already used for Quill.

**Verification caveat — be transparent about this:** no in-session browser/screenshot tool was available, so this was verified via `php artisan serve` + `curl` (HTTP 200, expected markup present, no server errors in the log) and the full test suite (118/118), **not** by actually looking at the rendered page in a browser. A real visual check by a human is still needed before calling this "done" — per the project's own UI-verification rule.

---

## 2026-10-22 — Blog module upgraded to feature parity; first "dashboard features, one by one" increment

**Context:** Owner asked to start adding admin dashboard features incrementally, starting with rebuilding the blog module (referencing `D:\Utab\blog-module-sample`, a Next.js/Prisma project, for *what a complete blog has* — not for code, since the stack is unrelated). A follow-up landing-page builder (referencing `D:\Utab\LMS\lead-automation`, which **is** Laravel but uses a full DDD module architecture — Domain/Application/Infrastructure/Presentation layers with Livewire) is noted as a later increment; that architecture style doesn't match this project's plain MVC/Service conventions and won't be copied wholesale if/when built.

**What shipped:**
- Rich text editor for post bodies (`<x-rich-text-editor>` + `resources/js/app.js`'s `richTextEditor` Alpine component), replacing the plain `<textarea>`. **Library: Quill.js** (owner's explicit choice over TipTap/Trix) — lazy dynamic-imported so it never bloats the public-site JS bundle (confirmed via `npm run build`: it lands in its own ~200KB chunk, loaded only when the admin editor mounts).
- Tags: the `tags`/`blog_post_tag` tables existed since Phase 2 but were never wired up. Admin form takes a comma-or-«،»-separated text input (no new JS tag-picker dependency); public post page links to a new tag-archive route (`/blog/tag/{tag:slug}`).
- `featured` (bool): shown as a separate hero block above the regular grid on `/blog`, excluded from the grid itself.
- `reading_time`: auto-computed on every save from word count (not an admin input) — 150 words/minute estimate. Backfilled for all 42 pre-existing imported posts via `BlogPost::all()->each->save()` (one-off tinker command, not a persisted Artisan command, since it's a single migration-time maintenance action, not a repeatable business operation).
- `view_count`: incremented on each public post view.
- `focus_keyword`: plain SEO-reference text field, stored only (matches the reference schema's own purpose — it doesn't drive any algorithm here either).
- **Scheduled publishing implemented without a new status or a cron job:** exposed `published_at` as an editable datetime field; public queries now require `status = published AND published_at <= now()` (`BlogPost::scopePublished()`). Setting a future `published_at` on a "published" post makes it invisible until that moment, then visible automatically on the next page load — no scheduler/queue needed. This is a deliberate simplification vs. the reference project's separate `SCHEDULED` enum value + polling cron.
- RSS feed at `/blog/feed.xml` (plain Blade-rendered XML, no new dependency).
- **Comments, with moderation — owner's explicit choice** (over "no comments at all", given spam/support burden on a B2B industrial site): flat (no reply-threading) `comments` table, `status` enum `pending/approved/spam`, default `pending`. Public submission reuses the exact honeypot pattern already established for quote requests (`website` field, `prohibited` rule, CSS-hidden not `aria-hidden`) plus `throttle:5,1`. Admin moderation UI at `/admin/comments` mirrors the existing quote-requests status-tabs pattern, with a pending-count badge in the nav (same pattern as the quote-requests badge).

**Not done, deliberately out of scope for this increment:** reply-threading on comments (the reference schema supports nested replies; flat is enough for a B2B site and avoids speculative complexity — YAGNI). Pages' (`/admin/pages`) editor still uses a plain textarea — the owner's request named the blog specifically; applying the same `<x-rich-text-editor>` component there is a trivial, low-risk follow-up but wasn't done without being asked, per "don't scope-creep beyond what was requested."

**Tests:** `tests/Feature/BlogFeaturesTest.php`, `tests/Feature/BlogCommentTest.php`, `tests/Feature/Admin/CommentModerationTest.php` — 118/118 suite passing after this change, no regressions.

---

## 2026-10-22 — Real price list applied; fresher live DB/uploads supersede the old local copy

**What the owner provided:** a fresh SQL dump of the real production database (`docs/h421876_pasargadbrush.sql`, generated 2026-10-22 — matches the real cPanel account name), a real company price list (`docs/لیست قیمت(1).pdf`), and a more complete/current `wp-content/uploads` folder (393MB, through 2026/09 — vs. the older local Duplicator copy's 254MB through 2023/24). All three are gitignored (`/docs/*.sql`, `/docs/uploads/`, `/docs/*.pdf`) — never committed.

**Source switched:** `WP_DB_DATABASE` now points at `pasargadbrush_live` (the freshly imported dump) and `WP_UPLOADS_PATH` at the new uploads folder, superseding the old local Duplicator copy used since Phase 0. The old copy is untouched, just no longer the active source.

**Price list applied via `php artisan wp:apply-owner-price-list --commit`** (idempotent, re-runnable — the actual mapping lives in code, not this doc, so it stays in sync): 7 existing products converted from `quote` to `priced` with real variant(s) from the PDF (one, "برس قالیشویی", got two variants — two real sizes on the sheet). Every mapping ambiguity was put to the owner before running, not guessed:
- Two near-duplicate "برس مبل شو" products existed (same description, WP slugs differing by `-2`) — owner said deactivate one, price the other. Deactivated `WP-25072`, priced `WP-25110`.
- "فرچه قالیشویی" (قطر ۳۸۰میلیمتر) vs "برس قالیشویی" (سیلندری ۹۰سانتیمتر) — owner confirmed these are two real sizes of the same product, not two products. Modeled as two variants on one product.
- "برس اسب" and "برس اسپیندل" (on the price list but not in our catalog at all) — owner said create them now from the price list alone.

**Real bug found while doing this, not fabricated around:** those last two products *do* exist on the live site (added Sept 2026, full real SEO content + real images) but have **zero rows in `wp_icl_translations`** — WPML never registered them, so the normal fa-language-filtered importer structurally cannot see them (confirmed: even their own `product_cat` terms are mis-registered as `element_type='comment'`). Rather than placeholder-creating these two products from price-list text alone, or hacking the general importer's WPML filter (which is correct and tested for the other 40), they're imported directly by known WordPress post ID inside `ApplyOwnerPriceList`, reusing the same `MediaCopier` the real importer uses — so they ended up with their actual article-length descriptions and real photos, not placeholders.

**Found but not acted on without asking:** comparing our 40 originally-imported products against this fresh dump, two (`WP-25040` برس صنایع نساجی, `WP-25127` برس آبرنگ) no longer exist on the live site at all (hard-deleted, not just unpublished) — still active on our site. Asked the owner whether to deactivate these to match (see chat) rather than silently removing real catalog entries.

---

## 2026-10-21 — Cart: cookie-based, lazy-created, never encrypted

**Decision:** Guest cart identity is a long-lived (`cart_token`) cookie, independent of login/session — resolved via `App\Services\Cart\CartResolver`. The cookie is excluded from Laravel's cookie encryption (`bootstrap/app.php`: `encryptCookies(except: ['cart_token'])`) since it holds no PII, just a random identifier. A `Cart` row is only ever created when something is actually added (`resolveForWrite`); merely browsing with no cart (`resolveForDisplay`) never touches the database.

**Real bug this surfaced, caught while writing tests (not by inspection):** Laravel's test client's `withCookie()` silently **encrypts** the value before sending, to simulate a real browser round-trip through `EncryptCookies` — this has nothing to do with the app's own `except` list, which only governs real HTTP traffic. Using `withCookie()` in tests for an intentionally-unencrypted cookie made every test see an empty cart, because the server tried to match a raw token against an encrypted blob. Fixed by using `withUnencryptedCookie()` instead, which exists specifically for this. Worth remembering for any future unencrypted-cookie feature.

---

## 2026-10-21 — No online payment yet; orders are "pending, we'll call you"

**Decision:** Checkout creates a real `Order` (status `pending`, `payment_status` `unpaid`) and tells the customer "همکاران ما ... برای هماهنگی پرداخت و ارسال با شما تماس می‌گیرند" (our team will contact you to arrange payment and shipping). No Zarinpal (or any) payment gateway API call is made.

**Why:** The owner wants Zarinpal eventually but we have no real merchant credentials — inventing an API integration without them would mean either fabricating credentials (never do this) or shipping a payment flow that can't actually charge anyone. The `payments` table/`Payment` model already exist and are ready for a real gateway service to be added later without reworking checkout; `docs/TOROB_INTEGRATION.md`-style "ready but not activated" pattern.

**Shipping cost is always 0** for the same class of reason: no real shipping-rate rule (flat? free over a threshold? carrier-calculated?) has been confirmed. Don't invent one — `PlaceOrder::handle()` has a comment pointing back here.

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
