<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case WAITING_FOR_CAPTURE = 'waiting_for_capture';
    case SUCCEEDED = 'succeeded';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [
                    self::WAITING_FOR_CAPTURE,
                    self::SUCCEEDED,
                    self::CANCELED,
                    self::REFUNDED,
                ]),
            self::WAITING_FOR_CAPTURE => in_array($newStatus, [
                self::SUCCEEDED,
                self::CANCELED,
            ]),
            self::SUCCEEDED => $newStatus === self::REFUNDED,
            self::CANCELED, self::REFUNDED => false,
        };
    }
}
