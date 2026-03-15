<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case Issued = 'issued';
    case Received = 'received';
}
