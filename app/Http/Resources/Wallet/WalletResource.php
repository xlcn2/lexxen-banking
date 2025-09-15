<?php

namespace App\Http\Resources\Wallet;

use App\Http\Resources\Transfer\StatementResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            'name' => $this->name,
            'balance' => $this->balance,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'account_id' => $this->account_id,
            'statements' => StatementResource::collection($this->whenLoaded('statements')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
