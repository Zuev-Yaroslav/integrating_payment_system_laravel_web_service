<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Enums\Yookassa\CancelInitiator;
use App\Enums\Yookassa\CancelReason;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            ::where('status', TransactionStatus::PENDING->value)
            ->chunk(100, function ($pendingTransactions) use ($gateway) {
                foreach ($pendingTransactions as $transaction) {
                    try {
                        $yookassaPayment = $gateway->getClient()->getPaymentInfo($transaction->gateway_payment_id);
                        $actualStatus = $yookassaPayment->getStatus();

                        DB::beginTransaction();
                        if ($actualStatus === TransactionStatus::CANCELED->value || $actualStatus === TransactionStatus::SUCCEEDED->value) {
                            $transaction->update([
                                'status' => $actualStatus,
                                'cancellation_details' => $yookassaPayment->cancellationDetails?->toArray(),
                            ]);
                            $transaction->order->update([
                                'status' => $actualStatus === 'succeeded' ? OrderStatus::COMPLETED : OrderStatus::FAILED
                            ]);
                            continue;
                        }

                        if ($actualStatus === TransactionStatus::PENDING->value && $transaction->created_at->addMinutes(10)->isPast()
                            && config('app.env') === 'local'
                        ) {

                            $transaction->update([
                                'status' => TransactionStatus::CANCELED->value,
                                'cancellation_details' => [
                                    'party' => CancelInitiator::YOO_MONEY->value,
                                    'reason' => CancelReason::EXPIRED_ON_CONFIRMATION->value,
                                ]
                            ]);
                            $transaction->order->update(['status' => OrderStatus::FAILED]);

                            Log::channel('payments')->info("Платеж {$transaction->id} принудительно отменен локально по таймауту Sandbox.");
                        }
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::channel('payments')->error("Ошибка проверки платежа {$transaction->id}: " . $e->getMessage());
                    }
                }
            });
    }
}
