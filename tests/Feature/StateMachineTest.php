<?php

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\OrderStateException;
use App\Exceptions\TransactionStateException;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;

it('prevents a completed order from being reopened', function () {
    $order = Order::create([
        'user_id' => User::factory()->create()->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
        'status' => OrderStatus::COMPLETED,
    ]);

    expect(fn () => $order->update(['status' => OrderStatus::PENDING]))
        ->toThrow(OrderStateException::class);
});

it('prevents a canceled transaction from becoming successful', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'status' => TransactionStatus::CANCELED,
    ]);

    expect(fn () => $transaction->update(['status' => TransactionStatus::SUCCEEDED]))
        ->toThrow(TransactionStateException::class);
});
