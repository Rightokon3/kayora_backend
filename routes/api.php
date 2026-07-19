<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\Admin\DashboardController;
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

/* ============================================================
   ADMIN PANEL — everything under /api/admin/*, own auth guard
   via the Admin model. Kept completely separate from both the
   customer group (unprefixed, User model) and the driver group
   (/api/driver/*, Driver model) above — no shared routes, no
   shared controllers. auth:sanctum alone would accept ANY valid
   token regardless of which model it belongs to, so every
   protected route below also runs through the `admin.guard`
   middleware (EnsureAdmin), which rejects the request unless the
   token's owner is actually an Admin instance — this is what
   stops a driver's or customer's token from being usable here.
============================================================ */
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'admin.guard'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/revenue-monthly', [DashboardController::class, 'monthlyRevenue']);
        Route::get('/dashboard/orders-weekly', [DashboardController::class, 'weeklyOrders']);
        Route::get('/dashboard/order-categories', [DashboardController::class, 'orderCategories']);
        Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
        Route::post('/dashboard/revenue', [DashboardController::class, 'storeRevenue']);

        // IMPORTANT: /customers/inactivation-requests must be registered
        // before /customers/{id} — same wildcard-ordering issue already
        // hit once with /tasks/performance vs /tasks/{order}. Without
        // this order, "inactivation-requests" would get swallowed as if
        // it were a literal {id} value.
        Route::get('/customers/inactivation-requests', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'inactivationRequests']);
        Route::delete('/customers/inactivation-requests/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'resolveInactivationRequest']);
        Route::get('/customers', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'index']);
        Route::delete('/customers/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'destroy']);

        // Future admin-panel endpoints (managing drivers, customers,
        // products, distributors, notifications, other admins, etc.) all
        // register here, under this same guarded group — never mixed
        // into the customer or driver groups above.
    });
});