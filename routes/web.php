<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OffersController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController as StoreProductController;
use App\Http\Controllers\Storefront\RestockRequestController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\StoreOrderController;
use App\Http\Controllers\Storefront\SubscriberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public)
|--------------------------------------------------------------------------
*/
Route::name('store.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/discover', [HomeController::class, 'discover'])->middleware('throttle:60,1')->name('discover');

    Route::get('/offers', [OffersController::class, 'index'])->name('offers');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('page');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
    Route::post('/subscribe', [SubscriberController::class, 'store'])->middleware('throttle:10,1')->name('subscribe');

    Route::get('/shop', [ShopController::class, 'index'])->name('shop');
    Route::get('/shop/{category:slug}', [ShopController::class, 'index'])->name('shop.category');
    Route::get('/product/{product:slug}', [StoreProductController::class, 'show'])->name('product');
    Route::get('/product/{product:slug}/reviews', [StoreProductController::class, 'reviews'])->name('product.reviews');
    Route::post('/product/{product:slug}/reviews', [StoreProductController::class, 'storeReview'])
        ->middleware('auth:web')->name('product.review');
    Route::post('/product/{product:slug}/restock', [RestockRequestController::class, 'store'])
        ->middleware('throttle:10,1')->name('product.restock');

    // Cart (session)
    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/order/{order:order_number}/confirmation', [StoreOrderController::class, 'confirmation'])->name('order.confirmation');

    // Guest order tracking (public)
    Route::get('/track', [StoreOrderController::class, 'trackForm'])->name('track');
    Route::post('/track', [StoreOrderController::class, 'track'])->middleware('throttle:20,1')->name('track.lookup');
    Route::post('/track/{order:order_number}/cancel', [StoreOrderController::class, 'cancel'])->name('track.cancel');

    // Account (auth: web guard)
    Route::middleware('auth:web')->prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{order:order_number}', [AccountController::class, 'show'])->name('orders.show');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');

        Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
        Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
        Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
        Route::patch('/addresses/{address}/default', [AccountController::class, 'defaultAddress'])->name('addresses.default');
    });
});

/*
|--------------------------------------------------------------------------
| Customer auth (web guard)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register']);
});
Route::post('/logout', [CustomerAuthController::class, 'logout'])->middleware('auth:web')->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Auth (guest)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin Panel (authenticated)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['admin.auth', 'admin.perm'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products + Variants
    Route::patch('products/{product}/featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
    Route::resource('products', ProductController::class);
    Route::resource('products.variants', VariantController::class)->shallow();

    // Categories
    // Media library (all images; also feeds the picker modal as JSON)
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('media/{key}', [MediaController::class, 'destroy'])->where('key', 'media:[0-9]+')->name('media.destroy');

    Route::patch('categories/{category}/home', [CategoryController::class, 'toggleHome'])->name('categories.toggle-home');
    Route::resource('categories', CategoryController::class);

    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('orders/{order}/details', [OrderController::class, 'updateDetails'])->name('orders.details');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');

    // POS (point of sale)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('pos/{sale}', [PosController::class, 'show'])->name('pos.show');

    // Expenses
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);

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

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::put('settings/pixels', [SettingsController::class, 'updatePixels'])->name('settings.pixels');
    Route::put('settings/courier', [SettingsController::class, 'updateCourier'])->name('settings.courier');
    Route::put('settings/campaign', [SettingsController::class, 'updateCampaign'])->name('settings.campaign');
});
