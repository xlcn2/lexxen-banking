<?php

namespace App\DTOs\User;

use App\DTOs\BaseDTO;
use App\Enums\UserStatus;

class CorporateUserDTO extends BaseDTO
{
    public function __construct(
        public readonly string $company_name,
        public readonly string $email,
        public readonly ?string $trading_name = null,
        public readonly ?string $password = null,
        public readonly ?string $cnpj = null,
        public readonly ?UserStatus $status = UserStatus::PENDING_APPROVAL,
    ) {
    }
}
