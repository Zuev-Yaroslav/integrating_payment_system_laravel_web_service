<?php

use App\Http\Controllers\BillingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::match(['get', 'post'], '/payments/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');

