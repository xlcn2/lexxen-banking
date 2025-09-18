<?php

namespace App\DTOs\Transfer;

class TransferDTO
{
    public function __construct(
        public readonly int $source_wallet_id,
        public readonly int $destination_wallet_id,
        public readonly float $amount,
        public readonly ?string $idempotency_key = null
    ) {
    }
}