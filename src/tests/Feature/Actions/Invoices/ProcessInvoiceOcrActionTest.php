<?php

declare(strict_types=1);

use App\Actions\Invoices\ProcessInvoiceOcrAction;
use App\Data\OcrResultData;
use App\Enums\OcrStatus;
use App\Enums\UploadBatchStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;
use App\Services\AzureFormRecognizerService;

it('updates invoice fields when ocr succeeds', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create(['total_invoices' => 1]);
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->create();

    $ocrResult = new OcrResultData(
        invoiceDate: '2024-03-01',
        invoiceNumber: 'FAC-001',
        issuerTaxId: 'B12345678',
        issuerName: 'Proveedor S.L.',
        taxableBase: 1000.00,
        vatPercentage: 21.00,
        vatAmount: 210.00,
        irpfPercentage: 15.00,
        irpfAmount: 150.00,
        total: 1210.00,
        confidence: 0.92,
        raw: ['result' => 'data'],
    );

    $this->mock(AzureFormRecognizerService::class)
        ->shouldReceive('analyze')
        ->once()
        ->andReturn($ocrResult);

    app(ProcessInvoiceOcrAction::class)->handle($invoice);

    $invoice->refresh();

    expect($invoice->ocr_status)->toBe(OcrStatus::Completed)
        ->and($invoice->ocr_confidence)->toBe(0.92)
        ->and($invoice->invoice_number)->toBe('FAC-001')
        ->and($invoice->issuer_tax_id)->toBe('B12345678')
        ->and($invoice->issuer_name)->toBe('Proveedor S.L.')
        ->and((float) $invoice->taxable_base)->toBe(1000.00)
        ->and((float) $invoice->total)->toBe(1210.00);
});

it('marks invoice as failed when service throws', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create(['total_invoices' => 1]);
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->create();

    $this->mock(AzureFormRecognizerService::class)
        ->shouldReceive('analyze')
        ->once()
        ->andThrow(new RuntimeException('Azure error'));

    app(ProcessInvoiceOcrAction::class)->handle($invoice);

    expect($invoice->fresh()->ocr_status)->toBe(OcrStatus::Failed);
});

it('updates batch processed count after ocr', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create(['total_invoices' => 2]);
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->create();
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();

    $ocrResult = new OcrResultData(
        invoiceDate: null, invoiceNumber: null, issuerTaxId: null, issuerName: null,
        taxableBase: null, vatPercentage: null, vatAmount: null, irpfPercentage: null, irpfAmount: null, total: null,
        confidence: 0.80, raw: [],
    );

    $this->mock(AzureFormRecognizerService::class)
        ->shouldReceive('analyze')
        ->once()
        ->andReturn($ocrResult);

    app(ProcessInvoiceOcrAction::class)->handle($invoice);

    $batch->refresh();

    expect($batch->processed_invoices)->toBe(2)
        ->and($batch->status)->toBe(UploadBatchStatus::Completed);
});
