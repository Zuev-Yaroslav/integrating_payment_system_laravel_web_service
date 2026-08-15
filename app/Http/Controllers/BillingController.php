<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
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
        $plans = [
            [
                'id' => 'basic',
                'name' => 'Базовый',
                'price' => 990,
                'currency' => 'RUB',
                'features' => [
                    'До 100 API запросов в месяц',
                    'Базовая поддержка',
                    'История последних 30 дней',
                ],
            ],
            [
                'id' => 'pro',
                'name' => 'Про',
                'price' => 2990,
                'currency' => 'RUB',
                'features' => [
                    'До 10,000 API запросов в месяц',
                    'Приоритетная поддержка',
                    'История последних 90 дней',
                    'Расширенная аналитика',
                ],
                'recommended' => true,
            ],
            [
                'id' => 'business',
                'name' => 'Бизнес',
                'price' => 9990,
                'currency' => 'RUB',
                'features' => [
                    'Неограниченные API запросы',
                    'Круглосуточная поддержка',
                    'Полная история',
                    'Расширенная аналитика',
                    'Выделенный аккаунт-менеджер',
                ],
            ],
        ];

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

        $planPrices = [
            'basic' => 990,
            'pro' => 2990,
            'business' => 9990,
        ];

        $planNames = [
            'basic' => 'Тариф "Базовый"',
            'pro' => 'Тариф "Про"',
            'business' => 'Тариф "Бизнес"',
        ];

        $planId = $validated['plan_id'];
        $amount = $planPrices[$planId];
        $description = $planNames[$planId];

        try {
            $confirmationUrl = $this->orderService->store([
                'amount' => $amount,
                'description' => $description,
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
