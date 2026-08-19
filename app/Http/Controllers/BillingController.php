<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Страница выбора тарифа и инициации оплаты
     */
    public function index(): Response
    {
        $plans = config('plans');

        return Inertia::render('Billing/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Инициация оплаты - создает заказ и редирект на YooKassa
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|string|in:basic,pro,business',
        ]);

        $planId = $validated['plan_id'];

        $plan = config('plans.' . $planId);
        try {
            $confirmationUrl = $this->orderService->store([
                'amount' => $plan['price'],
                'description' => $plan['name'],
            ]);

            return response()->json([
                'redirect_url' => $confirmationUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Не удалось инициировать платеж. Попробуйте еще раз.',
            ], 422);
        }
    }

    /**
     * Страница обработки платежа (опрос статуса)
     */
    public function processing(Request $request): Response
    {
        $orderId = $request->query('order_id');

        return Inertia::render('Billing/Processing', [
            'orderId' => $orderId,
        ]);
    }

    /**
     * Страница успешного платежа
     */
    public function success(Request $request): Response
    {
        $orderId = $request->query('order_id');
        $order = null;
        $planName = 'выбранный тариф';

        if ($orderId) {
            $order = Order::where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();

            if ($order) {
                $planNames = [
                    'Тариф "Базовый"' => 'Базовый',
                    'Тариф "Про"' => 'Про',
                    'Тариф "Бизнес"' => 'Бизнес',
                ];
                $planName = $planNames[$order->description] ?? $order->description;
            }
        }

        return Inertia::render('Billing/Success', [
            'order_id' => $orderId,
            'plan_name' => $planName,
        ]);
    }

    /**
     * Страница ошибки при платеже
     */
    public function failed(Request $request): Response
    {
        return Inertia::render('Billing/Failed', [
            'error_message' => $request->query('error', 'Неизвестная ошибка'),
        ]);
    }

    /**
     * API endpoint для проверки статуса заказа
     */
    public function checkStatus(string $orderId)
    {
        $order = Order::with('payment')
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $payment = $order->payment;

        $status = $payment->status ?? 'created';

        // Если платеж успешен или ошибка, возвращаем результат
        if ($status === 'succeeded') {
            return response()->json([
                'status' => 'completed',
                'order_id' => $order->id,
            ]);
        } elseif ($status === 'failed') {
            return response()->json([
                'status' => 'failed',
                'error' => $payment->error_message ?? 'Платеж отклонен',
            ]);
        }

        // В противном случае платеж еще обрабатывается
        return response()->json([
            'status' => 'processing',
        ]);
    }
}
