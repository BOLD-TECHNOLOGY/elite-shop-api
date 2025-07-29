<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VendorOrderController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('super_admin')->group(function () {
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserDetails']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
    Route::get('/users-stats', [UserController::class, 'getUserStats']);
});

Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::apiResource('shops', ShopController::class);
    
    Route::get('/shops/{shop}/products', [ShopController::class, 'getProducts']);
    Route::post('/shops/{shop}/products', [ShopController::class, 'addProduct']);
    Route::put('/shops/{shop}/products/{product}', [ShopController::class, 'updateProduct']);
    Route::delete('/shops/{shop}/products/{product}', [ShopController::class, 'deleteProduct']);
    
    Route::apiResource('products', ProductController::class);

    Route::get('/orders', [VendorOrderController::class, 'index']);
    Route::put('/orders/{order}/status', [VendorOrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [VendorOrderController::class, 'destroy']);
});

Route::post('/upload', [UploadController::class, 'upload'])->middleware('auth:api');

Route::middleware(['auth:sanctum', 'role:blogger'])->prefix('blogger')->group(function () {
});

Route::middleware(['auth:sanctum', 'role:rider'])->prefix('rider')->group(function () {
});

Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:user'])->prefix('user')->group(function () {
});

Route::prefix('public')->group(function () {
    Route::get('/shops', [ShopController::class, 'publicIndex']);
    Route::get('/shops/{shop}', [ShopController::class, 'publicShow']);

    Route::get('/products', [ProductController::class, 'publicIndex']);
    
    Route::get('/products/latest', [ProductController::class, 'latest']);
    Route::get('/products/{product}', [ProductController::class, 'publicShow']);
});