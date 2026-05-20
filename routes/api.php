<?php

use Illuminate\Support\Facades\Route;
use Minishop\Http\Controllers\Api\V1\AuthController;
use Minishop\Http\Controllers\Api\V1\CartController as ApiCartController;
use Minishop\Http\Controllers\Api\V1\CategoryController;
use Minishop\Http\Controllers\Api\V1\CouponController as ApiCouponController;
use Minishop\Http\Controllers\Api\V1\OrderController as ApiOrderController;
use Minishop\Http\Controllers\Api\V1\ProductController;
use Minishop\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::post('coupons/validate', [ApiCouponController::class, 'validate'])->name('coupons.validate');

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [ApiCartController::class, 'show'])->name('show');
        Route::post('/items', [ApiCartController::class, 'addItem'])->name('items.store');
        Route::patch('/items/{cartItem}', [ApiCartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{cartItem}', [ApiCartController::class, 'removeItem'])->name('items.destroy');
        Route::delete('/', [ApiCartController::class, 'clear'])->name('clear');
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('user', [UserController::class, 'show'])->name('user.show');

        Route::get('orders', [ApiOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [ApiOrderController::class, 'show'])->name('orders.show');
    });
});
