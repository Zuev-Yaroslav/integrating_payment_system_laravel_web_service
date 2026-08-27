<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Gateways\YookassaGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public static function make(): PaymentGatewayInterface
    {
        $driver = config('services.payment_gateway.driver', 'yookassa');

        return match ($driver) {
            'yookassa' => new YookassaGateway(),
            default => throw new InvalidArgumentException("Платежный драйвер [{$driver}] не поддерживается."),
        };
    }
}
