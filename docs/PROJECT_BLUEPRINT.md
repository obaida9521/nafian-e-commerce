# E-Commerce Platform — Laravel 12 Project Blueprint
> **For Claude Code Agent**: Read this entire file before writing a single line of code.
> This is the single source of truth. Follow every convention exactly.

---

## 1. PROJECT OVERVIEW

**Type**: Full-stack e-commerce platform (Blade + Laravel 12)
**Phase 1**: Admin panel (complete back-office)
**Phase 2**: Customer-facing storefront
**Database**: MySQL 8+
**Queue**: Laravel Queue with database driver (Redis-ready)
**Cache**: File driver (Redis-ready)
**Auth**: Laravel Breeze (Blade stack) — separate guard for admin and customer

---

## 2. TECHNOLOGY STACK

```
Laravel 12
PHP 8.3+
MySQL 8
Blade templating (NO Livewire, NO Vue, NO React)
Alpine.js (for lightweight JS interactivity — dropdowns, modals, toggles)
Tailwind CSS v3 (via Vite)
Chart.js (admin dashboard charts)
Laravel Spatie Permission (RBAC)
Laravel Media Library (product images)
Intervention Image (image resizing)
Laravel Excel (Maatwebsite — exports)
```

**Install these packages in order:**
```bash
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require intervention/image
composer require maatwebsite/excel
npm install -D tailwindcss alpinejs @alpinejs/collapse
```

---

## 3. DIRECTORY STRUCTURE

Follow this structure exactly. Do not deviate.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── VariantController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── OrderController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── CouponController.php
│   │   │   ├── ReportController.php
│   │   │   └── ActivityLogController.php
│   │   ├── Auth/
│   │   │   ├── AdminAuthController.php
│   │   │   └── CustomerAuthController.php
│   │   └── Storefront/
│   │       ├── HomeController.php
│   │       ├── ProductController.php
│   │       ├── CartController.php
│   │       ├── CheckoutController.php
│   │       └── OrderController.php
│   ├── Middleware/
│   │   ├── AdminMiddleware.php
│   │   └── EnsureEmailIsVerified.php
│   └── Requests/
│       ├── Admin/
│       │   ├── StoreProductRequest.php
│       │   ├── UpdateProductRequest.php
│       │   ├── StoreVariantRequest.php
│       │   ├── StoreOrderStatusRequest.php
│       │   ├── AdjustInventoryRequest.php
│       │   └── StoreCouponRequest.php
│       └── Storefront/
│           ├── CheckoutRequest.php
│           └── AddToCartRequest.php
├── Models/
│   ├── User.php                    (customers)
│   ├── Admin.php                   (admin users — separate model)
│   ├── Product.php
│   ├── ProductVariant.php
│   ├── AttributeDefinition.php
│   ├── VariantAttributeValue.php
│   ├── Category.php
│   ├── ProductCategory.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Payment.php
│   ├── Coupon.php
│   ├── CouponUsage.php
│   ├── Address.php
│   ├── InventoryTransaction.php
│   ├── StockReservation.php
│   ├── AdminActivityLog.php
│   └── Notification.php
├── Services/
│   ├── ProductService.php
│   ├── VariantService.php
│   ├── InventoryService.php
│   ├── OrderService.php
│   ├── CartService.php
│   ├── CouponService.php
│   ├── PaymentService.php
│   └── ReportService.php
├── Repositories/
│   ├── ProductRepository.php
│   ├── VariantRepository.php
│   ├── OrderRepository.php
│   ├── InventoryRepository.php
│   └── CouponRepository.php
├── Observers/
│   └── OrderObserver.php
├── Jobs/
│   ├── SendOrderNotification.php
│   └── ExpireStockReservations.php
└── Enums/
    ├── OrderStatus.php
    ├── PaymentMethod.php
    ├── PaymentStatus.php
    ├── InventoryTransactionType.php
    └── UserRole.php

resources/
└── views/
    ├── layouts/
    │   ├── admin.blade.php          (admin shell)
    │   ├── auth.blade.php           (login page shell)
    │   └── app.blade.php            (storefront shell — Phase 2)
    ├── admin/
    │   ├── dashboard/
    │   │   └── index.blade.php
    │   ├── products/
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   └── edit.blade.php
    │   ├── variants/
    │   │   └── _form.blade.php      (partial — embedded in product form)
    │   ├── categories/
    │   │   ├── index.blade.php
    │   │   └── _form.blade.php
    │   ├── orders/
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── inventory/
    │   │   └── index.blade.php
    │   ├── customers/
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── coupons/
    │   │   └── index.blade.php
    │   ├── reports/
    │   │   └── index.blade.php
    │   └── activity-logs/
    │       └── index.blade.php
    ├── auth/
    │   ├── admin-login.blade.php
    │   └── login.blade.php
    └── components/
        ├── admin/
        │   ├── stat-card.blade.php
        │   ├── data-table.blade.php
        │   ├── status-badge.blade.php
        │   ├── pagination.blade.php
        │   └── confirm-modal.blade.php
        └── ui/
            ├── alert.blade.php
            └── breadcrumb.blade.php
```

---

## 4. DATABASE SCHEMA

Create migrations in this exact order. Each migration file name is given.

### Migration 1: `2024_01_01_000001_create_admins_table.php`
```sql
admins
  id               BIGINT UNSIGNED PK AUTO_INCREMENT
  name             VARCHAR(100) NOT NULL
  email            VARCHAR(150) UNIQUE NOT NULL
  password         VARCHAR(255) NOT NULL
  role             ENUM('superadmin','admin','manager','viewer') DEFAULT 'admin'
  is_active        BOOLEAN DEFAULT TRUE
  last_login_at    TIMESTAMP NULL
  remember_token   VARCHAR(100) NULL
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
```

### Migration 2: `2024_01_01_000002_create_users_table.php`
```sql
users
  id               BIGINT UNSIGNED PK AUTO_INCREMENT
  name             VARCHAR(100) NOT NULL
  email            VARCHAR(150) UNIQUE NULL
  phone            VARCHAR(20) UNIQUE NULL
  password         VARCHAR(255) NULL        (null = guest)
  email_verified_at TIMESTAMP NULL
  is_active        BOOLEAN DEFAULT TRUE
  remember_token   VARCHAR(100) NULL
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
  deleted_at       TIMESTAMP NULL           (soft delete)

INDEX: email, phone
```

### Migration 3: `2024_01_01_000003_create_addresses_table.php`
```sql
addresses
  id               BIGINT UNSIGNED PK
  user_id          FK → users.id (cascade delete)
  label            VARCHAR(50) NULL         (e.g. Home, Office)
  recipient_name   VARCHAR(100) NOT NULL
  phone            VARCHAR(20) NOT NULL
  address_line1    VARCHAR(255) NOT NULL
  address_line2    VARCHAR(255) NULL
  city             VARCHAR(100) NOT NULL
  district         VARCHAR(100) NOT NULL
  postal_code      VARCHAR(20) NULL
  is_default       BOOLEAN DEFAULT FALSE
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEX: user_id
```

### Migration 4: `2024_01_01_000004_create_categories_table.php`
```sql
categories
  id               BIGINT UNSIGNED PK
  parent_id        FK → categories.id NULL (self-referencing)
  name             VARCHAR(100) NOT NULL
  slug             VARCHAR(120) UNIQUE NOT NULL
  description      TEXT NULL
  image_path       VARCHAR(255) NULL
  sort_order       INT DEFAULT 0
  is_active        BOOLEAN DEFAULT TRUE
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
  deleted_at       TIMESTAMP NULL

INDEX: slug, parent_id
```

### Migration 5: `2024_01_01_000005_create_attribute_definitions_table.php`
```sql
attribute_definitions
  id               BIGINT UNSIGNED PK
  name             VARCHAR(50) NOT NULL     (e.g. "Size", "Color", "Type")
  slug             VARCHAR(60) UNIQUE NOT NULL
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
```

### Migration 6: `2024_01_01_000006_create_products_table.php`
```sql
products
  id               BIGINT UNSIGNED PK
  name             VARCHAR(200) NOT NULL
  slug             VARCHAR(220) UNIQUE NOT NULL
  description      LONGTEXT NULL
  short_description VARCHAR(500) NULL
  is_active        BOOLEAN DEFAULT TRUE
  is_featured      BOOLEAN DEFAULT FALSE
  meta_title       VARCHAR(200) NULL
  meta_description VARCHAR(300) NULL
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
  deleted_at       TIMESTAMP NULL

INDEX: slug, is_active, is_featured
```

### Migration 7: `2024_01_01_000007_create_product_categories_table.php`
```sql
product_categories
  product_id       FK → products.id (cascade delete)
  category_id      FK → categories.id (cascade delete)
  PRIMARY KEY (product_id, category_id)
```

### Migration 8: `2024_01_01_000008_create_product_variants_table.php`
```sql
product_variants
  id               BIGINT UNSIGNED PK
  product_id       FK → products.id (cascade delete)
  sku              VARCHAR(100) UNIQUE NOT NULL
  price            DECIMAL(10,2) NOT NULL
  compare_at_price DECIMAL(10,2) NULL       (strikethrough price)
  cost_price       DECIMAL(10,2) NULL       (for profit calculation)
  stock_quantity   INT UNSIGNED DEFAULT 0
  reserved_quantity INT UNSIGNED DEFAULT 0
  weight_grams     INT UNSIGNED NULL
  is_active        BOOLEAN DEFAULT TRUE
  sort_order       INT DEFAULT 0
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
  deleted_at       TIMESTAMP NULL

COMPUTED: available_quantity = stock_quantity - reserved_quantity
INDEX: product_id, sku, is_active
```

### Migration 9: `2024_01_01_000009_create_variant_attribute_values_table.php`
```sql
variant_attribute_values
  id               BIGINT UNSIGNED PK
  variant_id       FK → product_variants.id (cascade delete)
  attribute_id     FK → attribute_definitions.id
  value            VARCHAR(100) NOT NULL    (e.g. "100ml", "Blue", "EDP")
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

UNIQUE KEY (variant_id, attribute_id)
INDEX: variant_id
```

### Migration 10: `2024_01_01_000010_create_coupons_table.php`
```sql
coupons
  id               BIGINT UNSIGNED PK
  code             VARCHAR(50) UNIQUE NOT NULL
  type             ENUM('percentage','fixed') NOT NULL
  value            DECIMAL(10,2) NOT NULL
  min_order_amount DECIMAL(10,2) DEFAULT 0
  max_uses         INT UNSIGNED NULL        (null = unlimited)
  used_count       INT UNSIGNED DEFAULT 0
  valid_from       TIMESTAMP NULL
  valid_until      TIMESTAMP NULL
  is_active        BOOLEAN DEFAULT TRUE
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
  deleted_at       TIMESTAMP NULL

INDEX: code
```

### Migration 11: `2024_01_01_000011_create_orders_table.php`
```sql
orders
  id               BIGINT UNSIGNED PK
  order_number     VARCHAR(30) UNIQUE NOT NULL  (e.g. ORD-2024-000001)
  user_id          FK → users.id NULL (null = guest)
  guest_email      VARCHAR(150) NULL
  guest_phone      VARCHAR(20) NULL
  status           ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending'
  
  -- Address snapshot (copied at order time, NOT FK)
  shipping_name    VARCHAR(100) NOT NULL
  shipping_phone   VARCHAR(20) NOT NULL
  shipping_address VARCHAR(255) NOT NULL
  shipping_city    VARCHAR(100) NOT NULL
  shipping_district VARCHAR(100) NOT NULL
  
  -- Financial snapshot
  subtotal         DECIMAL(10,2) NOT NULL
  discount_amount  DECIMAL(10,2) DEFAULT 0
  delivery_charge  DECIMAL(10,2) DEFAULT 0
  total_amount     DECIMAL(10,2) NOT NULL
  
  coupon_id        FK → coupons.id NULL (SET NULL on delete)
  coupon_code      VARCHAR(50) NULL     (snapshot)
  
  payment_method   ENUM('cod','online') NOT NULL
  notes            TEXT NULL
  admin_notes      TEXT NULL
  cancelled_reason TEXT NULL
  
  shipped_at       TIMESTAMP NULL
  delivered_at     TIMESTAMP NULL
  cancelled_at     TIMESTAMP NULL
  
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEX: order_number, user_id, status, created_at
```

### Migration 12: `2024_01_01_000012_create_order_items_table.php`
```sql
order_items
  id               BIGINT UNSIGNED PK
  order_id         FK → orders.id (cascade delete)
  variant_id       FK → product_variants.id (SET NULL on delete)
  
  -- Snapshots (immutable after creation)
  product_name     VARCHAR(200) NOT NULL
  variant_name     VARCHAR(200) NOT NULL    (e.g. "100ml - EDP")
  sku              VARCHAR(100) NOT NULL
  unit_price       DECIMAL(10,2) NOT NULL
  quantity         INT UNSIGNED NOT NULL
  line_total       DECIMAL(10,2) NOT NULL   (unit_price × quantity)
  
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEX: order_id, variant_id
```

### Migration 13: `2024_01_01_000013_create_payments_table.php`
```sql
payments
  id               BIGINT UNSIGNED PK
  order_id         FK → orders.id (cascade delete)
  method           ENUM('cod','online') NOT NULL
  gateway          VARCHAR(50) NULL         (e.g. 'sslcommerz', 'bkash', 'stripe')
  gateway_transaction_id VARCHAR(200) NULL
  amount           DECIMAL(10,2) NOT NULL
  status           ENUM('pending','paid','failed','refunded') DEFAULT 'pending'
  paid_at          TIMESTAMP NULL
  refunded_at      TIMESTAMP NULL
  refund_amount    DECIMAL(10,2) NULL
  metadata         JSON NULL                (gateway raw response)
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEX: order_id, status, gateway_transaction_id
```

### Migration 14: `2024_01_01_000014_create_coupon_usages_table.php`
```sql
coupon_usages
  id               BIGINT UNSIGNED PK
  coupon_id        FK → coupons.id (cascade delete)
  user_id          FK → users.id NULL
  order_id         FK → orders.id (cascade delete)
  used_at          TIMESTAMP NOT NULL

UNIQUE KEY (coupon_id, order_id)
INDEX: coupon_id, user_id
```

### Migration 15: `2024_01_01_000015_create_inventory_transactions_table.php`
```sql
inventory_transactions
  id               BIGINT UNSIGNED PK
  variant_id       FK → product_variants.id (cascade delete)
  type             ENUM('purchase','sale','return','adjustment','reservation','reservation_release') NOT NULL
  quantity_change  INT NOT NULL             (positive = add, negative = deduct)
  stock_before     INT NOT NULL             (snapshot)
  stock_after      INT NOT NULL             (snapshot)
  reference_type   VARCHAR(50) NULL         (e.g. 'order', 'manual')
  reference_id     BIGINT UNSIGNED NULL     (order_id or null)
  reason           VARCHAR(255) NULL
  created_by_type  VARCHAR(50) NULL         ('admin' or 'system')
  created_by_id    BIGINT UNSIGNED NULL
  created_at       TIMESTAMP

-- This table is IMMUTABLE. No updates, no deletes ever.
INDEX: variant_id, type, created_at
```

### Migration 16: `2024_01_01_000016_create_stock_reservations_table.php`
```sql
stock_reservations
  id               BIGINT UNSIGNED PK
  order_id         FK → orders.id (cascade delete)
  variant_id       FK → product_variants.id (cascade delete)
  quantity         INT UNSIGNED NOT NULL
  status           ENUM('active','released','converted') DEFAULT 'active'
  expires_at       TIMESTAMP NOT NULL       (15 min for online, 24hr for COD)
  created_at       TIMESTAMP
  updated_at       TIMESTAMP

INDEX: order_id, variant_id, status, expires_at
```

### Migration 17: `2024_01_01_000017_create_admin_activity_logs_table.php`
```sql
admin_activity_logs
  id               BIGINT UNSIGNED PK
  admin_id         FK → admins.id (SET NULL)
  action           VARCHAR(100) NOT NULL    (e.g. 'order.status_changed')
  resource_type    VARCHAR(50) NOT NULL     (e.g. 'order', 'product')
  resource_id      BIGINT UNSIGNED NULL
  description      VARCHAR(255) NOT NULL
  old_value        JSON NULL
  new_value        JSON NULL
  ip_address       VARCHAR(45) NULL
  user_agent       VARCHAR(500) NULL
  created_at       TIMESTAMP

-- IMMUTABLE. No updates, no deletes.
INDEX: admin_id, action, resource_type, created_at
```

---

## 5. MODELS & RELATIONSHIPS

### Admin.php
```php
// Guard: 'admin'
// Does NOT use SoftDeletes
// Has: activityLogs()
// Fillable: name, email, password, role, is_active
```

### User.php
```php
// Guard: 'web'
// Uses SoftDeletes
// Has: orders(), addresses(), defaultAddress()
// Fillable: name, email, phone, password, is_active
```

### Product.php
```php
// Uses SoftDeletes, HasMedia (Spatie)
// BelongsToMany: categories
// HasMany: variants (active scope)
// Accessors: thumbnail, price_range
// Scopes: active(), featured()
```

### ProductVariant.php
```php
// Uses SoftDeletes
// BelongsTo: product
// HasMany: attributeValues, inventoryTransactions
// Accessor: available_quantity = stock_quantity - reserved_quantity
// Accessor: display_name = attribute values joined (e.g. "100ml / EDP")
```

### Order.php
```php
// BelongsTo: user (nullable), coupon (nullable)
// HasMany: items, payments
// HasOne: activeReservation
// Accessor: status_label, status_color (for badge)
// Scopes: byStatus(), forAdmin()
```

### Category.php
```php
// Uses SoftDeletes
// BelongsTo: parent (self)
// HasMany: children (self)
// BelongsToMany: products
// Accessor: full_path (e.g. "Fashion > T-Shirts")
```

---

## 6. ENUMS

### OrderStatus.php
```php
enum OrderStatus: string {
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';

    public function label(): string { ... }
    public function color(): string { ... }  // returns Tailwind color class
    public function canTransitionTo(self $next): bool { ... }
}
```

### PaymentMethod.php
```php
enum PaymentMethod: string {
    case COD    = 'cod';
    case Online = 'online';
}
```

### InventoryTransactionType.php
```php
enum InventoryTransactionType: string {
    case Purchase           = 'purchase';
    case Sale               = 'sale';
    case Return             = 'return';
    case Adjustment         = 'adjustment';
    case Reservation        = 'reservation';
    case ReservationRelease = 'reservation_release';
}
```

---

## 7. SERVICES (Business Logic)

### InventoryService.php — CRITICAL
```
Methods:
  reserveStock(Order $order): void
    - Within DB::transaction()
    - Check available_quantity >= requested for EACH variant
    - If any variant fails → throw InsufficientStockException
    - Increment reserved_quantity on variant
    - Create StockReservation record
    - Create InventoryTransaction (type: reservation)

  releaseReservation(Order $order): void
    - Decrement reserved_quantity on variant
    - Update StockReservation status = 'released'
    - Create InventoryTransaction (type: reservation_release)

  convertReservationToSale(Order $order): void
    - Within DB::transaction()
    - Decrement both stock_quantity AND reserved_quantity
    - Update StockReservation status = 'converted'
    - Create InventoryTransaction (type: sale)

  adjustStock(ProductVariant $variant, int $quantity, string $reason, Admin $admin): void
    - Within DB::transaction()
    - Update stock_quantity directly
    - Create InventoryTransaction (type: adjustment)
    - Create AdminActivityLog

  getLowStockVariants(int $threshold = 5): Collection
```

### OrderService.php
```
Methods:
  createOrder(array $data, ?User $user): Order
    - Within DB::transaction()
    - Generate order_number (ORD-YYYY-XXXXXX)
    - Validate coupon if provided
    - Create Order record
    - Create OrderItem records (with price snapshots)
    - Call InventoryService::reserveStock()
    - Create Payment record
    - Dispatch SendOrderNotification job
    - Return Order

  updateStatus(Order $order, OrderStatus $newStatus, Admin $admin): void
    - Validate transition via OrderStatus::canTransitionTo()
    - Update order status
    - If newStatus = delivered → call InventoryService::convertReservationToSale()
    - If newStatus = cancelled → call InventoryService::releaseReservation()
    - Create AdminActivityLog
    - Dispatch SendOrderNotification job

  cancelOrder(Order $order, string $reason, Admin $admin): void
```

### CouponService.php
```
Methods:
  validate(string $code, float $orderAmount, ?User $user): Coupon
    - Check exists, is_active
    - Check valid_from <= now <= valid_until
    - Check used_count < max_uses
    - Check orderAmount >= min_order_amount
    - Throw CouponException with specific message on any failure

  calculateDiscount(Coupon $coupon, float $subtotal): float
    - percentage: min(subtotal * value/100, max_discount if set)
    - fixed: min(value, subtotal)

  markUsed(Coupon $coupon, Order $order, ?User $user): void
```

### CartService.php
```
Storage: Laravel Session
Methods:
  add(int $variantId, int $quantity): void
  update(int $variantId, int $quantity): void
  remove(int $variantId): void
  clear(): void
  getItems(): Collection         (loads variant + product from DB)
  getSubtotal(): float
  getCount(): int
  applyCoupon(string $code): void
  removeCoupon(): void
```

### ReportService.php
```
Methods:
  getDashboardStats(): array
    Returns: today_orders, today_revenue, total_products, low_stock_count

  getSalesChart(string $period = '7d'): array
    Returns: labels[], revenue[], orders[]

  getOrderStatusBreakdown(): array

  getTopProducts(int $limit = 10): Collection

  getRevenueReport(Carbon $from, Carbon $to): array
    Returns: gross_revenue, discount_total, delivery_total, net_revenue, cod_total, online_total
```

---

## 8. ROUTES

### routes/web.php structure:
```php
// Admin Auth (no middleware)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin Panel (admin middleware)
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', ProductController::class);
    Route::resource('products.variants', VariantController::class)->shallow();

    // Categories
    Route::resource('categories', CategoryController::class);

    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Inventory
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Customers
    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'update']);

    // Coupons
    Route::resource('coupons', CouponController::class)->except(['show']);

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Activity Log
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
```

---

## 9. CONTROLLERS (Admin — Key Methods)

Controllers are **thin**: validate via Form Request → delegate to Service/Repository → return view or redirect with flash. No business logic in controllers.

### DashboardController.php
```
index()
  - ReportService::getDashboardStats()
  - ReportService::getSalesChart('7d')
  - ReportService::getOrderStatusBreakdown()
  - ReportService::getTopProducts(10)
  - return view('admin.dashboard.index', compact(...))
```

### ProductController.php (resource)
```
index()   - ProductRepository::paginated(filters from request), eager load variants+categories
create()  - load categories + attributeDefinitions for the form
store()   - StoreProductRequest → ProductService::create() → redirect products.index with success
edit()    - load product with variants.attributeValues, categories
update()  - UpdateProductRequest → ProductService::update()
destroy() - ProductService::softDelete() (sets deleted_at, logs activity)
```

### VariantController.php (shallow resource under products)
```
store()   - StoreVariantRequest → VariantService::create($product, data)
update()  - StoreVariantRequest → VariantService::update($variant, data)
destroy() - VariantService::softDelete($variant)
```

### OrderController.php
```
index()        - OrderRepository::forAdmin(filters: status, date range, search), paginate
show()         - load order.items, payments, user, activeReservation
updateStatus() - StoreOrderStatusRequest → OrderService::updateStatus($order, $status, $admin)
                 catch domain exceptions → redirect back with error
cancel()       - OrderService::cancelOrder($order, $reason, $admin)
```

### InventoryController.php
```
index()  - InventoryRepository::variantsWithStock(filters: low_stock, search), paginate
adjust() - AdjustInventoryRequest → InventoryService::adjustStock($variant, $qty, $reason, $admin)
```

### CustomerController.php (index, show, update only)
```
index()  - User::withCount('orders')->paginate
show()   - load user.orders, addresses; lifetime value (sum delivered totals)
update() - toggle is_active / edit basics; log activity
```

### CouponController.php (except show)
```
index/create/store/edit/update/destroy via CouponService where logic applies
store/update validate code uniqueness, type+value, date range
```

### ReportController.php
```
index()  - ReportService::getRevenueReport($from, $to) + chart data
export() - ReportService::getRevenueReport() → Maatwebsite\Excel download (xlsx)
```

### ActivityLogController.php
```
index() - AdminActivityLog::with('admin')->latest()->filter(action, resource_type, admin)->paginate
          READ ONLY. No create/update/delete.
```

---

## 10. FORM REQUESTS (Validation)

All write actions go through a Form Request. `authorize()` does the RBAC role check; `rules()` does validation. Return custom `messages()` where helpful.

### StoreProductRequest.php
```php
authorize(): admin role in [superadmin, admin, manager]
rules():
  name              => required|string|max:200
  slug              => nullable|string|max:220|unique:products,slug
  description       => nullable|string
  short_description => nullable|string|max:500
  is_active         => boolean
  is_featured       => boolean
  meta_title        => nullable|string|max:200
  meta_description  => nullable|string|max:300
  category_ids      => required|array|min:1
  category_ids.*    => exists:categories,id
  variants          => required|array|min:1   (at least one variant)
```
`UpdateProductRequest` = same, but `slug` unique ignores current id.

### StoreVariantRequest.php
```php
rules():
  sku               => required|string|max:100|unique:product_variants,sku (ignore self on update)
  price             => required|numeric|min:0
  compare_at_price  => nullable|numeric|min:0|gte:price
  cost_price        => nullable|numeric|min:0
  stock_quantity    => required|integer|min:0
  weight_grams      => nullable|integer|min:0
  is_active         => boolean
  attributes        => array
  attributes.*.attribute_id => required|exists:attribute_definitions,id
  attributes.*.value        => required|string|max:100
```

### StoreOrderStatusRequest.php
```php
rules():
  status        => required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded
  admin_notes   => nullable|string|max:1000
// Transition legality enforced in OrderService, NOT here.
```

### AdjustInventoryRequest.php
```php
rules():
  quantity => required|integer   (signed: +add, -deduct; service rejects if result < 0)
  reason   => required|string|max:255
```

### StoreCouponRequest.php
```php
rules():
  code             => required|string|max:50|unique:coupons,code (ignore self on update)
  type             => required|in:percentage,fixed
  value            => required|numeric|min:0 (if percentage: max:100)
  min_order_amount => nullable|numeric|min:0
  max_uses         => nullable|integer|min:1
  valid_from       => nullable|date
  valid_until      => nullable|date|after_or_equal:valid_from
  is_active        => boolean
```

---

## 11. REPOSITORIES (Query Layer)

Repositories own complex/reused queries. Services call repositories; controllers may call them for read-only listing. Keep Eloquent query-building here, business rules in Services.

### ProductRepository.php
```
paginated(array $filters): LengthAwarePaginator
  filters: search (name/sku), category_id, is_active, is_featured
  eager: variants (active), categories, first media (thumbnail)
findWithVariants(int $id): Product
```

### VariantRepository.php
```
forProduct(int $productId): Collection
findBySku(string $sku): ?ProductVariant
lockForUpdate(int $id): ProductVariant   (used inside InventoryService transactions)
```

### OrderRepository.php
```
forAdmin(array $filters): LengthAwarePaginator
  filters: status, date_from, date_to, search (order_number/email/phone)
  eager: user, items
withFullDetail(int $id): Order   (items, payments, reservation, user)
```

### InventoryRepository.php
```
variantsWithStock(array $filters): LengthAwarePaginator
  filters: search, low_stock (available <= threshold)
  select computed available_quantity
transactionsFor(int $variantId): LengthAwarePaginator
```

### CouponRepository.php
```
findActiveByCode(string $code): ?Coupon
paginated(array $filters): LengthAwarePaginator
```

---

## 12. OBSERVERS, JOBS & EVENTS

### OrderObserver.php
```
creating(Order $order)
  - Auto-generate order_number if empty: ORD-{YYYY}-{zero-padded sequence}
created(Order $order)
  - Write AdminActivityLog (or system log) 'order.created'
updated(Order $order)
  - If status changed → log old/new status snapshot
// Register in AppServiceProvider::boot() via Order::observe(OrderObserver::class)
```

### Jobs

**SendOrderNotification.php** (queued, `database` driver)
```
__construct(public Order $order, public string $event)  // event: created|status_changed
handle()
  - Build notification payload
  - Send email (and/or SMS stub) to customer/guest
  - Persist Notification record
// implements ShouldQueue
```

**ExpireStockReservations.php** (queued, scheduled)
```
handle()
  - Find StockReservation where status=active AND expires_at < now()
  - For each: InventoryService::releaseReservation($order)
  - Mark reservation released
// Schedule in routes/console.php: every minute
//   Schedule::job(new ExpireStockReservations)->everyMinute();
```

### Domain Exceptions (`app/Exceptions/`)
```
InsufficientStockException  — thrown by InventoryService::reserveStock()
CouponException             — thrown by CouponService::validate() (carries user-facing message)
InvalidStatusTransitionException — thrown by OrderService::updateStatus()
// Controllers catch these → redirect back with ->with('error', $e->getMessage())
```

---

## 13. full UI in claude code design
desiign will be same to same and mobile and desktop friendly. if need react.js then do that.
```
Fetch this design file, read its readme, and implement the relevant aspects of the design. https://api.anthropic.com/v1/design/h/v5TPFDdo7VV7ieDhVJ8VzA?open_file=Nafian.dc.html
Implement: Nafian.dc.html

primary color:  #691d2a
background color: #fff2e3
```
---

### Layout: `layouts/admin.blade.php`
```
Fixed sidebar left (260px wide)
  - Logo top (text or image)
  - Nav links with Heroicons (outline)
  - Active state: bg-gray-100 text-gray-900 rounded-lg
  - Collapsed variant for mobile

Top bar (sticky)
  - Page title (left)
  - Search (center, optional)
  - Notification bell + Admin avatar dropdown (right)

Main content area
  - ml-[260px] on desktop
  - p-6 padding
  - max-w-none (full width)
  - bg-gray-50 min-h-screen
```



### Stat Card Component (`components/admin/stat-card.blade.php`)
```
Props: $title, $value, $change (optional, e.g. "+12%"), $icon, $color
Layout: icon left, title + value right, change below
```


---


## 14. SECURITY & MIDDLEWARE

### AdminMiddleware.php
```php
// Check auth()->guard('admin')->check()
// If not authenticated → redirect to admin.login
// Log last_login_at on successful auth
```

### Admin Guard config (config/auth.php)
```php
'guards' => [
    'admin' => [
        'driver'   => 'session',
        'provider' => 'admins',
    ],
],
'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Admin::class,
    ],
],
```

### RBAC Implementation
```
Use role-based checks in controllers:
  - superadmin: full access
  - admin: all except deleting admins
  - manager: orders, inventory, customers only
  - viewer: read-only on all

Use Blade directives in views:
  @if(auth()->guard('admin')->user()->role === 'superadmin')
  Or create @adminCan('delete-products') custom directive
```

---

## 15. SEEDERS

Create these seeders and call from DatabaseSeeder.php:

```
AdminSeeder         → 1 superadmin: admin@shop.com / password
CategorySeeder      → 3 parent categories: Perfume, Fashion, Accessories
AttributeSeeder     → Size, Color, Type, Concentration
ProductSeeder       → 5 products with 2-3 variants each (perfume focused)
CouponSeeder        → 2 coupons: WELCOME10 (10%), FLAT50 (BDT 50 fixed)
```

---

## 16. IMPORTANT IMPLEMENTATION RULES

### Rule 1: Transactions are sacred
Every operation touching stock AND orders AND payments must be wrapped in `DB::transaction()`. If anything fails, everything rolls back.

### Rule 2: Never edit order financials
Once an order is created, `subtotal`, `discount_amount`, `delivery_charge`, `total_amount` on the `orders` table are IMMUTABLE. Refunds go through the `payments` table only.

### Rule 3: Inventory logs are append-only
Never update or delete rows in `inventory_transactions` or `admin_activity_logs`. Only INSERT.

### Rule 4: Price snapshots
`order_items.unit_price` is copied at order creation time from `product_variants.price`. Never join back to `product_variants.price` for financial calculations on existing orders.

### Rule 5: Stock checks must be pessimistic
Always check `available_quantity = stock_quantity - reserved_quantity`, never just `stock_quantity`.

### Rule 6: Admin auth is separate
Admin login is `/admin/login` using `auth()->guard('admin')`. Customer auth uses `auth()->guard('web')`. Never mix guards.

### Rule 7: Soft deletes everywhere
All admin-managed models use `SoftDeletes`. Never hard delete products, variants, categories, coupons, customers in production.

### Rule 8: Flash messages
Use session flash for all admin actions:
```php
return redirect()->back()->with('success', 'Order status updated.');
return redirect()->back()->with('error', 'Insufficient stock.');
```
Display them in the admin layout using a dismissible alert component.

---

## 17. BUILD ORDER FOR AGENT

Build in this exact sequence. Complete each phase before starting the next.

### Phase 1 — Foundation (do first)
1. Laravel 12 fresh install
2. Install all packages (see Section 2)
3. Configure auth guards (admin + web)
4. Run all migrations in order
5. Create all Enums
6. Create all Models with relationships
7. Create Seeders and run them
8. Create admin login page + AdminMiddleware
9. Verify admin can log in at `/admin/login`

### Phase 2 — Core Services
10. InventoryService (most critical — test this first)
11. CouponService
12. CartService
13. OrderService
14. ReportService

### Phase 3 — Admin Panel (build in this order)
15. Admin layout (sidebar, topbar, flash messages)
16. Dashboard page (stats + charts)
17. Category management (simple CRUD — good warm-up)
18. Product management (complex form with variants)
19. Inventory page
20. Order list + Order detail + Status update
21. Customer list + detail
22. Coupon management
23. Reports page
24. Activity log page

### Phase 4 — Storefront (Phase 2 of project)
25. Customer auth
26. Homepage
27. Product listing page (with filters)
28. Product detail page
29. Cart (session-based)
30. Checkout flow
31. Order confirmation
32. Customer account pages

---

## 18. TESTING CHECKLIST (Verify after each phase)

After Phase 1:
- [ ] `php artisan migrate --seed` runs without errors
- [ ] Admin can log in at `/admin/login`
- [ ] Admin guard blocks `/admin/dashboard` when not logged in

After Phase 2:
- [ ] InventoryService::reserveStock() correctly deducts from available, not stock
- [ ] Concurrent reservation test: simulate 2 requests for last item — only 1 should succeed
- [ ] CouponService rejects expired/maxed out codes

After Phase 3:
- [ ] Can create product with 3 variants
- [ ] Inventory adjusts and logs correctly
- [ ] Order status change triggers activity log entry
- [ ] Dashboard stats show correct numbers

---

## 19. COMMON PITFALLS — DO NOT DO THESE

- ❌ Do NOT store cart in database (use session)
- ❌ Do NOT deduct stock when order is placed (only reserve)
- ❌ Do NOT recalculate order total from current prices
- ❌ Do NOT use a single 'users' table for both admins and customers
- ❌ Do NOT delete inventory_transaction rows
- ❌ Do NOT allow negative stock_quantity (add DB check constraint)
- ❌ Do NOT use sync() on product_categories without detaching first (data loss risk)
- ❌ Do NOT forget to update reserved_quantity when cancelling orders
- ❌ Do NOT hardcode currency — use config('shop.currency') = 'BDT'

---

## 20. CONFIG FILE

Create `config/shop.php`:
```php
return [
    'name'              => env('SHOP_NAME', 'My Store'),
    'currency'          => env('SHOP_CURRENCY', 'BDT'),
    'currency_symbol'   => env('SHOP_CURRENCY_SYMBOL', '৳'),
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 5),
    'reservation_ttl_minutes' => [
        'online' => 15,
        'cod'    => 1440,  // 24 hours
    ],
    'order_number_prefix' => 'ORD',
    'per_page'          => 20,
];
```

---

## 21. BLADE COMPONENTS (Props Contract)

Anonymous components in `resources/views/components/`. Each defines props via `@props([...])`. Reuse these everywhere — do not inline duplicate markup.

### admin/stat-card.blade.php
```php
@props([
  'title',                 // string
  'value',                 // string|int (pre-formatted, e.g. "৳12,400")
  'change' => null,        // string|null e.g. "+12%"
  'changeDirection' => 'up', // up|down → green/red
  'icon',                  // heroicon name string
  'color' => 'gray',       // tailwind color key for icon bg
])
// Layout: icon tile left, title (muted) + value (bold) right, change pill below.
```

### admin/data-table.blade.php
```php
@props([
  'headers',      // array<string>  column labels
  'rows' => null, // optional; usually use slot for custom cells
  'empty' => 'No records found.', // empty-state text
])
// Renders <thead> from headers; body via {{ $slot }} (caller emits <tr>).
// Sticky header, zebra rows, responsive overflow-x wrapper.
```

### admin/status-badge.blade.php
```php
@props([
  'label',   // string display text (e.g. "Shipped")
  'color',   // tailwind color key from Enum::color() (green/yellow/red/blue/gray)
])
// Renders rounded pill: bg-{color}-100 text-{color}-800.
// Feed it OrderStatus->label() and OrderStatus->color().
```

### admin/pagination.blade.php
```php
@props(['paginator']) // LengthAwarePaginator
// Wrapper over {{ $paginator->links() }} styled to match admin theme.
// Shows "Showing X–Y of Z" summary left, page links right.
```

### admin/confirm-modal.blade.php
```php
@props([
  'id',                       // unique modal id (Alpine x-data scope)
  'title',
  'message',
  'confirmLabel' => 'Confirm',
  'confirmColor' => 'red',
  'action',                   // form action URL
  'method' => 'DELETE',       // spoofed via @method
])
// Alpine-driven. Trigger: x-on:click="$dispatch('open-modal','{{ $id }}')".
// Used for delete/cancel confirmations. Posts a form on confirm.
```

### ui/alert.blade.php
```php
@props([
  'type' => 'info',  // success|error|warning|info → color + icon
  'dismissible' => true,
])
// Reads from session flash in layout:
//   @if(session('success')) <x-ui.alert type="success">{{ session('success') }}</x-ui.alert> @endif
// Alpine x-show for dismiss; auto-hide after 4s optional.
```

### ui/breadcrumb.blade.php
```php
@props(['items']) // array<array{label:string, url?:string}>
// Last item = current (no link, muted). Chevron separators.
```

---

## 22. FACTORIES & TEST DATA

Every model gets a factory. Tests use factories + custom states — never manual `new Model()`. Faker via `fake()`.

### Factories to create
```
AdminFactory            superadmin() / manager() / viewer() states
UserFactory             guest() state (password null), withAddress()
CategoryFactory         parent() / child(Category $parent) states
ProductFactory          active() / featured() / withVariants(int $n) states
ProductVariantFactory   inStock(int) / outOfStock() / reserved(int) states
CouponFactory           percentage() / fixed() / expired() / maxedOut() states
OrderFactory            pending()/confirmed()/shipped()/delivered()/cancelled()
                        forGuest() / withItems(int $n) states
```

### Key state examples
```php
// ProductVariantFactory
public function outOfStock(): static {
    return $this->state(fn () => ['stock_quantity' => 0, 'reserved_quantity' => 0]);
}
public function reserved(int $qty): static {
    return $this->state(fn (array $attrs) => ['reserved_quantity' => $qty]);
}

// CouponFactory
public function expired(): static {
    return $this->state(fn () => ['valid_until' => now()->subDay()]);
}
public function maxedOut(): static {
    return $this->state(fn (array $a) => ['max_uses' => 5, 'used_count' => 5]);
}

// OrderFactory
public function delivered(): static {
    return $this->state(fn () => ['status' => 'delivered', 'delivered_at' => now()]);
}
```

### Test data rules
```
- Inventory/order tests: build via OrderFactory->withItems() + ProductVariantFactory->inStock().
- Concurrency test (last-item): variant inStock(1), fire reserveStock() twice → second throws InsufficientStockException.
- Coupon tests: use expired()/maxedOut() states, assert CouponException message.
- Never seed production seeders inside tests; use RefreshDatabase + factories.
- Immutable tables (inventory_transactions, admin_activity_logs): assert rows EXIST, never updated.
```

---

*End of Blueprint. Start with Phase 1 and work sequentially.*
