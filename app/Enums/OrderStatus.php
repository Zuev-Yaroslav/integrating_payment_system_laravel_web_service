<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [
                self::COMPLETED,
                self::FAILED,
            ]),
            self::COMPLETED, self::FAILED => false,
        };
    }
}
