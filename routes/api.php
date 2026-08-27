<?php

use App\Http\Controllers\BillingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/payments/callback', [\App\Http\Controllers\PaymentWebhookController::class, 'callback'])->name('payment.callback');

