<?php

namespace App\Http\Resources\Transfer;

use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
            'source_wallet_id' => $this->source_wallet_id,
            'destination_wallet_id' => $this->destination_wallet_id,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
