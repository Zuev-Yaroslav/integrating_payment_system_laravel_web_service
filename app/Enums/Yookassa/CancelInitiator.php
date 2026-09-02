<?php

namespace App\Enums\Yookassa;

enum CancelInitiator: string
{
    case MERCHANT = 'merchant';
    case YOO_MONEY = 'yoo_money';
    case PAYMENT_NETWORK = 'payment_network';
}
