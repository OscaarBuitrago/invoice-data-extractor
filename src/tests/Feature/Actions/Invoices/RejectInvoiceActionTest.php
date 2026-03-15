<?php

declare(strict_types=1);

use App\Actions\Invoices\RejectInvoiceAction;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;

it('marks invoice as rejected with notes', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();

    app(RejectInvoiceAction::class)->handle($invoice, 'Factura duplicada');

    $invoice->refresh();

    expect($invoice->validation_status)->toBe(ValidationStatus::Rejected)
        ->and($invoice->validation_notes)->toBe('Factura duplicada');
});
