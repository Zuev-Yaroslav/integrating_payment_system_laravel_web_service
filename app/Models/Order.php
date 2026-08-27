<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin Builder<Order>
 *
 * @property string $id
 * @property string $description
 * @property string currency
 * @property float amount
 * @property string updated_at
 * @property string created_at
 * @property string status
 * @property int user_id
 * @property-read Transaction|null $transaction
 */
class Order extends Model
{
    use HasUlids;

    protected $fillable = [
        'description',
        'user_id',
        'amount',
        'status',
    ];

    /**
     * @return HasOne<Transaction, $this>
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
