<?php

namespace App\DTOs\Account;

use App\DTOs\BaseDTO;
use App\Enums\AccountStatus;

class AccountDTO extends BaseDTO
{
    public function __construct(
        public readonly string $number,
        public readonly ?AccountStatus $status = AccountStatus::ACTIVE,
    ) {
    }
}
