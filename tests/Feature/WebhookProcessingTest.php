<?php

use App\Enums\WebhookEventStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\TransactionWebhookEvent;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Transactions\YooKassaTransactionService;

it('marks unknown webhook events as skipped instead of throwing', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);
    $transaction = Transaction::create(['order_id' => $order->id]);
    $payload = [
        'event' => 'payment.unknown',
        'object' => [
            'id' => 'gateway-payment-unknown',
            'metadata' => ['transaction_id' => $transaction->id],
        ],
    ];

    (new YooKassaTransactionService(Mockery::mock(PaymentGatewayInterface::class)))
        ->callback($payload, $transaction->id);

    expect(TransactionWebhookEvent::query()->first()->processing_status)
        ->toBe(WebhookEventStatus::SKIPPED->value);
});
