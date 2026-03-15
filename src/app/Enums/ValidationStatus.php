<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';
}
