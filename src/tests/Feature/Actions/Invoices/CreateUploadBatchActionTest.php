<?php

declare(strict_types=1);

use App\Actions\Invoices\CreateUploadBatchAction;
use App\Enums\OcrStatus;
use App\Enums\UploadBatchStatus;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('creates upload batch with correct data', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = [
        UploadedFile::fake()->create('factura1.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('factura2.pdf', 100, 'application/pdf'),
    ];

    $batch = app(CreateUploadBatchAction::class)->handle($files);

    expect($batch->consultancy_id)->toBe($consultancy->id)
        ->and($batch->client_company_id)->toBe($company->id)
        ->and($batch->user_id)->toBe($user->id)
        ->and($batch->total_invoices)->toBe(2)
        ->and($batch->processed_invoices)->toBe(0)
        ->and($batch->status)->toBe(UploadBatchStatus::Processing);
});

it('creates one invoice per uploaded file', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = [
        UploadedFile::fake()->create('factura1.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('factura2.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('factura3.pdf', 100, 'application/pdf'),
    ];

    $batch = app(CreateUploadBatchAction::class)->handle($files);

    expect($batch->invoices)->toHaveCount(3);

    $batch->invoices->each(function ($invoice): void {
        expect($invoice->ocr_status)->toBe(OcrStatus::Pending)
            ->and($invoice->validation_status)->toBe(ValidationStatus::Pending);
    });
});

it('stores files and saves correct file names', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $file = UploadedFile::fake()->create('mi-factura.pdf', 100, 'application/pdf');

    $batch = app(CreateUploadBatchAction::class)->handle([$file]);

    $invoice = $batch->invoices->first();

    expect($invoice->file_name)->toBe('mi-factura.pdf')
        ->and($invoice->file_path)->not->toBeEmpty();

    Storage::disk('local')->assertExists($invoice->file_path);
});
