<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Billing routes
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/initiate', [BillingController::class, 'initiate'])->name('payment.initiate');
    Route::get('/billing/processing', [BillingController::class, 'processing'])->name('billing.processing');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('payment.success');
    Route::get('/billing/failed', [BillingController::class, 'failed'])->name('payment.failed');

    // Payment status check API
    Route::get('/api/v1/orders/{orderId}/status', [BillingController::class, 'checkStatus']);
});

Route::match(['get', 'post'], '/payments/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth'])
    ->name('order.store');


require __DIR__.'/settings.php';
