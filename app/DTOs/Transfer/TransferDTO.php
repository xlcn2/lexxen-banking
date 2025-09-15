<?php

namespace App\DTOs\Transfer;

use App\DTOs\BaseDTO;

class TransferDTO extends BaseDTO
{
    public function __construct(
        public readonly int $source_wallet_id,
        public readonly int $destination_wallet_id,
        public readonly float $amount,
        public readonly ?string $idempotency_key = null,
    ) {
    }
}
