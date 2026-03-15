<?php

declare(strict_types=1);

use App\Actions\Invoices\ExportInvoicesToSageAction;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\SageExport;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    Excel::fake();
});

function sageSetup(): array
{
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();

    return ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch];
}

it('generates excel download and marks invoice as exported', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = sageSetup();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);

    app(ExportInvoicesToSageAction::class)->handle([$invoice->id]);

    // Verify side effects: invoice marked as exported and record created
    expect($invoice->fresh()->exported_to_sage)->toBeTrue();
    expect(SageExport::withoutGlobalScopes()->count())->toBe(1);
});

it('only exports validated invoices', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = sageSetup();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $validated = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);
    $pending = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Pending]);

    app(ExportInvoicesToSageAction::class)->handle([$validated->id, $pending->id]);

    $export = SageExport::withoutGlobalScopes()->latest()->first();
    expect($export->total_invoices)->toBe(1);
});

it('throws if no validated invoices among selection', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = sageSetup();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $pending = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Pending]);

    expect(fn () => app(ExportInvoicesToSageAction::class)->handle([$pending->id]))
        ->toThrow(ValidationException::class);
});

it('marks invoices as exported after download', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = sageSetup();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);

    app(ExportInvoicesToSageAction::class)->handle([$invoice->id]);

    expect($invoice->fresh()->exported_to_sage)->toBeTrue()
        ->and($invoice->fresh()->exported_to_sage_at)->not->toBeNull();
});

it('creates a sage export record', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = sageSetup();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $invoices = Invoice::factory()->count(2)->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);

    app(ExportInvoicesToSageAction::class)->handle($invoices->pluck('id')->toArray());

    $export = SageExport::withoutGlobalScopes()->latest()->first();
    expect($export->total_invoices)->toBe(2)
        ->and($export->user_id)->toBe($user->id);
});
