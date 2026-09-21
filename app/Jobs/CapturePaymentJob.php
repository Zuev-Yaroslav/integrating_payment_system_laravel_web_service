<?php

namespace App\Jobs;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use YooKassa\Model\Payment\PaymentInterface;

class CapturePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private PaymentInterface $paymentObject,
        private string $idempotenceKey,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewayInterface $gateway): void
    {
        $gateway->getClient()->capturePayment([
            'amount' => $this->paymentObject->amount,
        ], $this->paymentObject->id, $this->idempotenceKey);
    }
}
