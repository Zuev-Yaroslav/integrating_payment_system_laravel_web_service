<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BillingException;
use App\Models\Order;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use YooKassa\Model\Payment\PaymentStatus;

class BillingService
{
    public function __construct(private OrderService $orderService) {}

    /**
     * @return array<string, mixed>
     */
    public function plans(): array
    {
        return config('plans');
    }

    public function initiate(string $planId): string
    {
        $plan = config("plans.{$planId}");

        try {
            return $this->orderService->store([
                'amount' => $plan['price'],
                'description' => $plan['name'],
            ]);
        } catch (\Throwable $exception) {
            throw new BillingException("Не удалось инициировать платеж: {$exception->getMessage()}", 400);
        }
    }

    public function dashboard()
    {
        return Order::query()
            ->where('user_id', auth()->id())
            ->with('transactions')
            ->latest()
            ->get();
    }

    public function retry(string $orderId): string
    {
        try {
            $order = Order::query()
                ->whereKey($orderId)
                ->where('user_id', Auth::id())
                ->whereIn('status', [OrderStatus::PENDING->value, OrderStatus::FAILED->value])
                ->firstOrFail();

            return $this->orderService->retry($order);
        } catch (\Throwable $exception) {
            throw new BillingException("Не удалось повторить платёж платеж: {$exception->getMessage()}", 400);
        }

    }

    /**
     * @return array<string, string>
     */
    public function successData(string $orderId): array
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->status !== OrderStatus::COMPLETED->value) {
            throw new HttpResponseException(redirect()->route('billing.processing', $order->id));
        }

        return [
            'order' => $order,
            'plan_name' => $order ? $this->planName($order->description) : 'выбранный тариф',
        ];
    }

    public function failed(string $orderId): Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('user_id', Auth::id())
            ->whereIN('status', [OrderStatus::FAILED->value, OrderStatus::PENDING->value])
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    public function status(string $orderId): array
    {
        $order = Order::query()
            ->wherekey($orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return match ($order?->status) {
            OrderStatus::COMPLETED->value => [
                'status' => OrderStatus::COMPLETED->value,
                'order_id' => $order->id,
            ],
            OrderStatus::FAILED->value => [
                'status' => OrderStatus::FAILED->value,
                'error' => $transaction->error_message ?? 'Платеж отклонен',
            ],
            default => ['status' => OrderStatus::PENDING->value],
        };
    }

    private function planName(string $description): string
    {
        return [
            'Тариф "Базовый"' => 'Базовый',
            'Тариф "Про"' => 'Про',
            'Тариф "Бизнес"' => 'Бизнес',
        ][$description] ?? $description;
    }
}
