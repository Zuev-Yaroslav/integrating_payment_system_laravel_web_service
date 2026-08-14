<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::match(['get', 'post'], '/payments/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth'])
    ->name('order.store');


require __DIR__.'/settings.php';
