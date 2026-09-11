<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\CallbackRequest;
use App\Jobs\ProcessYooKassaWebhookJob;
use App\Models\Transaction;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct()
    {

    }

    public function callback(CallbackRequest $request)
    {
        $payload = $request->all();

        $eventId = $payload['object']['id'] ?? null;

        $lock = Cache::lock("yookassa_event:{$eventId}", 120);

        if (!$lock->get()) {
            Log::channel('payments')->warning("Контроллер: Повторный вебхук заблокирован. Event ID: {$eventId}");

            return response()->json(['status' => 'duplicate ignored'], 200);
        }

        ProcessYooKassaWebhookJob::dispatch($payload);

        return response()->json(['status' => 'success']);
    }
}
