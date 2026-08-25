<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Transaction;
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
    public function getClient(): Client
    {
        $client = new Client;
        $client->setAuth(config('services.yookassa.shop_id'), config('services.yookassa.secret_key'));

        return $client;
    }

    /**
     * @param array{
     *      'transaction_id': int,
     *      'return_url': string
     * } $options
     * @return string
     *
     * @throws ApiConnectionException
     * @throws ApiException
     * @throws AuthorizeException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws ForbiddenException
     * @throws InternalServerError
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    public function createPayment(float $amount, string $description, array $options): CreatePaymentResponse
    {
        $client = $this->getClient();

        return $client->createPayment([
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $options['return_url'],
            ],
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'capture' => false,
            'metadata' => [
                'transaction_id' => $options['transaction_id'],
            ],
            'description' => $description,
        ], $options['transaction_id']);
    }

    public function callback(array $data): void
    {
        $notification = match($data['event']) {
            NotificationEventType::PAYMENT_SUCCEEDED => new NotificationSucceeded($data),
            NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE => new NotificationWaitingForCapture($data),
            NotificationEventType::PAYMENT_CANCELED => new NotificationCanceled($data),
        };
        $payment = $notification->getObject();

        if (isset($payment->status) && $payment->status === PaymentStatus::WAITING_FOR_CAPTURE) {
            $this->getClient()->capturePayment([
                'amount' => $payment->amount,
            ], $payment->id, uniqid('', true));
        }

        if (isset($payment->status) && $payment->status === PaymentStatus::SUCCEEDED) {
            if ($payment->paid === true) {
                $metadata = $payment->metadata;
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

        if (isset($payment->status) && $payment->status === PaymentStatus::CANCELED) {
            $metadata = $payment->metadata;
            if (isset($metadata->transaction_id)) {
                $transaction = Transaction::find($metadata->transaction_id);
                $transaction->status = PaymentStatus::CANCELED;
                $transaction->order()->update([
                    'status' => OrderStatus::FAILED,
                ]);
                $transaction->save();
            }

        }
    }
}
