<?php

declare(strict_types=1);

use App\Actions\Invoices\ListInvoicesAction;
use App\Data\InvoiceFiltersData;
use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;

function setupListTest(): array
{
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();

    return ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch];
}

it('returns paginated completed invoices for active company', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->count(3)->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->pending()->create();

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData);

    expect($result->total())->toBe(3);
});

it('filters by type', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['type' => 'received']);
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['type' => 'issued']);

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData(type: 'received'));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->type->value)->toBe('received');
});

it('filters by validation status', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['validation_status' => ValidationStatus::Validated]);
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['validation_status' => ValidationStatus::Pending]);

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData(validationStatus: 'validated'));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->validation_status)->toBe(ValidationStatus::Validated);
});

it('filters by date range', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['invoice_date' => '2024-01-15']);
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['invoice_date' => '2024-06-15']);

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData(dateFrom: '2024-06-01', dateTo: '2024-12-31'));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->invoice_date->format('Y-m-d'))->toBe('2024-06-15');
});

it('filters by operation type', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['operation_type' => 'normal']);
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['operation_type' => 'intra_community']);

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData(operationType: 'intra_community'));

    expect($result->total())->toBe(1);
});

it('filters by exported to sage', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['exported_to_sage' => true]);
    Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create(['exported_to_sage' => false]);

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData(exportedToSage: false));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->exported_to_sage)->toBeFalse();
});

it('only returns invoices from the active company', function (): void {
    ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch] = setupListTest();
    $otherCompany = ClientCompany::factory()->for($consultancy)->create();
    $otherBatch = UploadBatch::factory()->for($consultancy)->for($otherCompany)->for($user)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    Invoice::factory()->count(2)->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    Invoice::factory()->for($consultancy)->for($otherCompany)->for($otherBatch, 'uploadBatch')->for($user)->completed()->create();

    $result = app(ListInvoicesAction::class)->handle(new InvoiceFiltersData);

    expect($result->total())->toBe(2);
});
