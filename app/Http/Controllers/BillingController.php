<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingException;
use App\Http\Requests\Billing\InitiateRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(private BillingService $billingService) {}

    /**
     * Страница выбора тарифа и инициации оплаты
     */
    public function index(): Response
    {
        return Inertia::render('Billing/Index', [
            'plans' => $this->billingService->plans(),
        ]);
    }

    /**
     * Инициация оплаты - создает заказ и редирект на YooKassa
     */
    public function initiate(InitiateRequest $request): JsonResponse
    {
        return response()->json([
            'redirect_url' => $this->billingService->initiate($request->validated()['plan_id']),
        ]);
    }

    /**
     * Страница обработки платежа (опрос статуса)
     */
    public function processing(string $orderId): Response
    {
        return Inertia::render('Billing/Processing', [
            'orderId' => $orderId,
        ]);
    }

    /**
     * Страница успешного платежа
     */
    public function success(string $orderId): Response
    {
        return Inertia::render('Billing/Success', [
            ...$this->billingService->successData($orderId),
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
    public function checkStatus(string $orderId): JsonResponse
    {
        return response()->json($this->billingService->status($orderId));
    }
}
