# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Nafian — a Laravel 12 fashion e-commerce app (BDT currency, Bangladesh courier/COD context) with a public Blade + Alpine.js storefront and a separate admin panel (orders, inventory, POS, expenses, reports, settings). Planning docs live in `docs/` (`PROJECT_BLUEPRINT.md` for the original design, `BUILD_TRACKER.md` for what's done, remaining work, and deploy notes). Local dev runs under XAMPP with MySQL (`nafian_ecommerce`).

## Commands

- Dev (server + queue listener + Vite): `composer run dev`
- Build assets: `npm run build`
- Tests (SQLite in-memory, sync queue, array mail — see `phpunit.xml`): `php artisan test --compact`, single test: `php artisan test --compact --filter=test_name`
- Format: `vendor/bin/pint --dirty --format agent`
- Seed demo data (admins, attributes, categories, products, coupons): `php artisan migrate:fresh --seed`

## Architecture

- **Two auth guards**: `web` (customers, `User`) and `admin` (`Admin` model, separate table). Admin routes use the `admin.auth` + `admin.perm` middleware aliases registered in `bootstrap/app.php`. Tests authenticate with `actingAs($admin, 'admin')`.
- **RBAC is enum-based, not spatie/permission**: `UserRole::canManage($area)` decides write access. `EnsureAdminPermission` allows all GET/HEAD requests and gets the area for write requests from the route name segment (`admin.<area>.<action>`). The route name therefore controls authorization, so new admin routes must follow that naming. `variants` counts as `products`. The `@adminCan('area')` Blade directive (in `AppServiceProvider`) only hides UI.
- **Business logic lives in `app/Services`**. Controllers stay thin and delegate to the services. Services wrap writes in `DB::transaction` and lock variant rows with `lockForUpdate()`.
- **Inventory model**: stock is tracked per `ProductVariant` (unique color×size) as `stock_quantity` + `reserved_quantity`. `InventoryService` is the only thing that should change these. Every change writes an `InventoryTransaction`.
  - Checkout → `OrderService::createOrder` snapshots prices into `order_items`, then reserves stock (`StockReservation`, TTL from `config('shop.reservation_ttl_minutes')`). An `InsufficientStockException` rolls back the whole order.
  - Status transitions are restricted by `OrderStatus::nextStatuses()`. Moving to `Delivered` converts reservations into sales (stock deducted). `Cancelled` releases them.
  - The scheduled `ExpireStockReservations` job (every minute, in `bootstrap/app.php`) releases expired reservations on pending orders.
- **POS sales** (`Sale`/`SaleItem`, `PosService`) are separate from online `Order`s and deduct stock right away. `ReportService::getProfitAndLoss` combines order revenue, POS revenue, COGS (variant `cost_price`) and `Expense`s.
- **Cart** is session-based (`CartService`), keyed by variant id. Coupons are checked by `CouponService`.
- **Settings** (`SettingsService`) are stored per group (`general`, `pixels`, `courier`) in a `settings` row. They are merged over code defaults, cached forever (`settings.{group}`), and secret fields are encrypted at rest. Always read and write them through the service.
- **Analytics/pixels** go through `AnalyticsService` (GA4-shaped events: `view_item`, `add_to_cart`, `begin_checkout`, `purchase`, `generate_lead`…). Controllers call it, events queue in the session and `storefront/partials/pixels.blade.php` flushes them via `window.nfTrack()` to Meta/GA4/GTM/TikTok; AJAX endpoints return them as `analytics` for `window.nfFlush()`. With API tokens set, the queued `SendServerAnalyticsEvent` job also sends to Meta CAPI / TikTok Events API / GA4 MP (purchase) using the same event id for dedup.
- **Media library** (`MediaLibraryService`, `/admin/media`) lists every image: `MediaAsset` library uploads plus product/variant Spatie media, category images and the logo, addressed by keys `media:{id}` / `category:{id}` / `logo`. Image fields offer `window.openMediaPicker()` (Alpine store in `resources/js/media-picker.js`); forms submit keys (`library_images[]`, `variants.*.library_image`, `library_image`, `library_logo`) and the chosen file is **copied** into the target. File inputs with `data-image-editor` get the client-side crop/resize editor (`resources/js/nf-image-editor.js`).
- Admin write actions are recorded with `ActivityLogger`. Order emails go out through the queued `SendOrderNotification` → `OrderStatusMail`.
- Global view helpers in `app/Support/helpers.php` (autoloaded): `shop_price()`, `color_hex()`, `order_status_style()`, `brand_logo()`, `estimated_delivery()`. Shop constants are in `config/shop.php`.
- Views: `resources/views/storefront`, `resources/views/admin`, shared components in `components/ui` and `components/admin`. Product imagery is CSS gradient placeholders, mirroring the prototype in `docs/nafian_prototype`.

## UI conventions (Claude Design import)

The storefront and admin panel follow the Claude Design project `Nafian` (boards: Home, Shop, Product, Checkout, Offers, Track Order, Admin, Admin Manage, Mobile).

- **Language is Bangla** for all customer- and admin-facing copy; numbers render with Bangla digits through `bn_digits()` / `bn_price()` / `bn_date()` / `bn_time()` / `bn_phone()` in `app/Support/helpers.php`. Input is normalised back with `latin_digits()` / `normalize_phone()`.
- **Palette and type live in `resources/css/app.css`** as theme tokens (`espresso`, `mocha`, `ink`, `cocoa`, `muted`, `sand*`, `panel*`, `canvas`, `line`, `hair`, `accent`, `rose`, `moss`). Use the tokens (`bg-panel`, `text-muted`) rather than new hex values. Fonts: Hind Siliguri, plus `font-display` (Libre Caslon Display) for headings. Shared bits: `.nf-input`, `.nf-label`, `.nf-shadow`, `.nf-switch`, `.nf-rail`, `.nf-line`.
- **The phone layout is a separate design, not a squeezed desktop one.** Everything below `sm` (640px) is the app-style layout: bottom tab bar, page-specific mobile headers (`@section('mobile_header')`), bottom sheets, sticky action bars, full-screen search. `desk:` (1100px) is the wide storefront/admin breakpoint. Pages opt out of chrome with `@section('no_tabbar')`, `@section('no_footer')`, `@section('hide_header_search')`.
- **Mobile headers render outside the page's Alpine scope**, so state they share with the page (shop filters, checkout steps) lives in an `Alpine.store()` defined in a `@push('head')` block, not in `x-data`.
- Product cards come from `storefront/partials/product-card.blade.php` with `$style` = `grid` (default), `rail` or `mini`.

## Storefront behaviour added with the design

- **Delivery zones**: inside vs outside `config('shop.inside_city')`, priced from the `general` settings group (`delivery_inside`, `delivery_outside`, `free_delivery_threshold`). `CartService::getSummary()` and checkout derive the zone from the selected city.
- **Payment methods** are `cod`, `mobile_banking`, `card` (legacy `online` kept for old rows) and each can be switched off in settings.
- **Orders** keep `shipping_area`, `shipping_postcode`, `delivery_zone`, `rider_name`, `rider_phone`, plus an `order_status_histories` row per transition — that history drives both the customer tracking timeline (`Order::trackingSteps()`) and the admin order page.
- **Tracking is order number + phone**; shoppers can cancel while the order is pending/confirmed, and only for orders in their session (`recent_orders`).
- `CatalogService` powers the shop page (filters, sorts, facets), best sellers, on-sale lists and `hide_when_out_of_stock` visibility. Products also carry fragrance notes, `ingredients`, `usage_instructions` and `is_combo` (the offers page's combo packs).
- Coupons support a `second_item_percentage` type (COMBO30-style "30% off the 2nd item") and an optional `max_discount_amount` cap; the offers campaign copy/coupon lives in the `campaign` settings group.

## Known gaps

The payment gateway (online payments stay `pending`), courier API calls (Steadfast/Pathao are stubs), and password reset/email verification are not implemented yet. Restock requests and newsletter/campaign subscribers are stored but nothing sends to them yet, and there is no admin screen for either. See `docs/BUILD_TRACKER.md`.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
