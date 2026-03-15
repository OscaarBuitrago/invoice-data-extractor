<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\OcrStatus;
use App\Enums\ValidationStatus;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class ValidateInvoiceAction
{
    public function handle(Invoice $invoice, array $data): void
    {
        if ($invoice->ocr_status !== OcrStatus::Completed) {
            throw ValidationException::withMessages([
                'invoice' => 'Solo se pueden validar facturas con OCR completado.',
            ]);
        }

        $invoice->update([
            ...$data,
            'validation_status' => ValidationStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
