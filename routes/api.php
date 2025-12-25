<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserDetailController;
use App\Http\Controllers\Api\ReportController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// User
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Address
Route::middleware('auth:sanctum')->group(function () {
    Route::get('addresses', [AddressController::class, 'index']); 
    Route::post('addresses', [AddressController::class, 'store']);
    Route::get('addresses/default', [AddressController::class, 'show']);
    Route::put('addresses/{id}', [AddressController::class, 'update']);
    Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
});

// Category
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

// Product
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/available', [ProductController::class, 'availableProducts']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

// Cart Item
// All routes require authentication (token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('cart-items', [CartItemController::class, 'index']);
    Route::post('cart-items', [CartItemController::class, 'store']);
    Route::put('cart-items/{id}', [CartItemController::class, 'update']);
    Route::delete('cart-items/{id}', [CartItemController::class, 'destroy']);
});
// Only admin users should access this
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('admin/cart-items', [CartItemController::class, 'adminIndex']); // Admin view all cart items
});

// Order
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
});

// Order Item
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/{orderId}/items', [OrderItemController::class, 'index']);
    Route::post('/order-items', [OrderItemController::class, 'store']);
    Route::get('/order-items/{id}', [OrderItemController::class, 'show']);
    Route::put('/order-items/{id}', [OrderItemController::class, 'update']);
    Route::delete('/order-items/{id}', [OrderItemController::class, 'destroy']);
});

// Review
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

// Wishlist
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
});

// Dashboard
Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);

// User Detail
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserDetailController::class, 'show']);
    Route::post('/me/update', [UserDetailController::class, 'update']);
});

// Report
Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('products/top-selling', [ReportController::class, 'topSellingProducts']);
    Route::get('products/least-selling', [ReportController::class, 'leastSellingProducts']);
    Route::get('products/revenue', [ReportController::class, 'productRevenue']);
    Route::get('products/stock', [ReportController::class, 'stockLevels']);
    Route::get('products/distribution', [ReportController::class, 'productDistribution']);
    Route::get('products/sales', [ReportController::class, 'productSales']);
});