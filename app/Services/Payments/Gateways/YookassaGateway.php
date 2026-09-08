<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
use YooKassa\Model\Payment\PaymentStatus;
use YooKassa\Request\Payments\CreatePaymentResponse;

class YookassaGateway implements PaymentGatewayInterface
{
    public function getClient(): Client
    {
        $client = new Client;
        $client->setAuth(config('services.yookassa.shop_id'), config('services.yookassa.secret_key'));

        return $client;
    }

    /**
     * @param Order $order
     * @param Transaction $transaction
     * @param array{
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
    public function createPayment(Order $order, Transaction $transaction, array $options): string
    {
        $client = $this->getClient();
        $paymentResponse = $client->createPayment([
            'amount' => [
                'value' => $order->amount,
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $options['return_url'],
            ],
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'capture' => false,
            'metadata' => [
                'transaction_id' => $transaction->id
            ],
            'description' => $order->description,
        ], $transaction->id);

        $transaction->update([
            'gateway_payment_id' => $paymentResponse->id,
        ]);

        return $paymentResponse->getConfirmation()->getConfirmationUrl();
    }

    public function refund(Transaction $transaction): bool
    {
        try {
            $client = $this->getClient();

            $response = $client->createRefund([
                'amount' => [
                    'value' => number_format((float)$transaction->order->amount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'metadata' => [
                    'transaction_id' => $transaction->id
                ],
                'payment_id' => $transaction->gateway_payment_id,
            ], Str::ulid());

            if ($response->getStatus() === PaymentStatus::SUCCEEDED) {
                $transaction->update(['status' => 'refunded']);
                Log::channel('payments')->info("Деньги по транзакции {$transaction->id} успешно возвращены клиенту.");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::channel('payments')->error("Ошибка при оформлении возврата по транзакции {$transaction->id}: " . $e->getMessage());
            return false;
        }
    }

    public function validateWebhook(Request $request): bool
    {
        return $request->has('event') && $request->has('object') && $request->has('object.id');
    }
}
