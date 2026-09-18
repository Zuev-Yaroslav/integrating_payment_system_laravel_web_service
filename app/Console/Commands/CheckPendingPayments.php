<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Enums\Yookassa\CancelInitiator;
use App\Enums\Yookassa\CancelReason;
use App\Exceptions\OrderStateException;
use App\Exceptions\TransactionStateException;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[Signature('payments:check-pending-payments')]
#[Description('Command description')]
class CheckPendingPayments extends Command
{
    public function __construct(private YooKassaTransactionService $paymentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gateway = PaymentGatewayFactory::make();
        Transaction
            ::query()
            ->where('status', TransactionStatus::PENDING->value)
            ->take(50)
            ->chunk(10, function ($pendingTransactions) use ($gateway) {
                foreach ($pendingTransactions as $transaction) {

                    try {
                        $this->info("Транзакция {$transaction->id}, шлюз: {$transaction->gateway_payment_id}");
                        $yookassaPayment = $gateway->getClient()->getPaymentInfo($transaction->gateway_payment_id);
                        $actualStatus = $yookassaPayment->getStatus();
                        $order = $transaction->order;
                        $refundedAmount = $yookassaPayment->getRefundedAmount();

                        $is10MinutesPast = $transaction->created_at->addMinutes(10)->isPast();
                    } catch (\Exception $exception) {
                        Log::channel('payments')->error($exception->getMessage());

                        continue;
                    }

                    try {
                        DB::beginTransaction();
                        if (
                            (($actualStatus === TransactionStatus::CANCELED->value ||
                                    $actualStatus === TransactionStatus::SUCCEEDED->value) && ((float)$refundedAmount?->value <= 0))
                        ) {
                            $transaction->update([
                                'status' => $actualStatus,
                                'cancellation_details' => $yookassaPayment->cancellationDetails?->toArray(),
                            ]);
                            if ($order->status === OrderStatus::PENDING->value) {
                                $order->update([
                                    'status' => $actualStatus === 'succeeded' ? OrderStatus::COMPLETED : OrderStatus::FAILED
                                ]);
                            }

                            DB::commit();
                            continue;
                        }

                        if ($refundedAmount && (float)$refundedAmount->value > 0) {
                            Log::channel('payments')->info("Планировщик: По данной транзакции деньги были возвращены. Меняем статус на refunded");
                            $transaction->update([
                                'status' => TransactionStatus::REFUNDED->value,
                            ]);
                        }

                        if ($actualStatus === TransactionStatus::WAITING_FOR_CAPTURE->value && $is10MinutesPast) {
                            Log::channel('payments')->info("Планировщик: Истек таймаут 10 минут для ХОЛДА транзакции {$transaction->id}. Отменяем заморозку средств.");
                            DB::commit();
                            $gateway->getClient()->cancelPayment($transaction->gateway_payment_id, Str::ulid());

                            continue;
                        }

                        if ($actualStatus === TransactionStatus::PENDING->value && $is10MinutesPast
                            && app()->environment('local')
                        ) {
                            $transaction->update([
                                'status' => TransactionStatus::CANCELED->value,
                                'cancellation_details' => [
                                    'party' => CancelInitiator::YOO_MONEY->value,
                                    'reason' => CancelReason::EXPIRED_ON_CONFIRMATION->value,
                                ]
                            ]);
                            if (OrderStatus::from($order->status)->canTransitionTo(OrderStatus::FAILED)) {
                                $order->update(['status' => OrderStatus::FAILED]);
                            }

                            Log::channel('payments')->info("Платеж {$transaction->id} принудительно отменен локально по таймауту Sandbox.");
                            DB::commit();
                            continue;
                        }
                        DB::commit();
                    } catch (TransactionStateException|OrderStateException $e) {
                        DB::rollBack();
                        Log::channel('payments')->info(
                            "Планировщик: Изменение статуса пропущено для Транзакции {$transaction->id}. " .
                            "Контекст: Вебхук закоммитил данные раньше Крона. Код: {$e->getCode()}. Cообщение: {$e->getMessage()}"
                        );

                        continue;

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::channel('payments')->error("Критическая ошибка проверки платежа {$transaction->id}: " . $e->getMessage());
                        throw $e;
                    }
                }
            });
    }
}
