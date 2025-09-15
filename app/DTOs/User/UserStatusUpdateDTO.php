<?php

namespace App\DTOs\User;

use App\DTOs\BaseDTO;
use App\Enums\UserStatus;

class UserStatusUpdateDTO extends BaseDTO
{
    public function __construct(
        public readonly UserStatus $status,
    ) {
    }
}
