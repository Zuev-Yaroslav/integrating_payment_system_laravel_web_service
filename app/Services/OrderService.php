<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(private TransactionService $paymentService) {}

    public function store(array $data): ?string
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = auth()->id();
            $order = Order::create($data);
            /** @var Transaction $transaction */
            $transaction = $order->transactions()->create();

            $gateway = PaymentGatewayFactory::make();

            $link = $gateway->createPayment($order, $transaction, [
                'transaction_id' => $transaction->id,
                'return_url' => route('billing.processing', ['orderId' => $order->id]),
            ]);

            DB::commit();

            return $link;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function retry(Order $order): string
    {
        DB::beginTransaction();

        try {
            /** @var Transaction $transaction */
            $transaction = $order->transactions()->create();
            $order->update(['status' => OrderStatus::PENDING->value]);

            $link = PaymentGatewayFactory::make()->createPayment($order, $transaction, [
                'transaction_id' => $transaction->id,
                'return_url' => route('billing.processing', ['orderId' => $order->id]),
            ]);

            DB::commit();

            return $link;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
