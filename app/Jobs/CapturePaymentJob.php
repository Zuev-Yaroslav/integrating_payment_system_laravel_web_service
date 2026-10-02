<?php

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\TransactionWebhookEvent;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Payment\PaymentStatus;

#[Backoff([1, 5, 10, 15, 20])]
class CapturePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private PaymentInterface $paymentObject,
        private string $idempotenceKey,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewayInterface $gateway): void
    {
        $gateway->getClient()->capturePayment([
            'amount' => $this->paymentObject->amount,
        ], $this->paymentObject->id, $this->idempotenceKey);
    }

    public function failed(\Throwable $throwable, PaymentGatewayInterface $gateway): void
    {
        $localTransactionId = $this->paymentObject->getMetadata()->offsetGet('transaction_id');
        Log::channel('payments')->critical(
            "КРИТИЧЕСКИЙ СБОЙ ОЧЕРЕДИ: Job [CapturePaymentJob] окончательно провалил подтверждения платежа! " .
            "Внутренний ULID транзакции: {$localTransactionId}. Ошибка: " . $throwable->getMessage()
        );

        if ($this->paymentObject->status === PaymentStatus::WAITING_FOR_CAPTURE) {
            TransactionWebhookEvent::where('gateway_payment_id', $this->paymentObject->id)
                ->where('event_type', NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE)
                ->update([
                    'processing_status' => WebhookEventStatus::FAILED->value
                ]);
        }
    }
}
