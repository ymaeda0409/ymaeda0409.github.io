<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\SessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LanguageController;
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

// ACCOUNT (any authenticated user)
Route::middleware(['auth:sanctum', 'role:CUSTOMER,DRIVER,staff'])->group(function () {
    Route::get('account', [AccountController::class, 'show']);
    Route::put('account', [AccountController::class, 'update']);
    Route::put('account/language', [AccountController::class, 'updateLanguage']);
});

// CUSTOMER
Route::middleware(['auth:sanctum', 'role:CUSTOMER'])->group(function () {
    Route::apiResource('addresses', AddressController::class)->except('show');
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
});
