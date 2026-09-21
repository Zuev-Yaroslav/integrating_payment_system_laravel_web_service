<?php

use App\Jobs\ProcessYooKassaWebhookJob;
use App\Services\Payments\Gateways\YookassaGateway;
use Illuminate\Support\Facades\Queue;

it('accepts a validated webhook and queues processing', function () {
    Queue::fake();
    $gateway = Mockery::mock(YookassaGateway::class);
    $gateway->shouldReceive('validateWebhook')->once()->andReturnTrue();
    $this->app->instance(YookassaGateway::class, $gateway);

    $payload = [
        'event' => 'payment.succeeded',
        'object' => [
            'id' => 'gateway-payment-1',
            'metadata' => ['transaction_id' => '01H00000000000000000000000'],
            'amount' => ['value' => '100.00', 'currency' => 'RUB'],
        ],
    ];

    $response = $this->withoutMiddleware()
        ->postJson(route('payment.callback'), $payload);

    $response->assertOk()->assertJson(['status' => 'success']);
    Queue::assertPushed(ProcessYooKassaWebhookJob::class);
});

it('rejects a webhook with missing transaction metadata', function () {
    $gateway = Mockery::mock(YookassaGateway::class);
    $gateway->shouldReceive('validateWebhook')->once()->andReturnTrue();
    $this->app->instance(YookassaGateway::class, $gateway);

    $response = $this->withoutMiddleware()
        ->postJson(route('payment.callback'), [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'gateway-payment-1',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ],
        ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('object.metadata.transaction_id');
});
