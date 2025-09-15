<?php

namespace App\DTOs\Account;

use App\DTOs\BaseDTO;
use App\Enums\AccountStatus;

class AccountStatusUpdateDTO extends BaseDTO
{
    public function __construct(
        public readonly AccountStatus $status,
    ) {
    }
}
