<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\OcrStatus;
use App\Enums\UploadBatchStatus;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Services\AzureFormRecognizerService;
use Throwable;

class ProcessInvoiceOcrAction
{
    public function __construct(
        private readonly AzureFormRecognizerService $service,
    ) {}

    public function handle(Invoice $invoice): void
    {
        $invoice->update(['ocr_status' => OcrStatus::Processing]);

        try {
            $result = $this->service->analyze($invoice->file_path);

            $invoice->update([
                'ocr_status' => OcrStatus::Completed,
                'ocr_confidence' => $result->confidence,
                'invoice_date' => $result->invoiceDate,
                'invoice_number' => $result->invoiceNumber,
                'issuer_tax_id' => $result->issuerTaxId,
                'issuer_name' => $result->issuerName,
                'taxable_base' => $result->taxableBase,
                'vat_percentage' => $result->vatPercentage,
                'vat_amount' => $result->vatAmount,
                'total' => $result->total,
                'ocr_raw' => $result->raw,
            ]);
        } catch (Throwable) {
            $invoice->update(['ocr_status' => OcrStatus::Failed]);
        }

        $this->updateBatchProgress($invoice->upload_batch_id);
    }

    private function updateBatchProgress(string $batchId): void
    {
        $batch = UploadBatch::withoutGlobalScopes()->findOrFail($batchId);

        $processed = Invoice::withoutGlobalScopes()
            ->where('upload_batch_id', $batchId)
            ->whereIn('ocr_status', [OcrStatus::Completed->value, OcrStatus::Failed->value])
            ->count();

        $hasErrors = Invoice::withoutGlobalScopes()
            ->where('upload_batch_id', $batchId)
            ->where('ocr_status', OcrStatus::Failed->value)
            ->exists();

        $status = match (true) {
            $processed >= $batch->total_invoices && $hasErrors => UploadBatchStatus::WithErrors,
            $processed >= $batch->total_invoices => UploadBatchStatus::Completed,
            default => UploadBatchStatus::Processing,
        };

        $batch->update([
            'processed_invoices' => $processed,
            'status' => $status,
        ]);
    }
}
