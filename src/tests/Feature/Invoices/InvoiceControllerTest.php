<?php

declare(strict_types=1);

use App\Enums\ValidationStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;

function makeInvoice(array $overrides = []): array
{
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create();
    $invoice = Invoice::factory()->for($consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create($overrides);

    return ['consultancy' => $consultancy, 'user' => $user, 'company' => $company, 'batch' => $batch, 'invoice' => $invoice];
}

function validPayload(): array
{
    return [
        'action' => 'validate',
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
}

it('requires authentication to view invoice', function (): void {
    ['invoice' => $invoice] = makeInvoice();

    $this->get(route('invoices.show', $invoice))->assertRedirect(route('login'));
});

it('requires company context to view invoice', function (): void {
    ['user' => $user, 'invoice' => $invoice] = makeInvoice();
    $this->actingAs($user);

    $this->get(route('invoices.show', $invoice))->assertRedirect(route('context.select'));
});

it('renders validation page with invoice data', function (): void {
    ['user' => $user, 'company' => $company, 'invoice' => $invoice] = makeInvoice();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertJson(['component' => 'Invoices/Validation']);
});

it('validates required fields on update', function (): void {
    ['user' => $user, 'company' => $company, 'invoice' => $invoice] = makeInvoice();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->put(route('invoices.update', $invoice), ['action' => 'validate'])
        ->assertSessionHasErrors(['invoice_date', 'invoice_number', 'issuer_tax_id', 'taxable_base', 'total']);
});

it('validates invoice and redirects to next pending', function (): void {
    ['user' => $user, 'company' => $company, 'batch' => $batch, 'invoice' => $invoice] = makeInvoice();
    $next = Invoice::factory()->for($invoice->consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->put(route('invoices.update', $invoice), validPayload())
        ->assertRedirect(route('invoices.show', $next));

    expect($invoice->fresh()->validation_status)->toBe(ValidationStatus::Validated);
});

it('redirects to dashboard when no more pending invoices', function (): void {
    ['user' => $user, 'company' => $company, 'invoice' => $invoice] = makeInvoice();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->put(route('invoices.update', $invoice), validPayload())
        ->assertRedirect(route('dashboard'));
});

it('rejects invoice and redirects to next pending', function (): void {
    ['user' => $user, 'company' => $company, 'batch' => $batch, 'invoice' => $invoice] = makeInvoice();
    $next = Invoice::factory()->for($invoice->consultancy)->for($company)->for($batch, 'uploadBatch')->for($user)->completed()->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->put(route('invoices.update', $invoice), [
        'action' => 'reject',
        'validation_notes' => 'Duplicada',
    ])->assertRedirect(route('invoices.show', $next));

    expect($invoice->fresh()->validation_status)->toBe(ValidationStatus::Rejected);
});

it('returns 404 if invoice belongs to another consultancy', function (): void {
    ['invoice' => $invoice] = makeInvoice();
    $otherUser = User::factory()->consultant()->create();
    $otherCompany = ClientCompany::factory()->for($otherUser->consultancy)->create();
    $this->actingAs($otherUser);
    session(['active_company_id' => $otherCompany->id]);

    $this->get(route('invoices.show', $invoice))->assertNotFound();
});
