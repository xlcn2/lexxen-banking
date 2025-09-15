<?php

namespace App\DTOs\Wallet;

use App\DTOs\BaseDTO;
use App\Enums\WalletStatus;

class WalletStatusUpdateDTO extends BaseDTO
{
    public function __construct(
        public readonly WalletStatus $status,
    ) {
    }
}
