<?php

declare(strict_types=1);

namespace App\Enums;

enum OcrStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
