<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case WAITING_FOR_CAPTURE = 'waiting_for_capture';
    case SUCCEEDED = 'succeeded';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';
}
