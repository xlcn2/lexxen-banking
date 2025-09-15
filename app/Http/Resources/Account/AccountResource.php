<?php

namespace App\Http\Resources\Account;

use App\Http\Resources\Wallet\WalletResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'number' => $this->number,
            'status' => $this->status->value,
            'total_balance' => $this->total_balance,
            'wallets' => WalletResource::collection($this->whenLoaded('wallets')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
