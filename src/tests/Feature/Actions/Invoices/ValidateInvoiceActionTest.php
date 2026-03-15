<?php

declare(strict_types=1);

use App\Actions\Invoices\ValidateInvoiceAction;
use App\Enums\OcrStatus;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('marks invoice as validated and updates fields', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();

    $data = [
        'invoice_date' => '2024-03-01',
        'invoice_number' => 'FAC-001',
        'issuer_tax_id' => 'B12345678',
        'issuer_name' => 'Proveedor S.L.',
        'taxable_base' => 1000.00,
        'vat_percentage' => 21.00,
        'vat_amount' => 210.00,
        'irpf_percentage' => null,
        'irpf_amount' => null,
        'total' => 1210.00,
        'type' => 'received',
        'operation_type' => 'normal',
        'validation_notes' => null,
    ];

    app(ValidateInvoiceAction::class)->handle($invoice, $data);

    $invoice->refresh();

    expect($invoice->validation_status)->toBe(ValidationStatus::Validated)
        ->and($invoice->validated_at)->not->toBeNull()
        ->and($invoice->invoice_number)->toBe('FAC-001')
        ->and((float) $invoice->total)->toBe(1210.00);
});

it('throws if invoice ocr is not completed', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->create([
        'ocr_status' => OcrStatus::Pending,
    ]);

    expect(fn () => app(ValidateInvoiceAction::class)->handle($invoice, []))
        ->toThrow(ValidationException::class);
});
