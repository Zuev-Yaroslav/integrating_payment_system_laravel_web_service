<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
