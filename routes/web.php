<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Minishop\Http\Controllers\Account\AddressController;
use Minishop\Http\Controllers\Account\DashboardController as AccountDashboardController;
use Minishop\Http\Controllers\Account\OrdersController as AccountOrdersController;
use Minishop\Http\Controllers\Account\PaymentController as AccountPaymentController;
use Minishop\Http\Controllers\Storefront\CartController;
use Minishop\Http\Controllers\Storefront\CheckoutController;
use Minishop\Http\Controllers\Storefront\CheckoutShippingRatesController;
use Minishop\Http\Controllers\Storefront\HomeController;
use Minishop\Http\Controllers\Storefront\PaymentController;
use Minishop\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use Minishop\Http\Controllers\Storefront\SupportChatController;
use Minishop\Http\Controllers\Storefront\TaxPreviewController;
use Minishop\Http\Controllers\Webhooks\WebhookController;
use Minishop\Rendering\StorefrontRendererContract;

Route::get('/', HomeController::class)->name('home');

Route::post('/chat', [SupportChatController::class, 'store'])->name('storefront.chat.store');

Route::prefix('products')->name('storefront.products.')->group(function () {
    Route::get('/', [StorefrontProductController::class, 'index'])->name('index');
    Route::get('/{product:slug}', [StorefrontProductController::class, 'show'])->name('show');
});

Route::prefix('cart')->name('storefront.cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/items', [CartController::class, 'addItem'])->name('items.store');
    Route::patch('/items/{cartItem}', [CartController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{cartItem}', [CartController::class, 'removeItem'])->name('items.destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('/sync', [CartController::class, 'sync'])->name('sync');
});

Route::prefix('checkout')->name('storefront.checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'create'])->name('create');
    Route::post('/shipping-rates', CheckoutShippingRatesController::class)
        ->middleware('throttle:20,1')
        ->name('shipping-rates');
    Route::post('/tax-preview', TaxPreviewController::class)
        ->middleware('throttle:30,1')
        ->name('tax-preview');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    Route::get('/pay/{order:order_number}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/pay/{order:order_number}/stripe', [PaymentController::class, 'stripeIntent'])->name('payment.stripe');
});

// Webhook routes are called by payment providers (no browser session/CSRF token).
Route::withoutMiddleware(VerifyCsrfToken::class)->middleware('throttle:60,1')->group(function () {
    Route::post('/webhooks/stripe', fn (Request $r) => app(WebhookController::class)->handle($r, 'stripe'))->name('webhooks.stripe');
    Route::post('/webhooks/{gateway}', [WebhookController::class, 'handle'])->name('webhooks.gateway');
});

Route::get('/order-confirmation/{order}', [CheckoutController::class, 'confirmation'])
    ->name('storefront.order.confirmation');

// Storefront registration page
Route::get('/register/customer', function () {
    return app(StorefrontRendererContract::class)->render('storefront/auth/Register');
})->middleware('guest')->name('storefront.register');

// Customer account area
Route::middleware(['auth', 'verified', 'role:customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', AccountDashboardController::class)->name('dashboard');
    Route::get('/orders', [AccountOrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AccountOrdersController::class, 'show'])->name('orders.show');
    Route::get('/address', [AddressController::class, 'edit'])->name('address.edit');
    Route::put('/address', [AddressController::class, 'update'])->name('address.update');
    Route::get('/payment', [AccountPaymentController::class, 'index'])->name('payment.index');
});
