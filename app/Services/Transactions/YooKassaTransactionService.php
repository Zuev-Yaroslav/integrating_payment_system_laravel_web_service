<?php

namespace App\Services\Transactions;

use App\Enums\OrderStatus;
use App\Enums\Yookassa\YooKassaStatus;
use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationRefundSucceeded;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Payment\PaymentStatus;
use YooKassa\Model\Refund\RefundInterface;
use YooKassa\Model\Refund\RefundStatus;

class YooKassaTransactionService
{
    public function __construct(private PaymentGatewayInterface $gateway)
    {

    }

    public function callback(array $data): void
    {
        $paymentObject = $this->getPaymentObjectByNotification($data)->getObject();

        switch ($data['event']) {
            case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                $this->ifWaitingForCapture($paymentObject);
                $this->ifSucceeded($paymentObject);
                break;
            case NotificationEventType::PAYMENT_SUCCEEDED:
                $this->ifSucceeded($paymentObject);
                break;
            case NotificationEventType::PAYMENT_CANCELED:
                $this->ifCancelled($paymentObject);
                break;
            case NotificationEventType::REFUND_SUCCEEDED:
                $this->ifRefundSucceeded($paymentObject);
                break;
        }
    }

    private function getPaymentObjectByNotification(array $payload): NotificationCanceled|NotificationWaitingForCapture|NotificationRefundSucceeded|NotificationSucceeded
    {
        return match($payload['event']) {
            NotificationEventType::PAYMENT_SUCCEEDED => new NotificationSucceeded($payload),
            NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE => new NotificationWaitingForCapture($payload),
            NotificationEventType::PAYMENT_CANCELED => new NotificationCanceled($payload),
            NotificationEventType::REFUND_SUCCEEDED => new NotificationRefundSucceeded($payload),
        };
    }

    private function ifRefundSucceeded(RefundInterface $refundObject)
    {
        if (isset($refundObject->status) && $refundObject->status === RefundStatus::SUCCEEDED) {

            $metadata = $refundObject->metadata;
            if (isset($metadata->transaction_id)) {
                $transaction = Transaction::findOrFail($metadata->transaction_id);
                $order = $transaction->order;
                Log::channel('payments')->info("Возврат оформлен успешно. Заказ {$order->id} уже выполнен. Транзакция is refunded");
                if ($transaction->status !== YookassaStatus::REFUNDED->value) {
                    $transaction->update([
                        'status' => YooKassaStatus::REFUNDED->value,
                    ]);
                }
                if ($order->status !== OrderStatus::COMPLETED->value) {
                    $order->update([
                        'status' => OrderStatus::COMPLETED->value,
                    ]);
                }
            }

        }
    }

    private function ifSucceeded(PaymentInterface $paymentObject): void
    {
        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::SUCCEEDED)
        {
            $metadata = $paymentObject->metadata;
            if (isset($metadata->transaction_id)) {
                /** @var Transaction $transaction */
                $transaction = Transaction::findOrFail($metadata->transaction_id);
                $order = $transaction->order;
                if ($order->status === OrderStatus::COMPLETED->value) {
                    Log::channel('payments')->alert("ДВОЙНАЯ ОПЛАТА: Заказ {$order->id} уже выполнен! Транзакция {$transaction->id} избыточна.");

                    $this->gateway->refund($transaction);
                    return;
                }

                if ($paymentObject->paid === true) {
                    $transaction->update([
                        'status' => PaymentStatus::SUCCEEDED,
                        'payment_method' => $paymentObject->payment_method->type,
                    ]);
                    $transaction->order()->update([
                        'status' => OrderStatus::COMPLETED,
                    ]);
                }
            }

        }
    }

    private function ifWaitingForCapture(PaymentInterface &$paymentObject)
    {
        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::WAITING_FOR_CAPTURE) {
            $paymentObject = $this->gateway->getClient()->capturePayment([
                'amount' => $paymentObject->amount,
            ], $paymentObject->id, uniqid('', true));
        }
    }

    private function ifCancelled(PaymentInterface $paymentObject)
    {
        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::CANCELED) {
            if (isset($metadata->transaction_id)) {
                $transaction = Transaction::findOrFail($metadata->transaction_id);
                $transaction->update([
                    'status' => PaymentStatus::CANCELED,
                    'cancellation_details' => $paymentObject->cancellationDetails?->toArray(),
                ]);
                $transaction->order()->update([
                    'status' => OrderStatus::FAILED,
                ]);
            }

        }
    }
}
