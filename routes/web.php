<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    //    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Billing routes
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/initiate', [BillingController::class, 'initiate'])->name('payment.initiate');
    Route::get('/billing/processing/{orderId}', [BillingController::class, 'processing'])->name('billing.processing');
    Route::get('/billing/success/{orderId}', [BillingController::class, 'success'])->name('payment.success');
    Route::get('/billing/failed/{orderId}', [BillingController::class, 'failed'])->name('payment.failed');

});

Route::get('/orders/{orderId}/status', [BillingController::class, 'checkStatus']);

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth'])
    ->name('order.store');

require __DIR__.'/settings.php';
