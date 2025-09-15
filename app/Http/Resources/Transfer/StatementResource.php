<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Resources\Json\JsonResource;

class StatementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'transfer_id' => $this->transfer_id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'created_at' => $this->created_at,
        ];
    }
}
