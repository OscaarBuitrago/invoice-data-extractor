<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\ValidationStatus;
use App\Models\Invoice;

class RejectInvoiceAction
{
    public function handle(Invoice $invoice, ?string $notes): void
    {
        $invoice->update([
            'validation_status' => ValidationStatus::Rejected,
            'validation_notes' => $notes,
        ]);
    }
}
