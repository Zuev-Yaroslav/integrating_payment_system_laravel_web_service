<?php

use App\Http\Controllers\BillingController;
use App\Http\Middleware\YookassaIpWhitelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/payments/callback', [\App\Http\Controllers\PaymentWebhookController::class, 'callback'])
    ->middleware([YookassaIpWhitelist::class, 'throttle:yookassa-webhook'])
    ->name('payment.callback');

