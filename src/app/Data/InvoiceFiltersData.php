<?php

declare(strict_types=1);

namespace App\Data;

readonly class InvoiceFiltersData
{
    public function __construct(
        public ?string $type = null,
        public ?string $validationStatus = null,
        public ?string $operationType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?bool $exportedToSage = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? null,
            validationStatus: $data['validation_status'] ?? null,
            operationType: $data['operation_type'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            exportedToSage: isset($data['exported_to_sage']) ? (bool) $data['exported_to_sage'] : null,
        );
    }
}
