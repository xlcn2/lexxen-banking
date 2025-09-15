<?php

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
