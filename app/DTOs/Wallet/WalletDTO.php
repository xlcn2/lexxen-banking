<?php

namespace App\DTOs\Wallet;

use App\DTOs\BaseDTO;
use App\Enums\WalletStatus;
use App\Enums\WalletType;

class WalletDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?WalletType $type = WalletType::WALLET,
        public readonly ?WalletStatus $status = WalletStatus::ACTIVE,
        public readonly ?float $balance = 0,
    ) {
    }
}
