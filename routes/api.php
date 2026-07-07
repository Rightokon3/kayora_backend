<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// --- APP 1: CUSTOMER/USER ---
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::get('/login', function () {
    return response()->json(['message' => 'Please authenticate using POST /api/login'], 401);
})->name('login');
Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);

// Guarded Protected System App Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\Api\AddressController::class, 'store']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [App\Http\Controllers\Api\CartOrderController::class, 'getCart']);
    Route::post('/cart/update', [App\Http\Controllers\Api\CartOrderController::class, 'updateQuantity']);
    Route::delete('/cart/remove/{productId}', [App\Http\Controllers\Api\CartOrderController::class, 'removeFromCart']);
    Route::post('/orders/place', [App\Http\Controllers\Api\CartOrderController::class, 'placeOrder']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::get('/orders/{id}/track', [App\Http\Controllers\Api\OrderController::class, 'track']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/settings', [App\Http\Controllers\Api\UserSettingsController::class, 'getSettings']);
    Route::put('/user/profile/update', [App\Http\Controllers\Api\UserSettingsController::class, 'updateProfile']);
    Route::patch('/user/settings/toggle', [App\Http\Controllers\Api\UserSettingsController::class, 'togglePreference']);
    Route::post('/user/inactivate-request', [App\Http\Controllers\Api\UserSettingsController::class, 'requestInactivation']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [AuthController::class, 'profile']);
});


Route::get('/shop/info', [App\Http\Controllers\Api\DistributorController::class, 'getShopInfo']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/distributor/apply', [App\Http\Controllers\Api\DistributorController::class, 'submitApplication']);
});


});

