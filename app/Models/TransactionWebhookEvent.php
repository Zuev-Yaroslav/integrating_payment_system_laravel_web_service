<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 */
class TransactionWebhookEvent extends Model
{
    protected $fillable = [
        'gateway_payment_id',
        'event_type',
        'transaction_id',
        'processing_status',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
