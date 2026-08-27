<?php

namespace App\Services\Payments\Contracts;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use YooKassa\Client;

interface PaymentGatewayInterface
{
    public function getClient(): Client;

    public function createPayment(Order $order, Transaction $transaction, array $options): string;

    public function validateWebhook(Request $request): bool;
}
