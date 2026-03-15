<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\InvoiceType;
use App\Enums\OcrStatus;
use App\Enums\UploadBatchStatus;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Services\GeminiInvoiceExtractorService;
use Throwable;

class ProcessInvoiceOcrAction
{
    public function __construct(
        private readonly GeminiInvoiceExtractorService $service,
    ) {}

    public function handle(Invoice $invoice): void
    {
        $invoice->update(['ocr_status' => OcrStatus::Processing]);

        try {
            $result = $this->service->analyze($invoice->file_path);

            if ($result->invoiceNumber !== null) {
                $isDuplicate = Invoice::withoutGlobalScopes()
                    ->where('client_company_id', $invoice->client_company_id)
                    ->where('invoice_number', $result->invoiceNumber)
                    ->where('id', '!=', $invoice->id)
                    ->whereIn('ocr_status', [OcrStatus::Completed->value, OcrStatus::Duplicate->value])
                    ->exists();

                if ($isDuplicate) {
                    $invoice->update(['ocr_status' => OcrStatus::Duplicate]);
                    $this->updateBatchProgress($invoice->upload_batch_id);

                    return;
                }
            }

            $counterpartyFields = $invoice->type === InvoiceType::Issued
                ? ['recipient_tax_id' => $result->recipientTaxId, 'recipient_name' => $result->recipientName]
                : ['issuer_tax_id' => $result->issuerTaxId, 'issuer_name' => $result->issuerName];

            $invoice->update([
                'ocr_status' => OcrStatus::Completed,
                'ocr_confidence' => $result->confidence,
                'invoice_date' => $result->invoiceDate,
                'invoice_number' => $result->invoiceNumber,
                ...$counterpartyFields,
                'taxable_base' => $result->taxableBase,
                'vat_percentage' => $result->vatPercentage,
                'vat_amount' => $result->vatAmount,
                'irpf_percentage' => $result->irpfPercentage,
                'irpf_amount' => $result->irpfAmount,
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
            ->whereIn('ocr_status', [OcrStatus::Completed->value, OcrStatus::Failed->value, OcrStatus::Duplicate->value])
            ->count();

        $hasErrors = Invoice::withoutGlobalScopes()
            ->where('upload_batch_id', $batchId)
            ->whereIn('ocr_status', [OcrStatus::Failed->value, OcrStatus::Duplicate->value])
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
