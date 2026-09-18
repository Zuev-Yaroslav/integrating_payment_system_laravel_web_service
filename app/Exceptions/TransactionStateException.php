<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\invalidTransition;
use Exception;
use Illuminate\Support\Facades\Log;

class TransactionStateException extends Exception implements invalidTransition
{
    protected $code = 'TX_STATE_VIOLATION';

    /**
     * Report the exception.
     */
    public function report(): void
    {
        Log::channel('payments')->error($this->getMessage(). ' ' . $this->getCode());
    }

    public static function invalidTransition(string $txId, string $from, string $to): self
    {
        return new self(
            "State Machine Violation: Нелегальный переход статуса транзакции {$txId} из [{$from}] в [{$to}]."
        );
    }
}
