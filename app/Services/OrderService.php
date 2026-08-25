<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(private PaymentService $paymentService) {}

    public function store(array $data): ?string
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = auth()->id();
            $order = Order::create($data);
            $transaction = $order->transaction()->create();
            $payment = $this->paymentService->createPayment($data['amount'], $data['description'], [
                'transaction_id' => $transaction->id,
                'return_url' => route('billing.processing', ['orderId' => $order->id]),
            ]);
            Log::info('payment', [$payment]);
            $transaction->gateway_payment_id = $payment->id;
            $transaction->save();
            DB::commit();

            return $payment->getConfirmation()->getConfirmationUrl();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }
}
