# Nafian Storefront Build Tracker

Source of truth UI: `docs/nafian_prototype/Nafian.dc.html` (+ `support.js`, screenshots).
Goal: replicate prototype UI **and** functionality on the existing Laravel 12 Blade backend.
Palette: primary `#691d2a`, dark `#4d141e`, bg `#fff2e3`, gold `#D4A853`, panel `#fff`, border `#EADBC4`. Font: DM Sans + Fira Code (mono). Currency shown as `$` (USD) to match prototype.

## State before this work
- Admin Phase 1–3 already built (migrations, models, enums, services, requests, seeders, factories, jobs, exceptions, admin controllers + views, components). Migrated.
- Storefront = 0%. Customer auth = 0%.

## Catalog (from prototype support.js) — fashion, NOT perfume
Categories: Bags, Outerwear, Apparel, Footwear, Accessories (home-featured: Bags, Apparel, Accessories).
10 products w/ tone gradients, colors, sizes, badge, rating, reviews, baseStock. p5 (Silk Twill Shirt) = out of stock; p4 (Bianca Loafers) = low stock (5).

---

## Task board

### A. Data layer realign (perfume → fashion) — DONE
- [x] A1 Migration: presentation cols (products tone/tone2/badge/rating/reviews_count; categories tone/tone2/show_on_home)
- [x] A2 Product / Category fillable + casts + helper methods (colorOptions/sizeOptions/findVariant/total_stock)
- [x] A3 `config/shop.php`: `colors` map, currency `$`, free-delivery threshold + charge
- [x] A4 CategorySeeder → 5 fashion categories w/ tones + show_on_home
- [x] A5 ProductSeeder → 10 prototype products, color×size variants (72), badge/rating/tones
- [x] A6 `migrate:fresh --seed` verified: 5 cats / 10 products / 72 variants / 4 featured / 3 home cats

### B. Storefront routing + controllers — DONE
- [x] B1 routes/web.php storefront group + customer auth (18 store.* routes)
- [x] B2 HomeController
- [x] B3 ShopController (PLP filters/sort/colours) + ProductController (PDP)
- [x] B4 CartController (add/update/remove, coupon apply/remove; /cart opens drawer)
- [x] B5 CheckoutController + CheckoutRequest → OrderService
- [x] B6 StoreOrderController (confirmation)
- [x] B7 AccountController (orders/detail/profile/addresses)

### C. Customer auth — DONE
- [x] C1 CustomerAuthController (login/register/logout, guard `web`)
- [x] C2 auth views (login/register)

### D. Views — DONE
- [x] D1 layouts/app.blade.php (header, mobile menu, footer, cart drawer, toasts, Alpine)
- [x] D2 home  · [x] D3 plp  · [x] D4 pdp  · [x] D5 checkout  · [x] D6 confirmation
- [x] D7 account (layout + orders/order-detail/profile/addresses)
- [x] D8 partials: header, footer, product-card, cart-drawer
- [x] D9 helpers: shop_price, color_hex, order_status_style, estimated_delivery

### E. Wiring / functionality — DONE
- [x] E1 Session cart + Alpine drawer + badge  · [x] E2 Coupon apply/remove
- [x] E3 Place order (guest + auth) → OrderService → confirmation
- [x] E4 PDP add-to-bag resolves colour+size → variant  · [x] E5 Account real orders
- [x] E6 Stock labels (in stock / low / sold out)

### F. Verify — DONE
- [x] F1 `npm run build` OK (76 kB css)
- [x] F2 Smoke: home/shop/category/pdp/login/register/admin-login all HTTP 200
- [x] F3 Feature tests: 7 passed / 23 assertions (StorefrontShoppingTest)

---

## G. Admin re-skin + Settings — DONE (2026-06-17)
- [x] G1 Admin shell to prototype: sidebar `#4d141e`, NAFIAN+Admin wordmark, user footer w/ role, topbar search, active states, "View storefront" link, Settings nav item
- [x] G2 `settings` table + `Setting` model + `SettingsService` (group defaults, secrets encrypted via Crypt, cached)
- [x] G3 `SettingsController` + 4 routes (index + general/pixels/courier PUT), activity-logged
- [x] G4 Settings view: 3 Alpine tabs — General (store profile + delivery), Marketing & Pixels (FB/GA4+GTM/TikTok w/ toggles), Courier (Steadfast + Pathao w/ sandbox toggle); reusable `toggle` + `pixel-card` partials
- [x] G5 Functional pixel injection: `storefront/partials/pixels.blade.php` emits FB/GA4/GTM/TikTok snippets only when enabled + ID set; included in app shell head
- [x] G6 Blank-secret-keeps-existing logic for courier creds
- [x] G7 Tests: AdminSettingsTest 6 pass (render, persist, secret-encryption-at-rest, blank-secret, pixel on/off). Full suite 50 pass.

## H. Admin order-detail + inventory re-skin — DONE (2026-06-17)
- [x] H1 Order detail → prototype: fulfilment stepper (done/current/upcoming/halted circles+lines), action bar (Mark-as-next primary, Cancel modal, Issue-refund), items+summary, customer/shipping/payment/timeline sidebar. Controller computes flow/primaryAction/cancellable/timeline; transitions stay valid via OrderService.
- [x] H2 Inventory → prototype: legend, grid (SKU+dot/Product/Variant/Stock/Reserved/Available/Updated), shared Alpine adjust modal (add/remove × qty → signed quantity_change, reason select + notes).
- [x] H3 `order_status_style` extended for payment states (paid/failed).
- [x] H4 Tests: order-detail render + status-advance added; full suite 52 pass.

## I. Admin products re-skin — DONE (2026-06-17)
- [x] I1 Products list → prototype grid: tone thumbnail, name+sku, category, variants, status pill, stock dot (green/amber/red), featured star toggle, Edit. Category + status filters auto-submit.
- [x] I2 `toggleFeatured` route + controller (PATCH products/{product}/featured), activity-logged.
- [x] I3 Product create/edit: prototype back-link + title + max-w-1080; form/variant card chrome aligned to `#EADBC4`. Variant/image/SEO logic unchanged (already functional).
- [x] I4 Tests: featured-toggle on/off added; full suite 53 pass.

## J. Admin customers + categories re-skin — DONE (2026-06-17)
- [x] J1 Customers → prototype table (avatar initial, email, phone, orders, spent, joined, status) + **SSR slide-over drawer** via `?view={id}` (spend/orders stat tiles, contact, order history links, Email + Suspend/Reactivate actions). Suspend posts to update → redirects back to open drawer.
- [x] J2 Categories → prototype **tone toggle-cards** (gradient header, item count, homepage switch, Edit/Delete). `toggleHome` route + controller (PATCH categories/{category}/home).
- [x] J3 Category form: added tone/tone2 colour pickers + show_on_home checkbox; validation extended.
- [x] J4 Tests: customer drawer render, suspend-from-drawer, category home toggle; full suite 56 pass.

## K. Admin coupons + reports re-skin — DONE (2026-06-17) → ADMIN RE-SKIN COMPLETE
- [x] K1 Coupons → prototype grid + **Alpine create/edit modal** (single modal, create vs edit via `editing` flag, edit prefilled from row JSON, `_method=PUT` for edit, reopens with old() on validation error). Replaces separate create/edit pages.
- [x] K2 Reports → prototype: quick range tabs (Today/7d/30d/Custom) + date pickers + Export, 4 stat cards, **3 Chart.js charts** (revenue line, orders-by-day bar, payment-method doughnut) rendered via `window.__report` in app.js; top-products grid.
- [x] K3 ReportController: range() honors ?range=, passes paymentSplit + activeRange.
- [x] K4 Tests: coupons modal render, reports quick-range render; full suite 58 pass.

**All admin screens now match prototype:** shell, dashboard, orders+detail, products+form, inventory, customers+drawer, coupons, categories, reports, activity log, settings.

## L. Storefront polish + cleanup — DONE (2026-06-17)
- [x] L1 Address book CRUD: AccountController store/update/destroy/default with ownership guards; addresses view add/edit Alpine modal + set-default + delete. Default-address uniqueness enforced.
- [x] L2 Guest order tracking: public /track form + POST lookup (order_number + email must both match; wrong email reveals nothing); track view w/ status flow + items. Confirmation "Track order" now routes guests to /track prefilled.
- [x] L3 Orphan cleanup: deleted customers/show + coupons/create+edit+_form views; create/edit/show controller actions now redirect to the drawer/modal index.
- [x] L4 Tests: address add+default, address ownership 403, guest track happy + wrong-email; full suite 61 pass.

## M. Tier 1 production-readiness — DONE (2026-06-17)
- [x] M1 Scheduler: `ExpireStockReservations` registered in `bootstrap/app.php` `->withSchedule()` everyMinute + withoutOverlapping. Releases stock from abandoned pending orders past reservation TTL.
- [x] M2 Real order emails: `App\Mail\OrderStatusMail` + `emails/order.blade.php` (branded HTML, created/cancelled/status variants, Track-order CTA). `SendOrderNotification` now mails user/guest email (was Log-only).
- [x] M3 RBAC enforcement: `UserRole::canManage(area)` (superadmin/admin=all, manager=orders+inventory+customers, viewer=read-only) + `EnsureAdminPermission` middleware on admin panel group (safe methods read-only-allowed, writes gated by role) + `@adminCan` Blade directive hiding Add buttons.
- [x] M4 Styled error pages: errors/layout + 403/404/419/500/503 (branded).
- [x] M5 Tests: RBAC (viewer no-delete, manager orders-yes coupons-no, admin all), 404 styled, email sent, reservation expiry. Full suite 67 pass.

### DEPLOY NOTES (required in production)
- Run the scheduler: cron `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` (drives reservation expiry).
- Run a queue worker (Supervisor): `php artisan queue:work` — `SendOrderNotification` + `ExpireStockReservations` are queued (`ShouldQueue`). `QUEUE_CONNECTION=database` already configured.
- Set real `MAIL_*` (currently `log`) so customers actually receive order emails.

## REMAINING (Tier 2/3)
- **Payment gateway** (provider deferred by user) — "Pay online" still records a pending payment with no capture.
- Customer password-reset + email verification (needs live mail).
- Courier Steadfast/Pathao consignment + "Test connection" (needs sandbox creds).
- Polish: real product images, storefront search modal, wishlist, newsletter persistence, SEO/OG/sitemap, error monitoring.
- Courier "Test connection" + real consignment creation = stubs (no live Steadfast/Pathao API calls yet).
- Storefront polish: real product images (gradients for now, matches prototype), search modal, wishlist, address add/edit CRUD (UI stub), newsletter persistence, guest order tracking.

## Notes / decisions
- Prototype is image-less (CSS gradient placeholders via tone/tone2). Replicating that — no Media Library images needed for v1.
- Variant = unique color×size (sku auto). PDP selection resolves to a variant id.
- Admin "Store/Admin switcher" pill = prototype-only gimmick; real app: storefront public, admin behind auth. Not replicating the pill.
