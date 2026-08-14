<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private PaymentService $paymentService)
    {

    }

    public function store(array $data): ?string
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = auth()->id();
            $order = Order::create($data);
            $payment = $order->payment()->create();
            $link = $this->paymentService->createPayment($data['amount'], $data['description'], [
                'transaction_id' => $payment->id,
            ]);
            DB::commit();

            return $link;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }
}
