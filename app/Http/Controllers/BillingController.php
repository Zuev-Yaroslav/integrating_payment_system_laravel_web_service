<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingException;
use App\Http\Requests\Billing\InitiateRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BillingController extends Controller
{
    public function __construct(private BillingService $billingService) {}

    /**
     * Страница выбора тарифа и инициации оплаты
     */
    public function index(Request $request): Response
    {
        if ($request->query('error')) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $request->query('error')]);
        }

        return Inertia::render('Billing/Index', [
            'plans' => $this->billingService->plans(),
//            'error' => $request->query('error'),
        ]);
    }

    /**
     * История заказов и диагностический поток платежных событий.
     */
    public function dashboard(): Response
    {
        return Inertia::render('Billing/Dashboard', [
            'orders' => OrderResource::collection($this->billingService->dashboard())->resolve(),
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
     * Повторная попытка оплаты для существующего заказа.
     */
    public function retry(string $orderId): HttpResponse
    {
        return Inertia::location($this->billingService->retry($orderId));
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
    public function failed(Request $request, string $orderId): Response
    {
        return Inertia::render('Billing/Failed', [
            'order' => OrderResource::make($this->billingService->failed($orderId))->resolve(),
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
