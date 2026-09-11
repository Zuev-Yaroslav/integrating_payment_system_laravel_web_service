<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\Transactions\YooKassaTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessYooKassaWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $payload,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(YooKassaTransactionService $paymentService): void
    {
        $transaction = Transaction::query()
            ->with('order')
            ->findOrFail($this->payload['object']['metadata']['transaction_id']);

        $paymentService->callback($this->payload, $transaction);
    }
}
