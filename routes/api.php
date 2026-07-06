<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserSettingsController;
use App\Http\Controllers\Api\CartOrderController;
use App\Http\Controllers\Api\DistributorController;
use App\Http\Controllers\Api\DriverDashboardController;
use App\Http\Controllers\Api\DriverTaskController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);

// Guarded Protected System App Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartOrderController::class, 'getCart']);
    Route::post('/cart/update', [CartOrderController::class, 'updateQuantity']);
    Route::delete('/cart/remove/{productId}', [CartOrderController::class, 'removeFromCart']);
    Route::post('/orders/place', [CartOrderController::class, 'placeOrder']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/orders/{id}/track', [OrderController::class, 'track']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/settings', [UserSettingsController::class, 'getSettings']);
    Route::put('/user/profile/update', [UserSettingsController::class, 'updateProfile']);
    Route::patch('/user/settings/toggle', [UserSettingsController::class, 'togglePreference']);
    Route::post('/user/inactivate-request', [UserSettingsController::class, 'requestInactivation']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [AuthController::class, 'profile']);
});


Route::get('/shop/info', [DistributorController::class, 'getShopInfo']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/distributor/apply', [DistributorController::class, 'submitApplication']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/driver/dashboard', [DriverDashboardController::class, 'getDashboardStats']);
    Route::post('/driver/location-update', [DriverDashboardController::class, 'updateDriverLocation']);
});
Route::prefix('driver')->group(function () {
    // Add this route to handle your dashboard tasks query
    Route::get('/tasks', [DriverTaskController::class, 'index']);
    
    // Ensure your other existing dashboard endpoints are here too
    Route::get('/dashboard', [DriverDashboardController::class, 'index']);
});