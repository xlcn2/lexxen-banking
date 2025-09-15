<?php

namespace App\DTOs\User;

use App\DTOs\BaseDTO;
use App\Enums\UserStatus;

class IndividualUserDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?string $cpf = null,
        public readonly ?string $birth_date = null,
        public readonly ?UserStatus $status = UserStatus::PENDING_APPROVAL,
    ) {
    }
}
