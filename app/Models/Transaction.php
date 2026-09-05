<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use YooKassa\Model\Payment\PaymentStatus;

/**
 * @property string $id
 * @property string order_id
 * @property string gateway_payment_id
 * @property string status
 * @property string payment_method
 * @property array{
 *     'party': string,
 *     'reason': string,
 * } cancellation_details
 * @property string error_message
 * @property string created_at
 * @property string updated_at
 * @mixin Builder
 */

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'cancellation_details' => 'array',
        ];
    }

    protected $fillable = [
        'order_id',
        'gateway_payment_id',
        'status',
        'payment_method',
        'cancellation_details',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getErrorMessageAttribute(): ?string
    {
        if ($this->cancellation_details) {
            $party = $this->cancellation_details['party'];
            $reason = $this->cancellation_details['reason'];

            return "Инициатор: {$party}. Причина: {$reason}.";
        }

        if ($this->status === PaymentStatus::CANCELED) {
            return 'Платеж отклонен системой платежей';
        }

        return null;
    }
}
