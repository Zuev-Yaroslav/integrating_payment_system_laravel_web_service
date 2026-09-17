<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Transactions\YooKassaTransactionService;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private YooKassaTransactionService $paymentService,
        private PaymentGatewayInterface $paymentGateway,
    ) {}

    public function store(array $data): ?string
    {
        try {
            DB::beginTransaction();

            $data['user_id'] = auth()->id();
            $order = Order::create($data)->refresh();
            /** @var Transaction $transaction */
            $transaction = $order->transactions()->create();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        try {
            return $this->paymentGateway->createPayment($order, $transaction, [
                'return_url' => route('billing.processing', ['orderId' => $order->id]),
            ]);
        } catch (Exception $e) {
            $transaction->update(['status' => TransactionStatus::CANCELED]);
            throw $e;
        }
    }

    public function retry(Order $order): string
    {
        try {
            DB::beginTransaction();
            /** @var Transaction $transaction */
            $transaction = $order->transactions()->create();
            $order->update(['status' => OrderStatus::PENDING->value]);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        try {
            $link = $this->paymentGateway->createPayment($order, $transaction, [
                'return_url' => route('billing.processing', ['orderId' => $order->id]),
            ]);

            return $link;
        } catch (Exception $e) {
            $transaction->update(['status' => TransactionStatus::CANCELED]);
            throw $e;
        }
    }
}
