<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;
use YooKassa\Common\Exceptions\ApiConnectionException;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Common\Exceptions\AuthorizeException;
use YooKassa\Common\Exceptions\BadApiRequestException;
use YooKassa\Common\Exceptions\ExtensionNotFoundException;
use YooKassa\Common\Exceptions\ForbiddenException;
use YooKassa\Common\Exceptions\InternalServerError;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Common\Exceptions\ResponseProcessingException;
use YooKassa\Common\Exceptions\TooManyRequestsException;
use YooKassa\Common\Exceptions\UnauthorizedException;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Payment\PaymentStatus;
use YooKassa\Request\Payments\CreatePaymentResponse;

class PaymentService
{
    public function callback(array $data): void
    {
        $gateway = PaymentGatewayFactory::make();
        $notification = match($data['event']) {
            NotificationEventType::PAYMENT_SUCCEEDED => new NotificationSucceeded($data),
            NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE => new NotificationWaitingForCapture($data),
            NotificationEventType::PAYMENT_CANCELED => new NotificationCanceled($data),
        };
        $paymentObject = $notification->getObject();
        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::WAITING_FOR_CAPTURE) {
            $paymentObject = $gateway->getClient()->capturePayment([
                'amount' => $paymentObject->amount,
            ], $paymentObject->id, uniqid('', true));
        }

        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::SUCCEEDED)
        {
            if ($paymentObject->paid === true) {
                $metadata = $paymentObject->metadata;
                if (isset($metadata->transaction_id)) {
                    $transaction = Transaction::find($metadata->transaction_id);
                    $transaction->status = PaymentStatus::SUCCEEDED;
                    $transaction->order()->update([
                        'status' => OrderStatus::COMPLETED,
                    ]);
                    $transaction->save();
                }
            }
        }

        if (isset($paymentObject->status) && $paymentObject->status === PaymentStatus::CANCELED) {
            $metadata = $paymentObject->metadata;
            if (isset($metadata->transaction_id)) {
                $transaction = Transaction::findOrFail($metadata->transaction_id);
                $transaction->status = PaymentStatus::CANCELED;
                $transaction->order()->update([
                    'status' => OrderStatus::FAILED,
                ]);
                $transaction->save();
            }

        }
    }
}
