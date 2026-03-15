<?php

declare(strict_types=1);

namespace App\Data;

readonly class OcrResultData
{
    public function __construct(
        public ?string $invoiceDate,
        public ?string $invoiceNumber,
        public ?string $issuerTaxId,
        public ?string $issuerName,
        public ?float $taxableBase,
        public ?float $vatPercentage,
        public ?float $vatAmount,
        public ?float $irpfPercentage,
        public ?float $irpfAmount,
        public ?float $total,
        public float $confidence,
        public array $raw,
    ) {}
}
