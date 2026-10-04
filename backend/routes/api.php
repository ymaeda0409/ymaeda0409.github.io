<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\SessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\Driver\DriverController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Support\Facades\Route;

/*
| All routes run through SetLocale (api middleware group) and return the unified envelope.
*/

// AUTH
Route::prefix('auth')->group(function () {
    Route::post('send-otp', [OtpController::class, 'send'])->middleware('throttle:otp');
    Route::post('verify-otp', [OtpController::class, 'verify'])->middleware('throttle:otp');
    Route::post('login', [SessionController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [SessionController::class, 'logout']);
        Route::get('me', [SessionController::class, 'me']);
    });
});

// PUBLIC CATALOG
Route::get('languages', [LanguageController::class, 'index']);
Route::get('stores/available', [StoreController::class, 'available']);
Route::get('stores/{store}', [StoreController::class, 'show'])->whereNumber('store');
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show'])->whereNumber('product');
Route::post('orders/quote', [OrderController::class, 'quote'])->middleware('throttle:60,1');
Route::post('payments/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:120,1');

// ACCOUNT (any authenticated user)
Route::middleware(['auth:sanctum', 'role:CUSTOMER,DRIVER,staff'])->group(function () {
    Route::get('account', [AccountController::class, 'show']);
    Route::put('account', [AccountController::class, 'update']);
    Route::put('account/language', [AccountController::class, 'updateLanguage']);
    Route::post('devices', [DeviceController::class, 'store']);
    Route::delete('devices', [DeviceController::class, 'destroy']);
});

// CUSTOMER
Route::middleware(['auth:sanctum', 'role:CUSTOMER'])->group(function () {
    Route::apiResource('addresses', AddressController::class)->except('show');

    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order');
    Route::get('orders/{order}/tracking', [OrderController::class, 'tracking'])->whereNumber('order');

    Route::post('payments', [PaymentController::class, 'store'])->middleware('throttle:20,1');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->whereNumber('payment');
});

// DRIVER
Route::prefix('driver')->middleware(['auth:sanctum', 'role:DRIVER'])->group(function () {
    Route::get('me', [DriverController::class, 'me']);
    Route::post('online', [DriverController::class, 'online']);
    Route::post('offline', [DriverController::class, 'offline']);
    Route::post('location', [DriverController::class, 'location'])->middleware('throttle:120,1');
    Route::get('delivery-requests', [DriverController::class, 'requests']);
    Route::get('deliveries', [DriverController::class, 'index']);
    Route::prefix('deliveries/{order}')->whereNumber('order')->group(function () {
        Route::get('/', [DriverController::class, 'show']);
        Route::post('accept', [DriverController::class, 'accept']);
        Route::post('decline', [DriverController::class, 'decline']);
        Route::post('pickup', [DriverController::class, 'pickup']);
        Route::post('arrive', [DriverController::class, 'arrive']);
        // PIN guessing is limited per order as well (bento.dispatch.max_pin_attempts).
        Route::post('complete', [DriverController::class, 'complete'])->middleware('throttle:20,1');
        Route::post('fail', [DriverController::class, 'fail']);
    });
});

// ADMIN / BACK-OFFICE (permissions + tenant scope enforced by policies)
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'role:staff'])->group(function () {
    Route::apiResource('franchises', Admin\FranchiseController::class);
    Route::apiResource('stores', Admin\StoreController::class);
    Route::apiResource('kitchens', Admin\KitchenController::class);
    Route::apiResource('delivery-zones', Admin\DeliveryZoneController::class)->parameters(['delivery-zones' => 'delivery_zone']);
    Route::apiResource('categories', Admin\CategoryController::class);
    Route::apiResource('products', Admin\ProductController::class);
    Route::get('stores/{store}/products', [Admin\StoreProductController::class, 'index']);
    Route::put('stores/{store}/products/{product}', [Admin\StoreProductController::class, 'update'])->whereNumber('product');
    Route::apiResource('languages', Admin\LanguageController::class)->only(['index', 'store', 'update']);
    Route::get('audit-logs', [Admin\AuditLogController::class, 'index']);

    Route::get('drivers/live', [Admin\DriverController::class, 'live']);
    Route::apiResource('drivers', Admin\DriverController::class)->except('destroy');
    Route::get('dashboard', Admin\DashboardController::class);
    Route::get('sales', [Admin\SalesController::class, 'index']);
    Route::get('customers', [Admin\CustomerController::class, 'index']);
    Route::get('customers/{customer}', [Admin\CustomerController::class, 'show'])->whereNumber('customer');
    Route::get('staff', [Admin\StaffController::class, 'index']);
    Route::post('staff', [Admin\StaffController::class, 'store']);
    Route::get('staff/{staff}', [Admin\StaffController::class, 'show'])->whereNumber('staff');
    Route::put('staff/{staff}', [Admin\StaffController::class, 'update'])->whereNumber('staff');
    Route::get('settings', [Admin\SettingController::class, 'index']);
    Route::put('settings', [Admin\SettingController::class, 'update']);
    Route::get('translations', [Admin\TranslationController::class, 'summary']);
    Route::get('translations/{type}', [Admin\TranslationController::class, 'index']);
    Route::put('translations/{type}/{id}', [Admin\TranslationController::class, 'update'])->whereNumber('id');

    Route::get('notification-templates', [Admin\NotificationTemplateController::class, 'index']);
    Route::put('notification-templates/{notificationTemplate}', [Admin\NotificationTemplateController::class, 'update']);

    // Orders + kitchen board actions
    Route::get('orders', [Admin\OrderController::class, 'index']);
    Route::get('orders/{order}', [Admin\OrderController::class, 'show']);
    Route::post('orders/{order}/accept', [Admin\OrderController::class, 'accept']);
    Route::post('orders/{order}/start-cooking', [Admin\OrderController::class, 'startCooking']);
    Route::post('orders/{order}/ready', [Admin\OrderController::class, 'ready']);
    Route::post('orders/{order}/cancel', [Admin\OrderController::class, 'cancel']);
});
