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
        'raw_payload',
        'processed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
