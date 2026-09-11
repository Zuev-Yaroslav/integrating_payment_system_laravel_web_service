<?php

namespace App\Services\Transactions;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
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

    public function callback(array $data, Transaction $transaction): void
    {
        $paymentObject = $this->getPaymentObjectByNotification($data)->getObject();

        switch ($data['event']) {
            case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                $this->ifWaitingForCapture($paymentObject);
                $this->ifSucceeded($paymentObject, $transaction);
                break;
            case NotificationEventType::PAYMENT_SUCCEEDED:
                $this->ifSucceeded($paymentObject, $transaction);
                break;
            case NotificationEventType::PAYMENT_CANCELED:
                $this->ifCancelled($paymentObject, $transaction);
                break;
            case NotificationEventType::REFUND_SUCCEEDED:
                $this->ifRefundSucceeded($paymentObject, $transaction);
                break;
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

    private function ifRefundSucceeded(RefundInterface $refundObject, Transaction $transaction): void
    {
        if (isset($refundObject->status) && $refundObject->status === RefundStatus::SUCCEEDED) {
            $order = $transaction->order;
            Log::channel('payments')->info("Возврат оформлен успешно. Заказ {$order->id} уже выполнен. Транзакция is refunded");
            if ($transaction->status !== TransactionStatus::REFUNDED->value) {
                $transaction->update([
                    'status' => TransactionStatus::REFUNDED->value,
                ]);
            }
        }
    }

    private function ifSucceeded(PaymentInterface $paymentObject, Transaction $transaction): void
    {
        if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::SUCCEEDED->value) {
            $order = $transaction->order;
            if ($order->status === OrderStatus::COMPLETED->value) {
                Log::channel('payments')->alert("ДВОЙНАЯ ОПЛАТА: Заказ {$order->id} уже выполнен! Транзакция {$transaction->id} избыточна.");
                $this->gateway->refund($transaction);

                return;
            }

            if ($paymentObject->paid === true) {
                $transaction->update([
                    'status' => TransactionStatus::SUCCEEDED,
                    'payment_method' => $paymentObject->payment_method->type,
                ]);
                $transaction->order()->update([
                    'status' => OrderStatus::COMPLETED,
                ]);
            }

        }
    }

    private function ifWaitingForCapture(PaymentInterface &$paymentObject)
    {
        if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::WAITING_FOR_CAPTURE->value) {
            $paymentObject = $this->gateway->getClient()->capturePayment([
                'amount' => $paymentObject->amount,
            ], $paymentObject->id, uniqid('', true));
        }
    }

    private function ifCancelled(PaymentInterface $paymentObject, Transaction $transaction)
    {
        if (isset($paymentObject->status) && $paymentObject->status === TransactionStatus::CANCELED->value) {
            $transaction->update([
                'status' => TransactionStatus::CANCELED->value,
                'cancellation_details' => $paymentObject->cancellationDetails?->toArray(),
            ]);
            $transaction->order()->update([
                'status' => OrderStatus::FAILED,
            ]);

        }
    }
}
