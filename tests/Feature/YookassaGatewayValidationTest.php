<?php

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payments\Gateways\YookassaGateway;
use Illuminate\Http\Request;

function yookassaPayload(Transaction $transaction, array $overrides = []): array
{
    return array_replace_recursive([
        'event' => 'payment.succeeded',
        'object' => [
            'id' => $transaction->gateway_payment_id,
            'metadata' => ['transaction_id' => $transaction->id],
            'amount' => ['value' => '100.00', 'currency' => 'RUB'],
        ],
    ], $overrides);
}

it('accepts a webhook whose transaction, amount and currency match the order', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'gateway_payment_id' => 'gateway-payment-1',
    ]);

    $request = Request::create('/payments/callback', 'POST', yookassaPayload($transaction));

    expect((new YookassaGateway)->validateWebhook($request))->toBeTrue();
});

it('rejects a webhook when payment identity, amount or currency does not match', function (array $overrides) {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'gateway_payment_id' => 'gateway-payment-1',
    ]);

    $request = Request::create('/payments/callback', 'POST', yookassaPayload($transaction, $overrides));

    expect((new YookassaGateway)->validateWebhook($request))->toBeFalse();
})->with([
    [['object' => ['id' => 'another-payment']]],
    [['object' => ['amount' => ['value' => '99.99']]]],
    [['object' => ['amount' => ['currency' => 'USD']]]],
]);

it('uses the original payment id when validating a refund webhook', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'gateway_payment_id' => 'gateway-payment-1',
    ]);

    $request = Request::create('/payments/callback', 'POST', yookassaPayload($transaction, [
        'event' => 'refund.succeeded',
        'object' => ['id' => 'refund-1', 'payment_id' => 'gateway-payment-1'],
    ]));

    expect((new YookassaGateway)->validateWebhook($request))->toBeTrue();
});
