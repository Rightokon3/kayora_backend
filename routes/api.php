<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverProfileController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\DriverStatusController;
use App\Http\Controllers\Api\DriverLocationController;
use App\Http\Controllers\Api\DriverTaskController;
use App\Http\Controllers\Api\DriverStatsController;

/* ============================================================
   CUSTOMER / USER APP — unprefixed, its own auth guard via User model
============================================================ */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/shop/info', [App\Http\Controllers\Api\DistributorController::class, 'getShopInfo']);
Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user/profile/update', [App\Http\Controllers\Api\UserSettingsController::class, 'updateProfile']);
    Route::get('/user/settings', [App\Http\Controllers\Api\UserSettingsController::class, 'getSettings']);
    Route::patch('/user/settings/toggle', [App\Http\Controllers\Api\UserSettingsController::class, 'togglePreference']);
    Route::post('/user/inactivate-request', [App\Http\Controllers\Api\UserSettingsController::class, 'requestInactivation']);

    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\Api\AddressController::class, 'store']);

    Route::get('/cart', [App\Http\Controllers\Api\CartOrderController::class, 'getCart']);
    Route::post('/cart/update', [App\Http\Controllers\Api\CartOrderController::class, 'updateQuantity']);
    Route::delete('/cart/remove/{productId}', [App\Http\Controllers\Api\CartOrderController::class, 'removeFromCart']);
    Route::post('/orders/place', [App\Http\Controllers\Api\CartOrderController::class, 'placeOrder']);

    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::get('/orders/{id}/track', [App\Http\Controllers\Api\OrderController::class, 'track']);

    Route::post('/distributor/apply', [App\Http\Controllers\Api\DistributorController::class, 'submitApplication']);
});

/* ============================================================
   DRIVER APP — everything under /api/driver/*, own auth guard
   via the Driver model. No overlap with the customer routes
   above: /login is customers, /driver/login is drivers.
============================================================ */
Route::prefix('driver')->group(function () {
    Route::post('/login', [DriverAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout']);
        Route::get('/me', [DriverAuthController::class, 'me']);

        // This is what account.tsx's GET request hits — see below.
        Route::get('/profile', [DriverProfileController::class, 'show']);

        // Admin fills these in; driver can only view most of it (enforce
        // that distinction inside the controller, not just the route).
        Route::put('/profile', [DriverProfileController::class, 'update']);

        Route::get('/vehicle', [VehicleController::class, 'myVehicle']);
        Route::post('/status', [DriverStatusController::class, 'update']);
Route::post('/location', [DriverLocationController::class, 'update']);

Route::get('/tasks/today', [DriverTaskController::class, 'today']);
Route::get('/tasks/{order}', [DriverTaskController::class, 'show']);
Route::post('/tasks/{order}/start', [DriverTaskController::class, 'start']);
Route::post('/tasks/{order}/complete', [DriverTaskController::class, 'complete']);

Route::get('/stats/today', [DriverStatsController::class, 'today']);
    });
    
});