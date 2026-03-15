<?php

declare(strict_types=1);

namespace App\Enums;

enum UploadBatchStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case WithErrors = 'with_errors';
}
