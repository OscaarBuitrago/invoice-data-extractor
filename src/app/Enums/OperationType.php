<?php

declare(strict_types=1);

namespace App\Enums;

enum OperationType: string
{
    case Normal = 'normal';
    case IntraCommunity = 'intra_community';
    case ReverseCharge = 'reverse_charge';
    case Import = 'import';
    case NotSubject = 'not_subject';
}
