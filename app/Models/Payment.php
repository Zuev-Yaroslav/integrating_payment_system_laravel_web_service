<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_id',
        'gateway_payment_id',
        'status',
        'payment_method',
        'error_message',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
