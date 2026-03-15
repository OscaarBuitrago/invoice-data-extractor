<?php

declare(strict_types=1);

use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    Excel::fake();
});

it('requires authentication', function (): void {
    $this->post(route('sage-exports.store'))->assertRedirect(route('login'));
});

it('requires company context', function (): void {
    $user = User::factory()->consultant()->create();
    $this->actingAs($user);

    $this->post(route('sage-exports.store'))->assertRedirect(route('context.select'));
});

it('downloads excel for validated invoices', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->post(route('sage-exports.store'), ['invoice_ids' => [$invoice->id]])
        ->assertOk();

    expect($invoice->fresh()->exported_to_sage)->toBeTrue();
});

it('returns 422 if no validated invoices', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)
        ->completed()->create(['validation_status' => ValidationStatus::Pending]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->postJson(route('sage-exports.store'), ['invoice_ids' => [$invoice->id]])
        ->assertUnprocessable();
});

it('cannot export invoices from another consultancy', function (): void {
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $userA = User::factory()->consultant()->for($consultancyA)->create();
    $companyA = ClientCompany::factory()->for($consultancyA)->create();
    $companyB = ClientCompany::factory()->for($consultancyB)->create();
    $userB = User::factory()->consultant()->for($consultancyB)->create();
    $batchB = UploadBatch::factory()->for($consultancyB)->for($companyB)->for($userB)->create();
    $invoiceB = Invoice::factory()->for($consultancyB)->for($companyB)->for($batchB, 'uploadBatch')->for($userB)
        ->completed()->create(['validation_status' => ValidationStatus::Validated]);

    $this->actingAs($userA);
    session(['active_company_id' => $companyA->id]);

    // Invoice belongs to consultancy B — ConsultancyScope prevents finding it,
    // so it's treated as if no validated invoices were selected
    $this->postJson(route('sage-exports.store'), ['invoice_ids' => [$invoiceB->id]])
        ->assertUnprocessable();
});
