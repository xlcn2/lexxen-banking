<?php

namespace App\DTOs\Transfer;

use App\DTOs\BaseDTO;

class StatementFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $start_date = null,
        public readonly ?string $end_date = null,
        public readonly ?int $page = 1,
        public readonly ?int $per_page = 15,
    ) {
    }
}
