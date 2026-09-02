<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string order_id
 * @property string gateway_payment_id
 * @property string status
 * @property string payment_method
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
}
