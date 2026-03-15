<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\OcrStatus;
use App\Enums\ValidationStatus;
use App\Models\Invoice;
use App\Models\UploadBatch;

class GetNextPendingInvoiceAction
{
    public function handle(UploadBatch $batch, Invoice $current): ?Invoice
    {
        return Invoice::withoutGlobalScopes()
            ->where('upload_batch_id', $batch->id)
            ->where('id', '!=', $current->id)
            ->where('ocr_status', OcrStatus::Completed)
            ->where('validation_status', ValidationStatus::Pending)
            ->oldest()
            ->first();
    }
}
