<?php

namespace App\Enums;

enum StatementType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
