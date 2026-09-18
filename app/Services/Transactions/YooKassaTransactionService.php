<?php

namespace App\Services\Transactions;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Enums\WebhookEventStatus;
use App\Models\Transaction;
use App\Models\TransactionWebhookEvent;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationRefundSucceeded;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Refund\RefundInterface;
use YooKassa\Model\Refund\RefundStatus;

class YooKassaTransactionService
{
    public function __construct(private PaymentGatewayInterface $gateway)
    {

    }

    public function callback(array $payload, string $transactionId): void
    {
        $paymentObject = $this->getPaymentObjectByNotification($payload)->getObject();

        $webhookEvent = $this->createWebhookEvent($payload, $transactionId);
        if (!$webhookEvent) {
            return;
        }
        try {
            switch ($payload['event']) {
                case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                    $this->ifWaitingForCapture($paymentObject, $transactionId);
                    break;
                case NotificationEventType::PAYMENT_SUCCEEDED:
                    $this->ifSucceeded($paymentObject, $transactionId);
                    break;
                case NotificationEventType::PAYMENT_CANCELED:
                    $this->ifCancelled($paymentObject, $transactionId);
                    break;
                case NotificationEventType::REFUND_SUCCEEDED:
                    $this->ifRefundSucceeded($paymentObject, $transactionId);
                    break;
                default:
                    Log::channel('payments')->warning("Получено необрабатываемое или новое событие от ЮKassa: {$payload['event']}. Транзакция: {$transactionId}");
                    $webhookEvent->update([
                        'processing_status' => WebhookEventStatus::SKIPPED,
                        'processed_at' => now(),
                    ]);
                    return;
            }

            $webhookEvent->update([
                'processing_status' => WebhookEventStatus::PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (Exception $exception) {
            $webhookEvent->update([
                'processing_status' => WebhookEventStatus::FAILED,
                'processed_at' => now(),
            ]);
            throw $exception;
        }

    }

    private function getPaymentObjectByNotification(array $payload): NotificationCanceled|NotificationWaitingForCapture|NotificationRefundSucceeded|NotificationSucceeded
    {
        return match ($payload['event']) {
            NotificationEventType::PAYMENT_SUCCEEDED => new NotificationSucceeded($payload),
            NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE => new NotificationWaitingForCapture($payload),
            NotificationEventType::PAYMENT_CANCELED => new NotificationCanceled($payload),
            NotificationEventType::REFUND_SUCCEEDED => new NotificationRefundSucceeded($payload),
        };
    }

    private function ifRefundSucceeded(RefundInterface $refundObject, string $transactionId): void
    {
        DB::transaction(function () use ($refundObject, $transactionId) {
            if (isset($refundObject->status) && $refundObject->status === RefundStatus::SUCCEEDED) {
                $lockedTransaction = Transaction::query()->lockForUpdate()->findOrFail($transactionId);

                Log::channel('payments')->info("Возврат оформлен успешно. Заказ {$lockedTransaction->order_id} уже выполнен. Транзакция is refunded");
                if ($lockedTransaction->status !== TransactionStatus::REFUNDED->value) {
                    $lockedTransaction->update([
                        'status' => TransactionStatus::REFUNDED->value,
                    ]);
                }
            }
        });
    }

    private function ifSucceeded(PaymentInterface $paymentObject, string $transactionId): void
    {
        DB::transaction(function () use ($paymentObject, $transactionId) {
            if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::SUCCEEDED->value) {
                $lockedTransaction = Transaction::query()->lockForUpdate()->findOrFail($transactionId);
                $order = $lockedTransaction->order()->lockForUpdate()->firstOrFail();

                if ($lockedTransaction->status === TransactionStatus::SUCCEEDED->value) {
                    return;
                }

                if ($order->status === OrderStatus::COMPLETED->value) {
                    Log::channel('payments')->alert("ДВОЙНАЯ ОПЛАТА: Заказ {$order->id} уже выполнен! Транзакция {$lockedTransaction->id} избыточна.");
                    DB::afterCommit(function () use ($lockedTransaction) {
                        $this->gateway->refund($lockedTransaction);
                    });

                    return;
                }

                if ($paymentObject->paid === true) {
                    $lockedTransaction->update([
                        'status' => TransactionStatus::SUCCEEDED,
                        'payment_method' => $paymentObject->payment_method->type,
                    ]);
                    $order->update([
                        'status' => OrderStatus::COMPLETED,
                    ]);
                }

            }
        });
    }

    private function ifWaitingForCapture(PaymentInterface $paymentObject, string $transactionId): void
    {
        $idempotenceKey = 'capture_' . $transactionId;
        $localTransaction = Transaction::query()->find($transactionId);

        if ($localTransaction && $localTransaction->status === TransactionStatus::SUCCEEDED->value) {
            return;
        }

        if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::WAITING_FOR_CAPTURE->value) {
            $this->gateway->getClient()->capturePayment([
                'amount' => $paymentObject->amount,
            ], $paymentObject->id, $idempotenceKey);
        }
    }

    private function ifCancelled(PaymentInterface $paymentObject, string $transactionId)
    {
        DB::transaction(function () use ($paymentObject, $transactionId) {
            if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::CANCELED->value) {
                $lockedTransaction = Transaction::query()->lockForUpdate()->findOrFail($transactionId);
                $order = $lockedTransaction->order()->lockForUpdate()->firstOrFail();

                $lockedTransaction->update([
                    'status' => TransactionStatus::CANCELED->value,
                    'cancellation_details' => $paymentObject->cancellationDetails?->toArray(),
                ]);
                $order->update([
                    'status' => OrderStatus::FAILED,
                ]);

            }
        });
    }

    private function createWebhookEvent(array $payload, string $transactionId): ?TransactionWebhookEvent
    {
        try {
            DB::beginTransaction();
            $gatewayPaymentId = $payload['object']['id'];
            $event = $payload['event'];
            /** @var TransactionWebhookEvent $webhookEvent */
            $webhookEvent = TransactionWebhookEvent::query()
                ->where('gateway_payment_id', $gatewayPaymentId)
                ->where('event_type', $event)
                ->lockForUpdate()
                ->first();
            if ($webhookEvent) {
                if ($webhookEvent->processing_status === WebhookEventStatus::PROCESSED->value) {
                    DB::commit();
                    Log::channel('payments')->info(
                        "Идемпотентность: Вебхук ЮKassa уже был успешно обработан. Пропускаем. ID: {$gatewayPaymentId} EVENT: {$event}"
                    );
                    return null;
                }
                if ($webhookEvent->processing_status === WebhookEventStatus::PROCESSING->value) {
                    $isLeaseExpired = $webhookEvent->created_at->addMinutes(5)->isPast();

                    if (!$isLeaseExpired) {
                        DB::rollBack();
                        throw new Exception("Параллельная обработка события. Запрос холдирован в очереди.");
                    }
                    Log::channel('payments')->warning("Обнаружен зависший поток обработки вебхука ID: {$gatewayPaymentId}. Перехватываем управление.");
                }
                // Если статус был FAILED или PROCESSING (с истекшим таймаутом),
                // мы РАЗРЕШАЕМ повторную обработку! Переводим статус обратно в PROCESSING
                $webhookEvent->update([
                    'processing_status' => WebhookEventStatus::PROCESSING->value,
                ]);
                DB::commit();
            } else {
                $webhookEvent = TransactionWebhookEvent::create([
                    'gateway_payment_id' => $payload['object']['id'],
                    'event_type' => $payload['event'],
                    'transaction_id' => $transactionId,
                    'processing_status' => WebhookEventStatus::PROCESSING,
                ]);
                DB::commit();
            }

            return $webhookEvent;
        } catch (QueryException $exception) {
            DB::rollBack();
            if ($exception->getCode() === '23505' || $exception->getCode() === '23000') {
                Log::channel('payments')->warning(
                    "Идемпотентность (PostgreSQL/Manual): Дубликат хука заблокирован. ID: {$payload['object']['id']}, Event: {$payload['event']}"
                );
            }
            throw $exception;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
