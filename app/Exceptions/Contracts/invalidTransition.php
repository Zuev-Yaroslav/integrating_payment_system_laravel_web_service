<?php

namespace App\Exceptions\Contracts;

interface invalidTransition
{
    public static function invalidTransition(string $txId, string $from, string $to): self;
}
