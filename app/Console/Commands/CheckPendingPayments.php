<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use YooKassa\Model\Payment\PaymentStatus;

#[Signature('payments:check-pending-payments')]
#[Description('Command description')]
class CheckPendingPayments extends Command
{
    public function __construct(private PaymentService $paymentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingTransactions = Transaction::where('status', PaymentStatus::PENDING)->get();
        $gateway = PaymentGatewayFactory::make();

        foreach ($pendingTransactions as $transaction) {
            try {
                $yookassaPayment = $gateway->getClient()->getPaymentInfo($transaction->gateway_payment_id);
                $actualStatus = $yookassaPayment->getStatus();

                if ($actualStatus === 'canceled' || $actualStatus === 'succeeded') {
                    $transaction->update(['status' => $actualStatus]);
                    $transaction->order->update([
                        'status' => $actualStatus === 'succeeded' ? OrderStatus::COMPLETED : OrderStatus::FAILED
                    ]);
                    continue;
                }

                if ($actualStatus === 'pending' && $transaction->created_at->addMinutes(10)->isPast()) {

                    // Локально переводим в статус отмены, защищая проект от "зависания"
                    $transaction->update(['status' => PaymentStatus::CANCELED]);
                    $transaction->order->update(['status' => OrderStatus::FAILED]);

                    Log::channel('payments')->info("Платеж {$transaction->id} принудительно отменен локально по таймауту Sandbox.");
                }
            } catch (\Exception $e) {
                Log::channel('payments')->error("Ошибка проверки платежа {$transaction->id}: " . $e->getMessage());
            }
        }
    }
}
