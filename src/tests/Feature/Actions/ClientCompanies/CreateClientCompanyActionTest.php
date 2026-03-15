<?php

declare(strict_types=1);

use App\Actions\ClientCompanies\CreateClientCompanyAction;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;

it('creates a client company with correct data', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();
    $this->actingAs($admin);

    $data = [
        'name' => 'Empresa Acme S.L.',
        'tax_id' => 'B11223344',
    ];

    // Act
    $company = app(CreateClientCompanyAction::class)->handle($data);

    // Assert
    expect($company)->toBeInstanceOf(ClientCompany::class);
    expect($company->name)->toBe('Empresa Acme S.L.');
    expect($company->tax_id)->toBe('B11223344');
    expect($company->consultancy_id)->toBe($consultancy->id);
    $this->assertDatabaseHas('client_companies', ['tax_id' => 'B11223344']);
});
