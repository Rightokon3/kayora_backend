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
use App\Http\Controllers\Api\CartOrderController;
use App\Http\Controllers\Api\DriverOrderController;
use App\Http\Controllers\Api\DriverDiscoveryController;
use App\Http\Controllers\Driver\DriverAccountController;
/* ============================================================
   CUSTOMER / USER APP — unprefixed, its own auth guard via User model
============================================================ */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/shop/info', [App\Http\Controllers\Api\DistributorController::class, 'getShopInfo']);
Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-profile', [AuthController::class, 'profile']);
    Route::put('/user/profile/update', [App\Http\Controllers\Api\UserSettingsController::class, 'updateProfile']);
    Route::get('/user/settings', [App\Http\Controllers\Api\UserSettingsController::class, 'getSettings']);
    Route::patch('/user/settings/toggle', [App\Http\Controllers\Api\UserSettingsController::class, 'togglePreference']);
    Route::post('/user/inactivate-request', [App\Http\Controllers\Api\UserSettingsController::class, 'requestInactivation']);

    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\Api\AddressController::class, 'store']);
    Route::get('/saved-addresses', [App\Http\Controllers\Api\AddressController::class, 'index']);

    Route::get('/cart', [App\Http\Controllers\Api\CartOrderController::class, 'getCart']);
    Route::post('/cart/add', [CartOrderController::class, 'addToCart']);
    Route::post('/cart/update', [App\Http\Controllers\Api\CartOrderController::class, 'updateQuantity']);
    Route::delete('/cart/remove/{productId}', [App\Http\Controllers\Api\CartOrderController::class, 'removeFromCart']);
    Route::post('/orders/place', [App\Http\Controllers\Api\CartOrderController::class, 'placeOrder']);

    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::get('/orders/{id}/track', [App\Http\Controllers\Api\OrderController::class, 'track']);

    Route::post('/distributor/apply', [App\Http\Controllers\Api\DistributorController::class, 'submitApplication']);

    // Customer-facing: used by my-cart.tsx to let the customer pick a
    // driver for ASAP delivery. Uses the customer's own auth:sanctum
    // guard — it must NOT live under the /driver prefix group below,
    // since that group is authenticated as a Driver, not a User.
    Route::get('/drivers/nearby', [DriverDiscoveryController::class, 'nearby']);
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

        // GET /me is served by DriverAccountController — it returns the
        // full account.tsx payload (driver + profile + vehicle + computed
        // stats). There used to be a second, earlier-registered
        // `Route::get('/me', [DriverAuthController::class, 'me'])` here —
        // Laravel matches static routes in registration order, so that
        // duplicate silently won the match every time and made this real
        // one unreachable. Removed; do not re-add a second GET /me.
        Route::get('/me', [DriverAccountController::class, 'show']);
        Route::patch('/me/profile', [DriverAccountController::class, 'updateProfile']);
        Route::patch('/me/duty-status', [DriverAccountController::class, 'updateDutyStatus']);

        // This is what account.tsx's GET request hits — see below.
        Route::get('/profile', [DriverProfileController::class, 'show']);

        // Admin fills these in; driver can only view most of it (enforce
        // that distinction inside the controller, not just the route).
        Route::put('/profile', [DriverProfileController::class, 'update']);

        Route::get('/vehicle', [VehicleController::class, 'myVehicle']);
        Route::post('/status', [DriverStatusController::class, 'update']);
        Route::post('/location', [DriverLocationController::class, 'update']);

        Route::get('/tasks/today', [DriverTaskController::class, 'today']);

        // IMPORTANT: this specific route MUST be registered before the
        // wildcard `/tasks/{order}` route directly below it. Laravel
        // matches routes in registration order, and `{order}` matches ANY
        // single path segment — including the literal word "performance".
        // With the wildcard registered first (as it originally was,
        // several lines below this), every request to
        // GET /driver/tasks/performance was being swallowed by
        // `/tasks/{order}` before it ever reached this route, with Laravel
        // trying to route-model-bind an Order with id "performance" —
        // which doesn't exist, hence the
        // "No query results for model [App\Models\Order] performance" 404.
        // Do not move this back below `/tasks/{order}`.
        Route::get('/tasks/performance', [DriverStatsController::class, 'performance']);

        Route::get('/tasks/{order}', [DriverTaskController::class, 'show']);
        Route::post('/tasks/{order}/start', [DriverTaskController::class, 'start']);
        Route::post('/tasks/{order}/complete', [DriverTaskController::class, 'complete']);

        Route::get('/stats/today', [DriverStatsController::class, 'today']);
        Route::get('/orders', [DriverOrderController::class, 'index']);
        Route::get('/orders/{order}', [DriverOrderController::class, 'show']);
        Route::post('/orders/{order}/accept', [DriverOrderController::class, 'accept']);
        Route::post('/orders/{order}/decline', [DriverOrderController::class, 'decline']);
        Route::post('/orders/{order}/start', [DriverOrderController::class, 'start']);
        Route::post('/orders/{order}/complete', [DriverOrderController::class, 'complete']);
        Route::get('/orders/{order}/track', [DriverOrderController::class, 'track']);
    });
});