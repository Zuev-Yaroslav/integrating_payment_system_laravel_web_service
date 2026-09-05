<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\Transaction\TransactionResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'transactions' => TransactionResource::collection($this->transactions)->resolve(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
