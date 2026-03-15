<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceType;
use App\Enums\OcrStatus;
use App\Enums\OperationType;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $consultancy = Consultancy::factory()->create();

        return [
            'consultancy_id' => $consultancy->id,
            'client_company_id' => ClientCompany::factory()->for($consultancy),
            'upload_batch_id' => UploadBatch::factory()->for($consultancy),
            'user_id' => User::factory()->consultant()->for($consultancy),
            'file_path' => 'invoices/test/factura.pdf',
            'file_name' => 'factura.pdf',
            'type' => InvoiceType::Received,
            'operation_type' => OperationType::Normal,
            'ocr_status' => OcrStatus::Pending,
            'validation_status' => ValidationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(['ocr_status' => OcrStatus::Pending]);
    }

    public function completed(): static
    {
        return $this->state([
            'ocr_status' => OcrStatus::Completed,
            'ocr_confidence' => 0.92,
            'invoice_date' => now()->subDays(5)->toDateString(),
            'invoice_number' => 'FAC-2024-001',
            'issuer_tax_id' => 'B12345678',
            'issuer_name' => 'Proveedor S.L.',
            'taxable_base' => 1000.00,
            'vat_percentage' => 21.00,
            'vat_amount' => 210.00,
            'total' => 1210.00,
        ]);
    }

    public function failed(): static
    {
        return $this->state(['ocr_status' => OcrStatus::Failed]);
    }

    public function lowConfidence(): static
    {
        return $this->state([
            'ocr_status' => OcrStatus::Completed,
            'ocr_confidence' => 0.55,
        ]);
    }
}
