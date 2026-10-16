<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayFactory;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use YooKassa\Common\Exceptions\NotFoundException;

#[Signature('payments:destroy-non-existent-payments')]
#[Description('Command description')]
class DestroyNonExistentPayments extends Command
{
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
                        $gateway->getClient()->getPaymentInfo($transaction->gateway_payment_id);

                    } catch (NotFoundException $exception) {
                        Log::channel('destroy_non_existent_payments')->error("Платёж в YooKassa не найден и будет удалён: " . $exception->getMessage());
                        $transaction->delete();
                        $this->info("Транзакция {$transaction->id} удалена");
                    } catch (Exception $exception) {
                        Log::channel('destroy_non_existent_payments')->error($exception->getMessage());
                    }
                }
            });
    }
}
