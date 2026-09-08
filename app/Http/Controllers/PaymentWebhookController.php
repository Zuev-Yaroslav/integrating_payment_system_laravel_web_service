<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\CallbackRequest;
use App\Http\Requests\StoreRequest;
use App\Jobs\ProccessYookassaWebhookJob;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(private YooKassaTransactionService $paymentService)
    {

    }

    public function callback(CallbackRequest $request)
    {
        $payload = $request->all();

        $eventId = $payload['object']['id'] ?? null;

        $lock = Cache::lock("yookassa_event:{$eventId}", 20);

        if (!$lock->get()) {
            Log::channel('payments')->warning("Контроллер: Повторный вебхук заблокирован. Event ID: {$eventId}");
            // Обязательно возвращаем 200, чтобы ЮKassa перестала слать этот хук
            return response()->json(['status' => 'duplicate ignored'], 200);
        }

        ProccessYookassaWebhookJob::dispatch($payload);

        return response()->json(['status' => 'success']);
    }
}
