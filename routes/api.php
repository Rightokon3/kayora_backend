<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

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