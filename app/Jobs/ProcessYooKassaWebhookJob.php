<?php

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\Transaction;
use App\Models\TransactionWebhookEvent;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([1, 5, 10, 15, 20])]
class ProcessYooKassaWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $payload,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(YooKassaTransactionService $paymentService): void
    {
        $paymentService->callback($this->payload, $this->payload['object']['metadata']['transaction_id']);
    }
    public function failed(Throwable $exception): void
    {
        $transactionId = $this->payload['object']['metadata']['transaction_id'];
        Log::channel('payments')->critical(
            "КРИТИЧЕСКИЙ СБОЙ ОЧЕРЕДИ: Job [ProcessYookassaWebhookJob] окончательно провалил обработку платежа! " .
            "Внутренний ULID транзакции: {$transactionId}. Ошибка: " . $exception->getMessage()
        );

        $gatewayPaymentId = $this->payload['object']['id'] ?? null;
        $event = $this->payload['event'] ?? null;

        if ($gatewayPaymentId && $event) {
            TransactionWebhookEvent::where('gateway_payment_id', $gatewayPaymentId)
                ->where('event_type', $event)
                ->update([
                    'processing_status' => WebhookEventStatus::FAILED->value
                ]);
        }
    }
}
