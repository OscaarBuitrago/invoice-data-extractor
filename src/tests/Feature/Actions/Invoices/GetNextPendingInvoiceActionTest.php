<?php

declare(strict_types=1);

use App\Actions\Invoices\GetNextPendingInvoiceAction;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;

it('returns next pending invoice from same batch', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();

    $current = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    $next = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();

    $result = app(GetNextPendingInvoiceAction::class)->handle($batch, $current);

    expect($result->id)->toBe($next->id);
});

it('returns null when no more pending invoices in batch', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();

    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create([
        'validation_status' => ValidationStatus::Validated,
    ]);

    $result = app(GetNextPendingInvoiceAction::class)->handle($batch, $invoice);

    expect($result)->toBeNull();
});
