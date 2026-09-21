<?php

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Gateways\YookassaGateway;
use App\Services\Transactions\YooKassaTransactionService;

it('creates a pending order and transaction before starting payment', function () {
    $user = User::factory()->create();
    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->andReturn('https://pay.example/checkout');

    $this->app->instance(YookassaGateway::class, $gateway);
    $this->app->instance(YooKassaTransactionService::class, Mockery::mock(YooKassaTransactionService::class));

    $response = $this->actingAs($user)->postJson(route('payment.initiate'), [
        'plan_id' => 'basic',
    ]);

    $response->assertOk()->assertJson(['redirect_url' => 'https://pay.example/checkout']);
    expect(Order::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(Transaction::query()->count())->toBe(1);
    expect(Order::first()->status)->toBe(OrderStatus::PENDING->value);
    expect(Transaction::first()->status)->toBe(TransactionStatus::PENDING->value);
});

it('does not expose another users order through status or retry endpoints', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $order = Order::create([
        'user_id' => $owner->id,
        'amount' => 100,
        'description' => 'Тариф "Базовый"',
    ]);

    $this->actingAs($attacker)->getJson("/orders/{$order->id}/status")->assertNotFound();
    $this->actingAs($attacker)->post(route('payment.retry', $order->id))->assertStatus(400);
});
